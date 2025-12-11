<?php
// admin/orders.php
session_start();
require_once __DIR__ . '/../config/db.php';

// ===== CEK LOGIN & ROLE ADMIN =====
if (!isset($_SESSION['user_id'])) {
    header('Location: /TUBES_2_Toko/auth/login.php?redirect=' . urlencode('/TUBES_2_Toko/admin/orders.php'));
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$stmt = $mysqli->prepare("SELECT username, role FROM user WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$resUser = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$resUser || $resUser['role'] !== 'admin') {
    header('Location: /TUBES_2_Toko/index.php');
    exit;
}

$adminName = $resUser['username'] ?? 'Admin';

// ===== FILTERS =====
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? 'all';
$page   = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

$limit  = 10;
$offset = ($page - 1) * $limit;

// ===== BASE WHERE (hanya transaksi yang sudah dibayar) =====
// asumsi: "Belum Dibayar" = belum dibayar, sisanya sudah dibayar
$where  = " WHERE t.status <> 'Belum Dibayar' ";
$params = [];
$types  = "";

// search by username / email / id
if ($search !== '') {
    $where .= " AND (u.username LIKE ? OR u.email LIKE ? OR CAST(t.id AS CHAR) LIKE ?) ";
    $like = "%{$search}%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types   .= "sss";
}

// filter status (Diproses, Selesai, dll)
if ($status === 'Diproses') {
    $where .= " AND t.status = 'Diproses' ";
} elseif ($status === 'Selesai') {
    $where .= " AND t.status = 'Selesai' ";
}

// ===== HITUNG TOTAL TRANSAKSI =====
$sqlCount = "
    SELECT COUNT(*) AS total
    FROM transactions t
    JOIN user u ON t.user_id = u.id
    $where
";
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

// ===== AMBIL DATA TRANSAKSI =====
$sql = "
    SELECT 
        t.id,
        t.user_id,
        t.total_price,
        t.status,
        t.date_created,
        u.username
    FROM transactions t
    JOIN user u ON t.user_id = u.id
    $where
    ORDER BY t.user_id DESC
    LIMIT ? OFFSET ?
";

$paramsMain = $params;
$typesMain  = $types . "ii";
$paramsMain[] = $limit;
$paramsMain[] = $offset;

$stmt = $mysqli->prepare($sql);
$stmt->bind_param($typesMain, ...$paramsMain);
$stmt->execute();
$transactions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// ===== AMBIL DETAIL PRODUK UNTUK TIAP TRANSAKSI =====
$itemsByTxn = [];

if (!empty($transactions)) {
    $txnIds = array_column($transactions, 'id');
    $placeholders = implode(',', array_fill(0, count($txnIds), '?'));
    $sqlItems = "
        SELECT 
            d.transaction_id,
            p.name,
            d.size,
            d.quantity
        FROM detail_transaction d
        JOIN products p ON d.product_id = p.id
        WHERE d.transaction_id IN ($placeholders)
        ORDER BY d.transaction_id ASC, d.id ASC
    ";

    $stmt = $mysqli->prepare($sqlItems);
    $typesItems = str_repeat('i', count($txnIds));
    $stmt->bind_param($typesItems, ...$txnIds);
    $stmt->execute();
    $resItems = $stmt->get_result();

    while ($row = $resItems->fetch_assoc()) {
        $tid = $row['transaction_id'];
        if (!isset($itemsByTxn[$tid])) $itemsByTxn[$tid] = [];

        $label = $row['name'];
        if (!empty($row['size'])) {
            $label .= ' (' . $row['size'] . ')';
        }
        $label .= ' × ' . (int)$row['quantity'];

        $itemsByTxn[$tid][] = $label;
    }
    $stmt->close();
}

?>
<!doctype html>
<html lang="en">
<?php
$pageTitle   = 'Orders — Féyora';
$extraStyles = '
    <link rel="stylesheet" href="/TUBES_2_Toko/styles/adminDashboard.css?v=' . time() . '">
    <link rel="stylesheet" href="/TUBES_2_Toko/styles/orders.css?v=' . time() . '">
';
include __DIR__ . '/../includes/head.php';
?>
<body class="admin-body">
<div class="admin-layout">

  <?php include __DIR__ . '/../includes/sideBar.php'; ?>

  <div class="admin-main">

    <!-- Topbar -->
    <header class="admin-topbar">
      <div class="admin-breadcrumb">
        <span>Home</span>
        <span class="sep">/</span>
        <span>Orders</span>
      </div>

      <div class="admin-topbar-right">
        <span class="admin-welcome">Hi, <?= htmlspecialchars($adminName) ?></span>
        <div class="admin-avatar-small"><?= strtoupper(substr($adminName, 0, 2)) ?></div>
      </div>
    </header>

    <main class="admin-content">

      <!-- Header + Export -->
      <div class="admin-content-header">
        <h1 class="admin-page-title">Orders</h1>
        <button class="btn-primary-admin"
                onclick="window.location.href='/TUBES_2_Toko/admin/report.php'">
          <i class="bi bi-file-earmark-pdf"></i> Export PDF
        </button>
      </div>

      <!-- Filter bar -->
      <section class="admin-filterbar">
        <form method="get" class="filter-form">
          <div class="filter-search">
            <i class="bi bi-search"></i>
            <input
              type="text"
              name="search"
              placeholder="Search by username, email, or order ID..."
              value="<?= htmlspecialchars($search) ?>"
            >
          </div>

          <div class="filter-group">
            <select name="status" class="filter-pill filter-select">
              <option value="all"      <?= $status==='all'?'selected':'' ?>>All Status</option>
              <option value="Order Created" <?= $status==='Order Created'?'selected':'' ?>>Order Created</option>
              <option value="Waiting for Payment"  <?= $status==='Waiting for Payment'?'selected':'' ?>>Waiting for Payment</option>
              <option value="Payment Success"  <?= $status==='Payment Success'?'selected':'' ?>>Payment Success</option>
              <option value="Cancelled"  <?= $status==='Cancelled'?'selected':'' ?>>Cancelled</option>
            </select>

            <button type="submit" class="filter-apply-btn">Apply</button>
          </div>
        </form>
      </section>

      <!-- Table -->
      <section class="admin-table-card">
        <table class="admin-table orders-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Customer</th>
              <th>Items</th>
              <th>Total Price</th>
              <th>Status</th>
              <th>Date</th>
            </tr>
          </thead>
          <tbody>
          <?php if (empty($transactions)): ?>
            <tr>
              <td colspan="6" style="text-align:center; padding:18px; color:#777;">
                No paid orders found.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($transactions as $t): ?>
              <tr>
                <!-- ID -->
                <td><?= (int)$t['id'] ?></td>

                <!-- Customer -->
                <td><?= htmlspecialchars($t['username']) ?></td>

                <!-- Items -->
                <td class="orders-items">
                  <?php
                  $tid = $t['id'];
                  if (!empty($itemsByTxn[$tid])):
                      foreach ($itemsByTxn[$tid] as $line):
                  ?>
                        <div><?= htmlspecialchars($line) ?></div>
                  <?php
                      endforeach;
                  else:
                      echo '<span style="color:#999;font-size:12px;">No items</span>';
                  endif;
                  ?>
                </td>

                <!-- Total -->
                <td class="cell-price">
                  Rp <?= number_format($t['total_price'], 0, ',', '.') ?>
                </td>

                <!-- Status -->
                <td>
                    <?php 
                        switch ($t['status']) {
                        case 'Order Created':
                            echo '<span class="status-pill status-draft">Order Created</span>';
                            break;
                        case 'Waiting for Payment':
                            echo '<span class="status-pill status-low">Waiting for Payment</span>';
                            break;
                        case 'Payment Success':
                            echo '<span class="status-pill status-published">Payment Success</span>';
                            break;
                        case 'Cancelled':
                            echo '<span class="status-pill status-danger">Cancelled</span>';
                            break;
                        default:
                            echo '<span class="status-pill status-draft">'. htmlspecialchars($t["status"]) .'</span>';
                            break;
                        }
                    ?>
                </td>

                <!-- Date -->
                <td>
                  <?= htmlspecialchars(date('d M Y H:i', strtotime($t['date_created']))) ?>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
          </tbody>
        </table>

        <!-- Pagination -->
        <div class="admin-table-footer">
          <div class="table-info">
            Showing <?= count($transactions) ?> of <?= $totalRows ?> orders
          </div>

          <div class="table-pagination">
            <?php
            $baseUrl = '/TUBES_2_Toko/admin/orders.php';
            $paramsGet = $_GET;
            unset($paramsGet['page']);

            // Prev
            if ($page > 1) {
                $paramsGet['page'] = $page - 1;
                echo '<a class="page-btn" href="'.$baseUrl.'?'.htmlspecialchars(http_build_query($paramsGet)).'">Previous</a>';
            } else {
                echo '<span class="page-btn disabled">Previous</span>';
            }

            // Window halaman
            $start = max(1, $page - 2);
            $end   = min($totalPages, $page + 2);

            if ($start > 1) {
                $paramsGet['page'] = 1;
                echo '<a class="page-number" href="'.$baseUrl.'?'.htmlspecialchars(http_build_query($paramsGet)).'">1</a>';
                if ($start > 2) echo '<span class="page-number disabled">...</span>';
            }

            for ($p = $start; $p <= $end; $p++) {
                $paramsGet['page'] = $p;
                $url = $baseUrl.'?'.htmlspecialchars(http_build_query($paramsGet));
                if ($p == $page) {
                    echo '<span class="page-number active">'.$p.'</span>';
                } else {
                    echo '<a class="page-number" href="'.$url.'">'.$p.'</a>';
                }
            }

            if ($end < $totalPages) {
                if ($end < $totalPages - 1) echo '<span class="page-number disabled">...</span>';
                $paramsGet['page'] = $totalPages;
                echo '<a class="page-number" href="'.$baseUrl.'?'.htmlspecialchars(http_build_query($paramsGet)).'">'.$totalPages.'</a>';
            }

            // Next
            if ($page < $totalPages) {
                $paramsGet['page'] = $page + 1;
                echo '<a class="page-btn" href="'.$baseUrl.'?'.htmlspecialchars(http_build_query($paramsGet)).'">Next</a>';
            } else {
                echo '<span class="page-btn disabled">Next</span>';
            }
            ?>
          </div>
        </div>

      </section>

    </main>
  </div>
</div>

</body>
</html>
