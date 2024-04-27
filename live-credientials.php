<?php

require_once __DIR__ . '/vendor/autoload.php';

class ProductSync {
    private $client;
    private $service;
    private $spreadsheetID;
    private $sheetRange;
    private $credentialsPath = __DIR__ . '/credentials.json';
    private $accessToken;
    private $shopID;

    public function __construct() {
        $this->client = new Google\Client();
        $this->client->setApplicationName( "goglesheetapi" );
        $this->client->setScopes( [ \Google_Service_Sheets::SPREADSHEETS ] );
        $this->client->setAccessToken( 'offline' );
        $this->client->setAuthConfig( $this->credentialsPath );
        $this->service       = new Google_Service_Sheets( $this->client );
        $this->spreadsheetID = '1igZQ5L-FlY7FTzqMpxPOzbscWLYo15hLW5s9YHwPRD4';
        $this->sheetRange    = 'products!A:K'; // Retrieve all products
        // $this->sheetRange    = 'products!A2:K2'; // Retrieve one product
        $this->shopID      = '0705e4e4-eca2-4c92-b201-fcb9c654f0df';
        $this->accessToken = $this->generateAccessToken();
    }

    public function generateAccessToken() {
        $curl = curl_init();

        curl_setopt_array(
            $curl,
            array(
                CURLOPT_URL            => 'https://vendor-api-staging.jumia.com/token',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING       => '',
                CURLOPT_MAXREDIRS      => 10,
                CURLOPT_TIMEOUT        => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST  => 'POST',
                CURLOPT_POSTFIELDS     => 'client_id=740b9a6d-1f91-4687-8250-e49b0159df40&grant_type=refresh_token&refresh_token=eyJhbGciOiJIUzI1NiIsInR5cCIgOiAiSldUIiwia2lkIiA6ICJiYTZkNTJjOS1mYTU4LTRiMzItYmU0OC00NDk1ZWNkODUxYTkifQ.eyJqdGkiOiI3NWIwMmFkZC1lMjY3LTQ1ZTgtYmZmMy01NmFlNzYxNGI2MGIiLCJleHAiOjE3NDUzMjAxODYsIm5iZiI6MCwiaWF0IjoxNzEzNzg0MTg2LCJpc3MiOiJodHRwczovL3ZlbmRvci1hcGktc3RhZ2luZy5qdW1pYS5jb20vYXV0aC9yZWFsbXMvYWNsIiwiYXVkIjoiaHR0cHM6Ly92ZW5kb3ItYXBpLXN0YWdpbmcuanVtaWEuY29tL2F1dGgvcmVhbG1zL2FjbCIsInN1YiI6ImFiMTNlNWJjLTExODYtNDBlOS04NzAxLTdiMzE5ZDQzN2ZhZSIsInR5cCI6IlJlZnJlc2giLCJhenAiOiI3NDBiOWE2ZC0xZjkxLTQ2ODctODI1MC1lNDliMDE1OWRmNDAiLCJhdXRoX3RpbWUiOjAsInNlc3Npb25fc3RhdGUiOiJiZTgwNjA5ZS1hNTNiLTRmYjktOTFjMy1kMzc4YTVkZTYxNzUiLCJzY29wZSI6InByb2ZpbGUgZW1haWwifQ.G8egLY5WAd3tr_HHs_Kbv9dxsU9Ye4qoJMTQEsf8JDc',
                CURLOPT_HTTPHEADER     => array(
                    'Content-Type: application/x-www-form-urlencoded',
                    'Cookie: __cf_bm=98toz4aPL5L9z4W9VHuxRT3ed1D3Bg4VlXU0YFbf_Ls-1714017926-1.0.1.1-F8DKfmNyoukG0VjsEOD8Iyg3ubGi_2Dy7GpOG.CqBnWqKnYDy89V2LoQsV0bEG1xuPX7rlTkeB.zaDvbfjPWzw',
                ),
            )
        );

        $response = curl_exec( $curl );

        curl_close( $curl );

        $response = json_decode( $response, true );

        return $response['access_token'];
    }

    /**
     * Fetches products from the Jumia API.
     *
     * @return string The JSON-encoded products data retrieved from the API.
     */
    public function fetchProductsFromApi() {
        $curl = curl_init();

        curl_setopt_array(
            $curl,
            array(
                CURLOPT_URL            => 'https://vendor-api-staging.jumia.com/catalog/products',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING       => '',
                CURLOPT_MAXREDIRS      => 10,
                CURLOPT_TIMEOUT        => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST  => 'GET',
                CURLOPT_HTTPHEADER     => array(
                    'Authorization: Bearer ' . $this->accessToken,
                    'Cookie: __cf_bm=5vqn_tlYJwoX74LqdRmj_Ii.zJgUksjaV_O6ewUqE5U-1713852921-1.0.1.1-pcINdhnibkJOgYhazL36Xm9j1xjtmB926xN4ZfQt2G1tXYC5LKLc1IImoGUJAtZnCgch2uulyojmT8kQMHwimw',
                ),
            )
        );

        $response = curl_exec( $curl );

        curl_close( $curl );
        // convert to array $response
        $response = json_decode( $response, true );
        return $response['products'];
    }

    /**
     * Fetch product from google sheet
     *
     * @return array The JSON-encoded products data retrieved from the API.
     */
    public function fetchProductsFromSheets() {
        $response = $this->service->spreadsheets_values->get( $this->spreadsheetID, $this->sheetRange );
        return $response->getValues();
    }

    public function updateOrCreateProducts( $vendorProducts, $googleProducts ) {

        $responseMessage = '';

        foreach ( $vendorProducts as $vendorProduct ) {

            // retrieve vendor sku
            $vendorSku = $vendorProduct['parentSku'];

            foreach ( $googleProducts as $googleProduct ) {

                // retrieve google products sku
                $googleSku = $googleProduct[2];

                if ( $vendorSku === $googleSku ) {
                    echo "Sku match with $googleSku updating... <br>";

                    // Update product
                    /* $this->updateExistingProduct();
                    $responseMessage .= "Product with SKU $googleSku already exists. Updating...<br>"; */
                } else {
                    // Create product
                    // echo "Product with SKU $googleSku not found. Creating... <br>";

                    $productCreating = $this->createNewProduct();
                    $responseMessage .= "Product with SKU $vendorSku not found. Creating... Response is $productCreating <br>";
                }
            }
        }

        echo $responseMessage;
    }

    public function getProductStatus() {

        $feedId = '988df395-b521-4cc4-a6ec-4b4f06f2ccd8';

        $curl = curl_init();

        curl_setopt_array(
            $curl,
            array(
                CURLOPT_URL            => 'https://vendor-api-staging.jumia.com/feeds/' . $feedId,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING       => '',
                CURLOPT_MAXREDIRS      => 10,
                CURLOPT_TIMEOUT        => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST  => 'GET',
                CURLOPT_HTTPHEADER     => array(
                    'Authorization: Bearer ' . $this->accessToken,
                ),
            )
        );

        $response = curl_exec( $curl );

        curl_close( $curl );
        echo $response;

    }

    public function updateProductStock() {

        $curl = curl_init();

        curl_setopt_array(
            $curl,
            array(
                CURLOPT_URL            => 'https://vendor-api-staging.jumia.com/feeds/products/stock',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING       => '',
                CURLOPT_MAXREDIRS      => 10,
                CURLOPT_TIMEOUT        => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST  => 'POST',
                CURLOPT_POSTFIELDS     => '{
                    "products": [
                        {
                        "sellerSku": "jalal4556",
                        "id": "3a1047e4-a9e2-3c9d-88a3-cfcdb906cf41",
                        "stock": 500
                        },
                        {
                        "sellerSku": "jalal4556",
                        "id": "3a1047e4-a9e2-3c9d-88a3-cfcdb906cf41",
                        "stock": 50
                        }
                    ]
                    }',
                CURLOPT_HTTPHEADER     => array(
                    'Authorization: Bearer ' . $this->accessToken,
                    'Content-Type: application/json',
                    'Cookie: __cf_bm=J.KuHSJ9RPzg0mXYMQJthrq5C1vsLZVV_c09qWXUzHI-1713786910-1.0.1.1-RxUQQR7OTwiBw46hjN2C2UKiN_SXEuuayY7ujq0kv_XUXeROozkcrW_ytbB57A1AIRgtwpRaZ9h1c3J2XveoQQ',
                ),
            )
        );

        $response = curl_exec( $curl );

        curl_close( $curl );
        echo $response;

    }
}

$productSync = new ProductSync();

/* Fetch products */
/* echo '<pre>';
print_r( $productSync->fetchProductsFromApi() );
echo '</pre>'; */

// echo '<br>';

/* echo '<pre>';
print_r( $productSync->fetchProductsFromSheets() );
echo '</pre>'; */


/* perform product creation or update operations here */
// $vendorProducts = $productSync->fetchProductsFromApi();
// $googleProducts = $productSync->fetchProductsFromSheets();
// $productSync->updateOrCreateProducts( $vendorProducts, $googleProducts );

// get product status
// $productSync->getProductStatus();

// update product stock
// $productSync->updateProductStock();