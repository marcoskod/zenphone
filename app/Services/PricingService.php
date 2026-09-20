<?php

namespace App\Services;

use App\Models\Setting;

class PricingService
{
    public function __construct(protected array $config)
    {
    }

    /**
     * Converts a supplier USD price to the FCFA price shown to the customer, applying the
     * configured exchange rate and margin. Rounded up to a whole franc: FCFA has no
     * subunit and FedaPay expects integer XOF amounts, so a price like 172.8 would risk
     * being rejected at checkout (and rounding up, never down, protects the margin).
     */
    public function calculatePrice(float $priceUsd): float
    {
        $fcfa = $priceUsd * (float) $this->config['exchange_rate_usd_fcfa'];

        $withMargin = $fcfa * (1 + ($this->marginPercent() / 100));

        return (float) ceil($withMargin);
    }

    /**
     * The admin-configurable margin (settings table) takes priority over
     * SMSPOOL_MARGIN_PERCENT from .env, so a change in the admin panel takes effect
     * immediately without a deploy. Falls back to the env-sourced config value if no
     * admin override has ever been saved.
     */
    public function marginPercent(): float
    {
        return (float) Setting::get(Setting::MARGIN_PERCENT_KEY, $this->config['margin_percent']);
    }
}
