<?php

namespace Modules\Warehouse\Exceptions;

use RuntimeException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class WarehouseSaleFailedException extends RuntimeException
{
    protected $message = 'Warehouse Sale Failed';
    protected $code    = 9200;

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
            'errors'  => [
                'common' => [$this->getMessage()]
            ]
        ], 422);
    }

    public function report(): void
    {
        Log::error('WarehouseSaleFailure: ' . $this->getMessage());
    }
}
