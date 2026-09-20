<?php

namespace App\Services\SmsProvider;

use App\Exceptions\SmsProviderException;
use App\Services\SmsProvider\Contracts\SmsProviderInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * SMSPool (https://api.smspool.net) implementation of the supplier contract. Docs are a
 * Postman collection: https://documenter.getpostman.com/view/30155063/2s9YXmZ1JY
 *
 * SMSPool identifies countries and services by numeric ids and names services messily
 * ("Google/Gmail", "Instagram / Threads"). This class hides that: the app only ever sees
 * stable slug codes derived from the names ("benin", "google", "instagram"), mapped back
 * to ids from the (cached) catalogs. Slugs use the part of the name before any "/", the
 * lowest id wins a collision and later ones get an "-{id}" suffix.
 *
 * Every request is a form POST; the key goes both in the body (`key`, as each endpoint
 * documents) and as a Bearer token (as the collection's auth does).
 */
class SmsPoolService implements SmsProviderInterface
{
    private const CATALOG_TTL_MINUTES = 360;

    private const PRICING_TTL_MINUTES = 10;

    private const STARTING_PRICE_TTL_MINUTES = 30;

    /** Services whose cheapest price defines a country's "dès X". */
    private const HEADLINE_SERVICES = [
        'whatsapp', 'telegram', 'google', 'instagram', 'facebook', 'tiktok',
        'twitter', 'snapchat', 'discord', 'openai', 'amazon', 'tinder',
    ];

    public function __construct(protected array $config)
    {
    }

    public function getCountries(): array
    {
        return collect($this->countryMap())
            ->map(fn (array $c, string $code) => ['code' => $code, 'name' => $c['name'], 'iso' => $c['iso']])
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();
    }

    public function getStartingPrices(): array
    {
        return Cache::remember('smspool.starting_prices', now()->addMinutes(self::STARTING_PRICE_TTL_MINUTES), function () {
            $serviceMap = $this->serviceMap();
            $serviceIds = collect(self::HEADLINE_SERVICES)->map(fn (string $code) => $serviceMap[$code]['id'] ?? null)->filter()->values()->all();

            // request/pricing refuses "all countries" (HTTP 500) but answers per service
            // with one row per country, so ask for each headline service in parallel.
            try {
                $responses = Http::pool(fn ($pool) => array_map(
                    fn (int $id) => $pool->baseUrl($this->config['base_url'])->acceptJson()->asForm()->timeout(25)->post('/request/pricing', ['service' => $id]),
                    $serviceIds,
                ));
            } catch (\Throwable $e) {
                Log::warning('SMSPool starting prices unavailable', ['error' => $e->getMessage()]);

                return [];
            }

            $codeByCountryId = collect($this->countryMap())->mapWithKeys(fn (array $c, string $code) => [$c['id'] => $code]);
            $cheapest = [];

            foreach ($responses as $response) {
                if (! $response instanceof Response || $response->failed()) {
                    continue;
                }

                foreach ($response->json() ?? [] as $row) {
                    $code = $codeByCountryId[(int) ($row['country'] ?? 0)] ?? null;
                    $price = (float) ($row['price'] ?? 0);

                    if ($code && $price > 0 && (! isset($cheapest[$code]) || $price < $cheapest[$code])) {
                        $cheapest[$code] = $price;
                    }
                }
            }

            return $cheapest;
        });
    }

    public function getServices(string $country): array
    {
        $codeById = collect($this->serviceMap())->mapWithKeys(fn (array $s, string $code) => [$s['id'] => [$code, $s['name']]]);

        return collect($this->cheapestPriceByService($this->countryId($country)))
            ->map(function (float $price, int $serviceId) use ($codeById) {
                if (! $codeById->has($serviceId)) {
                    return null;
                }

                [$code, $name] = $codeById[$serviceId];

                // "Facebook / Meta Viewpoints" doesn't fit a tile; show the brand part. A
                // slug that had to be disambiguated (suffix) keeps the full name so two
                // tiles never read identically.
                $label = preg_match('/-\d+$/', $code) ? $name : trim(explode('/', $name)[0]);

                return ['code' => $code, 'label' => $label, 'price_usd' => $price];
            })
            ->filter()
            ->sortBy('label', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();
    }

    public function getPrice(string $country, string $service): ?float
    {
        $serviceId = $this->serviceMap()[$service]['id'] ?? null;

        if ($serviceId === null) {
            return null;
        }

        return $this->cheapestPriceByService($this->countryId($country))[$serviceId] ?? null;
    }

    public function buyActivation(string $country, string $service): array
    {
        $quote = $this->getPrice($country, $service);

        if ($quote === null) {
            throw new SmsProviderException("Ce service n'est pas disponible pour ce pays.");
        }

        $json = $this->request('post', '/purchase/sms', [
            'country' => $this->countryId($country),
            'service' => $this->serviceMap()[$service]['id'],
            'max_price' => number_format($quote * (1 + (float) $this->config['max_price_tolerance']), 2, '.', ''),
            'pricing_option' => 0,
            'quantity' => 1,
        ], authenticated: true);

        if (empty($json['order_id'])) {
            throw new SmsProviderException("Impossible de réserver un numéro pour le moment. Réessayez dans un instant.");
        }

        $expires = isset($json['expiration'])
            ? Carbon::createFromTimestamp((int) $json['expiration'])
            : now()->addSeconds((int) ($json['expires_in'] ?? 900));

        return [
            'id' => (string) $json['order_id'],
            'phone' => '+'.ltrim((string) ($json['number'] ?? ''), '+'),
            'status' => 'PENDING',
            'expires' => $expires->toIso8601String(),
            'price_usd' => isset($json['cost']) ? (float) $json['cost'] : null,
        ];
    }

    /**
     * SMSPool statuses: 1 pending, 3 completed (SMS in `sms`/`full_sms`), 6 refunded,
     * 2 expired, 5 cancelled. Anything unrecognized is treated as still PENDING on purpose:
     * the only consequence of guessing "dead" wrongly is refunding a live order.
     */
    public function checkOrder(string $id): array
    {
        $json = $this->request('post', '/sms/check', ['orderid' => $id], authenticated: true, retryable: true);

        $status = match ((int) ($json['status'] ?? 1)) {
            3 => 'RECEIVED',
            2 => 'TIMEOUT',
            5, 6 => 'CANCELED',
            default => 'PENDING',
        };

        $code = trim((string) ($json['sms'] ?? ''));
        $sms = $status === 'RECEIVED' && $code !== '' && $code !== '0'
            ? [['code' => $code, 'text' => $json['full_sms'] ?? null]]
            : [];

        return [
            'status' => $status,
            'sms' => $sms,
            'expires' => isset($json['expiration']) ? Carbon::createFromTimestamp((int) $json['expiration'])->toIso8601String() : null,
        ];
    }

    public function cancelOrder(string $id): array
    {
        // A refused cancellation comes back as success:0 + a message, which request()
        // turns into a SmsProviderException; reaching the return means SMSPool refunded us.
        $this->request('post', '/sms/cancel', ['orderid' => $id], authenticated: true);

        return ['status' => 'CANCELED'];
    }

    public function getBalance(): ?float
    {
        try {
            $json = $this->request('post', '/request/balance', [], authenticated: true);
        } catch (SmsProviderException) {
            return null;
        }

        return isset($json['balance']) ? (float) $json['balance'] : null;
    }

    /** @return array<string, array{id: int, name: string, iso: ?string}> */
    private function countryMap(): array
    {
        return Cache::remember('smspool.country_map', now()->addMinutes(self::CATALOG_TTL_MINUTES), function () {
            $map = [];

            foreach ($this->rawList('/country/retrieve_all') as $row) {
                if (($row['region'] ?? '') === 'Virtuals') {
                    continue;
                }

                $code = $this->uniqueCode($map, Str::slug($row['name'], ''), (int) $row['ID']);
                $map[$code] = ['id' => (int) $row['ID'], 'name' => $row['name'], 'iso' => $row['short_name'] ?? null];
            }

            return $map;
        });
    }

    /** @return array<string, array{id: int, name: string}> */
    private function serviceMap(): array
    {
        return Cache::remember('smspool.service_map', now()->addMinutes(self::CATALOG_TTL_MINUTES), function () {
            $rows = $this->rawList('/service/retrieve_all');
            usort($rows, fn ($a, $b) => $a['ID'] <=> $b['ID']);

            $map = [];

            foreach ($rows as $row) {
                $code = $this->uniqueCode($map, Str::slug(trim(explode('/', $row['name'])[0]), ''), (int) $row['ID']);
                $map[$code] = ['id' => (int) $row['ID'], 'name' => $row['name']];
            }

            return $map;
        });
    }

    private function uniqueCode(array $taken, string $slug, int $id): string
    {
        $slug = $slug !== '' ? $slug : 'item';

        return isset($taken[$slug]) ? "{$slug}-{$id}" : $slug;
    }

    private function countryId(string $code): int
    {
        $id = $this->countryMap()[$code]['id'] ?? null;

        if ($id === null) {
            throw new SmsProviderException('Pays inconnu.');
        }

        return $id;
    }

    /**
     * Cheapest price per service id in a country. request/pricing returns one row per
     * (service, pool); the cheapest pool is what a customer is quoted and what
     * buyActivation() caps max_price against.
     *
     * @return array<int, float>
     */
    private function cheapestPriceByService(int $countryId): array
    {
        return Cache::remember("smspool.pricing.{$countryId}", now()->addMinutes(self::PRICING_TTL_MINUTES), function () use ($countryId) {
            $rows = $this->request('post', '/request/pricing', ['country' => $countryId]);
            $cheapest = [];

            foreach ($rows as $row) {
                if (! is_array($row) || ! isset($row['service'], $row['price'])) {
                    continue;
                }

                $price = (float) $row['price'];
                $serviceId = (int) $row['service'];

                if ($price > 0 && (! isset($cheapest[$serviceId]) || $price < $cheapest[$serviceId])) {
                    $cheapest[$serviceId] = $price;
                }
            }

            return $cheapest;
        });
    }

    private function rawList(string $endpoint): array
    {
        return $this->request('get', $endpoint);
    }

    /**
     * @throws SmsProviderException
     */
    private function request(string $method, string $endpoint, array $params = [], bool $authenticated = false, bool $retryable = false): array
    {
        Log::info('SMSPool API request', ['method' => $method, 'endpoint' => $endpoint]);

        $pending = Http::baseUrl($this->config['base_url'])->acceptJson()->asForm()->timeout(25);

        if ($authenticated) {
            $pending = $pending->withToken((string) $this->config['api_key']);
            $params['key'] = (string) $this->config['api_key'];
        }

        if ($retryable) {
            // Only connection-level failures are retried. An order endpoint that already
            // answered (even with an error) must never be re-sent: a purchase could bill
            // twice. check/cancel are safe to repeat; purchase/sms is not retryable.
            $pending = $pending->retry(
                times: 2,
                sleepMilliseconds: 200,
                when: fn (\Throwable $e) => $e instanceof ConnectionException,
                throw: false,
            );
        }

        try {
            $response = $method === 'get' ? $pending->get($endpoint) : $pending->post($endpoint, $params);
        } catch (ConnectionException $e) {
            Log::error('SMSPool API connection error', ['endpoint' => $endpoint, 'message' => $e->getMessage()]);

            throw new SmsProviderException('Le fournisseur de numéros est injoignable pour le moment. Réessayez dans un instant.', previous: $e);
        }

        $json = $response->json();

        if ($response->failed() || (is_array($json) && array_key_exists('success', $json) && (int) $json['success'] === 0)) {
            Log::error('SMSPool API error response', [
                'endpoint' => $endpoint,
                'status' => $response->status(),
                'body' => Str::limit($response->body(), 500),
            ]);

            throw new SmsProviderException($this->customerMessage($response), $response->status());
        }

        Log::info('SMSPool API response', ['endpoint' => $endpoint, 'status' => $response->status()]);

        return is_array($json) ? $json : [];
    }

    /**
     * Provider error text is written for the reseller, not the customer ("insufficient
     * balance", HTML fragments...). Translate the cases a customer can hit into something
     * actionable, and shout in the logs about the ones only the operator can fix.
     */
    private function customerMessage(Response $response): string
    {
        $json = $response->json();
        $message = is_array($json)
            ? ($json['errors'][0]['message'] ?? $json['message'] ?? '')
            : '';
        $message = trim(strip_tags((string) $message)) ?: trim($response->body());

        $type = is_array($json) ? collect($json['pools'] ?? [])->pluck('type')->first() : null;

        if ($type === 'OUT_OF_STOCK' || Str::contains($message, ["couldn't find an available", 'out of stock'], ignoreCase: true)) {
            return 'Aucun numéro disponible pour ce service pour le moment. Réessayez plus tard ou choisissez un autre pays.';
        }

        if (Str::contains($message, ['balance', 'funds', 'insufficient'], ignoreCase: true)) {
            Log::critical('SMSPool account balance too low to fulfil orders', ['message' => $message]);

            return 'Service momentanément indisponible. Réessayez dans quelques minutes.';
        }

        if (Str::contains($message, ['api key', 'unauthor', 'invalid key', 'key'], ignoreCase: true) && in_array($response->status(), [400, 401, 403], true)) {
            Log::critical('SMSPool rejected our API key', ['message' => $message]);

            return 'Service momentanément indisponible. Réessayez dans quelques minutes.';
        }

        return $message !== '' ? $message : "Erreur du fournisseur (HTTP {$response->status()})";
    }
}
