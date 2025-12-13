<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'])) {
    die("Not logged in");
}

$user_id = (int)$_SESSION['user_id'];
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    die("Invalid transaction ID");
}

$chk = $mysqli->prepare("SELECT id, status FROM transactions WHERE id = ? AND user_id = ?");
$chk->bind_param("ii", $id, $user_id);
$chk->execute();
$trx = $chk->get_result()->fetch_assoc();

if (!$trx) {
    die("Transaction not found");
}

$u = $mysqli->prepare("UPDATE transactions SET status = 'Payment Success' WHERE id = ? AND user_id = ?");
$u->bind_param("ii", $id, $user_id);
$u->execute();

$del = $mysqli->prepare("
    DELETE c
    FROM cart c
    JOIN detail_transaction d
      ON d.product_id = c.product_id
     AND d.size = c.size
    WHERE c.user_id = ?
      AND d.transaction_id = ?
");
$del->bind_param("ii", $user_id, $id);
$del->execute();

unset($_SESSION['checkout_ids']);
if (isset($_SESSION['transaction_id']) && (int)$_SESSION['transaction_id'] === $id) {
    unset($_SESSION['transaction_id']);
}

header("Location: /TUBES_2_Toko/frontend/order/payment-success.html?transaction_id=" . $id);
exit;