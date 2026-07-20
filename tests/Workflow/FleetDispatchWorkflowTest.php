<?php

namespace Tests\Workflow;

use Modules\StockManagement\Models\StockIn\StockPurchase;

class FleetDispatchWorkflowTest extends BaseWorkflowTestCase
{
    /**
     * Workflow 16: Fleet Trip Direct Dispatch
     * Create a stock purchase -> create fleet trip, checking quantity reduced at source
     */
    public function test_workflow_16_fleet_trip_direct_dispatch(): void
    {
        // 1. Purchase: 100 kg of Tomato at Theni Warehouse
        $purchaseResponse = $this->postJson('/stock-in-entry', [
            'stock_type' => 'in',
            'reference_no' => 'PRCH-WF16',
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
                    'remarks' => 'Purchase WF16',
                    'invoice_number' => 'INV-WF16',
                    'vendor' => 'Vendor ABC',
                    'purchase_date' => now()->format('Y-m-d'),
                ]
            ]
        ]);
        $purchaseResponse->assertStatus(201);
        $batchCode = StockPurchase::orderBy('id', 'desc')->first()->batch_code;

        // Create route and vehicle
        $vehicle = \Modules\FleetManagement\Models\FleetVehicle::create([
            'registration_number' => 'KL-06-A-1616',
            'model' => 'Eicher Pro',
            'type' => 'Truck',
            'capacity' => 5000
        ]);
        $route = \Modules\FleetManagement\Models\FleetRoutes::create([
            'name' => 'Route WF16',
            'description' => 'Test Route WF16'
        ]);

        // 2. Create fleet trip from Theni Warehouse (dispatched quantity 40)
        $payloadTrip = [
            'route_id'   => $route->id,
            'vehicle_id' => $vehicle->id,
            'start_date' => now()->format('Y-m-d'),
            'tag'        => 'Trip WF16',
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

        // 3. Assert stock is reduced at source Theni Warehouse (leaving 60.00)
        $ledgerService = app(\Modules\StockLedger\Services\StockLedgerService::class);
        $this->assertEquals(60.00, $ledgerService->getAvailableStock($this->theniWarehouse->id, $this->tomato->id, $batchCode, $this->gradeBo->code));

        // 4. Assert stock ledger entry exists for DISPATCH with quantity -40.00
        $this->assertDatabaseHas('stock_ledger_entries', [
            'location_id'      => $this->theniWarehouse->id,
            'product_id'       => $this->tomato->id,
            'batch_code'       => $batchCode,
            'transaction_type' => 'DISPATCH',
            'quantity'         => -40.00
        ]);
    }

    /**
     * Workflow 17: Fleet Trip Transferred Dispatch
     * Create purchase -> move it to another warehouse -> assign it to fleet from the new warehouse
     */
    public function test_workflow_17_fleet_trip_transferred_dispatch(): void
    {
        // 1. Purchase: 100 kg of Tomato at Theni Warehouse
        $purchaseResponse = $this->postJson('/stock-in-entry', [
            'stock_type' => 'in',
            'reference_no' => 'PRCH-WF17',
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
                    'remarks' => 'Purchase WF17',
                    'invoice_number' => 'INV-WF17',
                    'vendor' => 'Vendor ABC',
                    'purchase_date' => now()->format('Y-m-d'),
                ]
            ]
        ]);
        $purchaseResponse->assertStatus(201);
        $batchCode = StockPurchase::orderBy('id', 'desc')->first()->batch_code;

        // 2. Transfer: Move 60 kg from Theni Warehouse to Madurai Warehouse
        $transferResponse = $this->postJson('/stock-transfer', [
            't_transferDate' => now()->format('Y-m-d'),
            't_transferType' => 'inter',
            't_fromLocation' => (string)$this->theniWarehouse->id,
            't_toLocation'   => (string)$this->maduraiWarehouse->id,
            't_product_name' => (string)$this->tomato->id,
            't_batch_code'   => $batchCode,
            't_grade'        => $this->gradeBo->code,
            't_quantity'     => 60,
            't_unit'         => 'kg'
        ]);
        $transferResponse->assertStatus(200);

        // Assert balances after transfer
        $ledgerService = app(\Modules\StockLedger\Services\StockLedgerService::class);
        $this->assertEquals(40.00, $ledgerService->getAvailableStock($this->theniWarehouse->id, $this->tomato->id, $batchCode, $this->gradeBo->code));
        $this->assertEquals(60.00, $ledgerService->getAvailableStock($this->maduraiWarehouse->id, $this->tomato->id, $batchCode, $this->gradeBo->code));

        // Create route and vehicle
        $vehicle = \Modules\FleetManagement\Models\FleetVehicle::create([
            'registration_number' => 'KL-06-A-1717',
            'model' => 'Eicher Pro',
            'type' => 'Truck',
            'capacity' => 5000
        ]);
        $route = \Modules\FleetManagement\Models\FleetRoutes::create([
            'name' => 'Route WF17',
            'description' => 'Test Route WF17'
        ]);

        // 3. Create fleet trip from Madurai Warehouse (dispatched quantity 25)
        $payloadTrip = [
            'route_id'   => $route->id,
            'vehicle_id' => $vehicle->id,
            'start_date' => now()->format('Y-m-d'),
            'tag'        => 'Trip WF17',
            'sent' => [
                [
                    'product_id'  => $this->tomato->id,
                    'batch'       => $batchCode,
                    'grade'       => $this->gradeBo->code,
                    'unit'        => 'kg',
                    'quantity'    => 25,
                    'location_id' => $this->maduraiWarehouse->id
                ]
            ]
        ];
        $this->postJson('/create-trip', $payloadTrip)->assertStatus(200);

        // 4. Assert stock is reduced at Madurai Warehouse (leaving 35.00)
        $this->assertEquals(35.00, $ledgerService->getAvailableStock($this->maduraiWarehouse->id, $this->tomato->id, $batchCode, $this->gradeBo->code));

        // 5. Assert stock ledger entry exists for DISPATCH at Madurai Warehouse with quantity -25.00
        $this->assertDatabaseHas('stock_ledger_entries', [
            'location_id'      => $this->maduraiWarehouse->id,
            'product_id'       => $this->tomato->id,
            'batch_code'       => $batchCode,
            'transaction_type' => 'DISPATCH',
            'quantity'         => -25.00
        ]);
    }

    /**
     * Workflow 18: Fleet Trip Reverse Transferred Dispatch
     * Make purchase -> transfer -> reverse transfer -> fleet trip from both source and destination
     */
    public function test_workflow_18_fleet_trip_reverse_transferred_dispatch(): void
    {
        // 1. Purchase: 100 kg of Tomato at Theni Warehouse
        $purchaseResponse = $this->postJson('/stock-in-entry', [
            'stock_type' => 'in',
            'reference_no' => 'PRCH-WF18',
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
                    'remarks' => 'Purchase WF18',
                    'invoice_number' => 'INV-WF18',
                    'vendor' => 'Vendor ABC',
                    'purchase_date' => now()->format('Y-m-d'),
                ]
            ]
        ]);
        $purchaseResponse->assertStatus(201);
        $batchCode = StockPurchase::orderBy('id', 'desc')->first()->batch_code;

        // 2. Transfer: Move 60 kg from Theni Warehouse to Madurai Warehouse
        $transferResponse = $this->postJson('/stock-transfer', [
            't_transferDate' => now()->format('Y-m-d'),
            't_transferType' => 'inter',
            't_fromLocation' => (string)$this->theniWarehouse->id,
            't_toLocation'   => (string)$this->maduraiWarehouse->id,
            't_product_name' => (string)$this->tomato->id,
            't_batch_code'   => $batchCode,
            't_grade'        => $this->gradeBo->code,
            't_quantity'     => 60,
            't_unit'         => 'kg'
        ]);
        $transferResponse->assertStatus(200);

        // 3. Reverse Transfer: Move 60 kg from Madurai Warehouse back to Theni Warehouse
        $reverseTransferResponse = $this->postJson('/stock-transfer', [
            't_transferDate' => now()->format('Y-m-d'),
            't_transferType' => 'inter',
            't_fromLocation' => (string)$this->maduraiWarehouse->id,
            't_toLocation'   => (string)$this->theniWarehouse->id,
            't_product_name' => (string)$this->tomato->id,
            't_batch_code'   => $batchCode,
            't_grade'        => $this->gradeBo->code,
            't_quantity'     => 60,
            't_unit'         => 'kg'
        ]);
        $reverseTransferResponse->assertStatus(200);

        // Assert balances after reverse transfer
        $ledgerService = app(\Modules\StockLedger\Services\StockLedgerService::class);
        $this->assertEquals(100.00, $ledgerService->getAvailableStock($this->theniWarehouse->id, $this->tomato->id, $batchCode, $this->gradeBo->code));
        $this->assertEquals(0.00, $ledgerService->getAvailableStock($this->maduraiWarehouse->id, $this->tomato->id, $batchCode, $this->gradeBo->code));

        // Create route and vehicle
        $vehicle = \Modules\FleetManagement\Models\FleetVehicle::create([
            'registration_number' => 'KL-06-A-1818',
            'model' => 'Eicher Pro',
            'type' => 'Truck',
            'capacity' => 5000
        ]);
        $route = \Modules\FleetManagement\Models\FleetRoutes::create([
            'name' => 'Route WF18',
            'description' => 'Test Route WF18'
        ]);

        // 4. Create fleet trip from Madurai Warehouse (destination/reversed source) -> should FAIL validation (available stock is 0)
        $payloadTripB = [
            'route_id'   => $route->id,
            'vehicle_id' => $vehicle->id,
            'start_date' => now()->format('Y-m-d'),
            'tag'        => 'Trip WF18 B',
            'sent' => [
                [
                    'product_id'  => $this->tomato->id,
                    'batch'       => $batchCode,
                    'grade'       => $this->gradeBo->code,
                    'unit'        => 'kg',
                    'quantity'    => 10,
                    'location_id' => $this->maduraiWarehouse->id
                ]
            ]
        ];
        $responseB = $this->postJson('/create-trip', $payloadTripB);
        $responseB->assertStatus(422);

        // 5. Create fleet trip from Theni Warehouse (source/restored location) for 30 units -> should SUCCESS
        $payloadTripA = [
            'route_id'   => $route->id,
            'vehicle_id' => $vehicle->id,
            'start_date' => now()->format('Y-m-d'),
            'tag'        => 'Trip WF18 A',
            'sent' => [
                [
                    'product_id'  => $this->tomato->id,
                    'batch'       => $batchCode,
                    'grade'       => $this->gradeBo->code,
                    'unit'        => 'kg',
                    'quantity'    => 30,
                    'location_id' => $this->theniWarehouse->id
                ]
            ]
        ];
        $this->postJson('/create-trip', $payloadTripA)->assertStatus(200);

        // Assert stock is reduced at Theni Warehouse (leaving 70)
        $this->assertEquals(70.00, $ledgerService->getAvailableStock($this->theniWarehouse->id, $this->tomato->id, $batchCode, $this->gradeBo->code));
    }

    /**
     * Workflow 19: Fleet Trip Shop Dispatch
     * Purchase -> transfer to shop -> fleet trip from shop
     */
    public function test_workflow_19_fleet_trip_shop_dispatch(): void
    {
        // 1. Purchase: 100 kg of Tomato at Theni Warehouse
        $purchaseResponse = $this->postJson('/stock-in-entry', [
            'stock_type' => 'in',
            'reference_no' => 'PRCH-WF19',
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
                    'remarks' => 'Purchase WF19',
                    'invoice_number' => 'INV-WF19',
                    'vendor' => 'Vendor ABC',
                    'purchase_date' => now()->format('Y-m-d'),
                ]
            ]
        ]);
        $purchaseResponse->assertStatus(201);
        $batchCode = StockPurchase::orderBy('id', 'desc')->first()->batch_code;

        // 2. Transfer: Move 50 kg from Theni Warehouse to Theni Shop
        $transferResponse = $this->postJson('/stock-transfer', [
            't_transferDate' => now()->format('Y-m-d'),
            't_transferType' => 'inter',
            't_fromLocation' => (string)$this->theniWarehouse->id,
            't_toLocation'   => (string)$this->theniShop->id,
            't_product_name' => (string)$this->tomato->id,
            't_batch_code'   => $batchCode,
            't_grade'        => $this->gradeBo->code,
            't_quantity'     => 50,
            't_unit'         => 'kg'
        ]);
        $transferResponse->assertStatus(200);

        // Create route and vehicle
        $vehicle = \Modules\FleetManagement\Models\FleetVehicle::create([
            'registration_number' => 'KL-06-A-1919',
            'model' => 'Eicher Pro',
            'type' => 'Truck',
            'capacity' => 5000
        ]);
        $route = \Modules\FleetManagement\Models\FleetRoutes::create([
            'name' => 'Route WF19',
            'description' => 'Test Route WF19'
        ]);

        // 3. Create fleet trip from Theni Shop (dispatched quantity 20)
        $payloadTrip = [
            'route_id'   => $route->id,
            'vehicle_id' => $vehicle->id,
            'start_date' => now()->format('Y-m-d'),
            'tag'        => 'Trip WF19',
            'sent' => [
                [
                    'product_id'  => $this->tomato->id,
                    'batch'       => $batchCode,
                    'grade'       => $this->gradeBo->code,
                    'unit'        => 'kg',
                    'quantity'    => 20,
                    'location_id' => $this->theniShop->id
                ]
            ]
        ];
        $this->postJson('/create-trip', $payloadTrip)->assertStatus(200);

        // 4. Assert stock is reduced at Theni Shop (leaving 30.00)
        $ledgerService = app(\Modules\StockLedger\Services\StockLedgerService::class);
        $this->assertEquals(30.00, $ledgerService->getAvailableStock($this->theniShop->id, $this->tomato->id, $batchCode, $this->gradeBo->code));

        // 5. Assert stock ledger entry exists for DISPATCH at Theni Shop with quantity -20.00
        $this->assertDatabaseHas('stock_ledger_entries', [
            'location_id'      => $this->theniShop->id,
            'product_id'       => $this->tomato->id,
            'batch_code'       => $batchCode,
            'transaction_type' => 'DISPATCH',
            'quantity'         => -20.00
        ]);
    }

    /**
     * Workflow 20: Warehouse-to-Warehouse Transfer -> Stock Adjustment -> Fleet Trip
     */
    public function test_workflow_20_warehouse_transfer_adjustment_fleet_trip(): void
    {
        $ledgerService = app(\Modules\StockLedger\Services\StockLedgerService::class);
        $adjustmentService = app(\Modules\StockAdjustment\Services\StockAdjustmentService::class);

        // 1. Purchase: 100 kg of Tomato at Theni Warehouse
        $purchaseResponse = $this->postJson('/stock-in-entry', [
            'stock_type' => 'in',
            'reference_no' => 'PRCH-WF20',
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
                    'remarks' => 'Purchase WF20',
                    'invoice_number' => 'INV-WF20',
                    'vendor' => 'Vendor ABC',
                    'purchase_date' => now()->format('Y-m-d'),
                ]
            ]
        ]);
        $purchaseResponse->assertStatus(201);
        $batchCode = StockPurchase::orderBy('id', 'desc')->first()->batch_code;

        // 2. Transfer: 50 kg from Theni Warehouse to Madurai Warehouse
        $this->postJson('/stock-transfer', [
            't_transferDate' => now()->format('Y-m-d'),
            't_transferType' => 'inter',
            't_fromLocation' => (string)$this->theniWarehouse->id,
            't_toLocation'   => (string)$this->maduraiWarehouse->id,
            't_product_name' => (string)$this->tomato->id,
            't_batch_code'   => $batchCode,
            't_grade'        => $this->gradeBo->code,
            't_quantity'     => 50,
            't_unit'         => 'kg'
        ])->assertStatus(200);

        $this->assertEquals(50.00, $ledgerService->getAvailableStock($this->maduraiWarehouse->id, $this->tomato->id, $batchCode, $this->gradeBo->code));

        // 3. Adjust: Perform Stock Adjustment at Madurai Warehouse reducing stock by 2 kg (within 5% of 50 = 2.5 kg, so immediately approved)
        $adjustment = $adjustmentService->createAdjustment([
            'location_id' => $this->maduraiWarehouse->id,
            'product_id' => $this->tomato->id,
            'batch_code' => $batchCode,
            'grade' => $this->gradeBo->code,
            'adjusted_qty' => -2.00,
            'new_qty' => 48.00,
            'reason' => 'audit_difference',
            'remarks' => 'Slight adjustment',
        ]);
        $this->assertEquals('approved', $adjustment->status);
        $this->assertEquals(48.00, $ledgerService->getAvailableStock($this->maduraiWarehouse->id, $this->tomato->id, $batchCode, $this->gradeBo->code));

        // Create route and vehicle
        $vehicle = \Modules\FleetManagement\Models\FleetVehicle::create([
            'registration_number' => 'KL-06-A-2020',
            'model' => 'Eicher Pro',
            'type' => 'Truck',
            'capacity' => 5000
        ]);
        $route = \Modules\FleetManagement\Models\FleetRoutes::create([
            'name' => 'Route WF20',
            'description' => 'Test Route WF20'
        ]);

        // 4. Create Fleet Trip dispatching 50 kg from Madurai Warehouse (should fail because available is 48 kg)
        $payloadFail = [
            'route_id'   => $route->id,
            'vehicle_id' => $vehicle->id,
            'start_date' => now()->format('Y-m-d'),
            'tag'        => 'Trip WF20 Fail',
            'sent' => [
                [
                    'product_id'  => $this->tomato->id,
                    'batch'       => $batchCode,
                    'grade'       => $this->gradeBo->code,
                    'unit'        => 'kg',
                    'quantity'    => 50,
                    'location_id' => $this->maduraiWarehouse->id
                ]
            ]
        ];
        $this->postJson('/create-trip', $payloadFail)->assertStatus(422);

        // 5. Create Fleet Trip dispatching 40 kg from Madurai Warehouse (should succeed)
        $payloadSuccess = [
            'route_id'   => $route->id,
            'vehicle_id' => $vehicle->id,
            'start_date' => now()->format('Y-m-d'),
            'tag'        => 'Trip WF20 Success',
            'sent' => [
                [
                    'product_id'  => $this->tomato->id,
                    'batch'       => $batchCode,
                    'grade'       => $this->gradeBo->code,
                    'unit'        => 'kg',
                    'quantity'    => 40,
                    'location_id' => $this->maduraiWarehouse->id
                ]
            ]
        ];
        $this->postJson('/create-trip', $payloadSuccess)->assertStatus(200);

        // 6. Assert stock reduced to 8.00 at Madurai Warehouse (48 - 40)
        $this->assertEquals(8.00, $ledgerService->getAvailableStock($this->maduraiWarehouse->id, $this->tomato->id, $batchCode, $this->gradeBo->code));
    }

    /**
     * Workflow 21: Single Warehouse Stock Adjustment -> Fleet Trip
     */
    public function test_workflow_21_single_warehouse_adjustment_fleet_trip(): void
    {
        $ledgerService = app(\Modules\StockLedger\Services\StockLedgerService::class);
        $adjustmentService = app(\Modules\StockAdjustment\Services\StockAdjustmentService::class);

        // 1. Purchase: 100 kg of Tomato at Theni Warehouse
        $purchaseResponse = $this->postJson('/stock-in-entry', [
            'stock_type' => 'in',
            'reference_no' => 'PRCH-WF21',
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
                    'remarks' => 'Purchase WF21',
                    'invoice_number' => 'INV-WF21',
                    'vendor' => 'Vendor ABC',
                    'purchase_date' => now()->format('Y-m-d'),
                ]
            ]
        ]);
        $purchaseResponse->assertStatus(201);
        $batchCode = StockPurchase::orderBy('id', 'desc')->first()->batch_code;

        // 2. Adjust: Perform Stock Adjustment at Theni Warehouse reducing stock by 4 kg (within 5% of 100 = 5 kg, so immediately approved)
        $adjustment = $adjustmentService->createAdjustment([
            'location_id' => $this->theniWarehouse->id,
            'product_id' => $this->tomato->id,
            'batch_code' => $batchCode,
            'grade' => $this->gradeBo->code,
            'adjusted_qty' => -4.00,
            'new_qty' => 96.00,
            'reason' => 'audit_difference',
            'remarks' => 'Adjustment WF21',
        ]);
        $this->assertEquals('approved', $adjustment->status);
        $this->assertEquals(96.00, $ledgerService->getAvailableStock($this->theniWarehouse->id, $this->tomato->id, $batchCode, $this->gradeBo->code));

        // Create route and vehicle
        $vehicle = \Modules\FleetManagement\Models\FleetVehicle::create([
            'registration_number' => 'KL-06-A-2021',
            'model' => 'Eicher Pro',
            'type' => 'Truck',
            'capacity' => 5000
        ]);
        $route = \Modules\FleetManagement\Models\FleetRoutes::create([
            'name' => 'Route WF21',
            'description' => 'Test Route WF21'
        ]);

        // 3. Create Fleet Trip dispatching 97 kg from Theni Warehouse (should fail because available is 96 kg)
        $payloadFail = [
            'route_id'   => $route->id,
            'vehicle_id' => $vehicle->id,
            'start_date' => now()->format('Y-m-d'),
            'tag'        => 'Trip WF21 Fail',
            'sent' => [
                [
                    'product_id'  => $this->tomato->id,
                    'batch'       => $batchCode,
                    'grade'       => $this->gradeBo->code,
                    'unit'        => 'kg',
                    'quantity'    => 97,
                    'location_id' => $this->theniWarehouse->id
                ]
            ]
        ];
        $this->postJson('/create-trip', $payloadFail)->assertStatus(422);

        // 4. Create Fleet Trip dispatching 50 kg from Theni Warehouse (should succeed)
        $payloadSuccess = [
            'route_id'   => $route->id,
            'vehicle_id' => $vehicle->id,
            'start_date' => now()->format('Y-m-d'),
            'tag'        => 'Trip WF21 Success',
            'sent' => [
                [
                    'product_id'  => $this->tomato->id,
                    'batch'       => $batchCode,
                    'grade'       => $this->gradeBo->code,
                    'unit'        => 'kg',
                    'quantity'    => 50,
                    'location_id' => $this->theniWarehouse->id
                ]
            ]
        ];
        $this->postJson('/create-trip', $payloadSuccess)->assertStatus(200);

        // 5. Assert stock reduced to 46.00 at Theni Warehouse (96 - 50)
        $this->assertEquals(46.00, $ledgerService->getAvailableStock($this->theniWarehouse->id, $this->tomato->id, $batchCode, $this->gradeBo->code));
    }

    /**
     * Workflow 22: Fleet Trip Quantity Adjustments & Cancellations (Reversals)
     */
    public function test_workflow_22_fleet_trip_adjustments_and_cancellations(): void
    {
        // 1. Purchase: 100 kg of Tomato at Theni Warehouse
        $purchaseResponse = $this->postJson('/stock-in-entry', [
            'stock_type' => 'in',
            'reference_no' => 'PRCH-WF22',
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
                    'remarks' => 'Purchase WF22',
                    'invoice_number' => 'INV-WF22',
                    'vendor' => 'Vendor ABC',
                    'purchase_date' => now()->format('Y-m-d'),
                ]
            ]
        ]);
        $purchaseResponse->assertStatus(201);
        $batchCode = StockPurchase::orderBy('id', 'desc')->first()->batch_code;

        // Create route and vehicle
        $vehicle = \Modules\FleetManagement\Models\FleetVehicle::create([
            'registration_number' => 'KL-06-A-2022',
            'model' => 'Eicher Pro',
            'type' => 'Truck',
            'capacity' => 5000
        ]);
        $route = \Modules\FleetManagement\Models\FleetRoutes::create([
            'name' => 'Route WF22',
            'description' => 'Test Route WF22'
        ]);

        // 2. Create fleet trip: send 40 kg
        $payloadTrip = [
            'route_id'   => $route->id,
            'vehicle_id' => $vehicle->id,
            'start_date' => now()->format('Y-m-d'),
            'tag'        => 'Trip WF22',
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

        $trip = \Modules\FleetManagement\Models\FleetTrip::orderBy('id', 'desc')->first();
        $this->assertNotNull($trip);

        $ledgerService = app(\Modules\StockLedger\Services\StockLedgerService::class);
        $this->assertEquals(60.00, $ledgerService->getAvailableStock($this->theniWarehouse->id, $this->tomato->id, $batchCode, $this->gradeBo->code));

        // Get the dispatch item id
        $dispatchItem = \Illuminate\Support\Facades\DB::table('fleet_trip_stocks')->where('fleet_trip_id', $trip->id)->first();
        $this->assertNotNull($dispatchItem);

        // 3. Adjust the trip details and change the dispatch item quantity to 15 kg
        $adjustPayload = [
            'route_id'   => $route->id,
            'vehicle_id' => $vehicle->id,
            'start_date' => now()->format('Y-m-d'),
            'tag'        => 'Trip WF22 Adjusted',
            'items' => [
                [
                    'id'       => $dispatchItem->id,
                    'quantity' => 15
                ]
            ]
        ];
        $this->postJson("/fleet-trips/{$trip->id}/adjust", $adjustPayload)->assertStatus(200);

        // Assert quantity updated in DB and stock restored to 85.00
        $this->assertDatabaseHas('fleet_trip_stocks', [
            'id' => $dispatchItem->id,
            'qty_sent' => 15.00
        ]);
        $this->assertEquals(85.00, $ledgerService->getAvailableStock($this->theniWarehouse->id, $this->tomato->id, $batchCode, $this->gradeBo->code));

        // 4. Adjust the dispatch item quantity to 0 kg (removal)
        $removePayload = [
            'route_id'   => $route->id,
            'vehicle_id' => $vehicle->id,
            'start_date' => now()->format('Y-m-d'),
            'tag'        => 'Trip WF22 Adjusted Empty',
            'items' => [
                [
                    'id'       => $dispatchItem->id,
                    'quantity' => 0
                ]
            ]
        ];
        $this->postJson("/fleet-trips/{$trip->id}/adjust", $removePayload)->assertStatus(200);

        // Assert dispatch item deleted and stock fully restored to 100.00
        $this->assertDatabaseMissing('fleet_trip_stocks', [
            'id' => $dispatchItem->id
        ]);
        $this->assertEquals(100.00, $ledgerService->getAvailableStock($this->theniWarehouse->id, $this->tomato->id, $batchCode, $this->gradeBo->code));

        // 5. Create a new dispatch on the same trip for 50 kg
        $payloadTrip2 = [
            'route_id'   => $route->id,
            'vehicle_id' => $vehicle->id,
            'start_date' => now()->format('Y-m-d'),
            'tag'        => 'Trip WF22 B',
            'sent' => [
                [
                    'product_id'  => $this->tomato->id,
                    'batch'       => $batchCode,
                    'grade'       => $this->gradeBo->code,
                    'unit'        => 'kg',
                    'quantity'    => 50,
                    'location_id' => $this->theniWarehouse->id
                ]
            ]
        ];
        $this->postJson('/create-trip', $payloadTrip2)->assertStatus(200);
        $trip2 = \Modules\FleetManagement\Models\FleetTrip::orderBy('id', 'desc')->first();

        // Stock at warehouse should be 50.00
        $this->assertEquals(50.00, $ledgerService->getAvailableStock($this->theniWarehouse->id, $this->tomato->id, $batchCode, $this->gradeBo->code));

        // 6. Cancel the entire trip
        $this->deleteJson("/fleet-trips/{$trip2->id}")->assertStatus(200);

        // Assert trip cancelled and stock restored back to 100.00
        $this->assertDatabaseHas('fleet_trips', [
            'id' => $trip2->id,
            'status' => 'cancelled'
        ]);
        $this->assertDatabaseHas('fleet_trip_stocks', [
            'fleet_trip_id' => $trip2->id
        ]);
        $this->assertEquals(100.00, $ledgerService->getAvailableStock($this->theniWarehouse->id, $this->tomato->id, $batchCode, $this->gradeBo->code));
    }

    /**
     * Workflow 23: Multi-Location Fleet Trip Dispatch
     */
    public function test_workflow_23_multi_location_fleet_trip_dispatch(): void
    {
        $ledgerService = app(\Modules\StockLedger\Services\StockLedgerService::class);

        // 1. Purchase: 100 kg of Tomato at Theni Warehouse
        $purchaseResponse = $this->postJson('/stock-in-entry', [
            'stock_type' => 'in',
            'reference_no' => 'PRCH-WF23',
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
                    'remarks' => 'Purchase WF23',
                    'invoice_number' => 'INV-WF23',
                    'vendor' => 'Vendor ABC',
                    'purchase_date' => now()->format('Y-m-d'),
                ]
            ]
        ]);
        $purchaseResponse->assertStatus(201);
        $batchCode = StockPurchase::orderBy('id', 'desc')->first()->batch_code;

        // 2. Transfer: Move 40 kg from Theni Warehouse to Madurai Warehouse
        $this->postJson('/stock-transfer', [
            't_transferDate' => now()->format('Y-m-d'),
            't_transferType' => 'inter',
            't_fromLocation' => (string)$this->theniWarehouse->id,
            't_toLocation'   => (string)$this->maduraiWarehouse->id,
            't_product_name' => (string)$this->tomato->id,
            't_batch_code'   => $batchCode,
            't_grade'        => $this->gradeBo->code,
            't_quantity'     => 40,
            't_unit'         => 'kg'
        ])->assertStatus(200);

        // Create route and vehicle
        $vehicle = \Modules\FleetManagement\Models\FleetVehicle::create([
            'registration_number' => 'KL-06-A-2023',
            'model' => 'Eicher Pro',
            'type' => 'Truck',
            'capacity' => 5000
        ]);
        $route = \Modules\FleetManagement\Models\FleetRoutes::create([
            'name' => 'Route WF23',
            'description' => 'Test Route WF23'
        ]);

        // 3. Create a single Fleet Trip dispatching:
        //    - 30 kg from Theni Warehouse (available is 60)
        //    - 20 kg from Madurai Warehouse (available is 40)
        $payloadTrip = [
            'route_id'   => $route->id,
            'vehicle_id' => $vehicle->id,
            'start_date' => now()->format('Y-m-d'),
            'tag'        => 'Trip WF23 Multi',
            'sent' => [
                [
                    'product_id'  => $this->tomato->id,
                    'batch'       => $batchCode,
                    'grade'       => $this->gradeBo->code,
                    'unit'        => 'kg',
                    'quantity'    => 30,
                    'location_id' => $this->theniWarehouse->id
                ],
                [
                    'product_id'  => $this->tomato->id,
                    'batch'       => $batchCode,
                    'grade'       => $this->gradeBo->code,
                    'unit'        => 'kg',
                    'quantity'    => 20,
                    'location_id' => $this->maduraiWarehouse->id
                ]
            ]
        ];
        $this->postJson('/create-trip', $payloadTrip)->assertStatus(200);

        // 4. Assert stock balances
        // Theni Warehouse: 100 - 40 - 30 = 30 kg
        $this->assertEquals(30.00, $ledgerService->getAvailableStock($this->theniWarehouse->id, $this->tomato->id, $batchCode, $this->gradeBo->code));
        // Madurai Warehouse: 40 - 20 = 20 kg
        $this->assertEquals(20.00, $ledgerService->getAvailableStock($this->maduraiWarehouse->id, $this->tomato->id, $batchCode, $this->gradeBo->code));

        // 5. Assert ledger entries logged correctly
        $this->assertDatabaseHas('stock_ledger_entries', [
            'location_id'      => $this->theniWarehouse->id,
            'product_id'       => $this->tomato->id,
            'batch_code'       => $batchCode,
            'transaction_type' => 'DISPATCH',
            'quantity'         => -30.00
        ]);

        $this->assertDatabaseHas('stock_ledger_entries', [
            'location_id'      => $this->maduraiWarehouse->id,
            'product_id'       => $this->tomato->id,
            'batch_code'       => $batchCode,
            'transaction_type' => 'DISPATCH',
            'quantity'         => -20.00
        ]);
    }

    /**
     * Workflow 24: Shop Transfer Arrival -> Stock Adjustment -> Fleet Trip
     */
    public function test_workflow_24_shop_transfer_adjustment_fleet_trip(): void
    {
        $ledgerService = app(\Modules\StockLedger\Services\StockLedgerService::class);
        $adjustmentService = app(\Modules\StockAdjustment\Services\StockAdjustmentService::class);

        // 1. Purchase: 100 kg of Tomato at Theni Warehouse
        $purchaseResponse = $this->postJson('/stock-in-entry', [
            'stock_type' => 'in',
            'reference_no' => 'PRCH-WF24',
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
                    'remarks' => 'Purchase WF24',
                    'invoice_number' => 'INV-WF24',
                    'vendor' => 'Vendor ABC',
                    'purchase_date' => now()->format('Y-m-d'),
                ]
            ]
        ]);
        $purchaseResponse->assertStatus(201);
        $batchCode = StockPurchase::orderBy('id', 'desc')->first()->batch_code;

        // 2. Transfer: Move 50 kg from Theni Warehouse to Theni Shop
        $this->postJson('/stock-transfer', [
            't_transferDate' => now()->format('Y-m-d'),
            't_transferType' => 'inter',
            't_fromLocation' => (string)$this->theniWarehouse->id,
            't_toLocation'   => (string)$this->theniShop->id,
            't_product_name' => (string)$this->tomato->id,
            't_batch_code'   => $batchCode,
            't_grade'        => $this->gradeBo->code,
            't_quantity'     => 50,
            't_unit'         => 'kg'
        ])->assertStatus(200);

        $this->assertEquals(50.00, $ledgerService->getAvailableStock($this->theniShop->id, $this->tomato->id, $batchCode, $this->gradeBo->code));

        // 3. Adjust: Perform Stock Adjustment at Theni Shop reducing stock by 2 kg (within 5% of 50 = 2.5 kg, so immediately approved)
        $adjustment = $adjustmentService->createAdjustment([
            'location_id' => $this->theniShop->id,
            'product_id' => $this->tomato->id,
            'batch_code' => $batchCode,
            'grade' => $this->gradeBo->code,
            'adjusted_qty' => -2.00,
            'new_qty' => 48.00,
            'reason' => 'audit_difference',
            'remarks' => 'Shop Adjustment WF24',
        ]);
        $this->assertEquals('approved', $adjustment->status);
        $this->assertEquals(48.00, $ledgerService->getAvailableStock($this->theniShop->id, $this->tomato->id, $batchCode, $this->gradeBo->code));

        // Create route and vehicle
        $vehicle = \Modules\FleetManagement\Models\FleetVehicle::create([
            'registration_number' => 'KL-06-A-2024',
            'model' => 'Eicher Pro',
            'type' => 'Truck',
            'capacity' => 5000
        ]);
        $route = \Modules\FleetManagement\Models\FleetRoutes::create([
            'name' => 'Route WF24',
            'description' => 'Test Route WF24'
        ]);

        // 4. Create Fleet Trip dispatching 40 kg from Theni Shop
        $payloadTrip = [
            'route_id'   => $route->id,
            'vehicle_id' => $vehicle->id,
            'start_date' => now()->format('Y-m-d'),
            'tag'        => 'Trip WF24 Shop',
            'sent' => [
                [
                    'product_id'  => $this->tomato->id,
                    'batch'       => $batchCode,
                    'grade'       => $this->gradeBo->code,
                    'unit'        => 'kg',
                    'quantity'    => 40,
                    'location_id' => $this->theniShop->id
                ]
            ]
        ];
        $this->postJson('/create-trip', $payloadTrip)->assertStatus(200);

        // 5. Assert stock reduced to 8.00 at Theni Shop (48 - 40)
        $this->assertEquals(8.00, $ledgerService->getAvailableStock($this->theniShop->id, $this->tomato->id, $batchCode, $this->gradeBo->code));
    }
}
