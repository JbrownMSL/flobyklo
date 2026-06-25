<?php helper('wtr'); ?>
<div style="font-family:system-ui,Arial,sans-serif;max-width:560px;">
  <h2 style="color:#9A7400;">Your Weekend Tool Rentals reservation is confirmed</h2>
  <p>Reservation <strong>#<?= (int) $res['id'] ?></strong></p>
  <p><strong>Dates:</strong> <?= esc($res['start_date']) ?> → <?= esc($res['end_date']) ?></p>
  <table style="width:100%;border-collapse:collapse;font-size:14px;">
    <tr style="border-bottom:1px solid #eee;"><th align="left">Item</th><th align="left">Qty</th><th align="right">Subtotal</th></tr>
    <?php foreach ($items as $it): ?>
      <tr><td><?= esc($it['equipment_name']) ?><?= $it['is_trailer'] ? ' (trailer)' : '' ?></td><td><?= (int) $it['qty'] ?></td><td align="right"><?= wtr_money($it['line_subtotal']) ?></td></tr>
    <?php endforeach ?>
  </table>
  <p><strong>Tax:</strong> <?= wtr_money($res['tax_total']) ?><br>
     <strong>Rental total:</strong> <?= wtr_money($res['grand_total']) ?><br>
     <strong>Paid today (50%):</strong> <?= wtr_money($res['amount_paid']) ?><br>
     <strong>Balance due at pickup:</strong> <?= wtr_money($res['balance_due']) ?>
     <?php if ($res['damage_deposit'] > 0): ?><br><span style="color:#667;">Security deposit hold: <?= wtr_money($res['damage_deposit']) ?> (released on return)</span><?php endif ?></p>
  <p style="color:#667;font-size:13px;">Please bring a valid driver's license and a second ID (utility bill, vehicle registration, or credit card) to pickup.</p>
  <p style="color:#667;font-size:13px;"><?= esc(config('Wtr')->pickupHours) ?>. Please bring valid ID and this confirmation.</p>
</div>
