<?php

require_once __DIR__ . '/vendor/autoload.php';

$client = new Google\Client();
$client->setApplicationName( "goglesheetapi" );
$client->setScopes( [ \Google_Service_Sheets::SPREADSHEETS ] );
$client->setAccessToken( 'offline' );
$client->setAuthConfig( __DIR__ . '/credentials.json' );
$service       = new Google_Service_Sheets( $client );
$spreadsheetID = "1igZQ5L-FlY7FTzqMpxPOzbscWLYo15hLW5s9YHwPRD4";

$range    = "products!A:E";
$response = $service->spreadsheets_values->get( $spreadsheetID, $range );
$values   = $response->getValues();

if ( empty( $values ) ) {
    print ( "No data found.\n" );
} else {
    foreach ( $values as $row ) {
        echo '<pre>';
        print_r( $row );
    }
}