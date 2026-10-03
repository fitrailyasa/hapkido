<?php

namespace Tests\Feature;

use Database\Factories\ArenaFactory;
use Database\Factories\AthleteFactory;
use Database\Factories\CategoryFactory;
use Database\Factories\ContingentFactory;
use Database\Factories\PerformanceFactory;
use Database\Factories\ScheduleFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesUsers;
use Tests\TestCase;

class SmokeTest extends TestCase
{
    use CreatesUsers;
    use RefreshDatabase;

    public function test_all_pages_render_without_errors(): void
    {
        $user = $this->userWithAllPermissions(['is_active' => true]);

        $contingent = ContingentFactory::new()->create();
        $category = CategoryFactory::new()->create(['type' => 'daeryun']);
        $athletes = AthleteFactory::new()->count(2)->create([
            'category_id' => $category->id,
            'contingent_id' => $contingent->id,
        ]);
        $arena = ArenaFactory::new()->create();
        $schedule = ScheduleFactory::new()->create([
            'category_id' => $category->id,
            'arena_id' => $arena->id,
            'type' => 'daeryun',
        ]);
        $performance = PerformanceFactory::new()->create([
            'athlete_id' => $athletes->first()->id,
            'schedule_id' => $schedule->id,
        ]);

        $public = [
            '/',
            '/login',
            '/register',
            '/welcome',
            '/display',
            '/display/data',
            '/display/arena/' . $arena->id,
            '/display/arena/' . $arena->id . '/data',
        ];

        $this->get('/')->assertRedirect('/display');

        foreach (array_slice($public, 1) as $uri) {
            $this->get($uri)->assertOk("GET {$uri} harus 200");
        }

        $admin = [
            '/admin',
            '/admin/dashboard/data',
            '/admin/athletes',
            '/admin/athletes/' . $athletes->first()->id,
            '/admin/athletes-labels',
            '/admin/contingents',
            '/admin/categories',
            '/admin/schedules',
            '/admin/schedules/' . $schedule->id,
            '/admin/schedules-export',
            '/admin/schedules-template',
            '/admin/arenas',
            '/admin/callings',
            '/admin/callings-data',
            '/admin/verifications',
            '/admin/readiness',
            '/admin/equipments',
            '/admin/equipment-loans',
            '/admin/matches',
            '/admin/performances',
            '/admin/scores/' . $performance->id,
            '/admin/brackets',
            '/admin/results',
            '/admin/history',
            '/admin/users',
            '/admin/roles',
            '/profile',
            '/dashboard',
        ];

        foreach (array_slice($admin, 0, -1) as $uri) {
            $this->actingAs($user)->get($uri)->assertOk("GET {$uri} harus 200");
        }

        // Alias dashboard sengaja redirect ke halaman utama back office
        $this->actingAs($user)->get('/dashboard')->assertRedirect('/admin');
    }
}
