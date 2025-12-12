<?php
require_once __DIR__ . '/../config/db.php';
session_start();

header('Content-Type: application/json');

$identifier = $_POST['identifier'] ?? '';
$password   = $_POST['password'] ?? '';

if ($identifier === '' || $password === '') {
  echo json_encode(['ok'=>false,'error'=>'Lengkapi data login']);
  exit;
}

$stmt = $mysqli->prepare("
  SELECT id, username, password, is_active, activation_token
  FROM `user`
  WHERE username = ? OR email = ?
  LIMIT 1
");
$stmt->bind_param('ss', $identifier, $identifier);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user || !password_verify($password, $user['password'])) {
  echo json_encode(['ok'=>false,'error'=>'Login gagal']);
  exit;
}

if ((int)$user['is_active'] !== 1) {
  echo json_encode([
    'ok' => false,
    'activation_link' =>
      "http://localhost/Feyora/backend/auth/activate.php?token=".$user['activation_token']
  ]);
  exit;
}

$_SESSION['user_id'] = $user['id'];
$_SESSION['username'] = $user['username'];

echo json_encode(['ok'=>true]);