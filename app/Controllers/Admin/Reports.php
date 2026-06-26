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

    /** Monthly income vs. expenses P&L. */
    public function pnl()
    {
        if ($r = $this->guard()) {
            return $r;
        }
        $from = $this->request->getGet('from') ?: date('Y-01-01');
        $to   = $this->request->getGet('to') ?: date('Y-m-d');
        $db   = db_connect();

        $income = $db->query(
            "SELECT DATE_FORMAT(paid_at,'%Y-%m') AS month, SUM(amount) AS income
               FROM payments
              WHERE status = 'completed' AND paid_at >= ? AND paid_at <= ?
              GROUP BY month ORDER BY month",
            [$from, $to]
        )->getResultArray();

        $expenses = $db->query(
            "SELECT DATE_FORMAT(date,'%Y-%m') AS month, SUM(amount) AS expenses
               FROM expenses
              WHERE date >= ? AND date <= ?
              GROUP BY month ORDER BY month",
            [$from, $to]
        )->getResultArray();

        return view('admin/report_pnl', [
            'title'    => 'P&L Report',
            'income'   => $income,
            'expenses' => $expenses,
            'from'     => $from,
            'to'       => $to,
        ]);
    }

    /** MTD summary (current month income / expense / per-event margin). */
    public function mtd()
    {
        if ($r = $this->guard()) {
            return $r;
        }
        $db  = db_connect();
        $mtd = date('Y-m-01');

        $income   = (float) ($db->table('payments')->selectSum('amount')
            ->where('paid_at >=', $mtd)->where('status', 'completed')
            ->get()->getRowArray()['amount'] ?? 0);
        $expenses = (float) ($db->table('expenses')->selectSum('amount')
            ->where('date >=', $mtd)->get()->getRowArray()['amount'] ?? 0);

        $eventMargins = $db->query(
            "SELECT e.id, e.event_date, c.name AS client_name,
                    COALESCE(SUM(qi.line_total),0) AS revenue,
                    COALESCE(SUM(ex.amount),0) AS cogs,
                    COALESCE(SUM(qi.line_total),0) - COALESCE(SUM(ex.amount),0) AS margin
               FROM events e
               JOIN clients c ON c.id = e.client_id
               LEFT JOIN quotes q ON q.event_id = e.id AND q.status = 'accepted'
               LEFT JOIN quote_items qi ON qi.quote_id = q.id
               LEFT JOIN expenses ex ON ex.event_id = e.id
              WHERE e.event_date >= ?
              GROUP BY e.id
              ORDER BY e.event_date",
            [$mtd]
        )->getResultArray();

        return view('admin/report_mtd', [
            'title'        => 'MTD Summary',
            'income'       => $income,
            'expenses'     => $expenses,
            'eventMargins' => $eventMargins,
            'month'        => date('F Y'),
        ]);
    }
}
