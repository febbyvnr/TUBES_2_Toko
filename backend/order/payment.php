<?php
header("Content-Type: application/json");
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['shipping'], $_SESSION['checkout_ids'], $_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

$shipping = $_SESSION['shipping'];
$user_id = $_SESSION['user_id'];
$selected = $_SESSION['checkout_ids'];

$ids = implode(",", array_map('intval', array_keys($selected)));

$query = $mysqli->query("
    SELECT c.quantity, c.size, p.name, p.price, p.image
    FROM cart c
    JOIN products p ON c.product_id = p.id
    WHERE c.user_id = $user_id AND c.id IN ($ids)
");

$items = [];
$subtotal = 0;
while ($row = $query->fetch_assoc()) {
    $row['subtotal'] = $row['price'] * $row['quantity'];
    $items[] = $row;
    $subtotal += $row['subtotal'];
}

echo json_encode([
    "shipping" => [
        "firstname" => $shipping['firstname'],
        "lastname"  => $shipping['lastname'],
        "email"     => $shipping['email'],
        "address"   => $shipping['address']
    ],
    "items" => $items,
    "subtotal" => $subtotal,
    "shipping_cost" => 5000,
    "admin_fee" => 2000,
    "total" => $subtotal + 7000
]);
