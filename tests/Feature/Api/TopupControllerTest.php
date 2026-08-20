<?php

namespace Tests\Feature\Api;

use App\Models\Topup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TopupControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_confirm_credits_balance_when_fedapay_approves_the_transaction(): void
    {
        Http::fake([
            '*/transactions/*' => Http::response([
                'id' => 12345,
                'status' => 'approved',
                'amount' => 5000,
            ], 200),
        ]);

        $user = User::factory()->create(['balance' => 1000]);

        $response = $this->actingAs($user)->postJson(route('api.topup.confirm'), [
            'transaction_id' => '12345',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'confirmed', 'balance' => 6000.0]);

        $this->assertEquals(6000.0, (float) $user->fresh()->balance);
        $this->assertDatabaseHas('topups', [
            'user_id' => $user->id,
            'external_reference' => '12345',
            'status' => 'confirmed',
            'amount_fcfa' => 5000,
        ]);
    }

    public function test_confirm_does_not_credit_balance_when_fedapay_status_is_pending(): void
    {
        Http::fake([
            '*/transactions/*' => Http::response([
                'id' => 12345,
                'status' => 'pending',
                'amount' => 5000,
            ], 200),
        ]);

        $user = User::factory()->create(['balance' => 1000]);

        $response = $this->actingAs($user)->postJson(route('api.topup.confirm'), [
            'transaction_id' => '12345',
        ]);

        $response->assertStatus(422);
        $this->assertEquals(1000.0, (float) $user->fresh()->balance);
        $this->assertDatabaseCount('topups', 0);
    }

    public function test_confirm_does_not_credit_balance_when_fedapay_verification_fails(): void
    {
        Http::fake([
            '*/transactions/*' => Http::response('transaction not found', 404),
        ]);

        $user = User::factory()->create(['balance' => 1000]);

        $response = $this->actingAs($user)->postJson(route('api.topup.confirm'), [
            'transaction_id' => '99999',
        ]);

        $response->assertStatus(502);
        $this->assertEquals(1000.0, (float) $user->fresh()->balance);
        $this->assertDatabaseCount('topups', 0);
    }

    public function test_confirm_is_idempotent_and_never_double_credits(): void
    {
        Http::fake([
            '*/transactions/*' => Http::response([
                'id' => 12345,
                'status' => 'approved',
                'amount' => 5000,
            ], 200),
        ]);

        $user = User::factory()->create(['balance' => 1000]);

        $first = $this->actingAs($user)->postJson(route('api.topup.confirm'), ['transaction_id' => '12345']);
        $first->assertStatus(200);

        $second = $this->actingAs($user)->postJson(route('api.topup.confirm'), ['transaction_id' => '12345']);
        $second->assertStatus(200);

        $this->assertEquals(6000.0, (float) $user->fresh()->balance);
        $this->assertDatabaseCount('topups', 1);

        // The second call should not have re-verified with FedaPay at all.
        Http::assertSentCount(1);
    }

    public function test_confirm_requires_authentication(): void
    {
        $response = $this->postJson(route('api.topup.confirm'), ['transaction_id' => '12345']);

        $response->assertStatus(401);
    }
}
