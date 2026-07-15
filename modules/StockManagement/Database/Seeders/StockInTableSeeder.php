<?php

namespace Modules\StockManagement\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Inventory\Models\Products;
use Modules\Locations\Models\LocationModel;
use Modules\Inventory\Models\ProductGrade;
use Modules\StockManagement\Services\StockMovement\ReferenceNumber\PurchaseReferenceNumberGenerator;
use Modules\StockManagement\Services\StockMovement\StockIn\PurchaseService;

class StockInTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $location = LocationModel::where('type', 'warehouse')->first();
        if (!$location) {
            $location = LocationModel::first();
        }

        $grade = ProductGrade::where('name', 'Big')->first();
        if (!$grade) {
            $grade = ProductGrade::first();
        }

        if (!$location || !$grade) {
            return;
        }

        // Generate unique reference number using the expected generator service
        $generator = new PurchaseReferenceNumberGenerator();
        $response = $generator->generate();
        $referenceNo = json_decode($response->getContent(), true)['reference_no'];

        $items = [];
        $allProducts = Products::all();

        foreach ($allProducts as $product) {
            $qty = ($product->name === 'Onion') ? 25000.00 : 1000.00;
            $unitModel = \Modules\Inventory\Models\UnitOfMeasurement::find($product->unit_id);
            $unitAbbr = $unitModel ? $unitModel->abbreviation : 'kg';

            $items[] = [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'grade' => $grade->code,
                'location_id' => $location->id,
                'quantity' => $qty,
                'unit' => $unitAbbr,
                'unit_cost' => 10.00,
                'total' => $qty * 10.00,
                'remarks' => 'Seeded initial purchase',
                'invoice_number' => 'INV-' . str_pad((string)random_int(1, 999), 3, '0', STR_PAD_LEFT),
                'vendor' => 'Vendor ABC',
                'purchase_date' => now()->format('Y-m-d'),
            ];
        }

        $payload = [
            'stock_type' => 'in',
            'reference_no' => $referenceNo,
            'movement_date' => now()->format('Y-m-d'),
            'in_type' => 'purchase',
            'items' => $items
        ];

        // Call the service to register the stock purchase, log to the ledger, and update balance caches
        $purchaseService = app(PurchaseService::class);
        $purchaseService->createStockIn($payload);
    }
}
