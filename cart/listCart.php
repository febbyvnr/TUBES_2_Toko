<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// ========= BATAL CHECKOUT ============
if (isset($_POST['cancel_checkout'])) {
    unset($_SESSION['checkout']);
    header("Location: listCart.php");
    exit;
}

// ========= CEK LOGIN ============
if (!isset($_SESSION['user_id'])) {
    header('Location: /TUBES_2_Toko/auth/login.php?redirect=' . urlencode('/TUBES_2_Toko/cart/listCart.php'));
    exit;
}

$user_id = (int) $_SESSION['user_id'];

// ========= SELECT ITEM UNTUK CHECKOUT ============
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cart_id'])) {

    $selected_id = (int) $_POST['cart_id'];

    $querySelect = "
        SELECT cart.id AS cart_id,
               products.name,
               products.price,
               products.image,
               cart.size,
               cart.quantity
        FROM cart
        JOIN products ON cart.product_id = products.id
        WHERE cart.id = ? AND cart.user_id = ?
    ";

    $stmt = $mysqli->prepare($querySelect);
    $stmt->bind_param("ii", $selected_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $item = $result->fetch_assoc();

    if ($item) {
        if (!isset($_SESSION['checkout'])) {
            $_SESSION['checkout'] = [];
        }
        $_SESSION['checkout'][$selected_id] = $item;
    }

    header("Location: listCart.php");
    exit;
}

// ========= AMBIL SEMUA ITEM CART ============
$queryCart = "
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

$stmtCart = $mysqli->prepare($queryCart);
$stmtCart->bind_param("i", $user_id);
$stmtCart->execute();
$resultCart = $stmtCart->get_result();
$cart = $resultCart->fetch_all(MYSQLI_ASSOC);

?>
<!doctype html>
<html lang="en">
<?php
  $pageTitle   = 'Your Cart — FEYORA';
  $extraStyles = '<link rel="stylesheet" href="styles/cart.css?v=' . time() . '">';
  include __DIR__ . '/../includes/head.php';
?>
<body>

<?php include __DIR__ . '/../includes/header.php'; ?>

<main class="cart-page">
<div class="container cart-wrapper">
    
    <!-- =======================
         CART ITEMS (kiri)
    ======================== -->
    <div class="cart-items-column">

        <div class="cart-header">
            <a href="product/listProduct.php" class="back-link">← Back to Products</a>
            <h2 class="cart-title">Your Shopping Cart</h2>
        </div>

        <?php if (count($cart) === 0): ?>
            <p class="empty-cart">Your cart is empty.</p>
        <?php else: ?>

        <?php foreach ($cart as $item): ?>
            <div class="cart-card">

                <!-- Gambar -->
                <img
                    src="assets/products/<?= htmlspecialchars($item['image']) ?>"
                    alt="<?= htmlspecialchars($item['name']) ?>"
                    class="cart-img"
                >

                <!-- Informasi Produk (kiri) -->
                <div class="cart-info">
                    <h3 class="cart-product-name"><?= htmlspecialchars($item['name']) ?></h3>

                    <div class="cart-size">
                        Size: <span><?= htmlspecialchars($item['size']) ?></span>
                    </div>

                    <!-- Qty -->
                    <form action="cart/update.php" method="POST" class="qty-form">
                        <input type="hidden" name="cart_id"   value="<?= (int)$item['cart_id'] ?>">
                        <input type="hidden" name="quantity" value="<?= (int)$item['quantity'] ?>">

                        <button type="submit" name="action" value="minus" class="qty-btn">−</button>
                        <div class="qty-number"><?= (int)$item['quantity'] ?></div>
                        <button type="submit" name="action" value="plus" class="qty-btn">+</button>
                    </form>

                    <div class="cart-price">
                        Rp <?= number_format($item['price'], 0, ',', '.') ?>
                    </div>
                </div>

                <!-- SELECT + DELETE (kanan) -->
                <div class="cart-actions">
                    <form method="POST">
                        <input type="hidden" name="cart_id" value="<?= $item['cart_id'] ?>">
                        <button class="select-btn">Select</button>
                    </form>

                    <a href="cart/delete.php?id=<?= (int)$item['cart_id'] ?>" class="delete-btn">
                        Delete
                    </a>
                </div>


                <!-- Total di tengah -->
                <div class="cart-summary">
                    <div class="cart-summary-label">Total</div>
                    <div class="cart-summary-value">
                        Rp <?= number_format($item['price'] * $item['quantity'], 0, ',', '.') ?>
                    </div>
                </div>

            </div>
        <?php endforeach; ?>

        <?php endif; ?>

    </div> <!-- end left -->



    <!-- =============================
         CHECKOUT SIDEBAR (Kanan)
    ============================== -->
    <div class="checkout-summary">

        <div class="summary-title">Item Checkout</div>

        <div class="summary-list">
        <?php
        $total = 0;
        if (!empty($_SESSION['checkout'])):
            foreach ($_SESSION['checkout'] as $c):
                $total += $c['price'] * $c['quantity'];
        ?>
            <div class="summary-item">
                <span><?= $c['name'] ?> x<?= $c['quantity'] ?></span>
                <span>Rp <?= number_format($c['price'] * $c['quantity'], 0, ',', '.') ?></span>
            </div>
        <?php endforeach; else: ?>
            <p style="color:#888;">Belum ada barang dipilih</p>
        <?php endif; ?>
        </div>

        <div class="summary-total">
            Total Belanja: Rp <?= number_format($total, 0, ',', '.') ?>
        </div>

        <form action="order/checkout.php" method="POST">
            <button class="checkout-btn" 
                <?= empty($_SESSION['checkout']) ? "onclick=\"alert('Pilih item dulu sebelum checkout!'); return false;\"" : '' ?>>
                Checkout
            </button>

        </form>

        <form method="POST">
            <button class="cancelCheckout-btn" name="cancel_checkout">
                Batal Checkout
            </button>
        </form>

    </div> 

</div> 
<!-- <?php include __DIR__ . '/../includes/footer.php'; ?> -->
</main>
</body>
</html>
