# Project Backlog & Enhancements

## Dynamic Product-Specific Grade System

### Goal
Replace the universal static grade values (`A`, `B`, `C` / `1`, `2`) with a dynamic, product-specific quality grade mapping. This will allow different types of items to have unique quality grades suited to their product type (e.g. size-based grades for some goods, ripeness/freshness scales for others).

### Required Steps

#### 1. Database & Migrations
- Create a `grades` table (e.g., `id`, `name`, `description`).
- Create a `product_grade` pivot table (e.g., `product_id`, `grade_id`) to define which grades are associated with each product.
- Update foreign key references in transaction tables:
  - `stock_purchase_items`
  - `warehouse_inventory`
  - `stock_transfer_items`
  - `stock_summary`
  - `warehouse_sale_items`

#### 2. Backend & CRUD Interfaces
- Add a CRUD interface in the **Product Catalogue / Inventory** module to manage quality grades.
- Add an interface on the Product edit/create forms to assign allowed grades to each product.

#### 3. Validation Logic Updates
- Refactor the Form Requests to perform dynamic database checks ensuring the selected grade is valid for the chosen product:
  - `PurchaseRequest`
  - `StockOutRequest`
  - `WarehouseSaleRequest`
  - `StockTransferRequest`

#### 4. Frontend & Dynamic AJAX Fields
- Modify the `stock_in_out.blade.php` and `warehouse_sale.blade.php` forms.
- Bind a Javascript `change` listener on the Product dropdown. When a product is selected, trigger an AJAX request (e.g. `/api/products/{id}/grades`) to load and populate only the specific grades mapped to that product.
