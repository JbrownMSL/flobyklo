<?= $this->extend('layout/main') ?>
<?= $this->section('content') ?>
<h1>Checkout</h1>

<div class="card">
  <h2>Summary</h2>
  <p class="muted"><?= esc($summary['start']) ?> → <?= esc($summary['end']) ?> · <?= (int) $summary['days'] ?> day(s)</p>
  <table class="totals">
    <tr><td>Rental subtotal</td><td style="text-align:right;"><?= wtr_money($summary['rental_subtotal']) ?></td></tr>
    <?php foreach ($summary['tax_by_class'] as $cls => $amt): ?>
      <tr><td><?= esc(config('Wtr')->taxClasses[$cls] ?? $cls) ?></td><td style="text-align:right;"><?= wtr_money($amt) ?></td></tr>
    <?php endforeach ?>
    <?php if ($summary['waiver'] > 0): ?><tr><td>Damage waiver</td><td style="text-align:right;"><?= wtr_money($summary['waiver']) ?></td></tr><?php endif ?>
    <tr><td>Rental total</td><td style="text-align:right;"><?= wtr_money($summary['grand_total']) ?></td></tr>
    <tr class="grand"><td>Charged today (50%)</td><td style="text-align:right;"><?= wtr_money($summary['booking_charge']) ?></td></tr>
    <tr><td class="muted">Balance due at pickup</td><td style="text-align:right;" class="muted"><?= wtr_money($summary['balance_due']) ?></td></tr>
    <?php if ($summary['damage_deposit'] > 0): ?><tr><td class="muted">+ Security deposit (authorized hold, released on return)</td><td style="text-align:right;" class="muted"><?= wtr_money($summary['damage_deposit']) ?></td></tr><?php endif ?>
  </table>
</div>

<?php if (! $accepted): ?>
  <!-- STEP 1: sign the rental agreement -->
  <div class="card">
    <h2>1 · Rental Agreement</h2>
    <div style="max-height:240px;overflow:auto;border:1px solid #eee;border-radius:8px;padding:.8rem;font-size:.85rem;">
      <?= $contract['body_html'] ?? '<em>No contract configured.</em>' ?>
    </div>
    <form action="<?= site_url('checkout/contract') ?>" method="post" id="sigForm">
      <?= csrf_field() ?>
      <input type="hidden" name="method" id="sigMethod" value="typed">
      <input type="hidden" name="signature_image" id="sigImage">
      <div style="margin-top:.8rem;">
        <label><input type="radio" name="sigchoice" value="typed" checked onclick="setMode('typed')" style="width:auto;"> Type my name</label>
        <label><input type="radio" name="sigchoice" value="drawn" onclick="setMode('drawn')" style="width:auto;"> Draw my signature</label>
      </div>
      <div id="typedBox"><label>Full legal name</label><input type="text" name="signature_name" id="sigName"></div>
      <div id="drawnBox" style="display:none;">
        <label>Sign below</label>
        <canvas id="pad" width="600" height="160" style="border:1px solid #d7dbe3;border-radius:8px;width:100%;touch-action:none;background:#fff;"></canvas>
        <button type="button" class="btn ghost" onclick="clearPad()" style="margin-top:.3rem;padding:.2rem .6rem;">Clear</button>
      </div>
      <label style="display:flex;gap:.5rem;align-items:flex-start;margin-top:.6rem;"><input type="checkbox" name="agree" value="1" style="width:auto;"> I have read and agree to the entire Rental Packet — the Equipment Rental Agreement, Liability Waiver &amp; Release, Safety Acknowledgment, Credit-Card Authorization, and the <strong>insurance terms</strong> (I will carry my own coverage or accept full liability), and the security-deposit and cancellation terms.</label>
      <label style="display:flex;gap:.5rem;align-items:flex-start;"><input type="checkbox" name="age_attest" value="1" style="width:auto;"> I confirm I am at least 18 years old and will present a valid driver's license and a second form of ID (utility bill, vehicle registration, or credit card) at pickup.</label>
      <button class="btn" type="submit" style="margin-top:.6rem;">Sign &amp; continue</button>
    </form>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/signature_pad@4/dist/signature_pad.umd.min.js"></script>
  <script>
    let pad;
    function setMode(m){
      document.getElementById('sigMethod').value=m;
      document.getElementById('typedBox').style.display = m==='typed'?'':'none';
      document.getElementById('drawnBox').style.display = m==='drawn'?'':'none';
      if(m==='drawn' && !pad){ pad = new SignaturePad(document.getElementById('pad')); }
    }
    function clearPad(){ if(pad) pad.clear(); }
    document.getElementById('sigForm').addEventListener('submit', function(){
      if(document.getElementById('sigMethod').value==='drawn' && pad && !pad.isEmpty()){
        document.getElementById('sigImage').value = pad.toDataURL('image/png');
      }
    });
  </script>
<?php else: ?>
  <!-- STEP 2: payment -->
  <div class="card">
    <h2>2 · Payment</h2>
    <?php if (! $square['live']): ?>
      <div class="flash">💳 Online card payment is launching soon. Reserve now and we'll confirm your booking and arrange the 50% deposit (<?= wtr_money($summary['booking_charge']) ?>) by phone or text.</div>
      <form action="<?= site_url('checkout/pay') ?>" method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="square_token" value="reserve-no-payment">
        <button class="btn" type="submit">Reserve now — we'll confirm &amp; arrange payment</button>
      </form>
    <?php else: ?>
      <div id="card-container"></div>
      <div id="card-errors" class="flash err" style="display:none;"></div>
      <form action="<?= site_url('checkout/pay') ?>" method="post" id="payForm">
        <?= csrf_field() ?>
        <input type="hidden" name="square_token" id="square_token">
        <button class="btn" type="button" id="payBtn">Pay <?= wtr_money($summary['booking_charge']) ?> now &amp; confirm</button>
      </form>
      <script src="https://<?= $square['env'] === 'production' ? 'web' : 'sandbox.web' ?>.squarecdn.com/v1/square.js"></script>
      <script>
        (async function(){
          const payments = Square.payments('<?= esc($square['appId']) ?>', '<?= esc($square['locationId']) ?>');
          const card = await payments.card();
          await card.attach('#card-container');
          document.getElementById('payBtn').addEventListener('click', async () => {
            const result = await card.tokenize();
            if (result.status === 'OK') {
              document.getElementById('square_token').value = result.token;
              document.getElementById('payForm').submit();
            } else {
              const e = document.getElementById('card-errors');
              e.style.display=''; e.textContent = 'Card error — please check your details.';
            }
          });
        })();
      </script>
    <?php endif ?>
  </div>
<?php endif ?>
<?= $this->endSection() ?>
