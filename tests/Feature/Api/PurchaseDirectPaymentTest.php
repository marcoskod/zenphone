<?php

namespace Tests\Feature\Api;

use App\Models\Order;
use App\Models\PendingPurchase;
use App\Models\User;
use App\Services\PricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\FakesSmsPool;
use Tests\TestCase;

class PurchaseDirectPaymentTest extends TestCase
{
    use FakesSmsPool;
    use RefreshDatabase;

    private function fakeSupplier(float $priceUsd = 1.0): void
    {
        Http::fake($this->smsPoolStubs([
            '*/request/pricing' => $this->smsPoolPricing($priceUsd),
            '*/transactions/*' => Http::response(['id' => 999, 'status' => 'approved', 'amount' => 1_000_000], 200),
        ]));
    }

    public function test_pay_init_requires_authentication(): void
    {
        $response = $this->postJson(route('api.purchase.pay-init'), [
            'service' => 'whatsapp',
            'country' => 'russia',
        ]);

        $response->assertStatus(401);
    }

    public function test_pay_init_buys_immediately_when_balance_covers_the_price(): void
    {
        $this->fakeSupplier();

        $user = User::factory()->create(['balance' => 100000]);

        $response = $this->actingAs($user)->postJson(route('api.purchase.pay-init'), [
            'service' => 'whatsapp',
            'country' => 'russia',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['paid_with' => 'balance', 'phone' => '+22961234567']);

        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('pending_purchases', 0);

        $expectedPriceFcfa = app(PricingService::class)->calculatePrice(1.0);
        $this->assertEquals(100000 - $expectedPriceFcfa, (float) $user->fresh()->balance);
    }

    public function test_pay_init_creates_a_pending_purchase_and_returns_fedapay_details_when_balance_is_insufficient(): void
    {
        $this->fakeSupplier();

        $user = User::factory()->create(['balance' => 0]);

        $response = $this->actingAs($user)->postJson(route('api.purchase.pay-init'), [
            'service' => 'whatsapp',
            'country' => 'russia',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['paid_with' => 'fedapay']);
        $response->assertJsonStructure(['pending_purchase_id', 'amount', 'description']);

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseHas('pending_purchases', [
            'user_id' => $user->id,
            'service' => 'whatsapp',
            'country' => 'russia',
            'status' => 'awaiting_payment',
        ]);

        Http::assertNotSent(fn ($request) => str_contains($request->url(), '/purchase/sms'));
    }

    public function test_pay_confirm_buys_the_activation_and_creates_the_order_once_fedapay_approves(): void
    {
        $this->fakeSupplier();

        $user = User::factory()->create(['balance' => 0]);

        $pending = PendingPurchase::create([
            'user_id' => $user->id,
            'service' => 'whatsapp',
            'country' => 'russia',
            'price_fcfa' => 700,
            'status' => 'awaiting_payment',
        ]);

        $response = $this->actingAs($user)->postJson(route('api.purchase.pay-confirm'), [
            'pending_purchase_id' => $pending->id,
            'transaction_id' => '999',
        ]);

        $order = Order::first();

        $response->assertStatus(200);
        $response->assertJson([
            'order_id' => $order->id,
            'phone' => '+22961234567',
            'status' => 'pending',
        ]);

        $this->assertSame('paid', $pending->fresh()->status);
        $this->assertSame($order->id, $pending->fresh()->order_id);
        // Paid directly via FedaPay: balance must stay untouched.
        $this->assertEquals(0.0, (float) $user->fresh()->balance);
    }

    public function test_pay_confirm_is_idempotent_and_never_buys_twice(): void
    {
        $this->fakeSupplier();

        $user = User::factory()->create(['balance' => 0]);

        $pending = PendingPurchase::create([
            'user_id' => $user->id,
            'service' => 'whatsapp',
            'country' => 'russia',
            'price_fcfa' => 700,
            'status' => 'awaiting_payment',
        ]);

        $first = $this->actingAs($user)->postJson(route('api.purchase.pay-confirm'), [
            'pending_purchase_id' => $pending->id,
            'transaction_id' => '999',
        ]);
        $first->assertStatus(200);

        $second = $this->actingAs($user)->postJson(route('api.purchase.pay-confirm'), [
            'pending_purchase_id' => $pending->id,
            'transaction_id' => '999',
        ]);
        $second->assertStatus(200);

        $this->assertDatabaseCount('orders', 1);
        // The supplier is asked to sell a number exactly once, on the first call only.
        $this->assertCount(1, Http::recorded(fn ($request) => str_contains($request->url(), '/purchase/sms')));
    }

    public function test_pay_confirm_rejects_an_unapproved_transaction(): void
    {
        Http::fake([
            '*/transactions/*' => Http::response(['id' => 999, 'status' => 'pending', 'amount' => 1_000_000], 200),
        ]);

        $user = User::factory()->create(['balance' => 0]);

        $pending = PendingPurchase::create([
            'user_id' => $user->id,
            'service' => 'whatsapp',
            'country' => 'russia',
            'price_fcfa' => 700,
            'status' => 'awaiting_payment',
        ]);

        $response = $this->actingAs($user)->postJson(route('api.purchase.pay-confirm'), [
            'pending_purchase_id' => $pending->id,
            'transaction_id' => '999',
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseCount('orders', 0);
        $this->assertSame('awaiting_payment', $pending->fresh()->status);
    }

    public function test_pay_confirm_refunds_balance_and_marks_the_pending_purchase_failed_when_the_supplier_purchase_fails(): void
    {
        Http::fake($this->smsPoolStubs([
            '*/transactions/*' => Http::response(['id' => 999, 'status' => 'approved', 'amount' => 1_000_000], 200),
            '*/purchase/sms' => $this->smsPoolOutOfStock(),
        ]));

        $user = User::factory()->create(['balance' => 0]);

        $pending = PendingPurchase::create([
            'user_id' => $user->id,
            'service' => 'whatsapp',
            'country' => 'russia',
            'price_fcfa' => 700,
            'status' => 'awaiting_payment',
        ]);

        $response = $this->actingAs($user)->postJson(route('api.purchase.pay-confirm'), [
            'pending_purchase_id' => $pending->id,
            'transaction_id' => '999',
        ]);

        $response->assertStatus(502);
        $this->assertDatabaseCount('orders', 0);
        $this->assertSame('failed', $pending->fresh()->status);
        $this->assertEquals(700.0, (float) $user->fresh()->balance);
    }

    public function test_pay_confirm_rejects_a_transaction_already_used_by_another_pending_purchase(): void
    {
        $this->fakeSupplier();

        $user = User::factory()->create(['balance' => 0]);

        PendingPurchase::create([
            'user_id' => $user->id,
            'service' => 'telegram',
            'country' => 'russia',
            'price_fcfa' => 700,
            'status' => 'paid',
            'fedapay_transaction_id' => '999',
        ]);

        $pending = PendingPurchase::create([
            'user_id' => $user->id,
            'service' => 'whatsapp',
            'country' => 'russia',
            'price_fcfa' => 700,
            'status' => 'awaiting_payment',
        ]);

        $response = $this->actingAs($user)->postJson(route('api.purchase.pay-confirm'), [
            'pending_purchase_id' => $pending->id,
            'transaction_id' => '999',
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_pay_confirm_requires_ownership_of_the_pending_purchase(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();

        $pending = PendingPurchase::create([
            'user_id' => $owner->id,
            'service' => 'whatsapp',
            'country' => 'russia',
            'price_fcfa' => 700,
            'status' => 'awaiting_payment',
        ]);

        $response = $this->actingAs($intruder)->postJson(route('api.purchase.pay-confirm'), [
            'pending_purchase_id' => $pending->id,
            'transaction_id' => '999',
        ]);

        $response->assertStatus(404);
    }
}
