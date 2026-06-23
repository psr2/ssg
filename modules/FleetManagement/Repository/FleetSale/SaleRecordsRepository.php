<?php

namespace Modules\FleetManagement\Repository\FleetSale;

use Modules\FleetManagement\Models\FleetSale;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SaleRecordsRepository
{
    const DEFAULT_RECORDS_PER_PAGE=10;
public function getSaleRecords($perPage = self::DEFAULT_RECORDS_PER_PAGE)
{
    return FleetSale::query()
        ->select([
            'fleet_sales.id',
            'fleet_sales.bill_number',
            'fleet_sales.customer_name',
            'fleet_sales.total_amount',
            'fleet_sales.created_at',
            DB::raw('(SELECT COALESCE(SUM(amount),0) FROM fleet_sale_payments WHERE fleet_sale_payments.fleet_sale_id = fleet_sales.id) as paid'),
            DB::raw('(fleet_sales.total_amount - (SELECT COALESCE(SUM(amount),0) FROM fleet_sale_payments WHERE fleet_sale_payments.fleet_sale_id = fleet_sales.id)) as balance')
        ])
        ->leftJoin('fleet_sale_items', 'fleet_sales.id', '=', 'fleet_sale_items.fleet_sale_id')
        ->groupBy('fleet_sales.id', 'fleet_sales.bill_number', 'fleet_sales.customer_name', 'fleet_sales.total_amount', 'fleet_sales.created_at')
        ->paginate($perPage);
}

}
