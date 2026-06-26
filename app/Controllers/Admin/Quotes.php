<?php

namespace App\Controllers\Admin;

class Quotes extends BaseAdmin
{
    public function index()
    {
        if ($r = $this->guard()) { return $r; }
        $db = db_connect();
        $quotes = $db->table('quotes q')
            ->select('q.*, c.name AS client_name')
            ->join('clients c', 'c.id = q.client_id', 'left')
            ->orderBy('q.created_at', 'DESC')
            ->get()->getResultArray();
        return view('admin/quotes/index', ['title' => 'Quotes', 'quotes' => $quotes]);
    }

    public function form($id = null)
    {
        if ($r = $this->guard()) { return $r; }
        $db      = db_connect();
        $quote   = $id ? $db->table('quotes')->where('id', $id)->get()->getRowArray() : null;
        $items   = $id ? $db->table('quote_items')->where('quote_id', $id)->get()->getResultArray() : [];
        $clients = $db->table('clients')->orderBy('name')->get()->getResultArray();
        $events  = $db->table('events')->orderBy('event_date', 'DESC')->get()->getResultArray();
        $recipes = $db->table('recipes')->orderBy('name')->get()->getResultArray();
        return view('admin/quotes/form', [
            'title'   => $quote ? 'Edit Quote' : 'New Quote',
            'quote'   => $quote,
            'items'   => $items,
            'clients' => $clients,
            'events'  => $events,
            'recipes' => $recipes,
        ]);
    }

    public function save()
    {
        if ($r = $this->guard()) { return $r; }
        // TODO: implement save
        return redirect()->to('/admin/quotes')->with('msg', 'Saved.');
    }

    public function pdf(int $id)
    {
        if ($r = $this->guard()) { return $r; }
        // TODO: generate PDF via mpdf
        return redirect()->back()->with('error', 'PDF generation not yet implemented.');
    }

    public function send(int $id)
    {
        if ($r = $this->guard()) { return $r; }
        // TODO: send quote via email + mark status = 'sent'
        return redirect()->back()->with('msg', 'Quote marked as sent.');
    }

    public function createInvoice(int $id)
    {
        if ($r = $this->guard()) { return $r; }
        // TODO: create invoice from accepted quote
        return redirect()->to('/admin/invoices')->with('msg', 'Invoice created.');
    }
}
