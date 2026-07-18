<?php

namespace Modules\ShopManagement\Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Locations\Models\LocationModel as Location;
use Modules\ShopManagement\Models\ShopCustomer;

class ShopCustomerUnitTest extends TestCase
{
    use RefreshDatabase;

    public function test_shop_customer_can_be_created_with_valid_attributes(): void
    {
        $shop = Location::create([
            'name' => 'Unit Test Shop',
            'type' => 'shop',
            'is_active' => true,
        ]);

        $customer = ShopCustomer::create([
            'shop_id' => $shop->id,
            'name' => 'John Doe',
            'phone' => '0987654321',
            'address' => '456 Side St',
            'credit_balance' => 200.00,
        ]);

        $this->assertDatabaseHas('shop_customers', [
            'id' => $customer->id,
            'name' => 'John Doe',
            'shop_id' => $shop->id,
            'credit_balance' => 200.00,
        ]);

        // Test relationship
        $this->assertEquals($shop->id, $customer->shop->id);
        $this->assertEquals('Unit Test Shop', $customer->shop->name);
    }

    public function test_shop_customer_defaults(): void
    {
        $shop = Location::create([
            'name' => 'Unit Test Shop 2',
            'type' => 'shop',
            'is_active' => true,
        ]);

        $customer = ShopCustomer::create([
            'shop_id' => $shop->id,
            'name' => 'Jane Smith',
        ]);

        $this->assertEquals(0.00, (float) $customer->credit_balance);
        $this->assertNull($customer->phone);
        $this->assertNull($customer->address);
    }
}
