<?php
session_start();
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');

$user_id = $_SESSION['user_id'];
$cart_id = (int)$_POST['cart_id'];
$action  = $_POST['action'];

$stmt = $mysqli->prepare(
    "SELECT quantity FROM cart WHERE id=? AND user_id=?"
);
$stmt->bind_param("ii", $cart_id, $user_id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

if (!$row) {
    echo json_encode(['error' => 'Not found']);
    exit;
}

$qty = $row['quantity'];
$qty += ($action === 'plus') ? 1 : -1;

if ($qty <= 0) {
    $del = $mysqli->prepare("DELETE FROM cart WHERE id=? AND user_id=?");
    $del->bind_param("ii", $cart_id, $user_id);
    $del->execute();
} else {
    $up = $mysqli->prepare(
        "UPDATE cart SET quantity=? WHERE id=? AND user_id=?"
    );
    $up->bind_param("iii", $qty, $cart_id, $user_id);
    $up->execute();
}

echo json_encode(['success' => true]);
