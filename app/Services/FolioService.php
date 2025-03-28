<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class FolioService
{
    protected $client;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => env('FOLIO_BASE_URL', 'https://folio-snapshot-okapi.dev.folio.org'),
            'timeout' => 30,
            'verify' => true,
        ]);
    }

    public function authenticate($tenant, $username, $password)
    {
        $response = $this->client->post('/authn/login', [
            'headers' => [
                'x-okapi-tenant' => $tenant ?? env('FOLIO_TENANT_ID', 'diku'),
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'username' => $username ?? env('FOLIO_USERNAME', 'diku_admin'),
                'password' => $password ?? env('FOLIO_PASSWORD', 'admin'),
            ],
        ]);

        return $response->getHeader('x-okapi-token')[0];
    }

    public function importUsers($token, $tenant, $payload)
    {
        try {
            $response = $this->client->post('/user-import', [
                'headers' => [
                    'x-okapi-tenant' => $tenant,
                    'x-okapi-token' => $token,
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload,
            ]);

            $statusCode = $response->getStatusCode();
            $body = json_decode($response->getBody()->getContents(), true);

            // Log the status code and response for debugging
            \Log::debug('importUsers Response:', [
                'statusCode' => $statusCode,
                'body' => $body,
            ]);

            return $body;
        } catch (RequestException $e) {
            // Log the error for debugging
            \Log::error('importUsers failed:', [
                'message' => $e->getMessage(),
                'statusCode' => $e->hasResponse() ? $e->getResponse()->getStatusCode() : null,
                'responseBody' => $e->hasResponse() ? (string) $e->getResponse()->getBody() : null,
            ]);

            // If the response is available, parse it
            if ($e->hasResponse()) {
                $statusCode = $e->getResponse()->getStatusCode();
                $body = json_decode($e->getResponse()->getBody()->getContents(), true);

                // Check if the response indicates a successful import despite the status code
                if (isset($body['failedRecords']) && $body['failedRecords'] === 0) {
                    \Log::info('Import succeeded despite non-2xx status code:', [
                        'statusCode' => $statusCode,
                        'body' => $body,
                    ]);
                    return $body; // Treat as success if no failed records
                }

                throw new \Exception("Failed to import users. Status: {$statusCode}, Response: " . json_encode($body));
            }

            throw new \Exception("Failed to import users: " . $e->getMessage());
        }
    }

    public function getServicePoints($token, $tenant)
    {
        $response = $this->client->get('/service-points', [
            'headers' => [
                'x-okapi-tenant' => $tenant,
                'x-okapi-token' => $token,
                'Content-Type' => 'application/json',
            ],
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }
}