<!doctype html>
<html><head><meta charset="utf-8"><title>Form 8300 worksheet — <?= esc($client['name']) ?></title>
<style>
 body{font-family:Georgia,serif;max-width:760px;margin:2rem auto;color:#222;line-height:1.45}
 h1{font-size:1.3rem;margin:0 0 .3rem} h2{font-size:1rem;margin:1.4rem 0 .4rem;border-bottom:1px solid #ccc}
 table{border-collapse:collapse;width:100%} td,th{border:1px solid #ccc;padding:.35rem .5rem;text-align:left}
 .note{background:#f6f6f0;border:1px solid #ddd;padding:.7rem .9rem;font-size:.9rem}
 @media print{.noprint{display:none}}
</style></head><body>
<p class="noprint"><button onclick="window.print()">Print</button></p>
<h1>IRS Form 8300 — worksheet</h1>
<div>Report of Cash Payments Over $10,000 Received in a Trade or Business · Flora by Klo LLC · EIN 42-3802286</div>

<h2>Part I — the person you received the cash from</h2>
<table>
 <tr><th style="width:30%">Name</th><td><?= esc($client['name']) ?></td></tr>
 <tr><th>Address</th><td><?= esc($client['address'] ?? '') ?: '<em>not on file — needed</em>' ?></td></tr>
 <tr><th>Phone / email</th><td><?= esc(trim(($client['phone'] ?? '') . '  ' . ($client['email'] ?? ''))) ?></td></tr>
 <tr><th>TIN (SSN/EIN), date of birth, ID used to verify</th><td><em>Collect from the client — required on the form, not stored here.</em></td></tr>
</table>

<h2>Part III — the cash received (last 12 months)</h2>
<table>
 <tr><th>Date</th><th>Invoice</th><th style="text-align:right">Amount</th></tr>
 <?php foreach ($cash['rows'] as $r): ?>
 <tr><td><?= esc(substr((string) $r['paid_at'], 0, 10)) ?></td><td>#<?= (int) $r['invoice_id'] ?></td><td style="text-align:right"><?= fbk_money($r['amount']) ?></td></tr>
 <?php endforeach ?>
 <tr><th colspan="2">Total cash</th><th style="text-align:right"><?= fbk_money($cash['total']) ?></th></tr>
</table>

<h2>How to file</h2>
<div class="note">
 <p><strong>Cash only.</strong> Card, Square and personal-check payments do not count. Cashier's checks and money orders count only in some transactions.</p>
 <p><strong>Due within 15 days</strong> of the payment that took related cash over $10,000. Give the client a written statement by January 31 of the next year.</p>
 <p><strong>E-file</strong> through the FinCEN BSA E-Filing System if the business files 10 or more information returns in the year; otherwise the paper form may be mailed.</p>
 <p>Form: <a href="https://www.irs.gov/pub/irs-pdf/f8300.pdf">irs.gov/pub/irs-pdf/f8300.pdf</a> · E-file: <a href="https://bsaefiling.fincen.treas.gov/">bsaefiling.fincen.treas.gov</a></p>
</div>
</body></html>
