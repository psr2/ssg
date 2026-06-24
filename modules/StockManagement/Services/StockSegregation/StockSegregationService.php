<?php

namespace Modules\StockManagement\Services\StockSegregation;

use Illuminate\Support\Facades\DB;
use Modules\StockManagement\Models\StockIn\StockPurchaseItem;
use Modules\StockManagement\Models\Warehouse\WarehouseInventory;
use Modules\StockManagement\Models\StockSummary\StockSummary;
use Modules\StockManagement\Models\Segregation\StockSegregation;
use Modules\StockManagement\Models\Segregation\StockSegregationItem;
use Modules\Inventory\Models\Products as Product;
use Exception;

class StockSegregationService
{
    /**
     * Calculate available stock dynamically for a batch and grade.
     */
    public function getAvailableStock(int $locationId, int $productId, string $batchCode, string $grade): float
    {
        $location = DB::table('locations')->where('id', $locationId)->first();
        $isShop = $location && $location->type === 'shop';

        if ($isShop) {
            $parentInventory = \Modules\ShopManagement\Models\ShopInventory::where('shop_id', $locationId)
                ->where('product_id', $productId)
                ->where('batch_id', $batchCode)
                ->first();
        } else {
            $parentInventory = WarehouseInventory::where('warehouse_id', $locationId)
                ->where('product_id', $productId)
                ->where('batch', $batchCode)
                ->first();
        }

        if (!$parentInventory) {
            return 0.00;
        }

        $parentInventoryGrade = $parentInventory->grade;
        $parentInventoryQty = (float)$parentInventory->qty;

        // 2. Calculate the base quantity for this grade
        if ($grade === $parentInventoryGrade) {
            // Get all other child grades that have been segregated from this batch at this location
            $childGrades = DB::table('stock_segregation_items')
                ->join('stock_segregations', 'stock_segregations.id', '=', 'stock_segregation_items.stock_segregation_id')
                ->where('stock_segregations.location_id', $locationId)
                ->where('stock_segregations.product_id', $productId)
                ->where('stock_segregations.parent_batch_code', $batchCode)
                ->where('stock_segregation_items.grade', '!=', $parentInventoryGrade)
                ->distinct()
                ->pluck('stock_segregation_items.grade')
                ->toArray();

            $sumChildGradesQty = 0.00;
            foreach ($childGrades as $cg) {
                // In a pure ledger, the quantity physically removed from the parent batch is the initial segregated quantity,
                // not the current remaining child stock.
                $sumChildGradesQty += DB::table('stock_segregation_items')
                    ->join('stock_segregations', 'stock_segregations.id', '=', 'stock_segregation_items.stock_segregation_id')
                    ->where('stock_segregations.location_id', $locationId)
                    ->where('stock_segregations.product_id', $productId)
                    ->where('stock_segregations.parent_batch_code', $batchCode)
                    ->where('stock_segregation_items.grade', $cg)
                    ->sum('stock_segregation_items.quantity') ?? 0.00;
            }

            $baseQty = (float)($parentInventoryQty - $sumChildGradesQty);
        } else {
            // For child grades, sum segregated qty at this location
            $baseQty = DB::table('stock_segregation_items')
                ->join('stock_segregations', 'stock_segregations.id', '=', 'stock_segregation_items.stock_segregation_id')
                ->where('stock_segregations.location_id', $locationId)
                ->where('stock_segregations.product_id', $productId)
                ->where('stock_segregations.parent_batch_code', $batchCode)
                ->where('stock_segregation_items.grade', $grade)
                ->sum('stock_segregation_items.quantity') ?? 0.00;
        }

        // 3. Subtract sold qty
        $soldQty = DB::table('warehouse_sale_items')
            ->join('warehouse_sales', 'warehouse_sales.id', '=', 'warehouse_sale_items.sale_id')
            ->where('warehouse_sales.warehouse_id', $locationId)
            ->where('warehouse_sale_items.product_id', $productId)
            ->where('warehouse_sale_items.batch_code', $batchCode)
            ->where('warehouse_sale_items.grade', $grade)
            ->sum('warehouse_sale_items.quantity') ?? 0.00;

        // 4. Subtract stock out qty (filtering by batch_code + grade for precise per-batch deduction)
        $stockOutQuery = DB::table('stock_out_items')
            ->join('master_stock_out', 'master_stock_out.id', '=', 'stock_out_items.stock_out_id')
            ->where('master_stock_out.location_id', $locationId)
            ->where('stock_out_items.product_id', $productId)
            ->where(function ($q) use ($batchCode) {
                // Match by batch_code if stored, or fall back to items with no batch_code recorded
                $q->where('stock_out_items.batch_code', $batchCode)
                  ->orWhereNull('stock_out_items.batch_code');
            });

        if ($grade === $parentInventoryGrade) {
            $stockOutQuery->where(function ($q) use ($grade) {
                $q->where('stock_out_items.grade', $grade)
                  ->orWhereNull('stock_out_items.grade');
            });
        } else {
            $stockOutQuery->where('stock_out_items.grade', $grade);
        }
        $stockOutQty = $stockOutQuery->sum('stock_out_items.quantity') ?? 0.00;

        // 5. Subtract transferred out qty
        $transferredOutQty = DB::table('stock_transfer_items')
            ->join('stock_transfers', 'stock_transfers.id', '=', 'stock_transfer_items.stock_transfer_id')
            ->where('stock_transfers.from_location_id', $locationId)
            ->where('stock_transfer_items.product_id', $productId)
            ->where('stock_transfer_items.batch_code', $batchCode)
            ->where('stock_transfer_items.grade', $grade)
            ->sum('stock_transfer_items.quantity') ?? 0.00;

        // 6. Add transferred in qty
        $transferredInQty = DB::table('stock_transfer_items')
            ->join('stock_transfers', 'stock_transfers.id', '=', 'stock_transfer_items.stock_transfer_id')
            ->where('stock_transfers.to_location_id', $locationId)
            ->where('stock_transfer_items.product_id', $productId)
            ->where('stock_transfer_items.batch_code', $batchCode)
            ->where('stock_transfer_items.grade', $grade)
            ->sum('stock_transfer_items.quantity') ?? 0.00;

        return max(0.00, (float)($baseQty + $transferredInQty - $soldQty - $stockOutQty - $transferredOutQty));
    }

    /**
     * Get details of a batch for segregation.
     *
     * @param string $batchCode
     * @param int $locationId
     * @return array
     * @throws Exception
     */
    public function getBatchDetails(string $batchCode, int $locationId): array
    {
        $item = StockPurchaseItem::where('batch', $batchCode)
            ->where('location_id', $locationId)
            ->first();

        if (!$item) {
            throw new Exception("Parent batch not found in the purchase records.");
        }

        $productName = Product::find($item->product)?->name ?? '—';

        // Check current available quantity of the unsorted grade dynamically
        $availableQty = $this->getAvailableStock($locationId, $item->product, $batchCode, $item->grade);

        return [
            'product_id' => $item->product,
            'product_name' => $productName,
            'original_qty' => $item->quantity,
            'available_qty' => $availableQty,
            'unit_cost' => $item->unit_cost,
            'unit' => $item->unit,
        ];
    }

    /**
     * Process a stock segregation entry.
     *
     * @param array $data
     * @return StockSegregation
     * @throws Exception
     */
    public function processSegregation(array $data): StockSegregation
    {
        return DB::transaction(function () use ($data) {
            $batchCode = $data['parent_batch_code'];
            $locationId = $data['location_id'];
            $productId = $data['product_id'];

            // Find the original purchase item to get the source grade
            $purchaseItem = StockPurchaseItem::where('batch', $batchCode)
                ->where('location_id', $locationId)
                ->first();

            if (!$purchaseItem) {
                throw new Exception("Parent batch not found in the purchase records.");
            }

            $sourceGrade = $purchaseItem->grade;

            // Get available unsorted stock of the parent batch dynamically
            $availableUnsorted = $this->getAvailableStock($locationId, $productId, $batchCode, $sourceGrade);

            // Calculate total output quantity
            $totalOutputQty = 0.00;
            foreach ($data['outputs'] as $output) {
                $totalOutputQty += (float)$output['quantity'];
            }

            if ($availableUnsorted < $totalOutputQty) {
                throw new Exception("Insufficient stock in the parent batch. Available: {$availableUnsorted}, Requested: {$totalOutputQty}");
            }

            // Generate unique reference number
            $referenceNo = 'SEG-' . now()->format('dmY') . '-' . str_pad(random_int(0, 99999), 5, '0', STR_PAD_LEFT);

            // Create the StockSegregation header
            $segregation = StockSegregation::create([
                'reference_no' => $referenceNo,
                'location_id' => $locationId,
                'product_id' => $productId,
                'parent_batch_code' => $batchCode,
                'parent_quantity' => $purchaseItem->quantity,
                'remarks' => $data['remarks'] ?? null,
                'created_by' => null,
                'segregation_date' => $data['segregation_date'],
            ]);

            // Save item details (without modifying warehouse_inventory or stock_summary tables)
            foreach ($data['outputs'] as $output) {
                StockSegregationItem::create([
                    'stock_segregation_id' => $segregation->id,
                    'grade' => $output['grade'],
                    'quantity' => $output['quantity'],
                    'unit' => $output['unit'],
                    'unit_cost' => $output['unit_cost'],
                    'remarks' => $output['remarks'] ?? null,
                ]);
            }

            return $segregation;
        });
    }
}
