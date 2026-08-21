<?php

namespace Tests\Feature\Admin;

use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupportTicketControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_cannot_access_support_tickets(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('admin.support'));

        $response->assertStatus(403);
    }

    public function test_admin_sees_contact_form_submissions(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        SupportTicket::factory()->create(['subject' => 'Problème de SMS non reçu']);

        $response = $this->actingAs($admin)->get(route('admin.support'));

        $response->assertStatus(200);
        $response->assertSee('Problème de SMS non reçu');
    }

    public function test_tickets_can_be_filtered_by_status(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        SupportTicket::factory()->create(['subject' => 'Ticket ouvert', 'status' => 'open']);
        SupportTicket::factory()->create(['subject' => 'Ticket fermé', 'status' => 'closed']);

        $response = $this->actingAs($admin)->get(route('admin.support', ['status' => 'closed']));

        $response->assertSee('Ticket fermé');
        $response->assertDontSee('Ticket ouvert');
    }

    public function test_admin_can_update_a_tickets_status_and_it_persists(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $ticket = SupportTicket::factory()->create(['status' => 'open']);

        $response = $this->actingAs($admin)->post(route('admin.support.status', $ticket), [
            'status' => 'closed',
        ]);

        $response->assertRedirect();
        $this->assertSame('closed', $ticket->fresh()->status);
    }

    public function test_status_update_rejects_an_invalid_value(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $ticket = SupportTicket::factory()->create(['status' => 'open']);

        $response = $this->actingAs($admin)->post(route('admin.support.status', $ticket), [
            'status' => 'not-a-real-status',
        ]);

        $response->assertSessionHasErrors('status');
        $this->assertSame('open', $ticket->fresh()->status);
    }
}
