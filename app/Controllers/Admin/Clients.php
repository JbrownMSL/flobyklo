<?php

namespace App\Controllers\Admin;

class Clients extends BaseAdmin
{
    public function index()
    {
        if ($r = $this->guard()) { return $r; }
        $db = db_connect();
        $clients = $db->table('clients')->orderBy('created_at', 'DESC')->get()->getResultArray();
        return view('admin/clients/index', ['title' => 'Clients', 'clients' => $clients]);
    }

    public function form($id = null)
    {
        if ($r = $this->guard()) { return $r; }
        $client = $id ? db_connect()->table('clients')->where('id', $id)->get()->getRowArray() : null;
        return view('admin/clients/form', ['title' => $client ? 'Edit Client' : 'New Client', 'client' => $client]);
    }

    public function save()
    {
        if ($r = $this->guard()) { return $r; }
        // TODO: implement save
        return redirect()->to('/admin/clients')->with('msg', 'Saved.');
    }

    public function setStatus(int $id)
    {
        if ($r = $this->guard()) { return $r; }
        $status = $this->request->getPost('status');
        db_connect()->table('clients')->where('id', $id)->update(['status' => $status]);
        return redirect()->back()->with('msg', 'Status updated.');
    }
}
