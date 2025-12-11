<?php if (session_status() === PHP_SESSION_NONE) session_start(); ?>

<link rel="stylesheet" href="/TUBES_2_Toko/styles/sideBar.css?v=<?=time()?>">

<div class="admin-sidebar">

    <!-- Logo -->
    <div class="sidebar-header">
        <img src="/TUBES_2_Toko/assets/Logo Feyora.png" class="sidebar-logo">
    </div>

    <!-- Menu -->
    <ul class="sidebar-menu">
        <li>
            <a href="/TUBES_2_Toko/admin/dashboard.php"
               class="<?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : '' ?>">
                <i class="bi bi-grid-1x2"></i> Dashboard
            </a>
        </li>

        <li>
            <a href="/TUBES_2_Toko/admin/orders.php"
               class="<?= basename($_SERVER['PHP_SELF']) === 'orders.php' ? 'active' : '' ?>">
                <i class="bi bi-bag-check"></i> Orders
            </a>
        </li>

        <li>
            <a href="/TUBES_2_Toko/admin/customers.php"
               class="<?= basename($_SERVER['PHP_SELF']) === 'customers.php' ? 'active' : '' ?>">
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