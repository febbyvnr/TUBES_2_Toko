<?php
session_start();
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([]);
    exit;
}

$user_id = $_SESSION['user_id'];

$sql = "
SELECT cart.id AS cart_id, products.name, products.price,
       products.image, cart.size, cart.quantity
FROM cart
JOIN products ON cart.product_id = products.id
WHERE cart.user_id = ?
";

$stmt = $mysqli->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();

echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
