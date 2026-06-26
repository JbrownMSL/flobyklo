<?php

namespace App\Controllers\Admin;

class Events extends BaseAdmin
{
    public function index()
    {
        if ($r = $this->guard()) { return $r; }
        $db = db_connect();
        $events = $db->table('events e')
            ->select('e.*, c.name AS client_name')
            ->join('clients c', 'c.id = e.client_id', 'left')
            ->orderBy('e.event_date', 'ASC')
            ->get()->getResultArray();
        return view('admin/events/index', ['title' => 'Events', 'events' => $events]);
    }

    public function form($id = null)
    {
        if ($r = $this->guard()) { return $r; }
        $event   = $id ? db_connect()->table('events')->where('id', $id)->get()->getRowArray() : null;
        $clients = db_connect()->table('clients')->orderBy('name')->get()->getResultArray();
        return view('admin/events/form', [
            'title'   => $event ? 'Edit Event' : 'New Event',
            'event'   => $event,
            'clients' => $clients,
        ]);
    }

    public function save()
    {
        if ($r = $this->guard()) { return $r; }
        // TODO: implement save
        return redirect()->to('/admin/events')->with('msg', 'Saved.');
    }
}
