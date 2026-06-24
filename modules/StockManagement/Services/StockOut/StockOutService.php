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
        // 🔹 In a pure ledger-based system, we do not physically mutate inventory records.
        // Available stock is calculated dynamically in the application layer.
        // Thus, we skip direct table mutations here.
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
