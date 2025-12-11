<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id']) || empty($_SESSION['checkout']) || empty($_POST['payment_method'])) {
    header('Location: payment.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$payment_method = $_POST['payment_method'];
$shipping = $_SESSION['shipping'];
$items = $_SESSION['checkout'];

// Hitung total harga
$total = 0;
foreach ($items as $i) {
    $total += $i['price'] * $i['quantity'];
}

// =====================
// 1. INSERT INTO ORDERS
// =====================
$queryOrder = "
    INSERT INTO orders (user_id, total_price, payment_method, name, phone, address, city, postal_code, created_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
";

$stmt = $mysqli->prepare($queryOrder);
$stmt->bind_param(
    "idssssss",
    $user_id,
    $total,
    $payment_method,
    $shipping['name'],
    $shipping['phone'],
    $shipping['address'],
    $shipping['city'],
    $shipping['postal_code']
);
$stmt->execute();
$order_id = $stmt->insert_id;
$stmt->close();

// =====================
// 2. INSERT ORDER ITEMS
// =====================
$queryItem = "
    INSERT INTO order_items (order_id, product_name, price, quantity)
    VALUES (?, ?, ?, ?)
";

$stmtItem = $mysqli->prepare($queryItem);

foreach ($items as $i) {
    $stmtItem->bind_param("isdi", $order_id, $i['name'], $i['price'], $i['quantity']);
    $stmtItem->execute();
}

$stmtItem->close();

// =====================
// 3. CLEAR USER CART
// =====================
$clearCart = $mysqli->prepare("DELETE FROM cart WHERE user_id = ?");
$clearCart->bind_param("i", $user_id);
$clearCart->execute();
$clearCart->close();

// =====================
// 4. CLEAR SESSION CHECKOUT
// =====================
unset($_SESSION['checkout']);

// =====================
// 5. REDIRECT TO SUCCESS PAGE
// =====================
header("Location: paymentSuccess.php?order_id=" . $order_id);
exit;
