<?php

namespace App\Controllers\Admin;

class Reports extends BaseAdmin
{
    public function index()
    {
        if ($r = $this->guard()) {
            return $r;
        }
        return view('admin/reports', ['title' => 'Reports']);
    }

    /** Tax collected by class for remittance (decision: tax-remittance report). */
    public function tax()
    {
        if ($r = $this->guard()) {
            return $r;
        }
        $from = $this->request->getGet('from') ?: date('Y-m-01');
        $to   = $this->request->getGet('to') ?: date('Y-m-d');
        $db = db_connect();
        $rows = $db->table('reservation_items ri')
            ->select('ri.tax_class, SUM(ri.line_subtotal) AS taxable, SUM(ri.tax_amount) AS tax')
            ->join('reservations r', 'r.id = ri.reservation_id')
            ->whereIn('r.status', ['confirmed', 'picked_up', 'returned'])
            ->where('r.start_date >=', $from)->where('r.start_date <=', $to)
            ->groupBy('ri.tax_class')->get()->getResultArray();
        return view('admin/report_tax', ['title' => 'Tax Collected', 'rows' => $rows, 'from' => $from, 'to' => $to]);
    }

    /** Per-item utilization (booked days / available days) + revenue. */
    public function utilization()
    {
        if ($r = $this->guard()) {
            return $r;
        }
        $db = db_connect();
        $rows = $db->table('reservation_items ri')
            ->select('e.id, e.name, COUNT(ri.id) AS rentals, SUM(ri.days * ri.qty) AS unit_days, SUM(ri.line_subtotal) AS revenue')
            ->join('equipment e', 'e.id = ri.equipment_id')
            ->join('reservations r', 'r.id = ri.reservation_id')
            ->whereIn('r.status', ['confirmed', 'picked_up', 'returned'])
            ->groupBy('e.id')->orderBy('revenue', 'DESC')->get()->getResultArray();
        return view('admin/report_utilization', ['title' => 'Utilization', 'rows' => $rows]);
    }

    /** Per-item P&L: revenue − costs, and owner-split breakdown. */
    public function pnl()
    {
        if ($r = $this->guard()) {
            return $r;
        }
        $db = db_connect();
        $items = $db->table('equipment')->orderBy('name')->get()->getResultArray();
        $out = [];
        foreach ($items as $e) {
            $rev = (float) ($db->table('reservation_items ri')->selectSum('line_subtotal')
                ->join('reservations r', 'r.id = ri.reservation_id')
                ->where('ri.equipment_id', $e['id'])->whereIn('r.status', ['confirmed', 'picked_up', 'returned'])
                ->get()->getRowArray()['line_subtotal'] ?? 0);
            $cost = (float) ($db->table('equipment_costs')->selectSum('amount')
                ->where('equipment_id', $e['id'])->get()->getRowArray()['amount'] ?? 0);
            $ownerCut = (float) ($db->table('income_allocations')->selectSum('amount')
                ->where('equipment_id', $e['id'])->where('owner_entity !=', 'WTR')->get()->getRowArray()['amount'] ?? 0);
            $out[] = ['name' => $e['name'], 'revenue' => $rev, 'costs' => $cost, 'owner_cut' => $ownerCut, 'net' => round($rev - $cost - $ownerCut, 2)];
        }
        return view('admin/report_pnl', ['title' => 'Per-item P&L', 'rows' => $out]);
    }

    /** Partner statement: income allocated to a given owner entity. */
    public function ownerStatement($id)
    {
        if ($r = $this->guard()) {
            return $r;
        }
        // $id here is unused placeholder; we key by entity name via GET for flexibility.
        $entity = $this->request->getGet('entity') ?: 'WTR';
        $db = db_connect();
        $rows = $db->table('income_allocations ia')
            ->select('ia.*, e.name AS equipment_name, r.start_date, r.end_date')
            ->join('equipment e', 'e.id = ia.equipment_id', 'left')
            ->join('reservation_items ri', 'ri.id = ia.reservation_item_id', 'left')
            ->join('reservations r', 'r.id = ri.reservation_id', 'left')
            ->where('ia.owner_entity', $entity)->orderBy('r.start_date', 'DESC')->get()->getResultArray();
        $entities = array_column($db->table('equipment_ownership')->select('owner_entity')->distinct()->get()->getResultArray(), 'owner_entity');
        return view('admin/report_owner', ['title' => 'Owner Statement', 'entity' => $entity, 'rows' => $rows, 'entities' => $entities]);
    }
}
