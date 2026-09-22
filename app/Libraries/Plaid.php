<?php

namespace App\Libraries;

class Plaid
{
    private string $clientId;
    private string $secret;
    private string $baseUrl;

    public function __construct()
    {
        $this->clientId = env('plaid.client_id');
        $this->secret   = env('plaid.secret');
        $this->baseUrl  = env('plaid.environment') === 'production'
            ? 'https://production.plaid.com'
            : 'https://sandbox.plaid.com';
    }

    public function createLinkToken(string $userId): array
    {
        return $this->makeRequest('/link/token/create', [
            'client_id'     => $this->clientId,
            'secret'        => $this->secret,
            'client_name'   => 'Flora by Klo',
            'user'          => ['client_user_id' => $userId],
            'products'      => ['transactions'],
            'country_codes' => ['US'],
            'language'      => 'en',
        ]);
    }

    public function exchangePublicToken(string $publicToken): array
    {
        return $this->makeRequest('/item/public_token/exchange', [
            'client_id'    => $this->clientId,
            'secret'       => $this->secret,
            'public_token' => $publicToken,
        ]);
    }

    public function getAccounts(string $accessToken): array
    {
        return $this->makeRequest('/accounts/get', [
            'client_id'    => $this->clientId,
            'secret'       => $this->secret,
            'access_token' => $accessToken,
        ]);
    }

    /**
     * #2944: MACU's Plaid Link grants every account on the online-banking login (Kaden's personal
     * 0507 accounts ride along with Flora's 9333 ones, and Link offers no account-select), so the
     * caller MUST pass the enabled account_ids. Bank::sync() also re-checks each row server-side.
     */
    public function getTransactions(string $accessToken, string $startDate, string $endDate, array $accountIds = []): array
    {
        $options = ['count' => 500];
        if ($accountIds !== []) {
            $options['account_ids'] = array_values($accountIds);
        }
        return $this->makeRequest('/transactions/get', [
            'client_id'    => $this->clientId,
            'secret'       => $this->secret,
            'access_token' => $accessToken,
            'start_date'   => $startDate,
            'end_date'     => $endDate,
            'options'      => $options,
        ]);
    }

    public function removeItem(string $accessToken): array
    {
        return $this->makeRequest('/item/remove', [
            'client_id'    => $this->clientId,
            'secret'       => $this->secret,
            'access_token' => $accessToken,
        ]);
    }

    private function makeRequest(string $endpoint, array $payload): array
    {
        $ch = curl_init($this->baseUrl . $endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS     => json_encode($payload),
        ]);

        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            $err = curl_error($ch);
            curl_close($ch);
            throw new \Exception('cURL error: ' . $err);
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $data = json_decode($response, true);
        if ($httpCode >= 400) {
            throw new \Exception('Plaid error: ' . ($data['error_message'] ?? 'HTTP ' . $httpCode));
        }

        return $data;
    }
}
