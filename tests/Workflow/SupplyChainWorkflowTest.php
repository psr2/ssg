<?php

namespace Tests\Workflow;

use Modules\StockManagement\Models\StockIn\StockPurchase;

class SupplyChainWorkflowTest extends BaseWorkflowTestCase
{
    /**
     * Workflow 1: Standard Supply Chain
     */
    public function test_workflow_1_standard_supply_chain(): void
    {
        // 1. Purchase: 100 kg of Tomato at Theni Warehouse (Grade: BO)
        $purchaseResponse = $this->postJson('/stock-in-entry', [
            'stock_type' => 'in',
            'reference_no' => 'PRCH-WF1',
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
                    'remarks' => 'Purchase WF1',
                    'invoice_number' => 'INV-WF1',
                    'vendor' => 'Vendor ABC',
                    'purchase_date' => now()->format('Y-m-d'),
                ]
            ]
        ]);
        $purchaseResponse->assertStatus(201);
        $batchCode = StockPurchase::first()->batch_code;

        // 2. Transfer: Move 50.00 kg of Tomato from Theni Warehouse to Theni Shop
        $transferResponse = $this->postJson('/stock-transfer', [
            't_transferDate' => now()->format('Y-m-d'),
            't_transferType' => 'inter',
            't_fromLocation' => (string) $this->theniWarehouse->id,
            't_toLocation'   => (string) $this->theniShop->id,
            't_product_name' => (string) $this->tomato->id,
            't_batch_code'   => $batchCode,
            't_grade'        => $this->gradeBo->code,
            't_quantity'     => 50.00,
            't_unit'         => 'kg',
            't_textarea'     => 'Transfer WF1',
        ]);
        $transferResponse->assertStatus(200);

        // 3. Sale: Sell 30.00 kg of Tomato from Theni Shop
        $saleResponse = $this->postJson('/stock-out-entry', [
            'stock_type'    => 'out',
            'reference_no'  => 'SALE-WF1',
            'movement_date' => now()->format('Y-m-d'),
            'out_type'      => 'sale',
            'destination'   => 'customer',
            'remarks'       => 'Sale WF1',
            'items'         => [
                [
                    'product_id' => $this->tomato->id,
                    'grade'      => $this->gradeBo->code,
                    'location_id' => $this->theniShop->id,
                    'quantity'   => 30.00,
                    'unit'       => 'kg',
                    'unit_cost'  => 10.00,
                    'total'      => 300.00,
                    'batch_code' => $batchCode,
                ]
            ]
        ]);
        $saleResponse->assertStatus(201);

        // Asserts
        $this->assertDatabaseHas('warehouse_inventory', [
            'warehouse_id' => $this->theniWarehouse->id,
            'batch' => $batchCode,
            'qty' => 50.00
        ]);

        $this->assertDatabaseHas('shop_inventory', [
            'shop_id' => $this->theniShop->id,
            'batch_id' => $batchCode,
            'qty' => 20.00
        ]);
    }

    /**
     * Workflow 2: Inter-Warehouse Re-balancing
     */
    public function test_workflow_2_inter_warehouse_rebalancing(): void
    {
        // 1. Purchase: 200 kg of Onion at Theni Warehouse
        $purchaseResponse = $this->postJson('/stock-in-entry', [
            'stock_type' => 'in',
            'reference_no' => 'PRCH-WF2',
            'movement_date' => now()->format('Y-m-d'),
            'in_type' => 'purchase',
            'items' => [
                [
                    'product_id' => $this->onion->id,
                    'product_name' => $this->onion->name,
                    'grade' => $this->gradeBo->code,
                    'location_id' => $this->theniWarehouse->id,
                    'quantity' => 200.00,
                    'unit' => 'kg',
                    'unit_cost' => 10.00,
                    'total' => 2000.00,
                    'remarks' => 'Purchase WF2',
                    'invoice_number' => 'INV-WF2',
                    'vendor' => 'Vendor ABC',
                    'purchase_date' => now()->format('Y-m-d'),
                ]
            ]
        ]);
        $purchaseResponse->assertStatus(201);
        $batchCode = StockPurchase::whereHas('purchaseItems', function ($q) {
            $q->where('product', $this->onion->id);
        })->first()->batch_code;

        // 2. Transfer: Move 120 kg of Onion from Theni Warehouse to Madurai Warehouse
        $transferResponse = $this->postJson('/stock-transfer', [
            't_transferDate' => now()->format('Y-m-d'),
            't_transferType' => 'inter',
            't_fromLocation' => (string) $this->theniWarehouse->id,
            't_toLocation'   => (string) $this->maduraiWarehouse->id,
            't_product_name' => (string) $this->onion->id,
            't_batch_code'   => $batchCode,
            't_grade'        => $this->gradeBo->code,
            't_quantity'     => 120.00,
            't_unit'         => 'kg',
            't_textarea'     => 'Transfer WF2',
        ]);
        $transferResponse->assertStatus(200);

        // 3. Sale: Sell 50.00 kg of Onion from Madurai Warehouse
        $saleResponse = $this->postJson('/stock-out-entry', [
            'stock_type'    => 'out',
            'reference_no'  => 'SALE-WF2',
            'movement_date' => now()->format('Y-m-d'),
            'out_type'      => 'sale',
            'destination'   => 'customer',
            'remarks'       => 'Sale WF2',
            'items'         => [
                [
                    'product_id' => $this->onion->id,
                    'grade'      => $this->gradeBo->code,
                    'location_id' => $this->maduraiWarehouse->id,
                    'quantity'   => 50.00,
                    'unit'       => 'kg',
                    'unit_cost'  => 10.00,
                    'total'      => 500.00,
                    'batch_code' => $batchCode,
                ]
            ]
        ]);
        $saleResponse->assertStatus(201);

        // Asserts
        $this->assertDatabaseHas('warehouse_inventory', [
            'warehouse_id' => $this->theniWarehouse->id,
            'batch' => $batchCode,
            'qty' => 80.00
        ]);

        $this->assertDatabaseHas('warehouse_inventory', [
            'warehouse_id' => $this->maduraiWarehouse->id,
            'batch' => $batchCode,
            'qty' => 70.00
        ]);
    }

    /**
     * Workflow 3: Customer Return & Resale
     */
    public function test_workflow_3_customer_return_and_resale(): void
    {
        // 1. Purchase: 100 kg of Tomato at Theni Warehouse
        $purchaseResponse = $this->postJson('/stock-in-entry', [
            'stock_type' => 'in',
            'reference_no' => 'PRCH-WF3',
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
                    'remarks' => 'Purchase WF3',
                    'invoice_number' => 'INV-WF3',
                    'vendor' => 'Vendor ABC',
                    'purchase_date' => now()->format('Y-m-d'),
                ]
            ]
        ]);
        $purchaseResponse->assertStatus(201);
        $batchCode = StockPurchase::whereHas('purchaseItems', function ($q) {
            $q->where('product', $this->tomato->id);
        })->orderBy('id', 'desc')->first()->batch_code;

        // 2. Sale: Sell 40.00 kg to Customer A
        $saleResponse1 = $this->postJson('/stock-out-entry', [
            'stock_type'    => 'out',
            'reference_no'  => 'SALE-WF3-A',
            'movement_date' => now()->format('Y-m-d'),
            'out_type'      => 'sale',
            'destination'   => 'customer',
            'remarks'       => 'Sale WF3 A',
            'items'         => [
                [
                    'product_id' => $this->tomato->id,
                    'grade'      => $this->gradeBo->code,
                    'location_id' => $this->theniWarehouse->id,
                    'quantity'   => 40.00,
                    'unit'       => 'kg',
                    'unit_cost'  => 10.00,
                    'total'      => 400.00,
                    'batch_code' => $batchCode,
                ]
            ]
        ]);
        $saleResponse1->assertStatus(201);

        // 3. Return: Customer A returns 15.00 kg to Theni Warehouse
        $returnResponse = $this->postJson('/stock-in-entry', [
            'stock_type' => 'in',
            'reference_no' => 'RET-WF3',
            'movement_date' => now()->format('Y-m-d'),
            'in_type' => 'return',
            'return_source' => 'Customer A',
            'return_reason' => 'Defect/Excess',
            'customer_name' => 'Customer A',
            'customer_contact' => '1234567890',
            'bill_number' => 15,
            'items' => [
                [
                    'product_id' => $this->tomato->id,
                    'product_name' => $this->tomato->name,
                    'grade' => $this->gradeBo->code,
                    'location_id' => $this->theniWarehouse->id,
                    'quantity' => 15.00,
                    'unit' => 'kg',
                    'unit_cost' => 10.00,
                    'total' => 150.00,
                    'remarks' => 'Return WF3',
                    'invoice_number' => 'INV-WF3-RET',
                    'vendor' => 'Vendor ABC',
                    'purchase_date' => now()->format('Y-m-d'),
                ]
            ]
        ]);
        $returnResponse->assertStatus(201);

        // Retrieve return batch
        $returnBatchCode = StockPurchase::orderBy('id', 'desc')->first()->batch_code;

        // 4. Sale: Sell 20.00 kg to Customer B from the original batch
        $saleResponse2 = $this->postJson('/stock-out-entry', [
            'stock_type'    => 'out',
            'reference_no'  => 'SALE-WF3-B',
            'movement_date' => now()->format('Y-m-d'),
            'out_type'      => 'sale',
            'destination'   => 'customer',
            'remarks'       => 'Sale WF3 B',
            'items'         => [
                [
                    'product_id' => $this->tomato->id,
                    'grade'      => $this->gradeBo->code,
                    'location_id' => $this->theniWarehouse->id,
                    'quantity'   => 20.00,
                    'unit'       => 'kg',
                    'unit_cost'  => 10.00,
                    'total'      => 200.00,
                    'batch_code' => $batchCode,
                ]
            ]
        ]);
        $saleResponse2->assertStatus(201);

        // Asserts: 100 - 40 - 20 = 40 kg in original batch, and 15 kg in return batch (Total 55 kg)
        $this->assertDatabaseHas('warehouse_inventory', [
            'warehouse_id' => $this->theniWarehouse->id,
            'batch' => $batchCode,
            'qty' => 40.00
        ]);

        $this->assertDatabaseHas('warehouse_inventory', [
            'warehouse_id' => $this->theniWarehouse->id,
            'batch' => $returnBatchCode,
            'qty' => 15.00
        ]);
    }

    /**
     * Workflow 4: Multi-Hop Redistribution
     */
    public function test_workflow_4_multihop_redistribution(): void
    {
        // 1. Purchase: 150 kg of Onion at Theni Warehouse
        $purchaseResponse = $this->postJson('/stock-in-entry', [
            'stock_type' => 'in',
            'reference_no' => 'PRCH-WF4',
            'movement_date' => now()->format('Y-m-d'),
            'in_type' => 'purchase',
            'items' => [
                [
                    'product_id' => $this->onion->id,
                    'product_name' => $this->onion->name,
                    'grade' => $this->gradeBo->code,
                    'location_id' => $this->theniWarehouse->id,
                    'quantity' => 150.00,
                    'unit' => 'kg',
                    'unit_cost' => 10.00,
                    'total' => 1500.00,
                    'remarks' => 'Purchase WF4',
                    'invoice_number' => 'INV-WF4',
                    'vendor' => 'Vendor ABC',
                    'purchase_date' => now()->format('Y-m-d'),
                ]
            ]
        ]);
        $purchaseResponse->assertStatus(201);
        $batchCode = StockPurchase::whereHas('purchaseItems', function ($q) {
            $q->where('product', $this->onion->id);
        })->orderBy('id', 'desc')->first()->batch_code;

        // 2. Transfer: Move 80.00 kg to Theni Shop
        $transferResponse1 = $this->postJson('/stock-transfer', [
            't_transferDate' => now()->format('Y-m-d'),
            't_transferType' => 'inter',
            't_fromLocation' => (string) $this->theniWarehouse->id,
            't_toLocation'   => (string) $this->theniShop->id,
            't_product_name' => (string) $this->onion->id,
            't_batch_code'   => $batchCode,
            't_grade'        => $this->gradeBo->code,
            't_quantity'     => 80.00,
            't_unit'         => 'kg',
            't_textarea'     => 'Transfer WF4-1',
        ]);
        $transferResponse1->assertStatus(200);

        // 3. Transfer: Move 30.00 kg from Theni Shop to Madurai Shop
        $transferResponse2 = $this->postJson('/stock-transfer', [
            't_transferDate' => now()->format('Y-m-d'),
            't_transferType' => 'inter',
            't_fromLocation' => (string) $this->theniShop->id,
            't_toLocation'   => (string) $this->maduraiShop->id,
            't_product_name' => (string) $this->onion->id,
            't_batch_code'   => $batchCode,
            't_grade'        => $this->gradeBo->code,
            't_quantity'     => 30.00,
            't_unit'         => 'kg',
            't_textarea'     => 'Transfer WF4-2',
        ]);
        $transferResponse2->assertStatus(200);

        // 4. Sale: Sell 20.00 kg from Madurai Shop
        $saleResponse = $this->postJson('/stock-out-entry', [
            'stock_type'    => 'out',
            'reference_no'  => 'SALE-WF4',
            'movement_date' => now()->format('Y-m-d'),
            'out_type'      => 'sale',
            'destination'   => 'customer',
            'remarks'       => 'Sale WF4',
            'items'         => [
                [
                    'product_id' => $this->onion->id,
                    'grade'      => $this->gradeBo->code,
                    'location_id' => $this->maduraiShop->id,
                    'quantity'   => 20.00,
                    'unit'       => 'kg',
                    'unit_cost'  => 10.00,
                    'total'      => 200.00,
                    'batch_code' => $batchCode,
                ]
            ]
        ]);
        $saleResponse->assertStatus(201);

        // Asserts
        $this->assertDatabaseHas('warehouse_inventory', [
            'warehouse_id' => $this->theniWarehouse->id,
            'batch' => $batchCode,
            'qty' => 70.00
        ]);

        $this->assertDatabaseHas('shop_inventory', [
            'shop_id' => $this->theniShop->id,
            'batch_id' => $batchCode,
            'qty' => 50.00
        ]);

        $this->assertDatabaseHas('shop_inventory', [
            'shop_id' => $this->maduraiShop->id,
            'batch_id' => $batchCode,
            'qty' => 10.00
        ]);
    }
}
