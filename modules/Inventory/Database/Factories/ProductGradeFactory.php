<?php

namespace Modules\Inventory\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Inventory\Models\ProductGrade;

class ProductGradeFactory extends Factory
{
    protected $model = ProductGrade::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->word() . ' Grade',
            'code' => strtoupper($this->faker->unique()->lexify('???')),
            'description' => $this->faker->sentence(),
            'is_active' => true,
        ];
    }
}
