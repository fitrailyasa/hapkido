<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Equipment>
 */
class EquipmentFactory extends Factory
{
    protected $model = \App\Models\Equipment::class;

    public function definition(): array
    {
        $total = fake()->numberBetween(2, 10);

        return [
            'type' => fake()->randomElement(['head_guard', 'body_protector']),
            'color' => fake()->randomElement(['Red', 'Blue', 'White']),
            'size' => fake()->randomElement(['S', 'M', 'L']),
            'code' => 'EQ-' . fake()->unique()->numberBetween(1000, 999999),
            'status' => 'available',
            'total_qty' => $total,
            'available_qty' => $total,
        ];
    }
}
