<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Topup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HistoryControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_orders_page_shows_only_the_authenticated_users_orders(): void
    {
        $user = User::factory()->create();
        Order::factory()->for($user)->create(['phone' => '+225070000001']);

        $otherUser = User::factory()->create();
        Order::factory()->for($otherUser)->create(['phone' => '+225070000002']);

        $response = $this->actingAs($user)->get(route('history'));

        $response->assertStatus(200);
        $response->assertSee('+225070000001');
        $response->assertDontSee('+225070000002');
    }

    public function test_topups_page_shows_only_the_authenticated_users_topups(): void
    {
        // Distinct amounts rather than operator names, since every operator name is
        // always present as a filter <option> regardless of which rows exist.
        $user = User::factory()->create();
        Topup::factory()->for($user)->create(['operator' => 'Orange Money', 'amount_fcfa' => 4321]);

        $otherUser = User::factory()->create();
        Topup::factory()->for($otherUser)->create(['operator' => 'Wave', 'amount_fcfa' => 8765]);

        $response = $this->actingAs($user)->get(route('history.topups'));

        $response->assertStatus(200);
        $response->assertSee('4 321 FCFA');
        $response->assertDontSee('8 765 FCFA');
    }

    public function test_topups_page_handles_zero_rows_gracefully(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('history.topups'));

        $response->assertStatus(200);
        $response->assertSee('Aucune recharge pour le moment.');
    }

    public function test_orders_can_be_filtered_by_service(): void
    {
        $user = User::factory()->create();
        Order::factory()->for($user)->create(['service' => 'whatsapp', 'phone' => '+225070000003']);
        Order::factory()->for($user)->create(['service' => 'google', 'phone' => '+225070000004']);

        $response = $this->actingAs($user)->get(route('history', ['service' => 'whatsapp']));

        $response->assertSee('+225070000003');
        $response->assertDontSee('+225070000004');
    }

    public function test_orders_can_be_filtered_by_status(): void
    {
        $user = User::factory()->create();
        Order::factory()->for($user)->create(['status' => 'received', 'phone' => '+225070000005']);
        Order::factory()->for($user)->create(['status' => 'cancelled', 'phone' => '+225070000006']);

        $response = $this->actingAs($user)->get(route('history', ['status' => 'cancelled']));

        $response->assertSee('+225070000006');
        $response->assertDontSee('+225070000005');
    }

    public function test_orders_can_be_filtered_by_date_range(): void
    {
        $user = User::factory()->create();
        Order::factory()->for($user)->create(['phone' => '+225070000007', 'created_at' => now()->subDays(10)]);
        Order::factory()->for($user)->create(['phone' => '+225070000008', 'created_at' => now()]);

        $response = $this->actingAs($user)->get(route('history', [
            'date_from' => now()->subDay()->toDateString(),
        ]));

        $response->assertSee('+225070000008');
        $response->assertDontSee('+225070000007');
    }

    public function test_topups_can_be_filtered_by_operator(): void
    {
        $user = User::factory()->create();
        Topup::factory()->for($user)->create(['operator' => 'Wave', 'amount_fcfa' => 1500]);
        Topup::factory()->for($user)->create(['operator' => 'MTN MoMo', 'amount_fcfa' => 2500]);

        $response = $this->actingAs($user)->get(route('history.topups', ['operator' => 'Wave']));

        $response->assertSee('1 500 FCFA');
        $response->assertDontSee('2 500 FCFA');
    }

    public function test_orders_are_paginated_at_twenty_per_page(): void
    {
        $user = User::factory()->create();
        Order::factory()->for($user)->count(25)->create();

        $response = $this->actingAs($user)->get(route('history'));

        $response->assertStatus(200);
        $response->assertViewHas('orders', function ($orders) {
            return $orders->count() === 20 && $orders->total() === 25;
        });

        $secondPage = $this->actingAs($user)->get(route('history', ['page' => 2]));

        $secondPage->assertViewHas('orders', fn ($orders) => $orders->count() === 5);
    }

    public function test_pagination_preserves_filters_across_pages(): void
    {
        $user = User::factory()->create();
        Order::factory()->for($user)->count(25)->create(['service' => 'whatsapp']);
        Order::factory()->for($user)->create(['service' => 'google']);

        $response = $this->actingAs($user)->get(route('history', ['service' => 'whatsapp']));

        $response->assertStatus(200);
        $response->assertSee('service=whatsapp', false);
    }

    public function test_csv_export_returns_the_right_content_type_and_row_count(): void
    {
        $user = User::factory()->create();
        Order::factory()->for($user)->create(['phone' => '+225070000009', 'service' => 'whatsapp']);
        Order::factory()->for($user)->create(['phone' => '+225070000010', 'service' => 'google']);

        $otherUser = User::factory()->create();
        Order::factory()->for($otherUser)->create(['phone' => '+225070000011']);

        $response = $this->actingAs($user)->get(route('history.export'));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $content = $response->streamedContent();
        $lines = array_filter(explode("\n", trim($content)));

        // 1 header row + 2 of this user's orders (not the other user's).
        $this->assertCount(3, $lines);
        $this->assertStringContainsString('+225070000009', $content);
        $this->assertStringContainsString('+225070000010', $content);
        $this->assertStringNotContainsString('+225070000011', $content);
    }

    public function test_csv_export_respects_the_current_filters(): void
    {
        $user = User::factory()->create();
        Order::factory()->for($user)->create(['phone' => '+225070000012', 'service' => 'whatsapp']);
        Order::factory()->for($user)->create(['phone' => '+225070000013', 'service' => 'google']);

        $response = $this->actingAs($user)->get(route('history.export', ['service' => 'whatsapp']));

        $content = $response->streamedContent();

        $this->assertStringContainsString('+225070000012', $content);
        $this->assertStringNotContainsString('+225070000013', $content);
    }
}
