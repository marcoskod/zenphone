<?php

namespace Tests\Feature;

use Tests\TestCase;

class LandingPageTest extends TestCase
{
    public function test_landing_page_can_be_rendered(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Recevez vos codes SMS');
    }

    public function test_landing_page_links_to_stub_routes(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee(route('pricing'), false);
        $response->assertSee(route('faq'), false);
        $response->assertSee(route('register'), false);
    }
}
