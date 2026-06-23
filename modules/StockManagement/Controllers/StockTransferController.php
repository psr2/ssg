<?php

namespace Modules\StockManagement\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;

use Modules\StockManagement\Requests\StockTransfer\StockTransferRequest;
use Modules\StockManagement\Services\StockTransfer\StockTransfer;
use Modules\StockManagement\Exceptions\StockOut\StockTransferFailedException;


class StockTransferController extends Controller
{

    public function __construct(protected StockTransfer $transfer) {}


    public function index(StockTransferRequest $withRequest)
    {
        try {
            $this->transfer->transferStock($withRequest->validated());
            return response()->json(['message' => 'Stock transfer successful'], 200);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json(['error' => 'Unexpected error occurred.'], 500);
        }
    }
}
