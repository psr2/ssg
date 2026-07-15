<?php

namespace Modules\StockManagement\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Modules\StockManagement\Requests\StockOut\StockOutRequest;
use Modules\StockManagement\Services\StockOut\StockOutService;



class StockOutController extends Controller
{
   public function stockOut(StockOutRequest $validated ,StockOutService $stockOut):JsonResponse{

      Log::debug($validated->all());
      
      $stockOut->recordStockOut($validated->validated());

      return response()->json([
            'success' => true,
            'message' => 'Stock entry saved successfully.'
        ],201);
   }

   /**
    * Search batches available for stock out.
    */
   public function searchBatches(Request $request, \Modules\StockManagement\Repositories\BatchCode\BatchCodeRepository $repo)
   {
       $results = $repo->search($request->all());
       return response()->json($results);
   }
}
