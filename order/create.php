<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

$sql = "SELECT c.id AS cart_id, c.quantity,
               p.id AS product_id, p.name, p.price, p.stock
        FROM cart c
        JOIN products p ON c.product_id = p.id
        WHERE c.user_id = $user_id";
$result = $mysqli->query($sql);

$items = [];
$total_price = 0;

while ($row = $result->fetch_assoc()) {
    // ini untuk cek stok
    if ($row['quantity'] > $row['stock']) {
        die("Stok kurang untuk produk: " . $row['name']);
    }

    $items[] = $row;
    $total_price += $row['price'] * $row['quantity'];
}

if (empty($items)) {
    die("Keranjang kosong, tidak bisa checkout.");
}

// mulai transaksi
$mysqli->begin_transaction();

try {
    // buat order baru
    $mysqli->query("
        INSERT INTO orders (user_id, total_price, status)
        VALUES ($user_id, $total_price, 'pending')
    ");

    $order_id = $mysqli->insert_id;

    foreach ($items as $item) {

        $mysqli->query("
            INSERT INTO order_items (order_id, product_id, quantity, price)
            VALUES ($order_id, {$item['product_id']}, {$item['quantity']}, {$item['price']})
        ");

        $mysqli->query("
            UPDATE products 
            SET stock = stock - {$item['quantity']}
            WHERE id = {$item['product_id']}
        ");

        $mysqli->query("
            DELETE FROM cart WHERE id = {$item['cart_id']}
        ");
    }

    $mysqli->commit();

    header("Location: listOrder.php?order_id=" . $order_id);
    exit;

} catch (Exception $e) {
    $mysqli->rollback();
    die("Gagal membuat order: " . $e->getMessage());
}
