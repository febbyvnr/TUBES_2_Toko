<?php
// backend/includes/sliderImages.php
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

$limit = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 8;

// PATH YANG BENAR (gambar ada di /TUBES_2_Toko/assets/products/)
$BASE_IMG_URL = "/TUBES_2_Toko/frontend/assets/products/";

$sql = "
    SELECT image
    FROM products
    WHERE image IS NOT NULL AND image <> ''
    ORDER BY id DESC
    LIMIT ?
";

$stmt = $mysqli->prepare($sql);
if (!$stmt) {
    echo json_encode(['ok' => false, 'error' => 'DB prepare failed']);
    exit;
}

$stmt->bind_param('i', $limit);
$stmt->execute();
$res = $stmt->get_result();

$images = [];
while ($row = $res->fetch_assoc()) {
    $images[] = $BASE_IMG_URL . rawurlencode($row['image']);
}

$stmt->close();

echo json_encode([
    'ok' => true,
    'images' => $images
]);