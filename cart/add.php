<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if(!isset($_POST['product_id']) || !isset($_POST['size'])) {
    die("Invalid request");
}

$product_id = intval($_POST['product_id']);
$size = $_POST['size'];
$qty = isset($_POST['qty']) ? intval($_POST['qty']) : 1;

//ambil data produk
$stmt = $mysqli->prepare("SELECT id, name, price FROM product WHERE id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if(!$product) {
    die("Product not found");
}

//Jika cart belum ada, buat baru
if(!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

//productID + size
$key = $product_id . "_" . $size;

//Jika sudah ada, tambahkan qty
if(isset($_SESSION['cart'][$key])) {
    $_SESSION['cart'][$key]['qty'] += $qty;
}else{
    $SESSION['cart'][$key] = [
        "id" => $product['id'],
        "name" => $product['name'],
        "size" => $size,
        "qty" => $qty,
        "price" => $product['price']
    ];
}

header("Location: listCart.php");
exit;
?>