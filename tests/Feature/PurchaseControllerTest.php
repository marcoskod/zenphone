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

    public function test_status_endpoint_syncs_the_order_status_and_sms_code_from_5sim(): void
    {
        Http::fake([
            '*/user/check/*' => Http::response([
                'id' => 123456,
                'status' => 'RECEIVED',
                'phone' => '+79001234567',
                'sms' => [
                    ['sender' => 'WhatsApp', 'text' => 'Your code: 654321', 'code' => '654321'],
                ],
            ], 200),
        ]);

        $user = User::factory()->create();
        $order = Order::factory()->for($user)->create([
            'fivesim_order_id' => 123456,
            'status' => 'pending',
            'sms_code' => null,
        ]);

        $response = $this->actingAs($user)->getJson(route('api.orders.status', $order));

        $response->assertStatus(200);
        $response->assertJson(['status' => 'received', 'sms_code' => '654321']);

        $order->refresh();
        $this->assertSame('received', $order->status);
        $this->assertSame('654321', $order->sms_code);
    }

    public function test_status_endpoint_does_not_clobber_an_already_received_sms_code(): void
    {
        Http::fake([
            '*/user/check/*' => Http::response([
                'id' => 123456,
                'status' => 'RECEIVED',
                'sms' => [],
            ], 200),
        ]);

        $user = User::factory()->create();
        $order = Order::factory()->for($user)->create([
            'fivesim_order_id' => 123456,
            'status' => 'received',
            'sms_code' => '111111',
        ]);

        $this->actingAs($user)->getJson(route('api.orders.status', $order));

        $this->assertSame('111111', $order->refresh()->sms_code);
    }

    public function test_status_endpoint_is_forbidden_for_another_users_order(): void
    {
        $owner = User::factory()->create();
        $order = Order::factory()->for($owner)->create(['fivesim_order_id' => 123456]);

        $intruder = User::factory()->create();

        $response = $this->actingAs($intruder)->getJson(route('api.orders.status', $order));

        $response->assertStatus(403);
    }

    public function test_status_endpoint_returns_502_when_5sim_errors(): void
    {
        Http::fake([
            '*/user/check/*' => Http::response('order not found', 404),
        ]);

        $user = User::factory()->create();
        $order = Order::factory()->for($user)->create(['fivesim_order_id' => 123456, 'status' => 'pending']);

        $response = $this->actingAs($user)->getJson(route('api.orders.status', $order));

        $response->assertStatus(502);
        $this->assertSame('pending', $order->refresh()->status);
    }

    public function test_cancel_refunds_balance_and_marks_order_cancelled(): void
    {
        Http::fake([
            '*/user/cancel/*' => Http::response(['id' => 123456, 'status' => 'CANCELED'], 200),
        ]);

        $user = User::factory()->create(['balance' => 1000]);
        $order = Order::factory()->for($user)->create([
            'fivesim_order_id' => 123456,
            'status' => 'pending',
            'price_fcfa' => 420,
        ]);

        $response = $this->actingAs($user)->postJson(route('purchase.cancel', $order));

        $response->assertStatus(200);
        $response->assertJson(['status' => 'cancelled']);

        $this->assertSame('cancelled', $order->refresh()->status);
        $this->assertEquals(1420.0, (float) $user->fresh()->balance);
    }

    public function test_cancel_does_not_refund_when_5sim_does_not_confirm_cancellation(): void
    {
        Http::fake([
            '*/user/cancel/*' => Http::response(['id' => 123456, 'status' => 'PENDING'], 200),
        ]);

        $user = User::factory()->create(['balance' => 1000]);
        $order = Order::factory()->for($user)->create([
            'fivesim_order_id' => 123456,
            'status' => 'pending',
            'price_fcfa' => 420,
        ]);

        $response = $this->actingAs($user)->postJson(route('purchase.cancel', $order));

        $response->assertStatus(422);
        $this->assertSame('pending', $order->refresh()->status);
        $this->assertEquals(1000.0, (float) $user->fresh()->balance);
    }

    public function test_cancel_does_not_refund_when_5sim_rejects_the_cancellation(): void
    {
        Http::fake([
            '*/user/cancel/*' => Http::response('order has sms', 400),
        ]);

        $user = User::factory()->create(['balance' => 1000]);
        $order = Order::factory()->for($user)->create([
            'fivesim_order_id' => 123456,
            'status' => 'received',
            'price_fcfa' => 420,
        ]);

        $response = $this->actingAs($user)->postJson(route('purchase.cancel', $order));

        $response->assertStatus(502);
        $this->assertSame('received', $order->refresh()->status);
        $this->assertEquals(1000.0, (float) $user->fresh()->balance);
    }

    public function test_cancel_is_forbidden_for_another_users_order(): void
    {
        $owner = User::factory()->create();
        $order = Order::factory()->for($owner)->create(['fivesim_order_id' => 123456]);

        $intruder = User::factory()->create();

        $response = $this->actingAs($intruder)->postJson(route('purchase.cancel', $order));

        $response->assertStatus(403);
    }
}
