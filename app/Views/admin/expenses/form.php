<?= $this->extend('layout/admin') ?>
<?= $this->section('content') ?>
<h1><?= esc($title) ?></h1>

<?php if ($plaidTxn): ?>
<div class="flash" style="border-color:#bfd4e3;background:#f0f6fa;color:#1e3a52;">
  Pre-filled from Plaid transaction: <strong><?= esc($plaidTxn['name']) ?></strong>
  (<?= esc($plaidTxn['date']) ?>, <?= fbk_money(abs((float) $plaidTxn['amount'])) ?>)
  — review category and event below before saving.
</div>
<?php endif ?>

<div class="card">
<form method="post" action="<?= site_url('admin/expenses/save') ?>">
  <?= csrf_field() ?>
  <input type="hidden" name="id" value="<?= (int) ($expense['id'] ?? 0) ?>">
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
        <?php
        $selected = old('category', $expense['category'] ?? 'other');
        foreach (['flowers','supplies','fuel','rent','labor','marketing','other'] as $c):
        ?>
          <option value="<?= $c ?>" <?= ($selected === $c) ? 'selected' : '' ?>><?= ucfirst($c) ?></option>
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
<?= $this->endSection() ?>
