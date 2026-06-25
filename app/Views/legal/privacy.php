<?= $this->extend('layout/main') ?>
<?= $this->section('content') ?>
<h1>Privacy Policy</h1>
<div class="card">
  <p>Weekend Tool Rentals collects the information needed to process your rental: your name, contact details, rental history, and a payment token from our processor (Square). <strong>We never store your full card number</strong> — card data is handled directly by Square.</p>
  <p>We use your information only to fulfill reservations, communicate about your rentals, and meet legal/tax obligations. We do not sell your information.</p>
  <p>Questions? <a href="<?= site_url('contact') ?>">Contact us</a>. <em>(Placeholder policy — to be finalized before launch.)</em></p>
</div>
<?= $this->endSection() ?>
