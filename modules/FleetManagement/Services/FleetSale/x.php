<?php

namespace Modules\FleetManagement\Services\FleetSale;

use Illuminate\Support\Facades\DB;

class FleetCreditReportService
{
    /**
     * Get pending credit payments filtered by route.
     *
     * @param int $routeId
     * @return \Illuminate\Support\Collection
     */
    public function getPendingCreditsByRoute(int $routeId)
    {
        return DB::table('fleet_sales as fs')
            ->leftJoin('fleet_sale_payments as fsp', 'fsp.fleet_sale_id', '=', 'fs.id')
            ->leftJoin('fleet_trips as ft', 'ft.id', '=', 'fs.fleet_trip_id')
            ->leftJoin('fleet_routes as fr', 'fr.id', '=', 'ft.route_id')
            ->selectRaw("
                fs.id AS sale_id,
                fs.bill_number,
                fs.customer_name,
                fs.total_amount,
                COALESCE(SUM(fsp.amount), 0) AS total_paid,
                (fs.total_amount - COALESCE(SUM(fsp.amount), 0)) AS pending_amount,
                ft.id AS trip_id,
                ft.route_id,
                fr.name AS route_name
            ")
            ->where('ft.route_id', $routeId)
            ->groupBy(
                'fs.id',
                'fs.bill_number',
                'fs.customer_name',
                'fs.total_amount',
                'ft.id',
                'ft.route_id',
                'fr.name'
            )
            ->having('pending_amount', '>', 0)
            ->get();
    }
}
