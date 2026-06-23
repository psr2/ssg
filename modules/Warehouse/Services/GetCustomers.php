<?php

namespace Modules\Warehouse\Services;

use Modules\Warehouse\Models\WarehouseCustomer;
use Illuminate\Support\Facades\Log;

class GetCustomers
{
    /**
     * Return all customers for a given warehouse, formatted for Fuse.js search.
     */
    public function allCustomers(int|string $warehouseId): \Illuminate\Http\JsonResponse
    {
        $customers = WarehouseCustomer::select('id', 'name')
            ->where('warehouse_id', $warehouseId)
            ->pluck('name', 'id');

        Log::debug('Warehouse customers fetched for warehouse: ' . $warehouseId, ['count' => $customers->count()]);

        if ($customers->isEmpty()) {
            Log::warning('No warehouse customers found for warehouse_id: ' . $warehouseId);

            return response()->json([
                'success' => false,
                'message' => 'No customers found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $customers,
        ]);
    }
}
