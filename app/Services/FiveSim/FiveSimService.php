<?php

namespace App\Services\FiveSim;

use App\Exceptions\FiveSimException;
use App\Services\FiveSim\Contracts\FiveSimServiceInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
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
        return $this->request('get', "/user/buy/activation/{$country}/{$operator}/{$product}", retryable: true);
    }

    /**
     * Calls GET /user/check/{id} (verified against 5sim docs: response includes
     * id/status/expires/phone/product/price/country plus an "sms" array with
     * created_at/date/sender/text/code once a message has arrived).
     *
     * This method only returns 5sim's raw response — it does not yet update the local
     * Order row's status/sms_code. That sync is wired in by whichever controller ends up
     * calling this for polling (action_05's purchase flow / action_06's SMS reception
     * screen), not here, since no controller exists yet in this phase.
     */
    public function checkOrder(int $id): array
    {
        return $this->request('get', "/user/check/{$id}", retryable: true);
    }

    /**
     * Calls GET /user/cancel/{id} (verified against 5sim docs: response is the order
     * object with status "CANCELED"; documented error cases are "order not found",
     * "order expired", "order has sms", "hosting order").
     */
    public function cancelOrder(int $id): array
    {
        return $this->request('get', "/user/cancel/{$id}");
    }

    /**
     * Calls GET /user/finish/{id} (verified against 5sim docs: response is the order
     * object with status "FINISHED"; no documented error cases for this endpoint).
     */
    public function finishOrder(int $id): array
    {
        return $this->request('get', "/user/finish/{$id}");
    }

    /**
     * @throws FiveSimException
     */
    private function request(string $method, string $endpoint, array $params = [], bool $retryable = false): array
    {
        Log::info('5sim API request', ['method' => $method, 'endpoint' => $endpoint]);

        $pendingRequest = Http::withToken($this->config['api_key'])
            ->baseUrl($this->config['base_url'])
            ->acceptJson();

        if ($retryable) {
            // By default Laravel's retry() re-attempts on ANY failed response (not just
            // connection errors) and throws once retries are exhausted — both wrong here:
            // an order endpoint like buyActivation() may have already billed the 5sim
            // account by the time a definitive error response comes back, so auto-retrying
            // that case risks a double charge. The `when` callback restricts retries to
            // genuine connection-level failures (timeout, DNS failure, connection refused,
            // ...), and `throw: false` stops it from auto-throwing on a definitive non-2xx
            // response; that case falls through to the $response->failed() check below,
            // as a single attempt, and is turned into a FiveSimException there instead.
            $pendingRequest = $pendingRequest->retry(
                times: 2,
                sleepMilliseconds: 200,
                when: fn (\Throwable $exception) => $exception instanceof ConnectionException,
                throw: false,
            );
        }

        try {
            $response = $pendingRequest->{$method}($endpoint, $params);
        } catch (ConnectionException $e) {
            Log::error('5sim API connection error', [
                'method' => $method,
                'endpoint' => $endpoint,
                'message' => $e->getMessage(),
            ]);

            throw new FiveSimException(
                "Impossible de contacter 5sim : {$e->getMessage()}",
                previous: $e,
            );
        }

        if ($response->failed()) {
            Log::error('5sim API error response', [
                'method' => $method,
                'endpoint' => $endpoint,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new FiveSimException($this->extractErrorMessage($response), $response->status());
        }

        Log::info('5sim API response', [
            'method' => $method,
            'endpoint' => $endpoint,
            'status' => $response->status(),
        ]);

        return $response->json() ?? [];
    }

    /**
     * 5sim does not consistently wrap errors as JSON — some endpoints return a bare
     * plain-text message instead. Prefer a JSON "error" field when present, otherwise
     * fall back to the raw response body.
     */
    private function extractErrorMessage(Response $response): string
    {
        $json = $response->json();

        if (is_array($json) && isset($json['error']) && is_string($json['error'])) {
            return $json['error'];
        }

        $body = trim($response->body());

        return $body !== '' ? $body : "Erreur 5sim (HTTP {$response->status()})";
    }
}
