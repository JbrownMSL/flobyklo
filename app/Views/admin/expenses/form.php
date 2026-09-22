<?= $this->extend('layout/admin') ?>
<?= $this->section('content') ?>
<h1><?= esc($title) ?></h1>

<?php if ($plaidTxn): ?>
<div class="flash" style="border-color:#bfd4e3;background:#f0f6fa;color:#1e3a52;">
  Pre-filled from Plaid transaction: <strong><?= esc($plaidTxn['name']) ?></strong>
  (<?= esc($plaidTxn['date']) ?>, <?= fbk_money(abs((float) $plaidTxn['amount'])) ?>)
  — category pre-picked from the bank's own label (<em><?= esc(strtolower(str_replace('_', ' ', (string) $plaidTxn['category']))) ?></em>); review it and the event before saving.
</div>
<?php endif ?>

<?php
$expId    = (int) ($expense['id'] ?? 0);
$cat      = old('category', $expense['category'] ?? 'other');
$waived   = ! empty($expense['receipt_waived']);
$needs    = $cat !== 'fuel' && ! $receipts && ! $waived;
?>

<?php if ($expId && $needs): ?>
<div class="flash" style="border-color:#f3c6c6;background:#fdf0f0;color:#a12;">
  <strong>Receipt photo needed.</strong> Every expense needs one except fuel.
  Use <em>Take / choose photo</em> below — or mark it as having no receipt.
</div>
<?php endif ?>

<div class="card">
<form method="post" action="<?= site_url('admin/expenses/save') ?>" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <input type="hidden" name="id" value="<?= $expId ?>">
  <?php if (! empty($expense['plaid_txn_id'])): ?>
  <input type="hidden" name="plaid_txn_id" value="<?= (int) $expense['plaid_txn_id'] ?>">
  <?php endif ?>

  <div class="row">
    <div>
      <label for="f_date">Date *</label>
      <input type="date" id="f_date" name="date"
             value="<?= esc(old('date', $expense['date'] ?? date('Y-m-d'))) ?>" required>
    </div>
    <div>
      <label for="f_vendor">Vendor / Payee</label>
      <input type="text" id="f_vendor" name="vendor"
             value="<?= esc(old('vendor', $expense['vendor'] ?? '')) ?>"
             placeholder="e.g. Dutch Masters Wholesale, Shell Gas…">
    </div>
  </div>

  <div class="row" style="margin-top:.7rem;">
    <div>
      <label for="f_category">Category *</label>
      <select id="f_category" name="category" required>
        <?php foreach (['flowers','supplies','fuel','rent','labor','marketing','other'] as $c): ?>
          <option value="<?= $c ?>" <?= ($cat === $c) ? 'selected' : '' ?>><?= ucfirst($c) ?></option>
        <?php endforeach ?>
      </select>
      <small class="muted" style="font-size:.72rem;">
        flowers/supplies = COGS &nbsp;|&nbsp; fuel/rent/labor/marketing = overhead
      </small>
    </div>
    <div>
      <label for="f_amount">Amount ($) *</label>
      <input type="number" id="f_amount" name="amount" step="0.01" min="0.01"
             value="<?= esc(old('amount', $expense['amount'] ?? '')) ?>" required>
    </div>
  </div>

  <!-- #2948 receipt capture. accept/capture give the phone the camera directly; on a
       desktop the same input is an ordinary file picker. -->
  <div style="margin-top:.9rem;padding:.7rem;border:1px dashed #d6c7cf;border-radius:8px;">
    <label for="f_receipts" style="margin-bottom:.2rem;">
      Receipt photo
      <span id="receipt-req" class="pill" style="<?= $cat === 'fuel' ? 'display:none;' : '' ?>background:#fdf0f0;color:#a12;">required</span>
      <span id="receipt-opt" class="pill" style="<?= $cat === 'fuel' ? '' : 'display:none;' ?>">not needed for fuel</span>
    </label>
    <input type="file" id="f_receipts" name="receipts[]" accept="image/*" capture="environment" multiple>
    <small class="muted" style="display:block;font-size:.72rem;margin-top:.25rem;">
      On a phone this opens the camera. JPEG/PNG/WebP/HEIC, up to 12&nbsp;MB each.
      The photo is attached when you save.
    </small>
  </div>

  <div style="margin-top:.7rem;">
    <label for="f_event">Event — assign as COGS (optional)</label>
    <select id="f_event" name="event_id">
      <option value="">— General overhead (no specific event) —</option>
      <?php
      $selEvent = (int) old('event_id', $expense['event_id'] ?? 0);
      foreach ($events as $ev):
      ?>
        <option value="<?= (int) $ev['id'] ?>" <?= ($selEvent === (int) $ev['id']) ? 'selected' : '' ?>>
          <?= esc($ev['event_date'] ?: '?') ?>
          <?php if ($ev['venue']): ?> — <?= esc($ev['venue']) ?><?php endif ?>
          (<?= esc($ev['type']) ?>)
        </option>
      <?php endforeach ?>
    </select>
    <small class="muted" style="font-size:.72rem;">
      Linking to an event lets the P&amp;L report calculate per-event margin.
    </small>
  </div>

  <div style="margin-top:.7rem;">
    <label for="f_notes">Notes</label>
    <textarea id="f_notes" name="notes" rows="3"
              placeholder="Receipt #, purpose, or any relevant detail…"><?= esc(old('notes', $expense['notes'] ?? '')) ?></textarea>
  </div>

  <?php if (! empty($expense['plaid_txn_id'])): ?>
  <p class="muted" style="font-size:.78rem;margin-top:.5rem;">
    Linked to Plaid transaction #<?= (int) $expense['plaid_txn_id'] ?>
  </p>
  <?php endif ?>

  <div style="margin-top:1rem;display:flex;gap:.8rem;align-items:center;">
    <button type="submit" class="btn">Save Expense</button>
    <a href="<?= site_url('admin/expenses') ?>" class="btn ghost">Cancel</a>
  </div>
</form>
</div>

<?php if ($expId): ?>
<div class="card">
  <h2 style="margin:0 0 .6rem;font-size:1rem;">Receipts <span class="muted">(<?= count($receipts) ?>)</span></h2>

  <?php if ($receipts): ?>
    <div style="display:flex;gap:.8rem;flex-wrap:wrap;">
      <?php foreach ($receipts as $r): ?>
        <div style="border:1px solid #e4dde0;border-radius:8px;padding:.4rem;width:180px;">
          <a href="<?= site_url('admin/receipts/' . (int) $r['id']) ?>" target="_blank" rel="noopener">
            <?php if ($r['previewable']): ?>
              <img src="<?= site_url('admin/receipts/' . (int) $r['id'] . '/thumb') ?>"
                   alt="Receipt <?= (int) $r['id'] ?>"
                   style="width:100%;height:120px;object-fit:cover;border-radius:5px;background:#f6f2f4;">
            <?php else: ?>
              <div style="height:120px;display:flex;align-items:center;justify-content:center;
                          border-radius:5px;background:#f6f2f4;font-size:.78rem;text-align:center;padding:.4rem;">
                Photo saved<br><span class="muted">(HEIC — open to view)</span>
              </div>
            <?php endif ?>
          </a>
          <div class="muted" style="font-size:.7rem;margin-top:.3rem;word-break:break-all;">
            <?= esc($r['original_name'] ?: ('receipt-' . $r['id'])) ?><br>
            <?= number_format(((int) $r['bytes']) / 1024) ?> KB · <?= esc(substr((string) $r['created_at'], 0, 16)) ?>
          </div>
          <form method="post" action="<?= site_url('admin/receipts/' . (int) $r['id'] . '/delete') ?>"
                onsubmit="return confirm('Remove this receipt photo?');" style="margin-top:.3rem;">
            <?= csrf_field() ?>
            <button type="submit" class="btn ghost" style="font-size:.72rem;padding:.15rem .5rem;">Remove</button>
          </form>
        </div>
      <?php endforeach ?>
    </div>
  <?php else: ?>
    <p class="muted" style="margin:0;">No receipt photo attached yet.</p>
  <?php endif ?>

  <?php if ($waived): ?>
    <p class="muted" style="margin-top:.7rem;font-size:.8rem;">
      Marked as no receipt available<?= $expense['receipt_waived_reason'] ? ' — ' . esc($expense['receipt_waived_reason']) : '' ?>.
      Attaching a photo clears this automatically.
    </p>
  <?php elseif ($cat !== 'fuel' && ! $receipts): ?>
    <form method="post" action="<?= site_url('admin/expenses/' . $expId . '/receipt-waive') ?>"
          style="margin-top:.8rem;display:flex;gap:.5rem;align-items:flex-end;flex-wrap:wrap;">
      <?= csrf_field() ?>
      <div>
        <label style="font-size:.78rem;">No receipt for this one? Say why</label>
        <input type="text" name="reason" maxlength="150" placeholder="e.g. auto-draft rent, cash tip" style="width:auto;">
      </div>
      <button type="submit" class="btn ghost" style="font-size:.78rem;">Mark as no receipt</button>
    </form>
  <?php endif ?>
</div>
<?php endif ?>

<script>
(function () {
  var cat = document.getElementById('f_category');
  var req = document.getElementById('receipt-req');
  var opt = document.getElementById('receipt-opt');
  if (!cat || !req || !opt) return;
  cat.addEventListener('change', function () {
    var fuel = cat.value === 'fuel';
    req.style.display = fuel ? 'none' : '';
    opt.style.display = fuel ? '' : 'none';
  });
})();
</script>
<?= $this->endSection() ?>
