<?php
require_once __DIR__ . '/../config/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

/**
 * RESPONSE FORMAT:
 * {
 *   ok: boolean,
 *   status: "success" | "info" | "error",
 *   message: string
 * }
 */

header('Content-Type: application/json; charset=UTF-8');

// ambil token
$token = trim($_GET['token'] ?? '');

if ($token === '') {
    echo json_encode([
        'ok'      => false,
        'status'  => 'error',
        'message' => 'Activation token is missing.'
    ]);
    exit;
}

// cari user berdasarkan token
$stmt = $mysqli->prepare("
    SELECT id, is_active
    FROM user
    WHERE activation_token = ?
    LIMIT 1
");

if (!$stmt) {
    echo json_encode([
        'ok'      => false,
        'status'  => 'error',
        'message' => 'Database error.'
    ]);
    exit;
}

$stmt->bind_param('s', $token);
$stmt->execute();
$res  = $stmt->get_result();
$user = $res->fetch_assoc();
$stmt->close();

// token tidak valid
if (!$user) {
    echo json_encode([
        'ok'      => false,
        'status'  => 'error',
        'message' => 'Invalid or expired activation token.'
    ]);
    exit;
}

// sudah aktif
if ((int)$user['is_active'] === 1) {
    echo json_encode([
        'ok'      => true,
        'status'  => 'info',
        'message' => 'Your account is already active. Please login.'
    ]);
    exit;
}

// aktivasi akun
$stmt = $mysqli->prepare("
    UPDATE user
    SET is_active = 1,
        activation_token = NULL
    WHERE id = ?
");

if (!$stmt) {
    echo json_encode([
        'ok'      => false,
        'status'  => 'error',
        'message' => 'Failed to activate account.'
    ]);
    exit;
}

$stmt->bind_param('i', $user['id']);

if ($stmt->execute()) {
    echo json_encode([
        'ok'      => true,
        'status'  => 'success',
        'message' => 'Your account has been successfully activated. You can now login.'
    ]);
} else {
    echo json_encode([
        'ok'      => false,
        'status'  => 'error',
        'message' => 'Activation failed. Please try again.'
    ]);
}

$stmt->close();
exit;