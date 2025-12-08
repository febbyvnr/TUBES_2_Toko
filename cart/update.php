<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if(!isset($_POST['cart_id']) || !isset($_POST['quantity'])) {
    die("Invalid request");
}

$cart_id = (int) $_POST['cart_id'];
$qty = intval($_POST['quantity']);
$action = $_POST['action'];

$user_id = (int) $_SESSION['user_id'];
$check = $mysqli->prepare("SELECT id, quantity FROM cart WHERE id = ? AND user_id = ?");
$check->bind_param("ii", $cart_id, $user_id);
$check->execute();
$res = $check->get_result();
if ($res->num_rows === 0) {
    die("Cart item not found");
}
$row = $res->fetch_assoc();


if ($action === 'plus') {
    $qty++;
} else if ($action === 'minus') {
    $qty = max(1, $qty - 1); // jangan kurang dari 1
} else {
    die("Invalid action");
}

//update ke db
$stmt = $mysqli->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
$stmt->bind_param("ii",  $qty, $cart_id);
$stmt->execute();

header("Location: listCart.php");
exit;
?>