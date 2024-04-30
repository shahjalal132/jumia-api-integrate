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
        // $this->sheetRange    = 'products!A:D';
        $this->sheetRange = 'products';
        $this->shopID     = '0705e4e4-eca2-4c92-b201-fcb9c654f0df';
        // $this->accessToken = $this->generateAccessToken();
    }

    public function generateAccessToken() {
        $curl = curl_init();

        curl_setopt_array(
            $curl,
            array(
                CURLOPT_URL            => 'https://vendor-api.jumia.com/token',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING       => '',
                CURLOPT_MAXREDIRS      => 10,
                CURLOPT_TIMEOUT        => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST  => 'POST',
                CURLOPT_POSTFIELDS     => 'client_id=784e325d-e0a9-4dab-a751-01113d9a4a86&grant_type=refresh_token&refresh_token=eyJhbGciOiJIUzI1NiIsInR5cCIgOiAiSldUIiwia2lkIiA6ICIyYTVmOTE3Zi1jNDRlLTQ3MWEtYTRiZC03NDE1NWU1ODYwZGIifQ.eyJqdGkiOiI2ODU2NzI1YS0wMjNmLTQ2NDAtOWMzMC01M2NjZDUwMDc2NWMiLCJleHAiOjE3NDU3NTE3NDMsIm5iZiI6MCwiaWF0IjoxNzE0MjE1NzQzLCJpc3MiOiJodHRwczovL3ZlbmRvci1hcGkuanVtaWEuY29tL2F1dGgvcmVhbG1zL2FjbCIsImF1ZCI6Imh0dHBzOi8vdmVuZG9yLWFwaS5qdW1pYS5jb20vYXV0aC9yZWFsbXMvYWNsIiwic3ViIjoiZTcyZTgxYmYtNTA4OS00N2IyLTlmMGQtZjA1ODg1Y2JmY2VmIiwidHlwIjoiUmVmcmVzaCIsImF6cCI6Ijc4NGUzMjVkLWUwYTktNGRhYi1hNzUxLTAxMTEzZDlhNGE4NiIsImF1dGhfdGltZSI6MCwic2Vzc2lvbl9zdGF0ZSI6Ijk0ZjkzNzVhLWM2NjMtNDM1Zi04MmI0LWRiMjkxNzdmN2Y5MSIsInNjb3BlIjoicHJvZmlsZSBlbWFpbCJ9.DR5gM7U97-Rapp5pKGLkTpZkC7IgAc5ILciLNG-sa_k',
                CURLOPT_HTTPHEADER     => array(
                    'Content-Type: application/x-www-form-urlencoded',
                ),
            )
        );

        $response = curl_exec( $curl );

        curl_close( $curl );

        $response = json_decode( $response, true );

        return $response['access_token'];
    }

    public function fetchProductsFromSheets() {
        $response = $this->service->spreadsheets_values->get( $this->spreadsheetID, $this->sheetRange );
        return $response->getValues();
    }

    public function pushProductInfoToSheet() {

        $filePath     = __DIR__ . '/Data/productData.json';
        $productData  = file_get_contents( $filePath );
        $productDatas = json_decode( $productData, true );
        $productDatas = $productDatas['products'];

        $values = [];
        foreach ( $productDatas as $productData ) {
            $values[] = [ $productData['sellerSku'], $productData['id'] ];
        }

        $body = new Google_Service_Sheets_ValueRange( [
            'values' => $values,
        ] );

        $params = [
            'valueInputOption' => 'RAW',
        ];

        $insert = [
            'insertDataOption' => 'INSERT_ROWS',
        ];

        $response = $this->service->spreadsheets_values->append(
            $this->spreadsheetID,
            $this->sheetRange,
            $body,
            $params,
            $insert
        );

        if ( $response ) {
            echo "Insert Data successfully";
        } else {
            echo "Something went wrong not inserted";
        }
    }

    public function getProductStatus() {

        $feedId = 'c7cf573f-d8bf-4e83-bf11-56ffc29a5c69';

        $curl = curl_init();

        curl_setopt_array(
            $curl,
            array(
                CURLOPT_URL            => 'https://vendor-api.jumia.com/feeds/' . $feedId,
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

    public function updateProductStock( $sku, $id, $stock ) {

        if ( '' == $sku || '' == $id || '' == $stock )
            return;



        // product array
        $productArray = [
            "products" => [
                [
                    "sellerSku" => $sku,
                    "id"        => $id,
                    "stock"     => intval( $stock ),
                ],
            ],
        ];

        // convert to json
        $productJson = json_encode( $productArray );

        $curl = curl_init();

        curl_setopt_array(
            $curl,
            array(
                CURLOPT_URL            => 'https://vendor-api.jumia.com/feeds/products/stock',
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
                    'Cookie: __cf_bm=OtHRyWyEqMVWGVYkrwfm.URc3oCI05Hga2SgJ85NY_g-1714219200-1.0.1.1-y3od0XgXWab8h5MqqcqE7la1_K.qXR1gM0j4rRtAHjiSIt5U8lV_9MKH3fIl36QLc9kwPmfE1yO8IGLxmEZQZQ',
                ),
            )
        );

        $response = curl_exec( $curl );

        curl_close( $curl );
        return $response;
    }

    public function updateProductPrice( $sku, $id, $price ) {

        if ( '' == $sku || '' == $id || 0 == $price )
            return;

        // product array
        $productArray = [
            'products' => [
                [
                    'sellerSku' => $sku,
                    'id'        => $id,
                    'category'  => null,
                    'price'     => [
                        'currency'  => 'MAD',
                        'value'     => intval( $price ),
                        'salePrice' => [
                            'value'   => null,
                            'startAt' => null,
                            'endAt'   => null,
                        ],
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
                CURLOPT_URL            => 'https://vendor-api.jumia.com/feeds/products/price',
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
                    'Cookie: __cf_bm=z3K9NrE2Gay_MAHsE9uQHdyaMuJflcf4O5LMC.12cGM-1714276081-1.0.1.1-ZUAYpc4xlTXwxVX3IHXIiKZjaJzFKFbNTqDIrtnppaoONYpr6XHJ8WmJ.d7lxRZkf5c_goQyQDGx1FbLLQELow',
                ),
            )
        );

        $response = curl_exec( $curl );

        curl_close( $curl );
        return $response;

    }

    public function updateStockPrice() {

        // Fetch products from sheet
        $productInfoFromSheet = $this->fetchProductsFromSheets();

        // Update product stock and price
        foreach ( $productInfoFromSheet as $product ) {
            $sku   = $product[0] ?? '';
            $id    = $product[1] ?? '';
            $stock = $product[2] ?? 0;
            $price = $product[3] ?? 0;

            // Update product stock
            $stockUpdated = $this->updateProductStock( $sku, $id, $stock );
            if ( !$stockUpdated ) {
                echo "Failed to update stock for product with SKU: $sku <br>";
                continue; // Skip to the next product if stock update fails
            }

            echo $stockUpdated;

            // Update product price
            $priceUpdated = $this->updateProductPrice( $sku, $id, $price );
            if ( !$priceUpdated ) {
                echo "Failed to update price for product with SKU: $sku <br>";
                continue; // Skip to the next product if price update fails
            }

            echo $priceUpdated;

            echo "Product Updated <br>";
        }
    }
}

$productSync = new ProductSync();
// $productSync->updateStockPrice();

// get product status
// $productSync->getProductStatus();

// updaate prodcut stocks
// $productSync->updateProductStock();

// update product price
// $productSync->updateProductPrice();

// get product from sheet
// echo '<pre>';
// print_r( $productSync->fetchProductsFromSheets() );

// push product infor to sheet
// $productSync->pushProductInfoToSheet();