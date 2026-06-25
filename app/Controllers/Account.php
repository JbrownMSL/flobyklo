<?php

namespace App\Controllers;

use App\Models\CustomerProfileModel;
use App\Models\ReservationModel;
use App\Services\Availability;
use App\Services\Square;

class Account extends BaseController
{
    public function index(): string
    {
        $uid = (int) auth()->id();
        $res = (new ReservationModel())->forUser($uid);
        return view('account/index', [
            'title'        => 'My Account',
            'reservations' => array_slice($res, 0, 5),
            'profile'      => (new CustomerProfileModel())->forUser($uid),
        ]);
    }

    public function reservations(): string
    {
        return view('account/reservations', [
            'title'        => 'My Reservations',
            'reservations' => (new ReservationModel())->forUser((int) auth()->id()),
        ]);
    }

    public function reservation(int $id): string
    {
        $rm = new ReservationModel();
        $res = $rm->find($id);
        if (! $res || (int) $res['user_id'] !== (int) auth()->id()) {
            return view('account/index', ['title' => 'My Account', 'reservations' => [], 'profile' => null]);
        }
        return view('account/reservation', [
            'title' => 'Reservation #' . $id,
            'res'   => $res,
            'items' => $rm->items($id),
            'canCancel' => in_array($res['status'], ['confirmed'], true) && strtotime($res['start_date']) > time(),
        ]);
    }

    /** Customer cancellation → tiered refund of the rental amount (decision #3). */
    public function cancel(int $id)
    {
        $rm  = new ReservationModel();
        $res = $rm->find($id);
        if (! $res || (int) $res['user_id'] !== (int) auth()->id()) {
            return redirect()->to('/account')->with('error', 'Not found.');
        }
        if ($res['status'] !== 'confirmed') {
            return redirect()->back()->with('error', 'This reservation can no longer be cancelled online.');
        }

        $hoursOut = (strtotime($res['start_date']) - time()) / 3600;
        $tiers = config('Wtr')->cancellationTiers;
        krsort($tiers);
        $pct = 0.25;
        foreach ($tiers as $threshold => $refundPct) {
            if ($hoursOut >= $threshold) { $pct = $refundPct; break; }
        }
        // Refund a % of what was actually collected (the 50% booking deposit).
        $refundAmt = round((float) $res['amount_paid'] * $pct, 2);

        $db = db_connect();
        $sq = new Square();

        // Refund the booking-deposit payment (partial per tier).
        $pmt = $db->table('payments')->where('reservation_id', $id)->where('type', 'rental_deposit')->get()->getRowArray();
        if ($pmt && $refundAmt > 0) {
            $r = $sq->refund((string) $pmt['square_payment_id'], $refundAmt, "wtr-refund-{$id}");
            $db->table('payments')->insert([
                'reservation_id' => $id, 'type' => 'refund', 'amount' => $refundAmt,
                'square_payment_id' => $r['refund_id'] ?? null,
                'status' => ($r['simulated'] ?? false) ? 'simulated' : (($r['ok'] ?? false) ? 'completed' : 'failed'),
                'note' => sprintf('Cancellation refund %d%% (%.1fh out)', (int) round($pct * 100), $hoursOut),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        // Release the damage-deposit hold.
        $dep = $db->table('deposits')->where('reservation_id', $id)->where('status', 'held')->get()->getRowArray();
        if ($dep && ! empty($dep['square_ref'])) {
            $sq->release((string) $dep['square_ref']);
            $db->table('deposits')->where('id', $dep['id'])->update(['status' => 'released']);
        }

        (new Availability())->release($id);
        $rm->update($id, ['status' => 'cancelled']);

        helper('wtr');
        return redirect()->to('/account/reservations')->with('msg',
            'Reservation cancelled. Refund of ' . wtr_money($refundAmt) . ' (' . (int) round($pct * 100) . '%) issued to your card.');
    }

    public function profile(): string
    {
        return view('account/profile', [
            'title'   => 'My Profile',
            'profile' => (new CustomerProfileModel())->forUser((int) auth()->id()),
        ]);
    }

    public function saveProfile()
    {
        $uid = (int) auth()->id();
        $pm  = new CustomerProfileModel();
        $data = [
            'user_id'   => $uid,
            'full_name' => trim((string) $this->request->getPost('full_name')),
            'phone'     => trim((string) $this->request->getPost('phone')),
            'address'   => trim((string) $this->request->getPost('address')),
            'city'      => trim((string) $this->request->getPost('city')),
            'state'     => strtoupper(trim((string) $this->request->getPost('state'))),
            'zip'       => trim((string) $this->request->getPost('zip')),
        ];
        $existing = $pm->forUser($uid);
        if ($existing) {
            $pm->update($existing['id'], $data);
        } else {
            $pm->insert($data);
        }
        return redirect()->to('/account')->with('msg', 'Profile saved.');
    }
}
