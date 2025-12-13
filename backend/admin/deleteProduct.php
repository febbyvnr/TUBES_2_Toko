<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /TUBES_2_Toko/frontend/auth/login.html");
    exit;
}

$user_id = (int)$_SESSION['user_id'];

$stmt = $mysqli->prepare("SELECT role FROM user WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user || $user['role'] !== 'admin') {
    http_response_code(403);
    echo "Forbidden";
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header("Location: /TUBES_2_Toko/frontend/admin/dashboard.html");
    exit;
}

$stmt = $mysqli->prepare("SELECT image FROM products WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$product) {
    header("Location: /TUBES_2_Toko/frontend/admin/dashboard.html");
    exit;
}

if (!empty($product['image'])) {
    $imagePath = __DIR__ . '/../assets/products/' . $product['image'];
    if (file_exists($imagePath)) {
        unlink($imagePath);
    }
}

$stmt = $mysqli->prepare("DELETE FROM products WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->close();

header("Location: /TUBES_2_Toko/frontend/admin/dashboard.html?deleted=1");
exit;