<?php

namespace Modules\StockManagement\Services\StockMovement\BatchCode;

use BatchCodeInterface;
use Illuminate\Support\Facades\Log;
use Modules\Inventory\Models\Products;
use Modules\Locations\Models\LocationModel as Location;

use Modules\Inventory\Exceptions\Products\ProductAbbreviationNotFoundException;
use Modules\Locations\Exceptions\LocationAbbreviationNotFoundException;

/**
 * Generates a unique, human-readable batch code.
 *
 * Implements the BatchCodeInterface as a concrete strategy.
 * Further implementations may use other strategies via the strategy pattern.
 * use constructor injection in the codebase
 */

class GenerateBatchCode
{
    /**
     * Generates a unique, human-readable batch code.
     *
     * Format:  {MonthCode}{YearCode}-{ProductAbbr}-{VendorPrefix}-{LocationAbbr}-{Random Digits}
     * Example: AT25-LAP-IT-PUN-55
     *
     * @param string $productName
     * @param string $vendor
     * @param string $grade // Optional — not used currently
     * @param string $location
     *
     * @return string
     *
     * @throws ProductAbbreviationNotFoundException
     * @throws LocationAbbreviationNotFoundException
     */


    public function generateBatchCode($productId, $vendor, $location): string
    {
        $batchCode = [
            $this->getMonthCode() . $this->getYearCode(),
            $this->getProductAbbreviation($productId),
            $this->getVendorPrefix($vendor),
            $this->getLocationAbbreviation($location),
            $this->randomDigits(),
        ];

        return implode('-', $batchCode);
    }


    /**
     * Returns the first and last uppercase letters of the current month.
     *
     * Example: "August" => "AT"
     *
     * @return string
     */
    private function getMonthCode(): string
    {
        $month = date('F');
        return strtoupper($month[0] . $month[strlen($month) - 1]);
    }

    /**
     * Returns the last two digits of current year.
     *
     * Example: "2025" => "25"
     *
     * @return string
     */
    private function getYearCode(): string
    {
        return  date('y');
    }

    /**
     * Returns two random digits as a string.
     *
     * @return string
     */
    private function randomDigits(): string
    {
        return str_pad(strval(rand(0, 99)), 2, '0', STR_PAD_LEFT);
    }

    /**
     * Returns the first and last uppercase letters of the vendor name.
     * Falls back to single letter if name is too short.
     *
     * @param string $vendor
     * @return string
     */
    private function getVendorPrefix(string $vendor): string
    {
        $vendor = trim($vendor);

        if (strlen($vendor) === 1) {
            return strtoupper($vendor[0] . $vendor[0]);
        }

        return strtoupper($vendor[0] . $vendor[strlen($vendor) - 1]);
    }

    /***
     *  TODO: 1. Move this logic to Internal API for maintainability 
     *        2. Wrong exception is used here
     */
    private function getProductAbbreviation(string $productId): string
    {
        Log::debug('product id is' . $productId);

        $abbreviation = Products::where('id', $productId)->value('abbreviation');

        Log::debug('product abbreviation is' . $abbreviation);


        if (!$abbreviation) {
            Log::warning('Product abbreviation not found');
            throw new ProductAbbreviationNotFoundException(
                "Product abbreviation not found for: {$productId}. " .
                "Error thrown from class - " . get_class($this)
            );
        }
        return strtoupper($abbreviation);
    }

    // TODO: Move this logic to Internal API for maintainability 
    private function getLocationAbbreviation(int $locationId): string
    {
        $abbreviation = Location::where('id', $locationId)->value('abbreviation');

        Log::debug('location abbreviation is' . $abbreviation);

        if (!$abbreviation) {
            Log::error('Location abbreviation not found',
            "Location abbreviation not found for: {$locationId}. " .
            "Error thrown from class - " . get_class($this)
        );

            throw new LocationAbbreviationNotFoundException(
                "Location abbreviation not found " 
            );
        }

        return strtoupper($abbreviation);
    }
}
