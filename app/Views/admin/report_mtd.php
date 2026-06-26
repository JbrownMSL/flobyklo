<?= $this->extend('layout/admin') ?>
<?= $this->section('content') ?>
<h1>Event Margins</h1>

<div class="kpis">
  <div class="kpi">
    <div class="n" style="color:#16a34a"><?= fbk_money($mtdIncome) ?></div>
    <div class="l">MTD Income (<?= esc($month) ?>)</div>
  </div>
  <div class="kpi">
    <div class="n" style="color:#ef4444"><?= fbk_money($mtdExpense) ?></div>
    <div class="l">MTD Expenses (<?= esc($month) ?>)</div>
  </div>
  <div class="kpi">
    <div class="n" style="color:<?= ($mtdIncome - $mtdExpense) >= 0 ? '#16a34a' : '#ef4444' ?>"><?= fbk_money($mtdIncome - $mtdExpense) ?></div>
    <div class="l">MTD Net</div>
  </div>
  <div class="kpi">
    <div class="n" style="color:#ef4444"><?= fbk_money($overhead) ?></div>
    <div class="l"><?= esc($year) ?> Overhead (no event)</div>
  </div>
</div>

<div class="card">
  <form method="get" action="<?= site_url('admin/reports/mtd') ?>" style="display:flex;gap:.8rem;align-items:flex-end;margin-bottom:1rem;flex-wrap:wrap">
    <div>
      <label>Year</label>
      <select name="year" onchange="this.form.submit()" style="width:auto">
        <?php foreach ($years as $y): ?>
          <option value="<?= esc($y) ?>"<?= $y == $year ? ' selected' : '' ?>><?= esc($y) ?></option>
        <?php endforeach ?>
      </select>
    </div>
  </form>

  <?php if (! $eventMargins): ?>
    <p class="muted">No events found for <?= esc($year) ?>.</p>
  <?php else: ?>
    <table>
      <thead>
        <tr>
          <th>Date</th>
          <th>Client</th>
          <th>Venue</th>
          <th>Type</th>
          <th style="text-align:right">Revenue</th>
          <th style="text-align:right">COGS</th>
          <th style="text-align:right">Margin $</th>
          <th style="text-align:right">Margin %</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $totalRev  = 0;
        $totalCogs = 0;
        $totalMarg = 0;
        foreach ($eventMargins as $ev):
            $totalRev  += (float) $ev['revenue'];
            $totalCogs += (float) $ev['cogs'];
            $totalMarg += (float) $ev['margin'];
            $isNeg = (float) $ev['margin'] < 0;
        ?>
          <tr>
            <td><?= esc(date('M j, Y', strtotime($ev['event_date']))) ?></td>
            <td><?= esc($ev['client_name']) ?></td>
            <td class="muted"><?= esc($ev['venue'] ?? '—') ?></td>
            <td><span class="pill"><?= esc(ucfirst($ev['type'])) ?></span></td>
            <td style="text-align:right;color:#16a34a"><?= fbk_money($ev['revenue']) ?></td>
            <td style="text-align:right;color:#ef4444"><?= fbk_money($ev['cogs']) ?></td>
            <td style="text-align:right;font-weight:700;color:<?= $isNeg ? '#ef4444' : '#16a34a' ?>">
              <?= fbk_money($ev['margin']) ?>
            </td>
            <td style="text-align:right;color:<?= $isNeg ? '#ef4444' : '#16a34a' ?>">
              <?= $ev['margin_pct'] !== null ? esc($ev['margin_pct']) . '%' : '<span class="muted">—</span>' ?>
            </td>
          </tr>
        <?php endforeach ?>
      </tbody>
      <tfoot>
        <?php
        $footerTotalPct = $totalRev > 0 ? round($totalMarg / $totalRev * 100, 1) : null;
        ?>
        <tr style="border-top:2px solid #cbd5e1;font-weight:800">
          <td colspan="4">Total (<?= count($eventMargins) ?> events)</td>
          <td style="text-align:right;color:#16a34a"><?= fbk_money($totalRev) ?></td>
          <td style="text-align:right;color:#ef4444"><?= fbk_money($totalCogs) ?></td>
          <td style="text-align:right;color:<?= $totalMarg < 0 ? '#ef4444' : '#16a34a' ?>"><?= fbk_money($totalMarg) ?></td>
          <td style="text-align:right;color:<?= $totalMarg < 0 ? '#ef4444' : '#16a34a' ?>">
            <?= $footerTotalPct !== null ? esc($footerTotalPct) . '%' : '<span class="muted">—</span>' ?>
          </td>
        </tr>
      </tfoot>
    </table>

    <p class="muted" style="font-size:.8rem;margin-top:.8rem">
      Revenue = accepted quote line totals. COGS = expenses linked to the event.
      Overhead expenses (not linked to an event) are shown in the KPI above but excluded from per-event margin.
    </p>
  <?php endif ?>
</div>

<p style="margin-top:.5rem"><a href="<?= site_url('admin/reports') ?>" class="muted">&larr; All Reports</a></p>
<?= $this->endSection() ?>
