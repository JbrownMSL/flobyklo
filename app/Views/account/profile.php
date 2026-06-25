<?= $this->extend('layout/main') ?>
<?= $this->section('content') ?>
<h1>My Profile</h1>
<div class="card">
  <form action="<?= site_url('account/profile') ?>" method="post">
    <?= csrf_field() ?>
    <label>Full name</label><input type="text" name="full_name" value="<?= esc($profile['full_name'] ?? '') ?>">
    <div class="row">
      <div><label>Phone</label><input type="text" name="phone" value="<?= esc($profile['phone'] ?? '') ?>"></div>
      <div><label>ZIP</label><input type="text" name="zip" value="<?= esc($profile['zip'] ?? '') ?>"></div>
    </div>
    <label>Address</label><input type="text" name="address" value="<?= esc($profile['address'] ?? '') ?>">
    <div class="row">
      <div><label>City</label><input type="text" name="city" value="<?= esc($profile['city'] ?? '') ?>"></div>
      <div><label>State</label><input type="text" name="state" maxlength="2" value="<?= esc($profile['state'] ?? '') ?>"></div>
    </div>
    <button class="btn" type="submit" style="margin-top:.6rem;">Save</button>
  </form>
</div>
<?= $this->endSection() ?>
