<?php
// /TUBES_2_Toko/backend/product/listProduct.php
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

// ================= HELPERS =================
function json_fail(string $msg = 'ERROR', int $code = 400): void {
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $msg], JSON_UNESCAPED_SLASHES);
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
    if (!empty($row['name']))  return (string)$row['name'];
    return 'Untitled Product';
}

function resolvePriceText(array $row): string {
    if ($row['price'] !== null && $row['price'] !== '') {
        return 'Rp ' . number_format((float)$row['price'], 0, ',', '.');
    }
    return '';
}

// ================= QUERY PARAMS =================
$category = trim((string)($_GET['category'] ?? ''));
$collection = trim($_GET['collection'] ?? '');
$lastStock = (int)($_GET['laststock'] ?? 0);
$priceMin = trim((string)($_GET['price_min'] ?? ''));
$priceMax = trim((string)($_GET['price_max'] ?? ''));
$sort     = trim((string)($_GET['sort'] ?? ''));
$page     = (int)($_GET['page'] ?? 1);
if ($page < 1) $page = 1;

$perPage = 30;

// ================= BUILD WHERE =================
$where  = [];
$params = [];
$types  = '';

if ($category !== '') {
    $where[]  = 'category = ?';
    $params[] = $category;
    $types   .= 's';
}

if ($collection === 'studio') {
    $where[] = "name LIKE 'Studio Collection%'";
}

if ($lastStock === 1) {
    $where[] = "stock <= 50";
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
if ($sort === 'price_asc')  $orderSql = 'ORDER BY price ASC, id DESC';
if ($sort === 'price_desc') $orderSql = 'ORDER BY price DESC, id DESC';

// ================= TOTAL COUNT =================
$sqlCount = "SELECT COUNT(*) AS cnt FROM products $whereSql";
$stmt = $mysqli->prepare($sqlCount);
if (!$stmt) json_fail('DB_PREPARE_ERROR', 500);

if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$rowCnt = $stmt->get_result()->fetch_assoc();
$stmt->close();

$total = (int)($rowCnt['cnt'] ?? 0);
$totalPages = max(1, (int)ceil($total / $perPage));
if ($page > $totalPages) $page = $totalPages;

$offset = ($page - 1) * $perPage;

// ================= DATA QUERY =================
$sql = "
    SELECT id, name, description, price, stock, category, image, added
    FROM products
    $whereSql
    $orderSql
    LIMIT ? OFFSET ?
";
$stmt = $mysqli->prepare($sql);
if (!$stmt) json_fail('DB_PREPARE_ERROR', 500);

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
while ($r = $res->fetch_assoc()) {
    $items[] = [
        'id'         => (int)$r['id'],
        'name'      => resolveTitle($r),
        'price_text' => resolvePriceText($r),
        'image_url'  => resolveImage($r),
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
    while ($c = $catRes->fetch_assoc()) {
        $categories[] = $c['category'];
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
        'page'         => $page,
        'per_page'     => $perPage,
        'total'        => $total,
        'total_pages'  => $totalPages,
        'items'        => $items,
    ],
], JSON_UNESCAPED_SLASHES);

exit;