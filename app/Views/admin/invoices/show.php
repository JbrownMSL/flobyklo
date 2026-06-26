<?= $this->extend('layout/admin') ?>
<?= $this->section('content') ?>
<p class="muted"><a href="<?= site_url('admin/invoices') ?>">← Invoices</a></p>

<div style="display:flex;align-items:center;gap:.8rem;flex-wrap:wrap;margin-bottom:.4rem;">
  <h1 style="margin:0;"><?= esc($invoice['number'] ?: '#' . $invoice['id']) ?></h1>
  <span class="pill pill--<?= esc($invoice['status']) ?>"><?= esc(str_replace('_', ' ', $invoice['status'])) ?></span>
  <a href="<?= site_url('admin/invoices/' . $invoice['id'] . '/pdf') ?>" class="btn ghost" style="margin-left:auto;font-size:.82rem;">⬇ PDF</a>
</div>

<div class="row">

  <!-- ── Left: invoice detail ────────────────────────────────────────── -->
  <div class="card" style="flex:2 1 380px;">
    <p class="muted" style="margin:0 0 .7rem;">
      Client: <strong><?= esc($invoice['client_name'] ?? '—') ?></strong>
      <?= $invoice['client_email'] ? '· ' . esc($invoice['client_email']) : '' ?>
      <?php if ($invoice['due_date']): ?>
        · Due <strong><?= esc($invoice['due_date']) ?></strong>
      <?php endif ?>
    </p>

    <!-- Totals -->
    <table style="width:auto;min-width:260px;margin-bottom:1rem;">
      <tr><td>Subtotal</td><td style="text-align:right;padding-left:2rem;"><?= fbk_money($invoice['subtotal']) ?></td></tr>
      <tr><td>Tax</td><td style="text-align:right;"><?= fbk_money($invoice['tax']) ?></td></tr>
      <tr><td><strong>Total</strong></td><td style="text-align:right;"><strong><?= fbk_money($invoice['total']) ?></strong></td></tr>
      <tr><td style="color:#059669;">Paid</td><td style="text-align:right;color:#059669;"><?= fbk_money($invoice['amount_paid']) ?></td></tr>
      <tr><td><strong>Balance due</strong></td><td style="text-align:right;"><strong><?= fbk_money($invoice['balance_due']) ?></strong></td></tr>
    </table>

    <!-- Payment history -->
    <h2>Payment history</h2>
    <table>
      <thead><tr><th>Date</th><th>Kind</th><th>Method</th><th>Amount</th><th>Ref</th></tr></thead>
      <tbody>
      <?php foreach ($payments as $p): ?>
        <tr>
          <td class="muted"><?= esc(substr($p['paid_at'] ?? $p['created_at'] ?? '', 0, 10)) ?></td>
          <td><span class="pill"><?= esc($p['kind']) ?></span></td>
          <td><?= esc($p['method']) ?></td>
          <td><?= fbk_money($p['amount']) ?></td>
          <td class="muted" style="font-size:.78rem;"><?= esc($p['square_ref'] ?? '') ?></td>
        </tr>
      <?php endforeach ?>
      <?php if (! $payments): ?>
        <tr><td colspan="5" class="muted">No payments recorded.</td></tr>
      <?php endif ?>
      </tbody>
    </table>
  </div>

  <!-- ── Right: actions ──────────────────────────────────────────────── -->
  <div style="flex:1 1 260px;display:flex;flex-direction:column;gap:.8rem;">

    <?php if ($invoice['status'] !== 'void' && $invoice['status'] !== 'paid'): ?>

    <!-- Manual payment -->
    <div class="card">
      <h2>Record payment</h2>
      <form action="<?= site_url('admin/invoices/' . $invoice['id'] . '/payment') ?>" method="post">
        <?= csrf_field() ?>
        <label>Amount</label>
        <input type="number" name="amount" step="0.01" min="0.01"
               value="<?= round((float)$invoice['balance_due'], 2) ?>" required>
        <label>Kind</label>
        <select name="kind">
          <?php
          $depositSuggested = (float)$invoice['amount_paid'] === 0.0;
          foreach (['deposit' => 'Deposit', 'balance' => 'Balance', 'other' => 'Other'] as $v => $l):
          ?>
            <option value="<?= $v ?>" <?= ($v === 'deposit' && $depositSuggested) || ($v === 'balance' && !$depositSuggested) ? 'selected' : '' ?>><?= $l ?></option>
          <?php endforeach ?>
        </select>
        <label>Method</label>
        <select name="method">
          <option value="cash">Cash</option>
          <option value="check">Check</option>
          <option value="other">Other</option>
        </select>
        <button class="btn" style="margin-top:.6rem;width:100%;">Record manual payment</button>
      </form>
    </div>

    <!-- Square card payment -->
    <div class="card">
      <h2>
        Card payment
        <?php if (! $squareLive): ?>
          <span class="pill" style="font-size:.7rem;vertical-align:middle;">SIMULATED</span>
        <?php endif ?>
      </h2>

      <?php if ($squareLive): ?>
        <!-- Live Square Web Payments SDK -->
        <form id="sq-card-form" action="<?= site_url('admin/invoices/' . $invoice['id'] . '/payment') ?>" method="post">
          <?= csrf_field() ?>
          <input type="hidden" name="method" value="square">
          <input type="hidden" name="nonce" id="sq-nonce">
          <label>Amount</label>
          <input type="number" name="amount" id="sq-amount" step="0.01" min="0.01"
                 value="<?= round((float)$invoice['balance_due'], 2) ?>" required>
          <label>Kind</label>
          <select name="kind" id="sq-kind">
            <option value="deposit" <?= (float)$invoice['amount_paid'] === 0.0 ? 'selected' : '' ?>>Deposit</option>
            <option value="balance" <?= (float)$invoice['amount_paid'] > 0.0 ? 'selected' : '' ?>>Balance</option>
            <option value="other">Other</option>
          </select>
          <div id="sq-card-container" style="margin:.6rem 0;min-height:56px;border:1px solid #d7dbe3;border-radius:7px;padding:.5rem;"></div>
          <p id="sq-card-error" style="color:#a12;font-size:.82rem;display:none;"></p>
          <button id="sq-pay-btn" class="btn" style="width:100%;margin-top:.4rem;">Charge card</button>
        </form>
        <script src="<?= $squareEnv === 'production' ? 'https://web.squarecdn.com/v1/square.js' : 'https://sandbox.web.squarecdn.com/v1/square.js' ?>"></script>
        <script>
        (async () => {
          const payments = Square.payments('<?= esc($squareAppId, 'js') ?>', '<?= esc(config('Fbk')->squareLocationId, 'js') ?>');
          const card = await payments.card();
          await card.attach('#sq-card-container');
          document.getElementById('sq-pay-btn').addEventListener('click', async (e) => {
            e.preventDefault();
            const result = await card.tokenize();
            if (result.status === 'OK') {
              document.getElementById('sq-nonce').value = result.token;
              document.getElementById('sq-card-form').submit();
            } else {
              const el = document.getElementById('sq-card-error');
              el.textContent = result.errors?.[0]?.message ?? 'Card tokenization failed.';
              el.style.display = 'block';
            }
          });
        })();
        </script>

      <?php else: ?>
        <!-- Simulated mode: no real card data -->
        <p class="muted" style="font-size:.82rem;margin-bottom:.7rem;">
          Square keys are not configured. Charges are simulated and no real card is processed.
        </p>
        <form action="<?= site_url('admin/invoices/' . $invoice['id'] . '/payment') ?>" method="post">
          <?= csrf_field() ?>
          <input type="hidden" name="method" value="square">
          <input type="hidden" name="nonce" value="sim_nonce_<?= $invoice['id'] ?>">
          <label>Amount</label>
          <input type="number" name="amount" step="0.01" min="0.01"
                 value="<?= round((float)$invoice['balance_due'], 2) ?>" required>
          <label>Kind</label>
          <select name="kind">
            <option value="deposit" <?= (float)$invoice['amount_paid'] === 0.0 ? 'selected' : '' ?>>Deposit</option>
            <option value="balance" <?= (float)$invoice['amount_paid'] > 0.0 ? 'selected' : '' ?>>Balance</option>
            <option value="other">Other</option>
          </select>
          <button class="btn" style="width:100%;margin-top:.6rem;background:#5b7fa6;">Simulate Square charge</button>
        </form>
      <?php endif ?>
    </div>

    <?php endif ?><!-- end not void/paid -->

    <!-- Status actions -->
    <div class="card">
      <h2>Actions</h2>
      <?php if (in_array($invoice['status'], ['draft', 'sent'], true)): ?>
        <form action="<?= site_url('admin/invoices/' . $invoice['id'] . '/payment') ?>" method="post" style="margin-bottom:.5rem;">
          <?= csrf_field() ?>
          <input type="hidden" name="_action" value="send">
          <button class="btn" style="width:100%;">Mark sent &amp; email client</button>
        </form>
      <?php endif ?>
      <?php if ($invoice['status'] !== 'void'): ?>
        <form action="<?= site_url('admin/invoices/' . $invoice['id'] . '/payment') ?>" method="post"
              onsubmit="return confirm('Void this invoice?');">
          <?= csrf_field() ?>
          <input type="hidden" name="_action" value="void">
          <button class="btn alt" style="width:100%;">Void invoice</button>
        </form>
      <?php endif ?>
      <?php if ($invoice['quote_id']): ?>
        <p style="margin-top:.7rem;"><a href="<?= site_url('admin/quotes/' . $invoice['quote_id']) ?>">← Back to quote</a></p>
      <?php endif ?>
    </div>

  </div><!-- /right -->
</div><!-- /row -->

<style>
  .pill--paid{background:#d1fae5;color:#065f46;border-color:#a7f3d0;}
  .pill--deposit_paid{background:#dbeafe;color:#1e40af;border-color:#93c5fd;}
  .pill--sent{background:#fef9c3;color:#713f12;border-color:#fde68a;}
  .pill--void{background:#f3f4f6;color:#6b7280;border-color:#d1d5db;}
</style>
<?= $this->endSection() ?>
