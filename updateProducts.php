<?php

// require main file
require_once "main.php";

$productSync = new ProductSync();
$productSync->updateStockPrice();