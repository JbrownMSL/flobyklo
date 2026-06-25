<?= $this->extend('layout/admin') ?>
<?= $this->section('content') ?>
<h1>Reservations</h1>
<div class="card">
  <div class="row" style="font-size:.85rem;">
    <a class="pill" href="<?= site_url('admin/reservations') ?>">All</a>
    <?php foreach (['confirmed', 'picked_up', 'returned', 'cancelled'] as $s): ?>
      <a class="pill" href="<?= site_url('admin/reservations?status=' . $s) ?>"><?= esc($s) ?></a>
    <?php endforeach ?>
  </div>
  <table style="margin-top:.6rem;"><thead><tr><th>#</th><th>Dates</th><th>Status</th><th>Total</th><th></th></tr></thead><tbody>
  <?php foreach ($reservations as $r): ?>
    <tr><td>#<?= (int) $r['id'] ?></td><td><?= esc($r['start_date']) ?> → <?= esc($r['end_date']) ?></td>
        <td><span class="pill"><?= esc($r['status']) ?></span></td><td><?= wtr_money($r['grand_total']) ?></td>
        <td><a href="<?= site_url('admin/reservations/' . $r['id']) ?>">Manage</a></td></tr>
  <?php endforeach ?>
  <?php if (! $reservations): ?><tr><td colspan="5" class="muted">None.</td></tr><?php endif ?>
  </tbody></table>
</div>
<?= $this->endSection() ?>
