<?= $this->extend('layout/admin') ?>
<?= $this->section('content') ?>
<h1>Utilization &amp; Revenue</h1>
<div class="card">
  <table><thead><tr><th>Equipment</th><th>Rentals</th><th>Unit-days booked</th><th>Revenue</th></tr></thead><tbody>
  <?php foreach ($rows as $r): ?>
    <tr><td><?= esc($r['name']) ?></td><td><?= (int) $r['rentals'] ?></td><td><?= (int) $r['unit_days'] ?></td><td><?= wtr_money($r['revenue']) ?></td></tr>
  <?php endforeach ?>
  <?php if (! $rows): ?><tr><td colspan="4" class="muted">No rental activity yet.</td></tr><?php endif ?>
  </tbody></table>
</div>
<?= $this->endSection() ?>
