<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_GET['id'])) {
    die("Missing transaction ID");
}

$trx_id = intval($_GET['id']);

$update = $mysqli->prepare("UPDATE transactions SET status = 'success' WHERE id = ?");
$update->bind_param("i", $trx_id);
$update->execute();

header("Location: paymentSuccess.php?transaction_id=" . $trx_id);
exit;
