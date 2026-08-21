<?php

namespace Tests\Feature\Admin;

use App\Models\Order;
use App\Models\Topup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_cannot_access_transactions(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('admin.transactions'));

        $response->assertStatus(403);
    }

    public function test_admin_sees_all_users_orders_and_topups(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $userA = User::factory()->create(['email' => 'a@example.com']);
        $userB = User::factory()->create(['email' => 'b@example.com']);

        Order::factory()->for($userA)->create(['phone' => '+225070000001']);
        Order::factory()->for($userB)->create(['phone' => '+225070000002']);
        Topup::factory()->for($userA)->create(['amount_fcfa' => 1234]);

        $response = $this->actingAs($admin)->get(route('admin.transactions'));

        $response->assertStatus(200);
        $response->assertSee('+225070000001');
        $response->assertSee('+225070000002');
        $response->assertSee('1 234 FCFA');
    }

    public function test_transactions_can_be_filtered_by_user(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $userA = User::factory()->create(['email' => 'findme@example.com']);
        $userB = User::factory()->create(['email' => 'other@example.com']);

        Order::factory()->for($userA)->create(['phone' => '+225070000003']);
        Order::factory()->for($userB)->create(['phone' => '+225070000004']);

        $response = $this->actingAs($admin)->get(route('admin.transactions', ['user' => 'findme']));

        $response->assertSee('+225070000003');
        $response->assertDontSee('+225070000004');
    }

    public function test_transactions_can_be_filtered_by_status(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create();

        Order::factory()->for($user)->create(['phone' => '+225070000005', 'status' => 'received']);
        Order::factory()->for($user)->create(['phone' => '+225070000006', 'status' => 'cancelled']);

        $response = $this->actingAs($admin)->get(route('admin.transactions', ['status' => 'cancelled']));

        $response->assertSee('+225070000006');
        $response->assertDontSee('+225070000005');
    }
}
