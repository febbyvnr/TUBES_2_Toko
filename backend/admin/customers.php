<?php
require_once __DIR__ . '/../config/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json; charset=UTF-8');

/* =======================
   AUTH CHECK (ADMIN ONLY)
   ======================= */
if (!isset($_SESSION['user_id'])) {
  http_response_code(401);
  echo json_encode(['ok' => false, 'error' => 'UNAUTHORIZED']);
  exit;
}

$user_id = (int)$_SESSION['user_id'];
$stmt = $mysqli->prepare("SELECT username, role FROM user WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$admin || $admin['role'] !== 'admin') {
  http_response_code(403);
  echo json_encode(['ok' => false, 'error' => 'FORBIDDEN']);
  exit;
}

/* =======================
   PARAMS
   ======================= */
$search = trim($_GET['search'] ?? '');
$status = trim($_GET['status'] ?? 'all'); // all|active|inactive
$page   = max(1, (int)($_GET['page'] ?? 1));

$limit  = 15;
$offset = ($page - 1) * $limit;

/* =======================
   BUILD WHERE + PARAMS
   ======================= */
$where  = " WHERE role = 'user' ";
$params = [];
$types  = "";

// search
if ($search !== '') {
  $where   .= " AND (username LIKE ? OR email LIKE ?) ";
  $like = "%{$search}%";
  $params[] = $like;
  $params[] = $like;
  $types   .= "ss";
}

// status
if ($status === 'active') {
  $where .= " AND is_active = 1 ";
} elseif ($status === 'inactive') {
  $where .= " AND is_active = 0 ";
} else {
  $status = 'all';
}

/* =======================
   COUNT TOTAL
   ======================= */
$sqlCount = "SELECT COUNT(*) AS total FROM user $where";
$stmt = $mysqli->prepare($sqlCount);

if (!empty($params)) {
  $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$totalRows = (int)$stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$totalPages = max(1, (int)ceil($totalRows / $limit));
if ($page > $totalPages) $page = $totalPages;
$offset = ($page - 1) * $limit;

/* =======================
   FETCH CUSTOMERS
   ======================= */
$sql = "
  SELECT id, username, email, phone, is_active
  FROM user
  $where
  ORDER BY id DESC
  LIMIT ? OFFSET ?
";

$stmt = $mysqli->prepare($sql);

if (!empty($params)) {
  $types2 = $types . "ii";
  $params2 = array_merge($params, [$limit, $offset]);
  $stmt->bind_param($types2, ...$params2);
} else {
  $stmt->bind_param("ii", $limit, $offset);
}

$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$customers = [];
foreach ($rows as $c) {
  $customers[] = [
    'id'        => (int)$c['id'],
    'username'  => $c['username'],
    'email'     => $c['email'],
    'phone'     => $c['phone'] ?: '-',
    'is_active' => (int)$c['is_active'],
    'status'    => ((int)$c['is_active'] === 1) ? 'active' : 'inactive'
  ];
}

/* =======================
   RESPONSE
   ======================= */
echo json_encode([
  'ok' => true,
  'admin' => [
    'username' => $admin['username']
  ],
  'filters' => [
    'search' => $search,
    'status' => $status
  ],
  'pagination' => [
    'page'       => $page,
    'perPage'    => $limit,
    'total'      => $totalRows,
    'totalPages' => $totalPages
  ],
  'customers' => $customers
]);
exit;