<?php

namespace App\Services;

class PricingService
{
    public function __construct(protected array $config)
    {
    }

    /**
     * Converts a 5sim USD price to the FCFA price shown to the customer, applying the
     * configured exchange rate and margin.
     */
    public function calculatePrice(float $priceUsd): float
    {
        $fcfa = $priceUsd * (float) $this->config['exchange_rate_usd_fcfa'];

        $withMargin = $fcfa * (1 + ((float) $this->config['margin_percent'] / 100));

        return round($withMargin, 2);
    }
}
