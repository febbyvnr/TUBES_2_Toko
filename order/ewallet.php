<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_GET['transaction_id'])) {
    die("Transaction ID missing.");
}

$transaction_id = intval($_GET['transaction_id']);
$wallet_type = $_GET['type'] ?? "Dana"; // default Dana jika tidak ada

// Ambil data transaksi
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

// Generate nomor pembayaran E-Wallet (tidak disimpan ke DB)
$wallet_number = "08" . str_pad($transaction_id, 10, "5", STR_PAD_LEFT);

// Deadline pembayaran (24 jam)
$deadline = date("d M Y, H:i", strtotime("+24 hours", strtotime($trx['date_created'])));

// Tentukan logo berdasarkan e-wallet
$logo = "";
if ($wallet_type == "Dana") {
    $logo = "/TUBES_2_Toko/assets/dana.png";
}
if ($wallet_type == "OVO") {
    $logo = "/TUBES_2_Toko/assets/ovo.png";
}
if ($wallet_type == "Gopay") {
    $logo = "/TUBES_2_Toko/assets/gopay.png";
}

?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Pembayaran - E-Wallet</title>

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

.wallet-box {
    border-top: 1px solid #eee;
    padding-top: 25px;
    margin-top: 20px;
}

.wallet-number {
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
function copyWallet() {
    navigator.clipboard.writeText("<?= $wallet_number ?>");
    alert("Nomor E-Wallet disalin!");
}
</script>

</head>

<body>

<div class="container">

    <div class="section-title">Pembayaran E-Wallet (<?= htmlspecialchars($wallet_type) ?>)</div>

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

    <!-- NOMOR PEMBAYARAN -->
    <div class="wallet-box">
        <div style="display:flex; align-items:center; gap:10px; font-size:20px;">
            <img src="<?= $logo ?>" width="38">
            <b><?= htmlspecialchars($wallet_type) ?></b>
        </div>

        <p style="margin:15px 0 5px;">Nomor Pembayaran</p>
        <span class="wallet-number"><?= $wallet_number ?></span>
        <span class="copy-btn" onclick="copyWallet()">SALIN</span>
    </div>

    <hr>

    <!-- INSTRUKSI -->
    <div class="instructions">
        <h3>Cara Membayar via <?= htmlspecialchars($wallet_type) ?></h3>

        <?php if ($wallet_type === "Dana"): ?>
            <div class="step">1. Buka aplikasi <b>DANA</b></div>
            <div class="step">2. Pilih menu <b>Kirim</b></div>
        <?php elseif ($wallet_type === "OVO"): ?>
            <div class="step">1. Buka aplikasi <b>OVO</b></div>
            <div class="step">2. Pilih menu <b>Transfer</b></div>
        <?php else: ?>
            <div class="step">1. Buka aplikasi <b>Gopay</b></div>
            <div class="step">2. Pilih menu <b>Bayar / Transfer</b></div>
        <?php endif; ?>

        <div class="step">3. Masukkan nomor tujuan <b><?= $wallet_number ?></b></div>
        <div class="step">4. Masukkan nominal pembayaran</div>
        <div class="step">5. Klik <b>Bayar</b> untuk menyelesaikan transaksi</div>
    </div>

    <!-- CHECK STATUS BUTTON -->
    <div class="check-status-wrapper">
        <a href="updateStatus.php?id=<?= $transaction_id ?>">
            <button class="check-status-btn">Check Status</button>
        </a>


    </div>

</div>

<script>
// COUNTDOWN 24 JAM
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
