<?php
session_start();
require_once "../config/db.php";

if (!isset($_GET['id'])) {
    die("Order tidak ditemukan.");
}

$order_id = intval($_GET['id']);

$order = $mysqli->query("
    SELECT * FROM orders WHERE id = $order_id
")->fetch_assoc();

if (!$order) {
    die("Order tidak ditemukan.");
}

$items = $mysqli->query("
    SELECT oi.*, p.name
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = $order_id
");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Detail Order</title>
</head>
<body>

<h2>Detail Order #<?= $order['id'] ?></h2>
<p>Status: <b><?= $order['status'] ?></b></p>
<p>Total Harga: <b><?= number_format($order['total_price']) ?></b></p>

<hr>

<h3>Update Status Order</h3>

<form action="updateStatus.php" method="post">
    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">

    <label>Status Order:</label>
    <select name="status">
        <option value="pending"     <?= $order['status']=='pending'?'selected':'' ?>>Pending</option>
        <option value="processing"  <?= $order['status']=='processing'?'selected':'' ?>>Processing</option>
        <option value="completed"   <?= $order['status']=='completed'?'selected':'' ?>>Completed</option>
        <option value="cancelled"   <?= $order['status']=='cancelled'?'selected':'' ?>>Cancelled</option>
    </select>

    <button type="submit">Update</button>
</form>

<hr>

<h3>Produk dalam Order</h3>

<table border="1" cellpadding="6">
<tr>
    <th>Produk</th>
    <th>Harga</th>
    <th>Qty</th>
</tr>

<?php while ($item = $items->fetch_assoc()): ?>
<tr>
    <td><?= $item['name'] ?></td>
    <td><?= number_format($item['price']) ?></td>
    <td><?= $item['quantity'] ?></td>
</tr>
<?php endwhile; ?>
</table>

<br>
<a href="listOrder.php">Kembali</a>

</body>
</html>
