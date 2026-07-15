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

        DB::table('locations')->insertOrIgnore([
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
                'name' => 'Madurai Warehouse',
                'type' => 'warehouse',
                'address' => 'Madurai, Tamil Nadu, India',
                'abbreviation' => 'madu',
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Coimbatore Warehouse',
                'type' => 'warehouse',
                'address' => 'Coimbatore, Tamil Nadu, India',
                'abbreviation' => 'coim',
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
            ],
            [
                'name' => 'Madurai Shop',
                'type' => 'shop',
                'address' => 'Madurai Junction, Tamil Nadu, India',
                'abbreviation' => 'madu-shop',
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Coimbatore Shop',
                'type' => 'shop',
                'address' => 'Coimbatore Town, Tamil Nadu, India',
                'abbreviation' => 'coim-shop',
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ]
        ]);
    }
}
