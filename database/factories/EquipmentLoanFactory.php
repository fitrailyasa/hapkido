<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EquipmentLoan>
 */
class EquipmentLoanFactory extends Factory
{
    protected $model = \App\Models\EquipmentLoan::class;

    public function definition(): array
    {
        return [
            'equipment_id' => EquipmentFactory::new(),
            'athlete_id' => AthleteFactory::new(),
            'qty' => 1,
            'schedule_id' => null,
            'loaned_at' => now(),
            'returned_at' => null,
            'loan_condition' => 'Baik',
            'return_condition' => null,
            'notes' => null,
            'status' => 'loaned',
            'loaned_by' => null,
            'returned_by' => null,
        ];
    }

    public function returned(): static
    {
        return $this->state(fn () => [
            'status' => 'returned',
            'returned_at' => now(),
        ]);
    }
}
