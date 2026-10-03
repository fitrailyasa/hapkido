<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Athlete>
 */
class AthleteFactory extends Factory
{
    protected $model = \App\Models\Athlete::class;

    public function definition(): array
    {
        $number = 'P' . fake()->unique()->numberBetween(10000, 99999);

        return [
            'name' => fake()->name(),
            'gender' => fake()->randomElement(['male', 'female']),
            'birth_date' => fake()->date('Y-m-d', '-10 years'),
            'id_number' => fake()->unique()->numerify('##########'),
            'contingent_id' => ContingentFactory::new(),
            'category_id' => CategoryFactory::new(),
            'participant_number' => $number,
            'qr_code' => $number,
            'status' => 'active',
            'photo' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['status' => 'inactive']);
    }
}
