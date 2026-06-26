<?= $this->extend('layout/admin') ?>
<?= $this->section('content') ?>
<h1><?= esc($title) ?></h1>

<div style="display:flex;gap:.7rem;margin-bottom:1rem;flex-wrap:wrap;align-items:center;">
  <a href="<?= site_url('admin/quotes/new') ?>" class="btn">+ New Quote</a>
  <div style="display:flex;gap:.4rem;flex-wrap:wrap;">
    <a href="<?= site_url('admin/quotes') ?>"
       class="btn ghost<?= ! $statusFilter ? ' active' : '' ?>"
       style="<?= ! $statusFilter ? 'background:#eef4eb;' : '' ?>">All</a>
    <?php foreach (['draft' => '#f0ede8', 'sent' => '#e8eef0', 'accepted' => '#eaf4ea', 'declined' => '#f4eaea'] as $s => $bg): ?>
    <a href="<?= site_url('admin/quotes?status=' . $s) ?>"
       class="btn ghost"
       style="<?= $statusFilter === $s ? "background:{$bg};" : '' ?>"><?= ucfirst($s) ?></a>
    <?php endforeach ?>
  </div>
</div>

<div class="card" style="padding:0;overflow:hidden;">
<?php if (! $quotes): ?>
  <p class="muted" style="padding:1.2rem">No quotes yet. <a href="<?= site_url('admin/quotes/new') ?>">Create one →</a></p>
<?php else: ?>
  <table>
    <thead>
      <tr style="background:#f8f9fb;">
        <th>#</th>
        <th>Client</th>
        <th>Status</th>
        <th>Subtotal</th>
        <th>Tax</th>
        <th>Total</th>
        <th>Deposit %</th>
        <th>Valid Until</th>
        <th>Created</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($quotes as $q): ?>
      <?php
        $statusColors = ['draft' => '#f0ede8', 'sent' => '#deeaf0', 'accepted' => '#e4f4e4', 'declined' => '#f4e4e4'];
        $sc = $statusColors[$q['status']] ?? '#f0ede8';
      ?>
      <tr>
        <td class="muted">#<?= (int) $q['id'] ?></td>
        <td><?= esc($q['client_name'] ?? '—') ?></td>
        <td><span class="pill" style="background:<?= $sc ?>"><?= esc($q['status']) ?></span></td>
        <td><?= fbk_money($q['subtotal']) ?></td>
        <td><?= fbk_money($q['tax']) ?></td>
        <td style="font-weight:600"><?= fbk_money($q['total']) ?></td>
        <td><?= (int) $q['deposit_pct'] ?>%</td>
        <td><?= $q['valid_until'] ? esc($q['valid_until']) : '—' ?></td>
        <td class="muted"><?= $q['created_at'] ? date('M j, Y', strtotime($q['created_at'])) : '—' ?></td>
        <td>
          <a href="<?= site_url('admin/quotes/' . $q['id']) ?>"
             class="btn ghost"
             style="font-size:.8rem;padding:.3rem .65rem;">View</a>
        </td>
      </tr>
    <?php endforeach ?>
    </tbody>
  </table>
<?php endif ?>
</div>
<?= $this->endSection() ?>
