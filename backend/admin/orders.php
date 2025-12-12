<?php
session_start();
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

// ===== AUTH ADMIN =====
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'UNAUTHORIZED']);
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$stmt = $mysqli->prepare("SELECT role FROM user WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user || $user['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'FORBIDDEN']);
    exit;
}

// ===== PARAMS =====
$search = trim($_GET['search'] ?? '');
$status = $_GET['status'] ?? 'all';
$page   = max(1, (int)($_GET['page'] ?? 1));

$limit  = 10;
$offset = ($page - 1) * $limit;

// ===== WHERE =====
$where  = " WHERE 1=1 ";
$params = [];
$types  = "";

// search
if ($search !== '') {
    $where .= " AND (
        u.username LIKE ? OR 
        u.email LIKE ? OR 
        CAST(t.id AS CHAR) LIKE ?
    ) ";
    $like = "%{$search}%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= "sss";
}

// status
if ($status !== 'all') {
    $where .= " AND t.status = ? ";
    $params[] = $status;
    $types .= "s";
}

// ===== COUNT =====
$sqlCount = "
    SELECT COUNT(*) AS total
    FROM transactions t
    JOIN user u ON u.id = t.user_id
    $where
";

$stmt = $mysqli->prepare($sqlCount);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$totalRows = (int)$stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$totalPages = max(1, ceil($totalRows / $limit));

// ===== DATA =====
$sql = "
    SELECT
        t.id,
        u.username,
        t.total_price,
        t.status,
        t.date_created
    FROM transactions t
    JOIN user u ON u.id = t.user_id
    $where
    ORDER BY t.id DESC
    LIMIT ? OFFSET ?
";

$params[] = $limit;
$params[] = $offset;
$types   .= "ii";

$stmt = $mysqli->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// ===== ITEMS =====
$itemsByOrder = [];

if ($orders) {
    $ids = array_column($orders, 'id');
    $in  = implode(',', array_fill(0, count($ids), '?'));

    $sqlItems = "
        SELECT
            d.transaction_id,
            p.name,
            d.size,
            d.quantity
        FROM detail_transaction d
        JOIN products p ON p.id = d.product_id
        WHERE d.transaction_id IN ($in)
    ";

    $stmt = $mysqli->prepare($sqlItems);
    $stmt->bind_param(str_repeat('i', count($ids)), ...$ids);
    $stmt->execute();
    $res = $stmt->get_result();

    while ($row = $res->fetch_assoc()) {
        $tid = $row['transaction_id'];
        $label = $row['name'];

        if ($row['size']) {
            $label .= " ({$row['size']})";
        }

        $label .= " × {$row['quantity']}";

        $itemsByOrder[$tid][] = $label;
    }
    $stmt->close();
}

// ===== RESPONSE =====
echo json_encode([
    'ok' => true,
    'data' => [
        'orders' => array_map(function ($o) use ($itemsByOrder) {
            return [
                'id'         => (int)$o['id'],
                'customer'   => $o['username'],
                'items'      => $itemsByOrder[$o['id']] ?? [],
                'total'      => (int)$o['total_price'],
                'status'     => $o['status'],
                'date'       => date('d M Y H:i', strtotime($o['date_created']))
            ];
        }, $orders),
        'pagination' => [
            'page'       => $page,
            'totalPages'=> $totalPages,
            'totalRows' => $totalRows
        ]
    ]
]);