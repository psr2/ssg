<?php

namespace Modules\Locations\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Modules\Locations\Models\LocationModel;
use Tests\TestCase;


class LocationManagementTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Follow test_[action]_performs_[expected_result]
     */

    public function test_can_store_location(): void
    {
        $payload = [
            'location_name' => 'nagalapuram',
            'location_type' => 'warehouse',
            'location_address' => 'Nagalapuram Central Market,Theni , 652568,Tamilnadu',
            'location_abbreviation' => 'nm',


        ];

        $response = $this->postJson('/create-location', $payload);

        $response->assertStatus(201)

            ->assertJson([
                'success' => true,
                'message' => 'Location created successfully!',
            ]);

        $this->assertDatabaseHas(
            'locations',
            [
                'name' => 'nagalapuram',
                'type' => 'warehouse',
                'address' => 'Nagalapuram Central Market,Theni , 652568,Tamilnadu',
                'abbreviation' => 'nm',




            ]
        );
    }

    public function test_it_returns_location_data_for_valid_id()
    {
        $location = LocationModel::factory()->create([
            'name' => 'Test Location',
            'type' => 'shop',
            'address' => '123 Main Street',
        ]);

        $response = $this->postJson('/edit-location', [
            'id' => $location->id,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $location->id,
                    'name' => 'Test Location',
                    'type' => 'shop',
                    'address' => '123 Main Street',
                ],
            ]);
    }


    public function test_can_update_location_successfully()
    {
        // Create a dummy location
        $location = LocationModel::create([
            'name' => 'Old Name',
            'type' => 'shop',
            'address' => 'Old Address',
        ]);

        // New data to update
        $payload = [
            'id' => $location->id,
            'name' => 'New Name',
            'type' => 'warehouse',
            'address' => 'New Address',
        ];

        // Call update endpoint
        $response = $this->postJson('/update-location', $payload);

        // Assert success response
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Location updated successfully.'
            ]);

        // Confirm DB was updated
        $this->assertDatabaseHas('locations', [
            'id' => $location->id,
            'name' => 'New Name',
            'type' => 'warehouse',
            'address' => 'New Address',
        ]);
    }

    public function test_fails_on_update_with_invalid_data()
    {
        // Call update endpoint without required fields
        $response = $this->postJson('/update-location', [
            'id' => 9999, // non-existent id
            'name' => '',
            'type' => 'invalid_type',
            'address' => '',
        ]);

        $response->assertStatus(422); // Validation error
    }

      public function test_can_delete_location_successfully()
    {
        $location = LocationModel::create([
            'name' => 'Delete Me',
            'type' => 'shop',
            'address' => 'Some address',
        ]);

        $response = $this->deleteJson('/delete-location/' . $location->id);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Location deleted successfully.',
                 ]);

        $this->assertDatabaseMissing('locations', [
            'id' => $location->id,
        ]);
    }

    public function test_delete_fails_for_non_existent_location()
    {
        $response = $this->deleteJson('/delete-location/9999');

        $response->assertStatus(500)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Failed to delete location.',
                 ]);
     }

    public function test_can_render_locations_dashboard(): void
    {
        $location = LocationModel::factory()->create();

        $response = $this->get('/locations');

        $response->assertStatus(200);
        $response->assertViewIs('locations::locations_dashboard');
        $response->assertViewHas('data');
    }

    public function test_can_share_locations_via_internal_api(): void
    {
        $location = LocationModel::factory()->create([
            'name' => 'Internal API Location',
            'type' => 'warehouse',
            'address' => 'Some Address',
            'abbreviation' => 'ial',
        ]);

        $response = $this->getJson('/api-locations');

        $response->assertStatus(200);
        
        $data = json_decode($response->getContent(), true);
        $this->assertNotEmpty($data);
        
        $found = collect($data)->firstWhere('name', 'Internal API Location');
        $this->assertNotNull($found);
    }

    public function test_store_location_validation_fails_for_invalid_data(): void
    {
        // 1. Missing name
        $response = $this->postJson('/create-location', [
            'location_type' => 'warehouse',
            'location_abbreviation' => 'abc',
            'location_address' => 'Valid Address',
        ]);
        $response->assertStatus(422);

        // 2. Invalid name pattern (contains numbers/hyphens)
        $response = $this->postJson('/create-location', [
            'location_name' => 'invalid-name-123',
            'location_type' => 'warehouse',
            'location_abbreviation' => 'abc',
            'location_address' => 'Valid Address',
        ]);
        $response->assertStatus(422);

        // 3. Type is not alpha
        $response = $this->postJson('/create-location', [
            'location_name' => 'Valid Name',
            'location_type' => 'warehouse-123',
            'location_abbreviation' => 'abc',
            'location_address' => 'Valid Address',
        ]);
        $response->assertStatus(422);

        // 4. Abbreviation too long
        $response = $this->postJson('/create-location', [
            'location_name' => 'Valid Name',
            'location_type' => 'warehouse',
            'location_abbreviation' => 'toolongabbr',
            'location_address' => 'Valid Address',
        ]);
        $response->assertStatus(422);
    }

    public function test_edit_location_fails_for_invalid_id(): void
    {
        $response = $this->postJson('/edit-location', [
            'id' => 'not-numeric',
        ]);
        $response->assertStatus(422);
    }

    public function test_update_location_fails_for_non_existent_id(): void
    {
        $response = $this->postJson('/update-location', [
            'id' => 99999, // non-existent id
            'name' => 'Some Name',
            'type' => 'warehouse',
            'address' => 'Some Address',
        ]);
        $response->assertStatus(500)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Failed to update location.',
                 ]);
    }
}
