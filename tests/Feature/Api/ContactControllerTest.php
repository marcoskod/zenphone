<?php

namespace Tests\Feature\Api;

use App\Models\SupportTicket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_form_creates_a_support_ticket(): void
    {
        $response = $this->postJson(route('api.contact'), [
            'name' => 'Aïssatou Diallo',
            'email' => 'aissatou@example.com',
            'subject' => 'Question sur un achat',
            'message' => "Je n'ai pas reçu mon SMS, pouvez-vous m'aider ?",
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('support_tickets', [
            'name' => 'Aïssatou Diallo',
            'email' => 'aissatou@example.com',
            'subject' => 'Question sur un achat',
            'status' => 'open',
        ]);
    }

    public function test_contact_form_works_without_a_subject(): void
    {
        $response = $this->postJson(route('api.contact'), [
            'name' => 'Koffi',
            'email' => 'koffi@example.com',
            'message' => 'Bonjour, une question.',
        ]);

        $response->assertStatus(200);
        $this->assertSame(1, SupportTicket::count());
    }

    public function test_contact_form_requires_name_email_and_message(): void
    {
        $response = $this->postJson(route('api.contact'), []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name', 'email', 'message']);
        $this->assertSame(0, SupportTicket::count());
    }
}
