<?= $this->extend('layout/admin') ?>
<?= $this->section('content') ?>
<h1><?= esc($title) ?></h1>

<p><a href="<?= site_url('admin/recipes/new') ?>" class="btn">+ New Recipe</a></p>

<div class="card">
<?php if (empty($recipes)): ?>
  <p class="muted">No recipes yet. <a href="<?= site_url('admin/recipes/new') ?>">Create the first one</a>.</p>
<?php else: ?>
  <table>
    <thead>
      <tr>
        <th>Name</th>
        <th>Type</th>
        <th>Stems</th>
        <th>Labor (min)</th>
        <th>Computed Cost</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($recipes as $r): ?>
      <tr>
        <td><a href="<?= site_url('admin/recipes/' . $r['id']) ?>"><?= esc($r['name']) ?></a></td>
        <td><span class="pill"><?= esc($r['type']) ?></span></td>
        <td><?= (int) $r['stem_count'] ?></td>
        <td><?= (int) $r['labor_minutes'] ?></td>
        <td><?= fbk_money($r['cost']) ?></td>
        <td>
          <a href="<?= site_url('admin/recipes/' . $r['id']) ?>"
             class="btn ghost" style="font-size:.8rem;padding:.3rem .7rem;">Edit</a>
        </td>
      </tr>
    <?php endforeach ?>
    </tbody>
  </table>
<?php endif ?>
</div>
<?= $this->endSection() ?>
