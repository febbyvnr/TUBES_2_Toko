<?php
session_start();
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
  echo json_encode(["ok" => false, "message" => "Not logged in"]);
  exit;
}

$user_id = (int)$_SESSION['user_id'];

if (!isset($_SESSION['checkout_ids']) || empty($_SESSION['checkout_ids'])) {
  echo json_encode(["ok" => false, "message" => "No selected items"]);
  exit;
}

if (isset($_SESSION['transaction_id']) && (int)$_SESSION['transaction_id'] > 0) {
  $tid = (int)$_SESSION['transaction_id'];

  $chk = $mysqli->prepare("SELECT status FROM transactions WHERE id = ? AND user_id = ?");
  $chk->bind_param("ii", $tid, $user_id);
  $chk->execute();
  $row = $chk->get_result()->fetch_assoc();

  if ($row && ($row['status'] === 'Order Created' || $row['status'] === 'Waiting for Payment')) {
    echo json_encode(["ok" => true, "data" => ["transaction_id" => $tid, "note" => "reuse_existing"]]);
    exit;
  }

  unset($_SESSION['transaction_id']);
}

$selected = $_SESSION['checkout_ids'];       
$cartIds = array_map('intval', array_keys($selected));
$in = implode(',', $cartIds);

if (!$in) {
  echo json_encode(["ok" => false, "message" => "Invalid selection"]);
  exit;
}

$q = $mysqli->prepare("
  SELECT c.id AS cart_id, c.product_id, c.quantity, c.size,
         p.price
  FROM cart c
  JOIN products p ON c.product_id = p.id
  WHERE c.user_id = ?
    AND c.id IN ($in)
");
$q->bind_param("i", $user_id);
$q->execute();
$res = $q->get_result();

$items = [];
$total = 0;

while ($row = $res->fetch_assoc()) {
  $items[] = $row;
  $total += ((int)$row['price'] * (int)$row['quantity']);
}

if (empty($items)) {
  echo json_encode(["ok" => false, "message" => "Selected items not found"]);
  exit;
}

$mysqli->begin_transaction();

try {
  $insT = $mysqli->prepare("INSERT INTO transactions (user_id, total_price) VALUES (?, ?)");
  $insT->bind_param("ii", $user_id, $total);
  $insT->execute();

  $transaction_id = $insT->insert_id;

  // insert detail_transaction
  $insD = $mysqli->prepare("
    INSERT INTO detail_transaction (transaction_id, product_id, size, price, quantity)
    VALUES (?, ?, ?, ?, ?)
  ");

  foreach ($items as $it) {
    $pid = (int)$it['product_id'];
    $qty = (int)$it['quantity'];
    $price = (int)$it['price'];
    $size = (string)$it['size'];

    $insD->bind_param("iisii", $transaction_id, $pid, $size, $price, $qty);
    $insD->execute();
  }

  $_SESSION['transaction_id'] = $transaction_id;

  $mysqli->commit();

  echo json_encode(["ok" => true, "data" => ["transaction_id" => $transaction_id, "total" => $total]]);
} catch (Throwable $e) {
  $mysqli->rollback();
  echo json_encode(["ok" => false, "message" => "Failed create transactions", "error" => $e->getMessage()]);
}