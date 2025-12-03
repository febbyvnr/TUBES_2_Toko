<?php
session_start();

if(!isset($_POST['key']) || !isset($_POST['qty'])) {
    die("Invalid request");
}

$key = $_POST['key'];
$qty = intval($_POST['qty']);

if($qty <= 0) {
    unset($_SESSION['cart'][$key]);
} else {
    $_SESSION['cart'][$key]['qty'] = $qty;
}

header("Location: listCart.php");
exit;
?>