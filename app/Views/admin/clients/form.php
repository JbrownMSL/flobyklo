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
$isEdit = $client !== null;
?>
<p class="muted"><a href="<?= site_url('admin/clients') ?>">← Clients</a></p>
<h1><?= esc($title) ?><?php if ($isEdit): ?> <span class="pill" style="vertical-align:middle;background:<?= $statusColors[$client['status']] ?? '#f0ede8' ?>;color:#fff;border-color:transparent;font-size:.7rem;"><?= esc(ucfirst($client['status'])) ?></span><?php endif ?></h1>

<?php if (! empty($errors)): ?>
<div class="flash err">
  <?php foreach ($errors as $e): ?><div><?= esc($e) ?></div><?php endforeach ?>
</div>
<?php endif ?>

<div class="card">
  <form action="<?= site_url('admin/clients/save') ?>" method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= $isEdit ? (int) $client['id'] : 0 ?>">

    <div class="row">
      <div style="flex:2;">
        <label>Name <span style="color:#c00;">*</span></label>
        <input type="text" name="name" value="<?= esc(old('name', $client['name'] ?? '')) ?>" required maxlength="150" placeholder="Full name">
      </div>
      <div style="flex:2;">
        <label>Email</label>
        <input type="email" name="email" value="<?= esc(old('email', $client['email'] ?? '')) ?>" maxlength="150" placeholder="email@example.com">
      </div>
      <div>
        <label>Phone</label>
        <input type="text" name="phone" value="<?= esc(old('phone', $client['phone'] ?? '')) ?>" maxlength="30" placeholder="(555) 000-0000">
      </div>
    </div>

    <div class="row">
      <div style="flex:3;">
        <label>Address</label>
        <input type="text" name="address" value="<?= esc(old('address', $client['address'] ?? '')) ?>" maxlength="255" placeholder="Street, City, State ZIP">
      </div>
      <div>
        <label>Source</label>
        <select name="source">
          <?php foreach ($sources as $s): ?>
            <option value="<?= $s ?>"<?= old('source', $client['source'] ?? 'inquiry') === $s ? ' selected' : '' ?>><?= esc(ucfirst($s)) ?></option>
          <?php endforeach ?>
        </select>
      </div>
      <div>
        <label>Status</label>
        <select name="status">
          <?php foreach ($statuses as $s): ?>
            <option value="<?= $s ?>"<?= old('status', $client['status'] ?? 'lead') === $s ? ' selected' : '' ?>><?= esc(ucfirst($s)) ?></option>
          <?php endforeach ?>
        </select>
      </div>
    </div>

    <div>
      <label>Notes</label>
      <textarea name="notes" rows="4" placeholder="Internal notes, inquiry details…"><?= esc(old('notes', $client['notes'] ?? '')) ?></textarea>
    </div>

    <div style="margin-top:.75rem;display:flex;gap:.5rem;align-items:center;">
      <button class="btn" type="submit"><?= $isEdit ? 'Save Changes' : 'Create Client' ?></button>
      <a href="<?= site_url('admin/clients') ?>" class="btn ghost">Cancel</a>
    </div>
  </form>
</div>

<?php if ($isEdit): ?>

<?php if (! empty($events)): ?>
<div class="card">
  <div class="row" style="align-items:center;">
    <h2 style="flex:1;margin-bottom:0;">Events</h2>
    <a href="<?= site_url('admin/events/new') ?>" class="btn ghost" style="font-size:.82rem;">+ New Event</a>
  </div>
  <table style="margin-top:.6rem;">
    <thead>
      <tr><th>Date</th><th>Type</th><th>Venue</th><th>Guests</th><th>Status</th><th></th></tr>
    </thead>
    <tbody>
      <?php foreach ($events as $e): ?>
      <tr>
        <td><?= esc($e['event_date'] ?? '—') ?></td>
        <td><?= esc(ucfirst($e['type'])) ?></td>
        <td><?= esc($e['venue'] ?? '—') ?></td>
        <td><?= isset($e['guest_count']) ? (int) $e['guest_count'] : '—' ?></td>
        <td><span class="pill"><?= esc(ucfirst($e['status'])) ?></span></td>
        <td><a href="<?= site_url('admin/events/' . (int) $e['id']) ?>">Edit</a></td>
      </tr>
      <?php endforeach ?>
    </tbody>
  </table>
</div>
<?php else: ?>
<div class="card" style="display:flex;align-items:center;gap:1rem;">
  <span class="muted">No events yet.</span>
  <a href="<?= site_url('admin/events/new') ?>" class="btn ghost" style="font-size:.82rem;">+ Add Event</a>
</div>
<?php endif ?>

<?php if (! empty($quotes)): ?>
<div class="card">
  <div class="row" style="align-items:center;">
    <h2 style="flex:1;margin-bottom:0;">Quotes</h2>
    <a href="<?= site_url('admin/quotes/new') ?>" class="btn ghost" style="font-size:.82rem;">+ New Quote</a>
  </div>
  <table style="margin-top:.6rem;">
    <thead>
      <tr><th>#</th><th>Status</th><th>Total</th><th>Deposit %</th><th>Valid Until</th><th>Created</th><th></th></tr>
    </thead>
    <tbody>
      <?php foreach ($quotes as $q): ?>
      <tr>
        <td>#<?= (int) $q['id'] ?></td>
        <td><span class="pill"><?= esc(ucfirst($q['status'])) ?></span></td>
        <td><?= fbk_money($q['total']) ?></td>
        <td><?= (int) $q['deposit_pct'] ?>%</td>
        <td class="muted"><?= esc($q['valid_until'] ?? '—') ?></td>
        <td class="muted" style="font-size:.82rem;"><?= esc(substr((string) $q['created_at'], 0, 10)) ?></td>
        <td><a href="<?= site_url('admin/quotes/' . (int) $q['id']) ?>">View</a></td>
      </tr>
      <?php endforeach ?>
    </tbody>
  </table>
</div>
<?php else: ?>
<div class="card" style="display:flex;align-items:center;gap:1rem;">
  <span class="muted">No quotes yet.</span>
  <a href="<?= site_url('admin/quotes/new') ?>" class="btn ghost" style="font-size:.82rem;">+ Add Quote</a>
</div>
<?php endif ?>

<?php endif ?>
<?= $this->endSection() ?>
