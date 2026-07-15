<?php

namespace Modules\StockManagement\Repositories;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use Modules\Locations\Enums\LocationType as LocationEnum;

use Modules\StockManagement\Models\StockIn\MasterStockIn as MasterRecord;
use Modules\StockManagement\Models\StockIn\StockPurchase;
use Modules\StockManagement\Models\StockIn\StockPurchaseItem;
use Modules\StockManagement\Models\StockSummary\StockSummary;
use Modules\StockManagement\Models\Warehouse\WarehouseInventory as Warehouse;
use Modules\ShopManagement\Models\ShopInventory as Shop;

use Exception;
use Modules\StockManagement\Exceptions\Purchase\StockPurchaseException;

/**
 * Sumamry table is used to reduce the load on database while -
 * running aggregation for dashbaord
 * 
 * Todo - 1. Move the class to the StockIn Directory
 *        2. Remove the N+1 query issue here to reduce database load
 * 
 */

class StockPurchaseRepository
{

    /**
     * Foreign key referencing the `master_stock_in` table.
     * This ID is obtained after creating a new MasterStockIn record.
     * Used to associate related purchase and item records.
     */

    private readonly int $masterStockInId;

    /**
     * Foreign key referencing the `stock_purchases` table.
     * This ID is assigned after creating a StockPurchase record.
     * Used when inserting individual purchase item entries.
     */

    private int $stockPurchaseId;

    public function __construct(
        protected \Modules\StockLedger\Services\StockLedgerService $ledgerService
    ) {}

    /**
     * Sets the dynamic items in the validated data from the services
     */

    private array $items;

    /**
     * Isolated dynamic data items from the data array 
     * Method chained for readability in services
     * 
     * @param $items
     */

    public function setItems(array $items): self
    {
        $this->items = $items;
        return $this;
    }

    /**
     * Creates a new stock purchase entry using a database transaction.
     *
     * This method wraps the following operations in a single database transaction:
     * 1. Creating a master stock record.
     * 2. Creating related purchase item entries.
     * 3. Creating summary table entries
     *
     * @param array $data Data required to create the stock purchase.
     *
     * @throws StockPurchaseException if the transaction fails.
     */

    public function create(array $data): void
    {
        try {
            DB::transaction(function () use ($data) {

                $this->masterEntry($data);
                $this->purchaseEntires($this->items);
            });
        } catch (\Exception $e) {

            Log::debug($e->getMessage());
            throw new StockPurchaseException();
        }
    }

    /**
     * Handles insertion on both Purchase and Purchase items table
     * Primary key of the purchase table is inserted as foreign key in the -
     * purchase_items table
     * Summary table is also updated with each purchase
     * 
     * Stock summary can not handle partial stock delivery as of now
     * 
     * Todo - remove the N+1 query issue here to reduce database load
     * */

    private function purchaseEntires(array $purchaseItems): void
    {
        Log::debug($purchaseItems);

        foreach ($purchaseItems as $item) {

            $purchase = StockPurchase::create([
                'master_stock_in_id' => $this->masterStockInId,
                'vendor'             => $item['vendor'],
                'invoice_number'     => $item['invoice_number'],
                'purchase_date'      => $item['purchase_date'],
                'batch_code'         => $item['batch_code'],
            ]);

            $this->stockPurchaseId = $purchase->id;

            StockPurchaseItem::create([
                'stock_in_purchase_id' => $this->stockPurchaseId,
                'location_id'          => $item['location_id'],
                'product'              => $item['product_id'],
                'batch'                => $item['batch_code'],
                'grade'                => $item['grade'],
                'quantity'             => $item['quantity'],
                'unit'                 => $item['unit'],
                'unit_cost'            => $item['unit_cost'],
                'total'                => $item['total'],
                'remarks'              => $item['remarks'] ?? null,
            ]);

            // Call Centralized Ledger Entry which logs and syncs all balance tables
            $this->ledgerService->recordEntry([
                'transaction_type' => 'PURCHASE',
                'location_id'      => $item['location_id'],
                'product_id'       => $item['product_id'],
                'batch_code'       => $item['batch_code'],
                'grade'            => $item['grade'] ?? null,
                'quantity'         => $item['quantity'],
                'unit'             => $item['unit'],
                'unit_cost'        => $item['unit_cost'],
                'reference_id'     => $this->stockPurchaseId,
                'reference_type'   => 'stock_purchases',
                'remarks'          => $item['remarks'] ?? null,
            ]);

        }
    }




    private function masterEntry(array $data): void
    {
        $master = MasterRecord::create([
            'reference_number'     => $data['reference_no'],
            'stock_in_type'        => $data['in_type'],
            'stock_movement_type'  => $data['stock_type'],
            'stock_in_date'        => $data['movement_date'],
        ]);

        $this->masterStockInId = $master->id;
    }

    /**
     * Batch code is mutated into the $items array in the PurchaService
     */

    private function  createWarehouseinventory($items)
    {
        Log::debug("received on warehosue");

        Warehouse::create([
            'warehouse_id' => $items['location_id'],
            'batch' => $items['batch_code'],
            'product_id' => $items['product_id'],
            'grade' => $items['grade'],
            'qty' => $items['quantity'],
            'unit_cost' => $items['unit_cost'],
        ]);

    }


    private function createShopInventory($items)
    {
        Log::debug("received on shop");

        Shop::create([
            'shop_id' => $items['location_id'],
            'batch_id' => $items['batch_code'],
            'product_id' => $items['product_id'],
            'grade' => $items['grade'],
            'qty' => $items['quantity'],
            'unit_cost' => $items['unit_cost'],
        ]);

    }
}
