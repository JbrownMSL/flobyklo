<?= $this->extend('layout/admin') ?>
<?= $this->section('content') ?>

<div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.5rem;margin-bottom:.6rem;">
  <h1 style="margin:0;"><?= esc($title) ?></h1>
  <a href="<?= site_url('admin/events') ?>" class="btn ghost">&#8592; Back to Events</a>
</div>

<?php if (!empty($capacityWarning)): ?>
<div class="flash err"><?= esc($capacityWarning) ?></div>
<?php endif ?>

<div id="cap-warn" class="flash err" style="display:none;"></div>

<div class="card">
  <form method="post" action="<?= site_url('admin/events/save') ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= (int) ($event['id'] ?? 0) ?>">

    <div class="row">
      <div>
        <label>Client *</label>
        <select name="client_id" required>
          <option value="">— select client —</option>
          <?php foreach ($clients as $c): ?>
          <option value="<?= (int) $c['id'] ?>" <?= ((int) ($event['client_id'] ?? 0)) === (int) $c['id'] ? 'selected' : '' ?>>
            <?= esc($c['name']) ?>
          </option>
          <?php endforeach ?>
        </select>
      </div>
      <div>
        <label>Type *</label>
        <select name="type" required>
          <?php foreach (['wedding', 'event', 'popup', 'other'] as $t): ?>
          <option value="<?= $t ?>" <?= ($event['type'] ?? 'wedding') === $t ? 'selected' : '' ?>><?= ucfirst($t) ?></option>
          <?php endforeach ?>
        </select>
      </div>
    </div>

    <div class="row">
      <div>
        <label>Event Date *</label>
        <input type="date" id="event_date" name="event_date" value="<?= esc($event['event_date'] ?? '') ?>" required>
      </div>
      <div>
        <label>Status *</label>
        <select name="status" required>
          <?php foreach ($statuses as $s): ?>
          <option value="<?= $s ?>" <?= ($event['status'] ?? 'tentative') === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
          <?php endforeach ?>
        </select>
      </div>
    </div>

    <div class="row">
      <div>
        <label>Venue</label>
        <input type="text" name="venue" value="<?= esc($event['venue'] ?? '') ?>" placeholder="Venue name / address">
      </div>
      <div>
        <label>Guest Count</label>
        <input type="number" name="guest_count" value="<?= esc($event['guest_count'] ?? '') ?>" min="1" placeholder="Approx. guests">
      </div>
    </div>

    <div style="margin:.6rem 0;">
      <label style="display:flex;align-items:center;gap:.4rem;cursor:pointer;">
        <input type="checkbox" name="capacity_weekend" value="1" <?= ! empty($event['capacity_weekend']) ? 'checked' : '' ?> style="width:auto;">
        Count toward weekend capacity cap
      </label>
    </div>

    <div>
      <label>Notes</label>
      <textarea name="notes" rows="3" placeholder="Internal notes…"><?= esc($event['notes'] ?? '') ?></textarea>
    </div>

    <div style="margin-top:1rem;display:flex;gap:.5rem;flex-wrap:wrap;">
      <button type="submit" class="btn">Save Event</button>
      <?php if (! empty($event['id'])): ?>
      <a href="<?= site_url('admin/events') ?>" class="btn ghost">Cancel</a>
      <?php endif ?>
    </div>
  </form>
</div>

<script>
(function () {
    var eventDates = <?= json_encode($eventDates) ?>;
    var cap        = <?= (int) $cap ?>;
    var editId     = <?= (int) ($event['id'] ?? 0) ?>;
    var capBox     = document.getElementById('cap-warn');
    var dateInput  = document.getElementById('event_date');
    var capCb      = document.querySelector('input[name="capacity_weekend"]');

    function satSun(dateStr) {
        var d   = new Date(dateStr + 'T12:00:00');
        var dow = d.getDay(); // 0=Sun, 6=Sat
        if (dow === 6) {
            var sat = dateStr;
            var sunD = new Date(d.getTime() + 86400000);
            var sun = sunD.toISOString().slice(0, 10);
            return [sat, sun];
        } else if (dow === 0) {
            var satD = new Date(d.getTime() - 86400000);
            sat = satD.toISOString().slice(0, 10);
            sun = dateStr;
            return [sat, sun];
        }
        return null;
    }

    function checkCap() {
        var val     = dateInput.value;
        var counted = capCb ? capCb.checked : false;

        if (! val || ! counted) {
            capBox.style.display = 'none';
            return;
        }

        var weekend = satSun(val);
        if (! weekend) {
            capBox.style.display = 'none';
            return;
        }

        var count = 0;
        for (var i = 0; i < eventDates.length; i++) {
            var e = eventDates[i];
            if (editId && parseInt(e.id) === editId) continue;
            if (e.date === weekend[0] || e.date === weekend[1]) count++;
        }

        if (count >= cap) {
            capBox.textContent = '⚠ This weekend already has ' + count + ' capacity event(s) booked (cap: ' + cap + '). You can still save.';
            capBox.style.display = 'block';
        } else if (count > 0) {
            capBox.textContent = 'ℹ This weekend has ' + count + ' capacity event(s) so far (cap: ' + cap + ').';
            capBox.style.display = 'block';
        } else {
            capBox.style.display = 'none';
        }
    }

    if (dateInput) { dateInput.addEventListener('change', checkCap); }
    if (capCb)     { capCb.addEventListener('change', checkCap); }

    // Run on page load in case editing an existing event
    checkCap();
}());
</script>

<?= $this->endSection() ?>
