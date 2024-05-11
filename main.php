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
        $this->sheetRange    = 'products!A:D';
        // $this->sheetRange = 'products!A1114:D1114';
        // $this->sheetRange = 'products';
        $this->shopID      = '0705e4e4-eca2-4c92-b201-fcb9c654f0df';
        $this->accessToken = $this->generateAccessToken();
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

    public function fetchProductsFromSheets() {
        $response = $this->service->spreadsheets_values->get( $this->spreadsheetID, $this->sheetRange );
        return $response->getValues();
    }

    public function createProductsTable() {
        // Require config file
        require 'config.php';

        // SQL command to create the products table
        $sql = "CREATE TABLE IF NOT EXISTS `products` (
                  `id` int unsigned NOT NULL AUTO_INCREMENT,
                  `sku` varchar(100) NOT NULL,
                  `sid` varchar(100) NOT NULL,
                  `stock` int unsigned NOT NULL,
                  `price` int unsigned NOT NULL,
                  `status` varchar(30) NOT NULL,
                  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
                  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                  PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci";

        // Execute the SQL statement
        $result = mysqli_query( $conn, $sql );

        // Check if the table creation was successful
        if ( !$result ) {
            // Handle the error if table creation failed
            echo "Error creating products table: " . mysqli_error( $conn );
        } else {
            echo "Products table created successfully";
        }

        // Close the database connection
        mysqli_close( $conn );
    }

    public function insertProductToDatabase() {
        // require config file
        require 'config.php';

        // Truncate the products table to remove previous products
        $truncate_sql    = "TRUNCATE TABLE products";
        $truncate_result = mysqli_query( $conn, $truncate_sql );

        if ( !$truncate_result ) {
            // Handle the error if truncation failed
            echo "Error truncating products table: " . mysqli_error( $conn );
            return; // Stop further execution if truncation fails
        }

        // fetch products for Google Sheet
        $sheetData = $this->fetchProductsFromSheets();

        // insert products to database
        foreach ( $sheetData as $sheetDatum ) {
            // retrieve product data
            $sku    = $sheetDatum[0];
            $sid    = $sheetDatum[1];
            $stock  = $sheetDatum[2];
            $price  = $sheetDatum[3];
            $status = 'pending'; // Set status to pending as per your table definition

            // Prepare the SQL statement
            $sql = "INSERT INTO products (sku, sid, stock, price, status) VALUES ('$sku', '$sid', $stock, $price, '$status')";

            // Execute the SQL statement
            $result = mysqli_query( $conn, $sql );

            // Check if the insertion was successful
            if ( !$result ) {
                // Handle the error if insertion failed
                echo "Error: " . mysqli_error( $conn );
            }
        }

        // Close the database connection
        mysqli_close( $conn );

        echo "Products inserted successfully to Database";
    }

    public function fetchProductFromDatabase() {
        // require config file
        require 'config.php';

        // fetch products from database
        $sql = "SELECT * FROM products WHERE status = 'pending' LIMIT 10";

        // Execute the SQL statement
        $result = mysqli_query( $conn, $sql );

        // Check if the query was successful
        if ( !$result ) {
            // Handle the error if query failed
            echo "Error: " . mysqli_error( $conn );
        } else {
            // Fetch the rows from the result set
            $products = array();
            while ( $row = mysqli_fetch_assoc( $result ) ) {
                // Add each row to the products array
                $products[] = $row;
            }

            // Close the result set
            mysqli_free_result( $result );

            // Close the database connection
            mysqli_close( $conn );

            // Return the fetched products
            return $products;
        }
    }

    public function updateProductStock( $sku, $id, $stock ) {

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

        // product array
        $productArray = [
            "products" => [
                [
                    "sellerSku"       => "$sku",
                    "id"              => "$id",
                    "category"        => "",
                    "price"           => [
                        "currency"  => "MAD",
                        "value"     => intval( $price ),
                        "salePrice" => [
                            "value"   => null,
                            "startAt" => "",
                            "endAt"   => "",
                        ],
                    ],
                    "businessClients" => [
                        [
                            "businessClientCode" => "jumia-ma",
                            "price"              => [
                                "currency"  => "MAD",
                                "value"     => intval( $price ),
                                "salePrice" => [
                                    "value"   => null,
                                    "startAt" => "",
                                    "endAt"   => "",
                                ],
                            ],
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

        // Fetch products from database
        $productInfoFromDB = $this->fetchProductFromDatabase();

        // Update product stock and price
        foreach ( $productInfoFromDB as $product ) {
            // Retrieve product data
            $id    = $product['id'] ?? 0;
            $sku   = $product['sku'] ?? '';
            $sid   = $product['sid'] ?? '';
            $stock = $product['stock'] ?? 0;
            $price = $product['price'] ?? 0;

            // Update product stock
            echo $this->updateProductStock( $sku, $sid, $stock );

            // Update product price
            echo $this->updateProductPrice( $sku, $sid, $price );

            // Update status to completed
            $this->updateProductStatus( $id, 'completed' );

            echo "Product Updated <br>";
        }
    }

    private function updateProductStatus( $id, $status ) {
        // require config file
        require 'config.php';

        // Update product status
        $sql = "UPDATE products SET status = '$status' WHERE id = $id";

        // Execute the SQL statement
        $result = mysqli_query( $conn, $sql );

        // Check if the query was successful
        if ( !$result ) {
            // Handle the error if query failed
            echo "Error updating status: " . mysqli_error( $conn );
        }

        // Close the database connection
        mysqli_close( $conn );
    }

    public function getProductStatus() {

        $feedId = 'dbe99ec8-5c22-4482-ab07-49213b038f53';

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
}

// $productSync = new ProductSync();
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

// insert products to database
// $productSync->insertProductToDatabase();

// fetch products from database
// echo '<pre>';
// print_r( $productSync->fetchProductFromDatabase() );

// Create products table
// $productSync->createProductsTable();