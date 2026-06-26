<?= $this->extend('layout/admin') ?>
<?= $this->section('content') ?>
<div style="display:flex;align-items:center;gap:1rem;margin-bottom:.4rem;">
  <h1 style="margin:0;">Contracts</h1>
</div>
<div class="card">
  <p class="muted">Contracts are generated when a quote is accepted, then signed by the client.</p>
  <?php if (empty($contracts)): ?>
    <p class="muted">No contracts yet.</p>
  <?php else: ?>
  <table>
    <thead><tr><th>#</th><th>Client</th><th>Quote</th><th>Signed</th><th>Created</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($contracts as $c): ?>
      <tr>
        <td>#<?= (int) $c['id'] ?></td>
        <td><?= esc($c['client_name'] ?? '—') ?></td>
        <td><?= $c['quote_id'] ? '#' . (int) $c['quote_id'] : '—' ?></td>
        <td><?= ! empty($c['signed_at']) ? esc($c['signed_name']) . ' — ' . esc($c['signed_at']) : '<span class="muted">unsigned</span>' ?></td>
        <td><?= esc($c['created_at'] ?? '') ?></td>
        <td><a class="btn ghost" href="<?= site_url('admin/contracts/' . (int) $c['id']) ?>">View</a></td>
      </tr>
    <?php endforeach ?>
    </tbody>
  </table>
  <?php endif ?>
</div>
<?= $this->endSection() ?>
