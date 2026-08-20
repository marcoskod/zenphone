<?php

namespace App\Services\FedaPay;

use App\Exceptions\FedaPayException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FedaPayService
{
    public function __construct(protected array $config)
    {
    }

    /**
     * Server-to-server verification of a FedaPay transaction after the client reports
     * an APPROVED result. Never trust the client callback alone - this is what actually
     * decides whether to credit a user's balance.
     *
     * Verified against FedaPay's docs (docs.fedapay.com) via WebFetch: GET /v1/
     * transactions/{id} with `Authorization: Bearer {secret_key}`, base URL
     * sandbox-api.fedapay.com (sandbox) or api.fedapay.com (live) depending on
     * FEDAPAY_ENVIRONMENT. The docs describe the transaction object as returned
     * directly at the response root (status/amount/amount_debited among other fields)
     * but did not include a worked example, so this defensively also checks a possible
     * nested wrapper key before giving up.
     *
     * @throws FedaPayException
     */
    public function verifyTransaction(int|string $transactionId): array
    {
        Log::info('FedaPay verify transaction request', ['transaction_id' => $transactionId]);

        try {
            $response = Http::withToken($this->config['secret_key'])
                ->baseUrl($this->config['base_url'])
                ->acceptJson()
                ->get("/transactions/{$transactionId}");
        } catch (ConnectionException $e) {
            Log::error('FedaPay API connection error', [
                'transaction_id' => $transactionId,
                'message' => $e->getMessage(),
            ]);

            throw new FedaPayException("Impossible de contacter FedaPay : {$e->getMessage()}", previous: $e);
        }

        if ($response->failed()) {
            Log::error('FedaPay API error response', [
                'transaction_id' => $transactionId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new FedaPayException($this->extractErrorMessage($response), $response->status());
        }

        $json = $response->json() ?? [];

        $transaction = $json['v1/transaction'] ?? $json['transaction'] ?? $json;

        Log::info('FedaPay verify transaction response', [
            'transaction_id' => $transactionId,
            'status' => $transaction['status'] ?? null,
        ]);

        return $transaction;
    }

    private function extractErrorMessage(\Illuminate\Http\Client\Response $response): string
    {
        $json = $response->json();

        if (is_array($json) && isset($json['message']) && is_string($json['message'])) {
            return $json['message'];
        }

        $body = trim($response->body());

        return $body !== '' ? $body : "Erreur FedaPay (HTTP {$response->status()})";
    }
}
