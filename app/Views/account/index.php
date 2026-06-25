<?= $this->extend('layout/main') ?>
<?= $this->section('content') ?>
<h1>My Account</h1>
<div class="row">
  <div class="card" style="flex:2 1 320px;">
    <h2>Recent reservations</h2>
    <?php if (! $reservations): ?><p class="muted">No reservations yet. <a href="<?= site_url('catalog') ?>">Browse equipment →</a></p><?php else: ?>
    <table><thead><tr><th>#</th><th>Dates</th><th>Status</th><th>Total</th></tr></thead><tbody>
      <?php foreach ($reservations as $r): ?>
        <tr><td><a href="<?= site_url('account/reservation/' . $r['id']) ?>">#<?= (int) $r['id'] ?></a></td>
            <td><?= esc($r['start_date']) ?> → <?= esc($r['end_date']) ?></td>
            <td><span class="pill"><?= esc($r['status']) ?></span></td>
            <td><?= wtr_money($r['grand_total']) ?></td></tr>
      <?php endforeach ?>
    </tbody></table>
    <p><a href="<?= site_url('account/reservations') ?>">All reservations →</a></p>
    <?php endif ?>
  </div>
  <div class="card" style="flex:1 1 220px;">
    <h2>Profile</h2>
    <?php if ($profile && $profile['full_name']): ?>
      <p><?= esc($profile['full_name']) ?><br><span class="muted"><?= esc($profile['phone']) ?></span></p>
    <?php else: ?><p class="muted">No profile details yet.</p><?php endif ?>
    <a class="btn ghost" href="<?= site_url('account/profile') ?>">Edit profile</a>
  </div>
</div>
<?= $this->endSection() ?>
