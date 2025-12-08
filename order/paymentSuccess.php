<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_GET['order_id'])) {
    header('Location: /TUBES_2_Toko/index.php');
    exit;
}

$order_id = (int) $_GET['order_id'];

// Ambil data order
$query = "SELECT * FROM orders WHERE id = ?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Ambil item order
$queryItems = "SELECT * FROM order_items WHERE order_id = ?";
$stmtItems = $mysqli->prepare($queryItems);
$stmtItems->bind_param("i", $order_id);
$stmtItems->execute();
$items = $stmtItems->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtItems->close();
?>

<!doctype html>
<html lang="en">

<?php
  $pageTitle   = 'Payment Success — FEYORA';
  $extraStyles = '<link rel="stylesheet" href="/TUBES_2_Toko/styles/paymentSuccess.css?v=' . time() . '">';
  include __DIR__ . '/../includes/head.php';
?>

<body>

<?php include __DIR__ . '/../includes/header.php'; ?>

<main class="success-page">

<div class="success-container">
    
    <h2>Order Successful 🎉</h2>
    <h3>Order ID: <?= $order_id ?></h3>

    <p><strong>Payment Method:</strong> <?= htmlspecialchars($order['payment_method']) ?></p>

    <!-- SHIPPING -->
    <div class="section">
        <h3>Shipping Address</h3>
        <p><?= htmlspecialchars($order['name']) ?></p>
        <p><?= htmlspecialchars($order['phone']) ?></p>
        <p><?= htmlspecialchars($order['address']) ?>,
           <?= htmlspecialchars($order['city']) ?>,
           <?= htmlspecialchars($order['postal_code']) ?></p>
    </div>

    <!-- ITEMS -->
    <div class="section">
        <h3>Items</h3>
        <?php foreach ($items as $i): ?>
            <div class="order-item">
                <span><?= $i['product_name'] ?> x<?= $i['quantity'] ?></span>
                <span>Rp <?= number_format($i['price'] * $i['quantity'], 0, ',', '.') ?></span>
            </div>
        <?php endforeach; ?>

        <h3>Total: Rp <?= number_format($order['total_price'], 0, ',', '.') ?></h3>
    </div>

    <a href="/TUBES_2_Toko/index.php" class="back-btn">Back to Home</a>

</div>

</main>

</body>
</html>
