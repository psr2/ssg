<?php

namespace Modules\Dashboard\Services\Dashboard;

use Modules\Locations\Models\LocationModel;
use Modules\Inventory\Models\Products;

class GetDashboardData
{
    protected StockLedgerSummary $ledgerSummary;
    protected TotalCreditSummary $creditSummary;
    protected LowStockSumamry $lowStockSummary;
    protected StockAlertsService $alertsService;

    public function __construct(
        StockLedgerSummary $ledgerSummary,
        TotalCreditSummary $creditSummary,
        LowStockSumamry $lowStockSummary,
        StockAlertsService $alertsService
    ) {
        $this->ledgerSummary = $ledgerSummary;
        $this->creditSummary = $creditSummary;
        $this->lowStockSummary = $lowStockSummary;
        $this->alertsService = $alertsService;
    }

    /**
     * Get all necessary dashboard data.
     *
     * @return array
     */
    public function execute(): array
    {
        // Get Warehouses and Shops
        $warehouses = LocationModel::where('type', 'warehouse')->get();
        $shops = LocationModel::where('type', 'shop')->get();
        $locations = LocationModel::all()->keyBy('id');

        // 1. Calculate Ledger Stocks
        $ledgerData = $this->ledgerSummary->calculateLedgerStocks($warehouses, $shops, $locations);

        // 2. Calculate Receivables
        $receivablesData = $this->creditSummary->getReceivables($warehouses, $shops);

        // 3. Calculate Low Stock
        $lowStockData = $this->lowStockSummary->calculateLowStock(
            $ledgerData['locProductStock'],
            $warehouses,
            $shops,
            $locations
        );

        // 4. Build Stock Alerts
        $stockAlerts = $this->alertsService->getStockAlerts(
            $ledgerData['pairs'],
            $ledgerData['locProductStock'],
            $locations
        );

        // 5. Fetch all products for the dropdown select lists
        $productList = Products::select('id', 'name')->orderBy('name')->get();

        return [
            'warehouse'         => $ledgerData['warehouseTotal'],
            'shopStock'         => $ledgerData['shopTotal'],
            'warehouses'        => $warehouses,
            'shops'             => $shops,
            'warehouseStocks'   => $ledgerData['warehouseStocks'],
            'shopStocks'        => $ledgerData['shopStocks'],
            'warehouseDues'     => $receivablesData['warehouseDues'],
            'shopDues'          => $receivablesData['shopDues'],
            'totalReceivables'  => $receivablesData['totalReceivables'],
            'lowStockTotal'     => $lowStockData['lowStockTotal'],
            'lowStockCounts'    => $lowStockData['lowStockCounts'],
            'stockAlerts'       => $stockAlerts,
            'productList'       => $productList,
            'warehouseStockMap' => $ledgerData['warehouseStockMap'],
            'shopStockMap'      => $ledgerData['shopStockMap'],
        ];
    }
}
