<?php

namespace Modules\ShopManagement\Controllers\Sale;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Modules\ShopManagement\Services\SearchCustomer\GetCustomers;

/**
 * Collects name , id of all customers and pass it to UI for searching customer names using fuss.js
 */

class CustomerLookupController extends Controller
{

    public function __construct(private GetCustomers $lookup) {}

    
    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'shop_id' => 'required',
        ]);

       return $this->lookup->allCustomers($request->input('shop_id'));
    }
}
