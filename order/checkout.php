<?php
session_start();
require_once __DIR__ . '/../config/db.php';

/* =======================================================
   CEK LOGIN
======================================================= */
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

/* =======================================================
   CEK ITEM TERPILIH UNTUK CHECKOUT
======================================================= */
if (!isset($_SESSION['checkout_ids']) || empty($_SESSION['checkout_ids'])) {
    header("Location: ../cart/listCart.php");
    exit;
}

/* =======================================================
   SAAT USER SUBMIT FORM ALAMAT
======================================================= */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["firstname"])) {

    $_SESSION["shipping"] = [
        "email"     => $_POST["email"],
        "firstname" => $_POST["firstname"],
        "lastname"  => $_POST["lastname"],
        "address"   => $_POST["address"],
        "city"      => $_POST["city"],
        "state"     => $_POST["state"],
        "zip"       => $_POST["zip"]
    ];

    header("Location: payment.php");
    exit;
}

/* =======================================================
   AMBIL ITEM CART TERPILIH
======================================================= */
$selected = $_SESSION['checkout_ids'];
$ids = implode(",", array_map('intval', array_keys($selected)));

$query = $mysqli->prepare("
    SELECT c.id AS cart_id, c.quantity, c.size,
           p.id AS product_id, p.name, p.price, p.image
    FROM cart c
    JOIN products p ON c.product_id = p.id
    WHERE c.user_id = ?
    AND c.id IN ($ids)
");
$query->bind_param("i", $user_id);
$query->execute();
$result = $query->get_result();

$items = [];
$total = 0;

while ($row = $result->fetch_assoc()) {
    $items[] = $row;
    $total += $row['price'] * $row['quantity'];
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Checkout</title>

    <style>
        body { font-family: Arial, sans-serif; background: #faf7f9; margin: 0; }
        .wrapper { width: 90%; max-width: 1200px; margin: 30px auto; display: flex; gap: 25px; }
        .checkout-left { width: 65%; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 0 10px #ddd; }
        h2 { margin-top: 0; font-size: 26px; margin-bottom: 15px; }
        label { font-size: 14px; font-weight: bold; }
        input { width: 100%; padding: 6px; border: 1px solid #ccc; border-radius: 5px; margin-top: 5px; margin-bottom: 20px; font-size: 15px; }
        .form-row { display: flex; gap: 20px; }
        .checkout-right { width: 50%; background: white; padding: 25px; border-radius: 12px; box-shadow: 0 0 10px #ddd; height: fit-content; }

        .order-item { display: flex; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px solid #eee; }
        .order-item img { width: 70px; height: 70px; border-radius: 8px; margin-right: 15px; object-fit: cover; }

        .summary-line { display: flex; justify-content: space-between; margin: 10px 0; font-size: 16px; }
        .summary-total { font-size: 20px; font-weight: bold; border-top: 2px solid #ccc; padding-top: 15px; margin-top: 10px; }

        .btn-row { display: flex; justify-content: space-between; margin-top: 20px; }
        .btn-return { padding: 14px 22px; background: none; border: none; color: #ff2d7a; font-size: 17px; font-weight: bold; text-decoration: none; cursor: pointer; }
        .btn-submit { padding: 14px 18px; background: #ff2d7a; border: none; color: white; border-radius: 8px; font-size: 18px; font-weight: bold; cursor: pointer; }
        .btn-submit:hover { background: #e02468; }
    </style>
</head>

<body>

<div class="wrapper">

    <!-- LEFT SIDE -->
    <div class="checkout-left">
        <h2>Shipping Address</h2>
        <p>Enter your shipping details</p>

        <form action="" method="POST">

            <label>Email</label>
            <input type="email" name="email" required>

            <div class="form-row">
                <div style="width: 50%;">
                    <label>First Name</label>
                    <input type="text" name="firstname" required>
                </div>
                <div style="width: 50%;">
                    <label>Last Name</label>
                    <input type="text" name="lastname" required>
                </div>
            </div>

            <!-- Use My Location -->
            <div style="display:flex; align-items:center; gap:10px;">
                <label for="address" style="flex:1;">Address</label>
                <label style="display:flex; align-items:center; gap:5px;">
                    <input type="checkbox" id="useLocation"> Use My Location
                </label>
            </div>

            <input type="text" name="address" id="address" required>

            <div class="form-row">
                <div style="width: 33%;">
                    <label>City</label>
                    <input type="text" name="city" required>
                </div>

                <div style="width: 33%;">
                    <label>State</label>
                    <input type="text" name="state" required>
                </div>

                <div style="width: 33%;">
                    <label>ZIP Code</label>
                    <input type="text" name="zip" required>
                </div>
            </div>

            <!-- Billing same as shipping -->
            <div style="margin:10px 0 25px 0; display:flex; gap:10px; align-items:center;">
                <input type="checkbox" id="sameBilling" style="width:18px; height:18px;">
                <label for="sameBilling" style="font-size:15px; color:#444;">Billing address is the same as shipping</label>
            </div>

            <div class="btn-row">
                <a href="../cart/listCart.php" class="btn-return">Return to Cart</a>
                <button type="submit" class="btn-submit">Continue to Payment</button>
            </div>

        </form>
    </div>

    <!-- RIGHT SIDE -->
    <div class="checkout-right">
        <h3>Order Summary</h3>

        <?php foreach ($items as $i): ?>
            <div class="order-item">
                <img src="/TUBES_2_Toko/assets/products/<?= $i['image'] ?>">
                <div>
                    <b><?= $i['name'] ?></b><br>
                    <span style="font-size:13px;color:#777;">Size: <?= strtoupper($i['size']) ?></span><br>
                    <span style="font-size:13px;color:#777;">Qty: <?= $i['quantity'] ?></span>
                </div>
                <div style="margin-left:auto;font-weight:bold;">
                    Rp <?= number_format($i['price'],0,',','.') ?>
                </div>
            </div>
        <?php endforeach; ?>

        <div class="summary-line"><span>Subtotal</span><span>Rp <?= number_format($total) ?></span></div>
        <div class="summary-line"><span>Shipping</span><span>Rp 5.000</span></div>
        <div class="summary-line"><span>Admin</span><span>Rp 2.000</span></div>

        <div class="summary-total">Total: Rp <?= number_format($total + 7000) ?></div>
    </div>

</div>

<script>
// ======== USE MY LOCATION ===========
document.getElementById("useLocation").addEventListener("change", () => {
    let addressInput = document.getElementById("address");

    if (!navigator.geolocation) {
        alert("Your browser doesn't support geolocation.");
        return;
    }

    navigator.geolocation.getCurrentPosition(success, () => {
        alert("Unable to retrieve location.");
    });

    function success(pos) {
        const lat = pos.coords.latitude;
        const lon = pos.coords.longitude;

        fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lon}`)
            .then(res => res.json())
            .then(data => {
                if (data.display_name) {
                    addressInput.value = data.display_name;
                }
            });
    }
});
</script>

</body>
</html>
