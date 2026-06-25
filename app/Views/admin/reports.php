<?= $this->extend('layout/admin') ?>
<?= $this->section('content') ?>
<h1>Reports</h1>
<div class="card">
  <ul style="line-height:2;">
    <li><a href="<?= site_url('admin/reports/tax') ?>">Tax collected (for remittance)</a> — split by equipment vs motor-vehicle rental tax.</li>
    <li><a href="<?= site_url('admin/reports/utilization') ?>">Utilization &amp; revenue per item</a></li>
    <li><a href="<?= site_url('admin/reports/pnl') ?>">Per-item P&amp;L (after owner splits)</a></li>
    <li><a href="<?= site_url('admin/reports/owner/0') ?>">Owner statements</a></li>
  </ul>
</div>
<?= $this->endSection() ?>
