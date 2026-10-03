<?php

namespace Tests\Feature;

use Database\Factories\AthleteFactory;
use Database\Factories\CategoryFactory;
use Database\Factories\MatchupFactory;
use Database\Factories\PerformanceFactory;
use Database\Factories\ScheduleFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesUsers;
use Tests\TestCase;

class ResultTest extends TestCase
{
    use CreatesUsers, RefreshDatabase;

    public function test_result_index_can_be_rendered(): void
    {
        $user = $this->userWithPermissions(['results.view']);

        $response = $this->actingAs($user)->get('/admin/results');

        $response->assertOk();
    }

    public function test_daeryun_results_list_athletes(): void
    {
        $user = $this->userWithPermissions(['results.view']);
        $category = CategoryFactory::new()->daeryun()->create(['name' => 'Kategori A']);
        $athlete = AthleteFactory::new()->create(['category_id' => $category->id, 'name' => 'Atlet Juara']);

        $response = $this->actingAs($user)->get('/admin/results');

        $response->assertOk();
        $response->assertSee('Atlet Juara');
        $response->assertSee('Kategori A');
    }

    public function test_finished_match_feeds_daeryun_ranking(): void
    {
        $user = $this->userWithPermissions(['results.view']);
        $category = CategoryFactory::new()->daeryun()->create();
        $athleteA = AthleteFactory::new()->create(['category_id' => $category->id]);
        $athleteB = AthleteFactory::new()->create(['category_id' => $category->id]);
        $schedule = ScheduleFactory::new()->create(['category_id' => $category->id, 'type' => 'daeryun']);
        MatchupFactory::new()->finished()->create([
            'schedule_id' => $schedule->id,
            'athlete_a_id' => $athleteA->id,
            'athlete_b_id' => $athleteB->id,
            'winner_athlete_id' => $athleteA->id,
            'score_a' => 9,
            'score_b' => 2,
        ]);

        $response = $this->actingAs($user)->get('/admin/results');

        $response->assertOk();
        $response->assertSee($athleteA->name);
    }

    public function test_art_results_list_performances(): void
    {
        $user = $this->userWithPermissions(['results.view']);
        CategoryFactory::new()->daeryun()->create();
        $category = CategoryFactory::new()->art()->create(['name' => 'Kategori Seni']);
        $schedule = ScheduleFactory::new()->art()->create(['category_id' => $category->id]);
        $performance = PerformanceFactory::new()->create([
            'schedule_id' => $schedule->id,
            'final_score' => 88.5,
        ]);

        $response = $this->actingAs($user)->get('/admin/results');

        $response->assertOk();
        $response->assertSee($performance->athlete->name);
        $response->assertSee('Kategori Seni');
    }

    public function test_results_page_renders_when_no_daeryun_category_exists(): void
    {
        $user = $this->userWithPermissions(['results.view']);
        CategoryFactory::new()->art()->create();

        $this->actingAs($user)->get('/admin/results')->assertOk();
    }

    public function test_result_index_requires_permission(): void
    {
        $user = $this->userWithAllPermissions();

        $user->revokePermissionTo('results.view');

        $this->actingAs($user)->get('/admin/results')->assertForbidden();
    }
}
