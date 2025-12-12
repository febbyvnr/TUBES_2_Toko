<?php
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

// ================= HELPERS =================
function json_fail($msg = 'ERROR') {
    echo json_encode(['ok' => false, 'error' => $msg]);
    exit;
}

function resolveImage(array $row): string {
    foreach (['image', 'image_name', 'img', 'gambar'] as $k) {
        if (!empty($row[$k])) {
            return '/TUBES_2_Toko/assets/products/' . rawurlencode($row[$k]);
        }
    }
    return '/TUBES_2_Toko/assets/products/placeholder.png';
}

function resolveTitle(array $row): string {
    if (!empty($row['name'])) return $row['name'];
    if (!empty($row['title'])) return $row['title'];
    return 'Untitled Product';
}

function resolvePriceText(array $row): string {
    if (isset($row['price'])) {
        return 'Rp ' . number_format((float)$row['price'], 0, ',', '.');
    }
    if (isset($row['harga'])) {
        return 'Rp ' . number_format((float)$row['harga'], 0, ',', '.');
    }
    return '';
}

// ================= QUERY PARAMS =================
$category   = trim($_GET['category']   ?? '');
$priceMin   = trim($_GET['price_min']  ?? '');
$priceMax   = trim($_GET['price_max']  ?? '');
$sort       = trim($_GET['sort']       ?? '');
$page       = max(1, (int)($_GET['page'] ?? 1));

$perPage = 30;
$offset  = ($page - 1) * $perPage;

// ================= BUILD WHERE =================
$where   = [];
$params  = [];
$types   = '';

if ($category !== '') {
    $where[]  = 'category = ?';
    $params[] = $category;
    $types   .= 's';
}

if ($priceMin !== '' && is_numeric($priceMin)) {
    $where[]  = 'price >= ?';
    $params[] = (float)$priceMin;
    $types   .= 'd';
}

if ($priceMax !== '' && is_numeric($priceMax)) {
    $where[]  = 'price <= ?';
    $params[] = (float)$priceMax;
    $types   .= 'd';
}

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

// ================= SORT (WHITELIST) =================
$orderSql = 'ORDER BY id DESC';
if ($sort === 'price_asc')  $orderSql = 'ORDER BY price ASC';
if ($sort === 'price_desc') $orderSql = 'ORDER BY price DESC';

// ================= TOTAL COUNT =================
$sqlCount = "SELECT COUNT(*) AS cnt FROM products $whereSql";
$stmt = $mysqli->prepare($sqlCount);
if (!$stmt) json_fail('DB_ERROR');

if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$total = (int)$stmt->get_result()->fetch_assoc()['cnt'];
$stmt->close();

$totalPages = max(1, (int)ceil($total / $perPage));
if ($page > $totalPages) $page = $totalPages;
$offset = ($page - 1) * $perPage;

// ================= DATA QUERY =================
$sql = "
    SELECT id, name, title, price, harga, image, image_name, img, gambar
    FROM products
    $whereSql
    $orderSql
    LIMIT ? OFFSET ?
";

$stmt = $mysqli->prepare($sql);
if (!$stmt) json_fail('DB_ERROR');

if ($params) {
    $types2  = $types . 'ii';
    $params2 = array_merge($params, [$perPage, $offset]);
    $stmt->bind_param($types2, ...$params2);
} else {
    $stmt->bind_param('ii', $perPage, $offset);
}

$stmt->execute();
$res = $stmt->get_result();

$items = [];
while ($row = $res->fetch_assoc()) {
    $items[] = [
        'id'         => (int)$row['id'],
        'title'      => resolveTitle($row),
        'price_text' => resolvePriceText($row),
        'image_url'  => resolveImage($row),
    ];
}
$stmt->close();

// ================= CATEGORIES META =================
$categories = [];
$catRes = $mysqli->query("
    SELECT DISTINCT category
    FROM products
    WHERE category IS NOT NULL AND category <> ''
    ORDER BY category
");
if ($catRes) {
    while ($r = $catRes->fetch_assoc()) {
        $categories[] = $r['category'];
    }
    $catRes->free();
}

// ================= RESPONSE =================
echo json_encode([
    'ok'   => true,
    'meta' => [
        'categories' => $categories,
    ],
    'data' => [
        'page'        => $page,
        'per_page'   => $perPage,
        'total'      => $total,
        'total_pages'=> $totalPages,
        'items'      => $items,
    ],
]);
exit;