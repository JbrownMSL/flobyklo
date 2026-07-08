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

        // Server-side spam backstop. The WP inquiry form already filters obvious
        // SEO / web-design / lead-gen sales spam, but this endpoint is public
        // (no auth, CSRF off), so a spammer can POST here directly and bypass it.
        // Hard-drop obvious spam: don't create a lead, log it, and return a normal
        // success so the sender gets no useful signal.
        [$spamScore, $spamHits] = $this->spamScore($name, $email, $phone, $occasion, $message);
        if ($spamScore >= 3) {
            log_message('error', sprintf(
                'Intake::lead DROPPED spam (score=%d hits=%s) name=%s email=%s',
                $spamScore,
                implode(',', $spamHits),
                $name,
                $email
            ));
            return $this->response->setJSON([
                'ok'      => true,
                'message' => 'Thank you! We will be in touch shortly.',
            ]);
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

    /**
     * Score an inquiry for sales spam. Returns [int $score, array $hits].
     * score >= 3 => obvious spam. Conservative: one weak signal never trips it,
     * so borderline real inquiries pass. Mirrors the WP mu-plugin fbk-spam-filter.php.
     */
    private function spamScore(string $name, string $email, string $phone, string $occasion, string $message): array
    {
        $score  = 0;
        $hits   = [];
        $hay    = strtolower(trim($name . ' ' . $message));
        $emailL = strtolower(trim($email));

        // Links in name/message — a florist inquiry almost never contains a URL.
        $links = preg_match_all('~https?://|www\.|\b[a-z0-9][a-z0-9-]*\.(?:com|net|org|io|co|biz|info|xyz|shop|store|online|site|website|ru|cn|top|club|link|tech)\b~i', $hay);
        if ($links >= 1) { $score += 2; $hits[] = 'link'; }
        if ($links >= 2) { $score += 2; $hits[] = 'multi-link'; }

        // Sales / SEO / web-design / lead-gen vocabulary (each hit +2).
        $kw = [
            'seo', 'search engine', 'googlesearchindex', 'google search', 'first page of google', "google's first page", 'first page', 'backlink', 'link building', 'guest post',
            'web design', 'web development', 'website design', 'web platform', 'web solution', 'website solution', 'digital marketing', 'online marketing',
            'increase traffic', 'targeted traffic', 'more traffic', 'organic traffic', 'website traffic',
            'rank your', 'ranking', 'higher ranking', 'top ranking', 'lead generation', 'leads for your', 'high-quality clients', 'high-quality leads', 'quality leads',
            'attract more', 'more clients', 'new clients', 'grow your business', 'boost your', 'branding refresh', 'brand refresh', 'rebrand', 'logo design',
            'we help businesses', 'we can help you', 'we can increase', 'our services', 'proposal for you', 'partnership opportunity',
            'cryptocurrency', 'bitcoin', 'forex', 'payday', 'casino', 'viagra', 'b2b', 'cold email', 'outsourc',
            'dear sir', 'dear owner', 'dear madam', 'to whom it may', 'congrats on your new domain', 'provide your name, contact',
            'squarespace', 'shopify', 'wix', 'godaddy',
        ];
        foreach ($kw as $k) {
            if (strpos($hay, $k) !== false) { $score += 2; $hits[] = $k; }
        }

        // Spammy sender-email signals.
        if ($emailL !== '' && preg_match('~(seo|rank|marketing|(?<![a-z])mkt|digitaltech|websolution|web-?design|web-?solution|leadgen|^domains?@|noreply-|search-|rocketdigital)~', $emailL)) {
            $score += 2;
            $hits[] = 'email-kw';
        }
        // Sender domain impersonates our own (search-florabyklo.com, noreply-florabyklo.com) but isn't florabyklo.com.
        if ($emailL !== '' && preg_match('~@[^ @]*florabyklo~', $emailL) && ! preg_match('~@florabyklo\.com$~', $emailL)) {
            $score += 3;
            $hits[] = 'domain-spoof';
        }

        return [$score, $hits];
    }
}
