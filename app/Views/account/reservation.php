<?= $this->extend('layout/main') ?>
<?= $this->section('content') ?>
<p class="muted"><a href="<?= site_url('account/reservations') ?>">← My reservations</a></p>
<h1>Reservation #<?= (int) $res['id'] ?> <span class="pill"><?= esc($res['status']) ?></span></h1>
<div class="card">
  <p class="muted"><?= esc($res['start_date']) ?> → <?= esc($res['end_date']) ?></p>
  <table><thead><tr><th>Item</th><th>Qty</th><th>Days</th><th>Subtotal</th></tr></thead><tbody>
  <?php foreach ($items as $it): ?>
    <tr><td><?= esc($it['equipment_name']) ?><?= $it['is_trailer'] ? ' <span class="pill">trailer</span>' : '' ?></td><td><?= (int) $it['qty'] ?></td><td><?= (int) $it['days'] ?></td><td><?= wtr_money($it['line_subtotal']) ?></td></tr>
  <?php endforeach ?>
  </tbody></table>
  <table class="totals" style="margin-top:.6rem;">
    <tr><td>Tax</td><td style="text-align:right;"><?= wtr_money($res['tax_total']) ?></td></tr>
    <tr class="grand"><td>Rental total</td><td style="text-align:right;"><?= wtr_money($res['grand_total']) ?></td></tr>
    <tr><td>Paid</td><td style="text-align:right;"><?= wtr_money($res['amount_paid']) ?></td></tr>
    <?php if ($res['balance_due'] > 0): ?><tr><td class="muted">Balance due at pickup</td><td style="text-align:right;" class="muted"><?= wtr_money($res['balance_due']) ?></td></tr><?php endif ?>
  </table>
</div>
<?php if ($canCancel): ?>
  <div class="card">
    <h2>Cancel this reservation</h2>
    <p class="muted">Refunds follow the policy: &gt;48h before pickup → 75% back · 24–48h → 50% · &lt;24h → 25%.</p>
    <form action="<?= site_url('account/reservation/' . $res['id'] . '/cancel') ?>" method="post" onsubmit="return confirm('Cancel this reservation?');">
      <?= csrf_field() ?><button class="btn alt" type="submit">Cancel reservation</button>
    </form>
  </div>
<?php endif ?>
<?= $this->endSection() ?>
