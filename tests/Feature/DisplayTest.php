<?php

namespace Tests\Feature;

use Database\Factories\ArenaFactory;
use Database\Factories\AthleteFactory;
use Database\Factories\CallingFactory;
use Database\Factories\CategoryFactory;
use Database\Factories\ContingentFactory;
use Database\Factories\MatchupFactory;
use Database\Factories\ScheduleFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DisplayTest extends TestCase
{
    use RefreshDatabase;

    public function test_display_screen_can_be_rendered_without_login(): void
    {
        ArenaFactory::new()->create(['name' => 'Arena Utama', 'label' => 'A1']);

        $response = $this->get('/display');

        $response->assertOk();
        $response->assertSee('Public Display');
    }

    public function test_display_data_returns_payload(): void
    {
        $contingent = ContingentFactory::new()->create();
        $category = CategoryFactory::new()->daeryun()->create();
        $athlete = AthleteFactory::new()->create([
            'contingent_id' => $contingent->id,
            'category_id' => $category->id,
        ]);
        $schedule = ScheduleFactory::new()->create([
            'category_id' => $category->id,
            'status' => 'running',
        ]);
        MatchupFactory::new()->create([
            'schedule_id' => $schedule->id,
            'athlete_a_id' => $athlete->id,
            'athlete_b_id' => $athlete->id,
            'status' => 'finished',
            'score_a' => 3,
            'score_b' => 1,
            'winner_athlete_id' => $athlete->id,
            'winner_id' => $athlete->id,
            'finished_at' => now(),
        ]);
        CallingFactory::new()->create([
            'schedule_id' => $schedule->id,
            'athlete_id' => $athlete->id,
            'status' => 'called',
            'level' => '30',
            'called_at' => now(),
        ]);

        $response = $this->getJson('/display/data');

        $response->assertOk();
        $response->assertJsonStructure([
            'now',
            'present_count',
            'arenas',
            'running',
            'callings',
            'results',
            'performances',
            'next',
            'streamer',
        ]);
        $this->assertSame(1, $response->json('running'));
        $this->assertSame(1, count($response->json('callings')));
        $this->assertSame('3 - 1', $response->json('results.0.score'));
    }

    public function test_display_data_shows_next_schedules(): void
    {
        ScheduleFactory::new()->create([
            'status' => 'pending',
            'start_time' => '08:30:00',
        ]);

        $response = $this->getJson('/display/data');

        $response->assertOk();
        $this->assertSame('08:30', $response->json('next.0.time'));
    }
}
