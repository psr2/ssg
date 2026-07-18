<?php

namespace Modules\Dashboard\Services\Dashboard;

use Illuminate\Support\Facades\DB;
use Modules\Inventory\Models\Products;
use Modules\StockManagement\Models\StockIn\StockPurchaseItem;

class StockAlertsService
{
    /**
     * Build Stock Alerts table based on pairs and location product stock.
     *
     * @param array $pairs
     * @param array $locProductStock
     * @param \Illuminate\Support\Collection $locations
     * @return array
     */
    public function getStockAlerts(array $pairs, array $locProductStock, $locations): array
    {
        $products = Products::with('unit')->get()->keyBy('id');

        // Pre-fetch last purchase dates for warehouses
        $lastPurchaseDates = StockPurchaseItem::join('stock_purchase', 'stock_purchase_items.stock_in_purchase_id', '=', 'stock_purchase.id')
            ->select('stock_purchase_items.product', 'stock_purchase_items.location_id', DB::raw('MAX(stock_purchase.purchase_date) as max_date'))
            ->groupBy('stock_purchase_items.product', 'stock_purchase_items.location_id')
            ->get()
            ->keyBy(fn($item) => $item->product . '_' . $item->location_id);

        $stockAlerts = [];
        foreach ($pairs as $key => $pair) {
            $pId = $pair['product_id'];
            $lId = $pair['location_id'];

            $product = $products[$pId] ?? null;
            $location = $locations[$lId] ?? null;

            if (!$product || !$location) {
                continue;
            }

            $currentQty = $locProductStock[$lId][$pId] ?? 0.00;

            // Get received qty for this specific product and location
            if ($location->type === 'shop') {
                $receivedQty = DB::table('stock_transfer_items')
                    ->join('stock_transfers', 'stock_transfers.id', '=', 'stock_transfer_items.stock_transfer_id')
                    ->where('stock_transfers.to_location_id', $lId)
                    ->where('stock_transfer_items.product_id', $pId)
                    ->sum('stock_transfer_items.quantity') ?? 0.00;

                $lastPurchaseDate = DB::table('stock_transfers')
                    ->join('stock_transfer_items', 'stock_transfers.id', '=', 'stock_transfer_items.stock_transfer_id')
                    ->where('stock_transfers.to_location_id', $lId)
                    ->where('stock_transfer_items.product_id', $pId)
                    ->max('stock_transfers.transfer_date') ?? 'N/A';
            } else {
                $purchasedQty = DB::table('stock_purchase_items')
                    ->where('product', $pId)
                    ->where('location_id', $lId)
                    ->sum('quantity') ?? 0.00;

                $transferredQty = DB::table('stock_transfer_items')
                    ->join('stock_transfers', 'stock_transfers.id', '=', 'stock_transfer_items.stock_transfer_id')
                    ->where('stock_transfers.to_location_id', $lId)
                    ->where('stock_transfer_items.product_id', $pId)
                    ->sum('stock_transfer_items.quantity') ?? 0.00;

                $receivedQty = $purchasedQty + $transferredQty;

                $maxPurchaseDate = $lastPurchaseDates[$pId . '_' . $lId]->max_date ?? null;
                $maxTransferDate = DB::table('stock_transfers')
                    ->join('stock_transfer_items', 'stock_transfers.id', '=', 'stock_transfer_items.stock_transfer_id')
                    ->where('stock_transfers.to_location_id', $lId)
                    ->where('stock_transfer_items.product_id', $pId)
                    ->max('stock_transfers.transfer_date') ?? null;

                if ($maxPurchaseDate && $maxTransferDate) {
                    $lastPurchaseDate = max($maxPurchaseDate, $maxTransferDate);
                } else {
                    $lastPurchaseDate = $maxPurchaseDate ?: ($maxTransferDate ?: 'N/A');
                }
            }

            // Percentage remaining
            $percentage = $receivedQty > 0 ? ($currentQty / $receivedQty) * 100 : ($currentQty > 0 ? 100 : 0);

            if ($percentage <= 15) {
                $status = 'Severe (' . number_format($percentage, 0) . '%)';
                $badgeClass = 'bg-danger';
            } elseif ($percentage <= 40) {
                $status = 'Low (' . number_format($percentage, 0) . '%)';
                $badgeClass = 'bg-warning';
            } else {
                $status = 'Normal (' . number_format($percentage, 0) . '%)';
                $badgeClass = 'bg-success';
            }

            $stockAlerts[] = [
                'product_name'   => $product->name,
                'sku'            => $product->sku,
                'category'       => $product->category,
                'location_name'  => $location->name,
                'location_type'  => ucfirst($location->type),
                'current_qty'    => $currentQty,
                'unit'           => $product->unit ? $product->unit->abbreviation : 'kg',
                'received'       => $receivedQty,
                'received_date'  => $lastPurchaseDate,
                'status'         => $status,
                'badge_class'    => $badgeClass,
                'percentage'     => $percentage
            ];
        }

        return $stockAlerts;
    }
}
