<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Matchup>
 */
class MatchupFactory extends Factory
{
    protected $model = \App\Models\Matchup::class;

    public function definition(): array
    {
        return [
            'schedule_id' => ScheduleFactory::new(),
            'athlete_a_id' => null,
            'athlete_b_id' => null,
            'winner_athlete_id' => null,
            'winner_id' => null,
            'score_a' => null,
            'score_b' => null,
            'status' => 'pending',
            'round' => 'penyisihan',
            'position' => 1,
            'started_at' => null,
            'finished_at' => null,
        ];
    }

    public function running(): static
    {
        return $this->state(fn () => ['status' => 'running', 'started_at' => now()]);
    }

    public function finished(): static
    {
        return $this->state(fn () => ['status' => 'finished', 'finished_at' => now()]);
    }
}
