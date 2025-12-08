<?php
session_start();

require_once __DIR__ . '/../config/db.php';

if(!isset($_SESSION['user_id'])) {
    die("Silakan login terlebih dahulu.");
}

$user_id = $_SESSION['user_id'];

$query = "
    SELECT cart.id AS cart_id, products.name, products.price, products.image, 
    cart.size, cart.quantity 
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
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Your Cart</title>
    <link rel="stylesheet" href="../styles/cart.css?v=<?= time() ?>">
</head>

<body>

<h2 class="cart-title">Your Shopping Cart</h2>

<?php if (count($cart) === 0): ?>
    <p class="empty-cart">Your cart is empty.</p>
<?php else: ?>

<div class="cart-container">
    <?php foreach ($cart as $item): ?>
    <div class="cart-card">
        <!-- Gambar -->
        <img src="../assets/products/<?= $item['image'] ?>" class="cart-img">

        <div class="cart-info">

            <!-- Nama Produk -->
            <h3 class="cart-product-name"><?= $item['name'] ?></h3>

            <!-- Size -->
            <div class="cart-size">Size: <span><?= $item['size'] ?></span></div>

            <!-- Kuantitas -->
            <form action="update.php" method="POST" class="qty-form">
                <input type="hidden" name="key" value="<?= $key ?>">

                <button type="submit" name="action" value="minus" class="qty-btn">-</button>

                <div class="qty-number"><?= $item['quantity'] ?></div>

                <button type="submit" name="action" value="plus" class="qty-btn">+</button>
            </form>

            <!-- Harga -->
            <div class="cart-price">Rp <?= number_format($item['price'], 0, ',', '.') ?></div>

            <!-- Total -->
            <div class="cart-total">
                Total: <span>Rp <?=number_format($item['price'] * $item['quantity'], 0, ',', '.') ?></span>
            </div>
        </div>

        <a href="delete.php?id=<?= $item['cart_id'] ?>" class="delete-btn">Delete</a>

    </div>
    <?php endforeach; ?>

</div>

<?php endif; ?>

</body>
</html>
