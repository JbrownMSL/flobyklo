<?php

        namespace App\Libraries;

        class Plaid
        {
            private $clientId;
            private $secret;
            private $baseUrl;

            public function __construct()
            {
                $this->clientId = env('plaid.client_id');
                $this->secret   = env('plaid.secret');
                $this->baseUrl  = env('plaid.environment') === 'production'
                    ? 'https://production.plaid.com'
                    : 'https://sandbox.plaid.com';
            }
            /**
             * Call Plaid's /transactions/enrich endpoint
             * @param array $transactions Array of transaction objects (from /transactions/get)
             * @return array Enriched response
             */
            public function enrichTransactions(array $transactions)
            {
                $cleaned = array_map(function ($txn, $index) {
                    // Use authorized_date if date is missing (common in Plaid)
                    $date = $txn['date'] ?? $txn['authorized_date'] ?? date('Y-m-d');

                    return [
                        'id'             => $txn['transaction_id'] ?? 'txn_' . $index,
                        'description'    => $txn['original_description']
                            ?? $txn['name']
                                ?? $txn['merchant_name']
                                ?? 'Unknown Transaction',
                        'amount'         => abs($txn['amount']),
                        'direction'      => $txn['amount'] >= 0 ? 'INFLOW' : 'OUTFLOW',
                        'iso_currency_code' => $txn['iso_currency_code'] ?? 'USD',
                        'date_posted'    => $date,

                    ];
                }, $transactions, array_keys($transactions));

                $payload = [
                    'client_id'     => $this->clientId,
                    'secret'        => $this->secret,
                    'account_type'  => 'depository',  // or 'credit' — we'll auto-detect below
                    'transactions'  => $cleaned,
                ];

                return $this->makeRequest('/transactions/enrich', $payload);
            }

            /**
             * @throws \Exception
             */
            public function removeItem($accessToken)
            {
                $payload = [
                    'client_id'    => $this->clientId,
                    'secret'       => $this->secret,
                    'access_token' => $accessToken,
                ];

                return $this->makeRequest('/item/remove', $payload);
            }
            private function makeRequest($endpoint, $payload)
            {
                $url = $this->baseUrl . '/' . $endpoint;

                $headers = [
                    'Content-Type: application/json',
                ];

                $ch = curl_init($url);

                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

                $response = curl_exec($ch);

                if (curl_errno($ch)) {
                    $error = curl_error($ch);
                    log_message('error', "Make Request curl error ->". $error);
                    curl_close($ch);
                    throw new \Exception("cURL error: " . $error);
                }

                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                //log_message('debug', "Make Response curl info ->". $httpCode);
                curl_close($ch);

                $responseArray = json_decode($response, true);
                //log_message('debug', "Make Response Array->". print_r($responseArray,true));
                if ($httpCode >= 400) {
                    throw new \Exception("HTTP error: " . $responseArray['error_message'] ?? 'Unknown error');
                }

                return $responseArray;
            }

            public function createLinkToken($clientUserId)
            {
                $payload = [
                    'client_id' => $this->clientId,
                    'secret' => $this->secret,
                    'client_name' => 'Motor Sportsland Menu',
                    'user' => [
                        'client_user_id' => $clientUserId
                    ],
                    'products' => ['transactions'], // Desired products
                    'country_codes' => ['US'],
                    'language' => 'en'
                ];
                log_message('error', 'Error in createLinkToken: ' . json_encode($payload));
                return $this->makeRequest('/link/token/create', $payload);
            }

            public function exchangePublicToken($publicToken)
            {
                $payload = [
                    'client_id' => $this->clientId,
                    'secret' => $this->secret,
                    'public_token' => $publicToken,
                ];

                return $this->makeRequest('/item/public_token/exchange', $payload);
            }

            public function getAccounts($accessToken)
            {
                $payload = [
                    'client_id' => $this->clientId,
                    'secret' => $this->secret,
                    'access_token' => $accessToken,
                ];

                return $this->makeRequest('/accounts/get', $payload);
            }

            public function getTransactions($accessToken, $startDate, $endDate)
            {
                // Plaid default page size is 100, max 500. Bump to 500 so a single
                // call covers any reasonable daily-sync window (30d) — observed max
                // across MSL tenants is ~150/180d.
                $payload = [
                    'client_id' => $this->clientId,
                    'secret' => $this->secret,
                    'access_token' => $accessToken,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'options' => ['count' => 500],
                ];

                return $this->makeRequest('/transactions/get', $payload);
            }
        }
