<?= $this->extend('layout/admin') ?>
<?= $this->section('content') ?>
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.8rem;">
  <h1 style="margin:0;"><?= esc($title) ?></h1>
  <a class="btn" href="<?= site_url('admin/expenses/new') ?>">+ Add Expense</a>
</div>

<div class="card" style="padding:.7rem 1rem;">
  <form method="get" style="display:flex;gap:.6rem;flex-wrap:wrap;align-items:flex-end;">
    <div>
      <label>Category</label>
      <select name="category" style="width:auto;">
        <option value="">All categories</option>
        <?php foreach (['flowers','supplies','fuel','rent','labor','marketing','other'] as $c): ?>
          <option value="<?= $c ?>" <?= ($filters['cat'] === $c) ? 'selected' : '' ?>><?= ucfirst($c) ?></option>
        <?php endforeach ?>
      </select>
    </div>
    <div>
      <label>Event (COGS)</label>
      <select name="event_id" style="width:auto;">
        <option value="">All events</option>
        <?php foreach ($events as $ev): ?>
          <option value="<?= (int) $ev['id'] ?>" <?= ($filters['evId'] == $ev['id']) ? 'selected' : '' ?>>
            <?= esc($ev['event_date'] ?: '?') ?> — <?= esc($ev['venue'] ?: ucfirst($ev['type'])) ?>
          </option>
        <?php endforeach ?>
      </select>
    </div>
    <div>
      <label>From</label>
      <input type="date" name="from" value="<?= esc($filters['from']) ?>" style="width:auto;">
    </div>
    <div>
      <label>To</label>
      <input type="date" name="to" value="<?= esc($filters['to']) ?>" style="width:auto;">
    </div>
    <div><label>&nbsp;</label><button type="submit" class="btn ghost">Filter</button></div>
    <?php if (array_filter(array_values($filters))): ?>
      <div><label>&nbsp;</label><a class="btn ghost" href="<?= site_url('admin/expenses') ?>">Clear</a></div>
    <?php endif ?>
  </form>
</div>

<div class="card">
  <div style="display:flex;justify-content:flex-end;margin-bottom:.5rem;font-size:.9rem;">
    <?php $count = count($expenses); ?>
    <span class="muted"><?= $count ?> record<?= $count !== 1 ? 's' : '' ?> &nbsp;|&nbsp; Total: <strong><?= fbk_money($total) ?></strong></span>
  </div>
  <table>
    <thead>
      <tr>
        <th>Date</th>
        <th>Vendor</th>
        <th>Category</th>
        <th style="text-align:right;">Amount</th>
        <th>Event (COGS)</th>
        <th>Source</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($expenses as $e): ?>
      <tr>
        <td><?= esc($e['date']) ?></td>
        <td><?= esc($e['vendor'] ?: '—') ?></td>
        <td><span class="pill"><?= esc($e['category']) ?></span></td>
        <td style="text-align:right;"><?= fbk_money($e['amount']) ?></td>
        <td>
          <?php if ($e['event_id']): ?>
            <a href="<?= site_url('admin/events/' . (int) $e['event_id']) ?>">
              <?= esc($e['event_date'] ?? '#' . $e['event_id']) ?>
              <?php if ($e['venue']): ?><span class="muted"> — <?= esc($e['venue']) ?></span><?php endif ?>
            </a>
          <?php else: ?>
            <span class="muted">—</span>
          <?php endif ?>
        </td>
        <td>
          <?php if ($e['plaid_txn_id']): ?>
            <span class="pill" title="Plaid txn #<?= (int) $e['plaid_txn_id'] ?>">Plaid</span>
          <?php else: ?>
            <span class="muted">Manual</span>
          <?php endif ?>
        </td>
        <td><a href="<?= site_url('admin/expenses/' . (int) $e['id']) ?>">Edit</a></td>
      </tr>
    <?php endforeach ?>
    <?php if (! $expenses): ?>
      <tr><td colspan="7" class="muted" style="padding:.8rem .6rem;">No expenses recorded yet.</td></tr>
    <?php endif ?>
    </tbody>
  </table>
</div>
<?= $this->endSection() ?>
