<?php

$files = [
    '/var/www/html/clients/inv/tests/Workflow/ShopInventoryReceivingTest.php',
    '/var/www/html/clients/inv/tests/Workflow/WarehouseDirectStockOutTest.php',
    '/var/www/html/clients/inv/tests/Workflow/WarehouseGradeMismatchStockOutTest.php',
    '/var/www/html/clients/inv/tests/Workflow/WarehouseInventoryReceivingTest.php',
    '/var/www/html/clients/inv/tests/Workflow/WarehouseInventorySaleTest.php',
    '/var/www/html/clients/inv/tests/Workflow/WarehouseReverseTransferStockOutTest.php',
    '/var/www/html/clients/inv/tests/Workflow/WarehouseReverseTransferTest.php',
    '/var/www/html/clients/inv/tests/Workflow/WarehouseStockOutOverdrawTest.php',
    '/var/www/html/clients/inv/tests/Workflow/WarehouseToWarehouseTransferTest.php',
    '/var/www/html/clients/inv/tests/Workflow/WarehouseTransferStockOutTest.php',
    '/var/www/html/clients/inv/tests/Workflow/WarehouseUnitMatchingWorkflowTest.php'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        if (unlink($file)) {
            echo "Deleted: " . basename($file) . "\n";
        } else {
            echo "Failed to delete: " . basename($file) . "\n";
        }
    }
}
