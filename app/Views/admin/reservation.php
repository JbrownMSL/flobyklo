<?= $this->extend('layout/admin') ?>
<?= $this->section('content') ?>
<p class="muted"><a href="<?= site_url('admin/reservations') ?>">← Reservations</a></p>
<h1>Reservation #<?= (int) $res['id'] ?> <span class="pill"><?= esc($res['status']) ?></span></h1>

<div class="row">
  <div class="card" style="flex:2 1 360px;">
    <p class="muted"><?= esc($res['start_date']) ?> → <?= esc($res['end_date']) ?> · user #<?= (int) $res['user_id'] ?></p>
    <table><thead><tr><th>Item</th><th>Qty</th><th>Days</th><th>Subtotal</th><th>Tax</th></tr></thead><tbody>
    <?php foreach ($items as $it): ?>
      <tr><td><?= esc($it['equipment_name']) ?><?= $it['is_trailer'] ? ' <span class="pill">trailer</span>' : '' ?></td><td><?= (int) $it['qty'] ?></td><td><?= (int) $it['days'] ?></td><td><?= wtr_money($it['line_subtotal']) ?></td><td><?= wtr_money($it['tax_amount']) ?></td></tr>
    <?php endforeach ?>
    </tbody></table>
    <p><strong>Total:</strong> <?= wtr_money($res['grand_total']) ?> · deposit <?= wtr_money($res['damage_deposit']) ?> <?= $res['damage_waiver'] ? '(waiver elected)' : '' ?></p>

    <h2>Payments</h2>
    <table><thead><tr><th>Type</th><th>Amount</th><th>Status</th><th>Note</th></tr></thead><tbody>
    <?php foreach ($payments as $p): ?><tr><td><?= esc($p['type']) ?></td><td><?= wtr_money($p['amount']) ?></td><td><?= esc($p['status']) ?></td><td class="muted"><?= esc($p['note'] ?? '') ?></td></tr><?php endforeach ?>
    <?php if (! $payments): ?><tr><td colspan="4" class="muted">None.</td></tr><?php endif ?>
    </tbody></table>
    <?php foreach ($damage as $d): ?>
      <p style="color:#b91c1c;">Damage: <?= esc($d['description']) ?> — assessed <?= wtr_money($d['assessed_cost']) ?>, captured <?= wtr_money($d['deposit_captured']) ?></p>
    <?php endforeach ?>
  </div>

  <div class="card" style="flex:1 1 240px;">
    <h2>Actions</h2>
    <?php if ($res['status'] === 'confirmed'): ?>
      <form action="<?= site_url('admin/reservations/' . $res['id'] . '/pickup') ?>" method="post"><?= csrf_field() ?><button class="btn">Mark picked up</button></form>
    <?php endif ?>
    <?php if ($res['status'] === 'picked_up'): ?>
      <form action="<?= site_url('admin/reservations/' . $res['id'] . '/return') ?>" method="post" style="margin:.4rem 0;"><?= csrf_field() ?><button class="btn">Clean return (release deposit)</button></form>
      <form action="<?= site_url('admin/reservations/' . $res['id'] . '/damage') ?>" method="post">
        <?= csrf_field() ?>
        <label>Damage description</label><textarea name="description" rows="2"></textarea>
        <label>Assessed cost</label><input type="number" step="0.01" name="assessed_cost" value="0">
        <button class="btn alt" type="submit" style="margin-top:.4rem;">Log damage &amp; capture</button>
      </form>
    <?php endif ?>
    <hr>
    <form action="<?= site_url('admin/reservations/' . $res['id'] . '/refund') ?>" method="post">
      <?= csrf_field() ?>
      <label>Manual refund amount</label><input type="number" step="0.01" name="amount" value="0">
      <button class="btn ghost" type="submit" style="margin-top:.4rem;">Issue refund</button>
    </form>
  </div>
</div>
<?= $this->endSection() ?>
