<?= $this->extend('layout/admin') ?>
<?= $this->section('content') ?>
<h1><?= esc($title) ?></h1>

<?php if ($plaidTxn): ?>
<div class="flash" style="border-color:#bfd4e3;background:#f0f6fa;color:#1e3a52;">
  Pre-filled from bank transaction: <strong><?= esc($plaidTxn['name']) ?></strong>
  (<?= esc($plaidTxn['date']) ?>, <?= fbk_money(abs((float) $plaidTxn['amount'])) ?>)
  — pick the category and client, then save.
</div>
<?php endif ?>

<div class="card">
<form method="post" action="<?= site_url('admin/income/save') ?>">
  <?= csrf_field() ?>
  <input type="hidden" name="id" value="<?= (int) ($row['id'] ?? 0) ?>">
  <?php if (! empty($row['plaid_txn_id'])): ?>
  <input type="hidden" name="plaid_txn_id" value="<?= (int) $row['plaid_txn_id'] ?>">
  <?php endif ?>

  <div class="row">
    <div>
      <label for="f_date">Date *</label>
      <input type="date" id="f_date" name="date" value="<?= esc(old('date', $row['date'] ?? date('Y-m-d'))) ?>" required>
    </div>
    <div>
      <label for="f_payer">Payer</label>
      <input type="text" id="f_payer" name="payer" value="<?= esc(old('payer', $row['payer'] ?? '')) ?>" placeholder="e.g. client name, Square deposit, market cash…">
    </div>
  </div>

  <div class="row" style="margin-top:.7rem;">
    <div>
      <label for="f_category">Category *</label>
      <select id="f_category" name="category" required>
        <?php $sel = old('category', $row['category'] ?? 'sales');
        foreach ($categories as $c): ?>
          <option value="<?= $c ?>" <?= $sel === $c ? 'selected' : '' ?>><?= ucfirst($c) ?></option>
        <?php endforeach ?>
      </select>
    </div>
    <div>
      <label for="f_amount">Amount ($) *</label>
      <input type="number" id="f_amount" name="amount" step="0.01" min="0.01" value="<?= esc(old('amount', $row['amount'] ?? '')) ?>" required>
    </div>
  </div>

  <div class="row" style="margin-top:.7rem;">
    <div>
      <label for="f_client">Client (optional)</label>
      <select id="f_client" name="client_id">
        <option value="">—</option>
        <?php $selC = (int) old('client_id', $row['client_id'] ?? 0);
        foreach ($clients as $c): ?>
          <option value="<?= (int) $c['id'] ?>" <?= $selC === (int) $c['id'] ? 'selected' : '' ?>><?= esc($c['name']) ?></option>
        <?php endforeach ?>
      </select>
    </div>
    <div>
      <label for="f_event">Event (optional)</label>
      <select id="f_event" name="event_id">
        <option value="">—</option>
        <?php $selE = (int) old('event_id', $row['event_id'] ?? 0);
        foreach ($events as $ev): ?>
          <option value="<?= (int) $ev['id'] ?>" <?= $selE === (int) $ev['id'] ? 'selected' : '' ?>>
            <?= esc($ev['event_date'] ?: '?') ?><?php if ($ev['venue']): ?> — <?= esc($ev['venue']) ?><?php endif ?> (<?= esc($ev['type']) ?>)
          </option>
        <?php endforeach ?>
      </select>
    </div>
  </div>

  <div style="margin-top:.7rem;">
    <label for="f_notes">Notes</label>
    <textarea id="f_notes" name="notes" rows="3"><?= esc(old('notes', $row['notes'] ?? '')) ?></textarea>
  </div>

  <div style="margin-top:1rem;display:flex;gap:.8rem;align-items:center;">
    <button type="submit" class="btn">Save Income</button>
    <a href="<?= site_url('admin/income') ?>" class="btn ghost">Cancel</a>
  </div>
</form>
</div>
<?= $this->endSection() ?>
