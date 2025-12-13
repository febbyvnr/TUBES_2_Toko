<?php
require_once __DIR__ . '/../config/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json; charset=UTF-8');

/* =======================
   AUTH CHECK (ADMIN ONLY)
   ======================= */
if (!isset($_SESSION['user_id'])) {
  http_response_code(401);
  echo json_encode(['ok' => false, 'error' => 'UNAUTHORIZED']);
  exit;
}

$user_id = (int)$_SESSION['user_id'];
$stmt = $mysqli->prepare("SELECT username, role FROM user WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$admin || $admin['role'] !== 'admin') {
  http_response_code(403);
  echo json_encode(['ok' => false, 'error' => 'FORBIDDEN']);
  exit;
}

/* =======================
   METHOD CHECK
   ======================= */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['ok' => false, 'error' => 'METHOD_NOT_ALLOWED']);
  exit;
}

/* =======================
   INPUT
   ======================= */
$name     = trim($_POST['name'] ?? '');
$desc     = trim($_POST['description'] ?? '');
$priceRaw = $_POST['price'] ?? '';
$stockRaw = $_POST['stock'] ?? '';
$category = trim($_POST['category'] ?? '');

$errors = [];

/* =======================
   VALIDATION
   ======================= */
if ($name === '') $errors[] = 'Product name is required.';
if ($desc === '') $errors[] = 'Description is required.';
if ($category === '') $errors[] = 'Category is required.';

if ($priceRaw === '' || !is_numeric($priceRaw) || (float)$priceRaw < 0) {
  $errors[] = 'Price must be a valid number.';
}
if ($stockRaw === '' || !is_numeric($stockRaw) || (int)$stockRaw < 0) {
  $errors[] = 'Stock must be a valid number.';
}

$price = (float)$priceRaw;
$stock = (int)$stockRaw;

/* =======================
   UPLOAD IMAGE (OPTIONAL)
   ======================= */
$imageFileName = null;

if (isset($_FILES['image']) && is_array($_FILES['image'])) {
  $f = $_FILES['image'];

  if (!defined('UPLOAD_ERR_NO_FILE')) {
    // fallback kalau environment aneh (harusnya ada di PHP)
    define('UPLOAD_ERR_NO_FILE', 4);
  }

  if (($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
    if ($f['error'] !== UPLOAD_ERR_OK) {
      $errors[] = 'Image upload error.';
    } else {
      $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
      $allowed = ['jpg', 'jpeg', 'png', 'webp'];

      if (!in_array($ext, $allowed, true)) {
        $errors[] = 'Image must be JPG, PNG, or WEBP.';
      } else if (($f['size'] ?? 0) > 10 * 1024 * 1024) {
        $errors[] = 'Image max size is 10MB.';
      } else {
        $uploadDir = realpath(__DIR__ . '/../assets') ?: (__DIR__ . '/../assets');
        $uploadDir = rtrim($uploadDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'products' . DIRECTORY_SEPARATOR;

        if (!is_dir($uploadDir)) {
          if (!mkdir($uploadDir, 0755, true)) {
            $errors[] = 'Failed to create upload directory.';
          }
        }

        if (empty($errors)) {
          $safeBase = preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($f['name']));
          $imageFileName = time() . '_' . $safeBase;
          $targetPath = $uploadDir . $imageFileName;

          if (!move_uploaded_file($f['tmp_name'], $targetPath)) {
            $errors[] = 'Failed to save uploaded image.';
            $imageFileName = null;
          }
        }
      }
    }
  }
}

/* =======================
   RETURN ERRORS
   ======================= */
if (!empty($errors)) {
  http_response_code(422);
  echo json_encode(['ok' => false, 'errors' => $errors]);
  exit;
}

/* =======================
   INSERT PRODUCT
   ======================= */
$stmt = $mysqli->prepare("
  INSERT INTO products (name, description, price, stock, category, image)
  VALUES (?, ?, ?, ?, ?, ?)
");

if (!$stmt) {
  if ($imageFileName) {
    @unlink(__DIR__ . '/../assets/products/' . $imageFileName);
  }
  http_response_code(500);
  echo json_encode(['ok' => false, 'errors' => ['Database query failed.']]);
  exit;
}

$stmt->bind_param('ssdis s', $name, $desc, $price, $stock, $category, $imageFileName);