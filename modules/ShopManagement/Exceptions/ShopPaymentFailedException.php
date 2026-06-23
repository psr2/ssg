<?php

namespace Modules\ShopManagement\Exceptions;

use RuntimeException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class ShopPaymentFailedException extends RuntimeException
{
    /**
     * Default error message.
     */
    protected $message = 'Fleet Payment Storing Failed';

    /**
     * Default error code.
     */
    protected $code = 8110;

    /**
     * Constructor.
     */
    public function __construct(?string $message = null, ?int $code = null, ?Throwable $previous = null)
    {
        parent::__construct(
            $message ?? $this->message,
            $code ?? $this->code,
            $previous
        );
    }

    /**
     * Render the exception into an HTTP JSON response.
     */
    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(), // ✅ Laravel-style message
            'errors' => [
                'common' => [$this->getMessage()] // ✅ standard "errors.common" key
            ]
        ], 422);
    }

    /**
     * Log the exception.
     */
    public function report(): void
    {
        Log::error('ShopPaymentStoreFailure: ' . $this->getMessage());
    }
}
