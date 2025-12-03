<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if(!isset($_POST['product_id']) || !isset($_POST['size'])) {
    die("Invalid request");
}

$product_id = intval
?>