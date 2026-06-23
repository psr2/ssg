<?php

namespace Modules\FleetManagement\Services\FleetSale;

use Modules\FleetManagement\Repository\FleetSale\SaleRecordsRepository;
use Illuminate\Support\Facades\Log;

/**
 * Todo - Replace the scan method name later
 *  - watch for pagination issues   
 *  - throw a custom exception
 * 
 */

class SaleRecords
{
    protected $saleRecordsRepository;

    public function __construct(SaleRecordsRepository $saleRecordsRepository)
    {
        $this->saleRecordsRepository = $saleRecordsRepository;
    }

    public function index($perPage)
    {
        try {
            $records = $this->saleRecordsRepository->getSaleRecords($perPage);


            $records->getCollection()->transform(function ($record) {
                return [
                    'bill_id'=>$record->id,
                    'bill_number' => $record->bill_number,
                    'customer_name' => $record->customer_name,
                    'total_amount' => number_format($record->total_amount, 2),
                    'created_at' => $record->created_at->format('Y-m-d H:i:s'),
                    'paid' => number_format($record->paid, 2),
                    'balance' => number_format($record->balance, 2),
                ];
            });
            return $records;
        } catch (\Illuminate\Database\QueryException $e) {
            throw new \Exception('Failed to fetch sale records: ' . $e->getMessage(), 500);
        }
    }

    public function scan($page)
    {
        return $this->index($page);
    }
}
