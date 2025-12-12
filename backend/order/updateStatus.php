<?php
session_start();
require_once __DIR__ . '/../config/db.php';

$id = $_GET['id'] ?? null;

if (!$id) {
    die("Invalid transaction ID");
}

/* UPDATE STATUS */
$stmt = $mysqli->prepare("
  UPDATE transactions
  SET status = 'Payment Success'
  WHERE id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();

/* REDIRECT KE FILE YANG BENAR */
header("Location: /TUBES_2_Toko/backend/order/paymentSuccess.php?transaction_id=".$id);
exit;
