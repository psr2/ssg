<?php

namespace Modules\Locations\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Locations\Models\LocationModel;

class LocationModelFactory extends Factory
{
    protected $model = LocationModel::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->company . ' ' . $this->faker->randomElement(['Warehouse', 'Shop']),
            'type' => $this->faker->randomElement(['warehouse', 'shop']),
            'address' => $this->faker->address,
            'abbreviation' => strtoupper($this->faker->lexify('??')), 

        ];
    }
}
