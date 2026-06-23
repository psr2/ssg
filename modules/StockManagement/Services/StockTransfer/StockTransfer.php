<?php

namespace Modules\StockManagement\Services\StockTransfer;

use Modules\StockManagement\Repositories\StockTransfer\StockTransferRepository;
use Modules\StockManagement\Exceptions\StockTransfer\StockTransferFailedException;
use Illuminate\Support\Facades\Log;

/**
 * Handles stock transfer between locations, including stock increment and decrement.
 * 
 * The stock transfer process is based on the location, and the stock levels are updated accordingly.
 * - Stock is incremented or decremented based on the transfer direction (from one location to another).
 * - The underlying business logic for stock changes (increment and decrement) is handled separately.
 * 
 * Stock updates are performed within the **Repository layer** to ensure data consistency and separation of concerns.
 * 
 * 
 * Todo - transfer based on batch code and grade
 * @throws  StockTransferFailedException If the stock transfer fails due to insufficient stock or other business rule violations.
 */

class StockTransfer
{
    public function __construct(protected StockTransferRepository $repo) {}

    /**
     * Handle stock transfer for single  product
     */
    public function transferStock(array $payload): bool
    {
        try {

            if($this->repo->handle($payload)){
                Log::debug("success");
            }

            return true;
            
        } catch (\Exception $e) {

            throw new StockTransferFailedException("Stock transfer failed: " . $e->getMessage());
        }
    }
}
