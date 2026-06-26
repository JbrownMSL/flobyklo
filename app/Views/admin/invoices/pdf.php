<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<style>
  body { font-family: sans-serif; color: #1d2433; font-size: 13px; margin: 0; padding: 0; }
  h1   { font-size: 22px; color: #5b7a54; margin: 0 0 4px; }
  h2   { font-size: 14px; margin: 18px 0 6px; }
  .header { display: table; width: 100%; margin-bottom: 24px; }
  .biz    { display: table-cell; vertical-align: top; }
  .inv    { display: table-cell; vertical-align: top; text-align: right; }
  .inv h1 { font-size: 28px; }
  table { width: 100%; border-collapse: collapse; }
  th    { background: #f0ede8; text-align: left; padding: 6px 8px; font-size: 12px; }
  td    { padding: 5px 8px; border-bottom: 1px solid #e8e4df; }
  .totals { margin-top: 16px; }
  .totals table { width: auto; margin-left: auto; }
  .totals td { padding: 3px 8px; border: none; }
  .totals .label { color: #667; }
  .totals .total-row td { font-weight: bold; font-size: 15px; border-top: 2px solid #5b7a54; padding-top: 6px; }
  .pill { display: inline-block; padding: 2px 8px; border-radius: 999px; background: #f0ede8; font-size: 11px; }
  .footer { margin-top: 30px; font-size: 11px; color: #999; text-align: center; }
  .client-block { background: #faf9f8; border: 1px solid #e8e4df; border-radius: 6px; padding: 10px 14px; margin-bottom: 18px; }
</style>
</head>
<body>

<div class="header">
  <div class="biz">
    <strong style="font-size:16px;color:#5b7a54;"><?= esc($biz->businessName) ?></strong><br>
    <?= esc($biz->businessEmail) ?>
  </div>
  <div class="inv">
    <h1>INVOICE</h1>
    <strong><?= esc($invoice['number'] ?: '#' . $invoice['id']) ?></strong><br>
    <span class="pill"><?= esc(strtoupper(str_replace('_', ' ', $invoice['status']))) ?></span>
    <?php if ($invoice['due_date']): ?>
      <br><small>Due: <?= esc($invoice['due_date']) ?></small>
    <?php endif ?>
  </div>
</div>

<div class="client-block">
  <strong>Bill to:</strong> <?= esc($invoice['client_name'] ?? 'Client') ?><br>
  <?php if ($invoice['client_email']): ?><?= esc($invoice['client_email']) ?><br><?php endif ?>
  <?php if ($invoice['client_phone'] ?? ''): ?><?= esc($invoice['client_phone']) ?><br><?php endif ?>
  <?php if ($invoice['client_address'] ?? ''): ?><?= esc($invoice['client_address']) ?><?php endif ?>
</div>

<h2>Summary</h2>
<table>
  <thead>
    <tr><th>Description</th><th style="text-align:right;">Amount</th></tr>
  </thead>
  <tbody>
    <tr>
      <td>Floral services per quote</td>
      <td style="text-align:right;"><?= fbk_money($invoice['subtotal']) ?></td>
    </tr>
    <?php if ((float)$invoice['tax'] > 0): ?>
    <tr>
      <td>Tax</td>
      <td style="text-align:right;"><?= fbk_money($invoice['tax']) ?></td>
    </tr>
    <?php endif ?>
  </tbody>
</table>

<div class="totals">
  <table>
    <tr><td class="label">Subtotal</td><td style="text-align:right;"><?= fbk_money($invoice['subtotal']) ?></td></tr>
    <tr><td class="label">Tax</td><td style="text-align:right;"><?= fbk_money($invoice['tax']) ?></td></tr>
    <tr class="total-row"><td>Total</td><td style="text-align:right;"><?= fbk_money($invoice['total']) ?></td></tr>
    <tr><td class="label" style="color:#059669;">Amount paid</td><td style="text-align:right;color:#059669;"><?= fbk_money($invoice['amount_paid']) ?></td></tr>
    <tr><td><strong>Balance due</strong></td><td style="text-align:right;"><strong><?= fbk_money($invoice['balance_due']) ?></strong></td></tr>
  </table>
</div>

<?php if ($payments): ?>
<h2>Payment history</h2>
<table>
  <thead>
    <tr><th>Date</th><th>Kind</th><th>Method</th><th style="text-align:right;">Amount</th></tr>
  </thead>
  <tbody>
  <?php foreach ($payments as $p): ?>
    <tr>
      <td><?= esc(substr($p['paid_at'] ?? $p['created_at'] ?? '', 0, 10)) ?></td>
      <td><?= esc($p['kind']) ?></td>
      <td><?= esc($p['method']) ?></td>
      <td style="text-align:right;"><?= fbk_money($p['amount']) ?></td>
    </tr>
  <?php endforeach ?>
  </tbody>
</table>
<?php endif ?>

<div class="footer">
  <?= esc($biz->businessName) ?> · <?= esc($biz->businessEmail) ?><br>
  Thank you for your business!
</div>
</body>
</html>
