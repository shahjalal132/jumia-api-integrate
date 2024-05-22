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
        $this->shopID = '0705e4e4-eca2-4c92-b201-fcb9c654f0df';
    }

    public function generateAccessToken() {
        // require config file
        require_once 'config.php';

        try {
            // Fetch client ID and refresh token from database
            $sql         = "SELECT client_id, refresh_token FROM api_credentials ORDER BY id DESC LIMIT 1";
            $stmt        = $conn->query( $sql );
            $credentials = $stmt->fetch( PDO::FETCH_ASSOC );

            if ( !$credentials ) {
                // Handle the case if no credentials are found
                echo "Error: No credentials found in the database";
                return;
            }

            // Assign client ID and refresh token from database to variables
            $clientID     = $credentials['client_id'];
            $refreshToken = $credentials['refresh_token'];

            // Initialize CURL
            $curl = curl_init();

            // Set CURL options
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
                    CURLOPT_POSTFIELDS     => 'client_id=' . urlencode( $clientID ) . '&grant_type=refresh_token&refresh_token=' . urlencode( $refreshToken ),
                    CURLOPT_HTTPHEADER     => array(
                        'Content-Type: application/x-www-form-urlencoded',
                    ),
                )
            );

            // Execute CURL request
            $response = curl_exec( $curl );

            // Close CURL
            curl_close( $curl );

            // Decode response
            $response = json_decode( $response, true );

            // Check if access token exists in the response
            if ( isset( $response['access_token'] ) ) {
                // Put access token to file
                $path = __DIR__ . '/Data/accessToken.txt';
                file_put_contents( $path, $response['access_token'] );
                echo "Access token generated and saved successfully";
            } else {
                // Handle the case if access token is not found in the response
                echo "Error: Access token not found in response";
            }
        } catch (PDOException $e) {
            // Handle PDO exceptions
            echo "Error: " . $e->getMessage();
        }
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

    public function insertProductToDatabase() {
        // require config file
        require_once 'config.php';

        try {
            // Truncate the products table to remove previous products
            $truncate_sql  = "TRUNCATE TABLE products";
            $truncate_stmt = $conn->prepare( $truncate_sql );
            $truncate_stmt->execute();

            // Fetch products from Google Sheets
            $sheetData = $this->fetchProductsFromSheets();

            // Prepare the SQL statement for inserting products
            $sql  = "INSERT INTO products (sku, sid, stock, price, status) VALUES (:sku, :sid, :stock, :price, :status)";
            $stmt = $conn->prepare( $sql );

            // Begin transaction
            $conn->beginTransaction();

            // Insert products into database
            foreach ( $sheetData as $sheetDatum ) {
                // Retrieve product data
                $sku    = $sheetDatum[0];
                $sid    = $sheetDatum[1];
                $stock  = $sheetDatum[2];
                $price  = $sheetDatum[3];
                $status = 'pending';

                // Bind parameters
                $stmt->bindParam( ':sku', $sku );
                $stmt->bindParam( ':sid', $sid );
                $stmt->bindParam( ':stock', $stock );
                $stmt->bindParam( ':price', $price );
                $stmt->bindParam( ':status', $status );

                // Execute the SQL statement
                $stmt->execute();
            }

            // Commit the transaction
            $conn->commit();

            echo "Products inserted successfully to Database";
        } catch (PDOException $e) {
            // Rollback the transaction if something failed
            $conn->rollBack();
            echo "Error: " . $e->getMessage();
        }

        // Close the database connection
        $conn = null;
    }

    public function fetchProductFromDatabase() {
        // require config file
        require_once 'config.php';

        try {
            // Fetch products from database
            $sql  = "SELECT * FROM products WHERE status = 'pending' LIMIT 40";
            $stmt = $conn->prepare( $sql );
            $stmt->execute();

            // Fetch the rows from the result set
            $products = $stmt->fetchAll( PDO::FETCH_ASSOC );

            // Close the database connection
            $conn = null;

            // Return the fetched products
            return $products;
        } catch (PDOException $e) {
            // Handle the error if query failed
            echo "Error: " . $e->getMessage();
            return false;
        }
    }

    public function updateProductStockProto( $sku, $sid, $stock ) {

        // get access token
        $accessToken = file_get_contents( __DIR__ . '/Data/accessToken.txt' );

        // product array
        $productArray = [
            "products" => [
                [
                    "sellerSku" => $sku,
                    "id"        => $sid,
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
                    'Authorization: Bearer ' . $accessToken,
                    'Content-Type: application/json',
                    'Cookie: __cf_bm=OtHRyWyEqMVWGVYkrwfm.URc3oCI05Hga2SgJ85NY_g-1714219200-1.0.1.1-y3od0XgXWab8h5MqqcqE7la1_K.qXR1gM0j4rRtAHjiSIt5U8lV_9MKH3fIl36QLc9kwPmfE1yO8IGLxmEZQZQ',
                ),
            )
        );

        $response = curl_exec( $curl );

        curl_close( $curl );
        return $response;
    }

    public function updateProductsStock() {

        // fetch products from database
        $products = $this->fetchProductFromDatabase();

        foreach ( $products as $product ) {

            // retrieve product data
            $sku   = $product['sku'];
            $id    = $product['sid'];
            $stock = $product['stock'];

            // update stock
            echo $this->updateProductStockProto( $sku, $id, $stock );

            // update status to completed
            $this->updateProductStatus( $id, 'stockCompleted' );

        }
    }

    public function updateProductPriceProto( $sku, $sid, $price ) {

        // get access token
        $accessToken = file_get_contents( __DIR__ . '/Data/accessToken.txt' );

        // product array
        $productArray = [
            "products" => [
                [
                    "sellerSku"       => "$sku",
                    "id"              => "$sid",
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
                    'Authorization: Bearer ' . $accessToken,
                    'Content-Type: application/json',
                    'Cookie: __cf_bm=z3K9NrE2Gay_MAHsE9uQHdyaMuJflcf4O5LMC.12cGM-1714276081-1.0.1.1-ZUAYpc4xlTXwxVX3IHXIiKZjaJzFKFbNTqDIrtnppaoONYpr6XHJ8WmJ.d7lxRZkf5c_goQyQDGx1FbLLQELow',
                ),
            )
        );

        $response = curl_exec( $curl );

        curl_close( $curl );
        return $response;

    }

    public function updateProductsPrice() {

        // fetch products from database
        $products = $this->fetchProductFromDatabase();

        foreach ( $products as $product ) {

            // retrieve product data
            $id    = $product['id'];
            $sku   = $product['sku'];
            $sid   = $product['sid'];
            $price = $product['price'];

            // update price
            echo $this->updateProductPriceProto( $sku, $sid, $price );

            // update status to completed
            $this->updateProductStatus( $id, 'priceCompleted' );
        }
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

            // Update stock
            echo $this->updateProductStockProto( $sku, $sid, $stock );

            // Update price
            echo $this->updateProductPriceProto( $sku, $sid, $price );

            // Update status to completed
            $this->updateProductStatus( $id, 'completed' );

            echo "Product Updated <br>";
        }
    }

    private function updateProductStatus( $id, $status ) {
        // require config file
        require_once 'config.php';

        try {
            // Update product status
            $sql  = "UPDATE products SET status = :status WHERE id = :id";
            $stmt = $conn->prepare( $sql );
            $stmt->bindParam( ':status', $status );
            $stmt->bindParam( ':id', $id );
            $stmt->execute();

            // Close the database connection
            $conn = null;
        } catch (PDOException $e) {
            // Handle the error if query failed
            echo "Error updating status: " . $e->getMessage();
        }
    }

    public function getProductStatus() {

        // get access token
        $accessToken = file_get_contents( __DIR__ . '/Data/accessToken.txt' );

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
                    'Authorization: Bearer ' . $accessToken,
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

// generate access token
// $productSync->generateAccessToken();

// get product status
// $productSync->getProductStatus();

// update product stocks
// $productSync->updateProductStock();

// update product price
// $productSync->updateProductPrice();

// get product from sheet
// echo '<pre>';
// print_r( $productSync->fetchProductsFromSheets() );

// push product info to sheet
// $productSync->pushProductInfoToSheet();

// insert products to database
// $productSync->insertProductToDatabase();

// fetch products from database
// echo '<pre>';
// print_r( $productSync->fetchProductFromDatabase() );