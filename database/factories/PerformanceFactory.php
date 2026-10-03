<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Performance>
 */
class PerformanceFactory extends Factory
{
    protected $model = \App\Models\Performance::class;

    public function definition(): array
    {
        return [
            'schedule_id' => ScheduleFactory::new()->art(),
            'athlete_id' => AthleteFactory::new(),
            'order_no' => fake()->numberBetween(1, 20),
            'status' => 'waiting',
            'final_score' => null,
            'rank' => null,
            'performed_at' => null,
        ];
    }

    public function finished(): static
    {
        return $this->state(fn () => [
            'status' => 'finished',
            'final_score' => 85.5,
            'performed_at' => now(),
        ]);
    }
}
