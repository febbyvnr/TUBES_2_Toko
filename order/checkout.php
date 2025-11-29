<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

$sql = "SELECT c.id AS cart_id, c.quantity,
               p.id AS product_id, p.name, p.price, p.stock
        FROM cart c
        JOIN products p ON c.product_id = p.id
        WHERE c.user_id = $user_id";
$result = $mysqli->query($sql);

$items = [];
while ($row = $result->fetch_assoc()) {
    $items[] = $row;
}

if (empty($items)) {
    echo "<h3>Keranjang kamu kosong!</h3>";
    echo "<a href='../index.php'>Belanja sekarang</a>";
    exit;
}

$total = 0;
foreach ($items as $item) {
    $total += $item['price'] * $item['quantity'];
}
?>
<!DOCTYPE html>
<html>
<head><title>Checkout</title></head>
<body>

<h2>Checkout</h2>

<table border="1" cellpadding="6">
<tr>
    <th>Produk</th>
    <th>Harga</th>
    <th>Qty</th>
    <th>Subtotal</th>
</tr>

<?php foreach ($items as $item): ?>
<tr>
    <td><?= $item['name'] ?></td>
    <td><?= number_format($item['price']) ?></td>
    <td><?= $item['quantity'] ?></td>
    <td><?= number_format($item['price'] * $item['quantity']) ?></td>
</tr>
<?php endforeach; ?>

<tr>
    <td colspan="3" align="right"><b>Total</b></td>
    <td><b><?= number_format($total) ?></b></td>
</tr>
</table>

<form method="post" action="create.php">
    <button type="submit" name="checkout" value="1">
        Konfirmasi Order
    </button>
</form>

</body>
</html>
