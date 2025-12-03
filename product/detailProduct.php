<?php

require_once __DIR__ . '/../config/db.php';

//Ambil ID
if (!isset($_GET['id'])) {
    die("Product not found.");
}

$id = intval($_GET['id']);

//Query produk berdasarkan ID
$stmt = $mysqli->prepare("SELECT * FROM products WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows === 0) {
    die("Product not found.");
}

$product = $result->fetch_assoc();

//Resolve image
$image = '../assets/products/placeholder.png';
foreach(['image', 'image_name', 'img', 'gambar'] as $key) {
    if(!empty($product[$key])) {
        $image = '../assets/products/' .$product[$key];
        break;
    }
}

$title = !empty($product['name']) ? htmlspecialchars($product['name']) :
        (!empty($product['title']) ? htmlspecialchars($product['title']) : 'Untitled Product');

if(!empty($product['price'])) {
    $price = 'Rp ' . number_format($product['price'], 2); 
}else if(!empty($product['harga'])){
    $price = 'Rp ' . number_format($product['harga'], 0, ',', '.');
}else{
    $price = 'No Price';
}

$desc = !empty($product['description']) ? nl2br(htmlspecialchars($product['description'])) : 'No description available.';

$currentSize = isset($product['size']) ? $product['size'] : '';
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= $title ?> - FEYORA</title>

        <link rel="stylesheet" href="../styles/HomePage.css?v=<?=time()?>">
        <link rel="stylesheet" href="../styles/DetailProduct.css?v=<?=time()?>">
    </head>

    <body>
        <?php include __DIR__ . '/../includes/header.php'; ?>

        <main class="detail-container">
            <div class="image-section">
                <img src="<?= $image ?>" alt="<?=$title ?>">
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

                <form action="../cart/add.php" method="POST" id="cartForm">
                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                    <input type="hidden" name="size" id="selectedSize" value="<?= $currentSize ?>">
                    <input type="hidden" name="qty" value="1">

                    <button type="submit" class="add-cart-btn">Add to Cart</button>
                </form>

                <a href="listProduct.php" class="back-link">← Back to Products</a>
            </div>
            
        </main>

        <script>
            document.querySelectorAll('.size-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    document.querySelectorAll('.size-btn').forEach(b => b.classList.remove('active'));
                    btn.classList.add('active');
                    console.log("Selected size:", btn.dataset.size);
                });
            });
        </script>

        <script>
        // Update hidden input setiap klik size
        document.querySelectorAll('.size-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.getElementById('selectedSize').value = btn.dataset.size;
            });
        });
    </script>
    </body>
</html>


