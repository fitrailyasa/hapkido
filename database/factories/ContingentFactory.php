<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Contingent>
 */
class ContingentFactory extends Factory
{
    protected $model = \App\Models\Contingent::class;

    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'name' => $name,
            'code' => strtoupper(Str::limit(Str::slug($name), 4, '')) . fake()->unique()->numberBetween(10, 9999),
            'region' => fake()->city(),
            'coach_name' => fake()->name(),
        ];
    }
}
