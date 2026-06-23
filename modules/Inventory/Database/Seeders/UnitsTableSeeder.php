<?php

namespace Modules\Inventory\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Inventory\Models\UnitOfMeasurement;

class UnitsTableSeeder extends Seeder
{
    public function run(): void
    {
        $units = [
            ['name' => 'Kilogram', 'abbreviation' => 'kg'],
            ['name' => 'Gram', 'abbreviation' => 'gm'],
            ['name' => 'Tonnes', 'abbreviation' => 'tn'],
            ['name' => 'Liter', 'abbreviation' => 'l'],
        ];

        foreach ($units as $unit) {
            UnitOfMeasurement::firstOrCreate(
                ['name' => $unit['name']],
                ['abbreviation' => $unit['abbreviation']]
            );
        }
    }
    
}
