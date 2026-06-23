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
                'name' => 'Main Warehouse',
                'type' => 'warehouse',
                'address' => '123 Main Street, Cityville',
                'tag' => 'central',
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Secondary Storage',
                'type' => 'storage',
                'address' => '456 Side Street, Townsville',
                'tag' => 'overflow',
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Returns Center',
                'type' => 'returns',
                'address' => '789 Return Rd, Returnsville',
                'tag' => 'returns',
                'status' => 'inactive',
                'created_at' => $now,
                'updated_at' => $now,
            ]
        ]);
    }
}
