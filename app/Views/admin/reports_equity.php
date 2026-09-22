<?= $this->extend('layout/admin') ?>
<?= $this->section('content') ?>
<h1><?= esc($title) ?></h1>
<div class="card">
  <table>
    <tr><td>Initial capital (Operating Agreement, Schedule A)</td><td style="text-align:right;"><?= fbk_money($capital) ?></td></tr>
    <tr><td>+ Net income to date (income <?= fbk_money($income) ?> − expenses <?= fbk_money($expenses) ?>)</td><td style="text-align:right;"><?= fbk_money($net) ?></td></tr>
    <tr><td>− Owner distributions</td><td style="text-align:right;"><?= fbk_money($drawTotal) ?></td></tr>
    <tr><th>Owner equity</th><th style="text-align:right;"><?= fbk_money($equity) ?></th></tr>
  </table>
  <p class="muted" style="font-size:.78rem;margin-top:.6rem;">
    Owner distributions are money you took out for yourself — they are NOT a business cost, so they never appear on the P&amp;L.
    ⚠️ Money you put INTO the business is not tracked separately yet: if you recorded it as Income it is counted in net income above.
  </p>
</div>
<div class="card">
  <h2 style="margin-top:0;font-size:1rem;">Distributions</h2>
  <table>
    <thead><tr><th>Date</th><th>Description</th><th style="text-align:right;">Amount</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($draws as $d): ?>
      <tr><td><?= esc($d['date']) ?></td><td><?= esc($d['vendor'] ?: ($d['notes'] ?: '—')) ?></td><td style="text-align:right;"><?= fbk_money($d['amount']) ?></td><td><a href="<?= site_url('admin/expenses/' . (int) $d['id']) ?>">Edit</a></td></tr>
    <?php endforeach ?>
    <?php if (! $draws): ?><tr><td colspan="4" class="muted">No distributions recorded yet.</td></tr><?php endif ?>
    </tbody>
  </table>
</div>
<?= $this->endSection() ?>
