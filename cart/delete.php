<?php
session_start();

if(!isset($_GET['key'])) {
    die("Invalid request");
}

$key = $_GET['key'];

if(isset($_SESSION['cart']['key'])) {
    unset($_SESSION['cart'][$key]);
}

header("Location: listCart.php");
exit;
?>