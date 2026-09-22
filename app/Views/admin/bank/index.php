<?= $this->extend('layout/admin') ?>
<?= $this->section('content') ?>

<h1><?= esc($title) ?></h1>

<div class="card">
  <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.5rem;">
    <h2 style="margin:0;">Connected Accounts</h2>
    <button class="btn" id="plaid-connect-btn">+ Connect Bank</button>
  </div>

  <?php if (empty($connections)): ?>
    <p class="muted" style="margin-top:.8rem;">No bank accounts connected yet.</p>
  <?php else: ?>
    <table style="margin-top:.8rem;">
      <thead>
        <tr>
          <th>Account</th>
          <th>Last 4</th>
          <th>Item ID</th>
          <th>Connected</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($connections as $c): ?>
        <tr>
          <td><?= esc($c['name']) ?></td>
          <td><?= $c['mask'] ? '****' . esc($c['mask']) : '—' ?></td>
          <td><code style="font-size:.8rem;"><?= esc(substr($c['item_id'], 0, 24)) ?>…</code></td>
          <td class="muted"><?= esc($c['created_at']) ?></td>
        </tr>
        <?php endforeach ?>
      </tbody>
    </table>
  <?php endif ?>

  <div id="plaid-status"></div>
</div>

<div class="card">
  <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.5rem;">
    <h2 style="margin:0;">Recent Transactions <small class="muted">(last 30 days)</small></h2>
    <?php if (!empty($connections)): ?>
    <button class="btn ghost" id="sync-btn">Sync Now</button>
    <?php endif ?>
  </div>

  <?php if (empty($transactions)): ?>
    <p class="muted" style="margin-top:.8rem;">No transactions yet. Connect a bank and run Sync.</p>
  <?php else: ?>
    <table style="margin-top:.8rem;">
      <thead>
        <tr>
          <th>Date</th>
          <th>Description</th>
          <th>Category</th>
          <th style="text-align:right;">Amount</th>
          <th>Status</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($transactions as $t): ?>
        <tr>
          <td><?= esc($t['date']) ?></td>
          <td><?= esc($t['name']) ?></td>
          <td><?= $t['category'] ? '<span class="pill">' . esc($t['category']) . '</span>' : '<span class="muted">—</span>' ?></td>
          <td style="text-align:right;<?= $t['amount'] < 0 ? 'color:#2a7a2a;' : '' ?>">
            <?= fbk_money(abs($t['amount'])) ?>
            <?= $t['amount'] < 0 ? ' <small class="muted">CR</small>' : '' ?>
          </td>
          <td>
            <?php if ($t['pending']): ?>
              <span class="pill">Pending</span>
            <?php else: ?>
              <span class="pill" style="background:#e8f5e9;color:#1b5e20;">Posted</span>
            <?php endif ?>
          </td>
          <td>
            <form method="post" action="<?= site_url('admin/bank/txn/' . (int) $t['id'] . '/expense') ?>" style="display:inline;">
              <?= csrf_field() ?>
              <button type="submit" class="btn ghost" style="font-size:.78rem;padding:.2rem .55rem;">
                + Expense
              </button>
            </form>
            <form method="post" action="<?= site_url('admin/bank/txn/' . (int) $t['id'] . '/income') ?>" style="display:inline;">
              <?= csrf_field() ?>
              <button type="submit" class="btn ghost" style="font-size:.78rem;padding:.2rem .55rem;">
                + Income
              </button>
            </form>
          </td>
        </tr>
        <?php endforeach ?>
      </tbody>
    </table>
  <?php endif ?>
</div>

<script src="https://cdn.plaid.com/link/v2/stable/link-initialize.js"></script>
<script>
(function () {
  'use strict';

  const csrf      = document.querySelector('meta[name="csrf"]')?.content ?? '';
  const statusEl  = document.getElementById('plaid-status');

  function showStatus(msg, isErr) {
    if (!statusEl) return;
    statusEl.style.cssText = 'margin-top:.7rem;padding:.6rem 1rem;border-radius:6px;border:1px solid;' +
      (isErr
        ? 'background:#fdf0f0;border-color:#f3c6c6;color:#a12;'
        : 'background:#f0faf2;border-color:#bfe3c8;color:#155724;');
    statusEl.textContent = msg;
  }

  function clearStatus() {
    if (statusEl) { statusEl.style.cssText = ''; statusEl.textContent = ''; }
  }

  const connectBtn = document.getElementById('plaid-connect-btn');
  if (connectBtn) {
    connectBtn.addEventListener('click', async () => {
      connectBtn.disabled = true;
      showStatus('Initializing Plaid Link…', false);

      try {
        const tokenRes = await fetch('<?= site_url('admin/bank/link') ?>', {
          method: 'POST',
          credentials: 'include',
          headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-Token': csrf,
          },
        });

        if (!tokenRes.ok) throw new Error('Link token request failed (' + tokenRes.status + ')');

        const tokenData = await tokenRes.json();
        if (!tokenData.link_token) throw new Error(tokenData.error || 'No link_token in response');

        const handler = Plaid.create({
          token: tokenData.link_token,

          onSuccess: async (publicToken) => {
            showStatus('Connecting account…', false);

            const exchRes = await fetch('<?= site_url('admin/bank/exchange') ?>', {
              method: 'POST',
              credentials: 'include',
              headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': csrf,
              },
              body: JSON.stringify({ public_token: publicToken }),
            });

            const exchData = await exchRes.json();
            if (!exchData.success) throw new Error(exchData.error || 'Token exchange failed');

            showStatus('Syncing transactions…', false);

            await fetch('<?= site_url('admin/bank/sync') ?>', {
              method: 'POST',
              credentials: 'include',
              headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': csrf,
              },
            });

            showStatus('Connected! Reloading…', false);
            setTimeout(() => location.reload(), 1500);
          },

          onExit: (err) => {
            connectBtn.disabled = false;
            if (err) {
              showStatus('Plaid: ' + (err.display_message || err.error_message || 'Unknown error'), true);
            } else {
              clearStatus();
            }
          },
        });

        handler.open();

      } catch (err) {
        connectBtn.disabled = false;
        showStatus('Error: ' + err.message, true);
      }
    });
  }

  const syncBtn = document.getElementById('sync-btn');
  if (syncBtn) {
    syncBtn.addEventListener('click', async () => {
      syncBtn.disabled = true;
      syncBtn.textContent = 'Syncing…';

      try {
        const res  = await fetch('<?= site_url('admin/bank/sync') ?>', {
          method: 'POST',
          credentials: 'include',
          headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-Token': csrf,
          },
        });
        const data = await res.json();

        if (data.success) {
          showStatus('Synced ' + data.synced + ' new transaction(s). Reloading…', false);
          setTimeout(() => location.reload(), 1500);
        } else {
          showStatus(data.error || 'Sync failed.', true);
          syncBtn.disabled = false;
          syncBtn.textContent = 'Sync Now';
        }
      } catch (err) {
        showStatus('Sync error: ' + err.message, true);
        syncBtn.disabled = false;
        syncBtn.textContent = 'Sync Now';
      }
    });
  }
})();
</script>

<?= $this->endSection() ?>
