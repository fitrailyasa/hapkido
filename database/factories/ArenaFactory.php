<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Arena>
 */
class ArenaFactory extends Factory
{
    protected $model = \App\Models\Arena::class;

    public function definition(): array
    {
        return [
            'name' => 'Arena ' . fake()->unique()->numberBetween(1, 99999),
            'label' => strtoupper(fake()->randomLetter()) . fake()->numberBetween(1, 9),
            'category_id' => null,
            'match_type' => fake()->randomElement(['daeryun', 'art']),
            'status' => 'idle',
            'current_schedule_id' => null,
        ];
    }
}
