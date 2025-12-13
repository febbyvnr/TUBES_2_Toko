<?php
require_once __DIR__ . '/../config/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

if (empty($_SESSION['user_id'])) {
  echo json_encode(['ok'=>false,'error'=>'NOT_LOGGED_IN']);
  exit;
}

$uid = (int)$_SESSION['user_id'];

$stmt = $mysqli->prepare("SELECT username, email, phone, address, profile_photo FROM user WHERE id=? LIMIT 1");
if (!$stmt) { echo json_encode(['ok'=>false,'error'=>'DB_ERROR']); exit; }
$stmt->bind_param('i', $uid);
$stmt->execute();
$me = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$me) { echo json_encode(['ok'=>false,'error'=>'NOT_FOUND']); exit; }

$username = trim($_POST['username'] ?? $me['username']);
$email    = trim($_POST['email'] ?? $me['email']);
$phone    = trim($_POST['phone'] ?? $me['phone']);
$address  = trim($_POST['address'] ?? $me['address']);

$profileFile = $me['profile_photo'];

if (!empty($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] !== UPLOAD_ERR_NO_FILE) {
  $f = $_FILES['profile_photo'];

  if ($f['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['ok'=>false,'error'=>'UPLOAD_ERROR']);
    exit;
  }

  $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
  if (!in_array($ext, ['jpg','jpeg','png'])) {
    echo json_encode(['ok'=>false,'error'=>'INVALID_FILE_TYPE']);
    exit;
  }

  if ($f['size'] > 64 * 1024 * 1024) {
    echo json_encode(['ok'=>false,'error'=>'FILE_TOO_LARGE']);
    exit;
  }

  $profileFile = uniqid('pf_') . '.' . $ext;

  $destDir = __DIR__ . '/../../frontend/assets/profile/';
  $dest    = $destDir . $profileFile;

  if (!is_dir($destDir)) mkdir($destDir, 0755, true);

  if (!move_uploaded_file($f['tmp_name'], $dest)) {
    echo json_encode(['ok'=>false,'error'=>'MOVE_FAILED']);
    exit;
  }

  if (!empty($me['profile_photo'])) {
    $oldPath = $destDir . $me['profile_photo'];
    if (is_file($oldPath)) @unlink($oldPath);
  }
}

$up = $mysqli->prepare("UPDATE user SET username=?, email=?, phone=?, address=?, profile_photo=? WHERE id=?");
if (!$up) { echo json_encode(['ok'=>false,'error'=>'DB_ERROR']); exit; }

$up->bind_param('sssssi', $username, $email, $phone, $address, $profileFile, $uid);

if (!$up->execute()) {
  $up->close();
  echo json_encode(['ok'=>false,'error'=>'UPDATE_FAILED']);
  exit;
}
$up->close();

$_SESSION['username'] = $username;

$photoUrl = null;
if (!empty($profileFile)) {
  $photoUrl = '/TUBES_2_Toko/frontend/assets/profile/' . rawurlencode($profileFile);
}

echo json_encode([
  'ok' => true,
  'user' => [
    'username' => $username,
    'email' => $email,
    'phone' => $phone,
    'address' => $address,
    'profile_photo_url' => $photoUrl
  ]
]);