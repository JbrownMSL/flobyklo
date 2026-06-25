<?= $this->extend('layout/main') ?>
<?= $this->section('content') ?>
<h1>Your Cart</h1>
<?php if (empty($summary['lines'])): ?>
  <div class="card">Your cart is empty. <a href="<?= site_url('catalog') ?>">Browse equipment →</a></div>
<?php else: ?>
  <div class="card">
    <p class="muted">Rental dates: <strong><?= esc($summary['start']) ?></strong> → <strong><?= esc($summary['end']) ?></strong> (<?= (int) $summary['days'] ?> day<?= $summary['days'] == 1 ? '' : 's' ?>)</p>
    <table>
      <thead><tr><th>Item</th><th>Qty</th><th>Days</th><th>Subtotal</th><th>Tax</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($summary['lines'] as $ln): ?>
        <tr>
          <td><?= esc($ln['name']) ?>
            <?php if ($ln['is_trailer']): ?><br><span class="pill">trailer · motor-vehicle tax</span>
              <form action="<?= site_url('cart/trailer') ?>" method="post" style="display:inline;">
                <?= csrf_field() ?><input type="hidden" name="parent_id" value="<?= (int) ($ln['parent_id'] ?? 0) ?>"><input type="hidden" name="remove" value="1">
                <button class="btn ghost" style="padding:.15rem .5rem;font-size:.75rem;">I have my own trailer — remove</button>
              </form>
            <?php endif ?>
          </td>
          <td><?= (int) $ln['qty'] ?></td><td><?= (int) $ln['days'] ?></td>
          <td><?= wtr_money($ln['line_subtotal']) ?></td>
          <td><?= wtr_money($ln['tax_amount']) ?></td>
          <td><?php if (! $ln['is_trailer']): ?>
            <form action="<?= site_url('cart/remove') ?>" method="post"><?= csrf_field() ?><input type="hidden" name="equipment_id" value="<?= (int) $ln['equipment_id'] ?>"><button class="btn ghost" style="padding:.15rem .5rem;">✕</button></form>
          <?php endif ?></td>
        </tr>
      <?php endforeach ?>
      </tbody>
    </table>
  </div>

  <div class="card">
    <form action="<?= site_url('cart/waiver') ?>" method="post">
      <?= csrf_field() ?>
      <label style="display:flex;gap:.5rem;align-items:center;">
        <input type="checkbox" name="waiver" value="1" style="width:auto;" onchange="this.form.submit()" <?= $waiver ? 'checked' : '' ?>>
        Add optional <strong>Damage Waiver</strong> (12% of rental) — covers accidental damage and waives the refundable security-deposit hold.
      </label>
    </form>
    <p class="muted" style="font-size:.82rem;margin-top:.4rem;">Without the waiver, a refundable <strong>security deposit</strong> ($300 under $10k / $500 at $10k+) is authorized at booking and released within 5 business days of a clean return; you must carry your own insurance or accept liability (acknowledged at checkout). <strong>50% is charged at booking; the remaining 50% at pickup.</strong></p>
    <table class="totals" style="margin-top:.6rem;">
      <tr><td>Rental subtotal</td><td style="text-align:right;"><?= wtr_money($summary['rental_subtotal']) ?></td></tr>
      <?php foreach ($summary['tax_by_class'] as $cls => $amt): ?>
        <tr><td><?= esc(config('Wtr')->taxClasses[$cls] ?? $cls) ?></td><td style="text-align:right;"><?= wtr_money($amt) ?></td></tr>
      <?php endforeach ?>
      <?php if ($summary['waiver'] > 0): ?><tr><td>Damage waiver</td><td style="text-align:right;"><?= wtr_money($summary['waiver']) ?></td></tr><?php endif ?>
      <tr class="grand"><td>Rental total</td><td style="text-align:right;"><?= wtr_money($summary['grand_total']) ?></td></tr>
      <tr><td><strong>Charged today (50%)</strong></td><td style="text-align:right;"><strong><?= wtr_money($summary['booking_charge']) ?></strong></td></tr>
      <tr><td class="muted">Balance due at pickup</td><td style="text-align:right;" class="muted"><?= wtr_money($summary['balance_due']) ?></td></tr>
      <?php if ($summary['damage_deposit'] > 0): ?><tr><td class="muted">+ Refundable security deposit (hold)</td><td style="text-align:right;" class="muted"><?= wtr_money($summary['damage_deposit']) ?></td></tr><?php endif ?>
    </table>
    <p style="margin-top:1rem;"><a class="btn" href="<?= site_url('checkout') ?>">Proceed to checkout →</a></p>
  </div>
<?php endif ?>
<?= $this->endSection() ?>
