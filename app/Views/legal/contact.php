<?= $this->extend('layout/main') ?>
<?= $this->section('content') ?>
<h1>Contact Us</h1>
<div class="card">
  <form action="<?= site_url('contact') ?>" method="post">
    <?= csrf_field() ?>
    <label>Your name</label><input type="text" name="name" value="<?= old('name') ?>" required>
    <label>Email</label><input type="email" name="email" value="<?= old('email') ?>" required>
    <label>Message</label><textarea name="message" rows="5" required><?= old('message') ?></textarea>
    <button class="btn" type="submit" style="margin-top:.6rem;">Send</button>
  </form>
</div>
<?= $this->endSection() ?>
