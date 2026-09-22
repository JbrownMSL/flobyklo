<?php

namespace App\Controllers\Admin;

use App\Models\ClientModel;

class Clients extends BaseAdmin
{
    private ClientModel $model;

    public function initController(
        \CodeIgniter\HTTP\RequestInterface $request,
        \CodeIgniter\HTTP\ResponseInterface $response,
        \Psr\Log\LoggerInterface $logger
    ): void {
        parent::initController($request, $response, $logger);
        $this->model = new ClientModel();
    }

    public function index(): \CodeIgniter\HTTP\ResponseInterface|string
    {
        if ($r = $this->guard()) {
            return $r;
        }

        $status = $this->request->getGet('status');
        $valid  = ClientModel::statusList();

        $builder = $this->model->orderBy('created_at', 'DESC');

        if ($status !== null && in_array($status, $valid, true)) {
            $builder->where('status', $status);
        }

        $clients = $builder->findAll();

        // Counts per status for filter tabs
        $db     = db_connect();
        $counts = [];
        foreach ($valid as $s) {
            $counts[$s] = (int) $db->table('clients')->where('status', $s)->countAllResults();
        }
        $counts['all'] = array_sum($counts);

        return view('admin/clients/index', [
            'title'   => 'Clients',
            'clients' => $clients,
            'current' => $status ?? 'all',
            'counts'  => $counts,
        ]);
    }

    public function form(int $id = 0): \CodeIgniter\HTTP\ResponseInterface|string
    {
        if ($r = $this->guard()) {
            return $r;
        }

        $client = $id ? $this->model->find($id) : null;
        if ($id && ! $client) {
            return redirect()->to('/admin/clients')->with('error', 'Client not found.');
        }

        $events = [];
        $quotes = [];
        if ($client) {
            $db     = db_connect();
            $events = $db->table('events')
                ->where('client_id', $id)
                ->orderBy('event_date', 'ASC')
                ->get()->getResultArray();

            $quotes = $db->table('quotes')
                ->where('client_id', $id)
                ->orderBy('created_at', 'DESC')
                ->get()->getResultArray();
        }

        $errors = session()->getFlashdata('errors') ?? [];

        return view('admin/clients/form', [
            'cash8300' => $id ? $this->cashLast12($id) : null,
            'title'   => $client ? 'Edit Client' : 'New Client',
            'client'  => $client,
            'events'  => $events,
            'quotes'  => $quotes,
            'sources' => ClientModel::sourceList(),
            'statuses'=> ClientModel::statusList(),
            'errors'  => $errors,
        ]);
    }

    public function save(): \CodeIgniter\HTTP\ResponseInterface
    {
        if ($r = $this->guard()) {
            return $r;
        }

        $id = (int) $this->request->getPost('id');

        $data = [
            'name'    => $this->request->getPost('name'),
            'email'   => $this->request->getPost('email') ?: null,
            'phone'   => $this->request->getPost('phone') ?: null,
            'address' => $this->request->getPost('address') ?: null,
            'source'  => $this->request->getPost('source'),
            'status'  => $this->request->getPost('status'),
            'notes'   => $this->request->getPost('notes') ?: null,
        ];

        if ($id) {
            $this->model->setValidationRule('id', 'permit_empty');
        }

        if (! $this->model->validate($data)) {
            session()->setFlashdata('errors', $this->model->errors());
            return redirect()->back()->withInput();
        }

        if ($id) {
            $this->model->update($id, $data);
            $back = "/admin/clients/{$id}";
            $msg  = 'Client updated.';
        } else {
            $this->model->insert($data);
            $newId = $this->model->getInsertID();
            $back  = "/admin/clients/{$newId}";
            $msg   = 'Client created.';
        }

        return redirect()->to($back)->with('msg', $msg);
    }

    public function setStatus(int $id): \CodeIgniter\HTTP\ResponseInterface
    {
        if ($r = $this->guard()) {
            return $r;
        }

        $status = $this->request->getPost('status');
        if (in_array($status, ClientModel::statusList(), true)) {
            $this->model->update($id, ['status' => $status]);
        }

        return redirect()->back()->with('msg', 'Status updated.');
    }

    /**
     * IRS Form 8300 (board #2947, Jason 2026-09-21: "if a client pays me over 10,000 ... a link off to
     * the side of that client so that I can print"). 8300 is CASH ONLY — currency (and, in some
     * transactions, cashier's checks / money orders of $10k or less) — and it AGGREGATES related cash
     * received within 12 months. A card or Square payment never counts, so this sums
     * payments.method = 'cash' only; flagging every large payment would manufacture filings.
     */
    private function cashLast12(int $clientId): array
    {
        // Invoice payments marked cash PLUS cash booked on the Income page for this client.
        $rows = db_connect()->query(
            "SELECT * FROM (
                SELECT p.id, p.amount, p.paid_at, p.invoice_id
                  FROM payments p JOIN invoices i ON i.id = p.invoice_id
                 WHERE i.client_id = ? AND p.method = 'cash' AND p.status = 'completed'
                   AND p.paid_at >= (NOW() - INTERVAL 12 MONTH)
                UNION ALL
                SELECT n.id, n.amount, CAST(n.date AS DATETIME) AS paid_at, NULL AS invoice_id
                  FROM income n
                 WHERE n.client_id = ? AND n.method = 'cash'
                   AND n.date >= (CURDATE() - INTERVAL 12 MONTH)
             ) u ORDER BY paid_at", [$clientId, $clientId]
        )->getResultArray();
        return ['rows' => $rows, 'total' => array_sum(array_column($rows, 'amount'))];
    }

    /** Printable 8300 worksheet: client + the cash payments, to copy onto the form or e-file. */
    public function form8300(int $id): \CodeIgniter\HTTP\ResponseInterface|string
    {
        if ($r = $this->guard()) {
            return $r;
        }
        $client = db_connect()->table('clients')->where('id', $id)->get()->getRowArray();
        if (! $client) {
            return redirect()->to('/admin/clients');
        }
        return view('admin/clients/form8300', ['client' => $client, 'cash' => $this->cashLast12($id)]);
    }
}
