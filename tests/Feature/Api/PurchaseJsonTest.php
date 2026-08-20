<?php

namespace Tests\Feature\Api;

use App\Models\Order;
use App\Models\User;
use App\Services\PricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PurchaseJsonTest extends TestCase
{
    use RefreshDatabase;

    public function test_requires_authentication(): void
    {
        $response = $this->postJson(route('api.purchase.json'), [
            'service' => 'whatsapp',
            'country' => 'russia',
        ]);

        $response->assertStatus(401);
    }

    public function test_insufficient_balance_returns_422_without_calling_the_5sim_purchase_endpoint(): void
    {
        Http::fake([
            '*/guest/products/*' => Http::response([
                'whatsapp' => ['Category' => 'activation', 'Qty' => 50, 'Price' => 100.0],
            ], 200),
            '*/user/buy/activation/*' => Http::response(['id' => 1, 'phone' => '+79000000000', 'status' => 'PENDING'], 200),
        ]);

        $user = User::factory()->create(['balance' => 10]);

        $response = $this->actingAs($user)->postJson(route('api.purchase.json'), [
            'service' => 'whatsapp',
            'country' => 'russia',
        ]);

        $response->assertStatus(422);
        $response->assertJson(['field' => 'balance']);

        $this->assertSame(10.0, (float) $user->fresh()->balance);
        $this->assertDatabaseCount('orders', 0);

        Http::assertNotSent(fn ($request) => str_contains($request->url(), '/user/buy/activation'));
    }

    public function test_successful_purchase_returns_order_and_waiting_url(): void
    {
        Http::fake([
            '*/guest/products/*' => Http::response([
                'whatsapp' => ['Category' => 'activation', 'Qty' => 50, 'Price' => 1.0],
            ], 200),
            '*/user/buy/activation/*' => Http::response([
                'id' => 123456,
                'phone' => '+79001234567',
                'status' => 'PENDING',
                'expires' => now()->addMinutes(15)->toIso8601String(),
            ], 200),
        ]);

        $user = User::factory()->create(['balance' => 100000]);

        $response = $this->actingAs($user)->postJson(route('api.purchase.json'), [
            'service' => 'whatsapp',
            'country' => 'russia',
        ]);

        $order = Order::first();

        $response->assertStatus(200);
        $response->assertJson([
            'order_id' => $order->id,
            'phone' => '+79001234567',
            'status' => 'pending',
            'waiting_url' => route('purchase.waiting', $order),
        ]);

        $expectedPriceFcfa = app(PricingService::class)->calculatePrice(1.0);
        $this->assertEquals(100000 - $expectedPriceFcfa, (float) $user->fresh()->balance);
    }

    public function test_failed_5sim_purchase_returns_502_and_touches_nothing(): void
    {
        Http::fake([
            '*/guest/products/*' => Http::response([
                'whatsapp' => ['Category' => 'activation', 'Qty' => 50, 'Price' => 1.0],
            ], 200),
            '*/user/buy/activation/*' => Http::response(['error' => 'no product'], 400),
        ]);

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
