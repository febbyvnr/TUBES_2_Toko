<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// ===== CEK LOGIN & ROLE ADMIN =====
if (!isset($_SESSION['user_id'])) {
    header('Location: /TUBES_2_Toko/auth/login.php?redirect=' . urlencode('/TUBES_2_Toko/admin/customers.php'));
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$stmt = $mysqli->prepare("SELECT username, role FROM user WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$resUser = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$resUser || $resUser['role'] !== 'admin') {
    header('Location: /TUBES_2_Toko/index.php');
    exit;
}

$adminName = $resUser['username'];

// ===== FILTERS =====
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? 'all';
$page   = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

$limit  = 15;
$offset = ($page - 1) * $limit;

// ===== BASE QUERY =====
$where  = " WHERE role = 'user' ";
$params = [];
$types  = "";

// Search by username / email
if ($search !== '') {
    $where   .= " AND (username LIKE ? OR email LIKE ?) ";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types   .= "ss";
}

// Status filter
if ($status === 'active') {
    $where .= " AND is_active = 1 ";
} elseif ($status === 'inactive') {
    $where .= " AND is_active = 0 ";
}

// ===== HITUNG TOTAL =====
$sqlCount = "SELECT COUNT(*) AS total FROM user $where";
$stmt = $mysqli->prepare($sqlCount);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$totalRows  = (int)$stmt->get_result()->fetch_assoc()['total'];
$totalPages = max(1, (int)ceil($totalRows / $limit));
$stmt->close();

// ===== AMBIL DATA CUSTOMER =====
$sql = "SELECT id, username, email, phone, password, is_active 
        FROM user
        $where
        ORDER BY id DESC
        LIMIT ? OFFSET ?";

$stmt = $mysqli->prepare($sql);

// tambahkan limit & offset ke bind_param
if (!empty($params)) {
    $typesWithLimit = $types . "ii";
    $paramsWithLimit = array_merge($params, [$limit, $offset]);
    $stmt->bind_param($typesWithLimit, ...$paramsWithLimit);
} else {
    $stmt->bind_param("ii", $limit, $offset);
}

$stmt->execute();
$customers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!doctype html>
<html lang="en">
<?php
$pageTitle   = 'Customers — Féyora';
$extraStyles = '
    <link rel="stylesheet" href="/TUBES_2_Toko/styles/adminDashboard.css?v=' . time() . '">
    <link rel="stylesheet" href="/TUBES_2_Toko/styles/customer.css?v=' . time() . '">
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
        <span>Customers</span>
      </div>

      <div class="admin-topbar-right">
        <span class="admin-welcome">Hi, <?= htmlspecialchars($adminName) ?></span>
        <div class="admin-avatar-small"><?= strtoupper(substr($adminName, 0, 2)) ?></div>
      </div>
    </header>

    <main class="admin-content">

      <!-- Header -->
      <div class="admin-content-header">
        <h1 class="admin-page-title">Customers</h1>
      </div>

      <!-- Filter -->
      <section class="admin-filterbar">
        <form method="get" class="filter-form">
          <!-- search bar pakai style dari customer.css -->
          <div class="customer-searchbar">
            <i class="bi bi-search"></i>
            <input
              type="text"
              name="search"
              placeholder="Search username or email..."
              value="<?= htmlspecialchars($search) ?>"
            >
          </div>

          <div class="filter-group">
            <select name="status" class="customer-filter-select">
              <option value="all"      <?= $status === 'all'      ? 'selected' : '' ?>>All Status</option>
              <option value="active"   <?= $status === 'active'   ? 'selected' : '' ?>>Active</option>
              <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inactive</option>
            </select>

            <button type="submit" class="customer-apply-btn">Apply</button>
          </div>
        </form>
      </section>

      <!-- Table -->
      <section class="customer-table-card">
        <table class="customer-table">
          <thead>
            <tr>
              <th>Username Email</th>
              <th>Phone</th>
              <th>Password</th>
              <th>Status</th>
              <th style="width:110px">Actions</th>
            </tr>
          </thead>

          <tbody>
          <?php if (empty($customers)): ?>
            <tr>
              <td colspan="5" style="text-align:center; padding:20px; color:#777;">
                No customers found.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($customers as $c): ?>
              <tr>
                <!-- Username + Email -->
                <td>
                  <div class="customer-user">
                    <span class="u-name"><?= htmlspecialchars($c['username']) ?></span>
                    <span class="u-email"><?= htmlspecialchars($c['email']) ?></span>
                  </div>
                </td>

                <!-- Phone -->
                <td class="customer-phone">
                  <?= htmlspecialchars($c['phone'] ?: '-') ?>
                </td>

                <!-- Password (masked) -->
                <td class="customer-pass">
                  <?= str_repeat('*', 8) ?>
                </td>

                <!-- Status -->
                <td>
                  <?php if ((int)$c['is_active'] === 1): ?>
                    <span class="status-active">Active</span>
                  <?php else: ?>
                    <span class="status-inactive">Inactive</span>
                  <?php endif; ?>
                </td>

                <!-- Actions -->
                <td class="customer-actions">
                  <a
                    href="/TUBES_2_Toko/admin/deleteCustomer.php?id=<?= (int)$c['id'] ?>"
                    onclick="return confirm('Delete this customer?')"
                    title="Delete"
                  >
                    <i class="bi bi-trash3"></i>
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
          </tbody>
        </table>

        <!-- Footer + Pagination -->
        <div class="admin-table-footer">
          <div class="table-info">
            Showing <?= count($customers) ?> of <?= $totalRows ?> customers
          </div>

          <div class="customer-pagination">
            <?php
            $baseUrl = '/TUBES_2_Toko/admin/customers.php';
            $queryParams = $_GET;
            unset($queryParams['page']);

            // Prev
            if ($page > 1) {
                $queryParams['page'] = $page - 1;
                $urlPrev = $baseUrl . '?' . http_build_query($queryParams);
                echo '<a href="'. htmlspecialchars($urlPrev) .'" class="page-btn">Previous</a>';
            } else {
                echo '<span class="page-btn disabled">Previous</span>';
            }

            // Pages
            for ($i = 1; $i <= $totalPages; $i++) {
                $queryParams['page'] = $i;
                $url = $baseUrl . '?' . http_build_query($queryParams);

                if ($i == $page) {
                    echo '<span class="page-number active">'. $i .'</span>';
                } else {
                    echo '<a href="'. htmlspecialchars($url) .'" class="page-number">'. $i .'</a>';
                }
            }

            // Next
            if ($page < $totalPages) {
                $queryParams['page'] = $page + 1;
                $urlNext = $baseUrl . '?' . http_build_query($queryParams);
                echo '<a href="'. htmlspecialchars($urlNext) .'" class="page-btn">Next</a>';
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
