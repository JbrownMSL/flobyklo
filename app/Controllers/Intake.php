<?php

namespace App\Controllers;

use App\Models\ClientModel;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Public intake endpoints — no auth required.
 * Used by the WP website inquiry form to land leads into the CRM.
 */
class Intake extends BaseController
{
    /**
     * POST /intake/lead
     *
     * Accepts a WP form submission (name, email, phone, message, occasion)
     * and creates a client row with source=inquiry and status=lead.
     * Returns JSON so the WP form handler can read the result.
     */
    public function lead(): ResponseInterface
    {
        $name     = trim((string) $this->request->getPost('name'));
        $email    = trim((string) $this->request->getPost('email'));
        $phone    = trim((string) $this->request->getPost('phone'));
        $message  = trim((string) $this->request->getPost('message'));
        $occasion = trim((string) $this->request->getPost('occasion'));

        if ($name === '') {
            return $this->response
                ->setStatusCode(422)
                ->setJSON(['ok' => false, 'error' => 'Name is required.']);
        }

        if ($email !== '' && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->response
                ->setStatusCode(422)
                ->setJSON(['ok' => false, 'error' => 'Invalid email address.']);
        }

        $notes = '';
        if ($occasion !== '') {
            $notes .= "Occasion: {$occasion}\n";
        }
        if ($message !== '') {
            $notes .= "Message: {$message}";
        }

        $model = new ClientModel();
        $model->skipValidation(false);

        $data = [
            'name'   => $name,
            'email'  => $email ?: null,
            'phone'  => $phone ?: null,
            'source' => 'inquiry',
            'status' => 'lead',
            'notes'  => trim($notes) ?: null,
        ];

        if (! $model->insert($data)) {
            log_message('error', 'Intake::lead insert failed: ' . json_encode($model->errors()));
            return $this->response
                ->setStatusCode(500)
                ->setJSON(['ok' => false, 'error' => 'Could not save inquiry.']);
        }

        return $this->response->setJSON([
            'ok'      => true,
            'message' => 'Thank you! We will be in touch shortly.',
        ]);
    }
}
