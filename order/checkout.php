<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// Pastikan user login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

// Pastikan user sudah select item
if (empty($_SESSION['checkout'])) {
    header("Location: ../cart/listCart.php");
    exit;
}

$items = $_SESSION['checkout'];
$total = 0;

foreach ($items as $item) {
    $total += $item['price'] * $item['quantity'];
}

// Jika form dikirim → simpan ke SESSION dan pindah ke payment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $_SESSION['shipping'] = [
        'name'        => $_POST['name'],
        'phone'       => $_POST['phone'],
        'address'     => $_POST['address'],
        'city'        => $_POST['city'],
        'postal_code' => $_POST['postal_code']
    ];

    header("Location: payment.php");
    exit;
}

?>
<!doctype html>
<html lang="en">

<?php
  $pageTitle   = 'Checkout — FEYORA';
  $extraStyles = '<link rel="stylesheet" href="/TUBES_2_Toko/styles/checkout.css?v=' . time() . '">';
  include __DIR__ . '/../includes/head.php';
?>

<body>

<?php include __DIR__ . '/../includes/header.php'; ?>

<main class="checkout-page">
<div class="container checkout-wrapper">

    <!-- ===========================
        LEFT = SHIPPING FORM
    ============================ -->
    <div class="checkout-left">

        <h2>Shipping Address</h2>

        <form method="POST" class="shipping-form">

            <label>
                Full Name
                <input type="text" name="name" required>
            </label>

            <label>
                Phone Number
                <input type="text" name="phone" required>
            </label>

            <label>
                Address
                <input type="text" name="address" required>
            </label>

            <label>
                City
                <input type="text" name="city" required>
            </label>

            <label>
                Postal Code
                <input type="text" name="postal_code" required>
            </label>

            <button type="submit" class="confirm-address-btn">
                Continue to Payment →
            </button>

        </form>

    </div>



    <!-- ===========================
        RIGHT = ORDER SUMMARY
    ============================ -->
    <div class="checkout-right">
        <h3>Order Summary</h3>

        <div class="order-summary-box">
        <?php foreach ($items as $item): ?>
            <div class="summary-item">
                <span><?= htmlspecialchars($item['name']) ?> x<?= $item['quantity'] ?></span>
                <span>Rp <?= number_format($item['price'] * $item['quantity'], 0, ',', '.') ?></span>
            </div>
        <?php endforeach; ?>
        </div>

        <div class="summary-total">
            <span>Total</span>
            <span>Rp <?= number_format($total, 0, ',', '.') ?></span>
        </div>
    </div>

</div>
</main>

</body>
</html>
