<?php

namespace Modules\FleetManagement\Services\FleetSale;

use Modules\FleetManagement\Repository\RouteSaleRepository;
use Modules\FleetManagement\Exceptions\Fleet\FleetPaymentStoreFailureException;
use Illuminate\Support\Facades\Log;

/**
 * Todo -  Check if assigned stock for route and total sale are same
 */

class StoreRouteSalePayments
{
    protected RouteSaleRepository $repo;

    protected $grandTotal;

    public function __construct(RouteSaleRepository $repo)
    {
        $this->repo = $repo;
    }

    /**
     * Store route sale payments payload using RouteSaleRepository.
     *
     * @param array $payload The payment data to store.
     * @return void
     *
     * @throws FleetPaymentStoreFailureException If storing fails.
     */
    public function store(array $payload): void
    {
        $this->calculateGrandTotal($payload);

        $stored = $this->repo->store($payload, $this->grandTotal);

        if (!$stored) {
            throw new FleetPaymentStoreFailureException("Failed to store payload");
        }
    }

    /**
     * Calculate the grand total for the sold items.
     * 
     * Resetting $grandTotal to zero to prevent state carry-over
     * if the same service instance is reused.
     * 
     * Todo: Use a DTO to ensure $payload is always validated.
     * 
     * @param array $payload
     * @return void
     */
    private function calculateGrandTotal(array $payload): void
    {
        $this->grandTotal = 0;

        if (!isset($payload['items']) || !is_array($payload['items'])) {
            throw new FleetPaymentStoreFailureException("Invalid or missing 'items' in payload.");
        }
        $this->grandTotal = array_sum(
            array_map('floatval', array_column($payload['items'], 'total_price'))
        );
    }
}
