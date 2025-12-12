<?php
header("Access-Control-Allow-Origin: https://frontend.toko.com");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

require_once __DIR__ . '/../config/db.php';

if (!isset($_GET['transaction_id'])) {
    echo json_encode(["error" => "Transaction ID missing"]);
    exit;
}

$id = (int) $_GET['transaction_id'];

$stmt = $mysqli->prepare("
    SELECT total_price, date_created
    FROM transactions
    WHERE id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$trx = $stmt->get_result()->fetch_assoc();

if (!$trx) {
    echo json_encode(["error" => "Transaction not found"]);
    exit;
}

/* DATA */
$total = (int) $trx['total_price'];

/* VA BCA – SAMA KAYA KODE LAMA */
$va_number = "126" . str_pad($id, 10, "0", STR_PAD_LEFT);

/* DEADLINE */
$deadline_ts = strtotime("+24 hours", strtotime($trx['date_created'])) * 1000;
$deadline = date("d M Y, H:i", strtotime("+24 hours", strtotime($trx['date_created'])));

/* RESPONSE (FORMAT MIRIP EWALLET) */
echo json_encode([
    "transaction_id" => $id,
    "bank"           => "BCA",
    "total"          => $total,
    "va_number"      => $va_number,
    "deadline"       => $deadline,
    "deadline_ts"    => $deadline_ts
]);
exit;
