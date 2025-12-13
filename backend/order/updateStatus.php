<?php
session_start();
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["ok" => false, "message" => "Not logged in"]);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$mode = $_GET['mode'] ?? "";

if ($id <= 0) {
    http_response_code(400);
    echo json_encode(["ok" => false, "message" => "Invalid transaction ID"]);
    exit;
}

// update status hanya kalau transaksi milik user
$stmt = $mysqli->prepare("
    UPDATE transactions
    SET status = 'Payment Success'
    WHERE id = ? AND user_id = ?
");
$stmt->bind_param("ii", $id, $user_id);
$stmt->execute();

if ($stmt->errno) {
    http_response_code(500);
    echo json_encode(["ok" => false, "message" => "DB error", "error" => $stmt->error]);
    exit;
}

// HAPUS CART item yang sedang checkout (kalau ada)
$selected = $_SESSION['checkout_ids'] ?? [];
$cartIds = array_map('intval', array_keys($selected));

if (!empty($cartIds)) {
    $in = implode(',', $cartIds);
    $del = $mysqli->prepare("DELETE FROM cart WHERE user_id = ? AND id IN ($in)");
    $del->bind_param("i", $user_id);
    $del->execute();
}

// bersihin session checkout (biar ga kepakai lagi)
unset($_SESSION['checkout_ids']);
// optional: kalau mau transaksi di session juga dibersihin
// unset($_SESSION['transaction_id']);

if ($mode === "json") {
    echo json_encode(["ok" => true, "transaction_id" => $id]);
    exit;
}

// kalau bukan json, redirect ke halaman sukses
header("Location: /TUBES_2_Toko/frontend/order/payment-success.html?transaction_id=" . $id);
exit;