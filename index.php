<?php

?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <title>Aura — Home</title>
  <link rel="stylesheet" href="styles/HomePage.css">
</head>
<body>
  <header class="topbar">
    <div class="container topbar-inner">
      <div class="brand">
        <img 
          src="assets/logo.jpg"
          alt="Logo"
          class="logo"
        >
        <span>FEYORA</span>
      </div>
      <nav class="topnav">
        <a href="#">New In</a>
        <a href="#">Tops</a>
        <a href="#">Blouses</a>
        <a href="#">Sale</a>
      </nav>
      <div class="actions">
        <a class="icon" href="/pages/auth/login.php" title="Login">Login</a>
        <a class="icon" href="#" title="Account">❤</a>
        <a class="icon" href="#" title="Cart">🛒</a>
      </div>
    </div>
  </header>

  <main>
    <section class="hero">
      <div class="container hero-inner">
        <h1>The Spring Collection Is Here</h1>
        <p class="lead">Discover fresh styles and vibrant tops for the new season. Effortless elegance, designed for you.</p>
        <a class="btn-primary" href="product/listProduct.php">Shop New Arrivals</a>
      </div>
    </section>

    <section class="container categories">
      <h3 class="section-title">Shop by Category</h3>
      <div class="cards">
        <div class="card">
          <div class="card-media" style="background-image:url('assets/blouse.jpg');"></div>
          <div class="card-caption">Blouses</div>
        </div>
        <div class="card">
          <div class="card-media" style="background-image:url('assets/casualTops.jpg');"></div>
          <div class="card-caption">Casual Tops</div>
        </div>
        <div class="card">
          <div class="card-media" style="background-image:url('assets/knitwear.jpg');"></div>
          <div class="card-caption">Knitwear</div>
        </div>
      </div>
    </section>

    <section class="container trending">
      <h3 class="section-title">Trending Now</h3>
      <div class="grid">
        <div class="product">
          <img className="product-img" src="assets/silkCamisole.jpg" alt="Silk Camisole" />
          <div class="product-meta">
            <div class="title">Silk Camisole</div>
            <div class="price">$85.00</div>
          </div>
        </div>
        <div class="product">
          <img className="product-img" src="assets/ribbedKnitTop.jpg" alt="Ribbed Knit Top" />
          <div class="product-meta">
            <div class="title">Ribbed Knit Top</div>
            <div class="price">$95.00</div>
          </div>
        </div>
        <div class="product">
          <img className="product-img" src="assets/stripedLongSleeve.jpg" alt="Stripped Long-Sleeve" />
          <div class="product-meta">
            <div class="title">Striped Long-Sleeve</div>
            <div class="price">$70.00</div>
          </div>
        </div>
        <div class="product">
          <img className="product-img" src="assets/puffSleeveBlouse.jpg" alt="Puff-Sleeve Blouse" />
          <div class="product-meta">
            <div class="title">Puff-Sleeve Blouse</div>
            <div class="price">$110.00</div>
          </div>
        </div>
      </div>
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
