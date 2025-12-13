<?php
session_start();
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
  echo json_encode(["ok" => false, "message" => "Not logged in"]);
  exit;
}

$user_id = (int)$_SESSION['user_id'];

if (!isset($_SESSION['transaction_id'])) {
  echo json_encode(["ok" => false, "message" => "No transaction in session"]);
  exit;
}

$tid = (int)$_SESSION['transaction_id'];

$u = $mysqli->prepare("
  UPDATE transaction
  SET status = 'Waiting for Payment'
  WHERE id = ? AND user_id = ?
");
$u->bind_param("ii", $tid, $user_id);
$u->execute();

if ($u->affected_rows >= 0) {
  echo json_encode(["ok" => true, "data" => ["transaction_id" => $tid]]);
} else {
  echo json_encode(["ok" => false, "message" => "Failed update status"]);
}