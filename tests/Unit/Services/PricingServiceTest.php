<?php

namespace Tests\Unit\Services;

use App\Services\PricingService;
use Tests\TestCase;

class PricingServiceTest extends TestCase
{
    public function test_calculate_price_applies_exchange_rate_and_margin(): void
    {
        $service = new PricingService([
            'exchange_rate_usd_fcfa' => 600,
            'margin_percent' => 20,
        ]);

        // 1 USD -> 600 FCFA, + 20% margin = 720 FCFA
        $this->assertSame(720.0, $service->calculatePrice(1.0));
    }

    public function test_calculate_price_with_zero_margin(): void
    {
        $service = new PricingService([
            'exchange_rate_usd_fcfa' => 600,
            'margin_percent' => 0,
        ]);

        $this->assertSame(1500.0, $service->calculatePrice(2.5));
    }

    public function test_calculate_price_rounds_to_two_decimals(): void
    {
        $service = new PricingService([
            'exchange_rate_usd_fcfa' => 601.5,
            'margin_percent' => 15,
        ]);

        $expected = round(2.5 * 601.5 * 1.15, 2);

        $this->assertSame($expected, $service->calculatePrice(2.5));
    }

    public function test_pricing_service_resolves_from_the_container_using_fivesim_config(): void
    {
        config([
            'fivesim.exchange_rate_usd_fcfa' => 600,
            'fivesim.margin_percent' => 20,
        ]);

        $service = app(PricingService::class);

        $this->assertSame(720.0, $service->calculatePrice(1.0));
    }
}
