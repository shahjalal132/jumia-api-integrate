<?php

$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'test';

$conn = mysqli_connect( $host, $user, $pass, $db ) or die( 'Connection Failed' );