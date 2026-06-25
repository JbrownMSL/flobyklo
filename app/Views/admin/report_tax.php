<?= $this->extend('layout/admin') ?>
<?= $this->section('content') ?>
<h1>Tax Collected</h1>
<div class="card">
  <form method="get" class="row" style="align-items:flex-end;">
    <div><label>From</label><input type="date" name="from" value="<?= esc($from) ?>"></div>
    <div><label>To</label><input type="date" name="to" value="<?= esc($to) ?>"></div>
    <div style="flex:0;"><button class="btn" type="submit">Run</button></div>
  </form>
  <table style="margin-top:.6rem;"><thead><tr><th>Tax class</th><th>Taxable base</th><th>Tax collected</th></tr></thead><tbody>
  <?php $tt = 0; foreach ($rows as $r): $tt += (float) $r['tax']; ?>
    <tr><td><?= esc(config('Wtr')->taxClasses[$r['tax_class']] ?? $r['tax_class']) ?></td><td><?= wtr_money($r['taxable']) ?></td><td><?= wtr_money($r['tax']) ?></td></tr>
  <?php endforeach ?>
  <?php if (! $rows): ?><tr><td colspan="3" class="muted">No taxable rentals in range.</td></tr><?php endif ?>
  </tbody><tfoot><tr style="font-weight:800;"><td colspan="2">Total tax to remit</td><td><?= wtr_money($tt) ?></td></tr></tfoot></table>
</div>
<?= $this->endSection() ?>
