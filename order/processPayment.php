<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['checkout_ids'])) {
    header("Location: ../cart/listCart.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$selected = $_SESSION['checkout_ids'];
$shipping = $_SESSION['shipping'] ?? null;

if (!$shipping) {
    header("Location: checkout.php");
    exit;
}

// Tangkap metode pembayaran
$payment_method = $_POST['method'] ?? null;
$total_price = $_POST['total_price'] ?? 0;

if (!$payment_method) {
    die("Payment method required");
}

// 1. Insert into transactions
$stmt = $mysqli->prepare("
    INSERT INTO transactions (user_id, total_price, status, date_created)
    VALUES (?, ?, 'pending', NOW())
");
$stmt->bind_param("id", $user_id, $total_price);
$stmt->execute();

$transaction_id = $mysqli->insert_id;
$stmt->close();

// 2. Ambil semua item keranjang yang di-checkout
$ids = implode(",", array_map('intval', array_keys($selected)));

$query = $mysqli->prepare("
    SELECT c.id AS cart_id, c.quantity, c.size, c.product_id,
           p.price
    FROM cart c
    JOIN products p ON c.product_id = p.id
    WHERE c.user_id = ?
    AND c.id IN ($ids)
");
$query->bind_param("i", $user_id);
$query->execute();
$result = $query->get_result();

// 3. Insert ke detail_transactions
while ($row = $result->fetch_assoc()) {
    $stmt2 = $mysqli->prepare("
        INSERT INTO detail_transaction (transactions_id, product_id, size, price, quantity)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt2->bind_param(
        "iisdi",
        $transaction_id,
        $row['product_id'],
        $row['size'],
        $row['price'],
        $row['quantity']
    );
    $stmt2->execute();
    $stmt2->close();
}

// 4. Hapus item dari cart
$mysqli->query("DELETE FROM cart WHERE user_id = $user_id AND id IN ($ids)");

// 5. Bersihkan checkout session
unset($_SESSION['checkout_ids']);
unset($_SESSION['shipping']);

// 6. Redirect ke payment success
header("Location: paymentSuccess.php?trx=" . $transaction_id);
exit;
