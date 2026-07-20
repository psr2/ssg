<?php

namespace Tests\Workflow;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Modules\Locations\Models\LocationModel;
use Modules\Inventory\Models\Products;
use Modules\Inventory\Models\ProductGrade;
use Modules\Inventory\Models\UnitOfMeasurement;
use Modules\StockManagement\Models\StockIn\StockPurchase;
use Modules\StockAdjustment\Models\StockAdjustment;
use Modules\StockAdjustment\Services\StockAdjustmentService;
use App\Models\User;


class InventoryWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected $tomato;
    protected $onion;
    protected $gradeBo;
    protected $unitKg;
    protected $theniWarehouse;
    protected $theniShop;
    protected $maduraiWarehouse;
    protected $maduraiShop;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        $this->unitKg = UnitOfMeasurement::firstOrCreate(
            ['abbreviation' => 'kg'],
            ['name' => 'Kilogram']
        );

        $this->tomato = Products::firstOrCreate(
            ['sku' => 'veg_tom'],
            [
                'name' => 'Tomato',
                'abbreviation' => 'tom',
                'unit_id' => $this->unitKg->id
            ]
        );

        $this->onion = Products::firstOrCreate(
            ['sku' => 'veg_on'],
            [
                'name' => 'Onion',
                'abbreviation' => 'on',
                'unit_id' => $this->unitKg->id
            ]
        );

        $this->gradeBo = ProductGrade::firstOrCreate(
            ['code' => 'BO'],
            [
                'name' => 'Big',
                'is_active' => true
            ]
        );

        $this->theniWarehouse = LocationModel::create([
            'name' => 'Theni Warehouse',
            'type' => 'warehouse',
            'abbreviation' => 'TW',
            'status' => 'active'
        ]);

        $this->theniShop = LocationModel::create([
            'name' => 'Theni Shop',
            'type' => 'shop',
            'abbreviation' => 'TS',
            'status' => 'active'
        ]);

        $this->maduraiWarehouse = LocationModel::create([
            'name' => 'Madurai Warehouse',
            'type' => 'warehouse',
            'abbreviation' => 'MW',
            'status' => 'active'
        ]);

        $this->maduraiShop = LocationModel::create([
            'name' => 'Madurai Shop',
            'type' => 'shop',
            'abbreviation' => 'MS',
            'status' => 'active'
        ]);
    }

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
    public function test_warehouse_sales_unit_matching_workflow(): void
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
