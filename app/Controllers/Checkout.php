<?php

namespace App\Controllers;

use App\Models\ContractModel;
use App\Models\ReservationModel;
use App\Models\ReservationItemModel;
use App\Services\Availability;
use App\Services\Cart as CartService;
use App\Services\ProfitSplit;
use App\Services\Square;

class Checkout extends BaseController
{
    public function index()
    {
        $cart = new CartService();
        if ($cart->isEmpty()) {
            return redirect()->to('/cart')->with('error', 'Your cart is empty.');
        }
        $waiver  = (bool) session()->get('wtr_waiver');
        $summary = $cart->summary($waiver);
        // Re-verify availability at checkout (someone may have booked since).
        $avail = new Availability();
        foreach ($summary['lines'] as $ln) {
            if (! $avail->isAvailable($ln['equipment_id'], $summary['start'], $summary['end'], $ln['qty'])) {
                return redirect()->to('/cart')->with('error', "“{$ln['name']}” is no longer available for those dates.");
            }
        }
        $cfg = config('Wtr');
        return view('checkout/index', [
            'title'    => 'Checkout',
            'summary'  => $summary,
            'waiver'   => $waiver,
            'contract' => (new ContractModel())->current(),
            'accepted' => session()->get('wtr_contract_acceptance_id'),
            'square'   => ['live' => (new Square())->isLive(), 'appId' => $cfg->squareAppId, 'locationId' => $cfg->squareLocationId, 'env' => $cfg->squareEnv],
        ]);
    }

    /** Records the e-signed contract acceptance before payment. */
    public function acceptContract()
    {
        $contract = (new ContractModel())->current();
        if (! $contract) {
            return redirect()->back()->with('error', 'No active rental contract is configured.');
        }
        $method = $this->request->getPost('method') === 'drawn' ? 'drawn' : 'typed';
        $name   = trim((string) $this->request->getPost('signature_name'));
        $image  = (string) $this->request->getPost('signature_image');
        $agree  = (bool) $this->request->getPost('agree');
        $age    = (bool) $this->request->getPost('age_attest');

        if (! $agree || ! $age) {
            return redirect()->back()->with('error', 'You must accept the agreement and confirm you are 18+.');
        }
        if ($method === 'typed' && $name === '') {
            return redirect()->back()->with('error', 'Type your full name to sign, or draw your signature.');
        }
        if ($method === 'drawn' && strlen($image) < 100) {
            return redirect()->back()->with('error', 'Please draw your signature.');
        }

        $db = db_connect();
        $db->table('contract_acceptances')->insert([
            'user_id'          => (int) auth()->id(),
            'contract_id'      => (int) $contract['id'],
            'contract_version' => $contract['version'],
            'method'           => $method,
            'signature_name'   => $name ?: null,
            'signature_image'  => $method === 'drawn' ? $image : null,
            'accepted_at'      => date('Y-m-d H:i:s'),
            'ip'               => $this->request->getIPAddress(),
            'user_agent'       => substr((string) $this->request->getUserAgent(), 0, 255),
        ]);
        session()->set('wtr_contract_acceptance_id', (int) $db->insertID());
        return redirect()->to('/checkout')->with('msg', 'Agreement signed — complete payment to confirm.');
    }

    /**
     * Finalize: create reservation + items + blackouts, charge rental via Square,
     * hold the damage deposit, allocate profit-split, email confirmation.
     */
    public function pay()
    {
        $cart = new CartService();
        if ($cart->isEmpty()) {
            return redirect()->to('/cart')->with('error', 'Your cart is empty.');
        }
        $acceptanceId = (int) session()->get('wtr_contract_acceptance_id');
        if (! $acceptanceId) {
            return redirect()->to('/checkout')->with('error', 'Please sign the rental agreement first.');
        }
        $waiver  = (bool) session()->get('wtr_waiver');
        $summary = $cart->summary($waiver);
        $token   = (string) $this->request->getPost('square_token'); // nonce from Web Payments SDK
        $userId  = (int) auth()->id();

        $db = db_connect();
        $db->transStart();

        $resModel = new ReservationModel();
        $resId = $resModel->insert([
            'user_id'                => $userId,
            'status'                 => 'pending',
            'start_date'             => $summary['start'],
            'end_date'               => $summary['end'],
            'rental_subtotal'        => $summary['rental_subtotal'],
            'waiver_amount'          => $summary['waiver'],
            'tax_total'              => $summary['tax_total'],
            'grand_total'            => $summary['grand_total'],
            'amount_paid'            => $summary['booking_charge'],
            'balance_due'            => $summary['balance_due'],
            'damage_deposit'         => $summary['damage_deposit'],
            'damage_waiver'          => $waiver ? 1 : 0,
            'contract_acceptance_id' => $acceptanceId,
        ], true);

        $itemModel = new ReservationItemModel();
        $avail = new Availability();
        foreach ($summary['lines'] as $ln) {
            $itemModel->insert([
                'reservation_id' => $resId,
                'equipment_id'   => $ln['equipment_id'],
                'qty'            => $ln['qty'],
                'days'           => $ln['days'],
                'rate_snapshot'  => $ln['rate_snapshot'],
                'line_subtotal'  => $ln['line_subtotal'],
                'tax_class'      => $ln['tax_class'],
                'tax_amount'     => $ln['tax_amount'],
                'is_trailer'     => $ln['is_trailer'],
                'auto_added'     => $ln['auto_added'],
            ]);
            $avail->book($ln['equipment_id'], $summary['start'], $summary['end'], $ln['qty'], $resId);
        }

        // --- Square: charge the 50% booking deposit now (balance at pickup);
        //     hold the refundable security deposit unless the waiver was taken ---
        $sq = new Square();
        $rentalCharge = $summary['booking_charge'];
        $chargeRes = $sq->charge($token, $rentalCharge, "wtr-deposit-{$resId}", "WTR booking deposit (50%) #{$resId}");
        $this->recordPayment($db, $resId, 'rental_deposit', $rentalCharge, $chargeRes);

        if (! ($chargeRes['ok'] ?? false)) {
            $db->transRollback();
            $avail->release($resId);
            return redirect()->to('/checkout')->with('error', 'Payment could not be processed. Please try again.');
        }

        if (! $waiver && $summary['damage_deposit'] > 0) {
            $holdRes = $sq->hold($token, $summary['damage_deposit'], "wtr-deposit-{$resId}", "WTR damage deposit #{$resId}");
            $this->recordPayment($db, $resId, 'damage_hold', $summary['damage_deposit'], $holdRes);
            $db->table('deposits')->insert([
                'reservation_id'  => $resId, 'kind' => 'damage', 'amount' => $summary['damage_deposit'],
                'square_ref'      => $holdRes['payment_id'] ?? null, 'status' => 'held', 'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        $resModel->update($resId, ['status' => 'confirmed']);
        (new ProfitSplit())->allocateReservation($resId);

        $db->transComplete();
        if ($db->transStatus() === false) {
            return redirect()->to('/checkout')->with('error', 'Booking failed — please retry.');
        }

        $this->sendConfirmation($resId);
        $cart->clear();
        session()->remove(['wtr_contract_acceptance_id', 'wtr_waiver']);
        return redirect()->to("/checkout/confirm/{$resId}");
    }

    public function confirm(int $id)
    {
        $res = (new ReservationModel())->find($id);
        if (! $res || (int) $res['user_id'] !== (int) auth()->id()) {
            return redirect()->to('/account');
        }
        return view('checkout/confirm', [
            'title' => 'Reservation Confirmed',
            'res'   => $res,
            'items' => (new ReservationModel())->items($id),
            'square'=> (new Square())->isLive(),
        ]);
    }

    private function recordPayment($db, int $resId, string $type, float $amount, array $res): void
    {
        $db->table('payments')->insert([
            'reservation_id'    => $resId,
            'square_payment_id' => $res['payment_id'] ?? null,
            'type'              => $type,
            'amount'            => $amount,
            'status'            => ($res['simulated'] ?? false) ? 'simulated' : (($res['ok'] ?? false) ? 'completed' : 'failed'),
            'note'              => ($res['simulated'] ?? false) ? 'Square not configured — recorded only' : null,
            'created_at'        => date('Y-m-d H:i:s'),
        ]);
    }

    private function sendConfirmation(int $resId): void
    {
        $res  = (new ReservationModel())->find($resId);
        $user = auth()->user();
        if (! $user) {
            return;
        }
        $body = view('emails/confirmation', [
            'res'   => $res,
            'items' => (new ReservationModel())->items($resId),
        ]);
        helper('wtr');
        wtr_send_email($user->email, 'Your Weekend Tool Rentals reservation #' . $resId, $body, 'confirmation', $resId);
        // Notify the owner of every new reservation (esp. pre-Square, where the
        // deposit is arranged manually).
        $adminBody = '<p>New reservation <b>#' . $resId . '</b> from ' . esc($user->email) . '.</p>'
            . '<p>Dates: ' . esc($res['start_date']) . ' → ' . esc($res['end_date'])
            . '<br>Rental total: ' . wtr_money($res['grand_total'])
            . '<br>Paid online: ' . wtr_money($res['amount_paid'])
            . '<br>Balance due at pickup: ' . wtr_money($res['balance_due']) . '</p>'
            . ((new Square())->isLive() ? '' : '<p><b>Payment is record-only (Square not live) — contact the customer to arrange the deposit.</b></p>');
        wtr_send_email(config('Wtr')->businessEmail, 'New WTR reservation #' . $resId, $adminBody, 'owner_notify', $resId);
    }
}
