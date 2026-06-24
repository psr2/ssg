# Proposal: Tracking Stock Segregation & Grading in Warehouse Inventory

## 1. Executive Summary
When raw/unsorted stock arrives at the warehouse, it is registered under a single incoming batch. It is then manually sorted (segregated) into distinct quality grades (e.g., Grade A, Grade B, and Rejects/Waste). 

Currently, the system has no built-in transaction type or data structure to log this sorting process. This document provides a concrete architectural design and implementation plan to track product segregation, maintain stock levels per segregation, and preserve supply chain traceability without violating system constraints.



---

## 2. Current System Analysis & Constraints

Through a review of the existing migrations and codebase, we identified several database constraints that dictate how segregation must be handled:

1. **`stock_purchase_items.batch` is Unique**:
   * Migrations enforce `$table->unique('batch');` on the purchase items table.
   * This means we cannot create multiple rows in `stock_purchase_items` with the exact same batch code but different grades.
2. **`stock_summary` Unique Key**:
   * Enforces `$table->unique(['product_id', 'location_id', 'batch_id'], 'uq_stock_summary');`.
   * Since `grade` is *not* part of this unique key constraint, we cannot store multiple grades under the same `batch_id` for a single product/location combination in the summary table.
3. **`warehouse_inventory.batch` Foreign Key**:
   * Enforces `$table->foreign('batch')->references('batch')->on('stock_purchase_items');`.
   * Any batch tracked in the warehouse inventory *must* exist as a row in the purchase items table.

### Conclusion for Batch Management:
To support segregation without breaking these constraints, **we must generate unique sub-batch codes** for the graded outputs. 
* *Example*: Original incoming batch `JE26-ON-AP-SDF-71` (Unsorted) gets segregated into:
  * `JE26-ON-AP-SDF-71-A` (Grade A)
  * `JE26-ON-AP-SDF-71-B` (Grade B)
  * `JE26-ON-AP-SDF-71-W` (Wastage/Reject)

---

## 3. Proposed Feature: "Stock Segregation" Module

We recommend adding a **Stock Segregation** screen under the **Stock Management** sidebar menu.

```
Stock Management
  ├── Stock In/Out
  ├── Internal Transfer
  ├── Stock Adjustment
  └── Stock Segregation  <-- NEW
```

### A. The User Interface (UI/UX Flow)
1. **Source Batch Selection**:
   * The user selects the **Warehouse** and searches for/selects an existing "Unsorted" or "Raw" batch using a batch autocomplete dropdown (similar to the batch selector in stock out).
   * The UI displays the selected batch's current product, current quantity, unit, and unit cost.
2. **Graded Output Grid**:
   * The user enters the quantity obtained for each grade:
     * **Grade A Qty**: `[ Input Box ]`
     * **Grade B Qty**: `[ Input Box ]`
     * **Reject/Wastage Qty**: `[ Input Box ]`
   * A dynamic validation label ensures that the sum of the output quantities does not exceed the source batch quantity.
3. **Remarks/Date**:
   * Input fields for `segregation_date` and `remarks`.

---

### B. Database Schema Changes

To maintain audit trails and parent-child relations, we introduce a segregation log table:

```php
Schema::create('stock_segregations', function (Blueprint $table) {
    $table->id();
    $table->string('reference_no')->unique(); // e.g. SEG-2026-00001
    $table->unsignedBigInteger('location_id'); // Warehouse where it happened
    $table->unsignedBigInteger('product_id');
    
    // Parent Batch Info
    $table->string('parent_batch_code');
    $table->decimal('parent_quantity', 12, 2);
    
    // Metadata
    $table->unsignedBigInteger('created_by');
    $table->text('remarks')->nullable();
    $table->timestamp('segregation_date');
    $table->timestamps();

    $table->foreign('location_id')->references('id')->on('locations');
    $table->foreign('product_id')->references('id')->on('products');
    $table->foreign('created_by')->references('id')->on('users');
});
```

We also log the individual output items:

```php
Schema::create('stock_segregation_items', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('stock_segregation_id');
    $table->string('child_batch_code'); // e.g. JE26-ON-AP-SDF-71-A
    $table->string('grade'); // A, B, Waste
    $table->decimal('quantity', 12, 2);
    $table->decimal('unit_cost', 12, 2);
    $table->string('unit');
    $table->timestamps();

    $table->foreign('stock_segregation_id')->references('id')->on('stock_segregations')->onDelete('cascade');
});
```

---

### C. Backend Execution & Invariant Enforcement

Upon submitting the segregation form, the backend executes the following within a **Database Transaction**:

1. **Lock & Verify Source Stock**:
   * Query the parent batch in `warehouse_inventory` with `lockForUpdate()`. Verify that the available quantity is greater than or equal to the total segregation quantity.
2. **Deduct Source Stock**:
   * Decrement the quantity of the parent batch in `warehouse_inventory` and `stock_summary` by the total segregated quantity. If the quantity reaches 0, the records can be deleted (or updated to 0).
3. **Register Child Batches as Valid Purchase Items**:
   * To satisfy the foreign key constraint on `warehouse_inventory`, the system inserts new rows into the `stock_purchase_items` table for each child batch (e.g. suffixing `-A`, `-B`, `-W`), copying over the original `stock_in_purchase_id` (so they remain linked to the original purchase order) and the `unit_cost`.
4. **Insert Child Batches into Inventory & Summary**:
   * Insert/upsert the child batches into `warehouse_inventory` and `stock_summary` under their respective grades and sub-batch codes.
5. **Log Segregation Header & Items**:
   * Save the records to `stock_segregations` and `stock_segregation_items` tables.

---

## 4. Lineage Tracking & Yield Analytics

By logging the parent-child relationships, we can provide valuable business reports:

### A. Sorting Yield Analytics
This report shows the sorting efficiency of incoming raw products.
* *Formula*: $\text{Grade A Yield \%} = \frac{\text{Grade A Qty}}{\text{Total Parent Qty}} \times 100$

**Example SQL Query**:
```sql
SELECT 
    p.name AS product_name,
    ss.parent_batch_code,
    ss.parent_quantity AS total_raw_weight,
    SUM(CASE WHEN ssi.grade = 'A' THEN ssi.quantity ELSE 0 END) AS grade_a_qty,
    SUM(CASE WHEN ssi.grade = 'B' THEN ssi.quantity ELSE 0 END) AS grade_b_qty,
    SUM(CASE WHEN ssi.grade = 'Waste' THEN ssi.quantity ELSE 0 END) AS waste_qty,
    (SUM(CASE WHEN ssi.grade = 'A' THEN ssi.quantity ELSE 0 END) / ss.parent_quantity) * 100 AS yield_grade_a_percent
FROM stock_segregations ss
JOIN stock_segregation_items ssi ON ss.id = ssi.stock_segregation_id
JOIN products p ON ss.product_id = p.id
GROUP BY ss.id, p.name, ss.parent_batch_code, ss.parent_quantity;
```

### B. Traceability Report
If a customer complains about quality issues with `JE26-ON-AP-SDF-71-A` (Grade A), the warehouse manager can search for this batch and instantly see:
* It was segregated from parent batch `JE26-ON-AP-SDF-71`.
* It came from **Vendor X** under **Purchase Invoice #INV-8834** on **2026-06-15**.
* This is crucial for supplier accountability and quality feedback loops.
