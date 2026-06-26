<?= $this->extend('layout/admin') ?>
<?= $this->section('content') ?>
<?php
  helper('fbk');

  $q          = $quote;
  $qid        = (int) $q['id'];
  $status     = $q['status'];
  $subtotal   = (float) $q['subtotal'];
  $tax        = (float) $q['tax'];
  $total      = (float) $q['total'];
  $depositAmt = round($total * ($q['deposit_pct'] / 100), 2);
  $totalCost  = 0.0;
  foreach ($items as $item) { $totalCost += (float) $item['cost']; }
  $margin = $total > 0 ? round(($total - $totalCost) / $total * 100, 1) : null;

  $statusColors = ['draft' => '#f0ede8', 'sent' => '#deeaf0', 'accepted' => '#e4f4e4', 'declined' => '#f4e4e4'];
  $sc = $statusColors[$status] ?? '#f0ede8';
?>

<div style="display:flex;align-items:center;gap:1rem;flex-wrap:wrap;margin-bottom:.5rem;">
  <a href="<?= site_url('admin/quotes') ?>" class="btn ghost" style="font-size:.82rem;">← Quotes</a>
  <h1 style="margin:0"><?= esc($title) ?></h1>
  <span class="pill" style="background:<?= $sc ?>;font-size:.85rem;"><?= esc($status) ?></span>
  <a href="<?= site_url('admin/quotes/' . $qid . '/pdf') ?>" class="btn ghost"
     style="margin-left:auto;font-size:.82rem;" target="_blank">⬇ PDF</a>
</div>

<!-- Client / Event info -->
<div class="row">
  <div class="card">
    <h2>Client</h2>
    <p>
      <strong><?= esc($q['client_name'] ?? '—') ?></strong><br>
      <?= $q['client_email'] ? '<a href="mailto:' . esc($q['client_email']) . '">' . esc($q['client_email']) . '</a>' : '<span class="muted">no email</span>' ?><br>
      <?= $q['client_phone'] ? esc($q['client_phone']) : '' ?>
    </p>
    <p>
      <a href="<?= site_url('admin/clients/' . (int) $q['client_id']) ?>" class="btn ghost" style="font-size:.8rem;">View Client →</a>
    </p>
  </div>
  <div class="card">
    <h2>Quote Details</h2>
    <table style="width:auto;">
      <tr><td class="muted" style="padding-right:1rem">Quote #</td><td><?= $qid ?></td></tr>
      <tr><td class="muted">Status</td><td><span class="pill" style="background:<?= $sc ?>"><?= esc($status) ?></span></td></tr>
      <tr><td class="muted">Valid Until</td><td><?= $q['valid_until'] ? esc($q['valid_until']) : '—' ?></td></tr>
      <tr><td class="muted">Deposit %</td><td><?= (int) $q['deposit_pct'] ?>%</td></tr>
      <tr><td class="muted">Created</td><td><?= $q['created_at'] ? date('M j, Y', strtotime($q['created_at'])) : '—' ?></td></tr>
      <?php if ($q['event_date']): ?>
      <tr><td class="muted">Event Date</td><td><?= esc($q['event_date']) ?></td></tr>
      <tr><td class="muted">Event Type</td><td><?= esc($q['event_type'] ?? '—') ?></td></tr>
      <tr><td class="muted">Venue</td><td><?= esc($q['venue'] ?? '—') ?></td></tr>
      <?php endif ?>
    </table>
  </div>
</div>

<!-- Line items -->
<div class="card" style="padding:0;overflow:hidden;">
  <div style="padding:.7rem 1.1rem;background:#f8f9fb;border-bottom:1px solid #eef;">
    <strong style="font-size:.95rem;">Line Items</strong>
  </div>
  <?php if (! $items): ?>
    <p class="muted" style="padding:1rem">No line items on this quote.</p>
  <?php else: ?>
  <div style="overflow-x:auto;">
  <table style="margin:0;">
    <thead>
      <tr style="background:#f8f9fb;">
        <th>Description</th>
        <th>Recipe</th>
        <th style="width:70px;text-align:right">Qty</th>
        <th style="width:100px;text-align:right">Unit Price</th>
        <th style="width:100px;text-align:right">Cost</th>
        <th style="width:100px;text-align:right">Line Total</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($items as $item): ?>
      <tr>
        <td><?= esc($item['description']) ?></td>
        <td class="muted" style="font-size:.82rem;">
          <?php
            $r = null;
            foreach ($recipes as $rec) {
                if ((int) $rec['id'] === (int) $item['recipe_id']) { $r = $rec; break; }
            }
            echo $r ? esc($r['name']) : '—';
          ?>
        </td>
        <td style="text-align:right"><?= number_format((float) $item['qty'], 2) ?></td>
        <td style="text-align:right"><?= fbk_money($item['unit_price']) ?></td>
        <td style="text-align:right" class="muted"><?= fbk_money($item['cost']) ?></td>
        <td style="text-align:right;font-weight:600"><?= fbk_money($item['line_total']) ?></td>
      </tr>
    <?php endforeach ?>
    </tbody>
  </table>
  </div>
  <?php endif ?>
</div>

<!-- Totals -->
<div class="card" style="background:#f8f9fb;">
  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:.8rem;">
    <div class="kpi" style="padding:.7rem .9rem;">
      <div class="l">Subtotal</div>
      <div class="n" style="font-size:1.2rem;"><?= fbk_money($subtotal) ?></div>
    </div>
    <div class="kpi" style="padding:.7rem .9rem;">
      <div class="l">Tax</div>
      <div class="n" style="font-size:1.2rem;"><?= fbk_money($tax) ?></div>
    </div>
    <div class="kpi" style="padding:.7rem .9rem;">
      <div class="l">Total</div>
      <div class="n" style="font-size:1.3rem;color:#3a7a50;"><?= fbk_money($total) ?></div>
    </div>
    <div class="kpi" style="padding:.7rem .9rem;">
      <div class="l">Deposit (<?= (int) $q['deposit_pct'] ?>%)</div>
      <div class="n" style="font-size:1.2rem;"><?= fbk_money($depositAmt) ?></div>
    </div>
    <div class="kpi" style="padding:.7rem .9rem;">
      <div class="l">Gross Margin</div>
      <div class="n" style="font-size:1.2rem;<?= $margin !== null ? ($margin >= 40 ? 'color:#3a7a50' : ($margin >= 20 ? 'color:#a06030' : 'color:#c04040')) : '' ?>">
        <?= $margin !== null ? $margin . '%' : '—' ?>
      </div>
    </div>
    <?php if ($invoice): ?>
    <div class="kpi" style="padding:.7rem .9rem;">
      <div class="l">Invoice</div>
      <div class="n" style="font-size:1rem;">
        <a href="<?= site_url('admin/invoices/' . (int) $invoice['id']) ?>"><?= esc($invoice['number']) ?></a>
      </div>
    </div>
    <?php endif ?>
  </div>
</div>

<!-- Action buttons based on status -->
<div class="card">
  <h2>Actions</h2>
  <div style="display:flex;gap:.7rem;flex-wrap:wrap;align-items:center;">

    <?php if ($status === 'sent' || $status === 'draft'): ?>
    <!-- Send / resend email -->
    <form method="post" action="<?= site_url('admin/quotes/' . $qid . '/send') ?>" style="margin:0;">
      <?= csrf_field() ?>
      <button type="submit" class="btn"><?= $status === 'sent' ? '↻ Resend Email' : '✉ Send to Client' ?></button>
    </form>
    <?php endif ?>

    <?php if (in_array($status, ['sent', 'draft'], true) && ! $invoice): ?>
    <!-- Accept + create invoice -->
    <form method="post" action="<?= site_url('admin/quotes/' . $qid . '/invoice') ?>" style="margin:0;"
          onsubmit="return confirm('Accept this quote and create an invoice?')">
      <?= csrf_field() ?>
      <button type="submit" class="btn" style="background:#3a7a50;">✓ Accept + Create Invoice</button>
    </form>
    <?php endif ?>

    <?php if ($status === 'accepted' && ! $invoice): ?>
    <form method="post" action="<?= site_url('admin/quotes/' . $qid . '/invoice') ?>" style="margin:0;">
      <?= csrf_field() ?>
      <button type="submit" class="btn" style="background:#3a7a50;">+ Create Invoice</button>
    </form>
    <?php endif ?>

    <?php if ($invoice): ?>
    <a href="<?= site_url('admin/invoices/' . (int) $invoice['id']) ?>" class="btn ghost">
      View Invoice <?= esc($invoice['number']) ?> →
    </a>
    <?php endif ?>

    <?php if (in_array($status, ['sent', 'draft', 'accepted'], true)): ?>
    <!-- Decline -->
    <form method="post" action="<?= site_url('admin/quotes/save') ?>" style="margin:0;"
          onsubmit="return confirm('Mark this quote as declined?')">
      <?= csrf_field() ?>
      <input type="hidden" name="_action" value="status">
      <input type="hidden" name="id" value="<?= $qid ?>">
      <input type="hidden" name="status" value="declined">
      <button type="submit" class="btn alt">✗ Mark Declined</button>
    </form>
    <?php endif ?>

    <?php if ($status === 'declined' || $status === 'sent'): ?>
    <!-- Revert to draft -->
    <form method="post" action="<?= site_url('admin/quotes/save') ?>" style="margin:0;">
      <?= csrf_field() ?>
      <input type="hidden" name="_action" value="status">
      <input type="hidden" name="id" value="<?= $qid ?>">
      <input type="hidden" name="status" value="draft">
      <button type="submit" class="btn ghost">↩ Revert to Draft</button>
    </form>
    <?php endif ?>

    <a href="<?= site_url('admin/quotes/' . $qid . '/pdf') ?>" class="btn ghost" target="_blank">⬇ Download PDF</a>

  </div>

  <?php if ($status === 'draft'): ?>
  <p style="margin-top:.8rem;font-size:.82rem;" class="muted">
    This quote is a draft. <a href="<?= site_url('admin/quotes/' . $qid) ?>">Edit it</a> to update items or send it to the client.
  </p>
  <?php endif ?>
</div>
<?= $this->endSection() ?>
