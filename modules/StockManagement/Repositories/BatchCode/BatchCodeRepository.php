<?php

namespace Modules\StockManagement\Repositories\BatchCode;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BatchCodeRepository
{

    /**
     * Search and return batch codes based on filters.
     *
     * @param array $filters {
     *     @type string|null $product_listing   Product name (e.g., onion, potato)
     *     @type int|null    $location          Location ID
     *     @type string|null $dateFrom          Purchase month and year in 'YYYY-MM' format
     * }
     *
     * @return \Illuminate\Support\Collection
     * 
     * Todo:: use telemetry and logging
     */

    public function search(array $filters = [])
    {
        Log::debug($filters);

        $query = DB::table('stock_summary as sm')
            ->join('products', 'sm.product_id', '=', 'products.id')
            ->join('locations', 'sm.location_id', '=', 'locations.id')
            ->select(
                'sm.batch_id as batch_code',
                'sm.current_qty',
                'locations.name as location',
                'products.name as product'
            );

        if (!empty($filters['product_listing'])) {
            $query->where('products.id', $filters['product_listing']);
        }

        if (!empty($filters['location'])) {
            $query->where('locations.id', $filters['location']);
        }

        if (!empty($filters['dateFrom'])) {
            $query->whereMonth('sm.last_updated', '=', date('m', strtotime($filters['dateFrom'])))
                ->whereYear('sm.last_updated', '=', date('Y', strtotime($filters['dateFrom'])));
        }

        // Optional: Log query for debugging
        Log::debug("SQL: " . $query->toSql());
        Log::debug("Bindings: ", $query->getBindings());

        return $query->get();
    }
}
