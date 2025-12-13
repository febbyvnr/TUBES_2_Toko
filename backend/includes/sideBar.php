<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// nama file aktif (dashboard.php, orders.php, dll)
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<link rel="stylesheet" href="/TUBES_2_Toko/styles/sideBar.css?v=<?= time() ?>">

<div class="admin-sidebar">
    <div class="sidebar-header">
        <img src="/TUBES_2_Toko/assets/Logo Feyora.png"
            class="sidebar-logo"
            alt="Feyora Logo">
    </div>

    <!-- Menu -->
    <ul class="sidebar-menu">
        <li>
            <a href="/TUBES_2_Toko/admin/dashboard.php"
                class="<?= $currentPage === 'dashboard.php' ? 'active' : '' ?>">
                <i class="bi bi-grid-1x2"></i> Dashboard
            </a>
        </li>
        <li>
            <a href="/TUBES_2_Toko/admin/orders.php"
                class="<?= $currentPage === 'orders.php' ? 'active' : '' ?>">
                <i class="bi bi-bag-check"></i> Orders
            </a>
        </li>
        <li>
            <a href="/TUBES_2_Toko/admin/customers.php"
              class="<?= $currentPage === 'customers.php' ? 'active' : '' ?>">
                  <i class="bi bi-people"></i> Customers
            </a>
        </li>
        <li>
            <a href="/TUBES_2_Toko/auth/logout.php" class="logout">
                <i class="bi bi-box-arrow-right"></i> Logout
            </a>
        </li>
    </ul>
</div>