<?php

namespace Tests\Feature\Api;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardJsonTest extends TestCase
{
    use RefreshDatabase;

    public function test_requires_authentication(): void
    {
        $response = $this->getJson(route('api.dashboard.json'));

        $response->assertStatus(401);
    }

    public function test_matches_the_blade_dashboards_numbers_for_the_same_data(): void
    {
        $user = User::factory()->create(['balance' => 5000]);
        Order::factory()->for($user)->create(['price_fcfa' => 350, 'sms_code' => '123456']);
        Order::factory()->for($user)->create(['price_fcfa' => 400, 'sms_code' => null]);

        $otherUser = User::factory()->create(['balance' => 99999]);
        Order::factory()->for($otherUser)->count(3)->create(['price_fcfa' => 1000, 'sms_code' => '999999']);

        $bladeResponse = $this->actingAs($user)->get(route('dashboard'));
        $jsonResponse = $this->actingAs($user)->getJson(route('api.dashboard.json'));

        $bladeStats = $bladeResponse->viewData('stats');
        $jsonStats = $jsonResponse->json('stats');

        $this->assertSame($bladeStats['numbers_bought'], $jsonStats['numbers_bought']);
        $this->assertSame($bladeStats['sms_received'], $jsonStats['sms_received']);
        $this->assertEquals((float) $bladeStats['balance'], $jsonStats['balance']);
        $this->assertEquals((float) $bladeStats['total_spent'], $jsonStats['total_spent']);

        $jsonResponse->assertStatus(200);
        $this->assertCount(2, $jsonResponse->json('recent_orders'));
    }

    public function test_recent_orders_only_include_the_authenticated_users_orders(): void
    {
        $user = User::factory()->create();
        Order::factory()->for($user)->create(['phone' => '+225070000001']);

        $otherUser = User::factory()->create();
        Order::factory()->for($otherUser)->create(['phone' => '+225070000002']);

        $response = $this->actingAs($user)->getJson(route('api.dashboard.json'));

        $phones = collect($response->json('recent_orders'))->pluck('phone');

        $this->assertTrue($phones->contains('+225070000001'));
        $this->assertFalse($phones->contains('+225070000002'));
    }
}
