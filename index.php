<?php

require_once __DIR__ . '/vendor/autoload.php';

class ProductSync {
    private $client;
    private $service;
    private $spreadsheetID;
    private $sheetRange;
    private $credentialsPath = __DIR__ . '/credentials.json';
    private $accessToken;

    public function __construct() {
        $this->client = new Google\Client();
        $this->client->setApplicationName( "goglesheetapi" );
        $this->client->setScopes( [ \Google_Service_Sheets::SPREADSHEETS ] );
        $this->client->setAccessToken( 'offline' );
        $this->client->setAuthConfig( $this->credentialsPath );
        $this->service       = new Google_Service_Sheets( $this->client );
        $this->spreadsheetID = '1igZQ5L-FlY7FTzqMpxPOzbscWLYo15hLW5s9YHwPRD4';
        $this->sheetRange    = 'products!A:E';
        $this->accessToken   = 'eyJhbGciOiJSUzI1NiIsInR5cCIgOiAiSldUIiwia2lkIiA6ICJtU0VNMmNQX0h4UldDTl9Lcm1Hal9zVjRRZWt6eUU1VTVlR2drUk5SOElNIn0.eyJqdGkiOiIzOGJmMjE2OS0zODhiLTQxNjctOGUzZC0zZjRlOGYyOTI5YTYiLCJleHAiOjE3MTM5OTQ4ODYsIm5iZiI6MCwiaWF0IjoxNzEzOTUxNjg2LCJpc3MiOiJodHRwczovL3ZlbmRvci1hcGktc3RhZ2luZy5qdW1pYS5jb20vYXV0aC9yZWFsbXMvYWNsIiwic3ViIjoiYWIxM2U1YmMtMTE4Ni00MGU5LTg3MDEtN2IzMTlkNDM3ZmFlIiwidHlwIjoiQmVhcmVyIiwiYXpwIjoiNzQwYjlhNmQtMWY5MS00Njg3LTgyNTAtZTQ5YjAxNTlkZjQwIiwiYXV0aF90aW1lIjowLCJzZXNzaW9uX3N0YXRlIjoiYmU4MDYwOWUtYTUzYi00ZmI5LTkxYzMtZDM3OGE1ZGU2MTc1IiwiYWNyIjoiMSIsInNjb3BlIjoicHJvZmlsZSBlbWFpbCIsImVtYWlsX3ZlcmlmaWVkIjp0cnVlLCJwcmVmZXJyZWRfdXNlcm5hbWUiOiJiZW9iaXAyMDIwK3N0Z3Rlc3RAZ21haWwuY29tIiwibG9jYWxlIjoiZW4iLCJlbWFpbCI6ImJlb2JpcDIwMjArc3RndGVzdEBnbWFpbC5jb20ifQ.EjP5a5Vw063HE-QW6zD0H3X3sQK7Uo4RCdLNnFve-bA773OiHab9CwPHamEQKCsuSN_qnRqzEeHqvrIrJeL6a-1Hn_UxLYLufaFhZJiA204q07fm7-yykmgtzlALuskpngsuCG_1ds80qNNj4XpVxHhNhfRQnbVJLNyyU5wHH3CQRaqQwO9kmnJs_RZDWjByBnZxBN-KRSTeluV5sG1Qb5oD43g7LxRRgHHpcSUVVSrhI0Ab8-a0Hyzm1r3OH-or4dz5XwAgvKBTcZSarV0G1Y0yQdV4_UZbCHkjOosGmICrXZPql8byVxznlRE3C0KOVKmMsXSy0SY71zCJvQbFjA';
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
        return $response;
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
        foreach ( $vendorProducts as $vendorProduct ) {
            $vendorSku = $vendorProduct['id'];
            $found     = false;

            foreach ( $googleProducts as $googleProduct ) {
                $googleSku = $googleProduct[2];

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

    public function synchronizeProducts() {
        // Fetch products from the API
        $vendorProducts = $this->fetchProductsFromApi();

        // Check if data is fetched successfully
        if ( empty( $vendorProducts ) ) {
            print ( "No data found from vendor API.\n" );
        } else {
            // Fetch products from Google Sheets
            $googleProducts = $this->fetchProductsFromSheets();

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

$productSync    = new ProductSync();
$ProductFromApi = $productSync->fetchProductsFromApi();

echo '<pre>';
print_r( $ProductFromApi );
die();

