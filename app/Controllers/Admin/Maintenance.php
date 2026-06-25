<?php

namespace App\Controllers\Admin;

use App\Models\BlackoutModel;
use App\Models\EquipmentModel;

class Maintenance extends BaseAdmin
{
    public function index()
    {
        if ($r = $this->guard()) {
            return $r;
        }
        $db = db_connect();
        return view('admin/maintenance', [
            'title'     => 'Maintenance',
            'log'       => $db->table('maintenance_log m')
                ->select('m.*, e.name AS equipment_name')
                ->join('equipment e', 'e.id = m.equipment_id', 'left')
                ->orderBy('m.start_date', 'DESC')->get()->getResultArray(),
            'equipment' => (new EquipmentModel())->orderBy('name')->findAll(),
        ]);
    }

    /** Log maintenance → writes a maintenance blackout so the item is unbookable. */
    public function log()
    {
        if ($r = $this->guard()) {
            return $r;
        }
        $eqId  = (int) $this->request->getPost('equipment_id');
        $start = (string) $this->request->getPost('start_date');
        $end   = (string) $this->request->getPost('end_date') ?: $start;
        $qty   = max(1, (int) $this->request->getPost('qty'));

        $blackoutId = (new BlackoutModel())->insert([
            'equipment_id' => $eqId, 'qty' => $qty, 'start_date' => $start, 'end_date' => $end,
            'reason' => 'maintenance', 'reservation_id' => null,
        ], true);

        $db = db_connect();
        $db->table('maintenance_log')->insert([
            'equipment_id' => $eqId,
            'kind'         => (string) $this->request->getPost('kind'),
            'start_date'   => $start,
            'end_date'     => $end,
            'cost'         => (float) $this->request->getPost('cost'),
            'blackout_id'  => $blackoutId,
            'notes'        => (string) $this->request->getPost('notes'),
            'created_at'   => date('Y-m-d H:i:s'),
        ]);
        // Maintenance cost feeds per-item P&L.
        if ((float) $this->request->getPost('cost') > 0) {
            $db->table('equipment_costs')->insert([
                'equipment_id' => $eqId, 'kind' => 'maintenance', 'amount' => (float) $this->request->getPost('cost'),
                'cost_date' => $start, 'notes' => (string) $this->request->getPost('kind'), 'created_at' => date('Y-m-d H:i:s'),
            ]);
        }
        return redirect()->to('/admin/maintenance')->with('msg', 'Maintenance logged — item blocked for those dates.');
    }

    /** Close maintenance early → clear the blackout from now forward. */
    public function close($id)
    {
        if ($r = $this->guard()) {
            return $r;
        }
        $db = db_connect();
        $m = $db->table('maintenance_log')->where('id', (int) $id)->get()->getRowArray();
        if ($m && ! empty($m['blackout_id'])) {
            (new BlackoutModel())->delete((int) $m['blackout_id']);
            $db->table('maintenance_log')->where('id', (int) $id)->update(['end_date' => date('Y-m-d')]);
        }
        return redirect()->to('/admin/maintenance')->with('msg', 'Maintenance closed — item available again.');
    }
}
