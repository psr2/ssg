<?php

namespace Modules\FleetManagement\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\FleetManagement\Models\FleetRoutes as FleetRoute;

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
