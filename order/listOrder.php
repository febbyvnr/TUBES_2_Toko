<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY id DESC");
$stmt->execute([$user_id]);
$orders = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head><title>Daftar Order</title></head>
<body>

<h2>Riwayat Order</h2>

<table border="1" cellpadding="6">
<tr>
    <th>ID</th>
    <th>Total Harga</th>
    <th>Status</th>
    <th>Aksi</th>
</tr>

<?php foreach ($orders as $o): ?>
<tr>
    <td><?= $o['id'] ?></td>
    <td><?= number_format($o['total_price']) ?></td>
    <td><?= $o['status'] ?></td>
    <td><a href="view.php?id=<?= $o['id'] ?>">Detail</a></td>
</tr>
<?php endforeach; ?>

</table>

</body>
</html>
