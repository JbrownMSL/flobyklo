<?php

namespace App\Controllers\Admin;

use App\Libraries\ReceiptStore;

/**
 * #2948 — upload / serve / delete expense receipt photos.
 *
 * Receipts are NOT in the webroot, so every view of one comes through show() or
 * thumb(), both behind the same admin guard as the rest of /admin. The only path
 * input is an expense_receipts id; the filename on disk comes from the row.
 */
class Receipts extends BaseAdmin
{
    /** POST /admin/expenses/(:num)/receipts — attach one or more photos. */
    public function upload(int $expenseId)
    {
        if ($r = $this->guard()) { return $r; }

        $db      = db_connect();
        $expense = $db->table('expenses')->where('id', $expenseId)->get()->getRowArray();
        if (! $expense) {
            return redirect()->to('/admin/expenses')->with('error', 'Expense not found.');
        }

        $back  = '/admin/expenses/' . $expenseId;
        $files = $this->request->getFileMultiple('receipts') ?: [];
        // A single-file input arrives as one object, not an array.
        $one   = $this->request->getFile('receipt');
        if ($one) { $files[] = $one; }

        $files = array_filter($files, static fn ($f) => $f && $f->getError() !== UPLOAD_ERR_NO_FILE);
        if (! $files) {
            return redirect()->to($back)->with('error', 'No photo was selected.');
        }

        $store  = new ReceiptStore();
        $saved  = 0;
        $errors = [];

        foreach ($files as $file) {
            $row = $store->store($file, $expenseId);
            if (is_string($row)) { $errors[] = $row; continue; }
            $db->table('expense_receipts')->insert($row);
            $saved++;
        }

        // A saved photo clears the waive: the receipt is no longer missing.
        if ($saved && ! empty($expense['receipt_waived'])) {
            $db->table('expenses')->where('id', $expenseId)
               ->update(['receipt_waived' => 0, 'receipt_waived_reason' => null]);
        }

        $to = redirect()->to($back);
        if ($saved)  { $to = $to->with('msg', $saved . ' receipt photo' . ($saved === 1 ? '' : 's') . ' attached.'); }
        if ($errors) { $to = $to->with('error', implode(' ', array_unique($errors))); }
        return $to;
    }

    /** GET /admin/receipts/(:num) — stream the full-size photo. */
    public function show(int $id)
    {
        if ($r = $this->guard()) { return $r; }
        return $this->stream($id, false);
    }

    /** GET /admin/receipts/(:num)/thumb — stream a downscaled JPEG for the list. */
    public function thumb(int $id)
    {
        if ($r = $this->guard()) { return $r; }
        return $this->stream($id, true);
    }

    /** POST /admin/receipts/(:num)/delete */
    public function delete(int $id)
    {
        if ($r = $this->guard()) { return $r; }

        $db  = db_connect();
        $row = $db->table('expense_receipts')->where('id', $id)->get()->getRowArray();
        if (! $row) {
            return redirect()->to('/admin/expenses')->with('error', 'Receipt not found.');
        }

        (new ReceiptStore())->delete($row);
        $db->table('expense_receipts')->where('id', $id)->delete();

        return redirect()->to('/admin/expenses/' . (int) $row['expense_id'])
            ->with('msg', 'Receipt photo removed.');
    }

    /** POST /admin/expenses/(:num)/receipt-waive — dismiss from the missing-receipts worklist. */
    public function waive(int $expenseId)
    {
        if ($r = $this->guard()) { return $r; }

        $reason = trim((string) $this->request->getPost('reason'));
        db_connect()->table('expenses')->where('id', $expenseId)->update([
            'receipt_waived'        => 1,
            'receipt_waived_reason' => $reason !== '' ? mb_substr($reason, 0, 150) : 'No receipt available',
        ]);

        return redirect()->to('/admin/expenses/' . $expenseId)
            ->with('msg', 'Marked as no receipt available.');
    }

    private function stream(int $id, bool $wantThumb)
    {
        $row = db_connect()->table('expense_receipts')->where('id', $id)->get()->getRowArray();
        if (! $row) {
            return $this->response->setStatusCode(404)->setBody('Not found');
        }

        $store = new ReceiptStore();
        $path  = $wantThumb ? $store->thumb($row) : $store->pathFor($row);
        $mime  = $wantThumb ? 'image/jpeg' : (string) $row['mime'];

        // thumb() returns null for a format GD cannot read; fall back to the original
        // rather than 404-ing, so the link still opens the receipt she captured.
        if ($wantThumb && $path === null) {
            $path = $store->pathFor($row);
            $mime = (string) $row['mime'];
        }

        if ($path === null) {
            return $this->response->setStatusCode(404)->setBody('File missing');
        }

        return $this->response
            ->setHeader('Content-Type', $mime)
            ->setHeader('Content-Length', (string) filesize($path))
            ->setHeader('Content-Disposition', 'inline; filename="receipt-' . $id . '"')
            ->setHeader('X-Content-Type-Options', 'nosniff')
            ->setHeader('Cache-Control', 'private, max-age=600')
            ->setBody((string) file_get_contents($path));
    }
}
