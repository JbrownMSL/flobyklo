<?= $this->extend('layout/main') ?>
<?= $this->section('content') ?>
<h1>Rental Terms</h1>
<div class="card"><?= $contract['body_html'] ?? '<p>Terms will be posted soon.</p>' ?></div>
<?= $this->endSection() ?>
