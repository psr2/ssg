<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Inventory\Database\Seeders\UnitsTableSeeder;
use Modules\Inventory\Database\Seeders\ProductsTableSeeder;
use Modules\Locations\Database\Seeders\LocationsTableSeeder;
use Modules\Inventory\Database\Seeders\ProductGradesTableSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UnitsTableSeeder::class,
            ProductsTableSeeder::class,
            LocationsTableSeeder::class,
            ProductGradesTableSeeder::class,
            \Modules\StockManagement\Database\Seeders\StockInTableSeeder::class,
            \Modules\FleetManagement\Database\Seeders\FleetRouteSeeder::class,
            \Modules\FleetManagement\Database\Seeders\FleetVehicleSeeder::class,
        ]);
    }
}
