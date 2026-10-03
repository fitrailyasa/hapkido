<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ReadinessCheck>
 */
class ReadinessCheckFactory extends Factory
{
    protected $model = \App\Models\ReadinessCheck::class;

    public function definition(): array
    {
        return [
            'schedule_id' => ScheduleFactory::new(),
            'athlete_id' => AthleteFactory::new(),
            'attendance_check' => false,
            'athlete_check' => false,
            'equipment_check' => false,
            'notes' => null,
            'status' => 'pending',
            'checked_by' => null,
            'checked_at' => null,
        ];
    }
}
