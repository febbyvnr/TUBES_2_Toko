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
        JOIN products p ON p.id = c.product_id
        WHERE c.user_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$items = $stmt->fetchAll();

if (!$items) {
    echo "Keranjang kosong!";
    exit;
}

$total_price = 0;

foreach ($items as $item) {
    if ($item['quantity'] > $item['stock']) {
        echo "Stok tidak cukup untuk produk: <b>{$item['name']}</b>";
        exit;
    }
    $total_price += $item['price'] * $item['quantity'];
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("INSERT INTO orders (user_id, total_price, status)
                           VALUES (?, ?, 'pending')");
    $stmt->execute([$user_id, $total_price]);

    $order_id = $pdo->lastInsertId();

    $insertItem = $pdo->prepare(
        "INSERT INTO order_items (order_id, product_id, quantity, price)
         VALUES (?, ?, ?, ?)"
    );
    $updateStock = $pdo->prepare(
        "UPDATE products SET stock = stock - ? WHERE id = ?"
    );
    $deleteCart = $pdo->prepare("DELETE FROM cart WHERE id = ?");

    foreach ($items as $item) {

        $insertItem->execute([
            $order_id,
            $item['product_id'],
            $item['quantity'],
            $item['price']
        ]);

        // Kurangi stok
        $updateStock->execute([
            $item['quantity'],
            $item['product_id']
        ]);

        // Hapus cart
        $deleteCart->execute([$item['cart_id']]);
    }

    $pdo->commit();

    header("Location: listOrder.php?order_id=" . $order_id);
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    echo "Error saat checkout: " . $e->getMessage();
}
