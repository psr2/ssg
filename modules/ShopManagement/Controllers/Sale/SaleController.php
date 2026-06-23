<?php

namespace Modules\ShopManagement\Controllers\Sale;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;

use Modules\ShopManagement\Requests\PaymentUpdateRequest;
use Modules\ShopManagement\Requests\ShopPaymentRequest;
use Modules\ShopManagement\Services\StorePayments;
use Modules\Inventory\API\Contracts\ProductInterface;
use Modules\ShopManagement\Services\UpdatePayment;

class SaleController extends Controller
{

    public function __construct(
        protected StorePayments $shop,
        protected ProductInterface $products,
        protected UpdatePayment $update)
    {}

    /**
     * Todo - if there is any bug the entire error message is sent to user which is a security bug , fix it
     */
    public function store(ShopPaymentRequest $validatedPayload)
    {
        Log::debug("call reached controller store method");

        try {
            // Process the payment
            $this->shop->processPayment($validatedPayload);

            return response()->json([
                'message' => 'Payment processed successfully.'
            ], 200);
        } catch (\Illuminate\Database\QueryException $e) {
            // Handles database-related errors (like duplicate keys, etc.)
            Log::error('Database error during payment:', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Error code - 58005 occurred .Please try again or contact support.'
            ], 500);
        } catch (\Modules\ShopManagement\Exceptions\ShopPaymentFailedException $e) {
            // Custom business exception (like missing inventory)
            Log::warning('Payment failed:', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            // Fallback: catch anything else
            Log::error('Unexpected payment exception:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Error code - 58006 occurred .Please try again or contact support.'
            ], 500);
        }
    }

    public function productList()
    {
        return $products = json_encode($this->products->shareProductList(), true);
    }

    public function updatePayments(PaymentUpdateRequest $request){


         $this->update->handle($request);
        
    }
}
