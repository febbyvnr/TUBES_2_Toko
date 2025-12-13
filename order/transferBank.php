<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_GET['transaction_id'])) {
    die("Transaction ID missing.");
}

$transaction_id = intval($_GET['transaction_id']);

// Ambil data transaksi dari database
$stmt = $mysqli->prepare("SELECT total_price, date_created FROM transactions WHERE id = ?");
$stmt->bind_param("i", $transaction_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Transaction not found.");
}

$trx = $result->fetch_assoc();

// Total pembayaran
$total = $trx['total_price'];

// Generate Virtual Account (ga disimpan database)
$va_number = "126" . str_pad($transaction_id, 10, "0", STR_PAD_LEFT);

// Deadline pembayaran = 24 jam dari date_created
$deadline = date("d M Y, H:i", strtotime("+24 hours", strtotime($trx['date_created'])));
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Pembayaran - Transfer Bank</title>

<style>
body {
    font-family: Arial;
    background: #faf7f9;
    margin: 0;
}

.container {
    width: 90%;
    max-width: 800px;
    margin: 30px auto;
    background: white;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 0 10px #ddd;
}

.section-title {
    font-size: 22px;
    font-weight: bold;
    color: #ff2d7a;
    margin-bottom: 20px;
}

.row {
    display: flex;
    justify-content: space-between;
    margin: 18px 0;
    font-size: 18px;
}

.value-red {
    color: #ff2d7a;
    font-weight: bold;
}

.bank-box {
    border-top: 1px solid #eee;
    padding-top: 25px;
    margin-top: 20px;
}

.va-number {
    font-size: 26px;
    color: #ff2d7a;
    font-weight: bold;
}

.copy-btn {
    color: #0099ff;
    cursor: pointer;
    margin-left: 15px;
    font-weight: bold;
}

.instructions {
    margin-top: 30px;
    font-size: 16px;
}

.step {
    margin: 10px 0;
}

hr {
    border: none;
    height: 1px;
    background: #eee;
    margin: 20px 0;
}

/* BUTTON CHECK STATUS DI DALAM CONTAINER, KANAN BAWAH */
.check-status-wrapper {
    margin-top: 25px;
    text-align: right;
}

.check-status-btn {
    background: #ff2d7a;
    color: white;
    padding: 12px 20px;
    font-size: 16px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    box-shadow: 0 3px 8px rgba(0,0,0,0.15);
    transition: 0.2s;
}

.check-status-btn:hover {
    background: #e02468;
}
</style>

<script>
// COPY VA NUMBER
function copyVA() {
    navigator.clipboard.writeText("<?= $va_number ?>");
    alert("Nomor VA disalin!");
}
</script>

</head>
<body>

<div class="container">

    <div class="section-title">Pembayaran</div>

    <div class="row">
        <span>Total Pembayaran</span>
        <span class="value-red">Rp<?= number_format($total, 0, ',', '.') ?></span>
    </div>

    <div class="row">
        <span>Bayar Dalam</span>
        <span class="value-red" id="countdown">--</span>
    </div>

    <div style="font-size:14px; margin-top:-10px; text-align:right;">
        Jatuh tempo <?= $deadline ?>
    </div>

    <div class="bank-box">
        <div style="display:flex; align-items:center; gap:10px; font-size:20px;">
            <img src="/TUBES_2_Toko/assets/bca.png" width =70px;>
            <b>Bank BCA</b>
        </div>

        <p style="margin:15px 0 5px;">No. Rekening (Virtual Account)</p>
        <span class="va-number"><?= $va_number ?></span>
        <span class="copy-btn" onclick="copyVA()">SALIN</span>
    </div>

    <hr>

    <div class="instructions">
        <h3>Petunjuk Transfer mBanking</h3>

        <div class="step">1. Pilih <b>m-Transfer > BCA Virtual Account</b></div>
        <div class="step">2. Masukkan nomor Virtual Account <b><?= $va_number ?></b> lalu klik <b>Send</b></div>
        <div class="step">3. Periksa nama Merchant dan Total Tagihan</div>
        <div class="step">4. Masukkan PIN m-BCA dan pilih OK</div>
        <div class="step">5. Jika gagal, coba cek limit ATM / iBanking</div>
    </div>

    <!-- check status button bawah kanan -->
    <div class="check-status-wrapper">
        <a href="updateStatus.php?id=<?= $transaction_id ?>">
            <button class="check-status-btn">Check Status</button>
        </a>


    </div>

</div>

<script>
// COUNTDOWN (24 hours dari date_created)
var deadline = new Date("<?= date("Y-m-d H:i:s", strtotime("+24 hours", strtotime($trx['date_created']))) ?>").getTime();

var x = setInterval(function() {
    var now = new Date().getTime();
    var distance = deadline - now;

    if (distance < 0) {
        document.getElementById("countdown").innerHTML = "Waktu Habis";
        clearInterval(x);
        return;
    }

    var hours = Math.floor(distance / (1000 * 60 * 60));
    var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
    var seconds = Math.floor((distance % (1000 * 60)) / 1000);

    document.getElementById("countdown").innerHTML =
        hours + " jam " + minutes + " menit " + seconds + " detik";

}, 1000);
</script>

</body>
</html>
