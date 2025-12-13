<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

$user_id  = (int) $_SESSION['user_id'];
$cart_id  = (int) $_POST['cart_id'];
$action   = $_POST['action'];

// Ambil quantity lama
$stmt = $mysqli->prepare("SELECT quantity FROM cart WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $cart_id, $user_id);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();

if (!$res) {
    header("Location: listCart.php");
    exit;
}

$qty = (int)$res['quantity'];

// Hitung quantity baru
if ($action === "plus") {
    $qty++;
} elseif ($action === "minus") {
    $qty--;
}

// quantity = 0 → hapus item
if ($qty <= 0) {
    $del = $mysqli->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
    $del->bind_param("ii", $cart_id, $user_id);
    $del->execute();
    header("Location: listCart.php");
    exit;
}

// Update quantity baru
$update = $mysqli->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?");
$update->bind_param("iii", $qty, $cart_id, $user_id);
$update->execute();

header("Location: listCart.php");
exit;