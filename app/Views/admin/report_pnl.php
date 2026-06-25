<?= $this->extend('layout/admin') ?>
<?= $this->section('content') ?>
<h1>Per-item P&amp;L</h1>
<div class="card">
  <table><thead><tr><th>Equipment</th><th>Revenue</th><th>Costs</th><th>Owner cut</th><th>Net to WTR</th></tr></thead><tbody>
  <?php foreach ($rows as $r): ?>
    <tr><td><?= esc($r['name']) ?></td><td><?= wtr_money($r['revenue']) ?></td><td><?= wtr_money($r['costs']) ?></td><td><?= wtr_money($r['owner_cut']) ?></td><td style="font-weight:700;color:<?= $r['net'] >= 0 ? '#16a34a' : '#ef4444' ?>;"><?= wtr_money($r['net']) ?></td></tr>
  <?php endforeach ?>
  <?php if (! $rows): ?><tr><td colspan="5" class="muted">No equipment.</td></tr><?php endif ?>
  </tbody></table>
</div>
<?= $this->endSection() ?>
