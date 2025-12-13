<?php
session_start();
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
  http_response_code(401);
  echo json_encode(["ok" => false, "message" => "Not logged in"]);
  exit;
}

$user_id = (int)$_SESSION['user_id'];

if (!isset($_SESSION['transaction_id']) || (int)$_SESSION['transaction_id'] <= 0) {
  http_response_code(400);
  echo json_encode(["ok" => false, "message" => "No transaction in session"]);
  exit;
}

$transaction_id = (int)$_SESSION['transaction_id'];
$method = $_POST['method'] ?? null;
$ewallet_type = $_POST['ewallet_type'] ?? null;

if (!$method) {
  http_response_code(400);
  echo json_encode(["ok" => false, "message" => "method is required"]);
  exit;
}

// pastikan transaksi milik user + ambil total dari DB (ini yg jadi source of truth)
$chk = $mysqli->prepare("SELECT id, total_price, status FROM transactions WHERE id = ? AND user_id = ?");
$chk->bind_param("ii", $transaction_id, $user_id);
$chk->execute();
$trx = $chk->get_result()->fetch_assoc();

if (!$trx) {
  http_response_code(404);
  echo json_encode(["ok" => false, "message" => "Transaction not found"]);
  exit;
}

// update status (PASTIKAN value ini ada di ENUM kamu)
$newStatus = ($method === "cod") ? "Payment Success" : "Waiting for Payment";

$u = $mysqli->prepare("UPDATE transactions SET status = ? WHERE id = ? AND user_id = ?");
$u->bind_param("sii", $newStatus, $transaction_id, $user_id);
$u->execute();

if ($u->errno) {
  http_response_code(500);
  echo json_encode(["ok" => false, "message" => "DB error", "error" => $u->error]);
  exit;
}

// jangan unset session dulu (biar halaman bank/ewallet masih bisa fetch by id)
echo json_encode([
  "ok" => true,
  "transaction_id" => $transaction_id,
  "payment_method" => $method,
  "ewallet_type" => $ewallet_type,
  "total" => (int)$trx["total_price"]
]);
exit;