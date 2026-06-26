<?= $this->extend('layout/admin') ?>
<?= $this->section('content') ?>
<?php
$statusColors = [
    'lead'      => '#2563eb',
    'consult'   => '#d97706',
    'booked'    => '#16a34a',
    'completed' => '#0f766e',
    'lost'      => '#9ca3af',
];
?>
<div class="row" style="align-items:center;">
  <h1 style="flex:1;">Clients</h1>
  <a class="btn" href="<?= site_url('admin/clients/new') ?>">+ New Client</a>
</div>

<div style="display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:1rem;">
  <a href="<?= site_url('admin/clients') ?>"
     style="padding:.3rem .8rem;border-radius:999px;border:1px solid <?= $current === 'all' ? '#7c9a6f' : '#d7dbe3' ?>;background:<?= $current === 'all' ? '#7c9a6f' : '#fff' ?>;color:<?= $current === 'all' ? '#fff' : '#334' ?>;font-size:.82rem;text-decoration:none;">
    All <span style="opacity:.7;">(<?= $counts['all'] ?>)</span>
  </a>
  <?php foreach (['lead', 'consult', 'booked', 'completed', 'lost'] as $s): ?>
  <a href="<?= site_url('admin/clients?status=' . $s) ?>"
     style="padding:.3rem .8rem;border-radius:999px;border:1px solid <?= $current === $s ? $statusColors[$s] : '#d7dbe3' ?>;background:<?= $current === $s ? $statusColors[$s] : '#fff' ?>;color:<?= $current === $s ? '#fff' : '#334' ?>;font-size:.82rem;text-decoration:none;">
    <?= ucfirst($s) ?> <span style="opacity:.7;">(<?= $counts[$s] ?? 0 ?>)</span>
  </a>
  <?php endforeach ?>
</div>

<div class="card" style="padding:0;overflow:hidden;">
  <table>
    <thead>
      <tr>
        <th>Name</th>
        <th>Email</th>
        <th>Phone</th>
        <th>Source</th>
        <th>Status</th>
        <th>Added</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
    <?php if (empty($clients)): ?>
      <tr><td colspan="7" class="muted" style="text-align:center;padding:2rem;">No clients<?= $current !== 'all' ? ' with status <em>' . esc($current) . '</em>' : '' ?>.</td></tr>
    <?php else: ?>
      <?php foreach ($clients as $c): ?>
      <tr>
        <td><a href="<?= site_url('admin/clients/' . (int) $c['id']) ?>"><?= esc($c['name']) ?></a></td>
        <td><?= $c['email'] ? esc($c['email']) : '<span class="muted">—</span>' ?></td>
        <td><?= $c['phone'] ? esc($c['phone']) : '<span class="muted">—</span>' ?></td>
        <td><?= esc(ucfirst($c['source'])) ?></td>
        <td>
          <span class="pill" style="background:<?= $statusColors[$c['status']] ?? '#f0ede8' ?>;color:#fff;border-color:transparent;">
            <?= esc(ucfirst($c['status'])) ?>
          </span>
        </td>
        <td class="muted" style="font-size:.82rem;"><?= esc(substr((string) $c['created_at'], 0, 10)) ?></td>
        <td style="white-space:nowrap;">
          <a href="<?= site_url('admin/clients/' . (int) $c['id']) ?>">Edit</a>
          &nbsp;|&nbsp;
          <form method="post" action="<?= site_url('admin/clients/' . (int) $c['id'] . '/status') ?>" style="display:inline;">
            <?= csrf_field() ?>
            <select name="status" onchange="this.form.submit()" style="padding:.15rem .3rem;font-size:.8rem;width:auto;border-radius:5px;">
              <?php foreach (['lead', 'consult', 'booked', 'completed', 'lost'] as $s): ?>
                <option value="<?= $s ?>"<?= $c['status'] === $s ? ' selected' : '' ?>><?= ucfirst($s) ?></option>
              <?php endforeach ?>
            </select>
          </form>
        </td>
      </tr>
      <?php endforeach ?>
    <?php endif ?>
    </tbody>
  </table>
</div>
<?= $this->endSection() ?>
