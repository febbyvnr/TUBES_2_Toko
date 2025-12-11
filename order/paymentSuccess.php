<?php
session_start();
require_once __DIR__ . '/../config/db.php';

$trx_id = $_GET['trx'] ?? null;

if (!$trx_id) {
    die("Invalid transaction ID");
}

$stmt = $mysqli->prepare("
    SELECT total_price, date_created, status
    FROM transactions
    WHERE id = ?
");
$stmt->bind_param("i", $trx_id);
$stmt->execute();
$result = $stmt->get_result();
$trx = $result->fetch_assoc();
$stmt->close();

?>
<!DOCTYPE html>
<html>
<head>
    <title>Payment Success</title>
    <style>
        body { font-family: Arial; background:#faf7f9; text-align:center; padding-top:80px; }
        .box {
            background:white; padding:30px; width:400px;
            margin:auto; border-radius:12px; box-shadow:0 0 10px #ddd;
        }
        h2 { color:#28a745; }
        a { color:white; background:#ff2d7a; padding:12px 20px;
            border-radius:8px; text-decoration:none; display:inline-block; margin-top:20px; }
    </style>
</head>
<body>

<div class="box">
    <h2>Payment Successful!</h2>
    <p>Your transaction has been completed.</p>
    
    <h3>Transaction Details</h3>
    <p><b>ID:</b> <?= $trx_id ?></p>
    <p><b>Total:</b> Rp <?= number_format($trx['total_price'],0,',','.') ?></p>
    <p><b>Date:</b> <?= $trx['date_created'] ?></p>
    <p><b>Status:</b> <?= ucfirst($trx['status']) ?></p>

    <a href="../products/index.php">Continue Shopping</a>
</div>

</body>
</html>
