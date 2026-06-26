<?php

namespace App\Controllers\Admin;

use App\Services\Square;

class Invoices extends BaseAdmin
{
    public function index()
    {
        if ($r = $this->guard()) { return $r; }
        $db = db_connect();
        $status = $this->request->getGet('status');
        $b = $db->table('invoices i')
            ->select('i.*, c.name AS client_name')
            ->join('clients c', 'c.id = i.client_id', 'left');
        if ($status) { $b->where('i.status', $status); }
        $invoices = $b->orderBy('i.created_at', 'DESC')->get()->getResultArray();
        return view('admin/invoices/index', ['title' => 'Invoices', 'invoices' => $invoices, 'status' => $status]);
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
        $db  = db_connect();
        $inv = $db->table('invoices')->where('id', $id)->get()->getRowArray();
        if (! $inv) { return redirect()->to('/admin/invoices')->with('error', 'Invoice not found.'); }
        $amount = (float) $this->request->getPost('amount');
        if ($amount <= 0) { return redirect()->back()->with('error', 'Enter a payment amount greater than 0.'); }
        $method = $this->request->getPost('method') ?: 'cash';
        $kind   = $this->request->getPost('kind') ?: 'balance';
        $squareRef = null; $status = 'completed';
        if ($method === 'square') {
            $res = (new \App\Services\Square())->charge((string) $this->request->getPost('nonce'), $amount, 'fbk-inv-' . $id . '-' . time());
            $status    = $res['status'] ?? 'simulated';
            $squareRef = $res['id'] ?? ('SIM-' . time());
        }
        $db->table('payments')->insert([
            'invoice_id' => $id, 'amount' => $amount, 'method' => $method, 'kind' => $kind,
            'square_ref' => $squareRef, 'status' => $status, 'paid_at' => date('Y-m-d H:i:s'),
        ]);
        $paid = (float) ($db->table('payments')->selectSum('amount')->where('invoice_id', $id)->get()->getRow()->amount ?? 0);
        $bal  = (float) $inv['total'] - $paid;
        $newStatus = $bal <= 0.001 ? 'paid' : ($paid > 0 ? 'deposit_paid' : $inv['status']);
        $db->table('invoices')->where('id', $id)->update(['amount_paid' => $paid, 'balance_due' => $bal, 'status' => $newStatus]);
        return redirect()->to('/admin/invoices/' . $id)->with('msg', 'Payment of $' . number_format($amount, 2) . ' recorded.');
    }

    public function pdf(int $id)
    {
        if ($r = $this->guard()) { return $r; }
        // TODO: generate PDF via mpdf
        return redirect()->back()->with('error', 'PDF generation not yet implemented.');
    }
}
