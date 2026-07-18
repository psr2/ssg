<?php

namespace Modules\Billing\Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Modules\Billing\Models\BillingAdjustment;
use Modules\Billing\Services\BillingAdjustmentService;
use Modules\Warehouse\Models\WarehouseSale;
use Modules\ShopManagement\Models\ShopSale;
use Modules\FleetManagement\Models\FleetSale;

class BillingAdjustmentTest extends TestCase
{
    use RefreshDatabase;

    protected BillingAdjustmentService $billingService;
    protected $warehouse;
    protected $shop;
    protected $warehouseCustomer;
    protected $shopCustomer;
    protected $route;
    protected $vehicle;
    protected $trip;

    protected function setUp(): void
    {
        parent::setUp();
        $this->billingService = $this->app->make(BillingAdjustmentService::class);

        // Create warehouse and shop locations
        $this->warehouse = \Modules\Locations\Models\LocationModel::create([
            'name' => 'Test Warehouse',
            'type' => 'warehouse',
            'is_active' => true,
        ]);

        $this->shop = \Modules\Locations\Models\LocationModel::create([
            'name' => 'Test Shop',
            'type' => 'shop',
            'is_active' => true,
        ]);

        // Create customers
        $this->warehouseCustomer = \Modules\Warehouse\Models\WarehouseCustomer::create([
            'name' => 'Test Warehouse Customer',
            'warehouse_id' => $this->warehouse->id,
        ]);

        $this->shopCustomer = \Modules\ShopManagement\Models\ShopCustomer::create([
            'name' => 'Test Shop Customer',
            'shop_id' => $this->shop->id,
        ]);

        // Create Fleet Route & Vehicle & Trip
        $this->route = \Modules\FleetManagement\Models\FleetRoutes::create([
            'name' => 'Route A',
            'description' => 'Route A Description',
        ]);

        $this->vehicle = \Modules\FleetManagement\Models\FleetVehicle::create([
            'registration_number' => 'REG-123',
            'type' => 'Truck',
        ]);

        $this->trip = \Modules\FleetManagement\Models\FleetTrip::create([
            'route_id' => $this->route->id,
            'vehicle_id' => $this->vehicle->id,
            'start_date' => now()->format('Y-m-d'),
            'tag' => 'TRIP-1',
        ]);
    }

    /**
     * Test warehouse sale billing adjustment.
     */
    public function test_warehouse_sale_billing_adjustment(): void
    {
        // 1. Create a dummy Warehouse Sale
        $sale = WarehouseSale::create([
            'customer_id' => $this->warehouseCustomer->id,
            'warehouse_id' => $this->warehouse->id,
            'sale_date' => now(),
            'total_amount' => 500.00,
            'paid_amount' => 100.00,
            'due_amount' => 400.00,
        ]);

        // 2. Adjust total from 500.00 to 450.00
        $adjustment = $this->billingService->createAdjustment([
            'sale_type' => 'warehouse',
            'sale_id' => $sale->id,
            'new_amount' => 450.00,
            'reason' => 'discretionary_discount',
            'remarks' => 'Goodwill discount',
            'adjusted_by' => 1,
        ]);

        // 3. Assertions
        $this->assertEquals(-50.00, $adjustment->adjusted_amount);
        $this->assertEquals(450.00, $adjustment->new_amount);

        // Assert sale record updated
        $sale->refresh();
        $this->assertEquals(450.00, $sale->total_amount);
        $this->assertEquals(350.00, $sale->due_amount); // 450 - 100 paid

        // Assert audit trail created
        $this->assertDatabaseHas('billing_adjustments', [
            'id' => $adjustment->id,
            'sale_type' => 'warehouse',
            'sale_id' => $sale->id,
            'original_amount' => 500.00,
            'adjusted_amount' => -50.00,
            'new_amount' => 450.00,
        ]);
    }

    /**
     * Test shop sale billing adjustment.
     */
    public function test_shop_sale_billing_adjustment(): void
    {
        // 1. Create a dummy Shop Sale
        $sale = ShopSale::create([
            'customer_id' => $this->shopCustomer->id,
            'sale_date' => now(),
            'total_amount' => 200.00,
            'paid_amount' => 200.00,
            'due_amount' => 0.00,
        ]);

        // 2. Adjust total to 220.00 (under-billed correction)
        $adjustment = $this->billingService->createAdjustment([
            'sale_type' => 'shop',
            'sale_id' => $sale->id,
            'new_amount' => 220.00,
            'reason' => 'billing_error',
            'remarks' => 'Under-billed by 20 dollars',
            'adjusted_by' => 1,
        ]);

        // Assertions
        $this->assertEquals(20.00, $adjustment->adjusted_amount);
        
        $sale->refresh();
        $this->assertEquals(220.00, $sale->total_amount);
        $this->assertEquals(20.00, $sale->due_amount); // 220 - 200 paid
    }

    /**
     * Test fleet sale billing adjustment.
     */
    public function test_fleet_sale_billing_adjustment(): void
    {
        // 1. Create a dummy Fleet Sale
        $sale = FleetSale::create([
            'fleet_trip_id' => $this->trip->id,
            'bill_number' => 'FS-12345',
            'customer_name' => 'Fleet Customer A',
            'total_amount' => 1000.00,
        ]);

        // 2. Adjust total to 900.00
        $adjustment = $this->billingService->createAdjustment([
            'sale_type' => 'fleet',
            'sale_id' => $sale->id,
            'new_amount' => 900.00,
            'reason' => 'price_correction',
            'remarks' => 'Correction',
            'adjusted_by' => 1,
        ]);

        // Assertions
        $this->assertEquals(-100.00, $adjustment->adjusted_amount);

        $sale->refresh();
        $this->assertEquals(900.00, $sale->total_amount);
    }

    /**
     * Test negative amount throws exception.
     */
    public function test_negative_amount_throws_exception(): void
    {
        $this->expectException(ValidationException::class);

        $this->billingService->createAdjustment([
            'sale_type' => 'warehouse',
            'sale_id' => 1,
            'new_amount' => -50.00,
            'reason' => 'price_correction',
            'adjusted_by' => 1,
        ]);
    }

    /**
     * Test the polymorphic relation to the adjusted sale record.
     */
    public function test_billing_adjustment_polymorphic_relation(): void
    {
        $sale = WarehouseSale::create([
            'customer_id' => $this->warehouseCustomer->id,
            'warehouse_id' => $this->warehouse->id,
            'sale_date' => now(),
            'total_amount' => 500.00,
            'paid_amount' => 100.00,
            'due_amount' => 400.00,
        ]);

        $adjustment = $this->billingService->createAdjustment([
            'sale_type' => 'warehouse',
            'sale_id' => $sale->id,
            'new_amount' => 450.00,
            'reason' => 'discretionary_discount',
            'remarks' => 'Goodwill discount',
            'adjusted_by' => 1,
        ]);

        $this->assertNotNull($adjustment->sale);
        $this->assertInstanceOf(WarehouseSale::class, $adjustment->sale);
        $this->assertEquals($sale->id, $adjustment->sale->id);
        $this->assertEquals($adjustment->sale_object->id, $sale->id);
    }

    /**
     * Test multiple sequential adjustments to the same sale.
     */
    public function test_multiple_billing_adjustments_on_same_sale(): void
    {
        $sale = WarehouseSale::create([
            'customer_id' => $this->warehouseCustomer->id,
            'warehouse_id' => $this->warehouse->id,
            'sale_date' => now(),
            'total_amount' => 500.00,
            'paid_amount' => 100.00,
            'due_amount' => 400.00,
        ]);

        // First adjustment: 500 to 450
        $adj1 = $this->billingService->createAdjustment([
            'sale_type' => 'warehouse',
            'sale_id' => $sale->id,
            'new_amount' => 450.00,
            'reason' => 'price_correction',
            'remarks' => 'First adjustment',
            'adjusted_by' => 1,
        ]);

        $sale->refresh();
        $this->assertEquals(450.00, $sale->total_amount);
        $this->assertEquals(350.00, $sale->due_amount);

        // Second adjustment: 450 to 480
        $adj2 = $this->billingService->createAdjustment([
            'sale_type' => 'warehouse',
            'sale_id' => $sale->id,
            'new_amount' => 480.00,
            'reason' => 'billing_error',
            'remarks' => 'Second adjustment',
            'adjusted_by' => 1,
        ]);

        $sale->refresh();
        $this->assertEquals(480.00, $sale->total_amount);
        $this->assertEquals(380.00, $sale->due_amount);

        // Assert database has both adjustment logs
        $this->assertDatabaseHas('billing_adjustments', [
            'id' => $adj1->id,
            'original_amount' => 500.00,
            'adjusted_amount' => -50.00,
            'new_amount' => 450.00,
        ]);

        $this->assertDatabaseHas('billing_adjustments', [
            'id' => $adj2->id,
            'original_amount' => 450.00,
            'adjusted_amount' => 30.00,
            'new_amount' => 480.00,
        ]);
    }

    /**
     * Test billing adjustment with decimal values (e.g. 100.50).
     */
    public function test_billing_adjustment_with_decimal_values(): void
    {
        $sale = WarehouseSale::create([
            'customer_id' => $this->warehouseCustomer->id,
            'warehouse_id' => $this->warehouse->id,
            'sale_date' => now(),
            'total_amount' => 500.00,
            'paid_amount' => 100.50,
            'due_amount' => 399.50,
        ]);

        // Adjust to a decimal value: 450.75
        $adjustment = $this->billingService->createAdjustment([
            'sale_type' => 'warehouse',
            'sale_id' => $sale->id,
            'new_amount' => 450.75,
            'reason' => 'price_correction',
            'remarks' => 'Adjusted to decimal value',
            'adjusted_by' => 1,
        ]);

        $this->assertEquals(450.75, $adjustment->new_amount);
        $this->assertEquals(-49.25, $adjustment->adjusted_amount);

        $sale->refresh();
        $this->assertEquals(450.75, $sale->total_amount);
        $this->assertEquals(350.25, $sale->due_amount); // 450.75 - 100.50
    }

    /**
     * Test that adjusting a sale to zero (cancellation) sets due_amount to zero instead of a negative value.
     */
    public function test_billing_adjustment_to_zero_sets_due_amount_to_zero_instead_of_negative(): void
    {
        $sale = WarehouseSale::create([
            'customer_id' => $this->warehouseCustomer->id,
            'warehouse_id' => $this->warehouse->id,
            'sale_date' => now(),
            'total_amount' => 500.00,
            'paid_amount' => 150.00,
            'due_amount' => 350.00,
        ]);

        // Adjust to 0.00 (cancellation)
        $adjustment = $this->billingService->createAdjustment([
            'sale_type' => 'warehouse',
            'sale_id' => $sale->id,
            'new_amount' => 0.00,
            'reason' => 'voided_sale',
            'remarks' => 'Voided',
            'adjusted_by' => 1,
        ]);

        $this->assertEquals(0.00, $adjustment->new_amount);
        $this->assertEquals(-500.00, $adjustment->adjusted_amount);

        $sale->refresh();
        $this->assertEquals(0.00, $sale->total_amount);
        $this->assertEquals(0.00, $sale->due_amount); // Should be exactly 0.00, NOT -150.00
    }
}
