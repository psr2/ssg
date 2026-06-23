<?php

namespace Modules\StockManagement\Controllers;

use App\Http\Controllers\Controller;
use Modules\StockManagement\Services\StockAdjustment\StockAdjustmentService;
use Modules\StockManagement\Models\StockIn\StockPurchaseItem;
use Modules\Locations\API\Internal\Contracts\LocationsInterface;
use Modules\StockManagement\Requests\StockAdjustment\StockAdjustmentRequest;
use Illuminate\Support\Facades\Log;

class StockAdjustmentController extends Controller
{

    public function __construct(
        protected LocationsInterface $locationsService,
        protected StockAdjustmentService $stock
    ){}

    public function index()
    {
        // Get all stock items
        $items = StockPurchaseItem::with('location')
            ->orderBy('id', 'desc')
            ->paginate(request('per_page', 15));

        // Get all locations from the interface
        $locations = $this->locationsService->shareLocation(); // assuming this returns JSON or array
        $locations = is_string($locations) ? json_decode($locations, true) : $locations;

        return view('stock_management::stock_adjustments', compact('items', 'locations'));
    }

    public function adjustStock(StockAdjustmentRequest $request){

        
        $this->stock->adjust($request->validated());

        return response()->json(['message' => 'Stock adjusted successfully']);
    }
}
