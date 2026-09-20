<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use App\Notifications\SmsReceived;
use App\Services\PricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\FakesSmsPool;
use Tests\TestCase;

class PurchaseControllerTest extends TestCase
{
    use FakesSmsPool;
    use RefreshDatabase;

    public function test_insufficient_balance_is_rejected_without_calling_the_supplier_purchase_endpoint(): void
    {
        Http::fake($this->smsPoolStubs([
            '*/request/pricing' => $this->smsPoolPricing(100.0),
        ]));

        $user = User::factory()->create(['balance' => 10]);

        $response = $this->actingAs($user)->post(route('purchase.store'), [
            'service' => 'whatsapp',
            'country' => 'russia',
        ]);

        $response->assertSessionHasErrors('balance');
        $this->assertSame(10.0, (float) $user->fresh()->balance);
        $this->assertDatabaseCount('orders', 0);

        Http::assertNotSent(fn ($request) => str_contains($request->url(), '/purchase/sms'));
    }

    public function test_successful_purchase_creates_order_and_deducts_balance(): void
    {
        Http::fake($this->smsPoolStubs([
            '*/request/pricing' => $this->smsPoolPricing(1.0),
        ]));

        $user = User::factory()->create(['balance' => 100000]);

        $response = $this->actingAs($user)->post(route('purchase.store'), [
            'service' => 'whatsapp',
            'country' => 'russia',
        ]);

        $this->assertDatabaseCount('orders', 1);

        $order = Order::first();

        $response->assertRedirect(route('purchase.waiting', $order));
        $this->assertSame($user->id, $order->user_id);
        $this->assertSame('ABC12345', $order->provider_order_id);
        $this->assertSame('+22961234567', $order->phone);
        $this->assertSame('pending', $order->status);
        $this->assertNull($order->sms_code);

        $expectedPriceFcfa = app(PricingService::class)->calculatePrice(1.0);

        $this->assertEquals($expectedPriceFcfa, (float) $order->price_fcfa);
        $this->assertEquals(100000 - $expectedPriceFcfa, (float) $user->fresh()->balance);
    }

    public function test_failed_supplier_purchase_leaves_balance_untouched_and_creates_no_order(): void
    {
        Http::fake($this->smsPoolStubs([
            '*/purchase/sms' => $this->smsPoolOutOfStock(),
        ]));

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
        // Only Telegram (907) is priced for this country; WhatsApp is not offered.
        Http::fake($this->smsPoolStubs([
            '*/request/pricing' => Http::response([
                ['service' => 907, 'service_name' => 'Telegram', 'country' => 97, 'country_name' => 'Benin', 'short_name' => 'BJ', 'pool' => 3, 'price' => '1.00'],
            ], 200),
        ]));

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

    public function test_status_endpoint_syncs_the_order_status_and_sms_code_from_the_supplier(): void
    {
        Http::fake($this->smsPoolStubs([
            '*/sms/check' => $this->smsPoolCheck(3, '654321'),
        ]));

        $user = User::factory()->create();
        $order = Order::factory()->for($user)->create([
            'provider_order_id' => 'ABC12345',
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
        Http::fake($this->smsPoolStubs([
            '*/sms/check' => $this->smsPoolCheck(3),
        ]));

        $user = User::factory()->create();
        $order = Order::factory()->for($user)->create([
            'provider_order_id' => 'ABC12345',
            'status' => 'received',
            'sms_code' => '111111',
        ]);

        $this->actingAs($user)->getJson(route('api.orders.status', $order));

        $this->assertSame('111111', $order->refresh()->sms_code);
    }

    public function test_status_endpoint_sends_sms_received_notification_exactly_once_on_first_poll_with_a_code(): void
    {
        Notification::fake();

        Http::fake($this->smsPoolStubs([
            '*/sms/check' => $this->smsPoolCheck(3, '654321'),
        ]));

        $user = User::factory()->create();
        $order = Order::factory()->for($user)->create([
            'provider_order_id' => 'ABC12345',
            'status' => 'pending',
            'sms_code' => null,
        ]);

        $this->actingAs($user)->getJson(route('api.orders.status', $order));

        Notification::assertSentTimes(SmsReceived::class, 1);
        Notification::assertSentTo($user, SmsReceived::class, function (SmsReceived $notification) {
            $data = $notification->toArray($notification);

            return $data['sms_code'] === '654321';
        });
    }

    public function test_status_endpoint_does_not_resend_sms_received_notification_on_a_later_poll(): void
    {
        Notification::fake();

        Http::fake($this->smsPoolStubs([
            '*/sms/check' => $this->smsPoolCheck(3, '654321'),
        ]));

        $user = User::factory()->create();
        $order = Order::factory()->for($user)->create([
            'provider_order_id' => 'ABC12345',
            'status' => 'pending',
            'sms_code' => null,
        ]);

        // First poll: code just arrived, notification should fire.
        $this->actingAs($user)->getJson(route('api.orders.status', $order));
        // Second poll: code already present, must not fire again.
        $this->actingAs($user)->getJson(route('api.orders.status', $order));

        Notification::assertSentTimes(SmsReceived::class, 1);
    }

    public function test_status_endpoint_does_not_send_sms_received_notification_when_no_code_is_present(): void
    {
        Notification::fake();

        Http::fake($this->smsPoolStubs([
            '*/sms/check' => $this->smsPoolCheck(1),
        ]));

        $user = User::factory()->create();
        $order = Order::factory()->for($user)->create([
            'provider_order_id' => 'ABC12345',
            'status' => 'pending',
            'sms_code' => null,
        ]);

        $this->actingAs($user)->getJson(route('api.orders.status', $order));

        Notification::assertNothingSent();
    }

    public function test_status_endpoint_is_forbidden_for_another_users_order(): void
    {
        $owner = User::factory()->create();
        $order = Order::factory()->for($owner)->create(['provider_order_id' => 'ABC12345']);

        $intruder = User::factory()->create();

        $response = $this->actingAs($intruder)->getJson(route('api.orders.status', $order));

        $response->assertStatus(403);
    }

    public function test_status_endpoint_returns_502_when_the_supplier_errors(): void
    {
        Http::fake($this->smsPoolStubs([
            '*/sms/check' => Http::response('order not found', 404),
        ]));

        $user = User::factory()->create();
        $order = Order::factory()->for($user)->create(['provider_order_id' => 'ABC12345', 'status' => 'pending']);

        $response = $this->actingAs($user)->getJson(route('api.orders.status', $order));

        $response->assertStatus(502);
        $this->assertSame('pending', $order->refresh()->status);
    }

    public function test_cancel_refunds_balance_and_marks_order_cancelled(): void
    {
        Http::fake($this->smsPoolStubs());

        $user = User::factory()->create(['balance' => 1000]);
        $order = Order::factory()->for($user)->create([
            'provider_order_id' => 'ABC12345',
            'status' => 'pending',
            'price_fcfa' => 420,
        ]);

        $response = $this->actingAs($user)->postJson(route('purchase.cancel', $order));

        $response->assertStatus(200);
        $response->assertJson(['status' => 'cancelled']);

        $this->assertSame('cancelled', $order->refresh()->status);
        $this->assertEquals(1420.0, (float) $user->fresh()->balance);
    }

    public function test_cancel_does_not_refund_when_the_supplier_refuses_to_cancel_an_active_order(): void
    {
        // Cancellation refused, and the order is still live (pending, no SMS) on the supplier side.
        Http::fake($this->smsPoolStubs([
            '*/sms/cancel' => $this->smsPoolCancelRefused(),
            '*/sms/check' => $this->smsPoolCheck(1),
        ]));

        $user = User::factory()->create(['balance' => 1000]);
        $order = Order::factory()->for($user)->create([
            'provider_order_id' => 'ABC12345',
            'status' => 'pending',
            'price_fcfa' => 420,
        ]);

        $response = $this->actingAs($user)->postJson(route('purchase.cancel', $order));

        $response->assertStatus(502);
        $this->assertSame('pending', $order->refresh()->status);
        $this->assertEquals(1000.0, (float) $user->fresh()->balance);
    }

    public function test_cancel_does_not_refund_when_the_supplier_rejects_the_cancellation(): void
    {
        Http::fake($this->smsPoolStubs([
            '*/sms/cancel' => $this->smsPoolCancelRefused(),
            '*/sms/check' => $this->smsPoolCheck(3, '123456'),
        ]));

        $user = User::factory()->create(['balance' => 1000]);
        $order = Order::factory()->for($user)->create([
            'provider_order_id' => 'ABC12345',
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
        $order = Order::factory()->for($owner)->create(['provider_order_id' => 'ABC12345']);

        $intruder = User::factory()->create();

        $response = $this->actingAs($intruder)->postJson(route('purchase.cancel', $order));

        $response->assertStatus(403);
    }
}
