<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// ===== CEK LOGIN ADMIN =====
if (!isset($_SESSION['user_id'])) {
    header('Location: /TUBES_2_Toko/auth/login.php?redirect=' . urlencode('/TUBES_2_Toko/admin/editProducts.php'));
    exit;
}

$user_id = (int) $_SESSION['user_id'];
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

// ===== Ambil ID Produk =====
if (!isset($_GET['id'])) {
    die("Product ID required!");
}
$product_id = (int) $_GET['id'];

// ===== Fetch produk lama =====
$stmt = $mysqli->prepare("SELECT * FROM products WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$product) {
    die("Product not found!");
}

$success = null;
$error = null;

// ===== KETIKA SUBMIT EDIT PRODUCT =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = $_POST['name'] ?? null;
    $desc     = $_POST['description'] ?? null;
    $price    = $_POST['price'] ?? null;
    $stock    = $_POST['stock'] ?? null;
    $category = $_POST['category'] ?? null;

    if ($name === null || $desc === null || $price === null ||
        $stock === null || $category === null || $category === "") {
        $error = "Semua field wajib diisi!";
    } else {
        // Upload image (optional)
        $img = $product['image']; // gunakan gambar lama jika tidak upload

        if (!$error) {
            $stmt = $mysqli->prepare("
                UPDATE products 
                SET name = ?, description = ?, price = ?, stock = ?, category = ?, image = ?
                WHERE id = ?
            ");
            $stmt->bind_param("ssiissi", $name, $desc, $price, $stock, $category, $img, $product_id);

            if ($stmt->execute()) {
                header("Location: /TUBES_2_Toko/admin/dashboard.php");
                exit;
            } else {
                $error = "Failed to Update Product!";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
    <?php
        $pageTitle   = 'Edit Product — Féyora';
        $extraStyles = '
            <link rel="stylesheet" href="/TUBES_2_Toko/styles/adminDashboard.css?v=' . time() . '">
            <link rel="stylesheet" href="/TUBES_2_Toko/styles/adminAddProducts.css?v=' . time() . '">
        ';
        include __DIR__ . '/../includes/head.php';
    ?>

<body class="admin-body">
    <div class="admin-layout">

        <?php include __DIR__ . '/../includes/sideBar.php'; ?>

        <div class="admin-main">

            <!-- TOPBAR -->
            <header class="admin-topbar">
                <div class="admin-breadcrumb">
                    <span>Home</span>
                    <span class="sep">/</span>
                    <span>Edit Product</span>
                </div>

                <div class="admin-topbar-right">
                    <span class="admin-welcome">Hi, <?= htmlspecialchars($adminName) ?></span>
                    <div class="admin-avatar-small"><?= strtoupper(substr($adminName, 0, 2)) ?></div>
                </div>
            </header>

            <form method="POST" enctype="multipart/form-data">
                <main class="admin-content">

                    <div class="admin-content-header">
                        <h1 class="admin-page-title">Edit Product</h1>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert danger"><?= $error ?></div>
                    <?php endif; ?>

                    <!-- GRID FORM -->
                    <div class="admin-product-grid">

                        <!-- LEFT CARD -->
                        <div class="admin-form-card">
                            <h3 class="form-section-title">Product Information</h3>

                            <label class="inputTitle">Product Name</label>
                            <input type="text" name="name" class="admin-input"
                                   value="<?= htmlspecialchars($product['name']) ?>" required>

                            <label class="inputTitle">Description</label>
                            <textarea name="description" class="admin-textarea" required><?= htmlspecialchars($product['description']) ?></textarea>
                        </div>

                        <!-- IMAGE CARD -->
                        <div class="admin-form-card">
                            <h3 class="form-section-title">Product Image (Not Editable)</h3>

                            <div class="media-preview-container">
                                <?php if ($product['image']): ?>
                                    <img src="/TUBES_2_Toko/assets/products/<?= $product['image'] ?>" 
                                        class="media-thumb" 
                                        style="pointer-events:none; opacity:1;">
                                <?php else: ?>
                                    <p>No image available.</p>
                                <?php endif; ?>
                            </div>

                            <!-- button submit tetap ada -->
                            <button type="submit" name="submit" class="btn-primary-admin full">
                                <i class="bi bi-check2-circle"></i> Save Changes
                            </button>
                        </div>

                        <!-- PRICE STOCK CATEGORY -->
                        <div class="admin-form-card">
                            <h3 class="form-section-title">Pricing & Stock</h3>

                            <label class="inputTitle">Price</label>
                            <input type="number" name="price" class="admin-input"
                                   value="<?= $product['price'] ?>" required>

                            <label class="inputTitle">Stock</label>
                            <input type="number" name="stock" class="admin-input"
                                   value="<?= $product['stock'] ?>" required>

                            <label class="inputTitle">Category</label>
                            <select name="category" class="admin-input" required>
                                <option value="" disabled>-- Select Category --</option>

                                <?php
                                    $categories = ["All","Tops","Jacket","Dress","Hoodies","Tshirts","Cardigans"];
                                    foreach ($categories as $cat) {
                                        $selected = ($product['category'] === $cat) ? 'selected' : '';
                                        echo "<option value='$cat' $selected>$cat</option>";
                                    }
                                ?>
                            </select>
                        </div>

                    </div>
                </main>
            </form>

        </div>
    </div>
</body>
</html>
