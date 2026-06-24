<?php

namespace Modules\Dashboard\Services\Dashboard;

class LowStockSumamry
{
    /**
     * Calculate low stock count (< 10) per product-location combination.
     *
     * @param array $locProductStock
     * @param \Illuminate\Support\Collection $warehouses
     * @param \Illuminate\Support\Collection $shops
     * @param \Illuminate\Support\Collection $locations
     * @return array
     */
    public function calculateLowStock(array $locProductStock, $warehouses, $shops, $locations): array
    {
        $lowStockTotal = 0;
        $lowStockCounts = [];

        foreach ($warehouses as $wh) {
            $lowStockCounts['wh_' . $wh->id] = 0;
        }
        foreach ($shops as $sh) {
            $lowStockCounts['sh_' . $sh->id] = 0;
        }

        foreach ($locProductStock as $lId => $prodStocks) {
            $loc = $locations[$lId] ?? null;
            if (!$loc) {
                continue;
            }

            foreach ($prodStocks as $pId => $qty) {
                if ($qty < 10) {
                    $lowStockTotal++;
                    if ($loc->type === 'shop') {
                        $lowStockCounts['sh_' . $lId]++;
                    } else {
                        $lowStockCounts['wh_' . $lId]++;
                    }
                }
            }
        }

        return [
            'lowStockTotal' => $lowStockTotal,
            'lowStockCounts' => $lowStockCounts,
        ];
    }
}