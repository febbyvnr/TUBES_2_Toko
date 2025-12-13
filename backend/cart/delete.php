<?php
session_start();
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');

$user_id = $_SESSION['user_id'] ?? 0;
$id = (int) ($_GET['id'] ?? 0);

$stmt = $mysqli->prepare(
    "DELETE FROM cart WHERE id=? AND user_id=?"
);
$stmt->bind_param("ii", $id, $user_id);
$stmt->execute();

echo json_encode(['success' => true]);
