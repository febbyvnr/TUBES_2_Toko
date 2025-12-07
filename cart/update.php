<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if(!isset($_POST['cart_id']) || !isset($_POST['quantity'])) {
    die("Invalid request");
}

$cart_id = intval($_POST['cart_id']);
$qty = intval($_POST['quantity']);

$stmt = $mysqli->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
$stmt->bind_param("ii",  $qty, $cart_id);
$stmt->execute();

header("Location: listCart.php");
exit;
?>