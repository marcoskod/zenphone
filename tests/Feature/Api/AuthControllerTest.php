<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_me_returns_unauthenticated_for_a_guest(): void
    {
        $response = $this->getJson(route('api.me'));

        $response->assertStatus(200);
        $response->assertJson(['authenticated' => false, 'user' => null]);
    }

    public function test_me_returns_the_current_user_when_authenticated(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson(route('api.me'));

        $response->assertStatus(200);
        $response->assertJson([
            'authenticated' => true,
            'user' => ['id' => $user->id, 'email' => $user->email],
        ]);
    }

    public function test_quick_auth_creates_a_new_account_for_an_unknown_email(): void
    {
        $response = $this->postJson(route('api.auth.quick'), [
            'email' => 'nouveau@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['authenticated' => true]);

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'nouveau@example.com']);
    }

    public function test_quick_auth_logs_in_an_existing_user_with_the_correct_password(): void
    {
        $user = User::factory()->create(['password' => bcrypt('correct-password')]);

        $response = $this->postJson(route('api.auth.quick'), [
            'email' => $user->email,
            'password' => 'correct-password',
        ]);

        $response->assertStatus(200);
        $this->assertAuthenticatedAs($user);
    }

    public function test_quick_auth_rejects_an_existing_user_with_the_wrong_password(): void
    {
        $user = User::factory()->create(['password' => bcrypt('correct-password')]);

        $response = $this->postJson(route('api.auth.quick'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('password');

        $this->assertGuest();

        // No new account was created and no duplicate user exists for this email.
        $this->assertSame(1, User::where('email', $user->email)->count());
    }

    public function test_quick_auth_requires_email_and_password(): void
    {
        $response = $this->postJson(route('api.auth.quick'), []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email', 'password']);
    }
}
