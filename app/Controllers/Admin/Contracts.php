<?php

namespace App\Controllers\Admin;

class Contracts extends BaseAdmin
{
    public function index()
    {
        if ($r = $this->guard()) { return $r; }
        $db = db_connect();
        $contracts = $db->table('contracts c')
            ->select('c.*, q.id AS quote_id, cl.name AS client_name')
            ->join('quotes q', 'q.id = c.quote_id', 'left')
            ->join('clients cl', 'cl.id = q.client_id', 'left')
            ->orderBy('c.created_at', 'DESC')
            ->get()->getResultArray();
        return view('admin/contracts/index', ['title' => 'Contracts', 'contracts' => $contracts]);
    }

    public function show(int $id)
    {
        if ($r = $this->guard()) { return $r; }
        $contract = db_connect()->table('contracts')->where('id', $id)->get()->getRowArray();
        if (! $contract) {
            return redirect()->to('/admin/contracts')->with('error', 'Contract not found.');
        }
        return view('admin/contracts/show', ['title' => 'Contract #' . $id, 'contract' => $contract]);
    }

    public function sign(int $id)
    {
        if ($r = $this->guard()) { return $r; }
        // TODO: record e-signature (typed/drawn) — reuse WTR SignaturePad pattern
        return redirect()->back()->with('msg', 'Contract signed.');
    }
}
