<?php

namespace App\Controllers\Admin;

use App\Models\ReservationModel;
use App\Services\Square;

class Reservations extends BaseAdmin
{
    public function index()
    {
        if ($r = $this->guard()) {
            return $r;
        }
        $status = $this->request->getGet('status');
        $rm = new ReservationModel();
        $q = $rm->orderBy('start_date', 'DESC');
        if ($status) {
            $q->where('status', $status);
        }
        return view('admin/reservations_list', [
            'title'        => 'Reservations',
            'reservations' => $q->findAll(),
            'status'       => $status,
        ]);
    }

    public function show($id)
    {
        if ($r = $this->guard()) {
            return $r;
        }
        $rm = new ReservationModel();
        $res = $rm->find((int) $id);
        if (! $res) {
            return redirect()->to('/admin/reservations')->with('error', 'Not found.');
        }
        $db = db_connect();
        return view('admin/reservation', [
            'title'    => 'Reservation #' . $id,
            'res'      => $res,
            'items'    => $rm->items((int) $id),
            'payments' => $db->table('payments')->where('reservation_id', (int) $id)->orderBy('id')->get()->getResultArray(),
            'deposits' => $db->table('deposits')->where('reservation_id', (int) $id)->get()->getResultArray(),
            'damage'   => $db->table('damage_reports')->where('reservation_id', (int) $id)->get()->getResultArray(),
        ]);
    }

    public function markPickedUp($id)
    {
        if ($r = $this->guard()) {
            return $r;
        }
        $id = (int) $id;
        $rm = new ReservationModel();
        $res = $rm->find($id);
        $db = db_connect();
        // Collect the remaining 50% balance at pickup (in person / on the
        // terminal — recorded here). Card-on-file auto-charge is a future add.
        $balance = (float) ($res['balance_due'] ?? 0);
        if ($balance > 0.005) {
            $db->table('payments')->insert([
                'reservation_id' => $id, 'type' => 'rental_balance', 'amount' => $balance,
                'status' => 'collected', 'note' => 'Balance collected at pickup',
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }
        $rm->update($id, ['status' => 'picked_up', 'amount_paid' => (float) $res['amount_paid'] + $balance, 'balance_due' => 0]);
        return redirect()->back()->with('msg', $balance > 0 ? ('Picked up — balance ' . wtr_money($balance) . ' collected.') : 'Marked picked up.');
    }

    /** Clean return → release the damage-deposit hold. */
    public function markReturned($id)
    {
        if ($r = $this->guard()) {
            return $r;
        }
        $db = db_connect();
        $dep = $db->table('deposits')->where('reservation_id', (int) $id)->where('status', 'held')->get()->getRowArray();
        if ($dep && ! empty($dep['square_ref'])) {
            (new Square())->release((string) $dep['square_ref']);
            $db->table('deposits')->where('id', $dep['id'])->update(['status' => 'released']);
        }
        (new ReservationModel())->update((int) $id, ['status' => 'returned']);
        return redirect()->back()->with('msg', 'Returned — deposit hold released.');
    }

    /** Log damage → capture (up to) the assessed cost from the deposit hold. */
    public function logDamage($id)
    {
        if ($r = $this->guard()) {
            return $r;
        }
        $id   = (int) $id;
        $cost = (float) $this->request->getPost('assessed_cost');
        $desc = (string) $this->request->getPost('description');
        $db = db_connect();
        $eqRow = $db->table('reservation_items')->where('reservation_id', $id)->get()->getRowArray();

        $db->table('damage_reports')->insert([
            'reservation_id' => $id,
            'equipment_id'   => (int) ($eqRow['equipment_id'] ?? 0),
            'description'    => $desc,
            'assessed_cost'  => $cost,
            'deposit_captured' => 0,
            'created_at'     => date('Y-m-d H:i:s'),
        ]);
        $drId = $db->insertID();

        $dep = $db->table('deposits')->where('reservation_id', $id)->where('status', 'held')->get()->getRowArray();
        if ($dep && ! empty($dep['square_ref']) && $cost > 0) {
            // Square auth-hold capture is all-or-nothing on /complete; for a partial
            // capture you complete then refund the difference. Record the intended capture.
            $sq = new Square();
            $sq->capture((string) $dep['square_ref']);
            $captured = min($cost, (float) $dep['amount']);
            if ($captured < (float) $dep['amount']) {
                $sq->refund((string) $dep['square_ref'], (float) $dep['amount'] - $captured, "wtr-depdiff-{$id}");
            }
            $db->table('deposits')->where('id', $dep['id'])->update(['status' => 'captured', 'captured_amount' => $captured]);
            $db->table('damage_reports')->where('id', $drId)->update(['deposit_captured' => $captured]);
            $db->table('payments')->insert([
                'reservation_id' => $id, 'type' => 'damage_capture', 'amount' => $captured,
                'square_payment_id' => $dep['square_ref'], 'status' => $sq->isLive() ? 'completed' : 'simulated',
                'note' => 'Damage capture', 'created_at' => date('Y-m-d H:i:s'),
            ]);
        }
        (new ReservationModel())->update($id, ['status' => 'returned']);
        return redirect()->back()->with('msg', 'Damage logged' . ($cost > 0 ? ' and deposit captured.' : '.'));
    }

    /** Admin-initiated manual refund of the rental payment. */
    public function refund($id)
    {
        if ($r = $this->guard()) {
            return $r;
        }
        $id = (int) $id;
        $amt = (float) $this->request->getPost('amount');
        $db = db_connect();
        $pmt = $db->table('payments')->where('reservation_id', $id)->where('type', 'rental_deposit')->get()->getRowArray();
        if ($pmt && $amt > 0) {
            $r2 = (new Square())->refund((string) $pmt['square_payment_id'], $amt, "wtr-adminrefund-{$id}-" . time());
            $db->table('payments')->insert([
                'reservation_id' => $id, 'type' => 'refund', 'amount' => $amt,
                'square_payment_id' => $r2['refund_id'] ?? null,
                'status' => ($r2['simulated'] ?? false) ? 'simulated' : (($r2['ok'] ?? false) ? 'completed' : 'failed'),
                'note' => 'Admin refund', 'created_at' => date('Y-m-d H:i:s'),
            ]);
        }
        return redirect()->back()->with('msg', 'Refund recorded.');
    }
}
