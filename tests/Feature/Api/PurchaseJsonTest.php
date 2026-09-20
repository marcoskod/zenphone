<?php

namespace Tests\Feature\Api;

use App\Models\Order;
use App\Models\User;
use App\Services\PricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\FakesSmsPool;
use Tests\TestCase;

class PurchaseJsonTest extends TestCase
{
    use FakesSmsPool;
    use RefreshDatabase;

    public function test_requires_authentication(): void
    {
        $response = $this->postJson(route('api.purchase.json'), [
            'service' => 'whatsapp',
            'country' => 'russia',
        ]);

        $response->assertStatus(401);
    }

    public function test_insufficient_balance_returns_422_without_calling_the_supplier_purchase_endpoint(): void
    {
        Http::fake($this->smsPoolStubs([
            '*/request/pricing' => $this->smsPoolPricing(100.0),
        ]));

        $user = User::factory()->create(['balance' => 10]);

        $response = $this->actingAs($user)->postJson(route('api.purchase.json'), [
            'service' => 'whatsapp',
            'country' => 'russia',
        ]);

        $response->assertStatus(422);
        $response->assertJson(['field' => 'balance']);

        $this->assertSame(10.0, (float) $user->fresh()->balance);
        $this->assertDatabaseCount('orders', 0);

        Http::assertNotSent(fn ($request) => str_contains($request->url(), '/purchase/sms'));
    }

    public function test_successful_purchase_returns_order_and_waiting_url(): void
    {
        Http::fake($this->smsPoolStubs([
            '*/request/pricing' => $this->smsPoolPricing(1.0),
        ]));

        $user = User::factory()->create(['balance' => 100000]);

        $response = $this->actingAs($user)->postJson(route('api.purchase.json'), [
            'service' => 'whatsapp',
            'country' => 'russia',
        ]);

        $order = Order::first();

        $response->assertStatus(200);
        $response->assertJson([
            'order_id' => $order->id,
            'phone' => '+22961234567',
            'status' => 'pending',
            'waiting_url' => route('purchase.waiting', $order),
        ]);

        $expectedPriceFcfa = app(PricingService::class)->calculatePrice(1.0);
        $this->assertEquals(100000 - $expectedPriceFcfa, (float) $user->fresh()->balance);
    }

    public function test_failed_supplier_purchase_returns_502_and_touches_nothing(): void
    {
        Http::fake($this->smsPoolStubs([
            '*/purchase/sms' => $this->smsPoolOutOfStock(),
        ]));

        $user = User::factory()->create(['balance' => 100000]);

        $response = $this->actingAs($user)->postJson(route('api.purchase.json'), [
            'service' => 'whatsapp',
            'country' => 'russia',
        ]);

        $response->assertStatus(502);
        $this->assertSame(100000.0, (float) $user->fresh()->balance);
        $this->assertDatabaseCount('orders', 0);
    }
}
