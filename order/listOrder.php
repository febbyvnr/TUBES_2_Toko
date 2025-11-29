<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

$result = $mysqli->query("
    SELECT * FROM orders 
    WHERE user_id = $user_id 
    ORDER BY id DESC
");
?>
<!DOCTYPE html>
<html>
<head><title>Order Saya</title></head>
<body>

<h2>Daftar Order Anda</h2>

<table border="1" cellpadding="6">
<tr>
    <th>ID</th>
    <th>Total Harga</th>
    <th>Status</th>
    <th>Detail</th>
</tr>

<?php while ($o = $result->fetch_assoc()): ?>
<tr>
    <td><?= $o['id'] ?></td>
    <td><?= number_format($o['total_price']) ?></td>
    <td><?= $o['status'] ?></td>
    <td><a href="view.php?id=<?= $o['id'] ?>">Lihat</a></td>
</tr>
<?php endwhile; ?>

</table>

</body>
</html>
