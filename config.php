<?php

$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'test';

try {
    $conn = new PDO( "mysql:host=$host;dbname=$db;charset=utf8", $user, $pass );
    // Set the PDO error mode to exception
    $conn->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
} catch (PDOException $e) {
    die( 'Connection Failed: ' . $e->getMessage() );
}