<?= $this->extend('layout/admin') ?>
<?= $this->section('content') ?>

<div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.5rem;margin-bottom:.6rem;">
  <h1 style="margin:0;"><?= esc($title) ?></h1>
  <div style="display:flex;gap:.5rem;flex-wrap:wrap;">
    <a href="<?= site_url('admin/events?view=calendar&month=' . esc($prev)) ?>" class="btn ghost">&#8249; Prev</a>
    <a href="<?= site_url('admin/events?view=calendar&month=' . esc($next)) ?>" class="btn ghost">Next &#8250;</a>
    <a href="<?= site_url('admin/events') ?>" class="btn ghost">&#9776; List</a>
    <a href="<?= site_url('admin/events/new') ?>" class="btn">+ New Event</a>
  </div>
</div>

<div class="card" style="padding:0;overflow:hidden;">
  <table style="table-layout:fixed;min-width:700px;">
    <thead>
      <tr>
        <?php foreach (['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $dayName): ?>
        <th style="text-align:center;font-size:.78rem;padding:.4rem;background:#f8fafc;"><?= $dayName ?></th>
        <?php endforeach ?>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($weeks as $week): ?>
      <tr>
        <?php foreach ($week as $colIdx => $date): ?>
        <?php
          $isWeekend  = ($colIdx === 0 || $colIdx === 6);
          $evs        = $date ? ($byDate[$date] ?? []) : [];
          $used       = $date ? ($weekendUsed[$date] ?? 0) : 0;
          $bgStyle    = '';
          if (! $date) {
              $bgStyle = 'background:#f1f5f9;';
          } elseif ($isWeekend) {
              if ($used >= $cap) {
                  $bgStyle = 'background:#fef2f2;';
              } elseif ($used > 0) {
                  $bgStyle = 'background:#fefce8;';
              } else {
                  $bgStyle = 'background:#f0fdf4;';
              }
          }
        ?>
        <td style="vertical-align:top;height:90px;padding:.3rem .35rem;border-right:1px solid #e2e8f0;<?= $bgStyle ?>">
          <?php if ($date): ?>
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.2rem;">
            <span style="font-size:.72rem;font-weight:700;color:#64748b;"><?= (int) substr($date, 8) ?></span>
            <?php if ($isWeekend): ?>
            <span style="font-size:.65rem;font-weight:700;color:<?= $used >= $cap ? '#dc2626' : '#4b7c59' ?>;"><?= (int) $used ?>/<?= (int) $cap ?></span>
            <?php endif ?>
          </div>
          <?php foreach ($evs as $ev): ?>
          <div style="font-size:.68rem;border-radius:4px;padding:.1rem .3rem;margin-bottom:.15rem;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;<?= $ev['status'] === 'confirmed' ? 'background:#7c9a6f;color:#fff;' : 'background:#d4a5a5;color:#fff;' ?>">
            <a href="<?= site_url('admin/events/' . (int) $ev['id']) ?>" style="color:#fff;text-decoration:none;" title="<?= esc($ev['client_name'] ?? '') . ' — ' . esc($ev['venue'] ?? '') ?>">
              <?= esc($ev['client_name'] ?? 'Event') ?>
            </a>
          </div>
          <?php endforeach ?>
          <?php endif ?>
        </td>
        <?php endforeach ?>
      </tr>
      <?php endforeach ?>
    </tbody>
  </table>
</div>

<div style="margin-top:.6rem;font-size:.75rem;color:#64748b;display:flex;gap:1rem;flex-wrap:wrap;">
  <span><span style="display:inline-block;width:11px;height:11px;background:#f0fdf4;border:1px solid #ccc;vertical-align:middle;"></span> Weekend open</span>
  <span><span style="display:inline-block;width:11px;height:11px;background:#fefce8;border:1px solid #ccc;vertical-align:middle;"></span> Partially booked</span>
  <span><span style="display:inline-block;width:11px;height:11px;background:#fef2f2;border:1px solid #ccc;vertical-align:middle;"></span> At capacity</span>
  <span><span style="display:inline-block;width:11px;height:11px;background:#7c9a6f;border-radius:2px;vertical-align:middle;"></span> Confirmed</span>
  <span><span style="display:inline-block;width:11px;height:11px;background:#d4a5a5;border-radius:2px;vertical-align:middle;"></span> Tentative/other</span>
  <span style="margin-left:.5rem;">Weekend cap: <?= (int) $cap ?> events</span>
</div>

<?= $this->endSection() ?>
