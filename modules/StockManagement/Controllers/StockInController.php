<?php

namespace Modules\StockManagement\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Modules\StockManagement\Requests\StockIn\PurchaseRequest;
use Modules\StockManagement\Services\StockMovement\StockIn\PurchaseService;

class StockInController extends Controller
{
    public function stockIn(PurchaseRequest $request, PurchaseService $purchaseService)
    {

        Log::debug($request->validated());
        
        $purchaseService->createStockIn($request->validated());
        
        return response()->json([
            'success' => true,
            'message' => 'Stock entry saved successfully.'
        ],201);
    }
}
