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

// ====== STATISTIK RINGKAS ======
$totalProducts         = 0;
$totalCustomers        = 0;
$totalActiveCustomers  = 0;
$totalLowStock         = 0;

// total produk
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

// ====== LIST PRODUK TERBARU (untuk tabel) ======
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
    ORDER BY id DESC
    LIMIT 20
";
if ($res = $mysqli->query($sqlProducts)) {
    while ($row = $res->fetch_assoc()) {
        $row['image_url'] = resolveProductImage($row);

        // status simple
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
        <div class="filter-search">
          <i class="bi bi-search"></i>
          <input type="text" placeholder="Search by product name..." />
        </div>
        <div class="filter-group">
          <button class="filter-pill">Category: All <i class="bi bi-chevron-down"></i></button>
          <button class="filter-pill">Stock: All <i class="bi bi-chevron-down"></i></button>
          <button class="filter-pill">Sort: Newest <i class="bi bi-chevron-down"></i></button>
        </div>
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
                Belum ada produk yang terdaftar.
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
            Showing <?= count($products) ?> of <?= $totalProducts ?> products
          </div>
          <div class="table-pagination">
            <button class="page-btn disabled">Previous</button>
            <button class="page-number active">1</button>
            <button class="page-number">2</button>
            <button class="page-number">3</button>
            <button class="page-btn">Next</button>
          </div>
        </div>
      </section>

    </main>
  </div>
</div>

</body>
</html>
