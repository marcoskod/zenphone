<?php

namespace Tests\Feature\Admin;

use App\Models\Order;
use App\Models\Setting;
use App\Models\Topup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_global_stats_across_all_users(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        Order::factory()->for($userA)->create(['price_fcfa' => 500, 'created_at' => now()]);
        Order::factory()->for($userB)->create(['price_fcfa' => 700, 'created_at' => now()]);
        Topup::factory()->for($userA)->create(['amount_fcfa' => 2000, 'status' => 'confirmed', 'created_at' => now()]);
        Topup::factory()->for($userB)->create(['amount_fcfa' => 999, 'status' => 'pending', 'created_at' => now()]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertViewHas('stats', function (array $stats) {
            // Revenue includes both orders (500+700) and the CONFIRMED topup only (2000),
            // not the pending one (999).
            return $stats['total_revenue'] === 3200.0
                && $stats['transaction_count'] === 3
                && $stats['active_users'] === 2;
        });
    }

    public function test_revenue_chart_data_groups_by_day_and_zero_fills(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create();

        Order::factory()->for($user)->create(['price_fcfa' => 1000, 'created_at' => now()]);
        Order::factory()->for($user)->create(['price_fcfa' => 500, 'created_at' => now()->subDays(40)]); // outside window

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertViewHas('chartData', function (array $chartData) {
            return count($chartData['labels']) === 30
                && count($chartData['revenue']) === 30
                && count($chartData['margin']) === 30
                && array_sum($chartData['revenue']) === 1000.0;
        });
    }

    public function test_revenue_chart_margin_reflects_the_current_setting(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create();

        Order::factory()->for($user)->create(['price_fcfa' => 1200, 'created_at' => now()]);

        Setting::set(Setting::MARGIN_PERCENT_KEY, 20);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        // price_fcfa = cost * 1.20 => margin portion = 1200 * 20 / 120 = 200.
        $response->assertViewHas('chartData', function (array $chartData) {
            return array_sum($chartData['margin']) === 200.0;
        });
    }
}
