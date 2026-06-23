<?php

namespace Modules\Inventory\Tests\Feature\Products;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Modules\Inventory\Models\Products;
use Modules\Inventory\Models\UnitOfMeasurement as Unit;

class ProductCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_render_product_catalog_page(): void
    {
        $response = $this->get('/product/catalog');

        $response->assertStatus(200);
        $response->assertViewIs('inventory::product-catalog');
        $response->assertViewHas('units');
    }

    public function test_can_list_products(): void
    {
        $unit = Unit::factory()->create(['name' => 'Kilogram', 'abbreviation' => 'Kg']);
        $product = Products::factory()->create([
            'name' => 'Onion',
            'abbreviation' => 'ON',
            'sku' => 'SKU-ONION',
            'unit_id' => $unit->id,
            'category' => 'Vegetables',
            'description' => 'Red onions',
        ]);

        $response = $this->getJson('/api/products');

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'id' => $product->id,
            'name' => 'Onion',
            'abbreviation' => 'ON',
            'sku' => 'SKU-ONION',
            'unit_id' => $unit->id,
            'unit_name' => 'Kilogram',
            'category' => 'Vegetables',
            'description' => 'Red onions',
        ]);
    }

    public function test_can_store_product(): void
    {
        $unit = Unit::factory()->create();

        $response = $this->postJson('/api/products', [
            'name' => 'Potato',
            'abbreviation' => 'PO',
            'unit_id' => $unit->id,
            'category' => 'Vegetables',
            'description' => 'Fresh potato',
            'sku' => 'SKU-POTATO'
        ]);

        $response->assertStatus(201)
                 ->assertJson([
                     'message' => 'Product created successfully.'
                 ]);

        $this->assertDatabaseHas('products', [
            'name' => 'Potato',
            'abbreviation' => 'PO',
            'unit_id' => $unit->id,
            'sku' => 'SKU-POTATO'
        ]);
    }

    public function test_store_product_fails_on_validation_errors(): void
    {
        $unit = Unit::factory()->create();

        // 1. Missing required field (name)
        $response = $this->postJson('/api/products', [
            'abbreviation' => 'PO',
            'unit_id' => $unit->id,
        ]);
        $response->assertStatus(422);

        // 2. Non-existent unit_id
        $response = $this->postJson('/api/products', [
            'name' => 'Potato',
            'abbreviation' => 'PO',
            'unit_id' => 99999, // invalid id
        ]);
        $response->assertStatus(422);

        // 3. Duplicate name
        Products::factory()->create([
            'name' => 'Onion',
            'unit_id' => $unit->id,
        ]);
        $response = $this->postJson('/api/products', [
            'name' => 'Onion',
            'abbreviation' => 'ON',
            'unit_id' => $unit->id,
        ]);
        $response->assertStatus(422);
    }

    public function test_can_update_product(): void
    {
        $unit = Unit::factory()->create();
        $product = Products::factory()->create([
            'name' => 'Tomato',
            'abbreviation' => 'TO',
            'unit_id' => $unit->id,
            'sku' => 'SKU-TOMATO',
        ]);

        $response = $this->putJson("/api/products/{$product->id}", [
            'name' => 'Updated Tomato',
            'abbreviation' => 'UT',
            'unit_id' => $unit->id,
            'category' => 'Fruit-Vegetable',
            'description' => 'Juicy tomatoes',
            'sku' => 'SKU-TOMATO-UPDATED',
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'Product updated successfully.'
                 ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Updated Tomato',
            'abbreviation' => 'UT',
            'sku' => 'SKU-TOMATO-UPDATED',
        ]);
    }

    public function test_can_delete_product(): void
    {
        $product = Products::factory()->create();

        $response = $this->deleteJson("/api/products/{$product->id}");

        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'Product deleted successfully.'
                 ]);

        $this->assertDatabaseMissing('products', [
            'id' => $product->id,
        ]);
    }
}
