<?= $this->extend('layout/main') ?>
<?= $this->section('content') ?>
<div class="card">
  <h1>Flora by Klo — Admin</h1>
  <p>This is a private back-office portal for Flora by Klo staff.</p>
  <a class="btn" href="<?= site_url('login') ?>">Sign in →</a>
</div>
<?= $this->endSection() ?>
