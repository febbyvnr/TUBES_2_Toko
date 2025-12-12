<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// ===== CEK LOGIN ADMIN =====
if (!isset($_SESSION['user_id'])) {
    header('Location: /TUBES_2_Toko/auth/login.php');
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$stmt = $mysqli->prepare("SELECT role FROM user WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$resUser = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$resUser || $resUser['role'] !== 'admin') {
    header('Location: /TUBES_2_Toko/index.php');
    exit;
}

// =============================
// DELETE PRODUCT
// =============================
if (!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit;
}

$productId = (int)$_GET['id'];

// GET OLD IMAGE
$stmt = $mysqli->prepare("SELECT image FROM products WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $productId);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();
$stmt->close();

if (!$product) {
    header("Location: dashboard.php");
    exit;
}

$oldImage = $product['image'];
$imagePath = __DIR__ . '/../assets/products/' . $oldImage;

// DELETE IMAGE FILE IF EXISTS
if ($oldImage && file_exists($imagePath)) {
    unlink($imagePath);
}

// DELETE PRODUCT FROM DATABASE
$stmt = $mysqli->prepare("DELETE FROM products WHERE id = ?");
$stmt->bind_param("i", $productId);

if ($stmt->execute()) {
    header("Location: dashboard.php?msg=deleted");
    exit;
} else {
    echo "Failed to delete product!";
}
?>
