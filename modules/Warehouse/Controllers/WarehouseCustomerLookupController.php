<?php

namespace Modules\Warehouse\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Warehouse\Services\GetCustomers;

/**
 * Returns warehouse customer list for Fuse.js-powered autocomplete in the sale modal.
 */
class WarehouseCustomerLookupController extends Controller
{
    public function __construct(private GetCustomers $lookup) {}

    public function index(Request $request)
    {
        $request->validate([
            'shop_id' => 'required|integer|exists:locations,id',
        ]);

        return $this->lookup->allCustomers($request->input('shop_id'));
    }
}
