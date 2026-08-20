<?php

namespace App\Services\FiveSim;

use App\Services\FiveSim\Contracts\FiveSimServiceInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FiveSimService implements FiveSimServiceInterface
{
    public function __construct(protected array $config)
    {
    }

    public function getProducts(string $country, string $operator = 'any'): array
    {
        return Cache::remember(
            "fivesim.products.{$country}.{$operator}",
            now()->addMinutes(5),
            fn () => $this->request('get', "/guest/products/{$country}/{$operator}"),
        );
    }

    /**
     * Calls GET /guest/countries. The exact response schema for this endpoint was not
     * confirmed against the live 5sim docs (the docs page did not render it during
     * fetching); it is expected to be a JSON object keyed by country slug (e.g. "russia")
     * with fields such as iso codes, prefixes, and localized names, per 5sim's other
     * /guest/* endpoints. Returned as-is; no controller consumes it yet (action_05).
     */
    public function getCountries(): array
    {
        return Cache::remember(
            'fivesim.countries',
            now()->addMinutes(5),
            fn () => $this->request('get', '/guest/countries'),
        );
    }

    public function buyActivation(string $country, string $operator, string $product): array
    {
        return $this->request('get', "/user/buy/activation/{$country}/{$operator}/{$product}");
    }

    public function checkOrder(int $id): array
    {
        return $this->request('get', "/user/check/{$id}");
    }

    public function cancelOrder(int $id): array
    {
        return $this->request('get', "/user/cancel/{$id}");
    }

    public function finishOrder(int $id): array
    {
        return $this->request('get', "/user/finish/{$id}");
    }

    private function request(string $method, string $endpoint, array $params = []): array
    {
        Log::info('5sim API request', ['method' => $method, 'endpoint' => $endpoint]);

        $response = Http::withToken($this->config['api_key'])
            ->baseUrl($this->config['base_url'])
            ->acceptJson()
            ->{$method}($endpoint, $params);

        if ($response->failed()) {
            Log::error('5sim API error response', [
                'method' => $method,
                'endpoint' => $endpoint,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        } else {
            Log::info('5sim API response', [
                'method' => $method,
                'endpoint' => $endpoint,
                'status' => $response->status(),
            ]);
        }

        return $response->json() ?? [];
    }
}
