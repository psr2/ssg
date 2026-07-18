<?php

namespace Modules\FleetManagement\Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\FleetManagement\Models\FleetRoutes;
use Modules\FleetManagement\Models\FleetCustomer;

class FleetCustomerUnitTest extends TestCase
{
    use RefreshDatabase;

    public function test_fleet_customer_can_be_created_with_valid_attributes(): void
    {
        $route = FleetRoutes::create([
            'name' => 'Route A',
            'description' => 'Test Route Description',
        ]);

        $customer = FleetCustomer::create([
            'customer_name' => 'Charlie Route',
            'customer_phone' => '1122334455',
            'route_id' => $route->id,
            'location' => 'Point B',
        ]);

        $this->assertDatabaseHas('fleet_customers', [
            'id' => $customer->id,
            'customer_name' => 'Charlie Route',
            'route_id' => $route->id,
            'location' => 'Point B',
        ]);

        // Test relationship
        $this->assertEquals($route->id, $customer->route->id);
        $this->assertEquals('Route A', $customer->route->name);
    }
}
