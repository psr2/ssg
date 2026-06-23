<?php

namespace Modules\FleetManagement\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\FleetManagement\Models\FleetRoute;

class FleetRouteFactory extends Factory
{
    protected $model = FleetRoute::class;

    public function definition()
    {
        return [
            'name'        => $this->faker->city . ' Route',
            'description' => $this->faker->sentence(6),
        ];
    }
}
