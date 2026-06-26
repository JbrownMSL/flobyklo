<?= $this->extend('layout/admin') ?>
<?= $this->section('content') ?>
<h1><?= esc($title) ?></h1>

<div class="kpis">
  <div class="kpi">
    <div class="n"><?= (int) $openLeads ?></div>
    <div class="l">Open Leads</div>
  </div>
  <div class="kpi">
    <div class="n"><?= (int) $upcomingEvents ?></div>
    <div class="l">Upcoming Events</div>
  </div>
  <div class="kpi">
    <div class="n"><?= fbk_money($unpaidInvoices) ?></div>
    <div class="l">Unpaid Invoices</div>
  </div>
  <div class="kpi">
    <div class="n"><?= fbk_money($mtdIncome) ?></div>
    <div class="l">MTD Income</div>
  </div>
  <div class="kpi">
    <div class="n"><?= fbk_money($mtdExpenses) ?></div>
    <div class="l">MTD Expenses</div>
  </div>
  <div class="kpi">
    <div class="n"><?= fbk_money($mtdIncome - $mtdExpenses) ?></div>
    <div class="l">MTD Net</div>
  </div>
</div>

<div class="row">
  <div class="card">
    <h2>Quick links</h2>
    <p><a href="<?= site_url('admin/clients/new') ?>">+ New Client</a></p>
    <p><a href="<?= site_url('admin/quotes/new') ?>">+ New Quote</a></p>
    <p><a href="<?= site_url('admin/expenses/new') ?>">+ New Expense</a></p>
    <p><a href="<?= site_url('admin/reports/mtd') ?>">MTD Report →</a></p>
  </div>
  <div class="card">
    <h2>Modules</h2>
    <table>
      <tr><td><a href="<?= site_url('admin/clients') ?>">Clients / CRM</a></td><td class="muted">lead pipeline</td></tr>
      <tr><td><a href="<?= site_url('admin/events') ?>">Events</a></td><td class="muted">bookings &amp; capacity</td></tr>
      <tr><td><a href="<?= site_url('admin/recipes') ?>">Recipes</a></td><td class="muted">stem-cost engine</td></tr>
      <tr><td><a href="<?= site_url('admin/quotes') ?>">Quotes</a></td><td class="muted">proposals &amp; PDFs</td></tr>
      <tr><td><a href="<?= site_url('admin/contracts') ?>">Contracts</a></td><td class="muted">e-sign</td></tr>
      <tr><td><a href="<?= site_url('admin/invoices') ?>">Invoices</a></td><td class="muted">payments &amp; Square</td></tr>
      <tr><td><a href="<?= site_url('admin/expenses') ?>">Expenses</a></td><td class="muted">COGS tracking</td></tr>
      <tr><td><a href="<?= site_url('admin/bank') ?>">Bank / Plaid</a></td><td class="muted">transaction sync</td></tr>
      <tr><td><a href="<?= site_url('admin/reports') ?>">P&amp;L Reports</a></td><td class="muted">income &amp; expenses</td></tr>
    </table>
  </div>
</div>
<?= $this->endSection() ?>
