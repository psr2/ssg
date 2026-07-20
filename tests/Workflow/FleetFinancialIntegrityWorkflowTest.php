<?php

namespace Tests\Workflow;

use Modules\StockManagement\Models\StockIn\StockPurchase;

class FleetFinancialIntegrityWorkflowTest extends BaseWorkflowTestCase
{
    /**
     * Workflow 25: Fleet Sale Stock Assignment & Quantity Validation
     */
    public function test_workflow_25_fleet_sale_stock_validation(): void
    {
        $ledgerService = app(\Modules\StockLedger\Services\StockLedgerService::class);

        // 1. Purchase: 100 kg of Tomato at Theni Warehouse
        $purchaseResponse = $this->postJson('/stock-in-entry', [
            'stock_type' => 'in',
            'reference_no' => 'PRCH-WF25',
            'movement_date' => now()->format('Y-m-d'),
            'in_type' => 'purchase',
            'items' => [
                [
                    'product_id' => $this->tomato->id,
                    'product_name' => $this->tomato->name,
                    'grade' => $this->gradeBo->code,
                    'location_id' => $this->theniWarehouse->id,
                    'quantity' => 100.00,
                    'unit' => 'kg',
                    'unit_cost' => 10.00,
                    'total' => 1000.00,
                    'remarks' => 'Purchase WF25',
                    'invoice_number' => 'INV-WF25',
                    'vendor' => 'Vendor ABC',
                    'purchase_date' => now()->format('Y-m-d'),
                ]
            ]
        ]);
        $purchaseResponse->assertStatus(201);
        $batchCode = StockPurchase::orderBy('id', 'desc')->first()->batch_code;

        // Create route and vehicle
        $vehicle = \Modules\FleetManagement\Models\FleetVehicle::create([
            'registration_number' => 'KL-06-A-2025',
            'model' => 'Eicher Pro',
            'type' => 'Truck',
            'capacity' => 5000
        ]);
        $route = \Modules\FleetManagement\Models\FleetRoutes::create([
            'name' => 'Route WF25',
            'description' => 'Test Route WF25'
        ]);

        // 2. Create Fleet Trip dispatching 40 kg Tomato (Grade BO, Unit kg)
        $payloadTrip = [
            'route_id'   => $route->id,
            'vehicle_id' => $vehicle->id,
            'start_date' => now()->format('Y-m-d'),
            'tag'        => 'Trip WF25',
            'sent' => [
                [
                    'product_id'  => $this->tomato->id,
                    'batch'       => $batchCode,
                    'grade'       => $this->gradeBo->code,
                    'unit'        => 'kg',
                    'quantity'    => 40,
                    'location_id' => $this->theniWarehouse->id
                ]
            ]
        ];
        $this->postJson('/create-trip', $payloadTrip)->assertStatus(200);
        $tripId = \Modules\FleetManagement\Models\FleetTrip::orderBy('id', 'desc')->first()->id;

        // 3. Attempt to log fleet sale for Tomato with Grade A (not assigned/dispatched on the trip)
        $responseInvalidGrade = $this->postJson('/fleet/sale/store', [
            'trip_id' => $tripId,
            'customer_name' => 'John Doe',
            'bill_no' => 'BILL2501',
            'payment_status' => 'paid',
            'amount_paid' => 200,
            'payment_date' => now()->format('Y-m-d'),
            'payment_mode' => 'cash',
            'items' => [
                [
                    'product' => $this->tomato->name,
                    'grade' => 'A', // not BO
                    'quantity' => 10,
                    'unit' => 'kg',
                    'unit_price' => 20,
                    'total_price' => 200
                ]
            ]
        ]);
        $responseInvalidGrade->assertStatus(422);
        $responseInvalidGrade->assertJsonValidationErrors(['items.0.product']);

        // 4. Attempt to log fleet sale for Tomato with Grade BO in quantity 50 kg (exceeds 40 kg dispatched)
        $responseOverdrawn = $this->postJson('/fleet/sale/store', [
            'trip_id' => $tripId,
            'customer_name' => 'John Doe',
            'bill_no' => 'BILL2502',
            'payment_status' => 'paid',
            'amount_paid' => 1000,
            'payment_date' => now()->format('Y-m-d'),
            'payment_mode' => 'cash',
            'items' => [
                [
                    'product' => $this->tomato->name,
                    'grade' => $this->gradeBo->code,
                    'quantity' => 50,
                    'unit' => 'kg',
                    'unit_price' => 20,
                    'total_price' => 1000
                ]
            ]
        ]);
        $responseOverdrawn->assertStatus(422);
        $responseOverdrawn->assertJsonValidationErrors(['items.0.quantity']);

        // 5. Log valid sale for Tomato Grade BO in quantity 25 kg (succeeds)
        $responseValid1 = $this->postJson('/fleet/sale/store', [
            'trip_id' => $tripId,
            'customer_name' => 'John Doe',
            'bill_no' => 'BILL2503',
            'payment_status' => 'paid',
            'amount_paid' => 500,
            'payment_date' => now()->format('Y-m-d'),
            'payment_mode' => 'cash',
            'items' => [
                [
                    'product' => $this->tomato->name,
                    'grade' => $this->gradeBo->code,
                    'quantity' => 25,
                    'unit' => 'kg',
                    'unit_price' => 20,
                    'total_price' => 500
                ]
            ]
        ]);
        $responseValid1->assertStatus(200);

        // 6. Attempt to log sale for another 20 kg (25 + 20 = 45 > 40, so should fail)
        $responseOverdrawn2 = $this->postJson('/fleet/sale/store', [
            'trip_id' => $tripId,
            'customer_name' => 'Jane Smith',
            'bill_no' => 'BILL2504',
            'payment_status' => 'paid',
            'amount_paid' => 400,
            'payment_date' => now()->format('Y-m-d'),
            'payment_mode' => 'cash',
            'items' => [
                [
                    'product' => $this->tomato->name,
                    'grade' => $this->gradeBo->code,
                    'quantity' => 20,
                    'unit' => 'kg',
                    'unit_price' => 20,
                    'total_price' => 400
                ]
            ]
        ]);
        $responseOverdrawn2->assertStatus(422);
        $responseOverdrawn2->assertJsonValidationErrors(['items.0.quantity']);

        // 7. Log valid sale for remaining 15 kg (succeeds)
        $responseValid2 = $this->postJson('/fleet/sale/store', [
            'trip_id' => $tripId,
            'customer_name' => 'Jane Smith',
            'bill_no' => 'BILL2505',
            'payment_status' => 'paid',
            'amount_paid' => 300,
            'payment_date' => now()->format('Y-m-d'),
            'payment_mode' => 'cash',
            'items' => [
                [
                    'product' => $this->tomato->name,
                    'grade' => $this->gradeBo->code,
                    'quantity' => 15,
                    'unit' => 'kg',
                    'unit_price' => 20,
                    'total_price' => 300
                ]
            ]
        ]);
        $responseValid2->assertStatus(200);
    }

    /**
     * Workflow 26: Fleet Sale Billing Entry Correction & Available Stock Restoration
     */
    public function test_workflow_26_fleet_sale_billing_correction_stock_restoration(): void
    {
        // 1. Purchase 100 kg of Tomato at Warehouse A
        $purchaseResponse = $this->postJson('/stock-in-entry', [
            'stock_type' => 'in',
            'reference_no' => 'PRCH-WF26',
            'movement_date' => now()->format('Y-m-d'),
            'in_type' => 'purchase',
            'items' => [
                [
                    'product_id' => $this->tomato->id,
                    'product_name' => $this->tomato->name,
                    'grade' => $this->gradeBo->code,
                    'location_id' => $this->theniWarehouse->id,
                    'quantity' => 100.00,
                    'unit' => 'kg',
                    'unit_cost' => 10.00,
                    'total' => 1000.00,
                    'remarks' => 'Purchase WF26',
                    'invoice_number' => 'INV-WF26',
                    'vendor' => 'Vendor ABC',
                    'purchase_date' => now()->format('Y-m-d'),
                ]
            ]
        ]);
        $purchaseResponse->assertStatus(201);
        $batchCode = StockPurchase::orderBy('id', 'desc')->first()->batch_code;

        // Create route and vehicle
        $vehicle = \Modules\FleetManagement\Models\FleetVehicle::create([
            'registration_number' => 'KL-06-A-2026',
            'model' => 'Eicher Pro',
            'type' => 'Truck',
            'capacity' => 5000
        ]);
        $route = \Modules\FleetManagement\Models\FleetRoutes::create([
            'name' => 'Route WF26',
            'description' => 'Test Route WF26'
        ]);

        // 2. Dispatch 40 kg Tomato to Fleet Trip
        $payloadTrip = [
            'route_id'   => $route->id,
            'vehicle_id' => $vehicle->id,
            'start_date' => now()->format('Y-m-d'),
            'tag'        => 'Trip WF26',
            'sent' => [
                [
                    'product_id'  => $this->tomato->id,
                    'batch'       => $batchCode,
                    'grade'       => $this->gradeBo->code,
                    'unit'        => 'kg',
                    'quantity'    => 40,
                    'location_id' => $this->theniWarehouse->id
                ]
            ]
        ];
        $this->postJson('/create-trip', $payloadTrip)->assertStatus(200);
        $tripId = \Modules\FleetManagement\Models\FleetTrip::orderBy('id', 'desc')->first()->id;

        // 3. Record Fleet Sale 1: 30 kg (Leaving 10 kg available)
        $saleResponse = $this->postJson('/fleet/sale/store', [
            'trip_id' => $tripId,
            'customer_name' => 'Alice',
            'bill_no' => 'BILL2601',
            'payment_status' => 'paid',
            'amount_paid' => 600,
            'payment_date' => now()->format('Y-m-d'),
            'payment_mode' => 'cash',
            'items' => [
                [
                    'product' => $this->tomato->name,
                    'grade' => $this->gradeBo->code,
                    'quantity' => 30,
                    'unit' => 'kg',
                    'unit_price' => 20,
                    'total_price' => 600
                ]
            ]
        ]);
        $saleResponse->assertStatus(200);

        // 4. Attempt Sale 2: 15 kg (Fails because only 10 kg remains available)
        $saleResponseOver = $this->postJson('/fleet/sale/store', [
            'trip_id' => $tripId,
            'customer_name' => 'Bob',
            'bill_no' => 'BILL2602',
            'payment_status' => 'paid',
            'amount_paid' => 300,
            'payment_date' => now()->format('Y-m-d'),
            'payment_mode' => 'cash',
            'items' => [
                [
                    'product' => $this->tomato->name,
                    'grade' => $this->gradeBo->code,
                    'quantity' => 15,
                    'unit' => 'kg',
                    'unit_price' => 20,
                    'total_price' => 300
                ]
            ]
        ]);
        $saleResponseOver->assertStatus(422);

        // 5. Simulate Billing Entry Correction: Correct Sale 1 quantity down from 30 kg to 20 kg
        $saleItem = \Modules\FleetManagement\Models\FleetSaleItem::whereHas('sale', function ($q) use ($tripId) {
            $q->where('fleet_trip_id', $tripId);
        })->first();
        $saleItem->update(['quantity' => 20]);

        // 6. Re-attempt Sale 2: 15 kg (Now succeeds because remaining stock is 40 - 20 = 20 kg >= 15 kg)
        $saleResponseSucceed = $this->postJson('/fleet/sale/store', [
            'trip_id' => $tripId,
            'customer_name' => 'Bob',
            'bill_no' => 'BILL2602',
            'payment_status' => 'paid',
            'amount_paid' => 300,
            'payment_date' => now()->format('Y-m-d'),
            'payment_mode' => 'cash',
            'items' => [
                [
                    'product' => $this->tomato->name,
                    'grade' => $this->gradeBo->code,
                    'quantity' => 15,
                    'unit' => 'kg',
                    'unit_price' => 20,
                    'total_price' => 300
                ]
            ]
        ]);
        $saleResponseSucceed->assertStatus(200);
    }

    /**
     * WORKFLOW 27:
     * Fleet Sale Billing Adjustment to Zero via Billing Module Restores Trip Available Stock
     * 1. Dispatch 50 kg Tomato to a Fleet Trip.
     * 2. Perform a Fleet Sale of 30 kg (Leaving 20 kg available on trip).
     * 3. Submit a Billing Adjustment for the Fleet Sale with new_amount = 0.00.
     * 4. Verify that the FleetSaleItem quantity is zeroed out (quantity = 0.00).
     * 5. Verify via trip details API (/fleet-trips/{trip}/details) that qty_sold is 0 and qty_available is restored to 50 kg.
     * 6. Perform a subsequent Fleet Sale of 40 kg, verifying it succeeds now that stock has been restored.
     */
    public function test_workflow_27_fleet_sale_billing_adjustment_to_zero_restores_trip_stock(): void
    {
        $route = \Modules\FleetManagement\Models\FleetRoutes::create(['name' => 'Route WF27', 'description' => 'Desc WF27']);
        $vehicle = \Modules\FleetManagement\Models\FleetVehicle::create(['registration_number' => 'KL-06-WF-27']);

        // Insert stock purchase dependencies for batch foreign key constraints
        $masterId = \DB::table('master_stock_in')->insertGetId([
            'reference_number' => 'REF-S27-' . uniqid(),
            'stock_movement_type' => 'in',
            'stock_in_type' => 'purchase',
            'stock_in_date' => now()->format('Y-m-d'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $purchaseId = \DB::table('stock_purchase')->insertGetId([
            'master_stock_in_id' => $masterId,
            'vendor' => 'Test Vendor',
            'invoice_number' => 'INV-S27-' . uniqid(),
            'purchase_date' => now()->format('Y-m-d'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        \DB::table('stock_purchase_items')->insert([
            'stock_in_purchase_id' => $purchaseId,
            'location_id' => $this->theniWarehouse->id,
            'product' => $this->tomato->id,
            'batch' => 'BATCH-SCENARIO-27',
            'grade' => $this->gradeBo->code,
            'quantity' => 100.00,
            'unit' => 'kg',
            'unit_cost' => 10.00,
            'total' => 1000.00,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        \DB::table('warehouse_inventory')->insert([
            'warehouse_id' => $this->theniWarehouse->id,
            'product_id'   => $this->tomato->id,
            'batch'        => 'BATCH-SCENARIO-27',
            'grade'        => $this->gradeBo->code,
            'qty'          => 100.00,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        $ledgerService = app(\Modules\StockLedger\Services\StockLedgerService::class);
        $ledgerService->recordEntry([
            'transaction_type' => 'PURCHASE',
            'location_id'      => $this->theniWarehouse->id,
            'product_id'       => $this->tomato->id,
            'batch_code'       => 'BATCH-SCENARIO-27',
            'grade'            => $this->gradeBo->code,
            'quantity'         => 100.00,
            'unit'             => 'kg',
            'unit_cost'        => 10.00,
            'remarks'          => 'Scenario 27 Stock'
        ]);

        // 1. Dispatch 50 kg Tomato to a Fleet Trip via /create-trip
        $response = $this->postJson('/create-trip', [
            'route_id'   => $route->id,
            'vehicle_id' => $vehicle->id,
            'start_date' => now()->format('Y-m-d'),
            'tag'        => 'TRIP-SCENARIO-27',
            'sent'       => [
                [
                    'product_id'  => $this->tomato->id,
                    'location_id' => $this->theniWarehouse->id,
                    'batch'       => 'BATCH-SCENARIO-27',
                    'grade'       => $this->gradeBo->code,
                    'unit'        => 'kg',
                    'quantity'    => 50,
                ]
            ]
        ]);
        $response->assertStatus(200);
        $tripId = \Modules\FleetManagement\Models\FleetTrip::latest('id')->first()->id;

        // 2. Perform a Fleet Sale of 30 kg
        $saleResponse = $this->postJson('/fleet/sale/store', [
            'trip_id' => $tripId,
            'customer_name' => 'Customer Scenario 27',
            'bill_no' => 'B2701',
            'payment_status' => 'paid',
            'amount_paid' => 600,
            'payment_date' => now()->format('Y-m-d'),
            'payment_mode' => 'cash',
            'items' => [
                [
                    'product' => $this->tomato->name,
                    'grade' => $this->gradeBo->code,
                    'quantity' => 30,
                    'unit' => 'kg',
                    'unit_price' => 20,
                    'total_price' => 600
                ]
            ]
        ]);
        $saleResponse->assertStatus(200);

        $sale = \Modules\FleetManagement\Models\FleetSale::where('fleet_trip_id', $tripId)->first();
        $this->assertNotNull($sale);

        // Verify intermediate stock: 30 kg sold, 20 kg available
        $details = $this->getJson("/fleet-trips/{$tripId}/details")->json('data');
        $this->assertEquals(30.00, $details['products_sent'][0]['qty_sold']);
        $this->assertEquals(20.00, $details['products_sent'][0]['qty_available']);

        // 3. Submit a Billing Adjustment via Billing Module (new_amount = 0.00)
        $adjResponse = $this->postJson('/billing-adjustments', [
            'sale_type' => 'fleet',
            'sale_id' => $sale->id,
            'new_amount' => 0.00,
            'reason' => 'voided_sale',
            'remarks' => 'Zeroing out billing entry',
        ]);
        $adjResponse->assertStatus(201);

        // 4. Verify FleetSaleItem quantity is zeroed out
        $saleItem = \Modules\FleetManagement\Models\FleetSaleItem::where('fleet_sale_id', $sale->id)->first();
        $this->assertEquals(0.00, (float)$saleItem->quantity);

        // 5. Verify trip details API reflects stock restoration: qty_sold = 0, qty_available = 50 kg
        $detailsAfter = $this->getJson("/fleet-trips/{$tripId}/details")->json('data');
        $this->assertEquals(0.00, $detailsAfter['products_sent'][0]['qty_sold']);
        $this->assertEquals(50.00, $detailsAfter['products_sent'][0]['qty_available']);

        // 6. Perform a new sale of 40 kg (Succeeds because 50 kg is now available)
        $newSaleResponse = $this->postJson('/fleet/sale/store', [
            'trip_id' => $tripId,
            'customer_name' => 'Customer Scenario 27 Next',
            'bill_no' => 'B2702',
            'payment_status' => 'paid',
            'amount_paid' => 800,
            'payment_date' => now()->format('Y-m-d'),
            'payment_mode' => 'cash',
            'items' => [
                [
                    'product' => $this->tomato->name,
                    'grade' => $this->gradeBo->code,
                    'quantity' => 40,
                    'unit' => 'kg',
                    'unit_price' => 20,
                    'total_price' => 800
                ]
            ]
        ]);
        $newSaleResponse->assertStatus(200);
    }

    /**
     * WORKFLOW 28:
     * Cancelling a Fleet Trip Restores Dispatched Stock to Source Location, Zeroes Associated Sales,
     * and Ensures Zero Outstanding Credit on the Dashboard.
     */
    public function test_workflow_28_cancelling_fleet_trip_restores_stock_and_zeroes_financials(): void
    {
        $route = \Modules\FleetManagement\Models\FleetRoutes::create(['name' => 'Route WF28', 'description' => 'Desc WF28']);
        $vehicle = \Modules\FleetManagement\Models\FleetVehicle::create(['registration_number' => 'KL-06-WF-28']);

        // Insert stock purchase dependencies for batch foreign key constraints
        $masterId = \DB::table('master_stock_in')->insertGetId([
            'reference_number' => 'REF-S28-' . uniqid(),
            'stock_movement_type' => 'in',
            'stock_in_type' => 'purchase',
            'stock_in_date' => now()->format('Y-m-d'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $purchaseId = \DB::table('stock_purchase')->insertGetId([
            'master_stock_in_id' => $masterId,
            'vendor' => 'Test Vendor',
            'invoice_number' => 'INV-S28-' . uniqid(),
            'purchase_date' => now()->format('Y-m-d'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        \DB::table('stock_purchase_items')->insert([
            'stock_in_purchase_id' => $purchaseId,
            'location_id' => $this->theniWarehouse->id,
            'product' => $this->tomato->id,
            'batch' => 'BATCH-SCENARIO-28',
            'grade' => $this->gradeBo->code,
            'quantity' => 100.00,
            'unit' => 'kg',
            'unit_cost' => 10.00,
            'total' => 1000.00,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        \DB::table('warehouse_inventory')->insert([
            'warehouse_id' => $this->theniWarehouse->id,
            'product_id'   => $this->tomato->id,
            'batch'        => 'BATCH-SCENARIO-28',
            'grade'        => $this->gradeBo->code,
            'qty'          => 100.00,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        $ledgerService = app(\Modules\StockLedger\Services\StockLedgerService::class);
        $ledgerService->recordEntry([
            'transaction_type' => 'PURCHASE',
            'location_id'      => $this->theniWarehouse->id,
            'product_id'       => $this->tomato->id,
            'batch_code'       => 'BATCH-SCENARIO-28',
            'grade'            => $this->gradeBo->code,
            'quantity'         => 100.00,
            'unit'             => 'kg',
            'unit_cost'        => 10.00,
            'remarks'          => 'Scenario 28 Initial Stock'
        ]);

        // 1. Dispatch 60 kg stock to Fleet Trip via /create-trip
        $dispatchResp = $this->postJson('/create-trip', [
            'route_id'   => $route->id,
            'vehicle_id' => $vehicle->id,
            'start_date' => now()->format('Y-m-d'),
            'tag'        => 'TRIP-SCENARIO-28',
            'sent'       => [
                [
                    'product_id'  => $this->tomato->id,
                    'location_id' => $this->theniWarehouse->id,
                    'batch'       => 'BATCH-SCENARIO-28',
                    'grade'       => $this->gradeBo->code,
                    'unit'        => 'kg',
                    'quantity'    => 60,
                ]
            ]
        ]);
        $dispatchResp->assertStatus(200);
        $tripId = \Modules\FleetManagement\Models\FleetTrip::latest('id')->first()->id;

        // Stock in warehouse should drop from 100 to 40 kg
        $this->assertEquals(40.00, $ledgerService->getAvailableStock($this->theniWarehouse->id, $this->tomato->id, 'BATCH-SCENARIO-28', $this->gradeBo->code));

        // 2. Perform a Fleet Sale of 40 kg with partial payment
        $saleResp = $this->postJson('/fleet/sale/store', [
            'trip_id'       => $tripId,
            'customer_name' => 'Customer Scenario 28',
            'bill_no'       => 'B2801',
            'payment_status' => 'partial',
            'amount_paid'   => 400,
            'payment_date'  => now()->format('Y-m-d'),
            'payment_mode'  => 'cash',
            'items'         => [
                [
                    'product'     => $this->tomato->name,
                    'grade'       => $this->gradeBo->code,
                    'quantity'    => 40,
                    'unit'        => 'kg',
                    'unit_price'  => 20,
                    'total_price' => 800
                ]
            ]
        ]);
        $saleResp->assertStatus(200);

        // 3. Cancel Fleet Trip via DELETE /fleet-trips/{tripId}
        $cancelResp = $this->deleteJson("/fleet-trips/{$tripId}");
        $cancelResp->assertStatus(200)->assertJson(['success' => true]);

        // 4. Verify trip status is 'cancelled'
        $trip = \Modules\FleetManagement\Models\FleetTrip::find($tripId);
        $this->assertEquals('cancelled', $trip->status);

        // 5. Verify source warehouse stock is restored back to 100 kg
        $this->assertEquals(100.00, $ledgerService->getAvailableStock($this->theniWarehouse->id, $this->tomato->id, 'BATCH-SCENARIO-28', $this->gradeBo->code));

        // 6. Verify FleetSale and FleetSaleItem are zeroed out
        $sale = \Modules\FleetManagement\Models\FleetSale::where('fleet_trip_id', $tripId)->first();
        $this->assertEquals(0.00, (float)$sale->total_amount);
        $saleItem = \Modules\FleetManagement\Models\FleetSaleItem::where('fleet_sale_id', $sale->id)->first();
        $this->assertEquals(0.00, (float)$saleItem->quantity);

        // 7. Verify allTrips repository reporting returns 0 metrics for cancelled trip
        $repo = app(\Modules\FleetManagement\Repository\FleetTripRepository::class);
        $tripsList = $repo->allTrips();
        $cancelledTripRecord = collect($tripsList->items())->firstWhere('id', $tripId);

        $this->assertNotNull($cancelledTripRecord);
        $this->assertEquals(0.00, (float)$cancelledTripRecord->total_sent);
        $this->assertEquals(0.00, (float)$cancelledTripRecord->total_billed);
        $this->assertEquals(0.00, (float)$cancelledTripRecord->remaining_stock);
        $this->assertEquals(0.00, (float)$cancelledTripRecord->outstanding_credit);
    }
}
