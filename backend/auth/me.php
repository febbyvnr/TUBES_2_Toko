<?php
// backend/auth/me.php
require_once __DIR__ . '/../config/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

if (empty($_SESSION['user_id'])) {
  echo json_encode(['ok' => true, 'logged_in' => false]);
  exit;
}

$uid = (int) $_SESSION['user_id'];

$stmt = $mysqli->prepare("SELECT id, username, profile_photo FROM user WHERE id = ? LIMIT 1");
if (!$stmt) {
  echo json_encode(['ok' => false, 'error' => 'DB_ERROR']);
  exit;
}

$stmt->bind_param('i', $uid);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
  echo json_encode(['ok' => true, 'logged_in' => false]);
  exit;
}

$photoUrl = null;
if (!empty($user['profile_photo'])) {
  // pastikan folder sesuai project kamu
  $photoUrl = '/TUBES_2_Toko/assets/profile/' . rawurlencode($user['profile_photo']);
}

echo json_encode([
  'ok' => true,
  'logged_in' => true,
  'user' => [
    'id' => (int)$user['id'],
    'username' => $user['username'] ?? 'User',
    'profile_photo_url' => $photoUrl
  ]
]);