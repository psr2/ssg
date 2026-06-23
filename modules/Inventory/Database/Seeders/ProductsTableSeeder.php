<?php

namespace Modules\Inventory\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Inventory\Models\Products;
use Modules\Inventory\Models\UnitOfMeasurement;

class ProductsTableSeeder extends Seeder
{
    /**
     * Cache units to avoid repeated DB queries
     */
    private array $unitCache = [];

    public function run(): void
    {
        $vegetables = [
            'Onion' => [
                'sku'  => 'veg_on',
                'unit' => 'kg'
            ],
            'Potato' => [
                'sku'  => 'veg_po',
                'unit' => 'kg'
            ],
            'Tomato' => [
                'sku'  => 'veg_to',
                'unit' => 'kg'
            ],
            'Cabbage' => [
                'sku'  => 'veg_ca',
                'unit' => 'piece'
            ],
            'Spinach' => [
                'sku'  => 'veg_sp',
                'unit' => 'bunch'
            ]
        ];

        foreach ($vegetables as $product => $details) {

            $unit = $this->parseProductUnits($details['unit']);

            Products::firstOrCreate(
                ['sku' => $details['sku']], // unique check
                [
                    'name' => $product,
                    'abbreviation' => strtolower(substr($product, 0, 2)),
                    'unit_id' => $unit->id
                ]
            );
        }
    }

    /**
     * Get or create unit
     */
    private function parseProductUnits(string $unitAbbr): UnitOfMeasurement
    {
        // Use cache to reduce DB hits
        if (!isset($this->unitCache[$unitAbbr])) {
            $this->unitCache[$unitAbbr] = UnitOfMeasurement::firstOrCreate(
                ['abbreviation' => $unitAbbr],
                ['name' => ucfirst($unitAbbr)]
            );
        }

        return $this->unitCache[$unitAbbr];
    }
}
