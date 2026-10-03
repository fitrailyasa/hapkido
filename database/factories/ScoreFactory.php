<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Score>
 */
class ScoreFactory extends Factory
{
    protected $model = \App\Models\Score::class;

    public function definition(): array
    {
        return [
            'performance_id' => PerformanceFactory::new(),
            'judge_no' => fake()->numberBetween(1, 3),
            'score' => fake()->randomFloat(2, 60, 99),
        ];
    }
}
