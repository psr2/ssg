<?php

namespace Modules\StockManagement\Repositories\StockOut;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\StockManagement\Models\StockOut\MasterStockOut;
use Modules\StockManagement\Models\StockOut\StockOutItem;

class StockOutRepository
{
    /**
     * Create master stock out + items in DB
     */
    public function createStockOut(array $data): MasterStockOut
    {
        return DB::transaction(function () use ($data) {
            // Insert into master_stock_out
            $master = MasterStockOut::create([
                'location_id'   => $data['location_id'] ?? $data['items'][0]['location_id'],
                'reference_no'  => $data['reference_no'] ?? null,
                'out_type'      => $data['out_type'] ?? 'sale',
                'out_date'      => $data['movement_date'] ?? now(),
                'remarks'       => $data['remarks'] ?? null,
            ]);

            // Insert items
            foreach ($data['items'] as $item) {
                $master->items()->create([
                    'product_id' => $item['product_id'] ?? null,  // you will map product name → id before this
                    'unit_id'    => $item['unit_id'] ?? null,     // you will map unit → id before this
                    'quantity'   => $item['quantity'],
                    'unit_cost'  => $item['unit_cost'] ?? null,
                    'total_cost' => $item['total'] ?? null,
                    'location_id'=> $item['location_id'],
                ]);
            }

            return $master;
        });
    }
}
