<?php

namespace Tests\Feature\Admin;

use App\Models\BalanceAdjustment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_cannot_access_user_management(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('admin.users'));

        $response->assertStatus(403);
    }

    public function test_admin_can_search_users_by_name_or_email(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        User::factory()->create(['name' => 'Aïssatou Diallo', 'email' => 'aissatou@example.com']);
        User::factory()->create(['name' => 'Koffi Mensah', 'email' => 'koffi@example.com']);

        $response = $this->actingAs($admin)->get(route('admin.users', ['search' => 'Aïssatou']));

        $response->assertStatus(200);
        $response->assertSee('Aïssatou Diallo');
        $response->assertDontSee('Koffi Mensah');
    }

    public function test_admin_can_suspend_and_reactivate_a_user(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create(['is_suspended' => false]);

        $this->actingAs($admin)->post(route('admin.users.suspend', $user));
        $this->assertTrue($user->fresh()->is_suspended);

        $this->actingAs($admin)->post(route('admin.users.suspend', $user));
        $this->assertFalse($user->fresh()->is_suspended);
    }

    public function test_suspended_user_cannot_log_in_via_the_standard_login_form(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
            'is_suspended' => true,
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_suspended_user_cannot_log_in_via_the_quick_auth_endpoint(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
            'is_suspended' => true,
        ]);

        $response = $this->postJson(route('api.auth.quick'), [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertStatus(422);
        $this->assertGuest();
    }

    public function test_active_user_can_still_log_in_normally(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
            'is_suspended' => false,
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertAuthenticatedAs($user);
    }

    public function test_admin_can_credit_a_users_balance_with_an_audit_trail(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create(['balance' => 1000]);

        $response = $this->actingAs($admin)->post(route('admin.users.credit', $user), [
            'amount_fcfa' => 500,
            'reason' => 'Geste commercial',
        ]);

        $response->assertRedirect();
        $this->assertEquals(1500.0, (float) $user->fresh()->balance);

        $this->assertDatabaseHas('balance_adjustments', [
            'user_id' => $user->id,
            'admin_id' => $admin->id,
            'amount_fcfa' => 500,
            'balance_after' => 1500,
            'reason' => 'Geste commercial',
        ]);
    }

    public function test_admin_can_debit_a_users_balance(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create(['balance' => 1000]);

        $this->actingAs($admin)->post(route('admin.users.credit', $user), [
            'amount_fcfa' => -300,
        ]);

        $this->assertEquals(700.0, (float) $user->fresh()->balance);
        $this->assertSame(1, BalanceAdjustment::where('amount_fcfa', -300)->count());
    }

    public function test_credit_requires_a_nonzero_amount(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create(['balance' => 1000]);

        $response = $this->actingAs($admin)->post(route('admin.users.credit', $user), [
            'amount_fcfa' => 0,
        ]);

        $response->assertSessionHasErrors('amount_fcfa');
        $this->assertEquals(1000.0, (float) $user->fresh()->balance);
    }
}
