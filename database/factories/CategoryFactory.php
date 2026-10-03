<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Category>
 */
class CategoryFactory extends Factory
{
    protected $model = \App\Models\Category::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => ucwords($name),
            'slug' => Str::slug($name) . '-' . fake()->unique()->numberBetween(1, 99999),
            'type' => fake()->randomElement(['daeryun', 'art']),
            'gender' => fake()->randomElement(['male', 'female', 'open']),
            'age_class' => fake()->randomElement(['Senior', 'Junior', null]),
        ];
    }

    public function daeryun(): static
    {
        return $this->state(fn () => ['type' => 'daeryun']);
    }

    public function art(): static
    {
        return $this->state(fn () => ['type' => 'art']);
    }
}
