<?php

namespace Modules\Inventory\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Modules\Inventory\Models\ProductGrade;

class ProductGradeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test rendering the grades dashboard.
     */
    public function test_can_render_grades_dashboard(): void
    {
        $response = $this->get('/grades');

        $response->assertStatus(200);
        $response->assertViewIs('inventory::grades');
        $response->assertViewHas('grades');
    }

    /**
     * Test storing a product grade successfully.
     */
    public function test_can_store_product_grade(): void
    {
        $payload = [
            'name' => 'Premium Grade A',
            'code' => 'PGA',
            'description' => 'Top tier premium grade product',
        ];

        $response = $this->post('/grades', $payload);

        $response->assertRedirect();
        
        $this->assertDatabaseHas('product_grades', [
            'name' => 'Premium Grade A',
            'code' => 'PGA',
            'description' => 'Top tier premium grade product',
            'is_active' => true,
        ]);
    }

    /**
     * Test storing validation failures.
     */
    public function test_store_grade_validation_fails_on_empty_and_duplicate_values(): void
    {
        // 1. Missing name & code
        $response = $this->post('/grades', [
            'description' => 'Just description'
        ]);
        $response->assertSessionHasErrors(['name', 'code']);

        // 2. Duplicate Name
        ProductGrade::factory()->create([
            'name' => 'Duplicate Name',
            'code' => 'DUP1'
        ]);

        $response = $this->post('/grades', [
            'name' => 'Duplicate Name',
            'code' => 'DUP2',
        ]);
        $response->assertSessionHasErrors(['name']);

        // 3. Duplicate Code
        $response = $this->post('/grades', [
            'name' => 'Unique Name',
            'code' => 'DUP1',
        ]);
        $response->assertSessionHasErrors(['code']);
    }

    /**
     * Test deleting a product grade successfully.
     */
    public function test_can_delete_product_grade(): void
    {
        $grade = ProductGrade::factory()->create([
            'name' => 'To Delete',
            'code' => 'DEL',
        ]);

        $response = $this->delete("/grades/{$grade->id}");

        $response->assertRedirect();

        $this->assertDatabaseMissing('product_grades', [
            'id' => $grade->id,
        ]);
    }

    /**
     * Test deleting non-existent grade fails.
     */
    public function test_delete_fails_for_non_existent_grade(): void
    {
        $response = $this->delete('/grades/99999');
        $response->assertStatus(404);
    }
}
