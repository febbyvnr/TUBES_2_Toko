<?php
header("Access-Control-Allow-Origin: https://frontend.toko.com");
header("Access-Control-Allow-Credentials: true");

require_once __DIR__ . '/../config/db.php';

$id = intval($_GET['transaction_id'] ?? 0);
$stmt = $mysqli->prepare("SELECT total_price, date_created, status FROM transactions WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();

header("Content-Type: application/json");
echo json_encode([
    "transaction_id" => $id,
    "total_price" => $res['total_price'],
    "date_created" => $res['date_created'],
    "status" => $res['status']
]);
exit;
