<?= $this->extend('layout/main') ?>
<?= $this->section('content') ?>
<h1>FAQ</h1>
<div class="card">
  <h2>How do I rent?</h2><p>Pick your dates on any equipment page, add to cart, sign the rental agreement, and pay. You'll get a confirmation email.</p>
  <h2>What does it cost to cancel?</h2><p>More than 48 hours before pickup → 75% refunded; 24–48 hours → 50%; less than 24 hours → 25%.</p>
  <h2>Is there a deposit?</h2><p>A refundable security deposit is authorized on your card at booking and released when you return the equipment in good condition. You may instead choose the optional Damage Waiver.</p>
  <h2>What about trailers?</h2><p>Some equipment includes a transport trailer by default (taxed separately as a motor vehicle under Utah law). You can remove it at checkout if you have your own adequate trailer.</p>
  <h2>Pickup &amp; return?</h2><p><?= esc(config('Wtr')->pickupHours) ?>. Bring valid ID and your confirmation.</p>
</div>
<?= $this->endSection() ?>
