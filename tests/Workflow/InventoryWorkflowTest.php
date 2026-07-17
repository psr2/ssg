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
    public function test_standard_inventory_receiving_in_shop(): void
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
    public function test_warehouse_direct_stock_out(): void
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
    public function test_warehouse_grade_mismatch_stock_out(): void
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
    public function test_standard_inventory_receiving_in_warehouse(): void
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
    public function test_warehouse_inventory_sale(): void
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
    public function test_warehouse_reverse_transfer_stock_out(): void
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
    public function test_warehouse_reverse_stock_transfer(): void
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
    public function test_warehouse_stock_out_overdraw(): void
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
    public function test_warehouse_to_warehouse_stock_transfer(): void
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
    public function test_warehouse_transfer_stock_out(): void
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
}

