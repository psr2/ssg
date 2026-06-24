import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/style.css',

                // Core App Assets
                'resources/js/app.js',
                'resources/js/common.js',
                'resources/js/product.js',
                'resources/js/stock_in.js',
                'resources/js/stock_out.js',
                'resources/js/stock_return.js',
                'resources/js/unit.js',

                // Warehouse Module
                'resources/js/warehouse/warehouse_sale.js',
                'resources/js/warehouse/warehouse_customer_search.js',
                'resources/js/warehouse/warehouse_credits.js',
                'resources/js/warehouse/warehouse_overview.js',

                // Inventory Module
                'resources/js/inventory/product.js',
                'resources/js/inventory/unit.js',

                // Shop Module
                'resources/js/shop/shop_sale.js',
                'resources/js/shop/customer_search.js',

                // Fleet Module
                'resources/js/fleet/fleet_sale.js',
                'resources/js/fleet/upload_report.js',
                'resources/js/fleet/loadProductsAndUnits.js',
                'resources/js/fleet/customer_search.js',
                'resources/js/fleet/update_fleet_payments.js',
                'resources/js/fleet/fleet_trip.js',
                'resources/js/fleet/fleet_vehicle.js',
                'resources/js/fleet/credit_report.js',
                'resources/js/fleet/fleet_route.js',

                // Stock Management Module
                'resources/js/stock-management/stock_transfer.js',
                'resources/js/stock-management/batch-id.js',
                'resources/js/stock-management/stock_adjustment.js',
                'resources/js/stock-management/stock_segregation.js',

                // Expenses Module
                'resources/js/expenses/expense.js',
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
});
