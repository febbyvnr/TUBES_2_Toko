<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

$order_id = $_GET['id'] ?? null;
if (!$order_id) die("Order tidak ditemukan");

$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->execute([$order_id]);
$order = $stmt->fetch();

$stmt = $pdo->prepare(
    "SELECT oi.*, p.name 
     FROM order_items oi
     JOIN products p ON p.id = oi.product_id
     WHERE oi.order_id = ?"
);
$stmt->execute([$order_id]);
$items = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head><title>Detail Order</title></head>
<body>

<h2>Detail Order #<?= $order['id'] ?></h2>
<p>Status: <?= $order['status'] ?></p>
<p>Total: <b><?= number_format($order['total_price']) ?></b></p>

<table border="1" cellpadding="6">
<tr>
    <th>Produk</th>
    <th>Harga</th>
    <th>Qty</th>
</tr>

<?php foreach ($items as $it): ?>
<tr>
    <td><?= $it['name'] ?></td>
    <td><?= number_format($it['price']) ?></td>
    <td><?= $it['quantity'] ?></td>
</tr>
<?php endforeach; ?>

</table>

<a href="listOrder.php">Kembali</a>

</body>
</html>
