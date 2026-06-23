<?php

namespace Modules\Expenses\Exceptions;

use RuntimeException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;


Class CategoryNotFoundException extends RuntimeException{
      /**
     * Default error message.
     *
     * @var string
     */
    protected  $message = 'Expense Category  not found';

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
            'message' => "Expense Category  not found",
            'errors' => [
                'common' => ['Expense Category  not found']
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
        Log::error('Expense Category  not found: ' . $this->getMessage());
    }
}