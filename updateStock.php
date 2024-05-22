<?php

// require config file
require_once 'config.php';

// require main file
require_once 'main.php';

// Fetch enabled or disabled status from the database
try {
    $sql  = "SELECT control_value FROM controls WHERE control_key = 'stock_update'";
    $stmt = $conn->prepare( $sql );
    $stmt->execute();
    $stockUpdateControl = $stmt->fetchColumn();
} catch (PDOException $e) {
    // Handle PDO exception
    echo "Error: " . $e->getMessage();
    exit; // Exit the script if an error occurs
}

// Conditionally update product stock based on the status
if ( $stockUpdateControl === 'stock-enable' ) {
    $productSync = new ProductSync();
    $productSync->updateProductsStock();
} else {
    echo "Stock update is disabled. No action taken.";
}
