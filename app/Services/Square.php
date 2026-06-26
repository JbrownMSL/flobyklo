<?php

namespace App\Services;

/**
 * Square payments. Uses the Square REST Payments API via CURLRequest.
 * **Graceful**: when keys are not configured (Config\Fbk::squareConfigured()
 * returns false) every call returns a simulated success so the payment flow
 * is fully testable without live keys — payments are recorded with
 * status='simulated'. Card data never touches our servers: the client
 * tokenizes via the Square Web Payments SDK and posts only a nonce.
 */
class Square
{
    private \Config\Fbk $cfg;
    private bool $live;
    private string $base;

    public function __construct()
    {
        $this->cfg  = config('Fbk');
        $this->live = $this->cfg->squareConfigured();
        $this->base = $this->cfg->squareEnv === 'production'
            ? 'https://connect.squareup.com'
            : 'https://connect.squareupsandbox.com';
    }

    public function isLive(): bool
    {
        return $this->live;
    }

    /** Immediate charge. amount in dollars. */
    public function charge(string $token, float $amount, string $idempotencyKey, string $note = ''): array
    {
        return $this->createPayment($token, $amount, $idempotencyKey, true, $note);
    }

    /** Authorization hold (delayed capture) for a deposit. */
    public function hold(string $token, float $amount, string $idempotencyKey, string $note = ''): array
    {
        return $this->createPayment($token, $amount, $idempotencyKey, false, $note);
    }

    /** Complete (capture) a previously authorized payment. */
    public function capture(string $paymentId): array
    {
        if (! $this->live) {
            return ['ok' => true, 'simulated' => true, 'payment_id' => $paymentId, 'status' => 'COMPLETED'];
        }
        return $this->call('POST', "/v2/payments/{$paymentId}/complete", new \stdClass());
    }

    /** Cancel (void/release) an authorized hold. */
    public function release(string $paymentId): array
    {
        if (! $this->live) {
            return ['ok' => true, 'simulated' => true, 'payment_id' => $paymentId, 'status' => 'CANCELED'];
        }
        return $this->call('POST', "/v2/payments/{$paymentId}/cancel", new \stdClass());
    }

    /** Full or partial refund of a captured payment. */
    public function refund(string $paymentId, float $amount, string $idempotencyKey): array
    {
        if (! $this->live) {
            return ['ok' => true, 'simulated' => true, 'refund_id' => 'sim_refund_' . substr($idempotencyKey, 0, 8), 'status' => 'COMPLETED'];
        }
        return $this->call('POST', '/v2/refunds', [
            'idempotency_key' => $idempotencyKey,
            'payment_id'      => $paymentId,
            'amount_money'    => ['amount' => $this->cents($amount), 'currency' => 'USD'],
        ]);
    }

    private function createPayment(string $token, float $amount, string $idem, bool $autocomplete, string $note): array
    {
        if (! $this->live) {
            return [
                'ok' => true, 'simulated' => true,
                'payment_id' => 'sim_' . substr(md5($idem), 0, 16),
                'status' => $autocomplete ? 'COMPLETED' : 'APPROVED',
            ];
        }
        $res = $this->call('POST', '/v2/payments', [
            'idempotency_key' => $idem,
            'source_id'       => $token,
            'amount_money'    => ['amount' => $this->cents($amount), 'currency' => 'USD'],
            'autocomplete'    => $autocomplete,
            'location_id'     => $this->cfg->squareLocationId,
            'note'            => substr($note, 0, 60),
        ]);
        if (($res['ok'] ?? false) && isset($res['body']['payment'])) {
            $res['payment_id'] = $res['body']['payment']['id'] ?? null;
            $res['status']     = $res['body']['payment']['status'] ?? null;
        }
        return $res;
    }

    private function cents(float $amount): int
    {
        return (int) round($amount * 100);
    }

    private function call(string $method, string $path, $payload): array
    {
        try {
            $client = \Config\Services::curlrequest(['baseURI' => $this->base, 'timeout' => 20, 'http_errors' => false]);
            $resp = $client->request($method, $path, [
                'headers' => [
                    'Square-Version' => '2024-10-17',
                    'Authorization'  => 'Bearer ' . $this->cfg->squareAccessToken,
                    'Content-Type'   => 'application/json',
                ],
                'body' => json_encode($payload),
            ]);
            $code = $resp->getStatusCode();
            $body = json_decode($resp->getBody(), true);
            return ['ok' => $code >= 200 && $code < 300, 'code' => $code, 'body' => $body];
        } catch (\Throwable $e) {
            log_message('error', 'Square call failed: ' . $e->getMessage());
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }
}
