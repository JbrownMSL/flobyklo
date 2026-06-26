<?= $this->extend('layout/admin') ?>
<?= $this->section('content') ?>
<?php
  helper('fbk');
  $isEdit    = $quote !== null;
  $qid       = $isEdit ? (int) $quote['id'] : 0;
  $recipeMap = [];
  foreach ($recipes as $r) {
      $recipeMap[$r['id']] = $r;
  }
?>
<div style="display:flex;align-items:center;gap:1rem;margin-bottom:.5rem;">
  <a href="<?= site_url('admin/quotes') ?>" class="btn ghost" style="font-size:.82rem;">← Quotes</a>
  <h1 style="margin:0"><?= esc($title) ?></h1>
</div>

<form method="post" action="<?= site_url('admin/quotes/save') ?>" id="quote-form">
  <?= csrf_field() ?>
  <input type="hidden" name="id" value="<?= $qid ?>">

  <!-- Header fields -->
  <div class="card">
    <div class="row">
      <div>
        <label>Client *</label>
        <select name="client_id" required>
          <option value="">— select client —</option>
          <?php foreach ($clients as $c): ?>
          <option value="<?= (int) $c['id'] ?>"
            <?= $isEdit && (int) $quote['client_id'] === (int) $c['id'] ? 'selected' : '' ?>>
            <?= esc($c['name']) ?>
          </option>
          <?php endforeach ?>
        </select>
      </div>
      <div>
        <label>Event (optional)</label>
        <select name="event_id">
          <option value="">— none —</option>
          <?php foreach ($events as $e): ?>
          <option value="<?= (int) $e['id'] ?>"
            <?= $isEdit && (int) $quote['event_id'] === (int) $e['id'] ? 'selected' : '' ?>>
            <?= esc($e['type']) ?> — <?= esc($e['event_date'] ?? '—') ?>
            <?= $e['venue'] ? ' @ ' . esc($e['venue']) : '' ?>
          </option>
          <?php endforeach ?>
        </select>
      </div>
      <div>
        <label>Deposit %</label>
        <input type="number" name="deposit_pct" id="deposit_pct" min="0" max="100"
               value="<?= $isEdit ? (int) $quote['deposit_pct'] : 50 ?>"
               oninput="updateTotals()">
      </div>
      <div>
        <label>Valid Until</label>
        <input type="date" name="valid_until"
               value="<?= $isEdit && $quote['valid_until'] ? esc($quote['valid_until']) : '' ?>">
      </div>
      <div>
        <label>Tax Rate %</label>
        <input type="number" name="tax_rate" id="tax_rate" min="0" max="100" step="0.01"
               value="<?= number_format((float) $taxRate, 4) ?>"
               oninput="updateTotals()">
      </div>
    </div>
  </div>

  <!-- Line items -->
  <div class="card" style="padding:0;overflow:hidden;">
    <div style="display:flex;justify-content:space-between;align-items:center;padding:.7rem 1.1rem;background:#f8f9fb;border-bottom:1px solid #eef;">
      <strong style="font-size:.95rem;">Line Items</strong>
      <button type="button" onclick="addRow()" class="btn" style="font-size:.82rem;padding:.35rem .8rem;">+ Add Item</button>
    </div>
    <div style="overflow-x:auto;">
    <table id="items-table" style="margin:0;">
      <thead>
        <tr style="background:#f8f9fb;">
          <th style="min-width:160px">Recipe</th>
          <th style="min-width:200px">Description</th>
          <th style="width:75px">Qty</th>
          <th style="width:100px">Unit Price</th>
          <th style="width:100px">Cost (total)</th>
          <th style="width:90px;text-align:right">Line Total</th>
          <th style="width:40px"></th>
        </tr>
      </thead>
      <tbody id="items-body">
      <?php foreach ($items as $i => $item): ?>
        <tr class="item-row" id="row-<?= $i ?>">
          <td>
            <select name="items[<?= $i ?>][recipe_id]"
                    onchange="fillFromRecipe(this, <?= $i ?>)"
                    style="width:100%;font-size:.85rem;">
              <option value="">— free text —</option>
              <?php foreach ($recipes as $r): ?>
              <option value="<?= (int) $r['id'] ?>"
                <?= (int) $item['recipe_id'] === (int) $r['id'] ? 'selected' : '' ?>>
                <?= esc($r['name']) ?>
              </option>
              <?php endforeach ?>
            </select>
          </td>
          <td>
            <input type="text" name="items[<?= $i ?>][description]"
                   value="<?= esc($item['description']) ?>"
                   placeholder="Description" style="width:100%;font-size:.85rem;">
          </td>
          <td>
            <input type="number" name="items[<?= $i ?>][qty]"
                   value="<?= number_format((float) $item['qty'], 2) ?>"
                   min="0" step="0.01" style="width:70px;font-size:.85rem;"
                   oninput="calcRow(<?= $i ?>)">
          </td>
          <td>
            <input type="number" name="items[<?= $i ?>][unit_price]"
                   value="<?= number_format((float) $item['unit_price'], 2) ?>"
                   min="0" step="0.01" style="width:90px;font-size:.85rem;"
                   oninput="calcRow(<?= $i ?>)">
          </td>
          <td>
            <input type="number" name="items[<?= $i ?>][cost]"
                   value="<?= number_format((float) $item['cost'], 2) ?>"
                   min="0" step="0.01" style="width:90px;font-size:.85rem;"
                   oninput="updateTotals()">
          </td>
          <td style="text-align:right;white-space:nowrap;" id="lt-<?= $i ?>">
            <?= fbk_money($item['line_total']) ?>
          </td>
          <td>
            <button type="button" onclick="removeRow(<?= $i ?>)"
                    style="background:#c47a7a;color:#fff;border:0;border-radius:5px;padding:.3rem .55rem;cursor:pointer;font-size:.85rem;">×</button>
          </td>
        </tr>
      <?php endforeach ?>
      <?php if (! $items): // Start with one blank row for new quotes ?>
        <tr class="item-row" id="row-0">
          <td>
            <select name="items[0][recipe_id]" onchange="fillFromRecipe(this, 0)" style="width:100%;font-size:.85rem;">
              <option value="">— free text —</option>
              <?php foreach ($recipes as $r): ?>
              <option value="<?= (int) $r['id'] ?>"><?= esc($r['name']) ?></option>
              <?php endforeach ?>
            </select>
          </td>
          <td><input type="text" name="items[0][description]" placeholder="Description" style="width:100%;font-size:.85rem;"></td>
          <td><input type="number" name="items[0][qty]" value="1" min="0" step="0.01" style="width:70px;font-size:.85rem;" oninput="calcRow(0)"></td>
          <td><input type="number" name="items[0][unit_price]" value="0.00" min="0" step="0.01" style="width:90px;font-size:.85rem;" oninput="calcRow(0)"></td>
          <td><input type="number" name="items[0][cost]" value="0.00" min="0" step="0.01" style="width:90px;font-size:.85rem;" oninput="updateTotals()"></td>
          <td style="text-align:right;" id="lt-0">$0.00</td>
          <td>
            <button type="button" onclick="removeRow(0)"
                    style="background:#c47a7a;color:#fff;border:0;border-radius:5px;padding:.3rem .55rem;cursor:pointer;font-size:.85rem;">×</button>
          </td>
        </tr>
      <?php endif ?>
      </tbody>
    </table>
    </div>
  </div>

  <!-- Totals summary -->
  <div class="card" style="background:#f8f9fb;">
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:.8rem;">
      <div class="kpi" style="padding:.7rem .9rem;">
        <div class="l">Subtotal</div>
        <div class="n" id="disp-subtotal" style="font-size:1.2rem;">$0.00</div>
      </div>
      <div class="kpi" style="padding:.7rem .9rem;">
        <div class="l">Tax</div>
        <div class="n" id="disp-tax" style="font-size:1.2rem;">$0.00</div>
      </div>
      <div class="kpi" style="padding:.7rem .9rem;">
        <div class="l">Total</div>
        <div class="n" id="disp-total" style="font-size:1.3rem;color:#3a7a50;">$0.00</div>
      </div>
      <div class="kpi" style="padding:.7rem .9rem;">
        <div class="l">Deposit (<span id="dep-pct-label">50</span>%)</div>
        <div class="n" id="disp-deposit" style="font-size:1.2rem;">$0.00</div>
      </div>
      <div class="kpi" style="padding:.7rem .9rem;">
        <div class="l">Gross Margin</div>
        <div class="n" id="disp-margin" style="font-size:1.2rem;">—</div>
      </div>
    </div>
  </div>

  <div style="display:flex;gap:.8rem;margin-top:.5rem;">
    <button type="submit" class="btn">Save Draft</button>
    <a href="<?= site_url('admin/quotes') ?>" class="btn ghost">Cancel</a>
  </div>
</form>

<script>
(function () {
  // Recipe data keyed by id
  const RECIPES = <?= json_encode(array_column($recipes, null, 'id'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

  let rowCount = <?= $items ? count($items) : 1 ?>;

  function escHtml(s) {
    return String(s)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function buildRecipeOptions() {
    let html = '<option value="">— free text —</option>';
    for (const id in RECIPES) {
      html += '<option value="' + escHtml(id) + '">' + escHtml(RECIPES[id].name) + '</option>';
    }
    return html;
  }

  window.addRow = function () {
    const n   = rowCount++;
    const row = document.createElement('tr');
    row.className = 'item-row';
    row.id        = 'row-' + n;
    row.innerHTML =
      '<td><select name="items[' + n + '][recipe_id]" onchange="fillFromRecipe(this,' + n + ')" style="width:100%;font-size:.85rem;">' +
        buildRecipeOptions() +
      '</select></td>' +
      '<td><input type="text" name="items[' + n + '][description]" placeholder="Description" style="width:100%;font-size:.85rem;"></td>' +
      '<td><input type="number" name="items[' + n + '][qty]" value="1" min="0" step="0.01" style="width:70px;font-size:.85rem;" oninput="calcRow(' + n + ')"></td>' +
      '<td><input type="number" name="items[' + n + '][unit_price]" value="0.00" min="0" step="0.01" style="width:90px;font-size:.85rem;" oninput="calcRow(' + n + ')"></td>' +
      '<td><input type="number" name="items[' + n + '][cost]" value="0.00" min="0" step="0.01" style="width:90px;font-size:.85rem;" oninput="updateTotals()"></td>' +
      '<td style="text-align:right;" id="lt-' + n + '">$0.00</td>' +
      '<td><button type="button" onclick="removeRow(' + n + ')" style="background:#c47a7a;color:#fff;border:0;border-radius:5px;padding:.3rem .55rem;cursor:pointer;font-size:.85rem;">×</button></td>';
    document.getElementById('items-body').appendChild(row);
    updateTotals();
  };

  window.fillFromRecipe = function (sel, n) {
    const rid = parseInt(sel.value, 10);
    if (! rid) { return; }
    const r = RECIPES[rid];
    if (! r) { return; }

    const descEl = document.querySelector('[name="items[' + n + '][description]"]');
    const costEl = document.querySelector('[name="items[' + n + '][cost]"]');
    const qtyEl  = document.querySelector('[name="items[' + n + '][qty]"]');

    if (descEl && ! descEl.value) { descEl.value = r.name; }
    if (costEl && qtyEl) {
      const qty = parseFloat(qtyEl.value) || 1;
      costEl.value = (qty * parseFloat(r.total_cost)).toFixed(2);
    }
    calcRow(n);
  };

  window.calcRow = function (n) {
    const qtyEl   = document.querySelector('[name="items[' + n + '][qty]"]');
    const priceEl = document.querySelector('[name="items[' + n + '][unit_price]"]');
    const costEl  = document.querySelector('[name="items[' + n + '][cost]"]');
    if (! qtyEl || ! priceEl) { return; }

    const qty   = parseFloat(qtyEl.value) || 0;
    const price = parseFloat(priceEl.value) || 0;
    const lt    = qty * price;

    // Auto-update total cost if recipe is selected
    const sel = document.querySelector('[name="items[' + n + '][recipe_id]"]');
    if (sel && sel.value && costEl) {
      const rid = parseInt(sel.value, 10);
      const r   = RECIPES[rid];
      if (r) {
        costEl.value = (qty * parseFloat(r.total_cost)).toFixed(2);
      }
    }

    const ltEl = document.getElementById('lt-' + n);
    if (ltEl) { ltEl.textContent = '$' + lt.toFixed(2); }
    updateTotals();
  };

  window.removeRow = function (n) {
    const row = document.getElementById('row-' + n);
    if (row) { row.remove(); updateTotals(); }
  };

  window.updateTotals = function () {
    let subtotal  = 0;
    let totalCost = 0;

    document.querySelectorAll('.item-row').forEach(function (row) {
      const n     = row.id.replace('row-', '');
      const qty   = parseFloat((document.querySelector('[name="items[' + n + '][qty]"]') || {}).value) || 0;
      const price = parseFloat((document.querySelector('[name="items[' + n + '][unit_price]"]') || {}).value) || 0;
      const cost  = parseFloat((document.querySelector('[name="items[' + n + '][cost]"]') || {}).value) || 0;
      subtotal  += qty * price;
      totalCost += cost; // cost is stored as total cost for the item (qty already baked in)
    });

    const taxRate = parseFloat(document.getElementById('tax_rate').value) || 0;
    const tax     = subtotal * (taxRate / 100);
    const total   = subtotal + tax;
    const depPct  = parseInt(document.getElementById('deposit_pct').value, 10) || 50;
    const deposit = total * (depPct / 100);
    const margin  = total > 0 ? ((total - totalCost) / total * 100) : null;

    document.getElementById('disp-subtotal').textContent = '$' + subtotal.toFixed(2);
    document.getElementById('disp-tax').textContent      = '$' + tax.toFixed(2);
    document.getElementById('disp-total').textContent    = '$' + total.toFixed(2);
    document.getElementById('disp-deposit').textContent  = '$' + deposit.toFixed(2);
    document.getElementById('dep-pct-label').textContent = depPct;
    document.getElementById('disp-margin').textContent   = margin !== null ? margin.toFixed(1) + '%' : '—';

    const marginEl = document.getElementById('disp-margin');
    if (margin !== null) {
      marginEl.style.color = margin >= 40 ? '#3a7a50' : (margin >= 20 ? '#a06030' : '#c04040');
    }
  };

  // Run on load to populate totals from existing items
  updateTotals();
}());
</script>
<?= $this->endSection() ?>
