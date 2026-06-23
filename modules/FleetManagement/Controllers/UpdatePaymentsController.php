<?php

namespace Modules\FleetManagement\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\FleetManagement\Requests\UpdatePaymentRequest;
use Modules\FleetManagement\Services\FleetSale\UpdateBalancePayments;
use Illuminate\Support\Facades\App; // Add this at the top if not already imported


class UpdatePaymentsController extends Controller
{
    protected UpdateBalancePayments $balance;

    public function __construct(UpdateBalancePayments $balance)
    {
        $this->balance = $balance;
    }

    public function index(UpdatePaymentRequest $request)
    {
        try {
            $result = $this->balance->update($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Payment updated successfully.',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            // Basic error structure
            $errorResponse = [
                'success' => false,
                'message' => 'Payment update failed.',
                'error' => $e->getMessage(),
            ];

            // If app is in debug mode, add stack trace
            if (App::hasDebugModeEnabled()) {
                $errorResponse['trace'] = $e->getTrace();
            }

            return response()->json($errorResponse, 500);
        }
    }
}
