<?php

namespace Modules\Locations\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class LocationsTableSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        DB::table('locations')->insert([
            [
                'name' => 'Theni Warehouse',
                'type' => 'warehouse',
                'address' => 'Theni, Tamil Nadu, India',
                'abbreviation' => 'thani',
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Theni Shop',
                'type' => 'shop',
                'address' => 'Theni Market, Tamil Nadu, India',
                'abbreviation' => 'thani-shop',
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ]
        ]);
    }
}
