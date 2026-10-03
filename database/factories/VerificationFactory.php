<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Verification>
 */
class VerificationFactory extends Factory
{
    protected $model = \App\Models\Verification::class;

    public function definition(): array
    {
        return [
            'schedule_id' => ScheduleFactory::new(),
            'athlete_id' => AthleteFactory::new(),
            'method' => 'qr',
            'verified_by' => null,
            'verified_at' => now(),
            'status' => 'present',
        ];
    }
}
