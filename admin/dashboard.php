<?php
// admin/dashboard.php
session_start();
require_once __DIR__ . '/../config/db.php';

// ===== CEK LOGIN & ROLE ADMIN =====
if (!isset($_SESSION['user_id'])) {
    header('Location: /TUBES_2_Toko/auth/login.php?redirect=' . urlencode('/TUBES_2_Toko/admin/dashboard.php'));
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

// ====== STATISTIK RINGKAS (untuk cards) ======
$totalProducts         = 0;
$totalCustomers        = 0;
$totalActiveCustomers  = 0;
$totalLowStock         = 0;

// total produk (semua, tidak terpengaruh filter)
if ($res = $mysqli->query("SELECT COUNT(*) AS c FROM products")) {
    $row = $res->fetch_assoc();
    $totalProducts = (int)$row['c'];
    $res->free();
}

// total customer (role user)
if ($res = $mysqli->query("SELECT COUNT(*) AS c FROM user WHERE role = 'user'")) {
    $row = $res->fetch_assoc();
    $totalCustomers = (int)$row['c'];
    $res->free();
}

// total customer aktif
if ($res = $mysqli->query("SELECT COUNT(*) AS c FROM user WHERE role = 'user' AND is_active = 1")) {
    $row = $res->fetch_assoc();
    $totalActiveCustomers = (int)$row['c'];
    $res->free();
}

// low stock (stok <= 50)
if ($res = $mysqli->query("SELECT COUNT(*) AS c FROM products WHERE stock > 0 AND stock <= 50")) {
    $row = $res->fetch_assoc();
    $totalLowStock = (int)$row['c'];
    $res->free();
}

// ====== LIST CATEGORY UNTUK FILTER ======
$categories = [];
if ($res = $mysqli->query("
    SELECT DISTINCT IFNULL(category,'') AS category
    FROM products
    WHERE category IS NOT NULL AND category <> ''
    ORDER BY category
")) {
    while ($row = $res->fetch_assoc()) {
        if ($row['category'] !== '') {
            $categories[] = $row['category'];
        }
    }
    $res->free();
}

// ====== BACA FILTER & SORT & SEARCH DARI QUERY STRING ======
$qSort     = isset($_GET['sort'])     ? $_GET['sort']     : 'newest';
$qCategory = isset($_GET['category']) ? trim($_GET['category']) : '';
$qStock    = isset($_GET['stock'])    ? trim($_GET['stock'])    : 'all'; // all|in|low|out
$qSearch   = isset($_GET['q'])        ? trim($_GET['q'])        : '';

// ====== SORTING ======
switch ($qSort) {
    case 'oldest':
        $order = 'ORDER BY id ASC';
        break;
    case 'price_asc':
        $order = 'ORDER BY price ASC';
        break;
    case 'price_desc':
        $order = 'ORDER BY price DESC';
        break;
    case 'newest':
    default:
        $order = 'ORDER BY id DESC';
        $qSort = 'newest';
        break;
}

// ====== BANGUN WHERE DARI CATEGORY + STOCK + SEARCH ======
$conditions = [];

// category filter
if ($qCategory !== '') {
    $catEsc = $mysqli->real_escape_string($qCategory);
    $conditions[] = "category = '" . $catEsc . "'";
}

// stock filter
if ($qStock === 'in') {
    $conditions[] = "stock > 50";
} elseif ($qStock === 'low') {
    $conditions[] = "stock > 0 AND stock <= 50";
} elseif ($qStock === 'out') {
    $conditions[] = "stock <= 0";
}

// search by name
if ($qSearch !== '') {
    $qEsc = $mysqli->real_escape_string($qSearch);
    $conditions[] = "name LIKE '%" . $qEsc . "%'";
}

$where = '';
if (count($conditions) > 0) {
    $where = 'WHERE ' . implode(' AND ', $conditions);
}

// ====== PAGINATION (untuk tabel) ======
$perPage = 20;
$page    = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;

// hitung total data SETELAH filter (untuk pagination & "Showing x of y")
$filteredTotal = 0;
if ($res = $mysqli->query("SELECT COUNT(*) AS c FROM products $where")) {
    $row = $res->fetch_assoc();
    $filteredTotal = (int)$row['c'];
    $res->free();
}

$totalPages = max(1, (int)ceil($filteredTotal / $perPage));
if ($page > $totalPages) $page = $totalPages;

$offset = ($page - 1) * $perPage;

// ====== AMBIL DATA PRODUK (PAGE INI SAJA) ======
function resolveProductImage(array $row): string {
    if (!empty($row['image'])) {
        return '/TUBES_2_Toko/assets/products/' . rawurlencode($row['image']);
    }
    return '/TUBES_2_Toko/assets/products/placeholder.png';
}

$products = [];
$sqlProducts = "
    SELECT id, name, price, stock, image
    FROM products
    $where
    $order
    LIMIT $perPage OFFSET $offset
";

if ($res = $mysqli->query($sqlProducts)) {
    while ($row = $res->fetch_assoc()) {
        $row['image_url'] = resolveProductImage($row);

        if ($row['stock'] <= 0) {
            $row['status'] = 'Out of Stock';
        } elseif ($row['stock'] <= 50) {
            $row['status'] = 'Low Stock';
        } else {
            $row['status'] = 'Published';
        }

        $products[] = $row;
    }
    $res->free();
}
?>
<!doctype html>
<html lang="en">
<?php
  $pageTitle   = 'Admin Dashboard — Féyora';
  $extraStyles = '
    <link rel="stylesheet" href="/TUBES_2_Toko/styles/adminDashboard.css?v=' . time() . '">
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
        <span>Dashboard</span>
      </div>

      <div class="admin-topbar-right">
        <span class="admin-welcome">Hi, <?= htmlspecialchars($adminName) ?></span>
        <div class="admin-avatar-small"><?= strtoupper(substr($adminName,0,2)) ?></div>
      </div>
    </header>

    <main class="admin-content">
      <div class="admin-content-header">
        <h1 class="admin-page-title">Dashboard</h1>
        <button class="btn-primary-admin" onclick="window.location.href='/TUBES_2_Toko/admin/addProduct.php'">
          <i class="bi bi-plus-lg"></i> Add New Product
        </button>
      </div>

      <!-- Stat cards -->
      <section class="admin-stats">
        <div class="stat-card">
          <div class="stat-label">Total Products</div>
          <div class="stat-value"><?= $totalProducts ?></div>
        </div>
        <div class="stat-card">
          <div class="stat-label">Customers</div>
          <div class="stat-value"><?= $totalCustomers ?></div>
          <div class="stat-sub"><?= $totalActiveCustomers ?> active</div>
        </div>
        <div class="stat-card">
          <div class="stat-label">Low Stock (≤ 50)</div>
          <div class="stat-value"><?= $totalLowStock ?></div>
        </div>
      </section>

      <!-- Filter bar -->
      <section class="admin-filterbar">
        <form method="get" class="filter-form">
          <div class="filter-search">
            <i class="bi bi-search"></i>
            <input
              type="text"
              name="q"
              placeholder="Search by product name..."
              value="<?= htmlspecialchars($qSearch) ?>"
            />
          </div>

          <div class="filter-group">
            <!-- CATEGORY -->
            <select name="category" class="filter-pill filter-select">
              <option value="">Category: All</option>
              <?php foreach ($categories as $cat): ?>
                <option value="<?= htmlspecialchars($cat) ?>"
                  <?= $qCategory === $cat ? 'selected' : '' ?>>
                  <?= htmlspecialchars($cat) ?>
                </option>
              <?php endforeach; ?>
            </select>

            <!-- STOCK -->
            <select name="stock" class="filter-pill filter-select">
              <option value="all"  <?= $qStock === 'all'  ? 'selected' : '' ?>>Stock: All</option>
              <option value="in"   <?= $qStock === 'in'   ? 'selected' : '' ?>>In Stock (&gt; 50)</option>
              <option value="low"  <?= $qStock === 'low'  ? 'selected' : '' ?>>Low Stock (1–50)</option>
              <option value="out"  <?= $qStock === 'out'  ? 'selected' : '' ?>>Out of Stock</option>
            </select>

            <!-- SORT -->
            <select name="sort" class="filter-pill filter-select">
              <option value="newest" <?= $qSort === 'newest' ? 'selected' : '' ?>>
                Sort: Newest
              </option>
              <option value="oldest" <?= $qSort === 'oldest' ? 'selected' : '' ?>>
                Sort: Oldest
              </option>
              <option value="price_asc" <?= $qSort === 'price_asc' ? 'selected' : '' ?>>
                Price: Low → High
              </option>
              <option value="price_desc" <?= $qSort === 'price_desc' ? 'selected' : '' ?>>
                Price: High → Low
              </option>
            </select>

            <button type="submit" class="filter-apply-btn">Apply</button>
          </div>
        </form>
      </section>

      <!-- Table -->
      <section class="admin-table-card">
        <table class="admin-table">
          <thead>
            <tr>
              <th style="width:40px;">
                <input type="checkbox" />
              </th>
              <th>Product Name</th>
              <th>Stock</th>
              <th>Price</th>
              <th>Status</th>
              <th style="width:110px; text-align:center;">Actions</th>
            </tr>
          </thead>
          <tbody>
          <?php if (empty($products)): ?>
            <tr>
              <td colspan="6" style="text-align:center; padding:20px; color:#777;">
                Belum ada produk yang sesuai filter.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($products as $p): ?>
              <tr>
                <td>
                  <input type="checkbox" />
                </td>

                <!-- Product Name -->
                <td class="cell-product">
                  <img src="<?= htmlspecialchars($p['image_url']) ?>" alt="" class="cell-thumb">
                  <span><?= htmlspecialchars($p['name']) ?></span>
                </td>

                <!-- Stock -->
                <td class="cell-stock">
                  <?php if ($p['stock'] <= 0): ?>
                    <span class="stock-badge stock-out">Out of Stock</span>
                  <?php elseif ($p['stock'] <= 50): ?>
                    <span class="stock-badge stock-low"><?= (int)$p['stock'] ?> In Stock</span>
                  <?php else: ?>
                    <span class="stock-badge stock-ok"><?= (int)$p['stock'] ?> In Stock</span>
                  <?php endif; ?>
                </td>

                <!-- Price -->
                <td class="cell-price">
                  Rp <?= number_format($p['price'], 0, ',', '.') ?>
                </td>

                <!-- Status -->
                <td>
                  <?php if ($p['status'] === 'Published'): ?>
                    <span class="status-pill status-published">Published</span>
                  <?php elseif ($p['status'] === 'Low Stock'): ?>
                    <span class="status-pill status-low">Low Stock</span>
                  <?php else: ?>
                    <span class="status-pill status-draft">Out of Stock</span>
                  <?php endif; ?>
                </td>

                <!-- Actions -->
                <td class="cell-actions">
                  <a href="/TUBES_2_Toko/product/detailProduct.php?id=<?= (int)$p['id'] ?>" title="View">
                    <i class="bi bi-eye"></i>
                  </a>
                  <a href="/TUBES_2_Toko/admin/editProduct.php?id=<?= (int)$p['id'] ?>" title="Edit">
                    <i class="bi bi-pencil"></i>
                  </a>
                  <a href="/TUBES_2_Toko/admin/deleteProduct.php?id=<?= (int)$p['id'] ?>" title="Delete"
                     onclick="return confirm('Hapus produk ini?');">
                    <i class="bi bi-trash3"></i>
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
          </tbody>
        </table>

        <div class="admin-table-footer">
          <div class="table-info">
            Showing <?= count($products) ?> of <?= $filteredTotal ?> products
          </div>

          <?php if ($totalPages > 1): ?>
          <div class="table-pagination">
            <?php
              // base URL ke file ini
              $baseUrl = '/TUBES_2_Toko/admin/dashboard.php';

              // base params (bawa semua filter kecuali page)
              $baseParams = $_GET;
              unset($baseParams['page']);

              // Prev
              if ($page > 1) {
                  $baseParams['page'] = $page - 1;
                  $urlPrev = $baseUrl . '?' . http_build_query($baseParams);
                  echo '<a class="page-btn" href="'. htmlspecialchars($urlPrev) .'">Previous</a>';
              } else {
                  echo '<span class="page-btn disabled">Previous</span>';
              }

              // window halaman
              $start = max(1, $page - 2);
              $end   = min($totalPages, $page + 2);

              if ($start > 1) {
                  $baseParams['page'] = 1;
                  $urlFirst = $baseUrl . '?' . http_build_query($baseParams);
                  echo '<a class="page-number" href="'. htmlspecialchars($urlFirst) .'">1</a>';
                  if ($start > 2) {
                      echo '<span class="page-number disabled">...</span>';
                  }
              }

              for ($p = $start; $p <= $end; $p++) {
                  $baseParams['page'] = $p;
                  $url = $baseUrl . '?' . http_build_query($baseParams);
                  if ($p == $page) {
                      echo '<span class="page-number active">'. $p .'</span>';
                  } else {
                      echo '<a class="page-number" href="'. htmlspecialchars($url) .'">'. $p .'</a>';
                  }
              }

              if ($end < $totalPages) {
                  if ($end < $totalPages - 1) {
                      echo '<span class="page-number disabled">...</span>';
                  }
                  $baseParams['page'] = $totalPages;
                  $urlLast = $baseUrl . '?' . http_build_query($baseParams);
                  echo '<a class="page-number" href="'. htmlspecialchars($urlLast) .'">'. $totalPages .'</a>';
              }

              // Next
              if ($page < $totalPages) {
                  $baseParams['page'] = $page + 1;
                  $urlNext = $baseUrl . '?' . http_build_query($baseParams);
                  echo '<a class="page-btn" href="'. htmlspecialchars($urlNext) .'">Next</a>';
              } else {
                  echo '<span class="page-btn disabled">Next</span>';
              }
            ?>
          </div>
          <?php endif; ?>
        </div>
      </section>

    </main>
  </div>
</div>

</body>
</html>
