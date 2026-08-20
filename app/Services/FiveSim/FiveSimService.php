<?php

namespace App\Services\FiveSim;

use App\Services\FiveSim\Contracts\FiveSimServiceInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FiveSimService implements FiveSimServiceInterface
{
    public function __construct(protected array $config)
    {
    }

    public function getProducts(string $country, string $operator = 'any'): array
    {
        return $this->request('get', "/guest/products/{$country}/{$operator}");
    }

    public function getCountries(): array
    {
        return $this->request('get', '/guest/countries');
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
        try {
            $response = Http::withToken($this->config['api_key'])
                ->baseUrl($this->config['base_url'])
                ->{$method}($endpoint, $params);

            return $response->json() ?? [];
        } catch (\Throwable $e) {
            Log::error('5sim API request failed', [
                'endpoint' => $endpoint,
                'message' => $e->getMessage(),
            ]);

            return [];
        }
    }
}
