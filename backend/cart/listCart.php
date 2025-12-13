<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['checkout_ids'])) {
    $_SESSION['checkout_ids'] = [];
}

if (isset($_POST['cancel_checkout'])) {
    $_SESSION['checkout_ids'] = [];
    header("Location: listCart.php");
    exit;
}

if (!isset($_SESSION['user_id'])) {
    header('Location: /TUBES_2_Toko/auth/login.php?redirect=' . urlencode('/TUBES_2_Toko/cart/listCart.php'));
    exit;
}

$user_id = (int) $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_cart_id'])) {

    $selected_id = (int) $_POST['toggle_cart_id'];
    $is_selected = ($_POST['selected'] ?? '0') === '1';

    $querySelect = "
        SELECT cart.id
        FROM cart
        WHERE cart.id = ? AND cart.user_id = ?
        LIMIT 1
    ";
    $stmt = $mysqli->prepare($querySelect);
    $stmt->bind_param("ii", $selected_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $item = $result->fetch_assoc();

    if ($item) {
        if (!isset($_SESSION['checkout_ids'])) {
            $_SESSION['checkout_ids'] = [];
        }

        if ($is_selected) {
            $_SESSION['checkout_ids'][$selected_id] = true;
        } else {
            unset($_SESSION['checkout_ids'][$selected_id]);
        }
    }

    header("Location: listCart.php");
    exit;
}

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
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <title>FEYORA</title>
    <!-- Bootstrap Icons -->
    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/TUBES_2_Toko/frontend/styles/HomePage.css">
    <link rel="stylesheet" href="/TUBES_2_Toko/frontend/styles/header.css">
    <link rel="stylesheet" href="/TUBES_2_Toko/frontend/styles/cart.css">
</head>
<body>
<div id="header"></div>
<main class="cart-page">
<div class="container cart-wrapper">
    <div class="cart-items-column">

        <div class="cart-header">
            <a href="../../frontend/product/listProduct.html" class="back-link">← Back to Products</a>
            <h2 class="cart-title">Your Shopping Cart</h2>
        </div>

        <?php if (count($cart) === 0): ?>
            <p class="empty-cart">Your cart is empty.</p>
        <?php else: ?>

        <?php foreach ($cart as $item): ?>
            <div class="cart-card">
                <div class="cart-select">
                    <form method="POST">
                        <input type="hidden" name="toggle_cart_id" value="<?= (int)$item['cart_id'] ?>">
                        <input type="hidden" name="selected"
                            value="<?= isset($_SESSION['checkout_ids'][$item['cart_id']]) ? '1' : '0' ?>">

                        <input
                            type="checkbox"
                            class="cart-select-checkbox"
                            <?= isset($_SESSION['checkout_ids'][$item['cart_id']]) ? 'checked' : '' ?>
                            onchange="
                                this.form.selected.value = this.checked ? '1' : '0';
                                this.form.submit();
                            "
                        >
                    </form>
                </div>

                <img
                    src="/TUBES_2_Toko/frontend/assets/products/<?= htmlspecialchars($item['image']) ?>"
                    alt="<?= htmlspecialchars($item['name']) ?>"
                    class="cart-img"
                >

                <div class="cart-info">
                    <div class="cart-info-header">
                        <h3 class="cart-product-name"><?= htmlspecialchars($item['name']) ?></h3>
                    </div>

                    <div class="cart-size">
                        Size: <span><?= htmlspecialchars($item['size']) ?></span>
                    </div>

                    <div class="cart-info-bottom">
                        <form action="/TUBES_2_Toko/backend/cart/update.php" method="POST" class="qty-form">
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
                </div>

                <div class="cart-summary">
                    <div class="cart-summary-label">Total</div>
                    <div class="cart-summary-value">
                        Rp <?= number_format($item['price'] * $item['quantity'], 0, ',', '.') ?>
                        <a href="/TUBES_2_Toko/backend/cart/delete.php?id=<?= (int)$item['cart_id'] ?>" 
                            class="delete-icon-btn summary-delete"
                            title="Hapus dari keranjang">
                            <i class="bi bi-trash3"></i>
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <?php endif; ?>

    </div>
    <div class="checkout-summary">
        <div class="summary-title">Item Checkout</div>
        <div class="summary-list">
            <?php
            $total = 0;
            $selectedIds = $_SESSION['checkout_ids'] ?? [];

            if (!empty($cart) && !empty($selectedIds)):
                foreach ($cart as $c):
                    if (empty($selectedIds[$c['cart_id']])) continue;

                    $lineTotal = $c['price'] * $c['quantity'];
                    $total += $lineTotal;
            ?>
                    <div class="summary-item">
                        <span><?= htmlspecialchars($c['name']) ?> x<?= (int)$c['quantity'] ?></span>
                        <span>Rp <?= number_format($lineTotal, 0, ',', '.') ?></span>
                    </div>
            <?php
                endforeach;
            else:
            ?>
                <p style="color:#888;">No products have been selected yet</p>
            <?php endif; ?>
            </div>

            <div class="summary-total">
                Total : Rp <?= number_format($total, 0, ',', '.') ?>
            </div>

        <form action="/TUBES_2_Toko/frontend/order/checkout.html" method="POST">
            <button class="checkout-btn" 
                <?= empty($_SESSION['checkout_ids']) ? "onclick=\"alert('Pilih item dulu sebelum checkout!'); return false;\"" : '' ?>>
                Checkout
            </button>
        </form>

        <form method="POST">
            <button class="cancelCheckout-btn" name="cancel_checkout">
                Cancel
            </button>
        </form>

    </div> 

</div> 
</main>
<div id="footer"></div>
<script>
    async function loadPart(id, url) {
        const el = document.getElementById(id);
        if (!el) return;

        const res = await fetch(url, {
            cache: "no-cache",
            credentials: "include"
        });

        const html = await res.text();
        el.innerHTML = html;

        // paksa execute script dari include
        el.querySelectorAll("script").forEach(oldScript => {
            const s = document.createElement("script");
            if (oldScript.src) s.src = oldScript.src;
            s.textContent = oldScript.textContent;
            document.body.appendChild(s);
            oldScript.remove();
        });

        // setelah header masuk + scriptnya sudah dieksekusi
        if (id === "header" && window.initHeaderAuth) {
            await window.initHeaderAuth();
        }
    }

    (async function () {
        await loadPart("header", "/TUBES_2_Toko/frontend/includes/header.html");
        await loadPart("footer", "/TUBES_2_Toko/frontend/includes/footer.html");
    })();
</script>
</body>
</html>
