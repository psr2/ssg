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

        $expandedItems = collect();

        foreach ($items->getCollection() as $item) {
            // Find all locations that have ledger entries for this batch
            $locationIds = \Modules\StockLedger\Models\StockLedgerEntry::where('batch_code', $item->batch)
                ->where('product_id', $item->product)
                ->distinct()
                ->pluck('location_id')
                ->all();

            // Always include the original location as well
            if (!in_array($item->location_id, $locationIds)) {
                $locationIds[] = $item->location_id;
            }

            foreach ($locationIds as $locId) {
                // Get the available stock at this location
                $availableQty = $ledgerService->getAvailableStock($locId, $item->product, $item->batch, $item->grade);

                // We only show the row if there is available stock > 0, OR if it's the original location (to allow corrections)
                if ($availableQty > 0 || $locId == $item->location_id) {
                    $clonedItem = clone $item;
                    $clonedItem->original_location_id = $item->location_id;
                    $clonedItem->original_quantity = $item->quantity;
                    
                    $clonedItem->location_id = $locId;
                    $clonedItem->quantity = $availableQty;
                    
                    $latestUnit = $ledgerService->getLatestUnit($locId, $item->product, $item->batch, $item->grade);
                    $clonedItem->unit = $latestUnit;
                    
                    $clonedItem->load('location');
                    
                    $expandedItems->push($clonedItem);
                }
            }
        }

        $items->setCollection($expandedItems);

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
