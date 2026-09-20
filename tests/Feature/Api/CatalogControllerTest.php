<?php

namespace Tests\Feature\Api;

use App\Services\PricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\FakesSmsPool;
use Tests\TestCase;

class CatalogControllerTest extends TestCase
{
    use FakesSmsPool;
    use RefreshDatabase;

    public function test_countries_carry_iso_and_a_starting_price_in_fcfa(): void
    {
        // WhatsApp (1012) costs $1.00 and Telegram (907) $2.00 in Benin (97) only.
        Http::fake($this->smsPoolStubs());

        $response = $this->getJson(route('api.countries'))->assertOk();

        $benin = collect($response->json('data'))->firstWhere('code', 'benin');
        $russia = collect($response->json('data'))->firstWhere('code', 'russia');

        $this->assertSame('BJ', $benin['iso']);
        $this->assertSame(app(PricingService::class)->calculatePrice(1.0), (float) $benin['from_price_fcfa']);
        $this->assertNull($russia['from_price_fcfa']); // nothing offered there: no price promised
    }

    public function test_countries_still_load_when_the_price_lookup_fails(): void
    {
        Http::fake($this->smsPoolStubs(['*/request/pricing' => Http::response('boom', 500)]));

        $data = $this->getJson(route('api.countries'))->assertOk()->json('data');

        $this->assertNotEmpty($data);
        $this->assertNull(collect($data)->firstWhere('code', 'benin')['from_price_fcfa']);
    }

    public function test_the_default_margin_triples_the_converted_price(): void
    {
        $service = new PricingService(['exchange_rate_usd_fcfa' => 600, 'margin_percent' => config('smspool.margin_percent')]);

        // $0.60 x 600 = 360 FCFA, tripled = 1080.
        $this->assertSame(1080.0, $service->calculatePrice(0.60));
        // No floating-point drift: 0.14 x 600 x 3 is exactly 252.
        $this->assertSame(252.0, $service->calculatePrice(0.14));
    }

    public function test_prices_never_fall_below_the_configured_floor(): void
    {
        $service = new PricingService(['exchange_rate_usd_fcfa' => 600, 'margin_percent' => 200, 'min_price_fcfa' => 100]);

        $this->assertSame(100.0, $service->calculatePrice(0.02)); // would be 36
        $this->assertSame(1080.0, $service->calculatePrice(0.60));
    }
}
