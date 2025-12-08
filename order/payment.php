<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /TUBES_2_Toko/auth/login.php');
    exit;
}

if (empty($_SESSION['checkout']) || empty($_SESSION['shipping'])) {
    header("Location: ../cart/listCart.php");
    exit;
}

$shipping = $_SESSION['shipping'];
$items    = $_SESSION['checkout'];

$total = 0;
foreach ($items as $i) {
    $total += $i['price'] * $i['quantity'];
}
?>

<!doctype html>
<html lang="en">
<?php
  $pageTitle   = 'Payment — FEYORA';
  $extraStyles = '<link rel="stylesheet" href="/TUBES_2_Toko/styles/payment.css?v=' . time() . '">';
  include __DIR__ . '/../includes/head.php';
?>
<body>

<?php include __DIR__ . '/../includes/header.php'; ?>

<main class="payment-page">

<div class="payment-container">

    <div class="section">
        <h3>Shipping Address</h3>
        <p><?= htmlspecialchars($shipping['name']) ?></p>
        <p><?= htmlspecialchars($shipping['phone']) ?></p>
        <p><?= htmlspecialchars($shipping['address']) ?>,
           <?= htmlspecialchars($shipping['city']) ?>,
           <?= htmlspecialchars($shipping['postal_code']) ?></p>
    </div>

    <div class="section">
        <h3>Order Summary</h3>
        <?php foreach ($items as $i): ?>
            <div class="order-item">
                <span><?= $i['name'] ?> x<?= $i['quantity'] ?></span>
                <span>Rp <?= number_format($i['price'] * $i['quantity'], 0, ',', '.') ?></span>
            </div>
        <?php endforeach; ?>

        <h4>Total: Rp <?= number_format($total, 0, ',', '.') ?></h4>
    </div>

    <form action="processPayment.php" method="POST" class="section">
        <h3>Choose Payment Method</h3>

        <label><input type="radio" name="payment_method" value="DANA" required> DANA</label><br>
        <label><input type="radio" name="payment_method" value="OVO"> OVO</label><br>
        <label><input type="radio" name="payment_method" value="ShopeePay"> ShopeePay</label><br>
        <label><input type="radio" name="payment_method" value="Transfer Bank"> Bank Transfer</label><br>
        <label><input type="radio" name="payment_method" value="COD"> COD</label><br>

        <button class="pay-btn">Confirm Payment</button>
    </form>

</div>

</main>

</body>
</html>
