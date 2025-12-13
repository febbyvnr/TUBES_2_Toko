<?php
require_once __DIR__ . '/../config/db.php';
session_start();

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'Missing product id']);
  exit;
}

$id = (int)$_GET['id'];

$stmt = $mysqli->prepare("SELECT * FROM products WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
  http_response_code(404);
  echo json_encode(['ok' => false, 'error' => 'Product not found']);
  exit;
}

$product = $res->fetch_assoc();

// resolve image
$image = '/TUBES_2_Toko/assets/products/placeholder.png';
foreach (['image', 'image_name', 'img', 'gambar'] as $key) {
  if (!empty($product[$key])) {
    $image = '/TUBES_2_Toko/assets/products/' . $product[$key];
    break;
  }
}

$data = [
  'id'          => $product['id'],
  'title'       => $product['name'] ?? $product['title'] ?? 'Untitled Product',
  'price'       => (int)$product['price'],
  'price_text'  => 'Rp ' . number_format($product['price'], 0, ',', '.'),
  'description' => $product['description'] ?? 'No description available.',
  'image'       => $image,
  'sizes'       => ['XS','S','M','L','XL','All Size'],
  'isLoggedIn'  => !empty($_SESSION['user_id'])
];

echo json_encode(['ok' => true, 'data' => $data]);
