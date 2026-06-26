<?= $this->extend('layout/admin') ?>
<?= $this->section('content') ?>
<div style="display:flex;align-items:center;justify-content:space-between;gap:1rem;margin-bottom:.4rem;">
  <h1 style="margin:0;"><?= esc($title) ?></h1>
  <a class="btn ghost" href="<?= site_url('admin/contracts') ?>">&#8592; Back</a>
</div>
<div class="card">
  <?php if (! empty($contract['quote_id'])): ?>
    <p class="muted">Quote: <a href="<?= site_url('admin/quotes/' . (int) $contract['quote_id']) ?>">#<?= (int) $contract['quote_id'] ?></a></p>
  <?php endif ?>
  <div style="white-space:pre-wrap;border:1px solid #e7c9c0;border-radius:6px;padding:1rem;background:#faf6f0;"><?= esc($contract['body'] ?? '(no contract body yet)') ?></div>
  <?php if (! empty($contract['signed_at'])): ?>
    <p style="margin-top:1rem;">Signed by <strong><?= esc($contract['signed_name']) ?></strong> on <?= esc($contract['signed_at']) ?>.</p>
  <?php else: ?>
    <form method="post" action="<?= site_url('admin/contracts/' . (int) $contract['id'] . '/sign') ?>" style="margin-top:1rem;">
      <?= csrf_field() ?>
      <label>Type your full name to sign</label>
      <input type="text" name="signed_name" required style="max-width:340px;">
      <button class="btn" type="submit" style="margin-top:.5rem;">Sign contract</button>
    </form>
  <?php endif ?>
</div>
<?= $this->endSection() ?>
