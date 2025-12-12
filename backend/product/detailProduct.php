<?php
// backend/product/detailProduct.php
require_once __DIR__ . '/../config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

// ===== CEK PARAM ID =====
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode([
        'ok' => false,
        'error' => 'INVALID_PRODUCT_ID'
    ]);
    exit;
}

$productId = (int) $_GET['id'];

// ===== QUERY PRODUK =====
$stmt = $mysqli->prepare("
    SELECT id, name, description, price, stock, category, image
    FROM products
    WHERE id = ?
    LIMIT 1
");
$stmt->bind_param("i", $productId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        'ok' => false,
        'error' => 'PRODUCT_NOT_FOUND'
    ]);
    exit;
}

$product = $result->fetch_assoc();
$stmt->close();

// ===== RESOLVE IMAGE =====
$imageUrl = '/TUBES_2_Toko/assets/products/placeholder.png';
if (!empty($product['image'])) {
    $imageUrl = '/TUBES_2_Toko/assets/products/' . rawurlencode($product['image']);
}

// ===== FORMAT DATA =====
$data = [
    'id' => (int) $product['id'],
    'title' => $product['name'],
    'price' => (int) $product['price'],
    'price_text' => 'Rp ' . number_format($product['price'], 0, ',', '.'),
    'description' => $product['description'],
    'description_html' => nl2br(htmlspecialchars($product['description'])),
    'stock' => (int) $product['stock'],
    'category' => $product['category'],
    'image_url' => $imageUrl,
    'size' => '' // optional (kalau nanti ada field size di DB)
];

// ===== STATUS LOGIN =====
$isLoggedIn = !empty($_SESSION['user_id']);

// ===== RESPONSE =====
echo json_encode([
    'ok' => true,
    'data' => [
        'product' => $data,
        'isLoggedIn' => $isLoggedIn
    ]
]);
exit;