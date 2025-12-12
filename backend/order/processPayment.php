<?php
header("Access-Control-Allow-Origin: https://frontend.toko.com");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type, Accept");
header("Access-Control-Allow-Methods: POST, OPTIONS");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

session_set_cookie_params([
    "samesite" => "None",
    "secure" => true,
    "httponly" => true
]);
session_start();

require_once __DIR__ . '/../config/db.php';

$user_id = $_SESSION['user_id'] ?? null;
$selected = $_SESSION['checkout_ids'] ?? null;

if (!$user_id || !$selected) {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

$payment_method = $_POST['method'] ?? null;
$wallet_type = $_POST['ewallet_type'] ?? null;
$total_price = $_POST['total_price'] ?? 0;

// INSERT TRANSACTION
$stmt = $mysqli->prepare("
    INSERT INTO transactions (user_id, total_price, status, date_created)
    VALUES (?, ?, 'Waiting for Payment', NOW())
");
$stmt->bind_param("id", $user_id, $total_price);
$stmt->execute();
$transaction_id = $mysqli->insert_id;
$stmt->close();

// INSERT DETAIL
$ids = implode(",", array_map('intval', array_keys($selected)));
$result = $mysqli->query("
    SELECT c.quantity, c.size, c.product_id, p.price
    FROM cart c
    JOIN products p ON c.product_id = p.id
    WHERE c.user_id = $user_id AND c.id IN ($ids)
");

while ($row = $result->fetch_assoc()) {
    $stmt2 = $mysqli->prepare("
        INSERT INTO detail_transaction
        (transaction_id, product_id, size, price, quantity)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt2->bind_param(
        "iisdi",
        $transaction_id,
        $row['product_id'],
        $row['size'],
        $row['price'],
        $row['quantity']
    );
    $stmt2->execute();
    $stmt2->close();
}

$mysqli->query("DELETE FROM cart WHERE user_id = $user_id AND id IN ($ids)");
unset($_SESSION['checkout_ids'], $_SESSION['shipping']);

if ($payment_method === "cod") {
    $mysqli->query("UPDATE transactions SET status='Payment Success' WHERE id=$transaction_id");
}

header("Content-Type: application/json");
echo json_encode([
    "status" => "success",
    "transaction_id" => $transaction_id,
    "payment_method" => $payment_method,
    "ewallet_type" => $wallet_type
]);
exit;
