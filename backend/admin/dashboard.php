<?php
// /TUBES_2_Toko/backend/admin/dashboard.php
require_once __DIR__ . '/../config/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json; charset=UTF-8');

function out($arr, $code = 200) {
  http_response_code($code);
  echo json_encode($arr);
  exit;
}

/* =======================
   AUTH CHECK (ADMIN ONLY)
   ======================= */
if (!isset($_SESSION['user_id'])) {
  out(['ok'=>false,'error'=>'UNAUTHORIZED'], 401);
}

$uid = (int)$_SESSION['user_id'];
$stmt = $mysqli->prepare("SELECT id, username, role FROM user WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $uid);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$admin || $admin['role'] !== 'admin') {
  out(['ok'=>false,'error'=>'FORBIDDEN'], 403);
}

/* =======================
   PARAMS
   ======================= */
$search   = trim($_GET['search'] ?? '');
$category = trim($_GET['category'] ?? '');
$stock    = trim($_GET['stock'] ?? 'all');   // all|in|low|out
$sort     = trim($_GET['sort'] ?? 'newest'); // newest|oldest|price_asc|price_desc
$page     = max(1, (int)($_GET['page'] ?? 1));
$perPage  = (int)($_GET['per_page'] ?? 20);
if ($perPage < 1) $perPage = 20;
if ($perPage > 100) $perPage = 100;

$offset = ($page - 1) * $perPage;

/* =======================
   WHERE
   ======================= */
$where = [];
$params = [];
$types = "";

if ($search !== '') {
  $where[] = "name LIKE ?";
  $params[] = "%{$search}%";
  $types .= "s";
}

if ($category !== '') {
  $where[] = "category = ?";
  $params[] = $category;
  $types .= "s";
}

if ($stock === 'in') {
  $where[] = "stock > 50";
} elseif ($stock === 'low') {
  $where[] = "stock BETWEEN 1 AND 50";
} elseif ($stock === 'out') {
  $where[] = "stock <= 0";
} else {
  $stock = 'all';
}

$whereSql = $where ? ("WHERE " . implode(" AND ", $where)) : "";

/* =======================
   ORDER BY (WHITELIST)
   ======================= */
$orderSql = "ORDER BY id DESC";
if ($sort === 'oldest') $orderSql = "ORDER BY id ASC";
if ($sort === 'price_asc') $orderSql = "ORDER BY price ASC";
if ($sort === 'price_desc') $orderSql = "ORDER BY price DESC";
if (!in_array($sort, ['newest','oldest','price_asc','price_desc'], true)) $sort = 'newest';

/* =======================
   COUNT TOTAL PRODUCTS (filtered)
   ======================= */
$sqlCount = "SELECT COUNT(*) AS cnt FROM products $whereSql";
$stmt = $mysqli->prepare($sqlCount);
if (!$stmt) out(['ok'=>false,'error'=>'DB_ERROR'], 500);

if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$total = (int)($stmt->get_result()->fetch_assoc()['cnt'] ?? 0);
$stmt->close();

$totalPages = max(1, (int)ceil($total / $perPage));
if ($page > $totalPages) $page = $totalPages;
$offset = ($page - 1) * $perPage;

/* =======================
   PRODUCTS (filtered)
   ======================= */
$sql = "
  SELECT id, name, price, stock, category, image
  FROM products
  $whereSql
  $orderSql
  LIMIT ? OFFSET ?
";
$stmt = $mysqli->prepare($sql);
if (!$stmt) out(['ok'=>false,'error'=>'DB_ERROR'], 500);

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
while ($r = $res->fetch_assoc()) {
  $img = !empty($r['image'])
    ? "/TUBES_2_Toko/frontend/assets/products/" . rawurlencode($r['image'])
    : "/TUBES_2_Toko/frontend/assets/products/placeholder.png";

  $products[] = [
    'id'       => (int)$r['id'],
    'name'     => (string)($r['name'] ?? ''),
    'price'    => (float)($r['price'] ?? 0),
    'stock'    => (int)($r['stock'] ?? 0),
    'category' => (string)($r['category'] ?? ''),
    'image'    => $img
  ];
}
$stmt->close();

/* =======================
   STATS (GLOBAL / quick)
   ======================= */
$stats = [
  'totalProducts' => 0,
  'totalCustomers' => 0,
  'totalActiveCustomers' => 0,
  'totalLowStock' => 0
];

if ($q = $mysqli->query("SELECT COUNT(*) c FROM products")) {
  $stats['totalProducts'] = (int)($q->fetch_assoc()['c'] ?? 0);
  $q->free();
}
if ($q = $mysqli->query("SELECT COUNT(*) c FROM user WHERE role='user'")) {
  $stats['totalCustomers'] = (int)($q->fetch_assoc()['c'] ?? 0);
  $q->free();
}
if ($q = $mysqli->query("SELECT COUNT(*) c FROM user WHERE role='user' AND is_active=1")) {
  $stats['totalActiveCustomers'] = (int)($q->fetch_assoc()['c'] ?? 0);
  $q->free();
}
if ($q = $mysqli->query("SELECT COUNT(*) c FROM products WHERE stock BETWEEN 1 AND 50")) {
  $stats['totalLowStock'] = (int)($q->fetch_assoc()['c'] ?? 0);
  $q->free();
}

/* =======================
   CATEGORIES
   ======================= */
$categories = [];
$catRes = $mysqli->query("
  SELECT DISTINCT category
  FROM products
  WHERE category IS NOT NULL AND category <> ''
  ORDER BY category
");
if ($catRes) {
  while ($c = $catRes->fetch_assoc()) $categories[] = $c['category'];
  $catRes->free();
}

/* =======================
   RESPONSE
   ======================= */
out([
  'ok' => true,
  'admin' => [
    'id' => (int)$admin['id'],
    'username' => $admin['username']
  ],
  'stats' => $stats,
  'filters' => [
    'search' => $search,
    'category' => $category,
    'stock' => $stock,
    'sort' => $sort,
    'per_page' => $perPage
  ],
  'pagination' => [
    'page' => $page,
    'per_page' => $perPage,
    'total' => $total,
    'total_pages' => $totalPages
  ],
  'products' => $products,
  'meta' => [
    'categories' => $categories
  ]
]);