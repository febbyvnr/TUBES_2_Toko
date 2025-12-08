<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if(!isset($_SESSION['user_id'])) {
    die("Anda harus login.");
}

if(!isset($_POST['product_id'])) {
    die("Invalid request");
}

$user_id = $_SESSION['user_id'];
$product_id = intval($_POST['product_id']);
$qty = isset($_POST['qty']) ? intval($_POST['qty']) : 1;

//cek apakah item sudah ada di cart
$stmt = $mysqli->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
$stmt->bind_param("ii", $user_id, $product_id);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows > 0) {
    //update quantity
    $row = $result->fetch_assoc();
    $newQty = $row['quantity'] + $qty;

    $update = $mysqli->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
    $update->bind_param("ii", $newQty, $row['id']);
    $update->execute();
} else {
    //insert baru
    $insert = $mysqli->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?,?,?)");
    $insert->bind_param("iii", $user_id, $product_id, $qty);
    $insert->execute();
}

header("Location: listCart.php");
exit;

?>


