<?php

namespace Modules\FleetManagement\Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\FleetManagement\Models\FleetRoutes;
use Modules\FleetManagement\Models\FleetVehicle;

class FleetManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        if (\DB::connection() instanceof \Illuminate\Database\SQLiteConnection) {
            \DB::statement('PRAGMA foreign_keys = OFF;');
        }
    }

    /**
     * Test that FleetRouteSeeder seeds the required routes correctly.
     */
    public function test_route_seeder_seeds_correct_routes(): void
    {
        $this->seed(\Modules\FleetManagement\Database\Seeders\FleetRouteSeeder::class);

        $this->assertDatabaseHas('fleet_routes', ['name' => 'Pooppara']);
        $this->assertDatabaseHas('fleet_routes', ['name' => 'kattapana']);
        $this->assertDatabaseHas('fleet_routes', ['name' => 'Mankulam']);
        $this->assertDatabaseHas('fleet_routes', ['name' => 'Kottayam']);
        $this->assertDatabaseHas('fleet_routes', ['name' => 'City Center']);
    }

    /**
     * Test that FleetVehicleSeeder seeds the vehicles correctly.
     */
    public function test_vehicle_seeder_seeds_correct_vehicles(): void
    {
        $this->seed(\Modules\FleetManagement\Database\Seeders\FleetVehicleSeeder::class);

        $this->assertDatabaseHas('fleet_vehicles', ['registration_number' => 'KL-06-A-1234']);
        $this->assertDatabaseHas('fleet_vehicles', ['registration_number' => 'KL-06-B-5678']);
        $this->assertDatabaseHas('fleet_vehicles', ['registration_number' => 'KL-06-C-9012']);
        $this->assertDatabaseHas('fleet_vehicles', ['registration_number' => 'KL-06-D-3456']);
    }

    /**
     * Test creating a fleet route.
     */
    public function test_can_create_route(): void
    {
        $payload = [
            'name' => 'Pooppara Route',
            'description' => 'Route connecting Pooppara and surrounding areas',
        ];

        $response = $this->postJson('/api/fleet-routes', $payload);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'Pooppara Route',
                    'description' => 'Route connecting Pooppara and surrounding areas',
                ]
            ]);

        $this->assertDatabaseHas('fleet_routes', [
            'name' => 'Pooppara Route',
            'description' => 'Route connecting Pooppara and surrounding areas',
        ]);
    }

    /**
     * Test listing fleet routes.
     */
    public function test_can_list_routes(): void
    {
        $route = FleetRoutes::create([
            'name' => 'Kottayam Route',
            'description' => 'Route to Kottayam Office',
        ]);

        $response = $this->getJson('/api/fleet-routes');

        $response->assertStatus(200)
            ->assertJsonFragment([
                'name' => 'Kottayam Route',
                'description' => 'Route to Kottayam Office',
            ]);
    }

    /**
     * Test updating a fleet route.
     */
    public function test_can_update_route(): void
    {
        $route = FleetRoutes::create([
            'name' => 'Mankulam Route',
            'description' => 'Old Description',
        ]);

        $payload = [
            'name' => 'Mankulam Route Updated',
            'description' => 'New Description',
        ];

        $response = $this->putJson("/api/fleet-routes/{$route->id}", $payload);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $route->id,
                    'name' => 'Mankulam Route Updated',
                    'description' => 'New Description',
                ]
            ]);

        $this->assertDatabaseHas('fleet_routes', [
            'id' => $route->id,
            'name' => 'Mankulam Route Updated',
            'description' => 'New Description',
        ]);
    }

    /**
     * Test deleting a fleet route.
     */
    public function test_can_delete_route(): void
    {
        $route = FleetRoutes::create([
            'name' => 'Kattappana Route',
            'description' => 'Temporary Route',
        ]);

        $response = $this->deleteJson("/api/fleet-routes/{$route->id}");

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertSoftDeleted('fleet_routes', [
            'id' => $route->id,
        ]);
    }

    /**
     * Test creating a fleet vehicle.
     */
    public function test_can_create_vehicle(): void
    {
        $payload = [
            'registration_number' => 'KL-06-E-9999',
            'model' => 'Tata LPT 1612',
            'type' => 'Truck',
            'capacity' => 6000,
            'notes' => 'Heavy vehicle',
        ];

        $response = $this->postJson('/fleet/vehicles', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'registration_number' => 'KL-06-E-9999',
                'model' => 'Tata LPT 1612',
                'type' => 'Truck',
                'capacity' => 6000,
                'notes' => 'Heavy vehicle',
            ]);

        $this->assertDatabaseHas('fleet_vehicles', [
            'registration_number' => 'KL-06-E-9999',
        ]);
    }

    /**
     * Test listing vehicles.
     */
    public function test_can_list_vehicles(): void
    {
        $vehicle = FleetVehicle::create([
            'registration_number' => 'KL-06-Z-1111',
            'model' => 'Mahindra Supro',
            'type' => 'Van',
            'capacity' => 1000,
        ]);

        $response = $this->getJson('/fleet/vehicles');

        $response->assertStatus(200)
            ->assertJsonFragment([
                'registration_number' => 'KL-06-Z-1111',
                'model' => 'Mahindra Supro',
            ]);
    }

    /**
     * Test updating a fleet vehicle.
     */
    public function test_can_update_vehicle(): void
    {
        $vehicle = FleetVehicle::create([
            'registration_number' => 'KL-06-Y-2222',
            'model' => 'Eicher Pro',
            'type' => 'Truck',
            'capacity' => 4000,
        ]);

        $payload = [
            'registration_number' => 'KL-06-Y-2222',
            'model' => 'Eicher Pro 3015',
            'type' => 'Truck',
            'capacity' => 5000,
            'notes' => 'Updated capacity',
        ];

        $response = $this->putJson("/fleet/vehicles/{$vehicle->id}", $payload);

        $response->assertStatus(200)
            ->assertJson([
                'id' => $vehicle->id,
                'model' => 'Eicher Pro 3015',
                'capacity' => 5000,
                'notes' => 'Updated capacity',
            ]);

        $this->assertDatabaseHas('fleet_vehicles', [
            'id' => $vehicle->id,
            'model' => 'Eicher Pro 3015',
            'capacity' => 5000,
        ]);
    }

    /**
     * Test deleting a fleet vehicle.
     */
    public function test_can_delete_vehicle(): void
    {
        $vehicle = FleetVehicle::create([
            'registration_number' => 'KL-06-X-3333',
            'model' => 'Maruti Super Carry',
            'type' => 'Pickup',
            'capacity' => 740,
        ]);

        $response = $this->deleteJson("/fleet/vehicles/{$vehicle->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Vehicle deleted successfully']);

        $this->assertDatabaseMissing('fleet_vehicles', [
            'id' => $vehicle->id,
        ]);
    }

    /**
     * Test trip creation input validation.
     */
    public function test_trip_creation_validation_rules(): void
    {
        $unit = \Modules\Inventory\Models\UnitOfMeasurement::factory()->create(['name' => 'Kilogram', 'abbreviation' => 'Kg']);
        $product = \Modules\Inventory\Models\Products::factory()->create([
            'name' => 'Tomato',
            'abbreviation' => 'TM',
            'unit_id' => $unit->id
        ]);
        $location = \Modules\Locations\Models\LocationModel::factory()->create([
            'type' => 'warehouse',
            'abbreviation' => 'WH'
        ]);
        $grade = \Modules\Inventory\Models\ProductGrade::factory()->create([
            'name' => 'Grade A',
            'code' => 'GA',
            'is_active' => true
        ]);
        $vehicle = \Modules\FleetManagement\Models\FleetVehicle::create([
            'registration_number' => 'KL-06-A-9999',
            'model' => 'Eicher Pro',
            'type' => 'Truck',
            'capacity' => 5000
        ]);
        $route = \Modules\FleetManagement\Models\FleetRoutes::create([
            'name' => 'Route A',
            'description' => 'Test Route'
        ]);

        // Insert foreign key dependencies for MySQL / SQLite constraints
        $masterId = \DB::table('master_stock_in')->insertGetId([
            'reference_number' => 'REF-VAL-' . uniqid(),
            'stock_movement_type' => 'in',
            'stock_in_type' => 'purchase',
            'stock_in_date' => now()->format('Y-m-d'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $purchaseId = \DB::table('stock_purchase')->insertGetId([
            'master_stock_in_id' => $masterId,
            'vendor' => 'Test Vendor',
            'invoice_number' => 'INV-VAL-' . uniqid(),
            'purchase_date' => now()->format('Y-m-d'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        \DB::table('stock_purchase_items')->insert([
            'stock_in_purchase_id' => $purchaseId,
            'location_id' => $location->id,
            'product' => $product->id,
            'batch' => 'BATCH-VAL-123',
            'grade' => $grade->name,
            'quantity' => 100.00,
            'unit' => 'Kg',
            'unit_cost' => 10.00,
            'total' => 1000.00,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Place batch in warehouse cache and ledger
        \DB::table('warehouse_inventory')->insert([
            'warehouse_id' => $location->id,
            'product_id'   => $product->id,
            'batch'        => 'BATCH-VAL-123',
            'grade'        => $grade->name,
            'qty'          => 100.00,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        \DB::table('stock_ledger_entries')->insert([
            'location_id'      => $location->id,
            'product_id'       => $product->id,
            'batch_code'       => 'BATCH-VAL-123',
            'grade'            => $grade->name,
            'quantity'         => 100.00,
            'unit'             => 'Kg',
            'transaction_type' => 'in',
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        // Case 1: Decimal quantity (should fail base integer validation)
        $payloadDecimal = [
            'route_id'   => $route->id,
            'vehicle_id' => $vehicle->id,
            'start_date' => now()->format('Y-m-d'),
            'tag'        => 'Test Tag',
            'sent' => [
                [
                    'product_id'  => $product->id,
                    'batch'       => 'BATCH-VAL-123',
                    'grade'       => $grade->name,
                    'unit'        => 'Kg',
                    'quantity'    => 10.5,
                    'location_id' => $location->id
                ]
            ]
        ];

        $responseDecimal = $this->postJson('/create-trip', $payloadDecimal);
        $responseDecimal->assertStatus(422)
            ->assertJsonValidationErrors(['sent.0.quantity']);

        // Case 2: Missing route_id
        $payloadMissingRoute = $payloadDecimal;
        unset($payloadMissingRoute['route_id']);
        $payloadMissingRoute['sent'][0]['quantity'] = 10; // valid integer

        $responseRoute = $this->postJson('/create-trip', $payloadMissingRoute);
        $responseRoute->assertStatus(422)
            ->assertJsonValidationErrors(['route_id']);
    }

    /**
     * Test successful trip creation records dispatch ledger entries and reduces available stock.
     */
    public function test_can_create_trip_successfully_and_reduces_stock(): void
    {
        $unit = \Modules\Inventory\Models\UnitOfMeasurement::factory()->create(['name' => 'Kilogram', 'abbreviation' => 'Kg']);
        $product = \Modules\Inventory\Models\Products::factory()->create([
            'name' => 'Tomato',
            'abbreviation' => 'TM',
            'unit_id' => $unit->id
        ]);
        $location = \Modules\Locations\Models\LocationModel::factory()->create([
            'type' => 'warehouse',
            'abbreviation' => 'WH'
        ]);
        $grade = \Modules\Inventory\Models\ProductGrade::factory()->create([
            'name' => 'Grade A',
            'code' => 'GA',
            'is_active' => true
        ]);
        $vehicle = \Modules\FleetManagement\Models\FleetVehicle::create([
            'registration_number' => 'KL-06-A-9999',
            'model' => 'Eicher Pro',
            'type' => 'Truck',
            'capacity' => 5000
        ]);
        $route = \Modules\FleetManagement\Models\FleetRoutes::create([
            'name' => 'Route A',
            'description' => 'Test Route'
        ]);

        // Insert foreign key dependencies for MySQL / SQLite constraints
        $masterId = \DB::table('master_stock_in')->insertGetId([
            'reference_number' => 'REF-SUCCESS-' . uniqid(),
            'stock_movement_type' => 'in',
            'stock_in_type' => 'purchase',
            'stock_in_date' => now()->format('Y-m-d'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $purchaseId = \DB::table('stock_purchase')->insertGetId([
            'master_stock_in_id' => $masterId,
            'vendor' => 'Test Vendor',
            'invoice_number' => 'INV-SUCCESS-' . uniqid(),
            'purchase_date' => now()->format('Y-m-d'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        \DB::table('stock_purchase_items')->insert([
            'stock_in_purchase_id' => $purchaseId,
            'location_id' => $location->id,
            'product' => $product->id,
            'batch' => 'BATCH-SUCCESS-123',
            'grade' => $grade->name,
            'quantity' => 100.00,
            'unit' => 'Kg',
            'unit_cost' => 10.00,
            'total' => 1000.00,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Place batch in warehouse cache for validator checks
        \DB::table('warehouse_inventory')->insert([
            'warehouse_id' => $location->id,
            'product_id'   => $product->id,
            'batch'        => 'BATCH-SUCCESS-123',
            'grade'        => $grade->name,
            'qty'          => 100.00,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        // Put initial stock in ledger
        $ledgerService = app(\Modules\StockLedger\Services\StockLedgerService::class);
        $ledgerService->recordEntry([
            'transaction_type' => 'PURCHASE',
            'location_id'      => $location->id,
            'product_id'       => $product->id,
            'batch_code'       => 'BATCH-SUCCESS-123',
            'grade'            => $grade->name,
            'quantity'         => 100.00,
            'unit'             => 'Kg',
            'unit_cost'        => 10.00,
            'remarks'          => 'Initial stock'
        ]);

        // Assert we have 100 Kg available before dispatch
        $this->assertEquals(100.00, $ledgerService->getAvailableStock($location->id, $product->id, 'BATCH-SUCCESS-123', $grade->name));

        $payload = [
            'route_id'   => $route->id,
            'vehicle_id' => $vehicle->id,
            'start_date' => now()->format('Y-m-d'),
            'tag'        => 'Test Tag',
            'sent' => [
                [
                    'product_id' => $product->id,
                    'batch'      => 'BATCH-SUCCESS-123',
                    'grade'      => $grade->name,
                    'unit'       => 'Kg',
                    'quantity'   => 40, // dispatch 40 Kg
                    'location_id' => $location->id
                ]
            ]
        ];

        $response = $this->postJson('/create-trip', $payload);

        $response->assertStatus(200);

        // Assert available stock has been reduced by 40 Kg, leaving 60 Kg
        $this->assertEquals(60.00, $ledgerService->getAvailableStock($location->id, $product->id, 'BATCH-SUCCESS-123', $grade->name));

        // Assert we have a DISPATCH record in stock ledger entries
        $this->assertDatabaseHas('stock_ledger_entries', [
            'location_id'      => $location->id,
            'product_id'       => $product->id,
            'batch_code'       => 'BATCH-SUCCESS-123',
            'transaction_type' => 'DISPATCH',
            'quantity'         => -40.00
        ]);
    }

    /**
     * Test cancelling a fleet trip with sales restores stock and zeroes sale totals.
     */
    public function test_cancelling_trip_with_sales_restores_stock_and_zeroes_financials(): void
    {
        $route = FleetRoutes::create(['name' => 'Cancel Test Route']);
        $vehicle = FleetVehicle::create(['registration_number' => 'KL-06-CANCEL-1']);
        $unit = \Modules\Inventory\Models\UnitOfMeasurement::firstOrCreate(['name' => 'Kg'], ['abbreviation' => 'Kg']);
        $location = \Modules\Locations\Models\LocationModel::factory()->create(['type' => 'warehouse', 'abbreviation' => 'WCA']);
        $product = \Modules\Inventory\Models\Products::firstOrCreate(['sku' => 'PROD-CANCEL'], ['name' => 'Cancel Product', 'abbreviation' => 'CP', 'unit_id' => $unit->id]);
        $grade = \Modules\Inventory\Models\ProductGrade::factory()->create(['name' => 'Grade Cancel', 'code' => 'GC', 'is_active' => true]);

        $masterId = \DB::table('master_stock_in')->insertGetId([
            'reference_number' => 'REF-CANCEL-' . uniqid(),
            'stock_movement_type' => 'in',
            'stock_in_type' => 'purchase',
            'stock_in_date' => now()->format('Y-m-d'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $purchaseId = \DB::table('stock_purchase')->insertGetId([
            'master_stock_in_id' => $masterId,
            'vendor' => 'Test Vendor',
            'invoice_number' => 'INV-CANCEL-' . uniqid(),
            'purchase_date' => now()->format('Y-m-d'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        \DB::table('stock_purchase_items')->insert([
            'stock_in_purchase_id' => $purchaseId,
            'location_id' => $location->id,
            'product' => $product->id,
            'batch' => 'BATCH-CANCEL-999',
            'grade' => $grade->name,
            'quantity' => 80.00,
            'unit' => 'Kg',
            'unit_cost' => 15.00,
            'total' => 1200.00,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        \DB::table('warehouse_inventory')->insert([
            'warehouse_id' => $location->id,
            'product_id'   => $product->id,
            'batch'        => 'BATCH-CANCEL-999',
            'grade'        => $grade->name,
            'qty'          => 80.00,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        $ledgerService = app(\Modules\StockLedger\Services\StockLedgerService::class);
        $ledgerService->recordEntry([
            'transaction_type' => 'PURCHASE',
            'location_id'      => $location->id,
            'product_id'       => $product->id,
            'batch_code'       => 'BATCH-CANCEL-999',
            'grade'            => $grade->name,
            'quantity'         => 80.00,
            'unit'             => 'Kg',
            'unit_cost'        => 15.00,
            'remarks'          => 'Stock for trip cancel test'
        ]);

        $dispatchResp = $this->postJson('/create-trip', [
            'route_id'   => $route->id,
            'vehicle_id' => $vehicle->id,
            'start_date' => now()->format('Y-m-d'),
            'tag'        => 'Cancel Test Trip',
            'sent'       => [
                [
                    'product_id'  => $product->id,
                    'batch'       => 'BATCH-CANCEL-999',
                    'grade'       => $grade->name,
                    'unit'        => 'Kg',
                    'quantity'    => 50,
                    'location_id' => $location->id
                ]
            ]
        ]);
        $dispatchResp->assertStatus(200);
        $tripId = \Modules\FleetManagement\Models\FleetTrip::latest('id')->first()->id;

        // Verify stock in warehouse was reduced to 30 Kg
        $this->assertEquals(30.00, $ledgerService->getAvailableStock($location->id, $product->id, 'BATCH-CANCEL-999', $grade->name));

        // Create a fleet sale
        $saleResp = $this->postJson('/fleet/sale/store', [
            'trip_id'        => $tripId,
            'customer_name'  => 'Cancel Customer',
            'bill_no'        => 'BC01',
            'payment_status' => 'paid',
            'amount_paid'    => 500,
            'payment_date'   => now()->format('Y-m-d'),
            'payment_mode'   => 'cash',
            'items'          => [
                [
                    'product'     => $product->name,
                    'grade'       => $grade->name,
                    'quantity'    => 20,
                    'unit'        => 'Kg',
                    'unit_price'  => 25,
                    'total_price' => 500
                ]
            ]
        ]);
        $saleResp->assertStatus(200);

        // Cancel the trip via DELETE endpoint
        $cancelResp = $this->deleteJson("/fleet-trips/{$tripId}");
        $cancelResp->assertStatus(200)->assertJson(['success' => true]);

        // Assert trip status updated to 'cancelled'
        $this->assertDatabaseHas('fleet_trips', [
            'id' => $tripId,
            'status' => 'cancelled'
        ]);

        // Assert stock restored back to 80 Kg
        $this->assertEquals(80.00, $ledgerService->getAvailableStock($location->id, $product->id, 'BATCH-CANCEL-999', $grade->name));

        // Assert sale and sale item total/quantity zeroed out
        $this->assertDatabaseHas('fleet_sales', [
            'fleet_trip_id' => $tripId,
            'total_amount'  => 0.00
        ]);
    }
}
