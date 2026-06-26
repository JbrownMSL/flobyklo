<?= $this->extend('layout/admin') ?>
<?= $this->section('content') ?>
<h1><?= esc($title) ?></h1>

<p><a href="<?= site_url('admin/recipes') ?>" class="muted">&larr; All Recipes</a></p>

<!-- ── Recipe metadata ─────────────────────────────────────────────────────── -->
<div class="card">
  <h2>Recipe Details</h2>
  <form method="post" action="<?= site_url('admin/recipes/save') ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= $recipe ? (int) $recipe['id'] : '' ?>">
    <div class="row">
      <div>
        <label>Name *</label>
        <input type="text" name="name"
               value="<?= esc(old('name', $recipe['name'] ?? '')) ?>"
               required maxlength="150">
      </div>
      <div style="flex:0 0 160px">
        <label>Type *</label>
        <select name="type">
          <?php foreach (['bouquet', 'centerpiece', 'install', 'boutonniere', 'arch', 'other'] as $t): ?>
            <option value="<?= $t ?>"
              <?= (old('type', $recipe['type'] ?? 'bouquet') === $t ? 'selected' : '') ?>>
              <?= ucfirst($t) ?>
            </option>
          <?php endforeach ?>
        </select>
      </div>
      <div style="flex:0 0 160px">
        <label>Labor (minutes) *</label>
        <input type="number" name="labor_minutes" id="labor-minutes-field" min="0"
               value="<?= (int) (old('labor_minutes', $recipe['labor_minutes'] ?? 0)) ?>"
               required>
      </div>
    </div>
    <div style="margin-top:.5rem">
      <label>Notes</label>
      <textarea name="notes" rows="2"><?= esc(old('notes', $recipe['notes'] ?? '')) ?></textarea>
    </div>
    <div style="margin-top:.8rem">
      <button class="btn" type="submit">Save Recipe</button>
      <a href="<?= site_url('admin/recipes') ?>" class="btn ghost" style="margin-left:.5rem">Cancel</a>
    </div>
  </form>
</div>

<?php if ($recipe): ?>

<!-- ── Stems + Cost ───────────────────────────────────────────────────────── -->
<div class="row" style="align-items:flex-start">

  <!-- Stems table + add/edit form -->
  <div class="card" style="flex:2;min-width:0">
    <h2>Stems</h2>

    <?php if (! empty($stems)): ?>
    <table id="stems-table">
      <thead>
        <tr>
          <th>Stem</th>
          <th>Qty</th>
          <th>Unit cost</th>
          <th>Line total</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($stems as $s): ?>
        <tr data-qty="<?= (float) $s['qty'] ?>" data-unit-cost="<?= (float) $s['unit_cost'] ?>">
          <td><?= esc($s['stem_name']) ?></td>
          <td><?= (float) $s['qty'] ?></td>
          <td><?= fbk_money($s['unit_cost']) ?></td>
          <td><?= fbk_money((float) $s['qty'] * (float) $s['unit_cost']) ?></td>
          <td style="white-space:nowrap">
            <button type="button" class="btn ghost edit-stem-btn"
                    style="font-size:.75rem;padding:.2rem .55rem"
                    data-id="<?= (int) $s['id'] ?>"
                    data-name="<?= esc($s['stem_name'], 'attr') ?>"
                    data-qty="<?= (float) $s['qty'] ?>"
                    data-cost="<?= (float) $s['unit_cost'] ?>">Edit</button>
            <form method="post"
                  action="<?= site_url('admin/recipes/stem/' . (int) $s['id'] . '/delete') ?>"
                  style="display:inline">
              <?= csrf_field() ?>
              <button type="submit" class="btn alt"
                      style="font-size:.75rem;padding:.2rem .55rem"
                      onclick="return confirm('Remove this stem?')">&times;</button>
            </form>
          </td>
        </tr>
      <?php endforeach ?>
      </tbody>
    </table>
    <?php else: ?>
    <p class="muted">No stems yet — add one below.</p>
    <table id="stems-table" style="display:none"><tbody></tbody></table>
    <?php endif ?>

    <!-- Add / edit stem form -->
    <form method="post"
          action="<?= site_url('admin/recipes/' . (int) $recipe['id'] . '/stem') ?>"
          id="stem-form"
          style="margin-top:1.2rem;border-top:1px solid #eef;padding-top:1rem">
      <?= csrf_field() ?>
      <input type="hidden" name="stem_id" id="stem-id-field" value="">
      <h2 id="stem-form-title" style="margin-top:0">Add Stem</h2>
      <div class="row">
        <div>
          <label>Stem name *</label>
          <input type="text" name="stem_name" id="stem-name-input"
                 placeholder="e.g. Garden Rose" required maxlength="150">
        </div>
        <div style="flex:0 0 90px">
          <label>Qty</label>
          <input type="number" name="qty" id="stem-qty-input"
                 value="1" step="0.01" min="0.01" required>
        </div>
        <div style="flex:0 0 110px">
          <label>Unit cost ($)</label>
          <input type="number" name="unit_cost" id="stem-cost-input"
                 value="0.00" step="0.01" min="0" required>
        </div>
      </div>
      <div style="margin-top:.6rem">
        <button class="btn" type="submit">Save Stem</button>
        <button type="button" class="btn ghost" id="cancel-edit-btn"
                style="display:none;margin-left:.5rem">Cancel Edit</button>
      </div>
    </form>
  </div>

  <!-- Live cost summary -->
  <div class="card" style="flex:1;min-width:220px;max-width:300px">
    <h2>Cost Summary</h2>
    <table style="font-size:.9rem;margin-bottom:.6rem">
      <tbody id="cost-breakdown">
        <tr><td colspan="2" class="muted" style="font-size:.82rem">Add stems to see breakdown.</td></tr>
      </tbody>
    </table>
    <div style="border-top:2px solid #eef;padding-top:.5rem;display:flex;justify-content:space-between;font-weight:700;font-size:1rem">
      <span>Total cost</span>
      <span id="total-cost-display"><?= fbk_money($cost) ?></span>
    </div>

    <!-- Margin / price suggester -->
    <div style="margin-top:1.2rem;border-top:1px solid #eef;padding-top:.8rem">
      <label>Target margin %</label>
      <div style="display:flex;gap:.5rem;align-items:center">
        <input type="number" id="margin-input"
               value="40" min="1" max="99" step="1"
               style="width:80px">
        <span class="muted" style="font-size:.8rem">%</span>
      </div>
      <div style="margin-top:.6rem;font-size:.9rem">
        Suggested price: <strong id="suggested-price"><?= fbk_money($cost > 0 ? $cost / (1 - 0.40) : 0) ?></strong>
      </div>
      <div style="font-size:.75rem;color:#aaa;margin-top:.2rem">
        price = cost &divide; (1 &minus; margin%)
      </div>
    </div>
  </div>

</div><!-- /.row -->

<script>
(function () {
  var LABOR_RATE    = <?= (float) $laborRate ?>;
  var LABOR_MINUTES = <?= (int) $recipe['labor_minutes'] ?>;

  /* ── recalculate and render ── */
  function calcCost() {
    var stemCost  = 0;
    var breakdown = '';

    document.querySelectorAll('#stems-table tbody tr[data-qty]').forEach(function (row) {
      var qty  = parseFloat(row.dataset.qty      || 0);
      var uc   = parseFloat(row.dataset.unitCost || 0);
      var line = qty * uc;
      stemCost += line;
      var name = row.querySelector('td').textContent.trim();
      breakdown += '<tr><td style="padding:.25rem .4rem">' + esc(name) +
                   '</td><td style="text-align:right;padding:.25rem .4rem">$' +
                   line.toFixed(2) + '</td></tr>';
    });

    /* preview unsaved stem from the add-form */
    var previewName  = document.getElementById('stem-name-input').value.trim();
    var editingStemId = document.getElementById('stem-id-field').value;
    if (previewName && ! editingStemId) {
      var pQty  = parseFloat(document.getElementById('stem-qty-input').value  || 0);
      var pCost = parseFloat(document.getElementById('stem-cost-input').value || 0);
      var pLine = pQty * pCost;
      stemCost += pLine;
      breakdown += '<tr style="opacity:.65"><td style="padding:.25rem .4rem"><em>' + esc(previewName) +
                   ' (new)</em></td><td style="text-align:right;padding:.25rem .4rem">$' +
                   pLine.toFixed(2) + '</td></tr>';
    }

    var laborCost = (LABOR_MINUTES / 60) * LABOR_RATE;
    breakdown += '<tr style="border-top:1px solid #eef"><td style="padding:.35rem .4rem;color:#667">Labor (' +
                 LABOR_MINUTES + ' min @ $' + LABOR_RATE + '/hr)</td>' +
                 '<td style="text-align:right;padding:.35rem .4rem;color:#667">$' +
                 laborCost.toFixed(2) + '</td></tr>';

    if (breakdown) {
      document.getElementById('cost-breakdown').innerHTML = breakdown;
    }

    var total = stemCost + laborCost;
    document.getElementById('total-cost-display').textContent = '$' + total.toFixed(2);

    var margin = parseFloat(document.getElementById('margin-input').value || 40);
    margin = Math.max(1, Math.min(99, margin));
    var suggested = margin < 100 ? total / (1 - margin / 100) : 0;
    document.getElementById('suggested-price').textContent = '$' + suggested.toFixed(2);
  }

  function esc(str) {
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  /* ── listen to inputs ── */
  ['stem-name-input', 'stem-qty-input', 'stem-cost-input', 'margin-input'].forEach(function (id) {
    document.getElementById(id).addEventListener('input', calcCost);
  });

  /* sync labor_minutes from the recipe form in real-time */
  var laborField = document.getElementById('labor-minutes-field');
  if (laborField) {
    laborField.addEventListener('input', function () {
      LABOR_MINUTES = parseInt(this.value, 10) || 0;
      calcCost();
    });
  }

  /* ── edit-stem: populate form ── */
  document.querySelectorAll('.edit-stem-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
      document.getElementById('stem-id-field').value   = this.dataset.id;
      document.getElementById('stem-name-input').value = this.dataset.name;
      document.getElementById('stem-qty-input').value  = this.dataset.qty;
      document.getElementById('stem-cost-input').value = this.dataset.cost;
      document.getElementById('stem-form-title').textContent = 'Edit Stem';
      document.getElementById('cancel-edit-btn').style.display = '';
      document.getElementById('stem-name-input').focus();
      calcCost();
    });
  });

  /* ── cancel edit ── */
  document.getElementById('cancel-edit-btn').addEventListener('click', function () {
    document.getElementById('stem-id-field').value   = '';
    document.getElementById('stem-name-input').value = '';
    document.getElementById('stem-qty-input').value  = '1';
    document.getElementById('stem-cost-input').value = '0.00';
    document.getElementById('stem-form-title').textContent = 'Add Stem';
    this.style.display = 'none';
    calcCost();
  });

  /* ── initial render ── */
  calcCost();
}());
</script>

<?php endif ?>
<?= $this->endSection() ?>
