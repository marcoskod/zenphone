<?php

namespace Tests\Feature\Admin;

use App\Models\Setting;
use App\Models\User;
use App\Services\PricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_cannot_access_margin_settings(): void
    {
        $user = User::factory()->create();

        $response = $this->get(route('admin.settings.margin'));
        $response->assertRedirect(route('login'));

        $response = $this->actingAs($user)->get(route('admin.settings.margin'));
        $response->assertStatus(403);
    }

    public function test_admin_can_view_the_margin_settings_page(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->get(route('admin.settings.margin'));

        $response->assertStatus(200);
    }

    public function test_admin_can_update_the_margin_and_it_persists(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->post(route('admin.settings.margin.update'), [
            'margin_percent' => 35,
        ]);

        $response->assertRedirect();
        $this->assertEquals(35.0, (float) Setting::get(Setting::MARGIN_PERCENT_KEY));
    }

    public function test_updated_margin_is_reflected_in_pricing_service_output(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->post(route('admin.settings.margin.update'), [
            'margin_percent' => 50,
        ]);

        $pricing = app(PricingService::class);

        // With EXCHANGE_RATE_USD_FCFA=600 (default) and the new 50% margin:
        // 1 USD -> 600 FCFA, +50% = 900 FCFA.
        $this->assertSame(900.0, $pricing->calculatePrice(1.0));
    }

    public function test_margin_update_requires_a_valid_numeric_value(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->post(route('admin.settings.margin.update'), [
            'margin_percent' => 'not-a-number',
        ]);

        $response->assertSessionHasErrors('margin_percent');
    }
}
