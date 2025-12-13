<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// Jika user belum login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Ambil semua item cart user
$query = $mysqli->prepare("
    SELECT c.id AS cart_id, c.quantity, c.size,
           p.id AS product_id, p.product_name, p.price
    FROM cart c
    JOIN products p ON c.product_id = p.id
    WHERE c.user_id = ?
");
$query->bind_param("i", $user_id);
query->execute();
$result = $query->get_result();

$cart_items = [];
$total_harga = 0;

while ($row = $result->fetch_assoc()) {
    $cart_items[] = $row;
    $total_harga += ($row['price'] * $row['quantity']);
}

// Jika cart kosong
if (empty($cart_items)) {
    echo "<script>alert('Keranjang anda kosong!'); window.location.href='../cart/index.php';</script>";
    exit;
}

// Proses Checkout ketika tombol submit ditekan
if (isset($_POST['checkout'])) {

    // Insert ke tabel transaksi utama
    $insertTrans = $mysqli->prepare("
        INSERT INTO transaksi (user_id, total_harga, tanggal_transaksi)
        VALUES (?, ?, NOW())
    ");
    $insertTrans->bind_param("id", $user_id, $total_harga);
    $insertTrans->execute();

    $transaksi_id = $insertTrans->insert_id; // ambil id transaksi baru

    // Insert ke tabel detail transaksi
    $insertDetail = $mysqli->prepare("
        INSERT INTO transaksi_detail (transaksi_id, product_id, quantity, size, subtotal)
        VALUES (?, ?, ?, ?, ?)
    ");

    foreach ($cart_items as $item) {
        $subtotal = $item['price'] * $item['quantity'];
        $insertDetail->bind_param(
            "iiisd",
            $transaksi_id,
            $item['product_id'],
            $item['quantity'],
            $item['size'],
            $subtotal
        );
        $insertDetail->execute();
    }

    // Hapus cart setelah checkout selesai
    $deleteCart = $mysqli->prepare("DELETE FROM cart WHERE user_id = ?");
    $deleteCart->bind_param("i", $user_id);
    $deleteCart->execute();

    echo "<script>
            alert('Checkout berhasil! Terima kasih telah berbelanja.');
            window.location.href='../order/paymentSuccess.php?id=$transaksi_id';
          </script>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Checkout</title>

    <link rel="stylesheet" href="/TUBES_2_Toko/styles/checkout.css">
</head>


<body>

<div class="checkout-container">
    <a href="../cart/index.php" class="btn-back">&larr; Kembali ke Keranjang</a>

    <h2>Checkout</h2>

    <?php foreach ($cart_items as $item): ?>
        <div class="cart-item">
            <div class="cart-left">
                <p><strong><?= $item['product_name']; ?></strong></p>
                <p>Size: <?= strtoupper($item['size']); ?></p>
                <p>Qty: <?= $item['quantity']; ?></p>
            </div>

            <div class="cart-right">
                <p>Rp <?= number_format($item['price'], 0, ',', '.'); ?></p>
                <p>Subtotal: Rp <?= number_format($item['price'] * $item['quantity'], 0, ',', '.'); ?></p>
            </div>
        </div>
    <?php endforeach; ?>

    <div class="total-section">
        <h3>Total Pembayaran</h3>
        <h3>Rp <?= number_format($total_harga, 0, ',', '.'); ?></h3>
    </div>

    <form action="" method="POST">
        <button type="submit" name="checkout" class="btn-primary">Bayar Sekarang</button>
    </form>

</div>

</body>
</html>
