<?= $this->extend('layout/admin') ?>
<?= $this->section('content') ?>
<h1>Reports &amp; P&amp;L</h1>

<div class="kpis">
  <div class="kpi">
    <div class="n" style="color:#16a34a"><?= fbk_money($ytdIncome) ?></div>
    <div class="l"><?= esc($year) ?> YTD Income</div>
  </div>
  <div class="kpi">
    <div class="n" style="color:#ef4444"><?= fbk_money($ytdExpenses) ?></div>
    <div class="l"><?= esc($year) ?> YTD Expenses</div>
  </div>
  <div class="kpi">
    <div class="n" style="color:<?= $ytdNet >= 0 ? '#16a34a' : '#ef4444' ?>"><?= fbk_money($ytdNet) ?></div>
    <div class="l"><?= esc($year) ?> YTD Net</div>
  </div>
  <div class="kpi">
    <div class="n"><?= esc($openLeads) ?></div>
    <div class="l">Open Leads / Consults</div>
  </div>
  <div class="kpi">
    <div class="n"><?= esc($upcomingEvents) ?></div>
    <div class="l">Upcoming Events</div>
  </div>
</div>

<div class="card">
  <h2>Available Reports</h2>
  <table>
    <thead><tr><th>Report</th><th>Description</th></tr></thead>
    <tbody>
      <tr>
        <td><a href="<?= site_url('admin/reports/pnl') ?>" class="btn" style="font-size:.82rem;padding:.3rem .8rem;">Monthly P&amp;L</a></td>
        <td class="muted">Income (payments) vs. expenses by calendar month — date-range filterable.</td>
      </tr>
      <tr>
        <td><a href="<?= site_url('admin/reports/mtd') ?>" class="btn" style="font-size:.82rem;padding:.3rem .8rem;">Event Margins</a></td>
        <td><a href="<?= site_url('admin/reports/equity') ?>" class="btn" style="font-size:.82rem;padding:.3rem .8rem;">Owner Equity</a></td>
        <td class="muted">Per-event revenue (accepted quote total) minus allocated COGS — with overhead summary.</td>
      </tr>
    </tbody>
  </table>
</div>
<?= $this->endSection() ?>
