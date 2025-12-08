<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /TUBES_2_Toko/auth/login.php?redirect=' . urlencode('/TUBES_2_Toko/cart/listCart.php'));
    exit;
}

$user_id = (int) $_SESSION['user_id'];

$query = "
    SELECT cart.id AS cart_id,
           products.name,
           products.price,
           products.image,
           cart.size,
           cart.quantity
    FROM cart
    JOIN products ON cart.product_id = products.id
    WHERE cart.user_id = ?
";

$stmt = $mysqli->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$cart = $result->fetch_all(MYSQLI_ASSOC);
?>
<!doctype html>
<html lang="en">
<?php
  // pakai head.php global
  $pageTitle   = 'Your Cart — FEYORA';
  $extraStyles = '<link rel="stylesheet" href="styles/cart.css?v=' . time() . '">';
  include __DIR__ . '/../includes/head.php';
?>
<body>
<?php include __DIR__ . '/../includes/header.php'; ?>

<main class="cart-page container">
    <h2 class="cart-title">Your Shopping Cart</h2>

    <?php if (count($cart) === 0): ?>
        <p class="empty-cart">Your cart is empty.</p>
    <?php else: ?>

    <div class="cart-container">
        <?php foreach ($cart as $item): ?>
        <div class="cart-card">
            <!-- Gambar -->
            <img
                src="assets/products/<?= htmlspecialchars($item['image']) ?>"
                alt="<?= htmlspecialchars($item['name']) ?>"
                class="cart-img"
            >

            <div class="cart-info">
                <!-- Nama Produk -->
                <h3 class="cart-product-name"><?= htmlspecialchars($item['name']) ?></h3>

                <!-- Size -->
                <div class="cart-size">
                    Size: <span><?= htmlspecialchars($item['size']) ?></span>
                </div>

                <!-- Kuantitas -->
                <form action="cart/update.php" method="POST" class="qty-form">
                    <!-- NOTE: kalau di update.php kamu pakai id, bisa ganti name/key sesuai kebutuhan -->
                    <input type="hidden" name="cart_id" value="<?= (int)$item['cart_id'] ?>">

                    <input type="hidden" name="quantity" value="<?= $item['quantity'] ?>">

                    <button type="submit" name="action" value="minus" class="qty-btn">-</button>

                    <div class="qty-number"><?= (int)$item['quantity'] ?></div>

                    <button type="submit" name="action" value="plus" class="qty-btn">+</button>
                </form>

                <!-- Harga -->
                <div class="cart-price">
                    Rp <?= number_format($item['price'], 0, ',', '.') ?>
                </div>

                <!-- Total -->
                <div class="cart-total">
                    Total:
                    <span>
                        Rp <?= number_format($item['price'] * $item['quantity'], 0, ',', '.') ?>
                    </span>
                </div>
            </div>

            <form action="selectCheckout.php" method="POST" style="display:inline;">
                <input type="hidden" name="cart_id" value="<?= $item['cart_id'] ?>">
                <button class="select-btn">Select</button>
            </form>
            <a href="cart/delete.php?id=<?= (int)$item['cart_id'] ?>" class="delete-btn">Delete</a>
        </div>
        <?php endforeach; ?>
    </div>
            <a href="product/listProduct.php" class="back-link">← Back to Products</a>
    <?php endif; ?>

    <!-- Checkout -->
    <div class="checkout-summary">
        <div class="summary-title">Ringkasan Checkout</div>

        <div class="summary-list">
        <?php
        $total = 0;
        if (isset($_SESSION['checkout']) && count($_SESSION['checkout']) > 0):
            foreach ($_SESSION['checkout'] as $c):
                $total += $c['price'] * $c['quantity'];
        ?>
            <div class="summary-item">
                <span><?= $c['name'] ?> x<?= $c['quantity'] ?></span>
                <span>Rp <?= number_format($c['price'] * $c['quantity'],0,',','.') ?></span>
            </div>
        <?php endforeach; else: ?>
            <p style="color:#888;">Belum ada barang dipilih</p>
        <?php endif; ?>
        </div>

        <div class="summary-total">
            Total Belanja: Rp <?= number_format($total, 0, ',', '.') ?>
        </div>

        <form action="checkout.php" method="POST">
            <button class="checkout-btn" <?= $total == 0 ? 'disabled' : '' ?>>
                Checkout
            </button>
        </form>

    </div>

</div>

</main>
</body>
</html>
