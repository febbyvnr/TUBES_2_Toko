<?php
require_once __DIR__ . '/config/db.php';

// helper buat ambil nama file gambar dari row
function resolveImageFromRow(array $row): string
{
    $imageName = '';
    if (!empty($row['image']))        $imageName = $row['image'];

    if ($imageName === '') {
        return 'assets/products/placeholder.png';
    }
    // base href sudah /TUBES_2_Toko/ jadi cukup relative path
    return 'assets/products/' . $imageName;
}

// Ambil distinct kategori
$categoryCards = [];
$featuredStudio = [];


$sqlFeat = "
    SELECT *
    FROM products
    WHERE name LIKE 'Studio Collection%'
    ORDER BY id DESC
";

$sqlCat = "
    SELECT DISTINCT IFNULL(category, '') AS category
    FROM products
    WHERE category IS NOT NULL AND category <> ''
    ORDER BY category
";

if ($cres = $mysqli->query($sqlCat)) {
    while ($crow = $cres->fetch_assoc()) {
        $cat = $crow['category'];

        // default image
        $imagePath = 'assets/products/placeholder.png';

        // cari satu produk di kategori ini yang punya gambar
        $stmt = $mysqli->prepare("
            SELECT image
            FROM products
            WHERE category = ?
              AND (
                    image IS NOT NULL AND image <> ''
                  )
            ORDER BY id DESC
            LIMIT 1
        ");

        if ($stmt) {
            $stmt->bind_param("s", $cat);
            $stmt->execute();
            $resProd = $stmt->get_result();
            if ($prodRow = $resProd->fetch_assoc()) {
                $imagePath = resolveImageFromRow($prodRow);
            }
            $stmt->close();
        }

        $categoryCards[] = [
            'category' => $cat,
            'image'    => $imagePath,
            // link ke listProduct + filter category
            'link'     => 'product/listProduct.php?category=' . urlencode($cat),
        ];
    }
    $cres->free();
}

if ($fres = $mysqli->query($sqlFeat)) {
    while ($row = $fres->fetch_assoc()) {
        $imageName = '';
        if (!empty($row['image'])) {
            $imageName = $row['image'];
        }

        if ($imageName === '') {
            $imagePath = 'assets/products/placeholder.png';
        } else {
            $imagePath = 'assets/products/' . $imageName;
        }

        $title = !empty($row['name'])
            ? htmlspecialchars($row['name'])
            : 'Studio Collection';

        $price = '';
        if (isset($row['price'])) {
            $price = 'Rp ' . number_format((float)$row['price'], 0, ',', '.');
        } elseif (isset($row['harga'])) {
            $price = 'Rp ' . number_format((float)$row['harga'], 0, ',', '.');
        }

        $featuredStudio[] = [
            'id'    => $row['id'],
            'image' => $imagePath,
            'title' => $title,
            'price' => $price,
        ];
    }
    $fres->free();
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">

  <base href="/TUBES_2_Toko/">

  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <title>FEYORA — Home</title>

  <link rel="stylesheet" href="styles/HomePage.css?v=<?=time()?>">
</head>
<body>
  <?php include __DIR__ . '/includes/header.php'; ?>
  <main>
    <section class="hero">
      <div class="container hero-inner">
        <p class="lead">Discover fresh styles and vibrant tops for the new season. Effortless elegance, designed for you.</p>
        <a class="btn-primary" href="product/listProduct.php">Shop Now</a>
      </div>
    </section>

    <section class="container categories">
      <h3 class="section-title">Shop by Category</h3>

      <div class="category-carousel">
        <div class="cards">
          <?php if (!empty($categoryCards)): ?>
            <?php foreach ($categoryCards as $card): ?>
              <div class="card">
                <a href="<?= htmlspecialchars($card['link']) ?>">
                  <div class="card-media" style="background-image:url('<?= htmlspecialchars($card['image']) ?>');"></div>
                  <div class="card-caption"><?= htmlspecialchars($card['category']) ?></div>
                </a>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <!-- Fallback kalau belum ada data di database -->
            <div class="card">
              <div class="card-media" style="background-image:url('assets/products/blouse.jpg');"></div>
              <div class="card-caption">Blouses</div>
            </div>
            <div class="card">
              <div class="card-media" style="background-image:url('assets/products/casualTops.jpg');"></div>
              <div class="card-caption">Casual Tops</div>
            </div>
            <div class="card">
              <div class="card-media" style="background-image:url('assets/products/knitwear.jpg');"></div>
              <div class="card-caption">Knitwear</div>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </section>

        <!-- FEATURED COLLECTION: Studio Collection -->
    <section class="container featured">
      <h3 class="section-title">Featured Collection: Studio Collection</h3>
      <?php if (!empty($featuredStudio)): ?>
        <div class="featured-carousel">
          <div class="featured-track">
            <?php foreach ($featuredStudio as $item): ?>
              <a class="featured-card" href="product/detailProduct.php?id=<?= htmlspecialchars($item['id']) ?>">
                <div class="featured-media" style="background-image:url('<?= htmlspecialchars($item['image']) ?>');"></div>
                <div class="featured-meta">
                  <div class="featured-title"><?= htmlspecialchars($item['title']) ?></div>
                  <?php if ($item['price'] !== ''): ?>
                    <div class="featured-price"><?= htmlspecialchars($item['price']) ?></div>
                  <?php endif; ?>
                </div>
              </a>
            <?php endforeach; ?>
          </div>
        </div>
      <?php else: ?>
        <p class="muted">Belum ada produk “Studio Collection” yang dapat ditampilkan.</p>
      <?php endif; ?>
    </section>

    <footer class="site-footer">
      <div class="container footer-grid">
        <div class="col">
          <div class="brand">FEYORA</div>
          <p class="muted">Timeless tops, designed for the modern woman.</p>
        </div>
        <div class="col">
          <strong>Shop</strong>
          <ul>
            <li><a href="#">New In</a></li>
            <li><a href="#">Tops</a></li>
            <li><a href="#">Blouses</a></li>
            <li><a href="#">Sale</a></li>
          </ul>
        </div>
        <div class="col">
          <strong>About</strong>
          <ul>
            <li><a href="#">Our Story</a></li>
            <li><a href="#">Careers</a></li>
            <li><a href="#">Sustainability</a></li>
          </ul>
        </div>
        <div class="col">
          <strong>Support</strong>
          <ul>
            <li><a href="#">Contact Us</a></li>
            <li><a href="#">FAQ</a></li>
            <li><a href="#">Shipping & Returns</a></li>
          </ul>
        </div>
      </div>
      <div class="container copyright">© 2024 Aura. All rights reserved.</div>
    </footer>
  </main>
</body>
</html>
