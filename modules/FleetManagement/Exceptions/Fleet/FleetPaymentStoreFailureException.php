<?php

namespace Modules\FleetManagement\Exceptions\Fleet;

use RuntimeException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class FleetPaymentStoreFailureException extends RuntimeException
{
    /**
     * Default error message.
     *
     * @var string
     */
    protected  $message = 'Fleet payment storing failed';

     /**
     * Default database error code.
     *
     * @var 
     */

    protected  $code=8110;

    /**
     * Constructor.
     *
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct(?string $message=null , ?int $code=null , ?Throwable $previous = null)
    {
        parent::__construct(
        $message ?? $this->message,
        $code ?? $this->code,
        $previous
        );
    }

    /**
     * Render the exception into an HTTP response.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => "Fleet Payment Storing Failed",
            'errors' => [
                'common' => ['Fleet Payment Storing Failed']
            ]
        ], 422);
    }

    /**
     * Report or log the exception.
     *
     * @return void
     */
    public function report(): void
    {
        // Add custom logging logic here, e.g., to Sentry or Laravel's Log
        Log::error('FleetPaymentStoreFailure: ' . $this->getMessage());
    }
}