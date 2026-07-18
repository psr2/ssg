<?php

namespace Modules\Warehouse\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Modules\Warehouse\Requests\WarehouseSaleRequest;
use Modules\Warehouse\Requests\WarehousePaymentUpdateRequest;
use Modules\Warehouse\Services\StoreSale;
use Modules\Warehouse\Services\UpdatePayment;
use Modules\Inventory\API\Contracts\ProductInterface;

class WarehouseSaleController extends Controller
{
    public function __construct(
        protected StoreSale       $sale,
        protected ProductInterface $products,
        protected UpdatePayment   $update,
        protected \Modules\Warehouse\Repositories\WarehouseSaleRepository $repo
    ) {}


    /**
     * Store a new warehouse sale.
     */
    public function store(WarehouseSaleRequest $validatedPayload)
    {

        try {
            $this->sale->process($validatedPayload->validated());

            return response()->json([
                'message' => 'Warehouse sale recorded successfully.',
            ], 200);

        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('DB error during warehouse sale:', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Error code W-5001: A database error occurred. Please try again or contact support.',
            ], 500);

        } catch (\Modules\Warehouse\Exceptions\WarehouseSaleFailedException $e) {
            Log::warning('Warehouse sale failed:', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => $e->getMessage(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('Unexpected warehouse sale exception:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Error code W-5002: An unexpected error occurred. Please try again or contact support.',
            ], 500);
        }
    }

    /**
     * Return the product list for the dynamic sale modal.
     */
    public function productList()
    {
        return json_encode($this->products->shareProductList(), true);
    }

    /**
     * Update an existing sale's payment (partial → full settlement).
     */
    public function updatePayments(WarehousePaymentUpdateRequest $request)
    {
        try {
            $this->update->handle($request);

            return response()->json([
                'message' => 'Payment updated successfully.',
            ], 200);

        } catch (\Exception $e) {
            Log::error('Warehouse payment update error:', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function searchBatches(\Illuminate\Http\Request $request, \Modules\Warehouse\Repositories\WarehouseBatchCodeRepository $repo)
    {
        $results = $repo->search($request->all());
        return response()->json($results);
    }
}
