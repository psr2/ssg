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
                $itemsSummary = $record->items->map(function ($item) {
                    $gradeStr = $item->grade ? " ({$item->grade})" : "";
                    $qty = (float)$item->quantity == (int)$item->quantity ? (int)$item->quantity : (float)$item->quantity;
                    return "{$qty} {$item->unit} {$item->product_name}{$gradeStr}";
                })->filter(function ($str) {
                    return !str_starts_with($str, '0 ');
                })->implode(', ');

                $totalAmount = (float) $record->total_amount;
                $paid = (float) $record->paid;
                
                // If total amount is 0, the bill was cancelled/zeroed out via billing adjustment
                $isCancelled = ($totalAmount === 0.0);
                $balance = $isCancelled ? 0.00 : max(0.00, $totalAmount - $paid);
                
                if ($isCancelled) {
                    $status = 'cancelled';
                } elseif ($balance <= 0) {
                    $status = 'paid';
                } else {
                    $status = 'partial';
                }

                return [
                    'bill_id' => $record->id,
                    'bill_number' => $record->bill_number,
                    'customer_name' => $record->customer_name,
                    'items_summary' => $itemsSummary ?: 'Cancelled Bill',
                    'total_amount' => number_format($totalAmount, 2),
                    'created_at' => $record->created_at->format('Y-m-d H:i:s'),
                    'paid' => number_format($paid, 2),
                    'balance' => number_format($balance, 2),
                    'status' => $status,
                    'is_cancelled' => $isCancelled,
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
