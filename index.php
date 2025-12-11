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
    return 'assets/products/' . $imageName;
}

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

        $imagePath = 'assets/products/placeholder.png';

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
<?php
  $pageTitle   = 'FEYORA — Home'; 
  $extraStyles = '';

  include __DIR__ . '/includes/head.php';
?>
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

    <?php include __DIR__ . '/includes/footer.php'; ?>
  </main>
</body>
</html>
