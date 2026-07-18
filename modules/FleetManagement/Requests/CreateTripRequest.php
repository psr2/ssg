<?php

namespace Modules\FleetManagement\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;

class CreateTripRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules()
    {
        return [
            // Basic Trip Fields
            'route_id'   => 'required|exists:fleet_routes,id',
            'vehicle_id' => 'required|exists:fleet_vehicles,id',
            'start_date' => 'required|date|after_or_equal:today',
            'tag'        => 'required|string|max:255',

            // Products Sent
            'sent' => 'required|array|min:1',
            'sent.*.product_id'  => 'required|exists:products,id',
            'sent.*.batch'       => 'required|string|max:255',
            'sent.*.grade'       => 'required|string|max:255',
            'sent.*.unit'        => 'required|string|max:255',
            'sent.*.quantity'    => 'required|integer|min:1',
            'sent.*.location_id' => 'required|exists:locations,id',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $sent = $this->input('sent', []);
            if (!is_array($sent)) {
                return;
            }

            $service = app(\Modules\StockLedger\Services\StockLedgerService::class);

            foreach ($sent as $index => $item) {
                $productId  = $item['product_id'] ?? null;
                $locationId = $item['location_id'] ?? null;
                $batchCode  = $item['batch'] ?? null;
                $grade      = $item['grade'] ?? null;
                $unit       = $item['unit'] ?? null;
                $quantity   = $item['quantity'] ?? null;

                // Skip if basic fields are missing (let base validation rules handle it)
                if (!$productId || !$locationId || !$batchCode) {
                    continue;
                }

                // 1. Verify if this batch exists in the specified location for the specified product
                $whBatch = DB::table('warehouse_inventory')
                    ->where('warehouse_id', $locationId)
                    ->where('batch', $batchCode)
                    ->first();

                $shBatch = DB::table('shop_inventory')
                    ->where('shop_id', $locationId)
                    ->where('batch_id', $batchCode)
                    ->first();

                $trBatch = DB::table('stock_transfers as st')
                    ->join('stock_transfer_items as sti', 'st.id', '=', 'sti.stock_transfer_id')
                    ->where('st.to_location_id', $locationId)
                    ->where('sti.batch_code', $batchCode)
                    ->first();

                $batchRecord = $whBatch ?? $shBatch ?? $trBatch;

                if (!$batchRecord) {
                    $validator->errors()->add("sent.{$index}.batch", "The selected batch code '{$batchCode}' is not available at the selected location.");
                    continue;
                }

                // 2. Validate product_id matches the batch
                $dbProductId = $batchRecord->product_id ?? null;
                if ($dbProductId && (int)$dbProductId !== (int)$productId) {
                    $validator->errors()->add("sent.{$index}.product_id", "The selected product does not match the batch code's product.");
                }

                // 3. Validate Grade matches
                $dbGrade = trim($batchRecord->grade ?? '');
                $inputGrade = trim($grade ?? '');
                if (strcasecmp($dbGrade, $inputGrade) !== 0) {
                    $validator->errors()->add("sent.{$index}.grade", "The selected grade '{$inputGrade}' is invalid for this batch. Expected '{$dbGrade}'.");
                }

                // 4. Validate Unit matches
                $product = DB::table('products')
                    ->leftJoin('units', 'products.unit_id', '=', 'units.id')
                    ->where('products.id', $productId)
                    ->select('units.abbreviation', 'units.name')
                    ->first();

                if ($product) {
                    $dbUnitAbbr = trim($product->abbreviation ?? '');
                    $dbUnitName = trim($product->name ?? '');
                    $inputUnit  = trim($unit ?? '');

                    if (strcasecmp($dbUnitAbbr, $inputUnit) !== 0 && strcasecmp($dbUnitName, $inputUnit) !== 0) {
                        $validator->errors()->add("sent.{$index}.unit", "The selected unit '{$inputUnit}' is invalid. Expected '{$dbUnitAbbr}'.");
                    }
                }

                // 5. Validate available quantity
                if ($quantity !== null && $quantity !== '') {
                    if (filter_var($quantity, FILTER_VALIDATE_INT) === false) {
                        $validator->errors()->add("sent.{$index}.quantity", "Quantity must not contain decimals.");
                    } else {
                        $qtyInt = (int)$quantity;
                        if ($qtyInt <= 0) {
                            $validator->errors()->add("sent.{$index}.quantity", "Quantity must be greater than zero.");
                        } else {
                            $availableQty = $service->getAvailableStock(
                                (int)$locationId,
                                (int)$productId,
                                $batchCode,
                                $dbGrade
                            );

                            if ($qtyInt > $availableQty) {
                                $validator->errors()->add("sent.{$index}.quantity", "Insufficient stock. Available quantity is {$availableQty}.");
                            }
                        }
                    }
                }
            }
        });
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'errors'  => $validator->errors(),
            'message' => 'Validation failed',
        ], 422));
    }

    public function messages()
    {
        return [
            // Trip
            'route_id.required'   => 'Please select a route.',
            'route_id.exists'     => 'The selected route does not exist.',
            'vehicle_id.required' => 'Please select a vehicle.',
            'vehicle_id.exists'   => 'The selected vehicle does not exist.',
            'start_date.required' => 'Trip start date is required.',
            'start_date.date'     => 'Start date must be a valid date.',
            'start_date.after_or_equal' => 'Start date must be today or a future date.',
            'tag.required'        => 'Tag is required.',

            // Sent products
            'sent.required'                 => 'At least one sent product must be added.',
            'sent.*.product_id.required'    => 'Product is required.',
            'sent.*.product_id.exists'      => 'The selected product is invalid.',
            'sent.*.quantity.required'      => 'Quantity is required.',
            'sent.*.quantity.integer'       => 'Quantity must be a whole number (no decimals allowed).',
            'sent.*.quantity.min'           => 'Quantity must be greater than zero.',
            'sent.*.location_id.required'   => 'Location is required for each sent item.',
            'sent.*.location_id.exists'     => 'The selected location is invalid.',
            'sent.*.batch.required'         => 'Batch is required for each sent item.',
            'sent.*.grade.required'         => 'Grade is required for each sent item.',
            'sent.*.unit.required'          => 'Unit is required for each sent item.',
        ];
    }
}
