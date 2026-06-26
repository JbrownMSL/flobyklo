<?php

namespace App\Controllers\Admin;

class Reports extends BaseAdmin
{
    // -----------------------------------------------------------------------
    // Hub — quick YTD KPIs + links to sub-reports
    // -----------------------------------------------------------------------
    public function index()
    {
        if ($r = $this->guard()) {
            return $r;
        }

        $db  = db_connect();
        $ytd = date('Y-01-01');
        $now = date('Y-m-d');

        $ytdIncome = (float) ($db->query(
            "SELECT COALESCE(SUM(amount),0) AS n FROM payments
              WHERE status='completed' AND paid_at >= ? AND paid_at <= ?",
            [$ytd . ' 00:00:00', $now . ' 23:59:59']
        )->getRowArray()['n'] ?? 0);

        $ytdExpenses = (float) ($db->query(
            "SELECT COALESCE(SUM(amount),0) AS n FROM expenses WHERE date >= ? AND date <= ?",
            [$ytd, $now]
        )->getRowArray()['n'] ?? 0);

        $openLeads = (int) ($db->query(
            "SELECT COUNT(*) AS n FROM clients WHERE status IN ('lead','consult')"
        )->getRowArray()['n'] ?? 0);

        $upcomingEvents = (int) ($db->query(
            "SELECT COUNT(*) AS n FROM events WHERE event_date >= ?", [$now]
        )->getRowArray()['n'] ?? 0);

        return view('admin/reports', [
            'title'          => 'Reports',
            'ytdIncome'      => $ytdIncome,
            'ytdExpenses'    => $ytdExpenses,
            'ytdNet'         => $ytdIncome - $ytdExpenses,
            'openLeads'      => $openLeads,
            'upcomingEvents' => $upcomingEvents,
            'year'           => date('Y'),
        ]);
    }

    // -----------------------------------------------------------------------
    // Monthly income vs. expenses P&L
    // -----------------------------------------------------------------------
    public function pnl()
    {
        if ($r = $this->guard()) {
            return $r;
        }

        $from = $this->request->getGet('from') ?: date('Y-01-01');
        $to   = $this->request->getGet('to')   ?: date('Y-m-d');
        $db   = db_connect();

        $incomeRows = $db->query(
            "SELECT DATE_FORMAT(paid_at,'%Y-%m') AS month, SUM(amount) AS income
               FROM payments
              WHERE status = 'completed'
                AND paid_at >= ? AND paid_at <= ?
              GROUP BY month ORDER BY month",
            [$from . ' 00:00:00', $to . ' 23:59:59']
        )->getResultArray();

        $expenseRows = $db->query(
            "SELECT DATE_FORMAT(date,'%Y-%m') AS month, SUM(amount) AS expenses
               FROM expenses
              WHERE date >= ? AND date <= ?
              GROUP BY month ORDER BY month",
            [$from, $to]
        )->getResultArray();

        // Expense breakdown by category for the period
        $byCategory = $db->query(
            "SELECT category, SUM(amount) AS total
               FROM expenses
              WHERE date >= ? AND date <= ?
              GROUP BY category ORDER BY total DESC",
            [$from, $to]
        )->getResultArray();

        // Merge income + expenses into a keyed-by-month map
        $months = [];
        foreach ($incomeRows as $row) {
            $months[$row['month']]['income'] = (float) $row['income'];
        }
        foreach ($expenseRows as $row) {
            $months[$row['month']]['expenses'] = (float) $row['expenses'];
        }
        ksort($months);

        $rows = [];
        $totalIncome   = 0;
        $totalExpenses = 0;
        foreach ($months as $month => $vals) {
            $income   = $vals['income']   ?? 0;
            $expenses = $vals['expenses'] ?? 0;
            $net      = $income - $expenses;
            $margin   = $income > 0 ? round($net / $income * 100, 1) : null;
            $rows[]   = compact('month', 'income', 'expenses', 'net', 'margin');
            $totalIncome   += $income;
            $totalExpenses += $expenses;
        }
        $totalNet    = $totalIncome - $totalExpenses;
        $totalMargin = $totalIncome > 0 ? round($totalNet / $totalIncome * 100, 1) : null;

        return view('admin/report_pnl', [
            'title'         => 'P&L Report',
            'rows'          => $rows,
            'byCategory'    => $byCategory,
            'totalIncome'   => $totalIncome,
            'totalExpenses' => $totalExpenses,
            'totalNet'      => $totalNet,
            'totalMargin'   => $totalMargin,
            'from'          => $from,
            'to'            => $to,
        ]);
    }

    // -----------------------------------------------------------------------
    // Per-event margin report (all events, default current year)
    // -----------------------------------------------------------------------
    public function mtd()
    {
        if ($r = $this->guard()) {
            return $r;
        }

        $year = (int) ($this->request->getGet('year') ?: date('Y'));
        $from = $year . '-01-01';
        $to   = $year . '-12-31';
        $db   = db_connect();

        // Revenue per event = sum of accepted-quote line_total (subquery avoids cross-join fanout)
        // COGS per event = sum of expenses with event_id set (subquery)
        $eventMargins = $db->query(
            "SELECT
                e.id,
                e.event_date,
                e.venue,
                e.type,
                e.status AS event_status,
                c.name  AS client_name,
                COALESCE(rev.revenue, 0) AS revenue,
                COALESCE(exp.cogs,    0) AS cogs,
                COALESCE(rev.revenue, 0) - COALESCE(exp.cogs, 0) AS margin
             FROM events e
             JOIN clients c ON c.id = e.client_id
             LEFT JOIN (
                 SELECT q.event_id, SUM(qi.line_total) AS revenue
                   FROM quotes q
                   JOIN quote_items qi ON qi.quote_id = q.id
                  WHERE q.status = 'accepted'
                  GROUP BY q.event_id
             ) AS rev ON rev.event_id = e.id
             LEFT JOIN (
                 SELECT event_id, SUM(amount) AS cogs
                   FROM expenses
                  WHERE event_id IS NOT NULL
                  GROUP BY event_id
             ) AS exp ON exp.event_id = e.id
            WHERE e.event_date >= ? AND e.event_date <= ?
            ORDER BY e.event_date DESC",
            [$from, $to]
        )->getResultArray();

        // Add margin_pct to each row
        foreach ($eventMargins as &$row) {
            $rev = (float) $row['revenue'];
            $row['margin']     = (float) $row['margin'];
            $row['margin_pct'] = $rev > 0 ? round($row['margin'] / $rev * 100, 1) : null;
        }
        unset($row);

        // MTD income (payments this month, for the quick KPI at top)
        $mtdStart   = date('Y-m-01');
        $mtdIncome  = (float) ($db->query(
            "SELECT COALESCE(SUM(amount),0) AS n FROM payments
              WHERE status='completed' AND paid_at >= ?",
            [$mtdStart . ' 00:00:00']
        )->getRowArray()['n'] ?? 0);
        $mtdExpense = (float) ($db->query(
            "SELECT COALESCE(SUM(amount),0) AS n FROM expenses WHERE date >= ?",
            [$mtdStart]
        )->getRowArray()['n'] ?? 0);

        // Overhead expenses not tied to any event
        $overhead = (float) ($db->query(
            "SELECT COALESCE(SUM(amount),0) AS n FROM expenses
              WHERE event_id IS NULL AND date >= ? AND date <= ?",
            [$from, $to]
        )->getRowArray()['n'] ?? 0);

        $years = range(date('Y'), max(2024, date('Y') - 3), -1);

        return view('admin/report_mtd', [
            'title'        => 'Event Margins',
            'eventMargins' => $eventMargins,
            'mtdIncome'    => $mtdIncome,
            'mtdExpense'   => $mtdExpense,
            'overhead'     => $overhead,
            'year'         => $year,
            'years'        => $years,
            'month'        => date('F Y'),
        ]);
    }
}
