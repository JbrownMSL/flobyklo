<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
  body        { margin: 0; padding: 0; background: #f9f6f3; font-family: Georgia, 'Times New Roman', serif; color: #2d2d2d; }
  .wrapper    { max-width: 600px; margin: 30px auto; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,.07); }
  .banner     { background: #1a1a2e; padding: 28px 32px; text-align: center; }
  .brand      { font-family: system-ui, sans-serif; font-size: 22px; font-weight: 800; color: #d4a5a5; letter-spacing: .04em; }
  .tagline    { color: #9a9abf; font-size: 13px; margin-top: 4px; font-family: system-ui, sans-serif; }
  .body       { padding: 28px 32px; }
  h2          { font-size: 20px; color: #5a7a5a; margin: 0 0 16px; }
  p           { margin: 0 0 14px; font-size: 15px; line-height: 1.65; }
  table       { width: 100%; border-collapse: collapse; margin: 18px 0; font-size: 14px; font-family: system-ui, sans-serif; }
  th          { background: #f5f3f0; text-align: left; padding: 8px 10px; font-size: 12px; color: #666; text-transform: uppercase; letter-spacing: .04em; }
  td          { padding: 8px 10px; border-bottom: 1px solid #f0ede8; vertical-align: top; }
  .right      { text-align: right; }
  .total-row  { font-weight: bold; font-size: 15px; }
  .total-row td { border-top: 2px solid #d4a5a5; border-bottom: none; color: #3a7a50; padding-top: 10px; }
  .deposit    { background: #f5f8f5; }
  .deposit td { color: #5a7a5a; font-style: italic; font-size: 13px; border-bottom: none; }
  .cta        { text-align: center; margin: 24px 0; }
  .btn        { display: inline-block; background: #7c9a6f; color: #fff; padding: 12px 28px; border-radius: 8px; text-decoration: none; font-family: system-ui, sans-serif; font-weight: 600; font-size: 15px; }
  .footer     { background: #f5f3f0; padding: 16px 32px; text-align: center; font-size: 12px; color: #999; font-family: system-ui, sans-serif; }
</style>
</head>
<body>
<?php
  helper('fbk');
  $q        = $quote;
  $subtotal = (float) $q['subtotal'];
  $tax      = (float) $q['tax'];
  $total    = (float) $q['total'];
  $dep      = round($total * ($q['deposit_pct'] / 100), 2);
  $fbk      = config('Fbk');
?>
<div class="wrapper">
  <div class="banner">
    <div class="brand">🌸 <?= esc($fbk->businessName) ?></div>
    <div class="tagline">Floral Design &amp; Artistry</div>
  </div>

  <div class="body">
    <h2>Your Floral Proposal is Ready</h2>

    <p>Hi <?= esc($q['client_name'] ?? 'there') ?>,</p>
    <p>
      Thank you for reaching out! We have prepared a floral proposal for you. Please review the details below.
      <?php if ($q['valid_until']): ?>
      This proposal is valid until <strong><?= esc($q['valid_until']) ?></strong>.
      <?php endif ?>
    </p>

    <?php if ($q['event_date']): ?>
    <p><strong>Event:</strong> <?= esc($q['event_type'] ?? 'Event') ?> on <?= esc($q['event_date']) ?>
      <?= $q['venue'] ? ' at ' . esc($q['venue']) : '' ?></p>
    <?php endif ?>

    <table>
      <thead>
        <tr>
          <th>Item</th>
          <th class="right" style="width:45px">Qty</th>
          <th class="right" style="width:80px">Price</th>
          <th class="right" style="width:80px">Total</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($items as $item): ?>
        <tr>
          <td><?= esc($item['description']) ?></td>
          <td class="right"><?= number_format((float) $item['qty'], 2) ?></td>
          <td class="right"><?= fbk_money($item['unit_price']) ?></td>
          <td class="right"><?= fbk_money($item['line_total']) ?></td>
        </tr>
      <?php endforeach ?>
      </tbody>
      <tfoot>
        <?php if ($tax > 0): ?>
        <tr><td colspan="3" class="right">Subtotal</td><td class="right"><?= fbk_money($subtotal) ?></td></tr>
        <tr><td colspan="3" class="right">Tax</td><td class="right"><?= fbk_money($tax) ?></td></tr>
        <?php endif ?>
        <tr class="total-row">
          <td colspan="3" class="right">Total</td>
          <td class="right"><?= fbk_money($total) ?></td>
        </tr>
        <tr class="deposit">
          <td colspan="3" class="right">Deposit Required (<?= (int) $q['deposit_pct'] ?>% to secure your date)</td>
          <td class="right"><?= fbk_money($dep) ?></td>
        </tr>
      </tfoot>
    </table>

    <?php if ($fbk->businessEmail): ?>
    <p style="font-size:14px;color:#666;">
      A PDF copy of this proposal is attached to this email. If you have any questions or would like to discuss adjustments, please reply to this email or reach us at
      <a href="mailto:<?= esc($fbk->businessEmail) ?>" style="color:#7c9a6f;"><?= esc($fbk->businessEmail) ?></a>.
    </p>
    <?php endif ?>

    <p>We would love to bring your floral vision to life and look forward to hearing from you!</p>

    <p>With love,<br><strong><?= esc($fbk->businessName) ?></strong></p>
  </div>

  <div class="footer">
    &copy; <?= date('Y') ?> <?= esc($fbk->businessName) ?>. All rights reserved.<br>
    <?= esc($fbk->businessEmail) ?>
  </div>
</div>
</body>
</html>
