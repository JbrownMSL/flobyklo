<?= $this->extend('layout/main') ?>
<?= $this->section('content') ?>
<div class="row" style="align-items:flex-end;">
  <div style="flex:2 1 300px;"><h1 style="margin:0;"><?= esc($heading) ?></h1></div>
  <form action="<?= site_url('search') ?>" method="get" style="flex:1 1 220px;">
    <input type="text" name="q" placeholder="Search equipment…" value="<?= esc(service('request')->getGet('q') ?? '') ?>">
  </form>
</div>

<div class="row" style="margin:.8rem 0;font-size:.9rem;">
  <a class="pill" href="<?= site_url('catalog') ?>">All</a>
  <?php foreach ($categories as $c): ?>
    <a class="pill" href="<?= site_url('catalog/' . $c['slug']) ?>"><?= esc($c['name']) ?></a>
  <?php endforeach ?>
</div>

<div class="grid">
  <?php foreach ($equipment as $e): ?>
    <div class="tile">
      <a href="<?= site_url('equipment/' . $e['id']) ?>">
        <?php if (! empty($e['thumb'])): ?>
          <img src="<?= esc($e['thumb']) ?>" alt="<?= esc($e['name']) ?>" loading="lazy">
        <?php else: ?>
          <div style="height:150px;background:#eee;display:flex;align-items:center;justify-content:center;color:#aaa;font-size:.85rem;">No photo</div>
        <?php endif ?>
      </a>
      <div class="body">
        <strong><a href="<?= site_url('equipment/' . $e['id']) ?>"><?= esc($e['name']) ?></a></strong>
        <span class="muted"><?= esc($e['category_name'] ?? '') ?></span>
        <?php if (! empty($e['is_dangerous'])): ?><span class="pill" style="background:#fef2f2;color:#b91c1c;border-color:#fecaca;">⚠ PPE required</span><?php endif ?>
        <span class="price"><?= wtr_money($e['daily_rate']) ?><span class="muted" style="font-weight:400;">/day</span></span>
        <a class="btn" style="margin-top:.5rem;" href="<?= site_url('equipment/' . $e['id']) ?>">View &amp; book</a>
      </div>
    </div>
  <?php endforeach ?>
  <?php if (! $equipment): ?><div class="card">No equipment found.</div><?php endif ?>
</div>
<?= $this->endSection() ?>
