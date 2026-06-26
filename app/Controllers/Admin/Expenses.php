<?php

namespace App\Controllers\Admin;

class Expenses extends BaseAdmin
{
    public function index()
    {
        if ($r = $this->guard()) { return $r; }
        $db = db_connect();
        $expenses = $db->table('expenses e')
            ->select('e.*, ev.event_date')
            ->join('events ev', 'ev.id = e.event_id', 'left')
            ->orderBy('e.date', 'DESC')
            ->get()->getResultArray();
        return view('admin/expenses/index', ['title' => 'Expenses', 'expenses' => $expenses]);
    }

    public function form($id = null)
    {
        if ($r = $this->guard()) { return $r; }
        $db      = db_connect();
        $expense = $id ? $db->table('expenses')->where('id', $id)->get()->getRowArray() : null;
        $events  = $db->table('events')->orderBy('event_date', 'DESC')->get()->getResultArray();
        return view('admin/expenses/form', [
            'title'   => $expense ? 'Edit Expense' : 'New Expense',
            'expense' => $expense,
            'events'  => $events,
        ]);
    }

    public function save()
    {
        if ($r = $this->guard()) { return $r; }
        // TODO: implement save
        return redirect()->to('/admin/expenses')->with('msg', 'Saved.');
    }
}
