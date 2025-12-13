<?php
header("Access-Control-Allow-Origin: https://frontend.toko.com");
header("Access-Control-Allow-Credentials: true");

require_once __DIR__ . '/../config/db.php';

$id = intval($_GET['transaction_id']);
$type = $_GET['type'] ?? "Dana";

$stmt = $mysqli->prepare("SELECT total_price, date_created FROM transactions WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$trx = $stmt->get_result()->fetch_assoc();

$wallet = "08" . str_pad($id, 10, "5", STR_PAD_LEFT);
$deadline = date("Y-m-d H:i:s", strtotime("+24 hours", strtotime($trx['date_created'])));

header("Content-Type: application/json");
echo json_encode([
    "transaction_id" => $id,
    "wallet_type" => $type,
    "total" => $trx['total_price'],
    "wallet_number" => $wallet,
    "deadline" => $deadline
]);
exit;
