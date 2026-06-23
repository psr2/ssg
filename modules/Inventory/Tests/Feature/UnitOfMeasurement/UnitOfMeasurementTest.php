<?php

namespace Modules\Inventory\Tests\Feature\UnitOfMeasurement;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

use Modules\Inventory\Models\UnitOfMeasurement as Unit;

class UnitOfMeasurementTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_render_units_dashboard(): void
    {
        $response = $this->get('/units');

        $response->assertStatus(200);
        $response->assertViewIs('inventory::Units.units');
    }

    public function test_can_list_units_of_measurement(): void
    {
        $unit = Unit::factory()->create([
            'name' => 'Gram',
            'abbreviation' => 'Gm',
        ]);

        $response = $this->getJson('/api/units');

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'id' => $unit->id,
            'name' => 'Gram',
            'abbreviation' => 'Gm',
        ]);
    }

    public function test_can_store_units_of_measurement(): void
    {
        $response = $this->postJson(
            '/api/units',
            [
                'name' => 'Kilogram',
                'abbreviation' => 'kg'
            ]
        );

        $response->assertStatus(201)
                 ->assertJson([
                     'message' => 'Unit created successfully.'
                 ]);

        $this->assertDatabaseHas(
            'units',
            [
                'name' => 'Kilogram',
                'abbreviation' => 'kg'
            ]
        );
    }

    public function test_store_unit_fails_on_validation_errors(): void
    {
        // 1. Missing name
        $response = $this->postJson('/api/units', [
            'abbreviation' => 'kg'
        ]);
        $response->assertStatus(422);

        // 2. Duplicate name
        Unit::factory()->create(['name' => 'Kilogram']);
        $response = $this->postJson('/api/units', [
            'name' => 'Kilogram',
            'abbreviation' => 'kg'
        ]);
        $response->assertStatus(422);
    }

    public function test_can_update_units_of_measurement(): void
    {
        $unit = Unit::factory()->create([
            'name' => 'old_name',
            'abbreviation' => 'old_abbr',
        ]);

        $response = $this->putJson(
            "/api/units/{$unit->id}",
            [
                'name' => 'new_name',
                'abbreviation' => 'new_abbr',
            ]
        );

        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'Unit updated successfully.'
                 ]);

        $this->assertDatabaseHas(
            'units',
            [
                'id' => $unit->id,
                'name' => 'new_name',
                'abbreviation' => 'new_abbr',
            ]
        );
    }

    public function test_can_delete_units_of_measurement(): void
    {
        $unit = Unit::factory()->create();

        $response = $this->deleteJson("/api/units/{$unit->id}");

        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'Unit deleted successfully.'
                 ]);

        $this->assertDatabaseMissing(
            'units',
            [
                'id' => $unit->id,
            ]
        );
    }
}
