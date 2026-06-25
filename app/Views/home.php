<?= $this->extend('layout/main') ?>
<?= $this->section('content') ?>
<div class="card" style="background:#1a1a1a;color:#fff;border:0;">
  <h1 style="color:#fff;">Rent the right tool for the weekend.</h1>
  <p class="muted" style="color:#cbd5e1;">Reserve equipment online, pick your dates, and pick it up ready to go.</p>
  <a class="btn" href="<?= site_url('catalog') ?>">Browse the catalog →</a>
</div>

<h2>Shop by category</h2>
<div class="grid" style="grid-template-columns:repeat(auto-fill,minmax(160px,1fr));">
  <?php foreach ($categories as $c): ?>
    <a class="tile" style="padding:1rem;text-align:center;font-weight:600;color:#1a1a1a;" href="<?= site_url('catalog/' . $c['slug']) ?>"><?= esc($c['name']) ?></a>
  <?php endforeach ?>
</div>

<h2 style="margin-top:1.5rem;">Featured equipment</h2>
<div class="grid">
  <?php foreach ($featured as $e): ?>
    <div class="tile">
      <?php $img = $e['id']; ?>
      <div class="body">
        <strong><a href="<?= site_url('equipment/' . $e['id']) ?>"><?= esc($e['name']) ?></a></strong>
        <span class="muted"><?= esc($e['category_name'] ?? '') ?></span>
        <span class="price"><?= wtr_money($e['daily_rate']) ?><span class="muted" style="font-weight:400;">/day</span></span>
        <a class="btn" style="margin-top:.5rem;" href="<?= site_url('equipment/' . $e['id']) ?>">View &amp; book</a>
      </div>
    </div>
  <?php endforeach ?>
  <?php if (! $featured): ?><p class="muted">No equipment listed yet.</p><?php endif ?>
</div>
<?= $this->endSection() ?>
