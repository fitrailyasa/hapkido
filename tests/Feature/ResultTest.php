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

    public function test_daeryun_podium_takes_the_two_semifinal_losers_as_bronze(): void
    {
        $user = $this->userWithPermissions(['results.view']);
        $category = CategoryFactory::new()->daeryun()->create(['name' => 'Daeryun Putra']);
        $athletes = AthleteFactory::new()->count(4)->create(['category_id' => $category->id]);
        $schedule = ScheduleFactory::new()->create(['category_id' => $category->id, 'type' => 'daeryun']);

        MatchupFactory::new()->finished()->create([
            'schedule_id' => $schedule->id,
            'round' => 'semifinal',
            'athlete_a_id' => $athletes[0]->id,
            'athlete_b_id' => $athletes[1]->id,
            'winner_athlete_id' => $athletes[0]->id,
            'score_a' => 9,
            'score_b' => 2,
        ]);
        MatchupFactory::new()->finished()->create([
            'schedule_id' => $schedule->id,
            'round' => 'semifinal',
            'athlete_a_id' => $athletes[2]->id,
            'athlete_b_id' => $athletes[3]->id,
            'winner_athlete_id' => $athletes[2]->id,
            'score_a' => 8,
            'score_b' => 5,
        ]);
        MatchupFactory::new()->finished()->create([
            'schedule_id' => $schedule->id,
            'round' => 'final',
            'athlete_a_id' => $athletes[0]->id,
            'athlete_b_id' => $athletes[2]->id,
            'winner_athlete_id' => $athletes[0]->id,
            'score_a' => 7,
            'score_b' => 3,
        ]);

        $response = $this->actingAs($user)->get('/admin/results');

        $response->assertOk();
        $response->assertViewHas('daeryun', function ($sections) use ($athletes) {
            $section = $sections->first();
            $podium = $section['podium'];

            if (count($podium) !== 4) {
                return false;
            }

            return $podium[0]['athlete']->id === $athletes[0]->id
                && $podium[1]['athlete']->id === $athletes[2]->id
                && $podium[2]['athlete']->id === $athletes[3]->id
                && $podium[3]['athlete']->id === $athletes[1]->id
                && $podium[2]['rank'] === 3
                && $podium[3]['rank'] === 3;
        });
        $response->assertViewHas('daeryun', function ($sections) {
            return array_column($sections->first()['rows'], 'rank') === [1, 2, 3, 3];
        });
    }

    public function test_art_ranking_gives_two_bronze_places(): void
    {
        $user = $this->userWithPermissions(['results.view']);
        $category = CategoryFactory::new()->art()->create(['name' => 'Seni Putri']);
        $schedule = ScheduleFactory::new()->art()->create(['category_id' => $category->id]);

        foreach ([20, 19, 15, 13] as $score) {
            $athlete = AthleteFactory::new()->create(['category_id' => $category->id]);

            PerformanceFactory::new()->create([
                'schedule_id' => $schedule->id,
                'athlete_id' => $athlete->id,
                'status' => 'finished',
                'final_score' => $score,
            ]);
        }

        $response = $this->actingAs($user)->get('/admin/results');

        $response->assertOk();
        $response->assertViewHas('art', function ($sections) {
            $section = $sections->first();

            return array_column($section['rows'], 'rank') === [1, 2, 3, 3]
                && array_column($section['podium'], 'rank') === [1, 2, 3, 3]
                && count($section['medals']) === 4;
        });
    }

    public function test_result_index_requires_permission(): void
    {
        $user = $this->userWithAllPermissions();

        $user->revokePermissionTo('results.view');

        $this->actingAs($user)->get('/admin/results')->assertForbidden();
    }
}
