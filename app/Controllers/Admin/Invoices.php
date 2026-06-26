<?php

namespace App\Controllers\Admin;

use App\Services\Square;

class Invoices extends BaseAdmin
{
    public function index()
    {
        if ($r = $this->guard()) { return $r; }
        $db = db_connect();
        $invoices = $db->table('invoices i')
            ->select('i.*, c.name AS client_name')
            ->join('clients c', 'c.id = i.client_id', 'left')
            ->orderBy('i.created_at', 'DESC')
            ->get()->getResultArray();
        return view('admin/invoices/index', ['title' => 'Invoices', 'invoices' => $invoices]);
    }

    public function show(int $id)
    {
        if ($r = $this->guard()) { return $r; }
        $db      = db_connect();
        $invoice  = $db->table('invoices')->where('id', $id)->get()->getRowArray();
        if (! $invoice) {
            return redirect()->to('/admin/invoices')->with('error', 'Invoice not found.');
        }
        $payments = $db->table('payments')->where('invoice_id', $id)->orderBy('paid_at', 'DESC')->get()->getResultArray();
        return view('admin/invoices/show', [
            'title'      => 'Invoice #' . ($invoice['number'] ?: $id),
            'invoice'    => $invoice,
            'payments'   => $payments,
            'squareLive' => (new Square())->isLive(),
        ]);
    }

    public function recordPayment(int $id)
    {
        if ($r = $this->guard()) { return $r; }
        // TODO: record manual payment (or Square payment token)
        return redirect()->back()->with('msg', 'Payment recorded.');
    }

    public function pdf(int $id)
    {
        if ($r = $this->guard()) { return $r; }
        // TODO: generate PDF via mpdf
        return redirect()->back()->with('error', 'PDF generation not yet implemented.');
    }
}
