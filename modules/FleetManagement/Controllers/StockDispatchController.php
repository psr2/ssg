<?php

namespace Modules\FleetManagement\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Modules\FleetManagement\Services\Dispatch\StockDispatcher;


class StockDispatchController extends Controller
{

    public function __construct(

        protected StockDispatcher $dispatch

    ){}
    
    public function handleStockDispatch(Request $request)
    {
        $validated = Validator::make($request->all(), [
            'trip'         => 'required|exists:fleet_trips,id',
            'product'      => 'required|exists:products,id',
            'qtySent'      => 'required|numeric|min:0.01',
            'location'     => 'required|exists:locations,id',
            'batch'        => 'string',
            'qtyReturned'  => 'numeric|min:0',
        ]);

        if ($validated->fails()) {
            return response()->json([
                'errors' => $validated->errors()
            ], 422);
        }

        $this->dispatch->handle($validated->validated());

      
        return response()->json([
            'message' => 'Stock dispatched successfully.'
        ]);
    }
}
