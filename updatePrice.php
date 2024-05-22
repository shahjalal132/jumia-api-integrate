<?php

// require config file
require_once 'config.php';

// require main file
require_once 'main.php';

try {
    // Fetch enabled or disabled status from the database
    $sql  = "SELECT control_value FROM controls WHERE control_key = 'price_update'";
    $stmt = $conn->prepare( $sql );
    $stmt->execute();
    $priceUpdateControl = $stmt->fetchColumn();
} catch (PDOException $e) {
    // Handle PDO exception
    echo "Error: " . $e->getMessage();
    exit; // Exit the script if an error occurs
}

// Conditionally update product prices based on the status
if ( $priceUpdateControl === 'price-enable' ) {
    $productSync = new ProductSync();
    $productSync->updateProductsPrice();
} else {
    echo "Price update is disabled. No action taken.";
}