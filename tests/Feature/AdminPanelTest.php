<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Users\Models\User;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_loads(): void
    {
        $this->get('/')->assertOk();
    }

    public function test_login_page_loads(): void
    {
        $this->get('/admin/login')->assertOk();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
    }

    public function test_public_registration_is_disabled(): void
    {
        $this->get('/admin/register')->assertNotFound();
    }

    public function test_admin_can_open_the_dashboard(): void
    {
        $this->actingAs($this->createAdmin())->get('/admin')->assertOk();
    }

    public function test_page_header_stays_visible_while_scrolling(): void
    {
        $this->actingAs($this->createAdmin())
            ->get('/admin/roles')
            ->assertOk()
            ->assertSee('fi-header sticky top-(--topbar-height)', false);
    }

    public function test_suspended_user_cannot_open_the_panel(): void
    {
        $this->actingAs($this->createAdmin(['is_suspended' => true]))->get('/admin')->assertRedirect('/admin/login');
        $this->assertGuest();
    }

    public function test_user_without_a_role_can_log_in_but_sees_no_modules(): void
    {
        $this->seedRoles();
        $user = User::factory()->create();

        $this->actingAs($user)->get('/admin')->assertOk();
        $this->actingAs($user)->get('/admin/users')->assertForbidden();
        $this->actingAs($user)->get('/admin/lookups')->assertForbidden();
    }
}
