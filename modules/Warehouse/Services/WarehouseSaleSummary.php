<?php

namespace Modules\Warehouse\Services;

use Modules\Warehouse\Models\WarehouseSale;
use Illuminate\Support\Facades\DB;

class WarehouseSaleSummary
{
    /**
     * Paginated listing of warehouse sales with customer names and latest bill number.
     */
    public function handle(int $perPage, array $options = [])
    {
        // Subquery: get the latest payment reference_number per sale
        $latestPaymentSub = DB::table('warehouse_payments as wp1')
            ->select('wp1.sale_id', 'wp1.reference_number')
            ->whereRaw('wp1.created_at = (SELECT MAX(wp2.created_at) FROM warehouse_payments wp2 WHERE wp2.sale_id = wp1.sale_id)')
            ->groupBy('wp1.sale_id', 'wp1.reference_number');

        $query = WarehouseSale::query()
            ->select([
                'warehouse_sales.id as sale_id',
                'warehouse_sales.paid_amount',
                'warehouse_sales.total_amount',
                'warehouse_sales.due_amount',
                'warehouse_sales.customer_id',
                'warehouse_sales.updated_at as last_updated',
                'warehouse_sales.created_at',
                'warehouse_customers.name as customer_name',
                'latest_payments.reference_number as bill_no',
            ])
            ->join('warehouse_customers', 'warehouse_sales.customer_id', '=', 'warehouse_customers.id')
            ->leftJoinSub($latestPaymentSub, 'latest_payments', function ($join) {
                $join->on('warehouse_sales.id', '=', 'latest_payments.sale_id');
            });

        // Filter: only show records with outstanding balance
        if (!empty($options['only_due'])) {
            $query->where('warehouse_sales.due_amount', '>', 0);
        }

        $sortableFields = [
            'warehouse_sales.due_amount',
            'warehouse_sales.total_amount',
            'warehouse_sales.paid_amount',
            'warehouse_sales.created_at',
            'warehouse_customers.name',
        ];

        $sortBy    = $options['sort_by'] ?? 'warehouse_sales.created_at';
        $sortOrder = $options['sort_order'] ?? 'desc';

        if (in_array($sortBy, $sortableFields) && in_array(strtolower($sortOrder), ['asc', 'desc'])) {
            $query->orderBy($sortBy, $sortOrder);
        }

        return $query->paginate($perPage);
    }
}
