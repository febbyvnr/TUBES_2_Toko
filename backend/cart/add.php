<?php
session_start();
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$product_id = (int)$_POST['product_id'];
$size = trim($_POST['size']);
$qty = (int)($_POST['qty'] ?? 1);

$stmt = $mysqli->prepare(
    "SELECT id, quantity FROM cart WHERE user_id=? AND product_id=? AND size=?"
);
$stmt->bind_param("iis", $user_id, $product_id, $size);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows > 0) {
    $row = $res->fetch_assoc();
    $newQty = $row['quantity'] + $qty;

    $up = $mysqli->prepare("UPDATE cart SET quantity=? WHERE id=?");
    $up->bind_param("ii", $newQty, $row['id']);
    $up->execute();
} else {
    $ins = $mysqli->prepare(
        "INSERT INTO cart (user_id, product_id, size, quantity) VALUES (?,?,?,?)"
    );
    $ins->bind_param("iisi", $user_id, $product_id, $size, $qty);
    $ins->execute();
}

echo json_encode(['success' => true]);
