<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\PendingPurchase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\FakesSmsPool;
use Tests\TestCase;

class ProductionReadinessTest extends TestCase
{
    use FakesSmsPool;
    use RefreshDatabase;

    private function pending(User $user, array $overrides = []): PendingPurchase
    {
        return PendingPurchase::create($overrides + [
            'user_id' => $user->id,
            'service' => 'whatsapp',
            'country' => 'benin',
            'price_fcfa' => 700,
            'status' => 'awaiting_payment',
        ]);
    }

    private function fakeBuy(): void
    {
        Http::fake($this->smsPoolStubs());
    }

    private function orderFor(User $user, array $overrides = []): Order
    {
        return Order::create($overrides + [
            'user_id' => $user->id,
            'provider_order_id' => 'ORD'.random_int(1000, 999999),
            'service' => 'whatsapp',
            'country' => 'benin',
            'phone' => '+22961000000',
            'price_fcfa' => 500,
            'status' => 'pending',
            'expires_at' => now()->subMinutes(10),
        ]);
    }

    // ── FedaPay webhook ──

    public function test_webhook_delivers_the_number_when_the_browser_never_confirmed(): void
    {
        Http::fake([
            '*/transactions/*' => Http::response(['id' => 42, 'status' => 'approved', 'amount' => 700, 'custom_metadata' => ['pending_purchase_id' => 1]], 200),
            ...$this->smsPoolStubs(),
        ]);

        $user = User::factory()->create();
        $pending = $this->pending($user);

        $this->postJson('/webhooks/fedapay', ['name' => 'transaction.approved', 'entity' => ['id' => 42]])
            ->assertOk();

        $this->assertSame('paid', $pending->fresh()->status);
        $this->assertDatabaseCount('orders', 1);
    }

    public function test_webhook_and_browser_confirm_together_buy_only_once(): void
    {
        Http::fake([
            '*/transactions/*' => Http::response(['id' => 42, 'status' => 'approved', 'amount' => 700, 'custom_metadata' => ['pending_purchase_id' => 1]], 200),
            ...$this->smsPoolStubs(),
        ]);

        $user = User::factory()->create();
        $pending = $this->pending($user);

        $this->postJson('/webhooks/fedapay', ['name' => 'transaction.approved', 'entity' => ['id' => 42]]);
        $this->actingAs($user)->postJson(route('api.purchase.pay-confirm'), [
            'pending_purchase_id' => $pending->id, 'transaction_id' => '42',
        ])->assertOk();

        $this->assertDatabaseCount('orders', 1);
        Http::assertSent(fn ($r) => str_contains($r->url(), '/purchase/sms'));
        $buys = Http::recorded(fn ($r) => str_contains($r->url(), '/purchase/sms'));
        $this->assertCount(1, $buys);
    }

    public function test_webhook_ignores_unrelated_events_and_unknown_transactions(): void
    {
        Http::fake(['*/transactions/*' => Http::response(['id' => 9, 'status' => 'approved', 'amount' => 100], 200)]);

        $this->postJson('/webhooks/fedapay', ['name' => 'transaction.declined', 'entity' => ['id' => 9]])->assertOk();
        $this->postJson('/webhooks/fedapay', ['name' => 'transaction.approved', 'entity' => ['id' => 9]])->assertOk();

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_webhook_asks_fedapay_to_retry_when_verification_fails(): void
    {
        Http::fake(['*/transactions/*' => Http::response('boom', 500)]);

        $this->postJson('/webhooks/fedapay', ['name' => 'transaction.approved', 'entity' => ['id' => 9]])
            ->assertStatus(503);
    }

    public function test_a_forged_webhook_cannot_deliver_an_unpaid_purchase(): void
    {
        Http::fake(['*/transactions/*' => Http::response(['id' => 42, 'status' => 'pending', 'amount' => 700, 'custom_metadata' => ['pending_purchase_id' => 1]], 200)]);

        $user = User::factory()->create();
        $pending = $this->pending($user);

        $this->postJson('/webhooks/fedapay', ['name' => 'transaction.approved', 'entity' => ['id' => 42]])->assertOk();

        $this->assertSame('awaiting_payment', $pending->fresh()->status);
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_someone_elses_transaction_cannot_pay_for_my_purchase(): void
    {
        $this->fakeBuy();
        Http::fake([
            '*/transactions/*' => Http::response(['id' => 42, 'status' => 'approved', 'amount' => 5000, 'custom_metadata' => ['pending_purchase_id' => 999]], 200),
        ]);

        $user = User::factory()->create();
        $pending = $this->pending($user);

        $this->actingAs($user)->postJson(route('api.purchase.pay-confirm'), [
            'pending_purchase_id' => $pending->id, 'transaction_id' => '42',
        ])->assertStatus(422);

        $this->assertDatabaseCount('orders', 0);
    }

    // ── Automatic refunds ──

    public function test_status_poll_refunds_a_timed_out_order_exactly_once(): void
    {
        Http::fake($this->smsPoolStubs(['*/sms/check' => $this->smsPoolCheck(2)]));

        $user = User::factory()->create(['balance' => 0]);
        $order = $this->orderFor($user);

        $this->actingAs($user)->getJson(route('api.orders.status', $order))->assertOk();
        $this->actingAs($user)->getJson(route('api.orders.status', $order))->assertOk();

        $this->assertEquals(500.0, (float) $user->fresh()->balance);
        $this->assertNotNull($order->fresh()->refunded_at);
    }

    public function test_cancel_refunds_an_already_expired_order_instead_of_erroring(): void
    {
        Http::fake($this->smsPoolStubs([
            '*/sms/cancel' => $this->smsPoolCancelRefused('This order has expired.'),
            '*/sms/check' => $this->smsPoolCheck(2),
        ]));

        $user = User::factory()->create(['balance' => 0]);
        $order = $this->orderFor($user);

        $this->actingAs($user)->postJson(route('purchase.cancel', $order))->assertOk();

        $this->assertEquals(500.0, (float) $user->fresh()->balance);
    }

    public function test_an_order_that_received_an_sms_is_never_refunded(): void
    {
        Http::fake($this->smsPoolStubs([
            '*/sms/cancel' => $this->smsPoolCancelRefused(),
            '*/sms/check' => $this->smsPoolCheck(3, '123456'),
        ]));

        $user = User::factory()->create(['balance' => 0]);
        $order = $this->orderFor($user);

        $this->actingAs($user)->postJson(route('purchase.cancel', $order))->assertStatus(502);

        $this->assertEquals(0.0, (float) $user->fresh()->balance);
    }

    public function test_sweeper_refunds_expired_orders_and_skips_ones_that_got_an_sms(): void
    {
        Notification::fake();

        $user = User::factory()->create(['balance' => 0]);
        $dead = $this->orderFor($user, ['provider_order_id' => 'ORD111']);
        $lucky = $this->orderFor($user, ['provider_order_id' => 'ORD222', 'price_fcfa' => 900]);
        $fresh = $this->orderFor($user, ['provider_order_id' => 'ORD333', 'expires_at' => now()->addMinutes(5)]);

        // SMSPool identifies the order by the `orderid` POST param, not by the URL.
        Http::fake($this->smsPoolStubs([
            '*/sms/check' => fn ($request) => Http::response(match ($request['orderid']) {
                'ORD111' => ['status' => 2, 'expiration' => now()->timestamp],
                'ORD222' => ['status' => 3, 'sms' => '999888', 'full_sms' => 'Your code is 999888'],
                default => ['status' => 1],
            }, 200),
        ]));

        $this->artisan('orders:sweep-expired')->assertSuccessful();
        $this->artisan('orders:sweep-expired')->assertSuccessful();

        $this->assertEquals(500.0, (float) $user->fresh()->balance);
        $this->assertNotNull($dead->fresh()->refunded_at);
        $this->assertNull($lucky->fresh()->refunded_at);
        $this->assertSame('999888', $lucky->fresh()->sms_code);
        $this->assertNull($fresh->fresh()->refunded_at);
    }

    // ── Hardening ──

    public function test_quick_auth_is_rate_limited(): void
    {
        User::factory()->create(['email' => 'victim@example.com']);

        for ($i = 0; $i < 5; $i++) {
            $this->postJson(route('api.auth.quick'), ['email' => 'victim@example.com', 'password' => 'wrong-password'])
                ->assertStatus(422);
        }

        $this->postJson(route('api.auth.quick'), ['email' => 'victim@example.com', 'password' => 'wrong-password'])
            ->assertStatus(429);
    }

    public function test_security_headers_are_sent(): void
    {
        $this->get('/')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function test_a_suspended_account_is_cut_off_mid_session(): void
    {
        $user = User::factory()->create(['is_suspended' => true]);

        $this->actingAs($user)->postJson(route('api.purchase.pay-init'), ['service' => 'whatsapp', 'country' => 'benin'])
            ->assertStatus(403);
    }

    public function test_the_old_public_topup_endpoint_is_gone(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/api/topup/confirm', ['transaction_id' => '1'])->assertStatus(404);
    }

    public function test_preflight_fails_in_a_default_local_setup_and_make_admin_promotes(): void
    {
        $this->artisan('zensms:preflight')->assertFailed();

        $user = User::factory()->create();
        $this->artisan('zensms:make-admin', ['email' => $user->email])->assertSuccessful();
        $this->assertTrue($user->fresh()->is_admin);

        $this->artisan('zensms:make-admin', ['email' => 'nobody@example.com'])->assertFailed();
    }
}
