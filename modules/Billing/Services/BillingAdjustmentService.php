<?php

namespace Modules\Billing\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Billing\Models\BillingAdjustment;
use Modules\Warehouse\Models\WarehouseSale;
use Modules\ShopManagement\Models\ShopSale;
use Modules\FleetManagement\Models\FleetSale;

class BillingAdjustmentService
{
    /**
     * Create and apply a billing adjustment to a sale.
     *
     * @param array $data
     * @return BillingAdjustment
     * @throws ValidationException
     */
    public function createAdjustment(array $data): BillingAdjustment
    {
        return DB::transaction(function () use ($data) {
            $saleType = $data['sale_type'];
            $saleId = $data['sale_id'];
            $newAmount = (float) $data['new_amount'];

            if ($newAmount < 0) {
                throw ValidationException::withMessages([
                    'new_amount' => ['Billed amount cannot be negative.']
                ]);
            }

            // 1. Resolve and validate the sale
            $sale = $this->resolveSale($saleType, $saleId, true);

            if (!$sale) {
                throw ValidationException::withMessages([
                    'sale_id' => ["The selected {$saleType} sale ID {$saleId} does not exist."]
                ]);
            }

            $originalAmount = (float) $sale->total_amount;
            $adjustedAmount = $newAmount - $originalAmount;

            // 2. Update the sale's financial state
            if ($saleType === 'fleet') {
                $sale->total_amount = $newAmount;
            } else {
                // Warehouse / Shop sales track paid and due amounts
                $sale->total_amount = $newAmount;
                $sale->due_amount = $newAmount - (float) $sale->paid_amount;
            }
            $sale->save();

            // 3. Record the adjustment audit trail
            return BillingAdjustment::create([
                'sale_type'       => $saleType,
                'sale_id'         => $saleId,
                'original_amount' => $originalAmount,
                'adjusted_amount' => $adjustedAmount,
                'new_amount'      => $newAmount,
                'reason'          => $data['reason'],
                'adjusted_by'     => $data['adjusted_by'] ?? auth()->id() ?? 1,
                'remarks'         => $data['remarks'] ?? null,
            ]);
        });
    }

    public function resolveSale(string $type, $id, bool $lock = false)
    {
        $query = null;
        switch ($type) {
            case 'warehouse':
                $query = WarehouseSale::query();
                break;
            case 'shop':
                $query = ShopSale::query();
                break;
            case 'fleet':
                $query = FleetSale::query();
                break;
            default:
                return null;
        }

        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->find($id);
    }

    /**
     * Get a list of potential sales for dropdown/search.
     */
    public function getPendingSalesForType(string $type): array
    {
        switch ($type) {
            case 'warehouse':
                return WarehouseSale::with('customer')
                    ->orderBy('id', 'desc')
                    ->get()
                    ->map(fn($s) => [
                        'id' => $s->id,
                        'label' => "WS-{$s->id} - " . ($s->customer->name ?? 'Guest') . " - Date: " . date('Y-m-d', strtotime((string)$s->sale_date)),
                        'amount' => $s->total_amount,
                        'paid' => $s->paid_amount,
                        'due' => $s->due_amount
                    ])
                    ->toArray();

            case 'shop':
                return ShopSale::with('customer')
                    ->orderBy('id', 'desc')
                    ->get()
                    ->map(fn($s) => [
                        'id' => $s->id,
                        'label' => "SS-{$s->id} - " . ($s->customer->name ?? 'Guest') . " - Date: " . date('Y-m-d', strtotime((string)$s->sale_date)),
                        'amount' => $s->total_amount,
                        'paid' => $s->paid_amount,
                        'due' => $s->due_amount
                    ])
                    ->toArray();

            case 'fleet':
                return FleetSale::orderBy('id', 'desc')
                    ->get()
                    ->map(fn($s) => [
                        'id' => $s->id,
                        'label' => "FS-{$s->id} (Bill: " . ($s->bill_number ?? 'N/A') . ") - " . ($s->customer_name ?? 'Guest'),
                        'amount' => $s->total_amount,
                        'paid' => $s->total_amount, // fleet_sales does not track separate due
                        'due' => 0.00
                    ])
                    ->toArray();

            default:
                return [];
        }
    }
}
