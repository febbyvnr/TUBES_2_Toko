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
$confirm = $_POST['confirm_password'] ?? '';

if ($confirm === '') {
  echo json_encode(['ok'=>false,'error'=>'PASSWORD_REQUIRED']);
  exit;
}

$stmt = $mysqli->prepare("SELECT password, profile_photo FROM user WHERE id=? LIMIT 1");
if (!$stmt) { echo json_encode(['ok'=>false,'error'=>'DB_ERROR']); exit; }

$stmt->bind_param('i', $uid);
$stmt->execute();
$me = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$me) { echo json_encode(['ok'=>false,'error'=>'NOT_FOUND']); exit; }

if (!password_verify($confirm, $me['password'])) {
  echo json_encode(['ok'=>false,'error'=>'PASSWORD_MISMATCH']);
  exit;
}

if (!empty($me['profile_photo'])) {
  $oldPath = __DIR__ . '/../assets/profile/' . $me['profile_photo'];
  if (is_file($oldPath)) @unlink($oldPath);
}

$del = $mysqli->prepare("DELETE FROM user WHERE id=? LIMIT 1");
if (!$del) { echo json_encode(['ok'=>false,'error'=>'DB_ERROR']); exit; }
$del->bind_param('i', $uid);

if (!$del->execute()) {
  $del->close();
  echo json_encode(['ok'=>false,'error'=>'DELETE_FAILED']);
  exit;
}
$del->close();

$_SESSION = [];
if (ini_get('session.use_cookies')) {
  $params = session_get_cookie_params();
  setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
}
session_destroy();

echo json_encode(['ok'=>true]);