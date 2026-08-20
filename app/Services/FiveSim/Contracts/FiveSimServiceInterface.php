<?php

namespace App\Services\FiveSim\Contracts;

interface FiveSimServiceInterface
{
    public function getProducts(string $country, string $operator = 'any'): array;

    public function getCountries(): array;

    public function buyActivation(string $country, string $operator, string $product): array;

    public function checkOrder(int $id): array;

    public function cancelOrder(int $id): array;

    public function finishOrder(int $id): array;
}
