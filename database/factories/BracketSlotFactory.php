<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BracketSlot>
 */
class BracketSlotFactory extends Factory
{
    protected $model = \App\Models\BracketSlot::class;

    public function definition(): array
    {
        return [
            'category_id' => CategoryFactory::new(),
            'round' => '4',
            'position' => 0,
            'athlete_id' => null,
        ];
    }
}
