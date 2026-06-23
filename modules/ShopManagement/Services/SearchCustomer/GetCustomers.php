<?php

namespace Modules\ShopManagement\Services\SearchCustomer;

use Modules\ShopManagement\Models\ShopCustomer;
use Illuminate\Support\Facades\Log;




/**
 * Todo - move db search to a SearchCustomerRepository
 */


class GetCustomers
{

    public function allCustomers($id )
    {
      
        $customers = ShopCustomer::select('id', 'name')
            ->where("shop_id", $id)
            ->pluck('name' ,'id');

        Log::debug($customers);

        if ($customers->isEmpty()) {

            Log::warning("No shop customers found ");

            return response()->json([
                'success' => false,
                'message' => 'No shop customers found .',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $customers,
        ]);
    }
}
