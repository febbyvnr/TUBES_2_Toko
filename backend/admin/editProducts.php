<?php
require_once __DIR__ . '/../config/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json; charset=UTF-8');

function json_exit(int $code, array $payload) {
  http_response_code($code);
  echo json_encode($payload);
  exit;
}

if (!isset($_SESSION['user_id'])) {
  json_exit(401, ['ok' => false, 'message' => 'UNAUTHORIZED']);
}

$user_id = (int)$_SESSION['user_id'];

$st = $mysqli->prepare("SELECT role FROM user WHERE id = ? LIMIT 1");
$st->bind_param("i", $user_id);
$st->execute();
$me = $st->get_result()->fetch_assoc();
$st->close();

if (!$me || $me['role'] !== 'admin') {
  json_exit(403, ['ok' => false, 'message' => 'FORBIDDEN']);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
  if ($id <= 0) json_exit(400, ['ok' => false, 'message' => 'Invalid id']);

  $q = $mysqli->prepare("SELECT id, name, description, price, stock, category, image FROM products WHERE id = ? LIMIT 1");
  $q->bind_param("i", $id);
  $q->execute();
  $p = $q->get_result()->fetch_assoc();
  $q->close();

  if (!$p) json_exit(404, ['ok' => false, 'message' => 'Product not found']);
  json_exit(200, ['ok' => true, 'data' => $p]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  json_exit(405, ['ok' => false, 'message' => 'METHOD_NOT_ALLOWED']);
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id <= 0) json_exit(400, ['ok' => false, 'message' => 'Invalid id']);

$name = trim($_POST['name'] ?? '');
$description = trim($_POST['description'] ?? '');
$category = trim($_POST['category'] ?? '');
$priceRaw = $_POST['price'] ?? '';
$stockRaw = $_POST['stock'] ?? '';

$errors = [];
if ($name === '') $errors[] = 'Product name is required.';
if ($description === '') $errors[] = 'Description is required.';
if ($category === '') $errors[] = 'Category is required.';
if ($priceRaw === '' || !is_numeric($priceRaw) || (float)$priceRaw < 0) $errors[] = 'Price must be a valid number.';
if ($stockRaw === '' || !is_numeric($stockRaw) || (int)$stockRaw < 0) $errors[] = 'Stock must be a valid number.';

if ($errors) json_exit(422, ['ok' => false, 'errors' => $errors]);

$price = (int)$priceRaw;
$stock = (int)$stockRaw;

$chk = $mysqli->prepare("SELECT id FROM products WHERE id = ? LIMIT 1");
$chk->bind_param("i", $id);
$chk->execute();
$exists = $chk->get_result()->fetch_assoc();
$chk->close();

if (!$exists) json_exit(404, ['ok' => false, 'message' => 'Product not found']);

$u = $mysqli->prepare("
  UPDATE products
  SET name = ?, description = ?, price = ?, stock = ?, category = ?
  WHERE id = ?
  LIMIT 1
");
$u->bind_param("ssiisi", $name, $description, $price, $stock, $category, $id);

if (!$u->execute()) {
  $err = $u->error;
  $u->close();
  json_exit(500, ['ok' => false, 'message' => 'Database update failed', 'db_error' => $err]);
}
$u->close();

json_exit(200, ['ok' => true, 'message' => 'Product updated']);