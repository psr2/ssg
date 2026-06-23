<?php

namespace Modules\Inventory\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Inventory\Models\UnitOfMeasurement;

class UnitOfMeasurementFactory extends Factory
{
    protected $model = UnitOfMeasurement::class;

    public function definition(): array
    {
        $units = [
            ['name' => 'Kilogram', 'abbreviation' => 'Kg'],
            ['name' => 'Gram',     'abbreviation' => 'Gm'],
            ['name' => 'Tonnes',   'abbreviation' => 'Tn'],
            ['name' => 'Liter',    'abbreviation' => 'L'],
        ];

        $unit = $this->faker->randomElement($units);

        return [
            'name' => $unit['name'],
            'abbreviation' => $unit['abbreviation'],
        ];
    }
}
