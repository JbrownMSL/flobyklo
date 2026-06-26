<?= $this->extend('layout/admin') ?>
<?= $this->section('content') ?>
<h1>Monthly P&amp;L</h1>

<div class="card">
  <form method="get" action="<?= site_url('admin/reports/pnl') ?>" style="display:flex;gap:.8rem;flex-wrap:wrap;align-items:flex-end;margin-bottom:1rem;">
    <div style="flex:1 1 160px">
      <label>From</label>
      <input type="date" name="from" value="<?= esc($from) ?>">
    </div>
    <div style="flex:1 1 160px">
      <label>To</label>
      <input type="date" name="to" value="<?= esc($to) ?>">
    </div>
    <div>
      <button type="submit" class="btn">Filter</button>
    </div>
    <div>
      <a href="<?= site_url('admin/reports/pnl?from=' . date('Y-01-01') . '&to=' . date('Y-m-d')) ?>" class="btn ghost">YTD</a>
    </div>
  </form>

  <?php if (! $rows): ?>
    <p class="muted">No income or expenses found in this date range.</p>
  <?php else: ?>
    <table>
      <thead>
        <tr>
          <th>Month</th>
          <th style="text-align:right">Income</th>
          <th style="text-align:right">Expenses</th>
          <th style="text-align:right">Net</th>
          <th style="text-align:right">Margin</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $row): ?>
          <?php $isNeg = $row['net'] < 0; ?>
          <tr>
            <td><?= esc(date('M Y', strtotime($row['month'] . '-01'))) ?></td>
            <td style="text-align:right;color:#16a34a"><?= fbk_money($row['income']) ?></td>
            <td style="text-align:right;color:#ef4444"><?= fbk_money($row['expenses']) ?></td>
            <td style="text-align:right;font-weight:700;color:<?= $isNeg ? '#ef4444' : '#16a34a' ?>">
              <?= fbk_money($row['net']) ?>
            </td>
            <td style="text-align:right;color:<?= $isNeg ? '#ef4444' : '#16a34a' ?>">
              <?= $row['margin'] !== null ? esc($row['margin']) . '%' : '<span class="muted">—</span>' ?>
            </td>
          </tr>
        <?php endforeach ?>
      </tbody>
      <tfoot>
        <tr style="border-top:2px solid #cbd5e1;font-weight:800">
          <td>Total</td>
          <td style="text-align:right;color:#16a34a"><?= fbk_money($totalIncome) ?></td>
          <td style="text-align:right;color:#ef4444"><?= fbk_money($totalExpenses) ?></td>
          <td style="text-align:right;color:<?= $totalNet < 0 ? '#ef4444' : '#16a34a' ?>"><?= fbk_money($totalNet) ?></td>
          <td style="text-align:right;color:<?= $totalNet < 0 ? '#ef4444' : '#16a34a' ?>">
            <?= $totalMargin !== null ? esc($totalMargin) . '%' : '<span class="muted">—</span>' ?>
          </td>
        </tr>
      </tfoot>
    </table>
  <?php endif ?>
</div>

<?php if ($byCategory): ?>
<div class="card">
  <h2>Expenses by Category</h2>
  <table>
    <thead>
      <tr><th>Category</th><th style="text-align:right">Total</th><th style="text-align:right">Share</th></tr>
    </thead>
    <tbody>
      <?php foreach ($byCategory as $cat): ?>
        <?php $share = $totalExpenses > 0 ? round($cat['total'] / $totalExpenses * 100, 1) : 0; ?>
        <tr>
          <td><span class="pill"><?= esc(ucfirst($cat['category'])) ?></span></td>
          <td style="text-align:right"><?= fbk_money($cat['total']) ?></td>
          <td style="text-align:right;color:#667"><?= esc($share) ?>%</td>
        </tr>
      <?php endforeach ?>
    </tbody>
  </table>
</div>
<?php endif ?>

<p style="margin-top:.5rem"><a href="<?= site_url('admin/reports') ?>" class="muted">&larr; All Reports</a></p>
<?= $this->endSection() ?>
