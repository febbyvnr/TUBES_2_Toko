<?php

require_once __DIR__ . '/../config/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$isLoggedIn = !empty($_SESSION['user_id']);

// Ambil ID
if (!isset($_GET['id'])) {
    die("Product not found.");
}

$id = intval($_GET['id']);

// Query produk berdasarkan ID
$stmt = $mysqli->prepare("SELECT * FROM products WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Product not found.");
}

$product = $result->fetch_assoc();

// Resolve image (sesuai <base href="/TUBES_2_Toko/"> => cukup "assets/...")
$image = 'assets/products/placeholder.png';
foreach (['image', 'image_name', 'img', 'gambar'] as $key) {
    if (!empty($product[$key])) {
        $image = 'assets/products/' . $product[$key];
        break;
    }
}

$title = !empty($product['name']) ? htmlspecialchars($product['name']) :
        (!empty($product['title']) ? htmlspecialchars($product['title']) : 'Untitled Product');

if (!empty($product['price'])) {
    $price = 'Rp ' . number_format($product['price'], 2);
} elseif (!empty($product['harga'])) {
    $price = 'Rp ' . number_format($product['harga'], 0, ',', '.');
} else {
    $price = 'No Price';
}

$desc = !empty($product['description'])
    ? nl2br(htmlspecialchars($product['description']))
    : 'No description available.';

$currentSize = isset($product['size']) ? $product['size'] : '';
?>
<!doctype html>
<html lang="en">
<?php
  // pakai head.php global
  $pageTitle   = $title . ' — FEYORA';
  $extraStyles = '<link rel="stylesheet" href="styles/DetailProduct.css?v=' . time() . '">';
  include __DIR__ . '/../includes/head.php';
?>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <main class="detail-container">
        <div class="image-section">
            <img src="<?= htmlspecialchars($image) ?>" alt="<?= $title ?>">
        </div>

        <div class="info-section">
            <h1 class="product-title"><?= $title ?></h1>

            <div class="product-price"><?= $price ?></div>

            <p class="product-description"><?= $desc ?></p>

            <div class="size-section">
                <span class="size-label">Size: </span>

                <div class="size-option">
                    <?php
                    $sizes = ['XS', 'S', 'M', 'L', 'XL', 'All Size'];
                    foreach ($sizes as $s) {
                        $activeClass = ($currentSize === $s) ? 'active' : '';
                        echo "<button class='size-btn $activeClass' data-size='$s'>$s</button>";
                    }
                    ?>
                </div>
            </div>

            <form action="cart/add.php" method="POST" id="cartForm">
                <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                <input type="hidden" name="size" id="selectedSize" value="<?= htmlspecialchars($currentSize) ?>">
                <input type="hidden" name="qty" id="qtyInput" value="1">
                
                <!-- Quantity -->
                <div class="qty-wrapper">
                    <span class="qty-label">Quantity:</span>

                    <div class="qty-control">
                        <button type="button" class="qty-btn" id="minusBtn">-</button>
                        <input type="text" class="qty-input" id="qtyDisplay" value="1">
                        <button type="button" class="qty-btn" id="plusBtn">+</button>
                    </div>
                </div>

                <button type="submit" class="add-cart-btn" id="addCartBtn"
                    <?= $isLoggedIn ? '' : 'data-require-login="1"' ?>
                >Add to Cart</button>
            </form>

            <a href="product/listProduct.php" class="back-link">← Back to Products</a>
        </div>
        
    </main>

    <!-- POPUP LOGIN DULU SBLM ADD TO CART -->
    <div id="login-required-popup"
        style="
            display:none;
            position:fixed;
            inset:0;
            background:rgba(0,0,0,0.35);
            z-index:9999;
            align-items:center;
            justify-content:center;">
      <div style="
            background:#fff;
            border-radius:10px;
            padding:20px 22px;
            max-width:320px;
            width:90%;
            box-shadow:0 8px 30px rgba(0,0,0,0.25);
            text-align:center;">
        <h4 style="margin-top:0; margin-bottom:10px; color:#222;">Anda Belum Login</h4>
        <p style="margin:0 0 18px; color:#555; font-size:14px;">
          Silakan login terlebih dahulu untuk menambahkan produk ke keranjang.
        </p>
        <div style="display:flex; gap:10px; justify-content:flex-end; margin-top:10px;">
          <button type="button"
                  id="login-popup-close"
                  style="
                    padding:8px 14px;
                    border-radius:6px;
                    border:1px solid #ccc;
                    background:#f5f5f5;
                    cursor:pointer;
                    font-size:13px;">
            Tutup
          </button>
          <button type="button"
                  id="login-popup-go"
                  style="
                    padding:8px 14px;
                    border-radius:6px;
                    border:none;
                    background:#ff2d7a;
                    color:#fff;
                    cursor:pointer;
                    font-size:13px;
                    font-weight:600;">
            Login
          </button>
        </div>
      </div>
    </div>

    <script>
        // POPUP BELUM LOGIN
        const addCartBtn   = document.getElementById('addCartBtn');
        const loginPopup   = document.getElementById('login-required-popup');
        const popupClose   = document.getElementById('login-popup-close');
        const popupGoLogin = document.getElementById('login-popup-go');

        if (addCartBtn && addCartBtn.dataset.requireLogin === '1') {
            addCartBtn.addEventListener('click', function (e) {
                e.preventDefault(); // jangan submit form
                if (loginPopup) {
                    loginPopup.style.display = 'flex';
                }
            });
        }

        if (popupClose && loginPopup) {
            popupClose.addEventListener('click', function () {
                loginPopup.style.display = 'none';
            });
        }

        if (popupGoLogin) {
            popupGoLogin.addEventListener('click', function () {
                const currentUrl = window.location.href;
                window.location.href = 'auth/login.php?redirect=' + encodeURIComponent(currentUrl);
            });
        }

        if (loginPopup) {
            loginPopup.addEventListener('click', function (e) {
                if (e.target === loginPopup) {
                    loginPopup.style.display = 'none';
                }
            });
        }

        // SIZE HANDLING
        const sizeButtons       = document.querySelectorAll('.size-btn');
        const selectedSizeInput = document.getElementById('selectedSize');
        const cartForm          = document.getElementById('cartForm');

        sizeButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                sizeButtons.forEach(b => b.classList.remove('active', 'error'));
                btn.classList.add('active');
                selectedSizeInput.value = btn.dataset.size;
            });
        });

        cartForm.addEventListener('submit', function (e) {
            if (!selectedSizeInput.value) {
                e.preventDefault();
                sizeButtons.forEach(b => b.classList.add('error'));
                alert('Please select a size before adding to cart.');
            }
        });

        // QUANTITY HANDLING
        const minusBtn   = document.getElementById('minusBtn');
        const plusBtn    = document.getElementById('plusBtn');
        const qtyDisplay = document.getElementById('qtyDisplay');
        const qtyInput   = document.getElementById('qtyInput');

        function setQty(val) {
            let qty = parseInt(val, 10);
            if (isNaN(qty) || qty < 1) {
                qty = 1;
            }
            qtyDisplay.value = qty;
            qtyInput.value   = qty;
        }

        minusBtn.addEventListener('click', () => {
            let current = parseInt(qtyDisplay.value, 10) || 1;
            if (current > 1) {
                setQty(current - 1);
            }
        });

        plusBtn.addEventListener('click', () => {
            let current = parseInt(qtyDisplay.value, 10) || 1;
            setQty(current + 1);
        });

        // ketik manual
        qtyDisplay.addEventListener('input', () => {
            qtyDisplay.value = qtyDisplay.value.replace(/[^\d]/g, '');
        });

        qtyDisplay.addEventListener('blur', () => {
            setQty(qtyDisplay.value);
        });
    </script>
</body>
</html>
