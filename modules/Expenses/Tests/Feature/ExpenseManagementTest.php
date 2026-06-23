<?php

namespace Modules\Expenses\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Modules\Expenses\Models\Expense;
use Modules\Expenses\Models\ExpenseCategory as Category;

class ExpenseManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_render_expenses_dashboard_when_empty(): void
    {
        // When there are no categories, ListExpenseCategory throws CategoryNotFoundException
        // but ExpenseUIController handles it gracefully and returns empty categories list.
        $response = $this->get('/expenses');

        $response->assertStatus(200);
        $response->assertViewIs('expense::expense');
        $response->assertViewHas('data', []);
        $response->assertViewHas('list');
    }

    public function test_can_render_expenses_dashboard_with_categories(): void
    {
        $category = Category::create([
            'name' => 'office supplies',
        ]);

        $expense = Expense::create([
            'expense_date' => '2026-06-22',
            'category_id' => $category->id,
            'amount' => 150.00,
            'payment_mode' => 'cash',
            'paid_to' => 'Local Stationery Store',
            'description' => 'Bought notebooks',
        ]);

        $response = $this->get('/expenses');

        $response->assertStatus(200);
        $response->assertViewIs('expense::expense');
        
        $viewData = $response->viewData('data');
        $this->assertNotEmpty($viewData);
        $this->assertEquals($category->id, $viewData['office supplies']);

        $viewList = $response->viewData('list');
        $this->assertCount(1, $viewList);
        $this->assertEquals($expense->id, $viewList->first()->id);
    }

    public function test_can_create_category(): void
    {
        $response = $this->postJson('/create-category', [
            'new_category' => 'Marketing',
        ]);

        // CreateExpenseCategory service returns an array: ['name' => ..., 'id' => ...]
        $response->assertStatus(200);
        $response->assertJson([
            'name' => 'marketing', // merged down and trimmed
        ]);

        $this->assertDatabaseHas('expense_categories', [
            'name' => 'marketing',
        ]);
    }

    public function test_create_category_fails_on_validation_errors(): void
    {
        // 1. Missing category name
        $response = $this->postJson('/create-category', []);
        $response->assertStatus(422);

        // 2. Duplicate category name
        Category::create(['name' => 'marketing']);
        $response = $this->postJson('/create-category', [
            'new_category' => 'Marketing',
        ]);
        $response->assertStatus(422);
    }

    public function test_can_record_expense(): void
    {
        $category = Category::create(['name' => 'travel']);

        $response = $this->postJson('/create-expense', [
            'expense_date' => '2026-06-22',
            'category_id' => $category->id,
            'amount' => 50.00,
            'payment_mode' => 'upi',
            'paid_to' => 'Taxi Driver',
            'description' => 'Ride to office',
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'status' => 'success',
                     'message' => 'Expense recorded successfully!',
                 ]);

        $this->assertDatabaseHas('expenses', [
            'category_id' => $category->id,
            'amount' => 50.00,
            'payment_mode' => 'upi',
            'paid_to' => 'Taxi Driver',
        ]);
    }

    public function test_record_expense_fails_on_validation_errors(): void
    {
        $category = Category::create(['name' => 'travel']);

        // 1. Missing required field (amount)
        $response = $this->postJson('/create-expense', [
            'expense_date' => '2026-06-22',
            'category_id' => $category->id,
            'payment_mode' => 'upi',
            'paid_to' => 'Taxi Driver',
            'description' => 'Ride to office',
        ]);
        $response->assertStatus(422);

        // 2. Invalid payment mode
        $response = $this->postJson('/create-expense', [
            'expense_date' => '2026-06-22',
            'category_id' => $category->id,
            'amount' => 50.00,
            'payment_mode' => 'bitcoin', // invalid
            'paid_to' => 'Taxi Driver',
            'description' => 'Ride to office',
        ]);
        $response->assertStatus(422);

        // 3. Invalid category ID
        $response = $this->postJson('/create-expense', [
            'expense_date' => '2026-06-22',
            'category_id' => 99999, // non-existent
            'amount' => 50.00,
            'payment_mode' => 'upi',
            'paid_to' => 'Taxi Driver',
            'description' => 'Ride to office',
        ]);
        $response->assertStatus(422);
    }

    public function test_can_update_expense(): void
    {
        $category = Category::create(['name' => 'travel']);
        $expense = Expense::create([
            'expense_date' => '2026-06-22',
            'category_id' => $category->id,
            'amount' => 50.00,
            'payment_mode' => 'upi',
            'paid_to' => 'Taxi Driver',
            'description' => 'Ride to office',
        ]);

        $response = $this->postJson('/update-expense', [
            'id' => $expense->id,
            'expense_date' => '2026-06-23',
            'category_id' => $category->id,
            'amount' => 75.00, // min 5 rupees required by validation
            'payment_mode' => 'cash',
            'paid_to' => 'Another Taxi Driver',
            'description' => 'Updated ride description',
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Expense updated successfully',
                 ]);

        $this->assertDatabaseHas('expenses', [
            'id' => $expense->id,
            'expense_date' => '2026-06-23 00:00:00',
            'amount' => 75.00,
            'payment_mode' => 'cash',
            'paid_to' => 'Another Taxi Driver',
        ]);
    }

    public function test_update_expense_fails_on_validation_errors(): void
    {
        $category = Category::create(['name' => 'travel']);
        $expense = Expense::create([
            'expense_date' => '2026-06-22',
            'category_id' => $category->id,
            'amount' => 50.00,
            'payment_mode' => 'upi',
            'paid_to' => 'Taxi Driver',
        ]);

        // Amount below min 5 rupees
        $response = $this->postJson('/update-expense', [
            'id' => $expense->id,
            'expense_date' => '2026-06-23',
            'category_id' => $category->id,
            'amount' => 3.00, // invalid (min: 5)
            'payment_mode' => 'cash',
            'paid_to' => 'Another Taxi Driver',
        ]);
        $response->assertStatus(422);
    }

    public function test_can_delete_expense(): void
    {
        $category = Category::create(['name' => 'travel']);
        $expense = Expense::create([
            'expense_date' => '2026-06-22',
            'category_id' => $category->id,
            'amount' => 50.00,
            'payment_mode' => 'upi',
            'paid_to' => 'Taxi Driver',
        ]);

        $response = $this->deleteJson("/delete-expense/{$expense->id}");

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Expense deleted successfully.',
                 ]);

        $this->assertDatabaseMissing('expenses', [
            'id' => $expense->id,
        ]);
    }

    public function test_delete_expense_returns_404_if_not_found(): void
    {
        $response = $this->deleteJson("/delete-expense/99999");

        $response->assertStatus(404)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Expense not found.',
                 ]);
    }
}
