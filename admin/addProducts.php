<?php
session_start(); require_once __DIR__ . '/../config/db.php'; 
// ===== CEK LOGIN ADMIN ===== 
if (!isset($_SESSION['user_id'])) { 
    header('Location: /TUBES_2_Toko/auth/login.php?redirect=' . urlencode('/TUBES_2_Toko/admin/addProducts.php')); 
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

// ADD PRODUCT
$success = null;
$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    echo "<pre>";
    print_r($_POST);
    echo "</pre>";

    $name     = $_POST['name'] ?? null;
    $desc     = $_POST['description'] ?? null;
    $price    = $_POST['price'] ?? null;
    $stock    = $_POST['stock'] ?? null;
    $category = $_POST['category'] ?? null;

    if ($name === null || $desc === null || $price === null || 
        $stock === null || $category === null || $category === "") {
        $error = "Semua field wajib diisi!";
    } else {

        // Upload image
        $img = null;
        if (!empty($_FILES['image']['name'])) {

            $uploadDir = __DIR__ . '/../assets/products/';

            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $img = time() . '_' . basename($_FILES['image']['name']);
            $target = $uploadDir . $img;

            if (!move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
                $error = "Upload gambar gagal!";
            }
        }

        if (!$error) {
            $stmt = $mysqli->prepare("
                INSERT INTO products (name, description, price, stock, category, image)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("ssiiss", $name, $desc, $price, $stock, $category, $img);

            if ($stmt->execute()) {
                header("Location: /TUBES_2_Toko/admin/dashboard.php");
                exit;
            } else {
                $error = "Failed to Add Products!";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
    <?php
        $pageTitle   = 'Admin Dashboard — Féyora';
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
                        <span>Add Product</span> 
                    </div>

                    <div class="admin-topbar-right">
                        <span class="admin-welcome">Hi, <?= htmlspecialchars($adminName) ?></span> 
                        <div class="admin-avatar-small"><?= strtoupper(substr($adminName, 0, 2)) ?></div>
                    </div> 
                </header> 
                
                <form method="POST" enctype="multipart/form-data"> 
                    <main class="admin-content"> 
                        <div class="admin-content-header">
                            <h1 class="admin-page-title">Add New Product</h1> 
                        </div> 
                        
                        <?php if ($success): ?> 
                            <div class="alert success"><?= $success ?></div> 
                        <?php endif; ?> 

                        <?php if ($error): ?> 
                            <div class="alert danger"><?= $error ?></div> 
                        <?php endif; ?> 
                        
                        <!-- FORM CARD PRODUK INFORMATION--> 
                        <div class="admin-product-grid">
                            <div class="admin-form-card">
                                <h3 class="form-section-title">Product Information</h3> 
                                
                                <label class="inputTitle">Product Name</label> 
                                <input type="text" name="name" class="admin-input" required> 
                                
                                <label class="inputTitle">Description</label> 
                                <textarea name="description" class="admin-textarea" required></textarea> 
                            </div> 
                            
                            <!-- FORM CARD IMAGE -->
                            <div class="admin-form-card">
                                <h3 class="form-section-title">Product Image</h3> 
                                
                                <!-- DROPZONE -->
                                <div id="dropzone" class="media-dropzone">
                                    <div class="media-dropzone-icon">⬆</div>
                                    <p class="drag-image">Drag & drop images here</p>
                                    <span class="browse-text">or Browse files</span>
                                    <input type="file" id="imageInput" name="image" accept="image/*" hidden>
                                </div>
                                
                                <!-- Preview -->
                                <div id="imagePreviewContainer" class="media-preview-container"></div>
                                
                                <button type="submit" name="submit" class="btn-primary-admin full">
                                    <i class="bi bi-check2-circle"></i> Save Product!
                                </button> 
                        </div>
                        
                        <!-- FORM CARD PRICE -->
                        <div class="admin-form-card">    
                            <h3 class="form-section-title">Pricing & Stock</h3> 
                            
                            <label class="inputTitle">Price</label>
                            <input type="number" name="price" class="admin-input" required>
                            
                            <label class="inputTitle">Stock</label>
                            <input type="number" name="stock" class="admin-input" required> 
                            
                            <label class="inputTitle">Category</label> 
                            <select name="category" class="admin-input" required> 
                                <option value="" disabled selected>-- Select Category --</option> 
                                <option value="All">All</option>
                                <option value="Tops">Tops</option> 
                                <option value="Jacket">Jacket</option> 
                                <option value="Dress">Dress</option> 
                                <option value="Hoodies">Hoodies</option>
                                <option value="Tshirts">Tshirts</option>
                                <option value="Cardigans">Cardigans</option> 
                            </select>  
                        </div>  
                    </main> 
                </form>
            </div>
        </div>
        <script>
        const dropzone = document.getElementById("dropzone");
        const input = document.getElementById("imageInput");
        const preview = document.getElementById("imagePreviewContainer");

        // open file dialog
        dropzone.addEventListener("click", () => input.click());

        input.addEventListener("change", function () {
            showPreview(this.files[0]);
        });

        // drag over
        dropzone.addEventListener("dragover", function (e) {
            e.preventDefault();
            dropzone.classList.add("dragover");
        });

        // drag leave
        dropzone.addEventListener("dragleave", function (e) {
            dropzone.classList.remove("dragover");
        });

        // drop
        dropzone.addEventListener("drop", function (e) {
            e.preventDefault();
            dropzone.classList.remove("dragover");

            const file = e.dataTransfer.files[0];
            input.files = e.dataTransfer.files;

            showPreview(file);
        });

        // show preview
        function showPreview(file) {
            const reader = new FileReader();
            reader.onload = function (e) {
                preview.innerHTML = `
                    <img src="${e.target.result}" class="media-thumb">
                `;
            };
            reader.readAsDataURL(file);
        }
        </script>
    </body>
</html>