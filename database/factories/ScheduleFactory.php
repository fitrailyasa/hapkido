<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Schedule>
 */
class ScheduleFactory extends Factory
{
    protected $model = \App\Models\Schedule::class;

    public function configure(): static
    {
        return $this->afterCreating(function (\App\Models\Schedule $schedule): void {
            DB::table('schedules')->where('id', $schedule->id)->update([
                'match_date' => $schedule->match_date->toDateString(),
            ]);
        });
    }

    public function definition(): array
    {
        return [
            'match_no' => 'M-' . fake()->unique()->numberBetween(1000, 999999),
            'category_id' => CategoryFactory::new(),
            'arena_id' => null,
            'type' => 'daeryun',
            'round' => 'penyisihan',
            'order_no' => fake()->numberBetween(1, 50),
            'match_date' => now()->toDateString(),
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
            'status' => 'pending',
        ];
    }

    public function art(): static
    {
        return $this->state(fn () => ['type' => 'art']);
    }

    public function running(): static
    {
        return $this->state(fn () => ['status' => 'running']);
    }
}
