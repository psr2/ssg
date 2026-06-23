<?php

namespace Modules\Warehouse\Exceptions;

use RuntimeException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class CustomerNotFoundException extends RuntimeException
{
    protected $message = 'Customer Not Found';
    protected $code    = 9201;

    public function __construct(?string $message = null, ?int $code = null, ?Throwable $previous = null)
    {
        parent::__construct(
            $message ?? $this->message,
            $code    ?? $this->code,
            $previous
        );
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
        ], 422);
    }

    public function report(): void
    {
        Log::warning('WarehouseCustomerNotFound: ' . $this->getMessage());
    }
}
