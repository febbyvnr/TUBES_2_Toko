<?php
require_once __DIR__ . '/../config/db.php';

function resolveImage($row)
{
    $possible = ['image', 'image_name', 'img', 'gambar'];
    foreach ($possible as $key) {
        if (!empty($row[$key])) {
            return '../assets/products/' . $row[$key];
        }
    }
    return '../assets/products/placeholder.png';
}

function resolveTitle($row)
{
    if (!empty($row['name'])) return htmlspecialchars($row['name']);
    if (!empty($row['title'])) return htmlspecialchars($row['title']);
    return "Untitled Product";
}

function resolvePrice($row)
{
    if (isset($row['price'])) {
        return '$' . number_format((float)$row['price'], 2);
    } elseif (isset($row['harga'])) {
        return 'Rp ' . number_format((float)$row['harga'], 0, ',', '.');
    }
    return '';
}

function renderProductCard($row)
{
    $image = resolveImage($row);
    $title = resolveTitle($row);
    $price = resolvePrice($row);

    return "
        <div class=\"card\">
            <div class=\"card-media\" style=\"background-image:url('{$image}');\"></div>
            <div class=\"card-caption\">{$title}</div>
            " . ($price !== '' ? "<div class=\"card-price\">{$price}</div>" : "") . "
        </div>
    ";
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Products — FEYORA</title>
    <link rel="stylesheet" href="../styles/ListProduct.css?v=<?=time()?>">
    <link rel="stylesheet" href="../styles/HomePage.css?v=<?=time()?>">
</head>

<body>
<?php include __DIR__ . '/../includes/header.php'; ?>

<main>
    <section class="container product-list">
        <h3 class="section-title">New Arrivals</h3>

        <!-- Filter & Sort Form -->
        <form method="get" class="filters" style="margin-bottom:18px; display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
            <?php
            // load distinct categories and sizes for filter selects
            $cats = [];
            // $sizes = [];
            if ($cres = $mysqli->query("SELECT DISTINCT IFNULL(category, '') AS category FROM products")) {
                    while ($r = $cres->fetch_assoc()) {
                            if ($r['category'] !== '') $cats[] = $r['category'];
                    }
                    $cres->free();
            }
            // if ($sres = $mysqli->query("SELECT DISTINCT IFNULL(size, '') AS size FROM products")) {
            //         while ($r = $sres->fetch_assoc()) {
            //                 if ($r['size'] !== '') $sizes[] = $r['size'];
            //         }
            //         $sres->free();
            // }

            $qCategory = isset($_GET['category']) ? htmlspecialchars($_GET['category']) : '';
            // $qSize = isset($_GET['size']) ? htmlspecialchars($_GET['size']) : '';
            $qMin = isset($_GET['price_min']) ? htmlspecialchars($_GET['price_min']) : '';
            $qMax = isset($_GET['price_max']) ? htmlspecialchars($_GET['price_max']) : '';
            $qSort = isset($_GET['sort']) ? htmlspecialchars($_GET['sort']) : '';
            ?>

            <label class="filter-label">
                Category
                <select name="category">
                    <option value="">All</option>
                    <?php foreach ($cats as $c): ?>
                        <option value="<?=htmlspecialchars($c)?>" <?=($qCategory===$c)?'selected':''?>><?=htmlspecialchars($c)?></option>
                    <?php endforeach; ?>
                </select>
            </label>

            <!-- <label class="filter-label">
                Size
                <select name="size">
                    <option value="">All</option>
                    <!-- <?php foreach ($sizes as $s): ?>
                        <option value="<?=htmlspecialchars($s)?>" <?=($qSize===$s)?'selected':''?>><?=htmlspecialchars($s)?></option>
                    <?php endforeach; ?> -->
                </select>
            </label> -->

            <label class="filter-label">
                Price min
                <input type="number" step="1000" name="price_min" value="<?=$qMin?>" placeholder="0">
            </label>

            <label class="filter-label">
                Price max
                <input type="number" step="1000" name="price_max" value="<?=$qMax?>" placeholder="99999999">
            </label>

            <label class="filter-label">
                Sort
                <select name="sort">
                    <option value="">Newest</option>
                    <option value="price_asc" <?=($qSort==='price_asc')?'selected':''?>>Price: Low → High</option>
                    <option value="price_desc" <?=($qSort==='price_desc')?'selected':''?>>Price: High → Low</option>
                </select>
            </label>
            
            <label class="filter-label" style="margin-left:auto">
                <span style="visibility: hidden;">__</span>
                <div class="filter-actions">
                    <button type="submit" class="btn-primary">Apply</button>
                    <a href="listProduct.php" class="reset-link">Reset</a>
                </div>
            </label>
        </form>

        <div class="cards">
        <?php
        // Build SQL with filters
        $conditions = [];
        if (!empty($_GET['category'])) {
                $cat = $mysqli->real_escape_string($_GET['category']);
                $conditions[] = "category = '" . $cat . "'";
        }
        // if (!empty($_GET['size'])) {
        //         $size = $mysqli->real_escape_string($_GET['size']);
        //         $conditions[] = "size = '" . $size . "'";
        // }
        if (isset($_GET['price_min']) && $_GET['price_min'] !== '') {
                $min = (float) $_GET['price_min'];
                $conditions[] = "price >= " . $min;
        }
        if (isset($_GET['price_max']) && $_GET['price_max'] !== '') {
                $max = (float) $_GET['price_max'];
                $conditions[] = "price <= " . $max;
        }

        $where = '';
        if (count($conditions) > 0) {
                $where = 'WHERE ' . implode(' AND ', $conditions);
        }

        $order = 'ORDER BY id DESC';
        if (!empty($_GET['sort'])) {
                if ($_GET['sort'] === 'price_asc') $order = 'ORDER BY price ASC';
                if ($_GET['sort'] === 'price_desc') $order = 'ORDER BY price DESC';
        }

        // Pagination setup
        $perPage = 30;
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

        // total count for current filters
        $countSql = "SELECT COUNT(*) AS cnt FROM products " . $where;
        $total = 0;
        if ($cres = $mysqli->query($countSql)) {
            $crow = $cres->fetch_assoc();
            $total = (int) $crow['cnt'];
            $cres->free();
        }

        $totalPages = max(1, (int) ceil($total / $perPage));
        if ($page > $totalPages) $page = $totalPages;
        $offset = ($page - 1) * $perPage;

        $sql = "SELECT * FROM products " . $where . " " . $order . " LIMIT " . $perPage . " OFFSET " . $offset;

        if ($result = $mysqli->query($sql)) {
            while ($row = $result->fetch_assoc()) {
                $imageName = '';
                if (!empty($row['image'])) $imageName = $row['image'];
                elseif (!empty($row['image_name'])) $imageName = $row['image_name'];
                elseif (!empty($row['img'])) $imageName = $row['img'];
                elseif (!empty($row['gambar'])) $imageName = $row['gambar'];

                if (empty($imageName)) {
                        $imagePath = '../assets/products/placeholder.png';
                } else {
                        $imagePath = '../assets/products/' . $imageName;
                }

                $title = !empty($row['name']) ? htmlspecialchars($row['name']) : (!empty($row['title']) ? htmlspecialchars($row['title']) : 'Untitled Product');
                $price = '';
                if (isset($row['price'])) {
                        $price = 'Rp ' . number_format((float)$row['price'], 2);
                } elseif (isset($row['harga'])) {
                        $price = 'Rp ' . number_format((float)$row['harga'], 0, ',', '.');
                }

                echo "        <div class=\"product-card\">\n";
                echo "          <a href=\"detailProduct.php?id=" . urlencode($row['id']) . "\">\n";
                echo "            <div class=\"product-media\" style=\"background-image:url('${imagePath}');\"></div>\n";
                echo "          </a>\n";
                echo "          <div class=\"product-info\">\n";
                echo "            <div class=\"product-title\">${title}</div>\n";
                echo "            <div class=\"product-price\">${price}</div>\n";
                echo "          </div>\n";
                echo "        </div>\n";
            }
            $result->free();
        } else {
                echo "<p class=\"muted\">Tidak dapat memuat produk: " . htmlspecialchars($mysqli->error) . "</p>\n";
        }
        ?>
        </div>

        <?php
        if (isset($totalPages) && $totalPages > 1) {
            echo '<div class="pagination">';

            $baseParams = $_GET;

            // Prev button
            if ($page > 1) {
                $baseParams['page'] = $page - 1;
                echo '<a class="page-btn" href="?' . htmlspecialchars(http_build_query($baseParams)) . '">&lt;</a>';
            } else {
                echo '<span class="page-btn disabled">&lt;</span>';
            }

            $start = max(1, $page - 3);
            $end = min($totalPages, $page + 3);

            if ($start > 1) {
                $baseParams['page'] = 1;
                echo '<a class="page-btn" href="?' . htmlspecialchars(http_build_query($baseParams)) . '">1</a>';
                if ($start > 2) echo '<span class="page-btn disabled">...</span>';
            }

            for ($p = $start; $p <= $end; $p++) {
                $baseParams['page'] = $p;
                if ($p == $page) {
                    echo '<span class="page-btn active">' . $p . '</span>';
                } else {
                    echo '<a class="page-btn" href="?' . htmlspecialchars(http_build_query($baseParams)) . '">' . $p . '</a>';
                }
            }

            if ($end < $totalPages) {
                if ($end < $totalPages - 1) echo '<span class="page-btn disabled">...</span>';
                $baseParams['page'] = $totalPages;
                echo '<a class="page-btn" href="?' . htmlspecialchars(http_build_query($baseParams)) . '">' . $totalPages . '</a>';
            }

            // Next button
            if ($page < $totalPages) {
                $baseParams['page'] = $page + 1;
                echo '<a class="page-btn" href="?' . htmlspecialchars(http_build_query($baseParams)) . '">&gt;</a>';
            } else {
                echo '<span class="page-btn disabled">&gt;</span>';
            }
            echo '</div>';
        }
        ?>

    </section>

  <footer class="site-footer">
        
