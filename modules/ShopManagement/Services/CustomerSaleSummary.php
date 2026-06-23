<?php

namespace Modules\ShopManagement\Services;

use Modules\ShopManagement\Models\ShopSale;
use Illuminate\Support\Facades\DB;

class CustomerSaleSummary
{
    /**
     * Handle customer sale summary with pagination, optional filtering and sorting.
     *
     * @param int $perPage
     * @param array $options
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function handle(int $perPage, array $options = [])
    {
        // Subquery to get latest payment per sale
        $latestPaymentSubquery = DB::table('shop_payments as sp1')
            ->select('sp1.sale_id', 'sp1.reference_number')
            ->whereRaw('sp1.created_at = (SELECT MAX(sp2.created_at) FROM shop_payments sp2 WHERE sp2.sale_id = sp1.sale_id)')
            ->groupBy('sp1.sale_id', 'sp1.reference_number');

        $query = ShopSale::query()
            ->select([
                'shop_sales.id as sale_id',
                'shop_sales.paid_amount',
                'shop_sales.total_amount',
                'shop_sales.due_amount',
                'shop_sales.customer_id',
                'shop_sales.updated_at as last_updated',
                'shop_sales.created_at',
                'shop_customers.name as customer_name',
                'latest_payments.reference_number as bill_no',
            ])
            ->join('shop_customers', 'shop_sales.customer_id', '=', 'shop_customers.id')
            ->leftJoinSub($latestPaymentSubquery, 'latest_payments', function ($join) {
                $join->on('shop_sales.id', '=', 'latest_payments.sale_id');
            });

        // Filter: Only show records with due amount > 0
        if (!empty($options['only_due'])) {
            $query->where('shop_sales.due_amount', '>', 0);
        }

        // Whitelist of sortable fields (for safety)
        $sortableFields = [
            'shop_sales.due_amount',
            'shop_sales.total_amount',
            'shop_sales.paid_amount',
            'shop_sales.created_at',
            'shop_customers.name',
        ];

        $sortBy = $options['sort_by'] ?? 'shop_sales.created_at';
        $sortOrder = $options['sort_order'] ?? 'desc';

        if (in_array($sortBy, $sortableFields) && in_array(strtolower($sortOrder), ['asc', 'desc'])) {
            $query->orderBy($sortBy, $sortOrder);
        }

        return $query->paginate($perPage);
    }
}
