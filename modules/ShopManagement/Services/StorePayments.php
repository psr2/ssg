<?php

declare(strict_types=1);

namespace Modules\ShopManagement\Services;

use Modules\ShopManagement\Exceptions\ShopPaymentFailedException;
use Modules\ShopManagement\Repository\ShopSaleRepository;


class StorePayments
{
    protected float $grandTotal = 0;

    public function __construct(protected ShopSaleRepository $repo){}

    /**
     * Process the payment and create sale.
     *
     * @param array $payload
     * @throws ShopPaymentFailedException
     */
    public function processPayment( $payload): void
    {
        $this->calculateGrandTotal($payload);
        $this->repo->handle($payload, $this->grandTotal);
    }

    /**
     * Calculate total amount of the sale from items.
     *
     * @param array $payload
     * @throws ShopPaymentFailedException
     */
    private function calculateGrandTotal( $payload): void
    {
        if (!isset($payload['items']) || !is_array($payload['items']) || empty($payload['items'])) {
            throw new ShopPaymentFailedException("Invalid or missing 'items' in payload.");
        }

        $this->grandTotal = array_sum(
            array_map(fn($item) => (float)$item['total_price'], $payload['items'])
        );
    }

    
}
