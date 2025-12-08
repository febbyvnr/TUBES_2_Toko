<?php
session_start();

// Ambil data dari checkout (pastikan checkout mengirim)
$shipping = $_SESSION['shipping'];
$cart = $_SESSION['cart_items'];
$total = $_SESSION['order_total'];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Payment</title>
    <link rel="stylesheet" href="../styles/payment.css">
</head>
<body>

<h2>Payment</h2>

<!-- Shipping Address -->
<div class="shipping-box">
    <h3>Shipping Address</h3>
    <p><?= $shipping['name'] ?></p>
    <p><?= $shipping['phone'] ?></p>
    <p><?= $shipping['address'] ?></p>
    <p><?= $shipping['city'] ?>, <?= $shipping['postal_code'] ?></p>
</div>

<!-- Order Summary -->
<div class="order-summary">
    <h3>Order Summary</h3>
    <?php foreach($cart as $item): ?>
        <div class="item">
            <span><?= $item['name'] ?></span>
            <span><?= $item['price'] ?> x <?= $item['qty'] ?></span>
        </div>
    <?php endforeach; ?>

    <h4>Total: Rp <?= number_format($total) ?></h4>
</div>

<!-- Payment Method -->
<form action="processPayment.php" method="POST">
    <h3>Choose Payment Method</h3>

    <label>
        <input type="radio" name="payment_method" value="DANA" required> DANA
    </label>

    <label>
        <input type="radio" name="payment_method" value="Transfer Bank"> Bank Transfer
    </label>

    <label>
        <input type="radio" name="payment_method" value="COD"> Cash On Delivery
    </label>

    <button type="submit">Confirm Payment</button>
</form>

</body>
</html>
