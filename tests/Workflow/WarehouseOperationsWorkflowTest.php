<?php

namespace Tests\Workflow;

use Modules\StockManagement\Models\StockIn\StockPurchase;

class WarehouseOperationsWorkflowTest extends BaseWorkflowTestCase
{
    /**
     * Workflow 5: Shop Inventory Receiving
     */
    public function test_workflow_5_standard_inventory_receiving_in_shop(): void
    {
        $payload = [
            'stock_type' => 'in',
            'reference_no' => 'PRCH-SH-001',
            'movement_date' => now()->format('Y-m-d'),
            'in_type' => 'purchase',
            'items' => [
                [
                    'product_id' => $this->onion->id,
                    'product_name' => $this->onion->name,
                    'grade' => $this->gradeBo->code,
                    'location_id' => $this->theniShop->id,
                    'quantity' => 100.00,
                    'unit' => 'kg',
                    'unit_cost' => 10.00,
                    'total' => 1000.00,
                    'remarks' => 'SH Receiving Test',
                    'invoice_number' => 'INV-SH-001',
                    'vendor' => 'Vendor ABC',
                    'purchase_date' => now()->format('Y-m-d'),
                ]
            ]
        ];

        $response = $this->postJson('/stock-in-entry', $payload);
        $response->assertStatus(201);

        $stockPurchase = StockPurchase::first();
        $batchCode = $stockPurchase->batch_code;

        // Assert that the Shop Inventory has been correctly loaded
        $this->assertDatabaseHas('shop_inventory', [
            'shop_id' => $this->theniShop->id,
            'batch_id' => $batchCode,
            'product_id' => $this->onion->id,
            'grade' => $this->gradeBo->code,
            'qty' => 100.00
        ]);

        // Assert ledger entry matches
        $this->assertDatabaseHas('stock_ledger_entries', [
            'transaction_type' => 'PURCHASE',
            'location_id' => $this->theniShop->id,
            'product_id' => $this->onion->id,
            'batch_code' => $batchCode,
            'grade' => $this->gradeBo->code,
            'quantity' => 100.00
        ]);
    }

    /**
     * Workflow 6: Direct Stock Out
     */
    public function test_workflow_6_warehouse_direct_stock_out(): void
    {
        // 1. Purchase 1000kg at Warehouse
        $purchasePayload = [
            'stock_type' => 'in',
            'reference_no' => 'PRCH-WH-DIR-SOUT',
            'movement_date' => now()->format('Y-m-d'),
            'in_type' => 'purchase',
            'items' => [
                [
                    'product_id' => $this->onion->id,
                    'product_name' => $this->onion->name,
                    'grade' => $this->gradeBo->code,
                    'location_id' => $this->theniWarehouse->id,
                    'quantity' => 1000.00,
                    'unit' => 'kg',
                    'unit_cost' => 10.00,
                    'total' => 10000.00,
                    'remarks' => 'Purchase for Direct Stock Out',
                    'invoice_number' => 'INV-SOUT-001',
                    'vendor' => 'Vendor ABC',
                    'purchase_date' => now()->format('Y-m-d'),
                ]
            ]
        ];

        $response = $this->postJson('/stock-in-entry', $purchasePayload);
        $response->assertStatus(201);

        $stockPurchase = StockPurchase::first();
        $batchCode = $stockPurchase->batch_code;

        // 2. Mark a Stock Out (spoilage) of 200kg
        $stockOutPayload = [
            'stock_type'    => 'out',
            'reference_no'  => 'SOUT-WH-001',
            'movement_date' => now()->format('Y-m-d'),
            'out_type'      => 'spoiled',
            'destination'   => 'spoiled',
            'remarks'       => 'Spoiled 200kg of Onion',
            'items'         => [
                [
                    'product_id' => $this->onion->id,
                    'grade'      => $this->gradeBo->code,
                    'location_id'=> $this->theniWarehouse->id,
                    'quantity'   => 200.00,
                    'unit'       => 'kg',
                    'unit_cost'  => 10.00,
                    'total'      => 2000.00,
                    'batch_code' => $batchCode,
                ]
            ]
        ];

        $response = $this->postJson('/stock-out-entry', $stockOutPayload);
        $response->assertStatus(201);

        // 3. Assert Warehouse final balance: 1000 - 200 = 800kg
        $this->assertDatabaseHas('warehouse_inventory', [
            'warehouse_id' => $this->theniWarehouse->id,
            'batch'        => $batchCode,
            'product_id'   => $this->onion->id,
            'grade'        => $this->gradeBo->code,
            'qty'          => 800.00
        ]);
    }

    /**
     * Workflow 7: Grade Mismatch Stock Out
     */
    public function test_workflow_7_warehouse_grade_mismatch_stock_out(): void
    {
        // 1. Purchase 1000kg at Warehouse with Grade NAME 'Big' (code 'BO')
        $purchasePayload = [
            'stock_type' => 'in',
            'reference_no' => 'PRCH-WH-GRADE-MISMATCH',
            'movement_date' => now()->format('Y-m-d'),
            'in_type' => 'purchase',
            'items' => [
                [
                    'product_id' => $this->onion->id,
                    'product_name' => $this->onion->name,
                    'grade' => 'Big', // Grade Name
                    'location_id' => $this->theniWarehouse->id,
                    'quantity' => 1000.00,
                    'unit' => 'kg',
                    'unit_cost' => 10.00,
                    'total' => 10000.00,
                    'remarks' => 'Purchase with Grade Name',
                    'invoice_number' => 'INV-MISMATCH',
                    'vendor' => 'Vendor ABC',
                    'purchase_date' => now()->format('Y-m-d'),
                ]
            ]
        ];

        $response = $this->postJson('/stock-in-entry', $purchasePayload);
        $response->assertStatus(201);

        $stockPurchase = StockPurchase::first();
        $batchCode = $stockPurchase->batch_code;

        // 2. Perform Stock Out of 300kg using Grade CODE 'BO'
        $stockOutPayload = [
            'stock_type'    => 'out',
            'reference_no'  => 'SOUT-MISMATCH-001',
            'movement_date' => now()->format('Y-m-d'),
            'out_type'      => 'spoiled',
            'destination'   => 'spoiled',
            'remarks'       => 'Spoil 300kg using Grade Code BO',
            'items'         => [
                [
                    'product_id' => $this->onion->id,
                    'grade'      => 'BO', // Grade Code
                    'location_id'=> $this->theniWarehouse->id,
                    'quantity'   => 300.00,
                    'unit'       => 'kg',
                    'unit_cost'  => 10.00,
                    'total'      => 3000.00,
                    'batch_code' => $batchCode,
                ]
            ]
        ];

        $response = $this->postJson('/stock-out-entry', $stockOutPayload);
        $response->assertStatus(201);

        // 3. Assert Warehouse final balance is decremented correctly to 700.00
        $this->assertDatabaseHas('warehouse_inventory', [
            'warehouse_id' => $this->theniWarehouse->id,
            'batch'        => $batchCode,
            'product_id'   => $this->onion->id,
            'grade'        => 'Big',
            'qty'          => 700.00
        ]);
    }

    /**
     * Workflow 8: Warehouse Inventory Receiving
     */
    public function test_workflow_8_standard_inventory_receiving_in_warehouse(): void
    {
        $payload = [
            'stock_type' => 'in',
            'reference_no' => 'PRCH-WH-001',
            'movement_date' => now()->format('Y-m-d'),
            'in_type' => 'purchase',
            'items' => [
                [
                    'product_id' => $this->onion->id,
                    'product_name' => $this->onion->name,
                    'grade' => $this->gradeBo->code,
                    'location_id' => $this->theniWarehouse->id,
                    'quantity' => 1000.00,
                    'unit' => 'kg',
                    'unit_cost' => 10.00,
                    'total' => 10000.00,
                    'remarks' => 'WH Receiving Test',
                    'invoice_number' => 'INV-WH-001',
                    'vendor' => 'Vendor ABC',
                    'purchase_date' => now()->format('Y-m-d'),
                ]
            ]
        ];

        $response = $this->postJson('/stock-in-entry', $payload);
        $response->assertStatus(201);

        $stockPurchase = StockPurchase::first();
        $batchCode = $stockPurchase->batch_code;

        // Assert that the Warehouse Inventory has been correctly loaded
        $this->assertDatabaseHas('warehouse_inventory', [
            'warehouse_id' => $this->theniWarehouse->id,
            'batch' => $batchCode,
            'product_id' => $this->onion->id,
            'grade' => $this->gradeBo->code,
            'qty' => 1000.00
        ]);
    }

    /**
     * Workflow 9: Warehouse Inventory Sale
     */
    public function test_workflow_9_warehouse_inventory_sale(): void
    {
        // 1. Purchase 1000kg at Warehouse
        $purchasePayload = [
            'stock_type' => 'in',
            'reference_no' => 'PRCH-WH-SALE',
            'movement_date' => now()->format('Y-m-d'),
            'in_type' => 'purchase',
            'items' => [
                [
                    'product_id' => $this->onion->id,
                    'product_name' => $this->onion->name,
                    'grade' => $this->gradeBo->code,
                    'location_id' => $this->theniWarehouse->id,
                    'quantity' => 1000.00,
                    'unit' => 'kg',
                    'unit_cost' => 10.00,
                    'total' => 10000.00,
                    'remarks' => 'Purchase for Sale',
                    'invoice_number' => 'INV-SALE-001',
                    'vendor' => 'Vendor ABC',
                    'purchase_date' => now()->format('Y-m-d'),
                ]
            ]
        ];

        $response = $this->postJson('/stock-in-entry', $purchasePayload);
        $response->assertStatus(201);

        $stockPurchase = StockPurchase::first();
        $batchCode = $stockPurchase->batch_code;

        // 2. Sell 300kg from the same warehouse
        $salePayload = [
            'stock_type'    => 'out',
            'reference_no'  => 'SALE-WH-001',
            'movement_date' => now()->format('Y-m-d'),
            'out_type'      => 'sale',
            'destination'   => 'customer',
            'remarks'       => 'WF Sale 300kg',
            'items'         => [
                [
                    'product_id' => $this->onion->id,
                    'grade'      => $this->gradeBo->code,
                    'location_id' => $this->theniWarehouse->id,
                    'quantity'   => 300.00,
                    'unit'       => 'kg',
                    'unit_cost'  => 10.00,
                    'total'      => 3000.00,
                    'batch_code' => $batchCode,
                ]
            ]
        ];

        $response = $this->postJson('/stock-out-entry', $salePayload);
        $response->assertStatus(201);

        // 3. Assert Warehouse final balance: 1000 - 300 = 700kg
        $this->assertDatabaseHas('warehouse_inventory', [
            'warehouse_id' => $this->theniWarehouse->id,
            'batch'        => $batchCode,
            'product_id'   => $this->onion->id,
            'grade'        => $this->gradeBo->code,
            'qty'          => 700.00
        ]);
    }

    /**
     * Workflow 10: Warehouse Reverse Transfer Stock Out
     */
    public function test_workflow_10_warehouse_reverse_transfer_stock_out(): void
    {
        // 1. Purchase 1000kg at Warehouse A
        $purchasePayload = [
            'stock_type' => 'in',
            'reference_no' => 'PRCH-WH-REV-TRNS-SOUT',
            'movement_date' => now()->format('Y-m-d'),
            'in_type' => 'purchase',
            'items' => [
                [
                    'product_id' => $this->onion->id,
                    'product_name' => $this->onion->name,
                    'grade' => $this->gradeBo->code,
                    'location_id' => $this->theniWarehouse->id,
                    'quantity' => 1000.00,
                    'unit' => 'kg',
                    'unit_cost' => 10.00,
                    'total' => 10000.00,
                    'remarks' => 'Initial Purchase at Warehouse A',
                    'invoice_number' => 'INV-WH-A',
                    'vendor' => 'Vendor ABC',
                    'purchase_date' => now()->format('Y-m-d'),
                ]
            ]
        ];

        $response = $this->postJson('/stock-in-entry', $purchasePayload);
        $response->assertStatus(201);

        $stockPurchase = StockPurchase::first();
        $batchCode = $stockPurchase->batch_code;

        // 2. Transfer 400kg from Warehouse A to Warehouse B
        $transferPayload1 = [
            't_transferDate' => now()->format('Y-m-d'),
            't_transferType' => 'inter',
            't_fromLocation' => (string) $this->theniWarehouse->id,
            't_toLocation'   => (string) $this->maduraiWarehouse->id,
            't_product_name' => (string) $this->onion->id,
            't_batch_code'   => $batchCode,
            't_grade'        => $this->gradeBo->code,
            't_quantity'     => 400.00,
            't_unit'         => 'kg',
            't_textarea'     => 'Transferring to Warehouse B',
        ];

        $response = $this->postJson('/stock-transfer', $transferPayload1);
        $response->assertStatus(200);

        // 3. Reverse transfer 150kg from Warehouse B back to Warehouse A
        $transferPayload2 = [
            't_transferDate' => now()->format('Y-m-d'),
            't_transferType' => 'inter',
            't_fromLocation' => (string) $this->maduraiWarehouse->id,
            't_toLocation'   => (string) $this->theniWarehouse->id,
            't_product_name' => (string) $this->onion->id,
            't_batch_code'   => $batchCode,
            't_grade'        => $this->gradeBo->code,
            't_quantity'     => 150.00,
            't_unit'         => 'kg',
            't_textarea'     => 'Reversing back to Warehouse A',
        ];

        $response = $this->postJson('/stock-transfer', $transferPayload2);
        $response->assertStatus(200);

        // 4. Mark stock out of 100kg from Warehouse A
        $stockOutPayload = [
            'stock_type'    => 'out',
            'reference_no'  => 'SOUT-WH-A-002',
            'movement_date' => now()->format('Y-m-d'),
            'out_type'      => 'spoiled',
            'destination'   => 'spoiled',
            'remarks'       => 'Spoiled 100kg of Onion at Warehouse A',
            'items'         => [
                [
                    'product_id' => $this->onion->id,
                    'grade'      => $this->gradeBo->code,
                    'location_id'=> $this->theniWarehouse->id,
                    'quantity'   => 100.00,
                    'unit'       => 'kg',
                    'unit_cost'  => 10.00,
                    'total'      => 1000.00,
                    'batch_code' => $batchCode,
                ]
            ]
        ];

        $response = $this->postJson('/stock-out-entry', $stockOutPayload);
        $response->assertStatus(201);

        // 5. Assert final quantities
        $this->assertDatabaseHas('warehouse_inventory', [
            'warehouse_id' => $this->theniWarehouse->id,
            'batch'        => $batchCode,
            'qty'          => 650.00
        ]);

        $this->assertDatabaseHas('warehouse_inventory', [
            'warehouse_id' => $this->maduraiWarehouse->id,
            'batch'        => $batchCode,
            'qty'          => 250.00
        ]);
    }

    /**
     * Workflow 11: Warehouse Reverse Transfer
     */
    public function test_workflow_11_warehouse_reverse_stock_transfer(): void
    {
        // 1. Purchase 1000kg at Warehouse A
        $purchasePayload = [
            'stock_type' => 'in',
            'reference_no' => 'PRCH-WH-A',
            'movement_date' => now()->format('Y-m-d'),
            'in_type' => 'purchase',
            'items' => [
                [
                    'product_id' => $this->onion->id,
                    'product_name' => $this->onion->name,
                    'grade' => $this->gradeBo->code,
                    'location_id' => $this->theniWarehouse->id,
                    'quantity' => 1000.00,
                    'unit' => 'kg',
                    'unit_cost' => 10.00,
                    'total' => 10000.00,
                    'remarks' => 'Initial Purchase at Warehouse A',
                    'invoice_number' => 'INV-WH-A',
                    'vendor' => 'Vendor ABC',
                    'purchase_date' => now()->format('Y-m-d'),
                ]
            ]
        ];

        $response = $this->postJson('/stock-in-entry', $purchasePayload);
        $response->assertStatus(201);

        $stockPurchase = StockPurchase::first();
        $batchCode = $stockPurchase->batch_code;

        // 2. Transfer 400kg from Warehouse A to Warehouse B
        $transferPayload1 = [
            't_transferDate' => now()->format('Y-m-d'),
            't_transferType' => 'inter',
            't_fromLocation' => (string) $this->theniWarehouse->id,
            't_toLocation'   => (string) $this->maduraiWarehouse->id,
            't_product_name' => (string) $this->onion->id,
            't_batch_code'   => $batchCode,
            't_grade'        => $this->gradeBo->code,
            't_quantity'     => 400.00,
            't_unit'         => 'kg',
            't_textarea'     => 'Transferring to Warehouse B',
        ];

        $response = $this->postJson('/stock-transfer', $transferPayload1);
        $response->assertStatus(200);

        // 3. Immediately reverse transfer 150kg from Warehouse B back to Warehouse A
        $transferPayload2 = [
            't_transferDate' => now()->format('Y-m-d'),
            't_transferType' => 'inter',
            't_fromLocation' => (string) $this->maduraiWarehouse->id,
            't_toLocation'   => (string) $this->theniWarehouse->id,
            't_product_name' => (string) $this->onion->id,
            't_batch_code'   => $batchCode,
            't_grade'        => $this->gradeBo->code,
            't_quantity'     => 150.00,
            't_unit'         => 'kg',
            't_textarea'     => 'Reversing back to Warehouse A',
        ];

        $response = $this->postJson('/stock-transfer', $transferPayload2);
        $response->assertStatus(200);

        // 4. Assert Warehouse A final balance: 1000 - 400 + 150 = 750kg
        $this->assertDatabaseHas('warehouse_inventory', [
            'warehouse_id' => $this->theniWarehouse->id,
            'batch' => $batchCode,
            'qty' => 750.00
        ]);

        // 5. Assert Warehouse B final balance: 400 - 150 = 250kg
        $this->assertDatabaseHas('warehouse_inventory', [
            'warehouse_id' => $this->maduraiWarehouse->id,
            'batch' => $batchCode,
            'qty' => 250.00
        ]);
    }

    /**
     * Workflow 12: Warehouse Stock Out Overdraw
     */
    public function test_workflow_12_warehouse_stock_out_overdraw(): void
    {
        // 1. Purchase 1000kg at Warehouse A
        $purchasePayload = [
            'stock_type' => 'in',
            'reference_no' => 'PRCH-WH-OVERDRAW',
            'movement_date' => now()->format('Y-m-d'),
            'in_type' => 'purchase',
            'items' => [
                [
                    'product_id' => $this->onion->id,
                    'product_name' => $this->onion->name,
                    'grade' => $this->gradeBo->code,
                    'location_id' => $this->theniWarehouse->id,
                    'quantity' => 1000.00,
                    'unit' => 'kg',
                    'unit_cost' => 10.00,
                    'total' => 10000.00,
                    'remarks' => 'Purchase for Overdraw Test',
                    'invoice_number' => 'INV-OVERDRAW',
                    'vendor' => 'Vendor ABC',
                    'purchase_date' => now()->format('Y-m-d'),
                ]
            ]
        ];

        $response = $this->postJson('/stock-in-entry', $purchasePayload);
        $response->assertStatus(201);

        $stockPurchase = StockPurchase::first();
        $batchCode = $stockPurchase->batch_code;

        // 2. Transfer 400kg from Warehouse A to Warehouse B
        $transferPayload = [
            't_transferDate' => now()->format('Y-m-d'),
            't_transferType' => 'inter',
            't_fromLocation' => (string) $this->theniWarehouse->id,
            't_toLocation'   => (string) $this->maduraiWarehouse->id,
            't_product_name' => (string) $this->onion->id,
            't_batch_code'   => $batchCode,
            't_grade'        => $this->gradeBo->code,
            't_quantity'     => 400.00,
            't_unit'         => 'kg',
            't_textarea'     => 'Transferring to Warehouse B',
        ];

        $response = $this->postJson('/stock-transfer', $transferPayload);
        $response->assertStatus(200);

        // 3. Attempt to stock out 500kg from Warehouse B (only has 400kg)
        $stockOutPayload = [
            'stock_type'    => 'out',
            'reference_no'  => 'SOUT-OVERDRAW-001',
            'movement_date' => now()->format('Y-m-d'),
            'out_type'      => 'spoiled',
            'destination'   => 'spoiled',
            'remarks'       => 'Attempting to overdraw 500kg of Onion from Warehouse B',
            'items'         => [
                [
                    'product_id' => $this->onion->id,
                    'grade'      => $this->gradeBo->code,
                    'location_id'=> $this->maduraiWarehouse->id,
                    'quantity'   => 500.00,
                    'unit'       => 'kg',
                    'unit_cost'  => 10.00,
                    'total'      => 5000.00,
                    'batch_code' => $batchCode,
                ]
            ]
        ];

        $response = $this->postJson('/stock-out-entry', $stockOutPayload);
        
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['items.0.quantity']);

        // 5. Assert Warehouse B balance is still unchanged (400.00)
        $this->assertDatabaseHas('warehouse_inventory', [
            'warehouse_id' => $this->maduraiWarehouse->id,
            'batch'        => $batchCode,
            'qty'          => 400.00
        ]);
    }

    /**
     * Workflow 13: Warehouse-to-Warehouse Transfer
     */
    public function test_workflow_13_warehouse_to_warehouse_stock_transfer(): void
    {
        // 1. Purchase 1000kg at Warehouse A
        $purchasePayload = [
            'stock_type' => 'in',
            'reference_no' => 'PRCH-WH-A',
            'movement_date' => now()->format('Y-m-d'),
            'in_type' => 'purchase',
            'items' => [
                [
                    'product_id' => $this->onion->id,
                    'product_name' => $this->onion->name,
                    'grade' => $this->gradeBo->code,
                    'location_id' => $this->theniWarehouse->id,
                    'quantity' => 1000.00,
                    'unit' => 'kg',
                    'unit_cost' => 10.00,
                    'total' => 10000.00,
                    'remarks' => 'Initial Purchase at Warehouse A',
                    'invoice_number' => 'INV-WH-A',
                    'vendor' => 'Vendor ABC',
                    'purchase_date' => now()->format('Y-m-d'),
                ]
            ]
        ];

        $response = $this->postJson('/stock-in-entry', $purchasePayload);
        $response->assertStatus(201);

        $stockPurchase = StockPurchase::first();
        $batchCode = $stockPurchase->batch_code;

        // 2. Transfer 400kg from Warehouse A to Warehouse B
        $transferPayload = [
            't_transferDate' => now()->format('Y-m-d'),
            't_transferType' => 'inter',
            't_fromLocation' => (string) $this->theniWarehouse->id,
            't_toLocation'   => (string) $this->maduraiWarehouse->id,
            't_product_name' => (string) $this->onion->id,
            't_batch_code'   => $batchCode,
            't_grade'        => $this->gradeBo->code,
            't_quantity'     => 400.00,
            't_unit'         => 'kg',
            't_textarea'     => 'Transferring 400 kg of Onion to Warehouse B',
        ];

        $response = $this->postJson('/stock-transfer', $transferPayload);
        $response->assertStatus(200);

        // 3. Assert Warehouse A inventory decreased by 400kg (leaving 600kg)
        $this->assertDatabaseHas('warehouse_inventory', [
            'warehouse_id' => $this->theniWarehouse->id,
            'batch' => $batchCode,
            'qty' => 600.00
        ]);

        // 4. Assert Warehouse B inventory increased to 400kg
        $this->assertDatabaseHas('warehouse_inventory', [
            'warehouse_id' => $this->maduraiWarehouse->id,
            'batch' => $batchCode,
            'qty' => 400.00
        ]);
    }

    /**
     * Workflow 14: Warehouse Transfer Stock Out
     */
    public function test_workflow_14_warehouse_transfer_stock_out(): void
    {
        // 1. Purchase 1000kg at Warehouse A
        $purchasePayload = [
            'stock_type' => 'in',
            'reference_no' => 'PRCH-WH-TRNS-SOUT',
            'movement_date' => now()->format('Y-m-d'),
            'in_type' => 'purchase',
            'items' => [
                [
                    'product_id' => $this->onion->id,
                    'product_name' => $this->onion->name,
                    'grade' => $this->gradeBo->code,
                    'location_id' => $this->theniWarehouse->id,
                    'quantity' => 1000.00,
                    'unit' => 'kg',
                    'unit_cost' => 10.00,
                    'total' => 10000.00,
                    'remarks' => 'Initial Purchase at Warehouse A',
                    'invoice_number' => 'INV-WH-A',
                    'vendor' => 'Vendor ABC',
                    'purchase_date' => now()->format('Y-m-d'),
                ]
            ]
        ];

        $response = $this->postJson('/stock-in-entry', $purchasePayload);
        $response->assertStatus(201);

        $stockPurchase = StockPurchase::first();
        $batchCode = $stockPurchase->batch_code;

        // 2. Transfer 400kg from Warehouse A to Warehouse B
        $transferPayload = [
            't_transferDate' => now()->format('Y-m-d'),
            't_transferType' => 'inter',
            't_fromLocation' => (string) $this->theniWarehouse->id,
            't_toLocation'   => (string) $this->maduraiWarehouse->id,
            't_product_name' => (string) $this->onion->id,
            't_batch_code'   => $batchCode,
            't_grade'        => $this->gradeBo->code,
            't_quantity'     => 400.00,
            't_unit'         => 'kg',
            't_textarea'     => 'Transferring to Warehouse B',
        ];

        $response = $this->postJson('/stock-transfer', $transferPayload);
        $response->assertStatus(200);

        // 3. Mark stock out of 150kg from Warehouse B
        $stockOutPayload = [
            'stock_type'    => 'out',
            'reference_no'  => 'SOUT-WH-B-001',
            'movement_date' => now()->format('Y-m-d'),
            'out_type'      => 'spoiled',
            'destination'   => 'spoiled',
            'remarks'       => 'Spoiled 150kg of Onion at Warehouse B',
            'items'         => [
                [
                    'product_id' => $this->onion->id,
                    'grade'      => $this->gradeBo->code,
                    'location_id'=> $this->maduraiWarehouse->id,
                    'quantity'   => 150.00,
                    'unit'       => 'kg',
                    'unit_cost'  => 10.00,
                    'total'      => 1500.00,
                    'batch_code' => $batchCode,
                ]
            ]
        ];

        $response = $this->postJson('/stock-out-entry', $stockOutPayload);
        $response->assertStatus(201);

        // 4. Assert Warehouse A final balance: 1000 - 400 = 600kg
        $this->assertDatabaseHas('warehouse_inventory', [
            'warehouse_id' => $this->theniWarehouse->id,
            'batch'        => $batchCode,
            'qty'          => 600.00
        ]);

        // 5. Assert Warehouse B final balance: 400 - 150 = 250kg
        $this->assertDatabaseHas('warehouse_inventory', [
            'warehouse_id' => $this->maduraiWarehouse->id,
            'batch'        => $batchCode,
            'qty'          => 250.00
        ]);
    }

    /**
     * Workflow 15: Warehouse Sales Unit Matching
     */
    public function test_workflow_15_warehouse_sales_unit_matching_workflow(): void
    {
        // 1. Purchase 100 Kg of Tomatoes
        $purchasePayload = [
            'stock_type' => 'in',
            'reference_no' => 'PRCH-WF-UNIT-MATCH',
            'movement_date' => now()->format('Y-m-d'),
            'in_type' => 'purchase',
            'items' => [
                [
                    'product_id' => $this->tomato->id,
                    'product_name' => $this->tomato->name,
                    'grade' => $this->gradeBo->name,
                    'location_id' => $this->theniWarehouse->id,
                    'quantity' => 100.00,
                    'unit' => 'Kg',
                    'unit_cost' => 10.00,
                    'total' => 1000.00,
                    'remarks' => 'Purchase for workflow test',
                    'invoice_number' => 'INV-WF-UNIT-001',
                    'vendor' => 'Vendor ABC',
                    'purchase_date' => now()->format('Y-m-d'),
                ]
            ]
        ];

        $response = $this->postJson('/stock-in-entry', $purchasePayload);
        $response->assertStatus(201);

        $stockPurchase = StockPurchase::whereHas('purchaseItems', function ($q) {
            $q->where('product', $this->tomato->id);
        })->orderBy('id', 'desc')->firstOrFail();

        $batchCode = $stockPurchase->batch_code;

        // 2. Attempt sale using mismatched unit 'pcs' (fails validation)
        $invalidSalePayload = [
            'customer_name'    => 'Workflow Customer',
            'bill_no'          => 'BILL-INV-001',
            'payment_status'   => 'paid',
            'amount_paid'      => 300.00,
            'payment_date'     => now()->format('Y-m-d'),
            'payment_mode'     => 'cash',
            'shop_id'          => $this->theniWarehouse->id,
            'items'            => [
                [
                    'product'     => $this->tomato->id,
                    'batch_code'  => $batchCode,
                    'grade'       => $this->gradeBo->name,
                    'quantity'    => 30.00,
                    'unit'        => 'pcs',
                    'unit_price'  => 10.00,
                    'total_price' => 300.00,
                ]
            ]
        ];

        $invalidResponse = $this->postJson('/warehouse/sale/store', $invalidSalePayload);
        $invalidResponse->assertStatus(422)
            ->assertJsonValidationErrors('items.0.unit')
            ->assertJsonFragment([
                'errors' => [
                    'items.0.unit' => [
                        "The selected unit 'pcs' does not match the purchase unit 'Kg' for batch '{$batchCode}'."
                    ]
                ]
            ]);

        // 3. Perform sale using matching unit 'Kg' (succeeds)
        $validSalePayload = [
            'customer_name'    => 'Workflow Customer',
            'bill_no'          => 'BILL-VAL-001',
            'payment_status'   => 'paid',
            'amount_paid'      => 300.00,
            'payment_date'     => now()->format('Y-m-d'),
            'payment_mode'     => 'cash',
            'shop_id'          => $this->theniWarehouse->id,
            'items'            => [
                [
                    'product'     => $this->tomato->id,
                    'batch_code'  => $batchCode,
                    'grade'       => $this->gradeBo->name,
                    'quantity'    => 30.00,
                    'unit'        => 'Kg',
                    'unit_price'  => 10.00,
                    'total_price' => 300.00,
                ]
            ]
        ];

        $validResponse = $this->postJson('/warehouse/sale/store', $validSalePayload);
        $validResponse->assertStatus(200);

        // 4. Assert stock levels and ledger entries
        $this->assertDatabaseHas('warehouse_inventory', [
            'warehouse_id' => $this->theniWarehouse->id,
            'batch'        => $batchCode,
            'qty'          => 70.00,
        ]);
    }
}
