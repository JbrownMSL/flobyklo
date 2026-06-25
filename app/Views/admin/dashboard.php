<?= $this->extend('layout/admin') ?>
<?= $this->section('content') ?>
<h1>Dashboard</h1>
<div class="kpis">
  <div class="kpi"><div class="n"><?= count($upcoming) ?></div><div class="l">Upcoming rentals</div></div>
  <div class="kpi"><div class="n"><?= count($returnsDue) ?></div><div class="l">Out / returns due</div></div>
  <div class="kpi"><div class="n" style="color:<?= count($overdue) ? '#ef4444' : '#1a1a1a' ?>;"><?= count($overdue) ?></div><div class="l">Overdue</div></div>
  <div class="kpi"><div class="n"><?= (int) $maintDue ?></div><div class="l">In maintenance</div></div>
  <div class="kpi"><div class="n"><?= (int) $openDamage ?></div><div class="l">Damage reports</div></div>
  <div class="kpi"><div class="n"><?= wtr_money($revenue30) ?></div><div class="l">Rental revenue (30d)</div></div>
</div>

<div class="card">
  <h2>Returns due</h2>
  <?php if (! $returnsDue): ?><p class="muted">Nothing out right now.</p><?php else: ?>
  <table><thead><tr><th>#</th><th>End date</th><th></th></tr></thead><tbody>
  <?php foreach ($returnsDue as $r): ?>
    <tr><td><a href="<?= site_url('admin/reservations/' . $r['id']) ?>">#<?= (int) $r['id'] ?></a></td>
        <td <?= strtotime($r['end_date']) < time() ? 'style="color:#ef4444;font-weight:700;"' : '' ?>><?= esc($r['end_date']) ?></td>
        <td><a href="<?= site_url('admin/reservations/' . $r['id']) ?>">Manage</a></td></tr>
  <?php endforeach ?>
  </tbody></table>
  <?php endif ?>
</div>

<div class="card">
  <h2>Upcoming</h2>
  <?php if (! $upcoming): ?><p class="muted">No upcoming reservations.</p><?php else: ?>
  <table><thead><tr><th>#</th><th>Start</th><th>End</th><th>Total</th></tr></thead><tbody>
  <?php foreach ($upcoming as $r): ?>
    <tr><td><a href="<?= site_url('admin/reservations/' . $r['id']) ?>">#<?= (int) $r['id'] ?></a></td><td><?= esc($r['start_date']) ?></td><td><?= esc($r['end_date']) ?></td><td><?= wtr_money($r['grand_total']) ?></td></tr>
  <?php endforeach ?>
  </tbody></table>
  <?php endif ?>
</div>
<?= $this->endSection() ?>
