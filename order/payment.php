<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['shipping']) || !isset($_SESSION['checkout_ids'])) {
    header("Location: checkout.php");
    exit;
}

$shipping = $_SESSION['shipping'];
$user_id = $_SESSION['user_id'];
$selected = $_SESSION['checkout_ids'];

$ids = implode(",", array_map('intval', array_keys($selected)));

$query = $mysqli->query("
    SELECT c.quantity, c.size, p.name, p.price, p.image
    FROM cart c
    JOIN products p ON c.product_id = p.id
    WHERE c.user_id = $user_id
    AND c.id IN ($ids)
");

$items = [];
$subtotal = 0;

while ($row = $query->fetch_assoc()) {
    $items[] = $row;
    $subtotal += $row['price'] * $row['quantity'];
}

$shipping_cost = 5000;
$admin_fee = 2000;
$total = $subtotal + $shipping_cost + $admin_fee;
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Pembayaran</title>

<link rel="stylesheet" href="../styles/payment.css">

</head>
<body>

<div class="container">

    <!-- ====================== ALAMAT PENGIRIMAN ===================== -->
    <div class="box">
        <div class="section-title">Alamat Pengiriman</div>

        <div class="address-wrapper">

            <div class="address-left">
                <b><?= $shipping['firstname'] . " " . $shipping['lastname'] ?></b><br>
                <span>(+62) 895 xxxx xxxx</span>
            </div>

            <div class="address-right">
                <?= $shipping['address'] ?>,
                <?= $shipping['city'] ?>,
                <?= $shipping['state'] ?>,
                <?= $shipping['zip'] ?>
            </div>

        </div>
    </div>

    <!-- ====================== PRODUK DIPESAN ===================== -->
    <div class="box">
        <div class="section-title">Produk Dipesan</div>

        <div class="product-header">
            <div>Produk</div>
            <div>Harga Satuan</div>
            <div>Jumlah</div>
            <div>Subtotal</div>
        </div>

        <?php foreach ($items as $i): ?>
        <div class="product-row">
            <div class="prod-info">
                <img src="/TUBES_2_Toko/assets/products/<?= $i['image'] ?>">
                <div>
                    <b><?= $i['name'] ?></b><br>
                    <span class="size-text">Size: <?= strtoupper($i['size']) ?></span>
                </div>
            </div>

            <div>Rp <?= number_format($i['price'], 0, ',', '.') ?></div>
            <div><?= $i['quantity'] ?></div>
            <div><b>Rp <?= number_format($i['price'] * $i['quantity'], 0, ',', '.') ?></b></div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- ====================== PAYMENT METHOD + SUMMARY ===================== -->

    <div class="payment-container">

        <!-- LEFT SIDE -->
        <div class="payment-left section-box">
            <div class="section-title">Metode Pembayaran</div>

            <div class="method-list">
                <div id="btn-bank" class="method-btn" onclick="selectMethod('bank')">Transfer Bank</div>
                <div id="btn-ewallet" class="method-btn" onclick="selectMethod('ewallet')">E-Wallet</div>
                <div id="btn-cod" class="method-btn active" onclick="selectMethod('cod')">COD (Bayar di Tempat)</div>
            </div>

            <div class="payment-detail" id="paymentDetail">

    <!-- DEFAULT (COD) -->
    <div id="detail-cod">
        <b>COD</b> — Cash on Delivery
    </div>

    <!-- TRANSFER BANK -->
    <div id="detail-bank" style="display:none;">
        <b>Transfer Bank</b> — Pembayaran via Virtual Account
    </div>

    <!-- E-WALLET -->
    <div id="detail-ewallet" style="display:none;">
        <b>E-Wallet</b> — Dana / OVO / Gopay
        <div class="ewallet-options">
            <label><input type="radio" name="ewallet_type" value="Dana"> Dana</label>
            <label><input type="radio" name="ewallet_type" value="OVO"> OVO</label>
            <label><input type="radio" name="ewallet_type" value="Gopay"> Gopay</label>
        </div>
    </div>

</div>


            <form id="paymentForm" action="processPayment.php" method="POST">
                <input type="hidden" name="method" id="method" value="cod">
                <input type="hidden" name="total_price" value="<?= $total ?>">
            </form>
        </div>

        <!-- RIGHT SIDE SUMMARY -->
        <div class="payment-right section-box">
            <h3>Ringkasan Pesanan</h3>

            <div class="summary-row"><span>Subtotal Produk</span><span>Rp <?= number_format($subtotal) ?></span></div>
            <div class="summary-row"><span>Subtotal Pengiriman</span><span>Rp <?= number_format($shipping_cost) ?></span></div>
            <div class="summary-row"><span>Biaya Admin</span><span>Rp <?= number_format($admin_fee) ?></span></div>

            <div class="total-line">
                <span>Total Pembayaran</span>
                <span>Rp <?= number_format($total) ?></span>
            </div>

            <button class="btn-submit" onclick="document.getElementById('paymentForm').submit()">Buat Pesanan</button>
        </div>

    </div>

</div>

<!-- ===================== JAVASCRIPT ===================== -->
<script>
function selectMethod(method) {

    // Set selected payment method
    document.getElementById("method").value = method;

    // Highlight selected button
    document.querySelectorAll(".method-btn")
        .forEach(btn => btn.classList.remove("active"));
    document.getElementById("btn-" + method).classList.add("active");

    // Hide all method details
    document.getElementById("detail-cod").style.display = "none";
    document.getElementById("detail-bank").style.display = "none";
    document.getElementById("detail-ewallet").style.display = "none";

    // Show selected method detail
    if(method === "cod"){
        document.getElementById("detail-cod").style.display = "block";
    }
    else if(method === "bank"){
        document.getElementById("detail-bank").style.display = "block";
    }
    else if(method === "ewallet"){
        document.getElementById("detail-ewallet").style.display = "block";
    }
}
</script>


</body>
</html>
