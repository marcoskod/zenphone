<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_sees_stats_for_their_own_orders_only(): void
    {
        $user = User::factory()->create(['balance' => 5000]);
        Order::factory()->for($user)->create(['price_fcfa' => 350, 'sms_code' => '123456']);
        Order::factory()->for($user)->create(['price_fcfa' => 400, 'sms_code' => null]);

        $otherUser = User::factory()->create(['balance' => 99999]);
        Order::factory()->for($otherUser)->count(3)->create(['price_fcfa' => 1000, 'sms_code' => '999999']);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertViewHas('stats', function (array $stats) {
            return $stats['numbers_bought'] === 2
                && $stats['sms_received'] === 1
                && (float) $stats['balance'] === 5000.0
                && (float) $stats['total_spent'] === 750.0;
        });
    }

    public function test_dashboard_shows_recent_orders_for_the_authenticated_user_only(): void
    {
        $user = User::factory()->create();
        Order::factory()->for($user)->create(['phone' => '+79005551234']);

        $otherUser = User::factory()->create();
        Order::factory()->for($otherUser)->create(['phone' => '+79009998888']);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertSee('+79005551234');
        $response->assertDontSee('+79009998888');
    }
}
