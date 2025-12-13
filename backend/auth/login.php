<?php
require_once __DIR__ . '/../config/db.php';
session_start();

header('Content-Type: application/json; charset=utf-8');

$identifier = $_POST['identifier'] ?? '';
$password   = $_POST['password'] ?? '';

if ($identifier === '' || $password === '') {
    echo json_encode(['ok'=>false,'error'=>'Lengkapi data login']);
    exit;
}

$stmt = $mysqli->prepare("
    SELECT id, username, password, role, is_active, activation_token
    FROM `user`
    WHERE username = ? OR email = ?
    LIMIT 1
");
$stmt->bind_param('ss', $identifier, $identifier);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user || !password_verify($password, $user['password'])) {
    echo json_encode(['ok'=>false,'error'=>'Login gagal']);
    exit;
}

if ((int)$user['is_active'] !== 1) {
    echo json_encode([
        'ok' => false,
        'error' => 'Account is not active yet',
        'activation_link' => "/TUBES_2_Toko/frontend/auth/activate.html?token=" . urlencode($user['activation_token'])
    ]);
    exit;
}

$_SESSION['user_id']  = (int)$user['id'];
$_SESSION['username'] = $user['username'];
$_SESSION['role']     = $user['role'];

$redirect = "/TUBES_2_Toko/index.html";
if (($user['role'] ?? '') === 'admin') {
    $redirect = "/TUBES_2_Toko/frontend/admin/dashboard.html";
}

echo json_encode([
    'ok' => true,
    'redirect' => $redirect,
    'role' => $user['role']
]);
exit;