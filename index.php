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
    private $productInfo = [];

    public function __construct() {
        $this->client = new Google\Client();
        $this->client->setApplicationName( "goglesheetapi" );
        $this->client->setScopes( [ \Google_Service_Sheets::SPREADSHEETS ] );
        $this->client->setAccessToken( 'offline' );
        $this->client->setAuthConfig( $this->credentialsPath );
        $this->service       = new Google_Service_Sheets( $this->client );
        $this->spreadsheetID = '1igZQ5L-FlY7FTzqMpxPOzbscWLYo15hLW5s9YHwPRD4';
        $this->sheetRange    = 'products!A:E';
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
            $vendorSku = $vendorProduct['id'];
            $found     = false;

            foreach ( $googleProducts as $googleProduct ) {
                $googleSku = $googleProduct[2];

                if ( $vendorSku == $googleSku ) {
                    // Update product
                    $found           = true;
                    $responseMessage = "Product with SKU $googleSku already exists. Updating...\n";
                    // Perform update logic here
                    $this->update_existing_product();
                    break;
                }
            }

            if ( !$found ) {
                // Create product
                $responseMessage = "Product with SKU $vendorSku not found. Creating...\n";
                // Perform create logic here
                $this->create_new_product();
            }
        }

        echo $responseMessage;
    }


    public function create_new_product() {
        // product array
        $productArray = [
            'shopId'   => $this->shopID,
            'products' => [
                [
                    'name'        => [
                        'value'        => 'Name should be between 15 and 60 characters',
                        'translations' => [],
                    ],
                    'description' => [
                        'value'        => 'Description should have more than 150 words.',
                        'translations' => [],
                    ],
                    'parentSku'   => '',
                    'sellerSku'   => 'jalal123456',
                    'barcodeEan'  => '1234567000001239999',
                    'variation'   => 1,
                    'brand'       => [ 'code' => 1126253, 'name' => '123 updated' ],
                    'category'    => [ 'code' => 1004141, 'name' => 'Gaming / PC Gaming / Accessories / Controllers' ],
                    'images'      => [
                        [ 'url' => 'https://ng.jumia.is/LgDWyaUAUqlaDlr6gmf0ui43GGk=/fit-in/500x500/filters:fill(white)/product/90/278208/1.jpg?4790', 'primary' => 1 ],
                    ],
                    'price'       => [
                        'currency'  => 'EGP',
                        'value'     => 200,
                        'salePrice' => [ 'value' => 150, 'startAt' => '', 'endAt' => '' ],
                    ],
                    'stock'       => 500,
                    'attributes'  => [
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

        echo "product created $response";
    }

    public function update_existing_product() {
        // product array
        $productArray = [
            'shopId'   => 'a8b5534b-277b-449c-97b7-c00979dd9c3a',
            'products' => [
                [
                    'name'                 => [
                        'value'        => 'Name of one variation of the product between 15 and 60 characters jalal',
                        'translations' => [
                            [ 'language' => 'AR', 'value' => 'اختبار اسم المنتج' ],
                            [ 'language' => 'FR', 'value' => 'Test du nom du produit' ],
                        ],
                    ],
                    'description'          => [
                        'value'        => 'Description should have more than 150 words. Product number35G41EAProduct nameHP Spectre x360 Convertible 14-ea0047naMicroprocessorIntel® Core™ i5-1135G7 (up to 4.2 GHz with Intel® Turbo Boost Technology, 8 MB L3 cache, 4 cores)ChipsetIntel® Integrated SoCMemory, standard8 GB LPDDR4x-3733 MHz RAM (onboard)Video graphicsIntel® Iris® Xᵉ GraphicsHard drive512 GB PCIe® NVMe™ TLC M.2 SSDOptical driveOptical drive not includedDisplay34.3 cm (13.5) diagonal, WUXGA+ (1920 x 1280), multitouch-enabled, IPS, edge-to-edge glass, micro-edge, Corning® Gorilla® Glass NBT™, 1000 nits, 100% sRGB, HP Sure View Reflect integrated privacy screenWireless connectivityIntel® Wi-Fi 6 AX201 (2x2) and Bluetooth® 5 combo (Supporting Gigabit file transfer speeds)Expansion slots1 microSD media card readerExternal ports2 Thunderbolt™ 4 with USB4™ Type-C® 40Gbps signaling rate (USB Power Delivery, DisplayPort™ 1.4, HP Sleep and Charge); 1 SuperSpeed USB Type-A 10Gbps signaling rate (HP Sleep and Charge); 1 headphone/microphone comboMinimum dimensions (W x D x H)29.83 x 22.01 x 1.69 cmWeight1.34 kgPower supply type65 W USB Type-C® power adapterBattery type4-cell, 66 Wh Li-ion polymerBattery life mixed usageUp to 15 hours and 45 minutesVideo Playback Battery lifeUp to 16 hours and 30 minutesWebcamHP True Vision 720p HD IR camera with camera shutter and integrated dual array digital microphonesAudio featuresAudio by Bang & Olufsen; Quad speakers; HP Audio BoostSoftwareOperating systemWindows 10 Home 64HP appsHP 3D DriveGuard; HP Audio Switch; HP JumpStart; HP Support AssistantSoftware includedMcAfee LiveSafe™Pre-installed softwareExpressVPN (30 day free trial); LastPass Premium (30 day free trial).',
                        'translations' => [
                            [ 'language' => 'AR', 'value' => 'اختبار اسم المنتج' ],
                            [ 'language' => 'FR', 'value' => 'Test du description du produit' ],
                        ],
                    ],
                    'parentSku'            => 'new_product_sku_60077999',
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
                        [ 'name' => 'note', 'value' => 'note about the product' ],
                        [ 'name' => 'plug_type', 'value' => 'EU' ],
                        [ 'name' => 'voltage', 'value' => '100' ],
                        [ 'name' => 'connection_gender', 'value' => 'gender of the connection' ],
                        [ 'name' => 'battery_capacity', 'value' => '100 mAh' ],
                        [ 'name' => 'model', 'value' => 'product model', 'translations' => [ [ 'language' => 'AR', 'value' => 'محتوى العبوة عربي' ], [ 'language' => 'FR', 'value' => 'Produit model' ] ] ],
                        [ 'name' => 'short_description', 'value' => 'short description should have at least 4 bullets short description short description short description' ],
                        [ 'name' => 'color_family', 'value' => 'Black' ],
                        [ 'name' => 'color', 'value' => 'Black' ],
                        [ 'name' => 'warranty_duration', 'value' => '10' ],
                    ],
                ],
                [
                    'name'                 => [
                        'value'        => 'Name of another variation of the product between 15 and 60 characters',
                        'translations' => [
                            [ 'language' => 'AR', 'value' => 'اختبار اسم المنتج' ],
                            [ 'language' => 'FR', 'value' => 'Test du nom du produit' ],
                        ],
                    ],
                    'description'          => [
                        'value'        => 'Description should have more than 150 words. Product number35G41EAProduct nameHP Spectre x360 Convertible 14-ea0047naMicroprocessorIntel® Core™ i5-1135G7 (up to 4.2 GHz with Intel® Turbo Boost Technology, 8 MB L3 cache, 4 cores)ChipsetIntel® Integrated SoCMemory, standard8 GB LPDDR4x-3733 MHz RAM (onboard)Video graphicsIntel® Iris® Xᵉ GraphicsHard drive512 GB PCIe® NVMe™ TLC M.2 SSDOptical driveOptical drive not includedDisplay34.3 cm (13.5) diagonal, WUXGA+ (1920 x 1280), multitouch-enabled, IPS, edge-to-edge glass, micro-edge, Corning® Gorilla® Glass NBT™, 1000 nits, 100% sRGB, HP Sure View Reflect integrated privacy screenWireless connectivityIntel® Wi-Fi 6 AX201 (2x2) and Bluetooth® 5 combo (Supporting Gigabit file transfer speeds)Expansion slots1 microSD media card readerExternal ports2 Thunderbolt™ 4 with USB4™ Type-C® 40Gbps signaling rate (USB Power Delivery, DisplayPort™ 1.4, HP Sleep and Charge); 1 SuperSpeed USB Type-A 10Gbps signaling rate (HP Sleep and Charge); 1 headphone/microphone comboMinimum dimensions (W x D x H)29.83 x 22.01 x 1.69 cmWeight1.34 kgPower supply type65 W USB Type-C® power adapterBattery type4-cell, 66 Wh Li-ion polymerBattery life mixed usageUp to 15 hours and 45 minutesVideo Playback Battery lifeUp to 16 hours and 30 minutesWebcamHP True Vision 720p HD IR camera with camera shutter and integrated dual array digital microphonesAudio featuresAudio by Bang & Olufsen; Quad speakers; HP Audio BoostSoftwareOperating systemWindows 10 Home 64HP appsHP 3D DriveGuard; HP Audio Switch; HP JumpStart; HP Support AssistantSoftware includedMcAfee LiveSafe™Pre-installed softwareExpressVPN (30 day free trial); LastPass Premium (30 day free trial).',
                        'translations' => [
                            [ 'language' => 'AR', 'value' => 'اختبار اسم المنتج' ],
                            [ 'language' => 'FR', 'value' => 'Test du description du produit' ],
                        ],
                    ],
                    'parentSku'            => 'jalal123456',
                    'sellerSku'            => 'jalal123456',
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
                        [ 'name' => 'note', 'value' => 'note about the product' ],
                        [ 'name' => 'plug_type', 'value' => 'EU' ],
                        [ 'name' => 'voltage', 'value' => '100' ],
                        [ 'name' => 'connection_gender', 'value' => 'gender of the connection' ],
                        [ 'name' => 'battery_capacity', 'value' => '100 mAh' ],
                        [ 'name' => 'model', 'value' => 'product model', 'translations' => [ [ 'language' => 'AR', 'value' => 'محتوى العبوة عربي' ], [ 'language' => 'FR', 'value' => 'Produit model' ] ] ],
                        [ 'name' => 'short_description', 'value' => 'short description should have at least 4 bullets short description short description short description' ],
                        [ 'name' => 'color_family', 'value' => 'Brown' ],
                        [ 'name' => 'color', 'value' => 'Brown' ],
                        [ 'name' => 'warranty_duration', 'value' => '10' ],
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

        echo "Product Updated $response";
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
echo '</pre>';

echo '<br>';
echo '<pre>';
print_r( $productSync->fetchProductsFromSheets() );
echo '</pre>'; */


/* perform operations here */
/* $vendorProducts = $productSync->fetchProductsFromApi();
echo '<pre>';
print_r( $vendorProducts );
echo '</pre>';

$googleProducts = $productSync->fetchProductsFromSheets();
echo '<pre>';
print_r( $googleProducts );
echo '/<pre>'; */

// $productSync->updateOrCreateProducts( $vendorProducts, $googleProducts );

/* Create and Update product manually */
/* $productSync->create_new_product();
echo "<br>";
$productSync->update_existing_product(); */