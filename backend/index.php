<?php
require_once __DIR__ . '/config/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

ini_set('display_errors', 0);
error_reporting(0);

// ===== CORS (kalau frontend beda origin) =====
// Kalau frontend kamu di Vercel, ganti jadi https://xxxxx.vercel.app
$FRONTEND_ORIGIN = "http://localhost/TUBES_2_Toko/frontend";

header('Content-Type: application/json; charset=UTF-8');
header("Access-Control-Allow-Origin: $FRONTEND_ORIGIN");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(204);
  exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
  http_response_code(405);
  echo json_encode(['ok' => false, 'errors' => ['Method not allowed']]);
  exit;
}

// helper buat path image
function resolveImage(string $imageName): string {
  $imageName = trim($imageName);
  if ($imageName === '') return '/TUBES_2_Toko/backend/assets/products/placeholder.png';
  return '/TUBES_2_Toko/backend/assets/products/' . rawurlencode($imageName);
}

$categoryCards = [];
$featuredStudio = [];
$slides = [];

// ===== Categories =====
$sqlCat = "
  SELECT DISTINCT IFNULL(category, '') AS category
  FROM products
  WHERE category IS NOT NULL AND category <> ''
  ORDER BY category
";

if ($cres = $mysqli->query($sqlCat)) {
  while ($crow = $cres->fetch_assoc()) {
    $cat = $crow['category'];

    $imagePath = '/TUBES_2_Toko/backend/assets/products/placeholder.png';

    $stmt = $mysqli->prepare("
      SELECT image
      FROM products
      WHERE category = ?
        AND image IS NOT NULL AND image <> ''
      ORDER BY id DESC
      LIMIT 1
    ");

    if ($stmt) {
      $stmt->bind_param("s", $cat);
      $stmt->execute();
      $resProd = $stmt->get_result();
      if ($prodRow = $resProd->fetch_assoc()) {
        $imagePath = resolveImage($prodRow['image'] ?? '');
      }
      $stmt->close();
    }

    $categoryCards[] = [
      'category' => $cat,
      'image'    => $imagePath,
      // link ini untuk frontend (kalau listProduct.html ada)
      'link'     => '/TUBES_2_Toko/frontend/product/listProduct.html?category=' . urlencode($cat),
    ];
  }
  $cres->free();
}

// ===== Featured Studio =====
$sqlFeat = "
  SELECT id, name, image, price
  FROM products
  WHERE name LIKE 'Studio Collection%'
  ORDER BY id DESC
  LIMIT 20
";

if ($fres = $mysqli->query($sqlFeat)) {
  while ($row = $fres->fetch_assoc()) {
    $imagePath = resolveImage($row['image'] ?? '');

    $title = !empty($row['name']) ? $row['name'] : 'Studio Collection';

    $price = '';
    if (isset($row['price'])) {
      $price = 'Rp ' . number_format((float)$row['price'], 0, ',', '.');
    }

    $featuredStudio[] = [
      'id'    => (int)$row['id'],
      'image' => $imagePath,
      'title' => $title,
      'price' => $price,
      'link'  => '/TUBES_2_Toko/frontend/product/detailProduct.html?id=' . (int)$row['id'],
    ];
  }
  $fres->free();
}

// ===== Slides (optional, dipakai login/register slideshow) =====
$sqlSlides = "
  SELECT image
  FROM products
  WHERE image IS NOT NULL AND image <> ''
  ORDER BY id DESC
  LIMIT 20
";
if ($sres = $mysqli->query($sqlSlides)) {
  while ($r = $sres->fetch_assoc()) {
    $slides[] = resolveImage($r['image'] ?? '');
  }
  $sres->free();
}

echo json_encode([
  'ok' => true,
  'data' => [
    'categories'     => $categoryCards,
    'featuredStudio' => $featuredStudio,
    'slides'         => $slides,
  ]
]);