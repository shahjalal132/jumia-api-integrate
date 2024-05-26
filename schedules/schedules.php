<?php

// Call updateStock.php every minute
$updateStockUrl = 'https://marabandbros.com/updateStock.php';
file_get_contents( $updateStockUrl );

sleep( 3 );

// Call updatePrice.php every 80 seconds
$updatePriceUrl = 'https://marabandbros.com/updatePrice.php';
file_get_contents( $updatePriceUrl );

sleep( 3 );

// Call salePriceUpdate.php every 100 seconds
$salePriceUpdateUrl = 'https://marabandbros.com/salePriceUpdate.php';
file_get_contents( $salePriceUpdateUrl );

echo "Schedule Run Successfully";