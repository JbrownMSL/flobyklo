<?php

namespace App\Controllers\Admin;

use App\Models\ReservationModel;

class Dashboard extends BaseAdmin
{
    public function index()
    {
        if ($r = $this->guard()) {
            return $r;
        }
        $rm = new ReservationModel();
        $db = db_connect();

        $maintDue = $db->table('unit_blackouts')
            ->where('reason', 'maintenance')->where('end_date >=', date('Y-m-d'))->countAllResults();
        $openDamage = $db->table('damage_reports')->countAllResults();

        return view('admin/dashboard', [
            'title'      => 'Admin Dashboard',
            'upcoming'   => $rm->upcoming(),
            'returnsDue' => $rm->returnsDue(),
            'overdue'    => $rm->overdue(),
            'maintDue'   => $maintDue,
            'openDamage' => $openDamage,
            'revenue30'  => (float) ($db->table('payments')
                ->selectSum('amount')->where('type', 'rental')->where('status !=', 'failed')
                ->where('created_at >=', date('Y-m-d', strtotime('-30 days')))->get()->getRowArray()['amount'] ?? 0),
        ]);
    }
}
