<?= $this->extend('layout/admin') ?>
<?= $this->section('content') ?>
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.8rem;">
  <h1 style="margin:0;"><?= esc($title) ?></h1>
  <a class="btn" href="<?= site_url('admin/income/new') ?>">+ Add Income</a>
</div>

<div class="card" style="padding:.7rem 1rem;">
  <form method="get" style="display:flex;gap:.6rem;flex-wrap:wrap;align-items:flex-end;">
    <div><label>From</label><input type="date" name="from" value="<?= esc($filters['from']) ?>"></div>
    <div><label>To</label><input type="date" name="to" value="<?= esc($filters['to']) ?>"></div>
    <div><button class="btn ghost" type="submit">Filter</button></div>
    <div style="margin-left:auto;"><strong>Total: <?= fbk_money($total) ?></strong></div>
  </form>
  <p class="muted" style="font-size:.78rem;margin:.5rem 0 0;">
    Money in that is not an invoice payment (bank deposits, cash, Venmo). Invoice payments are recorded on the invoice.
  </p>
</div>

<div class="card">
  <table>
    <thead>
      <tr><th>Date</th><th>Payer</th><th>Category</th><th style="text-align:right;">Amount</th><th>Client</th><th>Source</th><th></th></tr>
    </thead>
    <tbody>
    <?php foreach ($rows as $i): ?>
      <tr>
        <td><?= esc($i['date']) ?></td>
        <td><?= esc($i['payer'] ?: '—') ?></td>
        <td><span class="pill"><?= esc($i['category']) ?></span></td>
        <td style="text-align:right;"><?= fbk_money($i['amount']) ?></td>
        <td><?= esc($i['client_name'] ?? '—') ?></td>
        <td><?= $i['plaid_txn_id'] ? '<span class="pill" title="Plaid txn #' . (int) $i['plaid_txn_id'] . '">Bank</span>' : '<span class="muted">Manual</span>' ?></td>
        <td><a href="<?= site_url('admin/income/' . (int) $i['id']) ?>">Edit</a></td>
      </tr>
    <?php endforeach ?>
    <?php if (! $rows): ?>
      <tr><td colspan="7" class="muted" style="padding:.8rem .6rem;">No income recorded yet.</td></tr>
    <?php endif ?>
    </tbody>
  </table>
</div>
<?= $this->endSection() ?>
