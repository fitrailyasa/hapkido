<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesUsers;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use CreatesUsers, RefreshDatabase;

    public function test_dashboard_can_be_rendered(): void
    {
        $user = $this->userWithPermissions(['dashboard.view']);

        $response = $this->actingAs($user)->get('/admin');

        $response->assertOk();
    }

    public function test_dashboard_data_endpoint_returns_payload(): void
    {
        $user = $this->userWithPermissions(['dashboard.view']);

        $response = $this->actingAs($user)->getJson('/admin/dashboard/data');

        $response->assertOk();
        $response->assertJsonStructure([
            'now',
            'arenas',
            'active_match',
            'next_match',
            'jadwal',
            'callings',
            'counts' => [
                'verification_present',
                'verification_total',
                'readiness_ready',
                'readiness_total',
                'running_matches',
                'waiting_callings',
            ],
        ]);
        $response->assertJsonPath('counts.verification_total', 0);
        $response->assertJsonPath('counts.readiness_total', 0);
        $response->assertJsonPath('counts.running_matches', 0);
    }

    public function test_dashboard_is_accessible_to_any_authenticated_user(): void
    {
        $user = $this->userWithPermissions([]);

        $response = $this->actingAs($user)->get('/admin');

        $response->assertOk();
    }
}
