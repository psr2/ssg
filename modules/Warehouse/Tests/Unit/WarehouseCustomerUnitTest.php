<?php

namespace Modules\Warehouse\Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Locations\Models\LocationModel as Location;
use Modules\Warehouse\Models\WarehouseCustomer;

class WarehouseCustomerUnitTest extends TestCase
{
    use RefreshDatabase;

    public function test_warehouse_customer_can_be_created_with_valid_attributes(): void
    {
        $warehouse = Location::create([
            'name' => 'Unit Test Warehouse',
            'type' => 'warehouse',
            'is_active' => true,
        ]);

        $customer = WarehouseCustomer::create([
            'warehouse_id' => $warehouse->id,
            'name' => 'Alice Smith',
            'phone' => '1234567890',
            'address' => '123 Main St',
            'location' => 'Zone A',
            'credit_balance' => 150.50,
        ]);

        $this->assertDatabaseHas('warehouse_customers', [
            'id' => $customer->id,
            'name' => 'Alice Smith',
            'warehouse_id' => $warehouse->id,
            'credit_balance' => 150.50,
        ]);

        // Test relationship
        $this->assertEquals($warehouse->id, $customer->warehouse->id);
        $this->assertEquals('Unit Test Warehouse', $customer->warehouse->name);
    }

    public function test_warehouse_customer_defaults(): void
    {
        $warehouse = Location::create([
            'name' => 'Unit Test Warehouse 2',
            'type' => 'warehouse',
            'is_active' => true,
        ]);

        $customer = WarehouseCustomer::create([
            'warehouse_id' => $warehouse->id,
            'name' => 'Bob Johnson',
        ]);

        $this->assertEquals(0.00, (float) $customer->credit_balance);
        $this->assertNull($customer->phone);
        $this->assertNull($customer->address);
        $this->assertNull($customer->location);
    }
}
