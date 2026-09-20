<?php

namespace Tests\Feature;

use Tests\TestCase;

class LandingPageTest extends TestCase
{
    public function test_home_page_can_be_rendered(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('numéro étranger');
        $response->assertSee('Comment ça marche', false);
        $response->assertSee('Questions fréquentes');
        $response->assertSee('FAQPage', false);
    }

    public function test_home_page_includes_the_order_form_and_modal_triggers(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Acheter maintenant');
        $response->assertSee('150+ pays');
        $response->assertSee('showCountriesModal = true', false);
        $response->assertSee('flagcdn.com/w80/bj.png', false);
        $response->assertSee('CGV');
        $response->assertSee('Mentions légales');
    }

    public function test_home_page_loads_the_fedapay_checkout_script(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('cdn.fedapay.com/checkout.js', false);
    }

    public function test_old_multi_page_routes_still_work_as_a_fallback_surface(): void
    {
        $this->get(route('register'))->assertStatus(200);
        $this->get(route('login'))->assertStatus(200);
        $this->get(route('pricing'))->assertStatus(200);
    }
}
