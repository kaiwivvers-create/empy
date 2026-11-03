<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_the_root_shows_the_landing_page_and_dashboard_requires_login(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee(__('app.landing.cta'));

        $this->get('/dashboard')->assertRedirect('/login');
    }
}
