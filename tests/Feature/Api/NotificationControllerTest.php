<?php

namespace Tests\Feature\Api;

use App\Models\Topup;
use App\Models\User;
use App\Notifications\TopupConfirmed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_marking_a_notification_read_updates_read_at_and_removes_it_from_unread(): void
    {
        $user = User::factory()->create();
        $topup = Topup::factory()->for($user)->create();

        $user->notify(new TopupConfirmed($topup));
        $notification = $user->unreadNotifications->first();

        $this->assertNotNull($notification);
        $this->assertNull($notification->read_at);

        $response = $this->actingAs($user)->postJson("/api/notifications/{$notification->id}/read");

        $response->assertStatus(200);
        $response->assertJson(['status' => 'read']);

        $this->assertNotNull($notification->fresh()->read_at);
        $this->assertCount(0, $user->fresh()->unreadNotifications);
    }

    public function test_a_user_cannot_mark_another_users_notification_as_read(): void
    {
        $owner = User::factory()->create();
        $topup = Topup::factory()->for($owner)->create();
        $owner->notify(new TopupConfirmed($topup));
        $notification = $owner->unreadNotifications->first();

        $intruder = User::factory()->create();

        $response = $this->actingAs($intruder)->postJson("/api/notifications/{$notification->id}/read");

        $response->assertStatus(404);
        $this->assertNull($notification->fresh()->read_at);
    }

    public function test_marking_a_notification_read_requires_authentication(): void
    {
        $user = User::factory()->create();
        $topup = Topup::factory()->for($user)->create();
        $user->notify(new TopupConfirmed($topup));
        $notification = $user->unreadNotifications->first();

        $response = $this->postJson("/api/notifications/{$notification->id}/read");

        $response->assertStatus(401);
    }
}
