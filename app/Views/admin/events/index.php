<?= $this->extend('layout/admin') ?>
<?= $this->section('content') ?>

<div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.5rem;margin-bottom:.6rem;">
  <h1 style="margin:0;">Events</h1>
  <div style="display:flex;gap:.5rem;flex-wrap:wrap;">
    <a href="<?= site_url('admin/events?view=calendar') ?>" class="btn ghost">&#128197; Calendar</a>
    <a href="<?= site_url('admin/events/new') ?>" class="btn">+ New Event</a>
  </div>
</div>

<form method="get" action="<?= site_url('admin/events') ?>" style="margin-bottom:1rem;display:flex;gap:.5rem;align-items:center;flex-wrap:wrap;">
  <label style="margin:0;display:inline;font-weight:600;font-size:.82rem;">Status:</label>
  <select name="status" style="width:auto;padding:.4rem .7rem;" onchange="this.form.submit()">
    <option value="">All</option>
    <?php foreach ($statuses as $s): ?>
    <option value="<?= esc($s) ?>" <?= $status === $s ? 'selected' : '' ?>><?= ucfirst(esc($s)) ?></option>
    <?php endforeach ?>
  </select>
  <?php if ($status): ?>
  <a href="<?= site_url('admin/events') ?>" class="btn ghost" style="padding:.4rem .7rem;font-size:.82rem;">Clear</a>
  <?php endif ?>
</form>

<div class="card" style="padding:0;overflow:hidden;">
  <table>
    <thead>
      <tr>
        <th>Date</th>
        <th>Client</th>
        <th>Type</th>
        <th>Venue</th>
        <th>Guests</th>
        <th>Status</th>
        <th>Cap</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($events)): ?>
      <tr><td colspan="8" class="muted" style="text-align:center;padding:2rem;">No events found.</td></tr>
      <?php else: foreach ($events as $ev): ?>
      <tr>
        <td><?= esc($ev['event_date']) ?></td>
        <td><?= esc($ev['client_name'] ?? '—') ?></td>
        <td><span class="pill"><?= esc($ev['type']) ?></span></td>
        <td><?= esc($ev['venue'] ?? '—') ?></td>
        <td><?= $ev['guest_count'] !== null ? (int) $ev['guest_count'] : '—' ?></td>
        <td>
          <span class="pill" style="<?= $ev['status'] === 'confirmed' ? 'background:#d1fae5;color:#065f46;' : ($ev['status'] === 'cancelled' ? 'background:#fee2e2;color:#991b1b;' : '') ?>">
            <?= esc($ev['status']) ?>
          </span>
        </td>
        <td style="text-align:center;"><?= $ev['capacity_weekend'] ? '&#10003;' : '' ?></td>
        <td><a href="<?= site_url('admin/events/' . (int) $ev['id']) ?>">Edit</a></td>
      </tr>
      <?php endforeach; endif ?>
    </tbody>
  </table>
</div>

<?= $this->endSection() ?>
