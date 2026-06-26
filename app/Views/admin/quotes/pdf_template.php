<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
  body     { font-family: DejaVu Sans, sans-serif; color: #1d2433; font-size: 11pt; margin: 0; padding: 0; }
  h1       { font-size: 20pt; color: #7c9a6f; margin: 0 0 4pt; }
  h2       { font-size: 12pt; margin: 14pt 0 4pt; color: #3a5a3a; border-bottom: 1px solid #d0ddd0; padding-bottom: 2pt; }
  .header  { margin-bottom: 18pt; }
  .brand   { font-size: 22pt; font-weight: bold; color: #d4a5a5; letter-spacing: 0.02em; }
  .meta    { font-size: 9.5pt; color: #667; margin-top: 8pt; }
  table    { width: 100%; border-collapse: collapse; font-size: 10pt; }
  th       { background: #f5f5f0; text-align: left; padding: 5pt 7pt; border-bottom: 1.5pt solid #c8d8c8; font-size: 9.5pt; }
  td       { padding: 5pt 7pt; border-bottom: 1pt solid #e8ede8; vertical-align: top; }
  .right   { text-align: right; }
  .muted   { color: #888; }
  .totals  { margin-top: 10pt; float: right; width: 260pt; }
  .totals table { font-size: 10.5pt; }
  .totals td { padding: 3pt 7pt; border: none; }
  .totals .total-row td { font-size: 12pt; font-weight: bold; color: #3a7a50; border-top: 1.5pt solid #c8d8c8; padding-top: 5pt; }
  .footer  { margin-top: 30pt; font-size: 9pt; color: #999; text-align: center; border-top: 1pt solid #e0e0e0; padding-top: 8pt; }
  .pill    { display: inline-block; font-size: 8.5pt; padding: 1.5pt 6pt; border-radius: 3pt; background: #f0ede8; color: #7a6050; }
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
  $totalCost = 0.0;
  foreach ($items as $item) { $totalCost += (float) $item['cost']; }
  $margin = $total > 0 ? round(($total - $totalCost) / $total * 100, 1) : null;
  $fbk    = config('Fbk');
?>

<div class="header">
  <table style="width:100%;border-collapse:collapse;">
    <tr>
      <td style="border:none;padding:0;vertical-align:top;width:50%;">
        <div class="brand">🌸 <?= esc($fbk->businessName) ?></div>
        <div class="meta"><?= esc($fbk->businessEmail) ?></div>
      </td>
      <td style="border:none;padding:0;vertical-align:top;text-align:right;">
        <h1>Floral Proposal</h1>
        <div class="meta">
          Quote #<?= (int) $q['id'] ?><br>
          Prepared: <?= date('F j, Y') ?><br>
          <?php if ($q['valid_until']): ?>
          Valid until: <?= esc($q['valid_until']) ?><br>
          <?php endif ?>
          Status: <span class="pill"><?= esc($q['status']) ?></span>
        </div>
      </td>
    </tr>
  </table>
</div>

<h2>Prepared For</h2>
<table style="width:auto;">
  <tr><td style="border:none;padding:2pt 0;font-weight:bold;"><?= esc($q['client_name'] ?? '—') ?></td></tr>
  <?php if ($q['client_email']): ?>
  <tr><td style="border:none;padding:2pt 0;"><?= esc($q['client_email']) ?></td></tr>
  <?php endif ?>
  <?php if ($q['client_phone']): ?>
  <tr><td style="border:none;padding:2pt 0;"><?= esc($q['client_phone']) ?></td></tr>
  <?php endif ?>
  <?php if ($q['event_date']): ?>
  <tr><td style="border:none;padding:2pt 0;">
    Event: <?= esc($q['event_type'] ?? '') ?>  — <?= esc($q['event_date']) ?>
    <?= $q['venue'] ? ' @ ' . esc($q['venue']) : '' ?>
  </td></tr>
  <?php endif ?>
</table>

<h2>Proposed Items</h2>
<table>
  <thead>
    <tr>
      <th>Description</th>
      <th class="right" style="width:50pt">Qty</th>
      <th class="right" style="width:70pt">Unit Price</th>
      <th class="right" style="width:75pt">Total</th>
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
</table>

<div style="clear:both; margin-top:10pt;">
  <div class="totals">
    <table>
      <tr>
        <td>Subtotal</td>
        <td class="right"><?= fbk_money($subtotal) ?></td>
      </tr>
      <?php if ($tax > 0): ?>
      <tr>
        <td>Tax</td>
        <td class="right"><?= fbk_money($tax) ?></td>
      </tr>
      <?php endif ?>
      <tr class="total-row">
        <td>Total</td>
        <td class="right"><?= fbk_money($total) ?></td>
      </tr>
      <tr>
        <td class="muted">Deposit Required (<?= (int) $q['deposit_pct'] ?>%)</td>
        <td class="right"><?= fbk_money($dep) ?></td>
      </tr>
      <tr>
        <td class="muted">Balance Due at Event</td>
        <td class="right"><?= fbk_money($total - $dep) ?></td>
      </tr>
    </table>
  </div>
  <div style="clear:both;"></div>
</div>

<div class="footer">
  Thank you for considering <?= esc($fbk->businessName) ?> for your florals. We look forward to bringing your vision to life!<br>
  Questions? Reach us at <?= esc($fbk->businessEmail) ?>
</div>
</body>
</html>
