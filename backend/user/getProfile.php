<?php
require_once __DIR__ . '/../config/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

if (empty($_SESSION['user_id'])) {
  echo json_encode(['ok' => false, 'error' => 'NOT_LOGGED_IN']);
  exit;
}

$uid = (int)$_SESSION['user_id'];

$stmt = $mysqli->prepare("SELECT id, username, email, phone, address, profile_photo FROM user WHERE id=? LIMIT 1");
if (!$stmt) { echo json_encode(['ok'=>false,'error'=>'DB_ERROR']); exit; }

$stmt->bind_param('i', $uid);
$stmt->execute();
$u = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$u) { echo json_encode(['ok'=>false,'error'=>'NOT_FOUND']); exit; }

$photoUrl = null;
if (!empty($u['profile_photo'])) {
  $photoUrl = '/TUBES_2_Toko/frontend/assets/profile/' . rawurlencode($u['profile_photo']);
}

echo json_encode([
  'ok' => true,
  'user' => [
    'id' => (int)$u['id'],
    'username' => $u['username'] ?? 'User',
    'email' => $u['email'] ?? '',
    'phone' => $u['phone'] ?? '',
    'address' => $u['address'] ?? '',
    'profile_photo_url' => $photoUrl
  ]
]);