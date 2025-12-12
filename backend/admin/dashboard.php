<?php
// /backend/admin/dashboard.php
require_once __DIR__ . '/../config/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json; charset=utf-8');

function fail($msg, $code = 400) {
  http_response_code($code);
  echo json_encode(['ok' => false, 'error' => $msg]);
  exit;
}

function resolveImage($row) {
  foreach (['image','image_name','img','gambar'] as $k) {
    if (!empty($row[$k])) {
      return '/TUBES_2_Toko/assets/products/' . rawurlencode($row[$k]);
    }
  }
  return '/TUBES_2_Toko/assets/products/placeholder.png';
}

// ===== auth admin =====
if (empty($_SESSION['user_id'])) fail('Unauthorized', 401);

$uid = (int)$_SESSION['user_id'];
$stmt = $mysqli->prepare("SELECT id, username, role FROM user WHERE id=? LIMIT 1");
$stmt->bind_param('i', $uid);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$admin || ($admin['role'] ?? '') !== 'admin') fail('Forbidden', 403);

// ===== params =====
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;

$q = trim($_GET['q'] ?? '');
$category = trim($_GET['category'] ?? '');
$stock = trim($_GET['stock'] ?? '');       // in|low|out
$sort = trim($_GET['sort'] ?? 'newest');   // newest|oldest|price_asc|price_desc

$w = [];
$params = [];
$types = "";

// search
if ($q !== '') {
  $w[] = "(name LIKE ? OR title LIKE ?)";
  $like = "%{$q}%";
  $params[] = $like;
  $params[] = $like;
  $types .= "ss";
}

// category
if ($category !== '') {
  $w[] = "category = ?";
  $params[] = $category;
  $types .= "s";
}

// stock filter
if ($stock === 'out') {
  $w[] = "stock <= 0";
} elseif ($stock === 'low') {
  $w[] = "stock > 0 AND stock <= 50";
} elseif ($stock === 'in') {
  $w[] = "stock > 50";
}

$whereSql = $w ? ("WHERE " . implode(" AND ", $w)) : "";

// sort
$orderSql = "ORDER BY id DESC";
if ($sort === 'oldest')     $orderSql = "ORDER BY id ASC";
if ($sort === 'price_asc')  $orderSql = "ORDER BY price ASC, id DESC";
if ($sort === 'price_desc') $orderSql = "ORDER BY price DESC, id DESC";

// ===== count total for pagination =====
$sqlCount = "SELECT COUNT(*) AS cnt FROM products $whereSql";
$stmt = $mysqli->prepare($sqlCount);
if (!$stmt) fail("DB_ERROR");
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$total = (int)$stmt->get_result()->fetch_assoc()['cnt'];
$stmt->close();

$totalPages = max(1, (int)ceil($total / $perPage));
if ($page > $totalPages) $page = $totalPages;

$offset = ($page - 1) * $perPage;

// ===== query page data =====
$sql = "
  SELECT id, name, price, stock, category, image, image_name, img, gambar
  FROM products
  $whereSql
  $orderSql
  LIMIT ? OFFSET ?
";
$stmt = $mysqli->prepare($sql);
if (!$stmt) fail("DB_ERROR");

if ($params) {
  $types2 = $types . "ii";
  $params2 = array_merge($params, [$perPage, $offset]);
  $stmt->bind_param($types2, ...$params2);
} else {
  $stmt->bind_param("ii", $perPage, $offset);
}

$stmt->execute();
$res = $stmt->get_result();

$products = [];
while ($row = $res->fetch_assoc()) {
  $products[] = [
    'id' => (int)$row['id'],
    'name' => $row['name'] ?? 'Untitled',
    'price' => (float)($row['price'] ?? 0),
    'stock' => (int)($row['stock'] ?? 0),
    'category' => $row['category'] ?? '',
    'image' => resolveImage($row),
  ];
}
$stmt->close();

$showing = count($products);

// ===== filters meta: categories list =====
$categories = [];
$cres = $mysqli->query("
  SELECT DISTINCT category
  FROM products
  WHERE category IS NOT NULL AND category <> ''
  ORDER BY category
");
if ($cres) {
  while ($r = $cres->fetch_assoc()) $categories[] = $r['category'];
  $cres->free();
}

// ===== stats =====
$stats = [
  'totalProducts' => 0,
  'totalCustomers' => 0,
  'totalActiveCustomers' => 0,
  'totalLowStock' => 0,
];

// total products
$r = $mysqli->query("SELECT COUNT(*) AS c FROM products")->fetch_assoc();
$stats['totalProducts'] = (int)($r['c'] ?? 0);

// low stock
$r = $mysqli->query("SELECT COUNT(*) AS c FROM products WHERE stock > 0 AND stock <= 50")->fetch_assoc();
$stats['totalLowStock'] = (int)($r['c'] ?? 0);

// customers
$r = $mysqli->query("SELECT COUNT(*) AS c FROM user WHERE role='customer'")->fetch_assoc();
$stats['totalCustomers'] = (int)($r['c'] ?? 0);

// active customers (kalau kolom is_active ada)
$r = $mysqli->query("SELECT COUNT(*) AS c FROM user WHERE role='customer' AND is_active=1")->fetch_assoc();
$stats['totalActiveCustomers'] = (int)($r['c'] ?? 0);

echo json_encode([
  'ok' => true,
  'admin' => [
    'id' => (int)$admin['id'],
    'username' => $admin['username'] ?? 'Admin',
  ],
  'stats' => $stats,
  'filters' => [
    'categories' => $categories,
  ],
  'pagination' => [
    'page' => $page,
    'per_page' => $perPage,
    'total' => $total,
    'total_pages' => $totalPages,
    'showing' => $showing,
  ],
  'products' => $products,
]);