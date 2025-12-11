<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_GET['transaction_id'])) {
    die("Transaction not found");
}

$transaction_id = intval($_GET['transaction_id']);

// Ambil data transaksi
$q = $mysqli->query("SELECT * FROM transactions WHERE id = $transaction_id");
$trx = $q->fetch_assoc();

if (!$trx) {
    die("Invalid transaction ID");
}

// TOTAL
$total_price = $trx['total_price'];

// VA Number (contoh: generate dari transaction_id)
$va_number = "1260" . str_pad($transaction_id, 10, "0", STR_PAD_LEFT);

// JATUH TEMPO (24 JAM)
$deadline = date("d M Y, H:i", strtotime("+24 hours"));
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Pembayaran - Transfer Bank</title>
<link rel="stylesheet" href="../styles/payment.css">
</head>

<body>

<div class="transfer-container">

    <h2 class="page-title">Pembayaran</h2>

    <!-- TOTAL PEMBAYARAN -->
    <div class="transfer-box">
        <div class="line">
            <span>Total Pembayaran</span>
            <span class="price">Rp <?= number_format($total_price) ?></span>
        </div>

        <div class="line">
            <span>Bayar Dalam</span>
            <span id="countdown" class="countdown">24 jam 00 menit 00 detik</span>
        </div>

        <div class="deadline">
            Jatuh tempo <?= $deadline ?>
        </div>
    </div>

    <!-- BANK INFORMATION -->
    <div class="transfer-box">
        
        <div class="bank-title">
            <img src="../assets/icons/bca.png" class="bank-icon">
            Bank BCA
        </div>

        <div class="va-label">Nomor Rekening (VA)</div>

        <div class="va-box">
            <span id="vaNumber"><?= $va_number ?></span>
            <button class="btn-copy" onclick="copyVA()">SALIN</button>
        </div>

        <div class="instruction-title">Petunjuk Transfer mBanking</div>

        <ol class="instruction-list">
            <li>Pilih <b>m-Transfer → BCA Virtual Account</b></li>
            <li>Masukkan nomor Virtual Account <b><?= $va_number ?></b> lalu pilih <b>Send</b></li>
            <li>Periksa informasi merchant & nama kamu</li>
            <li>Masukkan PIN m-BCA lalu pilih <b>OK</b></li>
        </ol>

    </div>

</div>

<script>
// COPY VA
function copyVA() {
    const text = document.getElementById("vaNumber").textContent;
    navigator.clipboard.writeText(text);
    alert("Nomor VA tersalin: " + text);
}

// COUNTDOWN 24 HOURS
let seconds = 24 * 60 * 60;

setInterval(() => {
    seconds--;

    let h = Math.floor(seconds / 3600);
    let m = Math.floor((seconds % 3600) / 60);
    let s = seconds % 60;

    document.getElementById("countdown").innerHTML =
        `${h} jam ${m} menit ${s} detik`;

}, 1000);
</script>

</body>
</html>
