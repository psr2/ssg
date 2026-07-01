<?php

namespace Modules\StockLedger\Controllers;

use App\Http\Controllers\Controller;
use Modules\StockLedger\Services\StockAdjustment\StockAdjustmentService;
use Modules\StockManagement\Models\StockIn\StockPurchaseItem;
use Modules\Locations\API\Internal\Contracts\LocationsInterface;
use Modules\StockLedger\Requests\StockAdjustment\StockAdjustmentRequest;
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
        $items = StockPurchaseItem::with(['location', 'productRelation', 'gradeRelation'])
            ->orderBy('id', 'desc')
            ->paginate(request('per_page', 15));

        $ledgerService = app(\Modules\StockLedger\Services\StockLedgerService::class);

        // Map over the items collection to inject live ledger data
        $items->getCollection()->transform(function ($item) use ($ledgerService) {
            $latestUnit = $ledgerService->getLatestUnit($item->location_id, $item->product, $item->batch, $item->grade);
            $latestLocationId = $ledgerService->getLatestLocationId($item->location_id, $item->product, $item->batch, $item->grade);
            $availableQty = $ledgerService->getAvailableStock($latestLocationId, $item->product, $item->batch, $item->grade);

            $item->quantity = $availableQty;
            $item->unit = $latestUnit;
            
            if ($latestLocationId != $item->location_id) {
                $item->location_id = $latestLocationId;
                $item->load('location');
            }

            return $item;
        });

        // Get all locations from the interface
        $locations = $this->locationsService->shareLocation(); // assuming this returns JSON or array
        $locations = is_string($locations) ? json_decode($locations, true) : $locations;

        return view('stock_ledger::stock_adjustments', compact('items', 'locations'));
    }

    public function adjustStock(StockAdjustmentRequest $request)
    {
        $this->stock->adjust($request->validated());

        return response()->json(['message' => 'Stock adjusted successfully']);
    }

    public function voidStock(int $id, \Illuminate\Http\Request $request)
    {
        $request->validate([
            'remarks' => ['required', 'string', 'min:10', 'max:500'],
        ]);

        try {
            $this->stock->void($id, $request->input('remarks'));
            return response()->json(['message' => 'Stock entry voided successfully.']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
