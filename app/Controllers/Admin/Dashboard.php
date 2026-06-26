<?php

namespace App\Controllers\Admin;

class Dashboard extends BaseAdmin
{
    public function index()
    {
        if ($r = $this->guard()) {
            return $r;
        }
        $db  = db_connect();
        $now = date('Y-m-d');
        $mtd = date('Y-m-01');

        $openLeads = (int) $db->table('clients')
            ->where('status', 'lead')->countAllResults();

        $upcomingEvents = (int) $db->table('events')
            ->where('event_date >=', $now)
            ->whereIn('status', ['tentative', 'confirmed'])
            ->countAllResults();

        $unpaidInvoices = (float) ($db->table('invoices')
            ->selectSum('balance_due')
            ->whereIn('status', ['sent', 'deposit_paid'])
            ->get()->getRowArray()['balance_due'] ?? 0);

        $mtdIncome = (float) ($db->table('payments')
            ->selectSum('amount')
            ->where('paid_at >=', $mtd)
            ->where('status', 'completed')
            ->get()->getRowArray()['amount'] ?? 0);

        $mtdExpenses = (float) ($db->table('expenses')
            ->selectSum('amount')
            ->where('date >=', $mtd)
            ->get()->getRowArray()['amount'] ?? 0);

        return view('admin/dashboard', [
            'title'          => 'Dashboard',
            'openLeads'      => $openLeads,
            'upcomingEvents' => $upcomingEvents,
            'unpaidInvoices' => $unpaidInvoices,
            'mtdIncome'      => $mtdIncome,
            'mtdExpenses'    => $mtdExpenses,
        ]);
    }
}
