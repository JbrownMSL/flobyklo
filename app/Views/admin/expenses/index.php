<?= $this->extend('layout/admin') ?>
<?= $this->section('content') ?>
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.8rem;">
  <h1 style="margin:0;"><?= esc($title) ?></h1>
  <a class="btn" href="<?= site_url('admin/expenses/new') ?>">+ Add Expense</a>
</div>

<?php if ($missingCount > 0): ?>
<div class="flash" style="border-color:#f3c6c6;background:#fdf0f0;color:#a12;">
  <strong><?= (int) $missingCount ?></strong> expense<?= $missingCount === 1 ? '' : 's' ?>
  still need<?= $missingCount === 1 ? 's' : '' ?> a receipt photo.
  <a href="<?= site_url('admin/expenses?receipt=missing') ?>">Show them</a>
  <span class="muted">(fuel never needs one)</span>
</div>
<?php endif ?>

<div class="card" style="padding:.7rem 1rem;">
  <form method="get" style="display:flex;gap:.6rem;flex-wrap:wrap;align-items:flex-end;">
    <div>
      <label>Category</label>
      <select name="category" style="width:auto;">
        <option value="">All categories</option>
        <?php foreach (['flowers','supplies','fuel','rent','labor','marketing','other'] as $c): ?>
          <option value="<?= $c ?>" <?= ($filters['cat'] === $c) ? 'selected' : '' ?>><?= ucfirst($c) ?></option>
        <?php endforeach ?>
      </select>
    </div>
    <div>
      <label>Receipt</label>
      <select name="receipt" style="width:auto;">
        <option value="">Any</option>
        <option value="missing"  <?= ($filters['rcpt'] === 'missing')  ? 'selected' : '' ?>>Missing (needs photo)</option>
        <option value="attached" <?= ($filters['rcpt'] === 'attached') ? 'selected' : '' ?>>Attached</option>
      </select>
    </div>
    <div>
      <label>Event (COGS)</label>
      <select name="event_id" style="width:auto;">
        <option value="">All events</option>
        <?php foreach ($events as $ev): ?>
          <option value="<?= (int) $ev['id'] ?>" <?= ($filters['evId'] == $ev['id']) ? 'selected' : '' ?>>
            <?= esc($ev['event_date'] ?: '?') ?> — <?= esc($ev['venue'] ?: ucfirst($ev['type'])) ?>
          </option>
        <?php endforeach ?>
      </select>
    </div>
    <div>
      <label>From</label>
      <input type="date" name="from" value="<?= esc($filters['from']) ?>" style="width:auto;">
    </div>
    <div>
      <label>To</label>
      <input type="date" name="to" value="<?= esc($filters['to']) ?>" style="width:auto;">
    </div>
    <div><label>&nbsp;</label><button type="submit" class="btn ghost">Filter</button></div>
    <?php if (array_filter(array_values($filters))): ?>
      <div><label>&nbsp;</label><a class="btn ghost" href="<?= site_url('admin/expenses') ?>">Clear</a></div>
    <?php endif ?>
  </form>
</div>

<div class="card">
  <div style="display:flex;justify-content:flex-end;margin-bottom:.5rem;font-size:.9rem;">
    <?php $count = count($expenses); ?>
    <span class="muted"><?= $count ?> record<?= $count !== 1 ? 's' : '' ?> &nbsp;|&nbsp; Total: <strong><?= fbk_money($total) ?></strong></span>
  </div>
  <table>
    <thead>
      <tr>
        <th>Date</th>
        <th>Vendor</th>
        <th>Category</th>
        <th style="text-align:right;">Amount</th>
        <th>Receipt</th>
        <th>Event (COGS)</th>
        <th>Source</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($expenses as $e): ?>
      <tr>
        <td><?= esc($e['date']) ?></td>
        <td><?= esc($e['vendor'] ?: '—') ?></td>
        <td><span class="pill"><?= esc($e['category']) ?></span></td>
        <td style="text-align:right;"><?= fbk_money($e['amount']) ?></td>
        <td>
          <?php $rc = (int) ($e['receipt_count'] ?? 0); ?>
          <?php if ($rc > 0): ?>
            <a href="<?= site_url('admin/receipts/' . (int) $e['receipt_id']) ?>" target="_blank" rel="noopener"
               title="<?= $rc ?> photo<?= $rc === 1 ? '' : 's' ?>">
              <img src="<?= site_url('admin/receipts/' . (int) $e['receipt_id'] . '/thumb') ?>"
                   alt="Receipt" loading="lazy"
                   style="width:34px;height:34px;object-fit:cover;border-radius:4px;vertical-align:middle;background:#f6f2f4;">
            </a>
            <?php if ($rc > 1): ?><span class="muted" style="font-size:.75rem;">×<?= $rc ?></span><?php endif ?>
          <?php elseif ($e['category'] === 'fuel'): ?>
            <span class="muted" style="font-size:.78rem;">not needed</span>
          <?php elseif (! empty($e['receipt_waived'])): ?>
            <span class="pill" title="<?= esc($e['receipt_waived_reason'] ?: '') ?>">waived</span>
          <?php else: ?>
            <a class="pill" style="background:#fdf0f0;color:#a12;text-decoration:none;"
               href="<?= site_url('admin/expenses/' . (int) $e['id']) ?>">no receipt</a>
          <?php endif ?>
        </td>
        <td>
          <?php if ($e['event_id']): ?>
            <a href="<?= site_url('admin/events/' . (int) $e['event_id']) ?>">
              <?= esc($e['event_date'] ?? '#' . $e['event_id']) ?>
              <?php if ($e['venue']): ?><span class="muted"> — <?= esc($e['venue']) ?></span><?php endif ?>
            </a>
          <?php else: ?>
            <span class="muted">—</span>
          <?php endif ?>
        </td>
        <td>
          <?php if ($e['plaid_txn_id']): ?>
            <span class="pill" title="Plaid txn #<?= (int) $e['plaid_txn_id'] ?>">Plaid</span>
          <?php else: ?>
            <span class="muted">Manual</span>
          <?php endif ?>
        </td>
        <td><a href="<?= site_url('admin/expenses/' . (int) $e['id']) ?>">Edit</a></td>
      </tr>
    <?php endforeach ?>
    <?php if (! $expenses): ?>
      <tr><td colspan="8" class="muted" style="padding:.8rem .6rem;">No expenses recorded yet.</td></tr>
    <?php endif ?>
    </tbody>
  </table>
</div>
<?= $this->endSection() ?>
