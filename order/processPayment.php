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

// capture metode pembayaran
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

// 2. Ambil semua item keranjang yang dicheckout
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

// 3. Insert ke detail_transaction
while ($row = $result->fetch_assoc()) {
    $stmt2 = $mysqli->prepare("
        INSERT INTO detail_transaction (transaction_id, product_id, size, price, quantity)
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

unset($_SESSION['checkout_ids']);
unset($_SESSION['shipping']);

// 5. REDIRECT BERDASARKAN METODE PEMBAYARAN
if ($payment_method === "bank") {
    header("Location: transferBank.php?transaction_id=" . $transaction_id);
    exit;
}

$wallet_type = $_POST['ewallet_type'] ?? null;

if ($payment_method === "ewallet") {
    if (!$wallet_type) {
        die("Please select an E-Wallet type!");
    }

    header("Location: ewallet.php?transaction_id=$transaction_id&type=$wallet_type");
    exit;
}

if ($payment_method === "cod") {
    $stmt3 = $mysqli->prepare("UPDATE transactions SET status='success' WHERE id=?");
    $stmt3->bind_param("i", $transaction_id);
    $stmt3->execute();

    header("Location: paymentSuccess.php?transaction_id=" . $transaction_id);
    exit;
}




// // 6. Redirect ke payment success

// header("Location: paymentSuccess.php?trx=" . $transaction_id);
// exit;
