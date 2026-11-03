<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_see_the_landing_page_with_a_login_action(): void
    {
        Setting::put('brand_name', 'Acme');
        Setting::put('brand_description', 'People first.');

        $this->get('/')
            ->assertOk()
            ->assertSee('Acme')
            ->assertSee('People first.')
            ->assertSee(__('app.landing.role_admin_title'))
            ->assertSee(__('app.landing.role_manager_title'))
            ->assertSee(__('app.landing.role_employee_title'))
            ->assertSee(__('app.landing.workflow_title'))
            ->assertDontSee(__('app.roles.super_admin'))
            ->assertSee('href="'.route('login').'"', false);
    }

    public function test_signed_in_users_are_sent_to_their_home_page(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@test.test',
            'password' => 'password',
            'role' => User::ROLE_ADMIN,
        ]);

        $this->actingAs($admin)
            ->get('/')
            ->assertRedirect(route('dashboard'));
    }

    public function test_super_admin_can_change_the_landing_description(): void
    {
        $superAdmin = User::create([
            'name' => 'Kai',
            'email' => 'kai@test.test',
            'password' => 'password',
            'role' => User::ROLE_SUPER_ADMIN,
        ]);

        $this->actingAs($superAdmin)
            ->post(route('branding.update'), [
                'brand_name' => 'Acme',
                'brand_tagline' => 'People',
                'brand_description' => 'A calm place to review work.',
            ])
            ->assertRedirect();

        $this->assertSame('A calm place to review work.', Setting::get('brand_description'));

        auth()->logout();

        $this->get('/')
            ->assertOk()
            ->assertSee('A calm place to review work.');
    }
}
