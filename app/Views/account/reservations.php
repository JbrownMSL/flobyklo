<?= $this->extend('layout/main') ?>
<?= $this->section('content') ?>
<h1>My Reservations</h1>
<div class="card">
  <?php if (! $reservations): ?><p class="muted">None yet.</p><?php else: ?>
  <table><thead><tr><th>#</th><th>Dates</th><th>Status</th><th>Total</th><th></th></tr></thead><tbody>
  <?php foreach ($reservations as $r): ?>
    <tr><td>#<?= (int) $r['id'] ?></td><td><?= esc($r['start_date']) ?> → <?= esc($r['end_date']) ?></td>
        <td><span class="pill"><?= esc($r['status']) ?></span></td><td><?= wtr_money($r['grand_total']) ?></td>
        <td><a href="<?= site_url('account/reservation/' . $r['id']) ?>">View</a></td></tr>
  <?php endforeach ?>
  </tbody></table>
  <?php endif ?>
</div>
<?= $this->endSection() ?>
