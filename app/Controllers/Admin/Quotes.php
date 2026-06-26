<?php

namespace App\Controllers\Admin;

use App\Models\QuoteModel;
use App\Models\QuoteItemModel;
use App\Models\InvoiceModel;

class Quotes extends BaseAdmin
{
    // -------------------------------------------------------------------------
    // List
    // -------------------------------------------------------------------------

    public function index()
    {
        if ($r = $this->guard()) { return $r; }

        $db     = db_connect();
        $status = $this->request->getGet('status');

        $builder = $db->table('quotes q')
            ->select('q.*, c.name AS client_name')
            ->join('clients c', 'c.id = q.client_id', 'left')
            ->orderBy('q.created_at', 'DESC');

        if ($status && in_array($status, ['draft', 'sent', 'accepted', 'declined'], true)) {
            $builder->where('q.status', $status);
        }

        return view('admin/quotes/index', [
            'title'        => 'Quotes',
            'quotes'       => $builder->get()->getResultArray(),
            'statusFilter' => $status,
        ]);
    }

    // -------------------------------------------------------------------------
    // Form (new quote) OR detail view (existing quote)
    // draft quotes show the editable form; any other status shows the show view.
    // -------------------------------------------------------------------------

    public function form($id = null)
    {
        if ($r = $this->guard()) { return $r; }

        $quoteModel = new QuoteModel();
        $db         = db_connect();

        $quote = $id ? $quoteModel->withClient((int) $id) : null;
        if ($id && ! $quote) {
            return redirect()->to('/admin/quotes')->with('error', 'Quote not found.');
        }

        $items   = $id ? (new QuoteItemModel())->where('quote_id', $id)->findAll() : [];
        $clients = $db->table('clients')->orderBy('name')->get()->getResultArray();
        $events  = $db->table('events')->orderBy('event_date', 'DESC')->get()->getResultArray();

        // Recipes with computed total cost (stem cost + labor)
        $laborRate = (float) config('Fbk')->laborRate;
        $recipes   = $db->table('recipes r')
            ->select('r.id, r.name, r.type, r.labor_minutes, COALESCE(SUM(rs.qty * rs.unit_cost), 0) AS stem_cost')
            ->join('recipe_stems rs', 'rs.recipe_id = r.id', 'left')
            ->groupBy('r.id')
            ->orderBy('r.name')
            ->get()->getResultArray();

        foreach ($recipes as &$rec) {
            $rec['total_cost'] = round((float) $rec['stem_cost'] + ($rec['labor_minutes'] / 60.0) * $laborRate, 2);
        }
        unset($rec);

        // Back-calculate tax rate from saved values for the form default
        $taxRate = 0.0;
        if ($quote && (float) $quote['subtotal'] > 0) {
            $taxRate = round((float) $quote['tax'] / (float) $quote['subtotal'] * 100, 4);
        }

        // Invoice link for accepted quotes
        $invoice = null;
        if ($id && $quote && $quote['status'] === 'accepted') {
            $invoice = $db->table('invoices')->where('quote_id', $id)->get()->getRowArray() ?: null;
        }

        $data = [
            'title'    => $quote ? ('Quote #' . $id . ' — ' . ucfirst($quote['status'])) : 'New Quote',
            'quote'    => $quote,
            'items'    => $items,
            'clients'  => $clients,
            'events'   => $events,
            'recipes'  => $recipes,
            'taxRate'  => $taxRate,
            'invoice'  => $invoice,
        ];

        if ($quote && $quote['status'] !== 'draft') {
            return view('admin/quotes/show', $data);
        }

        return view('admin/quotes/form', $data);
    }

    // -------------------------------------------------------------------------
    // Save (full save or quick status change)
    // -------------------------------------------------------------------------

    public function save()
    {
        if ($r = $this->guard()) { return $r; }

        $id     = (int) $this->request->getPost('id');
        $action = $this->request->getPost('_action');

        // Quick status-only update (decline / revert to draft from show view)
        if ($action === 'status') {
            if (! $id) {
                return redirect()->to('/admin/quotes')->with('error', 'No quote specified.');
            }
            $newStatus = $this->request->getPost('status');
            if (! in_array($newStatus, ['draft', 'declined'], true)) {
                return redirect()->back()->with('error', 'Invalid status.');
            }
            (new QuoteModel())->update($id, ['status' => $newStatus]);
            return redirect()->to('/admin/quotes/' . $id)->with('msg', 'Quote status updated to ' . $newStatus . '.');
        }

        // Full save — validates and upserts quote + items
        $clientId = (int) $this->request->getPost('client_id');
        if (! $clientId) {
            return redirect()->back()->withInput()->with('error', 'Client is required.');
        }

        $eventId    = (int) $this->request->getPost('event_id') ?: null;
        $depositPct = max(0, min(100, (int) ($this->request->getPost('deposit_pct') ?: 50)));
        $validUntil = $this->request->getPost('valid_until') ?: null;
        $taxRate    = (float) ($this->request->getPost('tax_rate') ?: 0);

        $quoteModel = new QuoteModel();

        if ($id) {
            $existing = $quoteModel->find($id);
            if (! $existing || $existing['status'] !== 'draft') {
                return redirect()->back()->with('error', 'Only draft quotes can be edited this way.');
            }
            $quoteModel->update($id, [
                'client_id'   => $clientId,
                'event_id'    => $eventId,
                'deposit_pct' => $depositPct,
                'valid_until' => $validUntil,
            ]);
        } else {
            $quoteModel->insert([
                'client_id'   => $clientId,
                'event_id'    => $eventId,
                'status'      => 'draft',
                'deposit_pct' => $depositPct,
                'valid_until' => $validUntil,
                'subtotal'    => 0,
                'tax'         => 0,
                'total'       => 0,
            ]);
            $id = (int) $quoteModel->getInsertID();
        }

        // Replace all items
        $db = db_connect();
        $db->table('quote_items')->where('quote_id', $id)->delete();

        $itemsPost = $this->request->getPost('items') ?? [];
        foreach ($itemsPost as $item) {
            $qty       = (float) ($item['qty'] ?? 1);
            $unitPrice = (float) ($item['unit_price'] ?? 0);
            $cost      = (float) ($item['cost'] ?? 0);       // total cost for the line
            $lineTotal = round($qty * $unitPrice, 2);
            $desc      = trim($item['description'] ?? '');

            if ($desc === '' && $lineTotal == 0.0) {
                continue; // skip blank rows
            }

            $db->table('quote_items')->insert([
                'quote_id'    => $id,
                'recipe_id'   => (int) ($item['recipe_id'] ?? 0) ?: null,
                'description' => $desc !== '' ? $desc : '(item)',
                'qty'         => $qty,
                'unit_price'  => $unitPrice,
                'line_total'  => $lineTotal,
                'cost'        => $cost,
            ]);
        }

        $quoteModel->recalcTotals($id, $taxRate);

        return redirect()->to('/admin/quotes/' . $id)->with('msg', 'Quote saved.');
    }

    // -------------------------------------------------------------------------
    // PDF download
    // -------------------------------------------------------------------------

    public function pdf(int $id)
    {
        if ($r = $this->guard()) { return $r; }

        $quoteModel = new QuoteModel();
        $quote      = $quoteModel->withClient($id);
        if (! $quote) {
            return redirect()->to('/admin/quotes')->with('error', 'Quote not found.');
        }
        $items = (new QuoteItemModel())->where('quote_id', $id)->findAll();

        if (! class_exists('\Mpdf\Mpdf')) {
            return redirect()->back()->with('error', 'PDF library unavailable — run composer install.');
        }

        $html = view('admin/quotes/pdf_template', ['quote' => $quote, 'items' => $items]);
        $mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => 'A4']);
        $mpdf->SetTitle('Quote #' . $id . ' — Flora by Klo');
        $mpdf->WriteHTML($html);
        $mpdf->Output('quote-' . $id . '.pdf', \Mpdf\Output\Destination::INLINE);
        exit;
    }

    // -------------------------------------------------------------------------
    // Send quote email to client
    // -------------------------------------------------------------------------

    public function send(int $id)
    {
        if ($r = $this->guard()) { return $r; }

        $quoteModel = new QuoteModel();
        $quote      = $quoteModel->withClient($id);
        if (! $quote) {
            return redirect()->to('/admin/quotes')->with('error', 'Quote not found.');
        }
        if (empty($quote['client_email'])) {
            return redirect()->back()->with('error', 'Client has no email address on file.');
        }
        if ($quote['status'] === 'declined') {
            return redirect()->back()->with('error', 'Cannot send a declined quote.');
        }

        $items   = (new QuoteItemModel())->where('quote_id', $id)->findAll();
        $subject = 'Your Floral Proposal — ' . config('Fbk')->businessName;
        $html    = view('emails/quote_proposal', [
            'quote' => $quote,
            'items' => $items,
        ]);

        // Attempt PDF attachment if mPDF is available
        $pdfPath = null;
        if (class_exists('\Mpdf\Mpdf')) {
            try {
                $pdfHtml = view('admin/quotes/pdf_template', ['quote' => $quote, 'items' => $items]);
                $mpdf    = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => 'A4']);
                $mpdf->WriteHTML($pdfHtml);
                $pdfPath = WRITEPATH . 'quote-' . $id . '-' . time() . '.pdf';
                $mpdf->Output($pdfPath, \Mpdf\Output\Destination::FILE);
            } catch (\Throwable $e) {
                log_message('error', 'PDF attachment failed for quote #' . $id . ': ' . $e->getMessage());
                $pdfPath = null;
            }
        }

        $to      = $quote['client_email'];
        $lockout = filter_var(env('fbk.devLockout', true), FILTER_VALIDATE_BOOLEAN);
        $allow   = array_filter(array_map('trim', explode(',', (string) env('fbk.devAllow', 'jbrown@motorsportsland.com'))));
        $blocked = $lockout && ! in_array(strtolower($to), array_map('strtolower', $allow), true);

        $db    = db_connect();
        $logId = null;
        if ($db->table('email_log')->insert([
            'recipient' => $to,
            'type'      => 'quote',
            'subject'   => substr($subject, 0, 200),
            'sent'      => 0,
            'sent_at'   => null,
        ])) {
            $logId = (int) $db->insertID();
        }

        $ok = false;
        if (! $blocked) {
            $fbkCfg = config('Fbk');
            $mailer = service('email');
            $mailer->setFrom($fbkCfg->businessEmail, $fbkCfg->businessName);
            $mailer->setTo($to);
            $mailer->setSubject($subject);
            $mailer->setMessage($html);
            $mailer->setMailType('html');
            if ($pdfPath !== null && is_file($pdfPath)) {
                $mailer->attach($pdfPath, '', 'quote-proposal.pdf', 'application/pdf');
            }
            $ok = (bool) $mailer->send(false);
        }

        if ($logId) {
            $db->table('email_log')->where('id', $logId)
               ->update(['sent' => $ok ? 1 : 0, 'sent_at' => $ok ? date('Y-m-d H:i:s') : null]);
        }
        if ($pdfPath !== null && is_file($pdfPath)) {
            @unlink($pdfPath);
        }

        $quoteModel->update($id, ['status' => 'sent']);

        $msg = $blocked
            ? 'Dev lockout — email suppressed; quote marked sent.'
            : ($ok ? 'Proposal emailed to ' . esc($to) . ' with PDF attachment.' : 'Email delivery failed — check mail config. Quote marked sent.');

        return redirect()->to('/admin/quotes/' . $id)->with('msg', $msg);
    }

    // -------------------------------------------------------------------------
    // Accept quote and create invoice
    // -------------------------------------------------------------------------

    public function createInvoice(int $id)
    {
        if ($r = $this->guard()) { return $r; }

        $quoteModel = new QuoteModel();
        $quote      = $quoteModel->find($id);
        if (! $quote) {
            return redirect()->to('/admin/quotes')->with('error', 'Quote not found.');
        }
        if (! in_array($quote['status'], ['sent', 'accepted', 'draft'], true)) {
            return redirect()->back()->with('error', 'Quote is declined — cannot create invoice.');
        }

        // If invoice already exists, redirect there
        $db       = db_connect();
        $existing = $db->table('invoices')->where('quote_id', $id)->get()->getRowArray();
        if ($existing) {
            return redirect()->to('/admin/invoices/' . $existing['id'])
                ->with('msg', 'Invoice already exists for this quote.');
        }

        // Mark quote accepted, create invoice
        $quoteModel->update($id, ['status' => 'accepted']);
        $quote['status'] = 'accepted';

        $invoiceModel = new InvoiceModel();
        $invoiceId    = $invoiceModel->createFromQuote($quote);

        return redirect()->to('/admin/invoices/' . $invoiceId)
            ->with('msg', 'Quote #' . $id . ' accepted — invoice created.');
    }
}
