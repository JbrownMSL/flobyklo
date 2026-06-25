<?= $this->extend('layout/admin') ?>
<?= $this->section('content') ?>
<div class="row" style="align-items:center;">
  <h1 style="flex:1;">Inventory</h1>
  <a class="btn" href="<?= site_url('admin/equipment/new') ?>">+ New equipment</a>
</div>

<div class="card">
  <h2>One-time CSV import</h2>
  <p class="muted">Columns: <code>name, sku, description, daily_rate, quantity, tax_class, is_trailer</code>. <code>tax_class</code> = <code>equipment_rental</code> or <code>motor_vehicle_rental</code>.</p>
  <form action="<?= site_url('admin/equipment/import') ?>" method="post" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <input type="file" name="csv" accept=".csv" required>
    <button class="btn" type="submit" style="margin-top:.4rem;">Import</button>
  </form>
</div>

<div class="card">
  <table><thead><tr><th>Name</th><th>Category</th><th>Daily</th><th>Qty</th><th>Tax class</th><th>Active</th><th></th></tr></thead><tbody>
  <?php foreach ($equipment as $e): ?>
    <tr><td><?= esc($e['name']) ?><?= $e['is_trailer'] ? ' <span class="pill">trailer</span>' : '' ?></td>
        <td class="muted"><?= (int) $e['category_id'] ?: '—' ?></td>
        <td><?= wtr_money($e['daily_rate']) ?></td><td><?= (int) $e['quantity'] ?></td>
        <td class="muted"><?= esc($e['tax_class']) ?></td>
        <td><?= $e['active'] ? '✓' : '—' ?></td>
        <td><a href="<?= site_url('admin/equipment/' . $e['id']) ?>">Edit</a></td></tr>
  <?php endforeach ?>
  <?php if (! $equipment): ?><tr><td colspan="7" class="muted">No equipment yet — add one or import a CSV.</td></tr><?php endif ?>
  </tbody></table>
</div>
<?= $this->endSection() ?>
