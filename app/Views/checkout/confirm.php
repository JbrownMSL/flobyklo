<?= $this->extend('layout/main') ?>
<?= $this->section('content') ?>
<div class="card" style="border-color:#bfe3c8;background:#f0faf2;">
  <h1 style="margin-top:0;">✅ Reservation #<?= (int) $res['id'] ?> confirmed</h1>
  <p>Thanks! Your equipment is reserved for <strong><?= esc($res['start_date']) ?></strong> → <strong><?= esc($res['end_date']) ?></strong>.</p>
  <?php if (! $square): ?><p class="muted">(Square not configured — this booking was recorded without a live charge.)</p><?php endif ?>
</div>
<div class="card">
  <table>
    <thead><tr><th>Item</th><th>Qty</th><th>Days</th><th>Subtotal</th></tr></thead>
    <tbody>
    <?php foreach ($items as $it): ?>
      <tr><td><?= esc($it['equipment_name']) ?><?= $it['is_trailer'] ? ' <span class="pill">trailer</span>' : '' ?></td><td><?= (int) $it['qty'] ?></td><td><?= (int) $it['days'] ?></td><td><?= wtr_money($it['line_subtotal']) ?></td></tr>
    <?php endforeach ?>
    </tbody>
  </table>
  <table class="totals" style="margin-top:.6rem;">
    <tr><td>Tax</td><td style="text-align:right;"><?= wtr_money($res['tax_total']) ?></td></tr>
    <tr><td>Rental total</td><td style="text-align:right;"><?= wtr_money($res['grand_total']) ?></td></tr>
    <tr class="grand"><td>Paid today (50%)</td><td style="text-align:right;"><?= wtr_money($res['amount_paid']) ?></td></tr>
    <tr><td class="muted">Balance due at pickup</td><td style="text-align:right;" class="muted"><?= wtr_money($res['balance_due']) ?></td></tr>
    <?php if ($res['damage_deposit'] > 0): ?><tr><td class="muted">Security deposit (hold)</td><td style="text-align:right;" class="muted"><?= wtr_money($res['damage_deposit']) ?></td></tr><?php endif ?>
  </table>
  <p style="margin-top:1rem;"><a class="btn" href="<?= site_url('account/reservations') ?>">View my reservations</a></p>
</div>
<?= $this->endSection() ?>
