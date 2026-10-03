<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Calling>
 */
class CallingFactory extends Factory
{
    protected $model = \App\Models\Calling::class;

    public function definition(): array
    {
        return [
            'schedule_id' => ScheduleFactory::new(),
            'athlete_id' => AthleteFactory::new(),
            'level' => null,
            'status' => 'waiting',
            'called_at' => null,
            'ready_at' => null,
        ];
    }

    public function called(): static
    {
        return $this->state(fn () => [
            'level' => '30',
            'status' => 'called',
            'called_at' => now(),
        ]);
    }
}
