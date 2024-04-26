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
        $this->sheetRange    = 'products!A:K';
        $this->shopID        = '0705e4e4-eca2-4c92-b201-fcb9c654f0df';
        $this->accessToken   = $this->generateAccessToken();
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
            $vendorSku = $vendorProduct['parentSku'];
            $found     = false;

            $googleSingleProduct = [];

            foreach ( $googleProducts as $googleProduct ) {
                $googleSingleProduct = $googleProduct;
                $googleSku           = $googleProduct[2];

                if ( $vendorSku === $googleSku ) {
                    // Update product
                    $found = true;
                    // Perform update logic here
                    $this->update_existing_product();
                    $responseMessage = "Product with SKU $googleSku already exists. Updating...\n";
                    continue;
                }
            }

            if ( !$found ) {
                // Create product
                // Perform create logic here
                $productCreating = $this->create_new_product();
                $responseMessage = "Product with SKU $vendorSku not found. Creating... Response is $productCreating \n";
            }
        }

        echo $responseMessage;
    }

    public function getProductStatus() {

        $feedId = 'b9a89294-300e-4a40-9942-7dd689674751';

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


    public function create_new_product() {

        // extract product informations
        /* $productName   = $product['1'] ?? null;
        $sku           = $product['2'] ?? null;
        $stock         = $product['3'] ?? null;
        $regular_price = $product['4'] ?? null;
        $selling_price = $product['5'] ?? null;
        $description   = $product['6'] ?? null;
        $brand         = $product['7'] ?? null;
        $category      = $product['8'] ?? null;
        $images        = $product['9'] ?? null;
        $attributes    = $product['10'] ?? null; */

        $productName   = 'PARKERSBURG RECTANGLE DINING TABLE PALADINA AND NATURAL WALNUT';
        $sku           = 'jalalProduct123';
        $stock         = 200;
        $regular_price = 500;
        $selling_price = 400;
        $description   = 'Stylish and sustainable, this wooden dining table is perfect for the environmentally conscious home. Solid wood and recycled sawdust are treated to a high-pressure lamination process to create this quality piece. The tabletop is stain, UV, light, and weather resistant for unparalleled durability. Also shock-resistant and break-proof, it stands up to heavy duty use. The smooth, rectangular top is supported by bold, angled leg supports for a striking design thats sure to make you look twice.';
        $brand         = 'Coaster';

        // product array
        $productArray = [
            'shopId'   => $this->shopID,
            'products' => [
                [
                    'name'        => [
                        'value'        => $productName,
                        'translations' => [],
                    ],
                    'description' => [
                        'value'        => $description,
                        'translations' => [],
                    ],
                    'parentSku'   => $sku,
                    'sellerSku'   => $sku,
                    'barcodeEan'  => '',
                    'variation'   => 1,
                    'brand'       => [ 'code' => 1126253, 'name' => $brand ],
                    'category'    => [ 'code' => 1004141, 'name' => 'Gaming / PC Gaming / Accessories / Controllers' ],
                    'images'      => [
                        [ 'url' => 'https://lindorfurniture.com/wp-content/uploads/2024/02/192751_21x900.jpg', 'primary' => true ],
                        [ 'url' => 'https://lindorfurniture.com/wp-content/uploads/2024/02/192751_1x900.jpg', 'primary' => false ],
                    ],
                    'price'       => [
                        'currency'  => 'EGP',
                        'value'     => $regular_price,
                        'salePrice' => [ 'value' => $selling_price, 'startAt' => '2024-04-26', 'endAt' => '2026-04-20' ],
                    ],
                    'stock'       => $stock,
                    'attributes'  => [
                        [ 'name' => 'isbn', 'value' => '0-6280-1750-2' ],
                        [ 'name' => 'product_weight', 'value' => '10kg' ],
                        [ 'name' => 'short_description', 'value' => '<ul><li>short description should have at least 4 bullets</li><li>short description&nbsp;</li><li>short description&nbsp;</li><li>short description&nbsp;</li></ul>' ],
                    ],
                ],
            ],
        ];

        // convert to json
        $productJson = json_encode( $productArray );

        $curl = curl_init();

        curl_setopt_array(
            $curl,
            array(
                CURLOPT_URL            => 'https://vendor-api-staging.jumia.com/feeds/products/create',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING       => '',
                CURLOPT_MAXREDIRS      => 10,
                CURLOPT_TIMEOUT        => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST  => 'POST',
                CURLOPT_POSTFIELDS     => $productJson,
                CURLOPT_HTTPHEADER     => array(
                    'Authorization: Bearer ' . $this->accessToken,
                    'Content-Type: application/json',
                    'Cookie: __cf_bm=J.KuHSJ9RPzg0mXYMQJthrq5C1vsLZVV_c09qWXUzHI-1713786910-1.0.1.1-RxUQQR7OTwiBw46hjN2C2UKiN_SXEuuayY7ujq0kv_XUXeROozkcrW_ytbB57A1AIRgtwpRaZ9h1c3J2XveoQQ',
                ),
            )
        );

        $response = curl_exec( $curl );

        curl_close( $curl );

        return "product created $response";
    }

    public function update_existing_product() {

        $sku = 'jalal114477';

        // product array
        $productArray = [
            'shopId'   => $this->shopID,
            'products' => [
                [
                    'name'                 => [
                        'value'        => 'Name of one variation of the product between 15 and 60 characters jalal',
                        'translations' => [],
                    ],
                    'description'          => [
                        'value'        => 'Description should have more than 150 words.',
                        'translations' => [],
                    ],
                    'parentSku'            => '',
                    'sellerSku'            => 'new_product_sku_jalal',
                    'barcodeEan'           => '1234567000001239999',
                    'variation'            => 1,
                    'brand'                => [ 'code' => 1126253, 'name' => '123 updated' ],
                    'category'             => [ 'code' => 1004141, 'name' => 'Gaming / PC Gaming / Accessories / Controllers' ],
                    'additionalCategories' => [],
                    'images'               => [
                        [ 'url' => 'https://ng.jumia.is/LgDWyaUAUqlaDlr6gmf0ui43GGk=/fit-in/500x500/filters:fill(white)/product/90/278208/1.jpg?4790', 'primary' => 1 ],
                        [ 'url' => 'https://ng.jumia.is/W-t47t1CIN1cl_y6KcnaM5Z-PjM=/fit-in/500x500/filters:fill(white)/product/90/278208/2.jpg?4790', 'primary' => null ],
                    ],
                    'price'                => [
                        'value'     => 200,
                        'salePrice' => [ 'value' => 150, 'startAt' => '2022-08-11 15:00', 'endAt' => '2022-08-22 17:00' ],
                    ],
                    'stock'                => 500,
                    'attributes'           => [
                        [ 'name' => 'isbn', 'value' => '0-6280-1750-2' ],
                    ],
                ],
                [
                    'name'                 => [
                        'value'        => 'Name of another variation of the product between 15 and 60 characters',
                        'translations' => [],
                    ],
                    'description'          => [
                        'value'        => 'Description should have more than 150 words.',
                        'translations' => [],
                    ],
                    'parentSku'            => $sku,
                    'sellerSku'            => $sku,
                    'barcodeEan'           => '1234567000003459999',
                    'variation'            => 2,
                    'brand'                => [ 'code' => 1126253, 'name' => '123 updated' ],
                    'category'             => [ 'code' => 1004141, 'name' => 'Gaming / PC Gaming / Accessories / Controllers' ],
                    'additionalCategories' => [],
                    'images'               => [
                        [ 'url' => 'https://ng.jumia.is/LgDWyaUAUqlaDlr6gmf0ui43GGk=/fit-in/500x500/filters:fill(white)/product/90/278208/1.jpg?4790', 'primary' => 1 ],
                        [ 'url' => 'https://ng.jumia.is/W-t47t1CIN1cl_y6KcnaM5Z-PjM=/fit-in/500x500/filters:fill(white)/product/90/278208/2.jpg?4790', 'primary' => null ],
                    ],
                    'price'                => [
                        'value'     => 200,
                        'salePrice' => [ 'value' => 150, 'startAt' => '2022-08-11 15:00', 'endAt' => '2022-08-22 17:00' ],
                    ],
                    'stock'                => 500,
                    'attributes'           => [
                        [ 'name' => 'isbn', 'value' => '0-6280-1750-2' ],
                    ],
                ],
            ],
        ];

        // convert to json
        $productJson = json_encode( $productArray );

        $curl = curl_init();

        curl_setopt_array(
            $curl,
            array(
                CURLOPT_URL            => 'https://vendor-api-staging.jumia.com/feeds/products/update',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING       => '',
                CURLOPT_MAXREDIRS      => 10,
                CURLOPT_TIMEOUT        => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST  => 'POST',
                CURLOPT_POSTFIELDS     => $productJson,
                CURLOPT_HTTPHEADER     => array(
                    'Authorization: Bearer ' . $this->accessToken,
                    'Content-Type: application/json',
                    'Cookie: __cf_bm=J.KuHSJ9RPzg0mXYMQJthrq5C1vsLZVV_c09qWXUzHI-1713786910-1.0.1.1-RxUQQR7OTwiBw46hjN2C2UKiN_SXEuuayY7ujq0kv_XUXeROozkcrW_ytbB57A1AIRgtwpRaZ9h1c3J2XveoQQ',
                ),
            )
        );

        $response = curl_exec( $curl );

        curl_close( $curl );

        echo "Product Updated with sku $sku, response is $response";
    }

    public function synchronizeProducts() {
        // Fetch products from the API
        $vendorProducts = $this->fetchProductsFromApi();

        // Fetch products from Google Sheets
        $googleProducts = $this->fetchProductsFromSheets();



    }
}

$productSync = new ProductSync();

/* Fetch products */
/* echo '<pre>';
print_r( $productSync->fetchProductsFromApi() );
echo '</pre>'; */

echo '<br>';

/* echo '<pre>';
print_r( $productSync->fetchProductsFromSheets() );
echo '</pre>'; */


/* perform product creation or update operations here */
// $vendorProducts = $productSync->fetchProductsFromApi();
// $googleProducts = $productSync->fetchProductsFromSheets();
// $productSync->updateOrCreateProducts( $vendorProducts, $googleProducts );

// get product status
// $productSync->getProductStatus();