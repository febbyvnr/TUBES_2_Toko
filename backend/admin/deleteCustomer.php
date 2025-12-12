<?php
require_once __DIR__ . '/../config/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json; charset=UTF-8');

function fail($msg, $code = 400) {
  http_response_code($code);
  echo json_encode(['ok' => false, 'error' => $msg]);
  exit;
}

/* ===== ADMIN CHECK ===== */
if (empty($_SESSION['user_id'])) fail('UNAUTHORIZED', 401);

$adminId = (int)$_SESSION['user_id'];
$stmt = $mysqli->prepare("SELECT role FROM user WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $adminId);
$stmt->execute();
$adminRow = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$adminRow || $adminRow['role'] !== 'admin') fail('FORBIDDEN', 403);

/* ===== INPUT ===== */
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id <= 0) fail('INVALID_ID');

if ($id === $adminId) fail('CANNOT_DELETE_SELF');

/* ===== DELETE (only role=user) ===== */
$stmt = $mysqli->prepare("DELETE FROM user WHERE id = ? AND role = 'user' LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();

$affected = $stmt->affected_rows;
$stmt->close();

if ($affected <= 0) fail('NOT_FOUND_OR_NOT_USER', 404);

echo json_encode(['ok' => true]);