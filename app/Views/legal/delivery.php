<?= $this->extend('layout/main') ?>
<?= $this->section('content') ?>
<h1>Request Delivery</h1>
<div class="card">
  <p class="muted">Delivery is available for an extra charge. Tell us what you need and we'll reply with a quote.</p>
  <form action="<?= site_url('delivery') ?>" method="post">
    <?= csrf_field() ?>
    <div class="row">
      <div><label>Your name</label><input type="text" name="name" value="<?= old('name') ?>" required></div>
      <div><label>Email</label><input type="email" name="email" value="<?= old('email') ?>" required></div>
      <div><label>Phone</label><input type="text" name="phone" value="<?= old('phone') ?>"></div>
    </div>
    <label>Equipment you'd like</label><textarea name="equipment" rows="2" placeholder="e.g. Bobcat MT85 + auger"><?= old('equipment') ?></textarea>
    <label>Delivery location (address)</label><input type="text" name="location" value="<?= old('location') ?>">
    <div class="row">
      <div><label>Delivery date &amp; time</label><input type="text" name="pickup" placeholder="e.g. Sat 6/14 8am" value="<?= old('pickup') ?>"></div>
      <div><label>Pickup/return date &amp; time</label><input type="text" name="dropoff" placeholder="e.g. Sun 6/15 5pm" value="<?= old('dropoff') ?>"></div>
    </div>
    <label>Notes</label><textarea name="notes" rows="2"><?= old('notes') ?></textarea>
    <button class="btn" type="submit" style="margin-top:.6rem;">Request a delivery quote</button>
  </form>
</div>
<?= $this->endSection() ?>
