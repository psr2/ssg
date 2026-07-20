<?php

namespace Modules\Reporting\Repositories;

use Illuminate\Support\Facades\DB;

class ReportingRepository
{
    /**
     * Get Stock & Inventory Report data across Warehouses and Shops.
     */
    public function getStockInventoryReport(array $filters = []): array
    {
        $query = DB::table('products as p')
            ->leftJoin('units as u', 'p.unit_id', '=', 'u.id')
            ->leftJoin('warehouse_inventory as wi', 'p.id', '=', 'wi.product_id')
            ->leftJoin('shop_inventory as si', 'p.id', '=', 'si.product_id')
            ->select(
                'p.id as product_id',
                'p.name as product_name',
                'p.sku',
                'p.category',
                'u.abbreviation as unit',
                DB::raw('COALESCE(SUM(DISTINCT wi.qty), 0) as warehouse_qty'),
                DB::raw('COALESCE(SUM(DISTINCT si.qty), 0) as shop_qty')
            )
            ->groupBy('p.id', 'p.name', 'p.sku', 'p.category', 'u.abbreviation');

        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($search) {
                $q->where('p.name', 'like', $search)
                  ->orWhere('p.sku', 'like', $search)
                  ->orWhere('p.category', 'like', $search);
            });
        }

        $items = $query->get()->map(function ($item) {
            // Aggregate warehouse inventory by grade
            $whGrades = DB::table('warehouse_inventory')
                ->where('product_id', $item->product_id)
                ->select('grade', DB::raw('SUM(qty) as qty'))
                ->groupBy('grade')
                ->get();

            $shGrades = DB::table('shop_inventory')
                ->where('product_id', $item->product_id)
                ->select('grade', DB::raw('SUM(qty) as qty'))
                ->groupBy('grade')
                ->get();

            $item->warehouse_qty = (float) $whGrades->sum('qty');
            $item->shop_qty = (float) $shGrades->sum('qty');
            $item->total_qty = $item->warehouse_qty + $item->shop_qty;
            $item->grade_breakdown = $whGrades->pluck('qty', 'grade')->toArray();
            return $item;
        });

        return $items->toArray();
    }

    /**
     * Get Stock Ledger & Movement Audit Report data.
     */
    public function getStockLedgerReport(array $filters = []): array
    {
        $query = DB::table('stock_ledger_entries as sle')
            ->join('products as p', 'sle.product_id', '=', 'p.id')
            ->leftJoin('locations as l', 'sle.location_id', '=', 'l.id')
            ->select(
                'sle.id',
                'sle.created_at',
                'sle.transaction_type',
                'p.name as product_name',
                'p.sku',
                'l.name as location_name',
                'sle.batch_code',
                'sle.grade',
                'sle.quantity',
                'sle.unit',
                'sle.unit_cost',
                'sle.remarks'
            )
            ->orderBy('sle.created_at', 'desc');

        if (!empty($filters['start_date'])) {
            $query->whereDate('sle.created_at', '>=', $filters['start_date']);
        }
        if (!empty($filters['end_date'])) {
            $query->whereDate('sle.created_at', '<=', $filters['end_date']);
        }
        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($search) {
                $q->where('p.name', 'like', $search)
                  ->orWhere('sle.batch_code', 'like', $search)
                  ->orWhere('sle.transaction_type', 'like', $search);
            });
        }
        if (!empty($filters['transaction_type'])) {
            $query->where('sle.transaction_type', $filters['transaction_type']);
        }

        return $query->get()->toArray();
    }

    /**
     * Get Warehouse Sales Report data.
     */
    public function getWarehouseSalesReport(array $filters = []): array
    {
        $query = DB::table('warehouse_sales as ws')
            ->leftJoin('warehouse_customers as wc', 'ws.customer_id', '=', 'wc.id')
            ->leftJoin('locations as l', 'ws.warehouse_id', '=', 'l.id')
            ->select(
                'ws.id as sale_id',
                'ws.sale_date',
                'l.name as warehouse_name',
                DB::raw("COALESCE(wc.name, 'Walk-in Customer') as customer_name"),
                'wc.phone as customer_phone',
                'ws.total_amount',
                'ws.paid_amount',
                'ws.due_amount',
                'ws.created_at'
            )
            ->orderBy('ws.sale_date', 'desc');

        if (!empty($filters['start_date'])) {
            $query->whereDate('ws.sale_date', '>=', $filters['start_date']);
        }
        if (!empty($filters['end_date'])) {
            $query->whereDate('ws.sale_date', '<=', $filters['end_date']);
        }
        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($search) {
                $q->where('wc.name', 'like', $search)
                  ->orWhere('ws.id', 'like', $search)
                  ->orWhere('l.name', 'like', $search);
            });
        }

        return $query->get()->map(function ($row) {
            $row->status = $row->due_amount <= 0 ? 'PAID' : ($row->paid_amount > 0 ? 'PARTIAL' : 'CREDIT');
            return $row;
        })->toArray();
    }

    /**
     * Get Shop Sales Report data.
     */
    public function getShopSalesReport(array $filters = []): array
    {
        $query = DB::table('shop_sales as ss')
            ->leftJoin('shop_customers as sc', 'ss.customer_id', '=', 'sc.id')
            ->select(
                'ss.id as sale_id',
                'ss.sale_date',
                DB::raw("COALESCE(sc.name, 'Walk-in Customer') as customer_name"),
                'sc.phone as customer_phone',
                'ss.total_amount',
                'ss.paid_amount',
                'ss.due_amount',
                'ss.created_at'
            )
            ->orderBy('ss.sale_date', 'desc');

        if (!empty($filters['start_date'])) {
            $query->whereDate('ss.sale_date', '>=', $filters['start_date']);
        }
        if (!empty($filters['end_date'])) {
            $query->whereDate('ss.sale_date', '<=', $filters['end_date']);
        }
        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($search) {
                $q->where('sc.name', 'like', $search)
                  ->orWhere('ss.id', 'like', $search);
            });
        }

        return $query->get()->map(function ($row) {
            $row->status = $row->due_amount <= 0 ? 'PAID' : ($row->paid_amount > 0 ? 'PARTIAL' : 'CREDIT');
            return $row;
        })->toArray();
    }

    /**
     * Get Fleet Sales & Trips Report data.
     */
    public function getFleetSalesReport(array $filters = []): array
    {
        $query = DB::table('fleet_sales as fs')
            ->leftJoin('fleet_trips as ft', 'fs.fleet_trip_id', '=', 'ft.id')
            ->leftJoin('fleet_routes as fr', 'ft.route_id', '=', 'fr.id')
            ->leftJoin('fleet_vehicles as fv', 'ft.vehicle_id', '=', 'fv.id')
            ->select(
                'fs.id as sale_id',
                'fs.bill_number',
                'fs.customer_name',
                'fs.total_amount',
                'fs.created_at',
                'ft.id as trip_id',
                'fr.name as route_name',
                'fv.registration_number as vehicle_reg'
            )
            ->orderBy('fs.created_at', 'desc');

        if (!empty($filters['start_date'])) {
            $query->whereDate('fs.created_at', '>=', $filters['start_date']);
        }
        if (!empty($filters['end_date'])) {
            $query->whereDate('fs.created_at', '<=', $filters['end_date']);
        }
        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($search) {
                $q->where('fs.customer_name', 'like', $search)
                  ->orWhere('fs.bill_number', 'like', $search)
                  ->orWhere('fr.name', 'like', $search)
                  ->orWhere('fv.registration_number', 'like', $search);
            });
        }

        return $query->get()->toArray();
    }

    /**
     * Get Expense Report data.
     */
    public function getExpenseReport(array $filters = []): array
    {
        $query = DB::table('expenses as e')
            ->leftJoin('expense_categories as ec', 'e.category_id', '=', 'ec.id')
            ->select(
                'e.id',
                'e.expense_date',
                'ec.name as category_name',
                'e.amount',
                'e.payment_mode',
                'e.paid_to',
                'e.description',
                'e.reference_id',
                'e.created_at'
            )
            ->orderBy('e.expense_date', 'desc');

        if (!empty($filters['start_date'])) {
            $query->whereDate('e.expense_date', '>=', $filters['start_date']);
        }
        if (!empty($filters['end_date'])) {
            $query->whereDate('e.expense_date', '<=', $filters['end_date']);
        }
        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($search) {
                $q->where('e.paid_to', 'like', $search)
                  ->orWhere('e.description', 'like', $search)
                  ->orWhere('ec.name', 'like', $search);
            });
        }

        return $query->get()->toArray();
    }

    /**
     * Get Stock Adjustment Report data.
     */
    public function getStockAdjustmentReport(array $filters = []): array
    {
        $query = DB::table('stock_adjustments as sa')
            ->join('products as p', 'sa.product_id', '=', 'p.id')
            ->leftJoin('locations as l', 'sa.location_id', '=', 'l.id')
            ->select(
                'sa.id',
                'sa.created_at',
                'p.name as product_name',
                'l.name as location_name',
                'sa.batch_code',
                'sa.grade',
                'sa.original_qty',
                'sa.adjusted_qty',
                'sa.new_qty',
                'sa.reason',
                'sa.status',
                'sa.remarks'
            )
            ->orderBy('sa.created_at', 'desc');

        if (!empty($filters['start_date'])) {
            $query->whereDate('sa.created_at', '>=', $filters['start_date']);
        }
        if (!empty($filters['end_date'])) {
            $query->whereDate('sa.created_at', '<=', $filters['end_date']);
        }
        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($search) {
                $q->where('p.name', 'like', $search)
                  ->orWhere('sa.batch_code', 'like', $search)
                  ->orWhere('sa.reason', 'like', $search);
            });
        }

        return $query->get()->toArray();
    }

    /**
     * Get Consolidated Outstanding Credits & Receivables Report.
     */
    public function getCreditsReport(array $filters = []): array
    {
        // 1. Warehouse Unpaid Sales
        $whSales = DB::table('warehouse_sales as ws')
            ->leftJoin('warehouse_customers as wc', 'ws.customer_id', '=', 'wc.id')
            ->select(
                DB::raw("'Warehouse' as source_module"),
                DB::raw("CONCAT('WH-SALE-', ws.id) as ref_number"),
                DB::raw("COALESCE(wc.name, 'Walk-in Customer') as customer_name"),
                'wc.phone as customer_phone',
                'ws.sale_date as invoice_date',
                'ws.total_amount',
                'ws.paid_amount',
                'ws.due_amount'
            )
            ->where('ws.due_amount', '>', 0);

        // 2. Shop Unpaid Sales
        $shopSales = DB::table('shop_sales as ss')
            ->leftJoin('shop_customers as sc', 'ss.customer_id', '=', 'sc.id')
            ->select(
                DB::raw("'Shop' as source_module"),
                DB::raw("CONCAT('SHOP-SALE-', ss.id) as ref_number"),
                DB::raw("COALESCE(sc.name, 'Walk-in Customer') as customer_name"),
                'sc.phone as customer_phone',
                'ss.sale_date as invoice_date',
                'ss.total_amount',
                'ss.paid_amount',
                'ss.due_amount'
            )
            ->where('ss.due_amount', '>', 0);

        $combined = $whSales->unionAll($shopSales)->get();

        if (!empty($filters['search'])) {
            $search = strtolower($filters['search']);
            $combined = $combined->filter(function ($row) use ($search) {
                return str_contains(strtolower($row->customer_name), $search) ||
                       str_contains(strtolower($row->ref_number), $search) ||
                       str_contains(strtolower($row->source_module), $search);
            });
        }

        return $combined->values()->toArray();
    }

    /**
     * Get inventory summary metrics across locations for dashboard widgets.
     */
    public function getInventorySummary(): array
    {
        $warehouseStock = DB::table('warehouse_inventory')
            ->select('product_id', 'grade', DB::raw('SUM(qty) as total_qty'))
            ->groupBy('product_id', 'grade')
            ->get();

        $shopStock = DB::table('shop_inventory')
            ->select('product_id', 'grade', DB::raw('SUM(qty) as total_qty'))
            ->groupBy('product_id', 'grade')
            ->get();

        return [
            'warehouse_stock' => $warehouseStock,
            'shop_stock' => $shopStock,
        ];
    }
}

