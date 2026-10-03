<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesUsers;
use Tests\TestCase;

class AccessControlTest extends TestCase
{
    use CreatesUsers, RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/admin/athletes');

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_without_permission_gets_403(): void
    {
        $user = $this->userWithPermissions([]);

        $response = $this->actingAs($user)->get('/admin/athletes');

        $response->assertForbidden();
    }

    public function test_user_with_permission_can_access_module(): void
    {
        $user = $this->userWithPermissions(['athletes.view']);

        $response = $this->actingAs($user)->get('/admin/athletes');

        $response->assertOk();
    }

    public function test_user_with_permission_can_access_route_group_by_role(): void
    {
        $user = $this->userWithRole('Coach');

        $response = $this->actingAs($user)->get('/admin/schedules');

        $response->assertOk();
    }

    public function test_coach_cannot_access_user_management(): void
    {
        $user = $this->userWithRole('Coach');

        $response = $this->actingAs($user)->get('/admin/users');

        $response->assertForbidden();
    }

    public function test_inactive_user_is_logged_out(): void
    {
        $user = $this->userWithPermissions(['athletes.view'], ['is_active' => false]);

        $response = $this->actingAs($user)->get('/admin/athletes');

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email', 'Akun Anda telah dinonaktifkan. Hubungi administrator.');
        $this->assertGuest();
    }

    public function test_inactive_user_is_logged_out_on_post_request(): void
    {
        $user = $this->userWithPermissions([], ['is_active' => false]);

        $response = $this->actingAs($user)->post('/logout');

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_permission_middleware_rejects_wrong_permission(): void
    {
        $user = $this->userWithPermissions(['categories.view']);

        $response = $this->actingAs($user)->get('/admin/athletes');

        $response->assertForbidden();
    }
}
