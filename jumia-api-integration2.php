<?php

require_once __DIR__ . '/vendor/autoload.php';

class ProductSync {
    private $client;
    private $service;
    private $spreadsheetID;

    public function __construct( $spreadsheetID, $credentialsPath ) {
        $this->client = new Google\Client();
        $this->client->setApplicationName( "goglesheetapi" );
        $this->client->setScopes( [ \Google_Service_Sheets::SPREADSHEETS ] );
        $this->client->setAccessToken( 'offline' );
        $this->client->setAuthConfig( $credentialsPath );
        $this->service       = new Google_Service_Sheets( $this->client );
        $this->spreadsheetID = $spreadsheetID;
    }

    public function fetchProductsFromApi() {
        // Your existing code to fetch products from the API
    }

    public function fetchProductsFromSheets( $range ) {
        $response = $this->service->spreadsheets_values->get( $this->spreadsheetID, $range );
        return $response->getValues();
    }

    public function updateOrCreateProducts( $vendorProducts, $googleProducts ) {
        foreach ( $vendorProducts as $vendorProduct ) {
            $vendorSku = $vendorProduct['sku'];
            $found     = false;

            foreach ( $googleProducts as $googleProduct ) {
                $googleSku = $googleProduct[0]; // Assuming SKU is in the first column

                if ( $vendorSku == $googleSku ) {
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

    public function synchronizeProducts( $apiRange, $sheetRange ) {
        // Fetch products from the API
        $vendorProducts = $this->fetchProductsFromApi();

        // Check if data is fetched successfully
        if ( empty( $vendorProducts ) ) {
            print ( "No data found from vendor API.\n" );
        } else {
            // Fetch products from Google Sheets
            $googleProducts = $this->fetchProductsFromSheets( $sheetRange );

            // Check if data is fetched successfully from Google Sheets
            if ( empty( $googleProducts ) ) {
                print ( "No data found from Google Sheets.\n" );
            } else {
                // Update or create products based on SKU matching
                $this->updateOrCreateProducts( $vendorProducts, $googleProducts );
            }
        }
    }
}

// Main code
$spreadsheetID   = "1igZQ5L-FlY7FTzqMpxPOzbscWLYo15hLW5s9YHwPRD4";
$credentialsPath = __DIR__ . '/credentials.json';

$productSync = new ProductSync( $spreadsheetID, $credentialsPath );
$productSync->synchronizeProducts( "vendor_range", "products!A:E" );
