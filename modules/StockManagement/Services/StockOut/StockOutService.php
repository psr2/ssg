<?php

namespace Modules\StockManagement\Services\StockOut;

use Modules\Inventory\Models\UnitOfMeasurement as Unit;
use Modules\Inventory\Models\Products as Products;
use Modules\StockManagement\Repositories\StockOut\StockOutRepository;
use Modules\StockManagement\Models\StockSummary\StockSummary;
use Modules\StockManagement\Models\Warehouse\WarehouseInventory;
use Modules\ShopManagement\Models\ShopInventory;
use Modules\Locations\Models\LocationModel;

class StockOutService
{
    protected StockOutRepository $repo;

    public function __construct(StockOutRepository $repo)
    {
        $this->repo = $repo;
    }

    /**
     * Record Stock Out transaction
     */
    public function recordStockOut(array $data): void
    {
        // 🔹 Map product/unit names → IDs before inserting
        $mappedData = $this->mapProductAndUnit($data);

        // 🔹 Insert via repository
        $this->repo->createStockOut($mappedData);

        // 🔹 Deduct stock from summary and stores
        $this->deductStock($mappedData);
    }

    /**
     * Convert product/unit names to IDs
     */
    private function mapProductAndUnit(array $data): array
    {
        foreach ($data['items'] as &$item) {
            $unitModel = Unit::where('abbreviation', $item['unit'])
                ->orWhere('name', $item['unit'])
                ->first();
            $item['unit_id'] = $unitModel ? $unitModel->id : 1; // Fallback unit ID
        }
        return $data;
    }

    private function deductStock(array $data): void
    {
        foreach ($data['items'] as $item) {
            // 1. Decrement Stock Summary
            $summary = StockSummary::where([
                'product_id'  => $item['product_id'],
                'location_id' => $item['location_id'],
                'batch_id'    => $item['batch_code'],
                'grade'       => $item['grade'],
            ])->first();

            if ($summary) {
                $summary->current_qty -= $item['quantity'];
                $summary->save();
            }

            // 2. Decrement Location Inventory (Warehouse or Shop)
            $locationType = $this->getLocationType((int) $item['location_id']);

            if ($locationType === 'warehouse') {
                $inventory = WarehouseInventory::where([
                    'warehouse_id' => $item['location_id'],
                    'batch'        => $item['batch_code'],
                    'product_id'   => $item['product_id'],
                ])->first();

                if ($inventory) {
                    $inventory->qty -= $item['quantity'];
                    $inventory->save();
                }
            } else if ($locationType === 'shop') {
                $inventory = ShopInventory::where([
                    'shop_id'    => $item['location_id'],
                    'batch_id'   => $item['batch_code'],
                    'product_id' => $item['product_id'],
                ])->first();

                if ($inventory) {
                    $inventory->qty -= $item['quantity'];
                    $inventory->save();
                }
            }
        }
    }

    private function getLocationType(int $locationId): string
    {
        $loc = LocationModel::find($locationId);
        if (!$loc) {
            return 'shop'; // Default fallback
        }
        $type = $loc->type;
        if ($type instanceof \Modules\Locations\Enums\LocationType) {
            return $type->value;
        }
        return (string) $type;
    }
}
