<?php
session_start();
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'], $_SESSION['transaction_id'])) {
    echo json_encode(["ok" => false, "message" => "Session invalid"]);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$tid = (int)$_SESSION['transaction_id'];

$q = $mysqli->prepare("
    UPDATE transactions
    SET status = 'Cancelled'
    WHERE id = ? AND user_id = ?
");
$q->bind_param("ii", $tid, $user_id);
$q->execute();

if ($q->errno) {
    echo json_encode(["ok" => false, "message" => "Failed cancel transaction", "error" => $q->error]);
    exit;
}

unset($_SESSION['transaction_id']);
unset($_SESSION['checkout_ids']);

echo json_encode(["ok" => true]);
exit;