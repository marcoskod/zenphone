<?php

namespace App\Services\SmsProvider\Contracts;

/**
 * What the app needs from a virtual-number supplier, independent of who it is. Countries
 * and services are addressed by stable, URL-safe app codes (e.g. "benin", "whatsapp"), never
 * by the supplier's own ids - each implementation maps codes to its ids internally.
 *
 * Order statuses are normalized to: PENDING, RECEIVED, CANCELED, TIMEOUT, FINISHED, BANNED.
 * CANCELED/TIMEOUT/BANNED with no SMS mean the supplier has already refunded us.
 */
interface SmsProviderInterface
{
    /** @return list<array{code: string, name: string, iso: ?string}> */
    public function getCountries(): array;

    /**
     * Services orderable in a country, each with its cheapest current USD price.
     *
     * @return list<array{code: string, label: string, price_usd: float}>
     */
    public function getServices(string $country): array;

    /** Cheapest current USD price for a service in a country, or null if not offered. */
    public function getPrice(string $country, string $service): ?float;

    /**
     * @return array{id: string, phone: string, status: string, expires: ?string, price_usd: ?float}
     */
    public function buyActivation(string $country, string $service): array;

    /**
     * @return array{status: string, sms: list<array{code: string, text: ?string}>, expires: ?string}
     */
    public function checkOrder(string $id): array;

    /** @return array{status: string} */
    public function cancelOrder(string $id): array;

    /** Supplier account balance in USD, or null if it couldn't be read. */
    public function getBalance(): ?float;
}
