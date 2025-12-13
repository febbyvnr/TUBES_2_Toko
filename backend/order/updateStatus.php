<?php
session_start();
require_once __DIR__ . '/../config/db.php';

$id = $_GET['id'] ?? null;

if (!$id) {
    die("Invalid transaction ID");
}

$stmt = $mysqli->prepare("
  UPDATE transactions
  SET status = 'Payment Success'
  WHERE id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();

header("Location: /TUBES_2_Toko/frontend/order/paymentSuccess.html?transaction_id=".$id);
exit;
