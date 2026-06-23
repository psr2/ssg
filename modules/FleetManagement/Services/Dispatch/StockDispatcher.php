<?php

namespace Modules\FleetManagement\Services\Dispatch;

use Exception;
use Modules\FleetManagement\Repository\Dispatch\StockDispatchRepository;
use Illuminate\Support\Facades\DB;
use Modules\FleetManagement\Exceptions\StockDispatch\StockDispatchException;
use Modules\Common\Exceptions\InsufficientStockException;
use Modules\FleetManagement\Exceptions\StockDispatch\InvalidReturnQuantityException;

/**
 * Service responsible for handling the fleet dispatch business logic.
 *
 * Todo :: Work on concurrency and DB level locking
 * 
 * This includes:
 * - Delegating persistence to the repository
 * - Stock summary table update
 */
class StockDispatcher
{
    /**
     * Inject the StockDispatchRepository dependency.
     *
     * @param StockDispatchRepository $repo Repository handling DB operations
     */
    public function __construct(
        protected StockDispatchRepository $repo
    ) {}

    /**
     * Main handler method to process stock dispatch.
     *
     * Wraps the dispatch logic in a database transaction to ensure
     * data consistency between multiple related operations.
     *
     * @param array $data Validated data required for dispatch processing
     *
     * @throws InsufficientStockException when qty_sent > current stock in stock summary table
     * @throws StockDispatchException when any error occurs during dispatch
     */
    public function handle(array $data)
    {
        DB::beginTransaction();

        try {

            $this->isReturnQuantityValid($data);
            // Check stock availability before proceeding
            $this->checkStockAvailability($data);

            // Persist dispatch entries
            $this->repo->storeDispatchEntries($data);

            // Update stock summary to reflect dispatched stock
            $this->repo->updateStockSummary($data);

            DB::commit();

            //Thrown when summary table does not have enough stock for required location  
        } catch (InsufficientStockException $insufficientStockException) {
            DB::rollBack();
            throw $insufficientStockException;

            //Thrown  when sent quantity is less than return quantity
        } catch (InvalidReturnQuantityException $invalidReturnQuantityException) {
            DB::rollBack();
            throw $invalidReturnQuantityException;
        } catch (StockDispatchException $stockDispatchException) {
            DB::rollBack();
            throw $stockDispatchException;
        } catch (\Throwable $other) {
            DB::rollBack();
            throw new StockDispatchException("An error occurred during dispatch: {$other->getMessage()}", 0, $other);
        }
    }



    private function checkStockAvailability($data)
    {
        $existingStock = $this->repo->hasStock($data);

        if ($existingStock < $data["qtySent"]) {
            throw new InsufficientStockException();
        }
    }

    private function isReturnQuantityValid($data)
    {

        if ($data['qtyReturned'] > $data['qtySent']) {
            throw new InvalidReturnQuantityException();
        }
    }
}
