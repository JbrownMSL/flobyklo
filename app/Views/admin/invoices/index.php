<?= $this->extend('layout/admin') ?>
<?= $this->section('content') ?>
<div style="display:flex;align-items:center;gap:1rem;flex-wrap:wrap;margin-bottom:.4rem;">
  <h1 style="margin:0;">Invoices</h1>
</div>

<div class="card">
  <div class="row" style="font-size:.85rem;margin-bottom:.8rem;">
    <a class="pill<?= ! $status ? ' active' : '' ?>" href="<?= site_url('admin/invoices') ?>">All</a>
    <?php foreach (['draft', 'sent', 'deposit_paid', 'paid', 'void'] as $s): ?>
      <a class="pill<?= $status === $s ? ' active' : '' ?>"
         href="<?= site_url('admin/invoices?status=' . $s) ?>"><?= esc(str_replace('_', ' ', $s)) ?></a>
    <?php endforeach ?>
  </div>

  <table>
    <thead>
      <tr>
        <th>#</th>
        <th>Client</th>
        <th>Status</th>
        <th>Total</th>
        <th>Paid</th>
        <th>Balance</th>
        <th>Due</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($invoices as $inv): ?>
      <tr>
        <td><strong><?= esc($inv['number'] ?: '#' . $inv['id']) ?></strong></td>
        <td><?= esc($inv['client_name'] ?? '—') ?></td>
        <td><span class="pill pill--<?= esc($inv['status']) ?>"><?= esc(str_replace('_', ' ', $inv['status'])) ?></span></td>
        <td><?= fbk_money($inv['total']) ?></td>
        <td><?= fbk_money($inv['amount_paid']) ?></td>
        <td><?= fbk_money($inv['balance_due']) ?></td>
        <td class="muted"><?= $inv['due_date'] ? esc($inv['due_date']) : '—' ?></td>
        <td><a href="<?= site_url('admin/invoices/' . $inv['id']) ?>">View →</a></td>
      </tr>
    <?php endforeach ?>
    <?php if (! $invoices): ?>
      <tr><td colspan="8" class="muted" style="text-align:center;padding:1.5rem 0;">No invoices yet. Accept a quote to create one.</td></tr>
    <?php endif ?>
    </tbody>
  </table>
</div>

<style>
  .pill.active{background:#7c9a6f;color:#fff;border-color:#7c9a6f;}
  .pill--paid{background:#d1fae5;color:#065f46;border-color:#a7f3d0;}
  .pill--deposit_paid{background:#dbeafe;color:#1e40af;border-color:#93c5fd;}
  .pill--sent{background:#fef9c3;color:#713f12;border-color:#fde68a;}
  .pill--void{background:#f3f4f6;color:#6b7280;border-color:#d1d5db;}
</style>
<?= $this->endSection() ?>
