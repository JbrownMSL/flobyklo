<?= $this->extend('layout/admin') ?>
<?= $this->section('content') ?>
<h1>Owner Statement</h1>
<div class="card">
  <form method="get" class="row" style="align-items:flex-end;">
    <div><label>Owner entity</label><select name="entity">
      <?php foreach (array_unique(array_merge(['WTR'], $entities)) as $e): ?><option value="<?= esc($e) ?>" <?= $entity === $e ? 'selected' : '' ?>><?= esc($e) ?></option><?php endforeach ?>
    </select></div>
    <div style="flex:0;"><button class="btn" type="submit">View</button></div>
  </form>
  <table style="margin-top:.6rem;"><thead><tr><th>Equipment</th><th>Dates</th><th>Allocated</th></tr></thead><tbody>
  <?php $tot = 0; foreach ($rows as $r): $tot += (float) $r['amount']; ?>
    <tr><td><?= esc($r['equipment_name']) ?></td><td><?= esc($r['start_date'] ?? '') ?> → <?= esc($r['end_date'] ?? '') ?></td><td><?= wtr_money($r['amount']) ?></td></tr>
  <?php endforeach ?>
  <?php if (! $rows): ?><tr><td colspan="3" class="muted">No allocations for <?= esc($entity) ?>.</td></tr><?php endif ?>
  </tbody><tfoot><tr style="font-weight:800;"><td colspan="2">Total to <?= esc($entity) ?></td><td><?= wtr_money($tot) ?></td></tr></tfoot></table>
</div>
<?= $this->endSection() ?>
