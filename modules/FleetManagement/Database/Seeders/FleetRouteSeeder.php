<?php

namespace Modules\FleetManagement\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\FleetManagement\Models\FleetRoute;

class FleetRouteSeeder extends Seeder
{
    public function run(): void
    {
        // Create 10 random routes for testing
        FleetRoute::factory()->count(10)->create();

        // Optionally, insert a few fixed routes
        FleetRoute::create([
            'name' => 'City Center',
            'description' => 'Main Market Area',
        ]);

        FleetRoute::create([
            'name' => 'Airport Express',
            'description' => 'Route to International Airport',
        ]);
    }
}
