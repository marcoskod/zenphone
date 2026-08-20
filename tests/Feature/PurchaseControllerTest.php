<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use App\Services\PricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PurchaseControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_insufficient_balance_is_rejected_without_calling_the_5sim_purchase_endpoint(): void
    {
        Http::fake([
            '*/guest/products/*' => Http::response([
                'whatsapp' => ['Category' => 'activation', 'Qty' => 50, 'Price' => 100.0],
            ], 200),
            '*/user/buy/activation/*' => Http::response([
                'id' => 1, 'phone' => '+79000000000', 'status' => 'PENDING',
            ], 200),
        ]);

        $user = User::factory()->create(['balance' => 10]);

        $response = $this->actingAs($user)->post(route('purchase.store'), [
            'service' => 'whatsapp',
            'country' => 'russia',
        ]);

        $response->assertSessionHasErrors('balance');
        $this->assertSame(10.0, (float) $user->fresh()->balance);
        $this->assertDatabaseCount('orders', 0);

        Http::assertNotSent(fn ($request) => str_contains($request->url(), '/user/buy/activation'));
    }

    public function test_successful_purchase_creates_order_and_deducts_balance(): void
    {
        Http::fake([
            '*/guest/products/*' => Http::response([
                'whatsapp' => ['Category' => 'activation', 'Qty' => 50, 'Price' => 1.0],
            ], 200),
            '*/user/buy/activation/*' => Http::response([
                'id' => 123456,
                'phone' => '+79001234567',
                'operator' => 'any',
                'product' => 'whatsapp',
                'price' => 1.0,
                'status' => 'PENDING',
                'expires' => now()->addMinutes(15)->toIso8601String(),
                'country' => 'russia',
            ], 200),
        ]);

        $user = User::factory()->create(['balance' => 100000]);

        $response = $this->actingAs($user)->post(route('purchase.store'), [
            'service' => 'whatsapp',
            'country' => 'russia',
        ]);

        $this->assertDatabaseCount('orders', 1);

        $order = Order::first();

        $response->assertRedirect(route('purchase.waiting', $order));
        $this->assertSame($user->id, $order->user_id);
        $this->assertSame(123456, $order->fivesim_order_id);
        $this->assertSame('+79001234567', $order->phone);
        $this->assertSame('pending', $order->status);
        $this->assertNull($order->sms_code);

        $expectedPriceFcfa = app(PricingService::class)->calculatePrice(1.0);

        $this->assertEquals($expectedPriceFcfa, (float) $order->price_fcfa);
        $this->assertEquals(100000 - $expectedPriceFcfa, (float) $user->fresh()->balance);
    }

    public function test_failed_5sim_purchase_leaves_balance_untouched_and_creates_no_order(): void
    {
        Http::fake([
            '*/guest/products/*' => Http::response([
                'whatsapp' => ['Category' => 'activation', 'Qty' => 50, 'Price' => 1.0],
            ], 200),
            '*/user/buy/activation/*' => Http::response(['error' => 'no product'], 400),
        ]);

        $user = User::factory()->create(['balance' => 100000]);

        $response = $this->actingAs($user)->post(route('purchase.store'), [
            'service' => 'whatsapp',
            'country' => 'russia',
        ]);

        $response->assertSessionHasErrors();
        $this->assertSame(100000.0, (float) $user->fresh()->balance);
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_purchase_requires_service_and_country(): void
    {
        $user = User::factory()->create(['balance' => 100000]);

        $response = $this->actingAs($user)->post(route('purchase.store'), []);

        $response->assertSessionHasErrors(['service', 'country']);
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_unavailable_service_for_country_is_rejected(): void
    {
        Http::fake([
            '*/guest/products/*' => Http::response([
                'google' => ['Category' => 'activation', 'Qty' => 10, 'Price' => 1.0],
            ], 200),
        ]);

        $user = User::factory()->create(['balance' => 100000]);

        $response = $this->actingAs($user)->post(route('purchase.store'), [
            'service' => 'whatsapp',
            'country' => 'russia',
        ]);

        $response->assertSessionHasErrors('service');
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_waiting_page_shows_the_order_for_its_owner(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->for($user)->create();

        $response = $this->actingAs($user)->get(route('purchase.waiting', $order));

        $response->assertStatus(200);
        $response->assertSee($order->phone);
    }

    public function test_waiting_page_is_forbidden_for_another_users_order(): void
    {
        $owner = User::factory()->create();
        $order = Order::factory()->for($owner)->create();

        $intruder = User::factory()->create();

        $response = $this->actingAs($intruder)->get(route('purchase.waiting', $order));

        $response->assertStatus(403);
    }
}
