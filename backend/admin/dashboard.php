<?php
require_once __DIR__ . '../../config/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json; charset=UTF-8');

/* =======================
   AUTH CHECK (ADMIN ONLY)
   ======================= */
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'ok' => false,
        'error' => 'UNAUTHORIZED'
    ]);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$stmt = $mysqli->prepare("SELECT username, role FROM user WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user || $user['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode([
        'ok' => false,
        'error' => 'FORBIDDEN'
    ]);
    exit;
}

/* =======================
   QUERY PARAMS
   ======================= */
$qSearch   = trim($_GET['q'] ?? '');
$qCategory = trim($_GET['category'] ?? '');
$qStock    = trim($_GET['stock'] ?? 'all'); // all|in|low|out
$qSort     = trim($_GET['sort'] ?? 'newest');
$page      = max(1, (int)($_GET['page'] ?? 1));
$perPage   = 20;

/* =======================
   STATISTICS
   ======================= */
function fetchCount($mysqli, $sql) {
    $res = $mysqli->query($sql);
    if (!$res) return 0;
    $row = $res->fetch_assoc();
    $res->free();
    return (int)$row['c'];
}

$stats = [
    'totalProducts'        => fetchCount($mysqli, "SELECT COUNT(*) c FROM products"),
    'totalCustomers'       => fetchCount($mysqli, "SELECT COUNT(*) c FROM user WHERE role='user'"),
    'totalActiveCustomers' => fetchCount($mysqli, "SELECT COUNT(*) c FROM user WHERE role='user' AND is_active=1"),
    'totalLowStock'        => fetchCount($mysqli, "SELECT COUNT(*) c FROM products WHERE stock > 0 AND stock <= 50")
];

/* =======================
   BUILD FILTER CONDITIONS
   ======================= */
$conditions = [];

if ($qCategory !== '') {
    $conditions[] = "category = '" . $mysqli->real_escape_string($qCategory) . "'";
}

if ($qStock === 'in') {
    $conditions[] = "stock > 50";
} elseif ($qStock === 'low') {
    $conditions[] = "stock > 0 AND stock <= 50";
} elseif ($qStock === 'out') {
    $conditions[] = "stock <= 0";
}

if ($qSearch !== '') {
    $conditions[] = "name LIKE '%" . $mysqli->real_escape_string($qSearch) . "%'";
}

$where = $conditions ? ('WHERE ' . implode(' AND ', $conditions)) : '';

/* =======================
   SORTING
   ======================= */
switch ($qSort) {
    case 'oldest':      $order = 'ORDER BY id ASC'; break;
    case 'price_asc':   $order = 'ORDER BY price ASC'; break;
    case 'price_desc':  $order = 'ORDER BY price DESC'; break;
    case 'newest':
    default:
        $order = 'ORDER BY id DESC';
        $qSort = 'newest';
        break;
}

/* =======================
   PAGINATION
   ======================= */
$resCount = $mysqli->query("SELECT COUNT(*) c FROM products $where");
$rowCount = $resCount->fetch_assoc();
$resCount->free();

$filteredTotal = (int)$rowCount['c'];
$totalPages = max(1, (int)ceil($filteredTotal / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

/* =======================
   PRODUCTS
   ======================= */
$products = [];
$sql = "
    SELECT id, name, price, stock, image
    FROM products
    $where
    $order
    LIMIT $perPage OFFSET $offset
";

$res = $mysqli->query($sql);
while ($row = $res->fetch_assoc()) {

    if ($row['stock'] <= 0) {
        $status = 'out';
    } elseif ($row['stock'] <= 50) {
        $status = 'low';
    } else {
        $status = 'published';
    }

    $products[] = [
        'id'     => (int)$row['id'],
        'name'   => $row['name'],
        'price'  => (float)$row['price'],
        'stock'  => (int)$row['stock'],
        'status' => $status,
        'image'  => $row['image']
            ? '/TUBES_2_Toko/assets/products/' . rawurlencode($row['image'])
            : '/TUBES_2_Toko/assets/products/placeholder.png'
    ];
}
$res->free();

/* =======================
   CATEGORIES (FILTER)
   ======================= */
$categories = [];
$resCat = $mysqli->query("
    SELECT DISTINCT category
    FROM products
    WHERE category IS NOT NULL AND category <> ''
    ORDER BY category
");
while ($row = $resCat->fetch_assoc()) {
    $categories[] = $row['category'];
}
$resCat->free();

/* =======================
   RESPONSE
   ======================= */
echo json_encode([
    'ok' => true,
    'admin' => [
        'username' => $user['username']
    ],
    'stats' => $stats,
    'filters' => [
        'categories' => $categories,
        'search'     => $qSearch,
        'category'   => $qCategory,
        'stock'      => $qStock,
        'sort'       => $qSort
    ],
    'pagination' => [
        'page'        => $page,
        'perPage'     => $perPage,
        'total'       => $filteredTotal,
        'totalPages'  => $totalPages
    ],
    'products' => $products
]);
exit;