<?php

namespace Modules\FleetManagement\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\FleetManagement\Models\FleetRoutes as FleetRoute;

class FleetRouteSeeder extends Seeder
{
    public function run(): void
    {
        // Insert required routes
        $routes = [
            ['name' => 'Pooppara', 'description' => 'Route to Pooppara'],
            ['name' => 'kattapana', 'description' => 'Route to Kattapana'],
            ['name' => 'Mankulam', 'description' => 'Route to Mankulam'],
            ['name' => 'Kottayam', 'description' => 'Route to Kottayam'],
            ['name' => 'City Center', 'description' => 'Main Market Area'],
        ];

        foreach ($routes as $route) {
            FleetRoute::firstOrCreate(['name' => $route['name']], $route);
        }
    }
}
