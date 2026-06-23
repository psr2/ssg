<?php

namespace Modules\Inventory\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Inventory\Models\Products;
use Modules\Inventory\Models\UnitOfMeasurement;

class ProductFactory extends Factory
{
    protected $model = Products::class;

    public function definition(): array
    {
        $vegetables = [
            'Onion', 'Potato', 'Tomato', 'Cabbage', 'Spinach',
            'Carrot', 'Broccoli', 'Cauliflower', 'Beetroot', 'Capsicum'
        ];

        $name = $this->faker->unique()->randomElement($vegetables);

        return [
            'name' => $name,
            'abbreviation' => strtoupper(substr($name, 0, 2)),
            'sku' => 'SKU' . $this->faker->unique()->numberBetween(100, 999),
            'unit_id' => UnitOfMeasurement::factory(),  // Make sure this factory exists!
            'category' => 'Vegetables',
        ];
    }
}
