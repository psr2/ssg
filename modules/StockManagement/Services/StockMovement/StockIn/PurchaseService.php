<?php

namespace Modules\StockManagement\Services\StockMovement\StockIn;

use Modules\StockManagement\Repositories\StockPurchaseRepository;
use Modules\StockManagement\Services\StockMovement\BatchCode\GenerateBatchCode;
use Modules\Locations\Models\LocationModel as Location;
use Illuminate\Support\Facades\Log;

/**
 * Handles stock purchase 
 * Todo - Update the warehosue table entires as well in the repo
 */

class PurchaseService
{
  
    private $validatedData;

    public function __construct(
        protected StockPurchaseRepository $purchaseRepository,
        protected GenerateBatchCode $batchCodeService
    ) {}

    /**
     * Handles the creation of a purchase entry.
     *
     * @param array $validatedData
     * @return void
     */
    public function createStockIn(array $validatedData): void
    {
        Log::debug('reached service');
        $this->set($validatedData);

        // Mutate the array with batch codes
        $withMutated = $this->mutateArray();

        Log::debug($withMutated);

        // Pass the mutated data to the repository
        $this->purchaseRepository
            ->setItems($withMutated['items'])
            ->create($withMutated);
    }

    /**
     * Sets the validated data into the service.
     *
     * @param array $validated
     * @return void
     */
    private function set(array $validated): void
    {
        $this->validatedData = $validated;
    }

    /**
     * Mutates the validated data by injecting a unique batch code into each item
     * using `GenerateBatchCode` service based on product, vendor, and location for 
     * supply chain traceability.Kept separate from repository operations to ensure
     * flexible and maintainable business rule updates.
     *     
     * @return array The mutated data with each item containing an additional 'batch_code' field.
     */
    private function mutateArray(): array
    {
        $mutatedData = $this->validatedData;

        // Loop through each item in the validated data and inject the batch code
        foreach ($mutatedData['items'] as &$item) {
            $item['batch_code'] = $this->batchCodeService->generateBatchCode(
                $item['product_id'],
                $item['vendor'],
                $item['location_id'],
            );

            $item['location_name'] = Location::find($item['location_id'])->type;

        }

        return $mutatedData;
    }
}
