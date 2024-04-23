<?php

require_once __DIR__ . '/vendor/autoload.php';

// Google Sheets API client setup
$client = new Google\Client();
$client->setApplicationName( "goglesheetapi" );
$client->setScopes( [ \Google_Service_Sheets::SPREADSHEETS ] );
$client->setAccessToken( 'offline' );
$client->setAuthConfig( __DIR__ . '/credentials.json' );
$service       = new Google_Service_Sheets( $client );
$spreadsheetID = "1igZQ5L-FlY7FTzqMpxPOzbscWLYo15hLW5s9YHwPRD4";

// Function to fetch products from the vendor's API
function fetch_products_from_api() {
    // Your existing code to fetch products from the API
}

// Function to fetch products from Google Sheets
function fetch_products_from_sheets( $service, $spreadsheetID, $range ) {
    $response = $service->spreadsheets_values->get( $spreadsheetID, $range );
    return $response->getValues();
}

// Function to update or create products based on SKU matching
function update_or_create_products( $vendor_products, $google_products ) {
    foreach ( $vendor_products as $vendor_product ) {
        $vendor_sku = $vendor_product['sku'];
        $found      = false;

        foreach ( $google_products as $google_product ) {
            $google_sku = $google_product[0]; // Assuming SKU is in the first column

            if ( $vendor_sku == $google_sku ) {
                // Update product
                // Implement your update logic here
                $found = true;
                break;
            }
        }

        if ( !$found ) {
            // Create product
            // Implement your create logic here
        }
    }
}

// Main code
// Fetch products from the API
$vendor_products = fetch_products_from_api();

// Check if data is fetched successfully
if ( empty( $vendor_products ) ) {
    print ( "No data found from vendor API.\n" );
} else {
    // Fetch products from Google Sheets
    $range           = "products!A:E";
    $google_products = fetch_products_from_sheets( $service, $spreadsheetID, $range );

    // Check if data is fetched successfully from Google Sheets
    if ( empty( $google_products ) ) {
        print ( "No data found from Google Sheets.\n" );
    } else {
        // Update or create products based on SKU matching
        update_or_create_products( $vendor_products, $google_products );
    }
}
