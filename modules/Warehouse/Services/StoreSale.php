<?php

declare(strict_types=1);

namespace Modules\Warehouse\Services;

use Modules\Warehouse\Exceptions\WarehouseSaleFailedException;
use Modules\Warehouse\Repositories\WarehouseSaleRepository;

class StoreSale
{
    protected float $grandTotal = 0;

    public function __construct(protected WarehouseSaleRepository $repo) {}

    /**
     * Calculate grand total and delegate to repository.
     *
     * @throws WarehouseSaleFailedException
     */
    public function process(mixed $payload): void
    {
        $this->calculateGrandTotal($payload);
        $this->repo->handle($payload, $this->grandTotal);
    }

    private function calculateGrandTotal(mixed $payload): void
    {
        if (!isset($payload['items']) || !is_array($payload['items']) || empty($payload['items'])) {
            throw new WarehouseSaleFailedException("Invalid or missing 'items' in payload.");
        }

        $this->grandTotal = array_sum(
            array_map(fn($item) => (float) $item['total_price'], $payload['items'])
        );
    }
}
