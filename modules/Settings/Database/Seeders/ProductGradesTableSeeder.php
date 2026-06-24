<?php

namespace Modules\Settings\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class ProductGradesTableSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        DB::table('product_grades')->insertOrIgnore([
            [
                'name' => 'Big',
                'code' => 'BO',
                'description' => 'Big Onion Grade',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Medium',
                'code' => 'MO',
                'description' => 'Medium Onion Grade',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Small',
                'code' => 'SO',
                'description' => 'Small Onion Grade',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        ]);
    }
}
