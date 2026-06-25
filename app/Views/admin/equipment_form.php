<?= $this->extend('layout/admin') ?>
<?= $this->section('content') ?>
<p class="muted"><a href="<?= site_url('admin/equipment') ?>">← Inventory</a></p>
<h1><?= esc($title) ?></h1>

<div class="card">
  <form action="<?= site_url('admin/equipment/save') ?>" method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= (int) ($eq['id'] ?? 0) ?>">
    <label>Name</label><input type="text" name="name" value="<?= esc($eq['name'] ?? '') ?>" required>
    <div class="row">
      <div><label>Category</label><select name="category_id"><option value="">—</option>
        <?php foreach ($categories as $c): ?><option value="<?= (int) $c['id'] ?>" <?= ($eq['category_id'] ?? 0) == $c['id'] ? 'selected' : '' ?>><?= esc($c['name']) ?></option><?php endforeach ?>
      </select></div>
      <div><label>SKU</label><input type="text" name="sku" value="<?= esc($eq['sku'] ?? '') ?>"></div>
    </div>
    <label>Description</label><textarea name="description" rows="3"><?= esc($eq['description'] ?? '') ?></textarea>
    <div class="row">
      <div><label>4-hour rate</label><input type="number" step="0.01" name="four_hour_rate" value="<?= esc($eq['four_hour_rate'] ?? '') ?>"></div>
      <div><label>Daily rate</label><input type="number" step="0.01" name="daily_rate" value="<?= esc($eq['daily_rate'] ?? '0') ?>"></div>
      <div><label>Weekly rate (blank = 6× daily)</label><input type="number" step="0.01" name="weekly_rate" value="<?= esc($eq['weekly_rate'] ?? '') ?>"></div>
      <div><label>Min days</label><input type="number" name="min_days" value="<?= (int) ($eq['min_days'] ?? 1) ?>"></div>
    </div>
    <div class="row">
      <div><label>Item value / cost (sets $300/$500 deposit)</label><input type="number" step="0.01" name="cost" value="<?= esc($eq['cost'] ?? '') ?>"></div>
    </div>
    <div class="row">
      <div><label>Quantity (fleet)</label><input type="number" name="quantity" value="<?= (int) ($eq['quantity'] ?? 1) ?>"></div>
      <div><label>Security deposit (blank = % of rental)</label><input type="number" step="0.01" name="damage_deposit" value="<?= esc($eq['damage_deposit'] ?? '') ?>"></div>
      <div><label>Tax class</label><select name="tax_class">
        <option value="equipment_rental" <?= ($eq['tax_class'] ?? '') === 'equipment_rental' ? 'selected' : '' ?>>Equipment rental</option>
        <option value="motor_vehicle_rental" <?= ($eq['tax_class'] ?? '') === 'motor_vehicle_rental' ? 'selected' : '' ?>>Motor-vehicle (trailer)</option>
      </select></div>
    </div>
    <div class="row">
      <div><label>Auto-add trailer</label><select name="requires_trailer_id"><option value="">none</option>
        <?php foreach ($trailers as $t): ?><option value="<?= (int) $t['id'] ?>" <?= ($eq['requires_trailer_id'] ?? 0) == $t['id'] ? 'selected' : '' ?>><?= esc($t['name']) ?></option><?php endforeach ?>
      </select></div>
      <div><label>Flags</label>
        <label style="font-weight:400;"><input type="checkbox" name="is_trailer" value="1" style="width:auto;" <?= ($eq['is_trailer'] ?? 0) ? 'checked' : '' ?>> Is a trailer</label>
        <label style="font-weight:400;"><input type="checkbox" name="is_dangerous" value="1" style="width:auto;" <?= ($eq['is_dangerous'] ?? 0) ? 'checked' : '' ?>> Dangerous (show PPE)</label>
        <label style="font-weight:400;"><input type="checkbox" name="active" value="1" style="width:auto;" <?= ($eq['active'] ?? 1) ? 'checked' : '' ?>> Active / listed</label>
      </div>
    </div>
    <hr>
    <label>PPE required</label><input type="text" name="ppe_required" value="<?= esc($safety['ppe_required'] ?? '') ?>">
    <label>Safety instructions</label><textarea name="safety_instructions" rows="2"><?= esc($safety['safety_instructions'] ?? '') ?></textarea>
    <label>Safety video URL</label><input type="text" name="safety_video_url" value="<?= esc($safety['safety_video_url'] ?? '') ?>">
    <button class="btn" type="submit" style="margin-top:.6rem;">Save equipment</button>
  </form>
</div>

<?php if (! empty($eq)): ?>
<div class="row">
  <div class="card" style="flex:1 1 300px;">
    <h2>Media</h2>
    <?php foreach ($media as $m): ?><p class="muted"><?= esc($m['kind']) ?>: <?= esc($m['url']) ?></p><?php endforeach ?>
    <form action="<?= site_url('admin/equipment/' . $eq['id'] . '/media') ?>" method="post">
      <?= csrf_field() ?>
      <select name="kind"><option value="image">image</option><option value="video">video</option><option value="safety_doc">safety_doc</option></select>
      <input type="text" name="url" placeholder="https://…/photo.jpg">
      <input type="text" name="caption" placeholder="caption (optional)">
      <button class="btn ghost" type="submit" style="margin-top:.4rem;">Add media</button>
    </form>
  </div>
  <div class="card" style="flex:1 1 300px;">
    <h2>Ownership / profit-split</h2>
    <form action="<?= site_url('admin/equipment/' . $eq['id'] . '/ownership') ?>" method="post">
      <?= csrf_field() ?>
      <?php $rows = $ownership ?: [['owner_entity' => 'WTR', 'owned_pct' => 100]]; ?>
      <?php for ($i = 0; $i < 3; $i++): $o = $rows[$i] ?? ['owner_entity' => '', 'owned_pct' => '']; ?>
        <div class="row"><div><input type="text" name="owner_entity[]" placeholder="Owner entity" value="<?= esc($o['owner_entity']) ?>"></div>
          <div><input type="number" step="0.01" name="owned_pct[]" placeholder="%" value="<?= esc($o['owned_pct']) ?>"></div></div>
      <?php endfor ?>
      <p class="muted">Use "WTR" for the share you keep. Percentages should total 100.</p>
      <button class="btn ghost" type="submit">Save ownership</button>
    </form>
  </div>
</div>
<?php endif ?>
<?= $this->endSection() ?>
