<?= $this->extend('layout/admin') ?>
<?= $this->section('content') ?>
<h1>Maintenance</h1>
<div class="card">
  <h2>Log maintenance (blocks the item for those dates)</h2>
  <form action="<?= site_url('admin/maintenance/log') ?>" method="post">
    <?= csrf_field() ?>
    <div class="row">
      <div><label>Equipment</label><select name="equipment_id" required>
        <?php foreach ($equipment as $e): ?><option value="<?= (int) $e['id'] ?>"><?= esc($e['name']) ?></option><?php endforeach ?>
      </select></div>
      <div><label>Qty units</label><input type="number" name="qty" value="1" min="1"></div>
    </div>
    <div class="row">
      <div><label>Start</label><input type="date" name="start_date" required></div>
      <div><label>End</label><input type="date" name="end_date"></div>
      <div><label>Cost</label><input type="number" step="0.01" name="cost" value="0"></div>
    </div>
    <label>Type / notes</label><input type="text" name="kind" placeholder="e.g. blade replacement"><textarea name="notes" rows="2"></textarea>
    <button class="btn" type="submit" style="margin-top:.4rem;">Log &amp; block</button>
  </form>
</div>
<div class="card">
  <table><thead><tr><th>Equipment</th><th>Dates</th><th>Type</th><th>Cost</th><th></th></tr></thead><tbody>
  <?php foreach ($log as $m): ?>
    <tr><td><?= esc($m['equipment_name']) ?></td><td><?= esc($m['start_date']) ?> → <?= esc($m['end_date'] ?? '—') ?></td>
        <td><?= esc($m['kind']) ?></td><td><?= wtr_money($m['cost']) ?></td>
        <td><?php if (! empty($m['blackout_id'])): ?><form action="<?= site_url('admin/maintenance/' . $m['id'] . '/close') ?>" method="post"><?= csrf_field() ?><button class="btn ghost" style="padding:.15rem .5rem;">Close</button></form><?php endif ?></td></tr>
  <?php endforeach ?>
  <?php if (! $log): ?><tr><td colspan="5" class="muted">No maintenance logged.</td></tr><?php endif ?>
  </tbody></table>
</div>
<?= $this->endSection() ?>
