<?= $this->extend('layout/main') ?>
<?= $this->section('content') ?>
<p class="muted"><a href="<?= site_url('catalog') ?>">← Catalog</a></p>
<div class="row">
  <div style="flex:1 1 320px;">
    <div class="card">
      <?php $images = array_filter($media, fn($m) => $m['kind'] === 'image'); ?>
      <?php if ($images): ?>
        <?php foreach ($images as $m): ?><img src="<?= esc($m['url']) ?>" alt="<?= esc($m['caption'] ?? $eq['name']) ?>" style="width:100%;border-radius:8px;margin-bottom:.5rem;"><?php endforeach ?>
      <?php else: ?><div style="height:200px;background:#eee;border-radius:8px;display:flex;align-items:center;justify-content:center;color:#aaa;">No photo</div><?php endif ?>

      <?php foreach (array_filter($media, fn($m) => $m['kind'] === 'video') as $m): ?>
        <p><b>How-to video:</b> <a href="<?= esc($m['url']) ?>" target="_blank">Watch ▶</a></p>
      <?php endforeach ?>
    </div>
  </div>

  <div style="flex:1 1 320px;">
    <h1 style="margin-top:0;"><?= esc($eq['name']) ?></h1>
    <p class="price" style="font-size:1.3rem;"><?= wtr_money($eq['daily_rate']) ?><span class="muted" style="font-weight:400;">/day</span>
       &nbsp;·&nbsp; <span class="muted"><?= wtr_money($weekly) ?>/week</span>
       <?php if (! empty($eq['four_hour_rate'])): ?>&nbsp;·&nbsp; <span class="muted"><?= wtr_money($eq['four_hour_rate']) ?>/4-hr</span><?php endif ?></p>
    <p><?= nl2br(esc($eq['description'])) ?></p>

    <?php if ($eq['is_dangerous'] && $safety): ?>
      <div class="card" style="border-color:#fecaca;background:#fef2f2;">
        <strong>⚠ Safety &amp; required PPE</strong>
        <?php if (! empty($safety['ppe_required'])): ?><p><b>PPE:</b> <?= esc($safety['ppe_required']) ?></p><?php endif ?>
        <?php if (! empty($safety['safety_instructions'])): ?><div><?= nl2br(esc($safety['safety_instructions'])) ?></div><?php endif ?>
        <?php if (! empty($safety['safety_video_url'])): ?><p><a href="<?= esc($safety['safety_video_url']) ?>" target="_blank">Watch safety video ▶</a></p><?php endif ?>
      </div>
    <?php endif ?>

    <?php if ($trailer): ?>
      <p class="pill">🚚 A transport trailer (<?= esc($trailer['name']) ?>) is added by default — taxed separately as a motor vehicle. You can remove it in the cart if you have your own.</p>
    <?php endif ?>

    <div class="card">
      <form action="<?= site_url('cart/add') ?>" method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="equipment_id" value="<?= (int) $eq['id'] ?>">
        <div class="row">
          <div><label>Start date</label><input type="date" name="start" required min="<?= date('Y-m-d') ?>"></div>
          <div><label>End date</label><input type="date" name="end" required min="<?= date('Y-m-d') ?>"></div>
        </div>
        <label>Quantity</label><input type="number" name="qty" value="1" min="1" max="<?= (int) $eq['quantity'] ?>">
        <p class="muted">Minimum rental: <?= (int) $eq['min_days'] ?> day(s). <?= (int) $eq['quantity'] ?> unit(s) in fleet.</p>
        <button class="btn" type="submit">Check availability &amp; add to cart</button>
      </form>
    </div>
  </div>
</div>
<?= $this->endSection() ?>
