<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if(!isset($_GET['id'])) {
    die("Invalid request");
}

$cart_id = intval($_GET['id']);

$stmt = $mysqli->prepare("DELETE FROM cart WHERE id = ?");
$stmt->bind_param("i", $cart_id);
$stmt->execute();

header("Location: listCart.php");
exit;
?>