<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Your Cart</title>
    <link rel="stylesheet" href="../styles/Cart.css?v=<?= time() ?>">
</head>

<body>

<h2>Your Shopping Cart</h2>

<?php if (empty($_SESSION['cart'])): ?>

<p>Your cart is empty.</p>

<?php else: ?>

<table border="1" cellpadding="10">
    <tr>
        <th>Product</th>
        <th>Size</th>
        <th>Qty</th>
        <th>Price</th>
        <th>Total</th>
        <th>Action</th>
    </tr>

    <?php foreach ($_SESSION['cart'] as $key => $item): ?>
    <tr>
        <td><?= $item['name'] ?></td>
        <td><?= $item['size'] ?></td>

        <td>
            <form action="update.php" method="POST" style="display:flex; gap:6px">
                <input type="hidden" name="key" value="<?= $key ?>">
                <input type="number" name="qty" value="<?= $item['qty'] ?>" min="1" style="width:60px">
                <button type="submit">Update</button>
            </form>
        </td>

        <td>Rp <?= number_format($item['price'], 0, ',', '.') ?></td>
        <td>Rp <?= number_format($item['qty'] * $item['price'], 0, ',', '.') ?></td>

        <td>
            <a href="delete.php?key=<?= $key ?>">Delete</a>
        </td>
    </tr>
    <?php endforeach; ?>

</table>

<?php endif; ?>

</body>
</html>
