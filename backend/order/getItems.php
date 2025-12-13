<?php
session_start();
require_once __DIR__ . '/../config/db.php';

header("Content-Type: application/json");

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["ok" => false, "msg" => "Not logged in"]);
    exit;
}

if (!isset($_SESSION['checkout_ids']) || empty($_SESSION['checkout_ids'])) {
    echo json_encode(["ok" => false, "msg" => "No checkout items"]);
    exit;
}

$user_id = $_SESSION['user_id'];
$ids = implode(",", array_map('intval', array_keys($_SESSION['checkout_ids'])));

$q = $mysqli->prepare("
    SELECT c.id AS cart_id, c.quantity, c.size,
           p.id AS product_id, p.name, p.price, p.image
    FROM cart c
    JOIN products p ON p.id = c.product_id
    WHERE c.user_id = ?
    AND c.id IN ($ids)
");
$q->bind_param("i", $user_id);
$q->execute();
$res = $q->get_result();

$items = [];
while ($row = $res->fetch_assoc()) {
    $items[] = $row;
}

echo json_encode([
    "ok" => true,
    "data" => [ "items" => $items ]
]);
