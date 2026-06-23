<?php

namespace Modules\StockManagement\Services\StockMovement\ReferenceNumber;

use Illuminate\Support\Facades\Config;

class StockReturnReferenceNumberGenerator
{
    const REFERENCE_NUMBER_PREFIX = 'RTN';

    /**
     * Generate unique purchase reference number.
     * Tech debt:: generate reference number by swoole async.
     * Move const to a new config file in the module if possible later at some point.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function generate(): \Illuminate\Http\JsonResponse
    {
        $date_without_hyphens = str_replace('-', '', now()->format('d-m-Y'));

        // Generate random number with leading zeros
        $random_number = str_pad(random_int(0, 99999), 5, '0', STR_PAD_LEFT);

        // Create the formatted reference number
        $reference_number = self::REFERENCE_NUMBER_PREFIX . '-' . $date_without_hyphens . '-' . $random_number;

        // Return formatted reference number as a JSON response
        return response()->json([
            'success' => true,
            'reference_no' => $reference_number,
            'message' => 'Stock return reference number generated successfully.'
        ]);
    }
}
