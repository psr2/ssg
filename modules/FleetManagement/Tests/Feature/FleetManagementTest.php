<?php

namespace Modules\FleetManagement\Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\FleetManagement\Models\FleetRoutes;
use Modules\FleetManagement\Models\FleetVehicle;

class FleetManagementTest extends TestCase
{
    use RefreshDatabase;

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
}
