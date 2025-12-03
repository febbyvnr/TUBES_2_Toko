<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/db.php';

// load current user info if logged in
$currentUser = null;
if (!empty($_SESSION['user_id'])) {
    $uid = (int) $_SESSION['user_id'];
    $stmt = $mysqli->prepare("SELECT id, username, profile_photo FROM user WHERE id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('i', $uid);
        $stmt->execute();
        $res = $stmt->get_result();
        $currentUser = $res->fetch_assoc();
        $stmt->close();
    }
}
?>
<header style="background: #fff; border-bottom: 1px solid #ddd;">
  <div style="max-width: var(--container); margin: 0 auto; padding: 0 20px;
        display: flex; align-items: center; justify-content: space-between; padding: 16px 0;">
    <a href="/TUBES_2_Toko/index.php" 
        style="text-decoration:none; color:inherit;">
        <div style="display: flex; align-items: center; gap: 6px; font-weight: 700;
                    font-size: x-large; font-family: 'Poppins', sans-serif; color: #ff2d7a;">
            <img src="/TUBES_2_Toko/assets/products/logo.jpg" 
                style="width:40px; height:auto;">
            FEYORA
        </div>
        </a>
    <nav class="topnav">
        <a href="/TUBES_2_Toko/index.php">New Arrivals</a>
        <a href="/TUBES_2_Toko/product/listProduct.php">All Tops</a>
        <a href="/TUBES_2_Toko/product/listProduct.php?collection=studio">Studio Collection</a>
        <a href="/TUBES_2_Toko/product/listProduct.php?laststock=1">Last Stock</a>
   </nav>
    <div class="actions" style="display:flex; align-items:center; gap:16px;">
        <?php if ($currentUser):
            $profile = $currentUser['profile_photo'] 
                ? '/TUBES_2_Toko/assets/profile/' . $currentUser['profile_photo'] 
                : null;

            $displayName = $currentUser['username'];
        ?>
            <!-- CART -->
            <a class="icon" 
            href="/TUBES_2_Toko/cart/listCart.php" 
            title="Cart"
            style="font-size:22px; text-decoration:none; color:#333;">
            🛒
            </a>
            <!-- PROFILE AREA -->
            <div class="profile-menu" 
                style="position:relative; display:flex; align-items:center;">
                <button class="profile-toggle" type="button"
                    style="
                        display:flex; 
                        align-items:center; 
                        gap:8px; 
                        background:none; 
                        border:none; 
                        cursor:pointer;">
                    <?php if ($profile && file_exists(__DIR__ . '/../' . ltrim($profile, '/'))): ?>
                        <img class="profile-circle" 
                            src="<?= htmlspecialchars($profile) ?>" 
                            alt="user"
                            style="
                                width:38px; 
                                height:38px; 
                                border-radius:50%; 
                                object-fit:cover;
                                border:2px solid #ddd;" >
                    <?php else: ?>
                        <div class="profile-circle placeholder"
                            style="
                                width:38px;
                                height:38px;
                                border-radius:50%;
                                background:#ddd;
                                display:flex;
                                align-items:center;
                                justify-content:center;
                                font-weight:bold;
                                color:#555;
                                border:2px solid #ccc;">
                            <?= strtoupper(mb_substr($currentUser['username'], 0, 2)) ?>
                        </div>
                    <?php endif; ?>
                    <span class="user-name" style="color:#333; font-weight:500;">
                        <?= htmlspecialchars($displayName) ?>
                    </span>
                </button>
                <!-- DROPDOWN -->
                <div class="profile-dropdown"
                    aria-hidden="true"
                    style="
                        display:none;
                        position:absolute;
                        top:48px;
                        right:0;
                        background:white;
                        border-radius:8px;
                        box-shadow:0 4px 14px rgba(0,0,0,0.12);
                        padding:10px 0;
                        min-width:160px;
                        z-index:20;">
                    <a href="/TUBES_2_Toko/user/settingProfile.php"
                    style="
                            display:block;
                            padding:10px 16px;
                            color:#333;
                            text-decoration:none;"
                    onmouseover="this.style.background='#f3f3f3'"
                    onmouseout="this.style.background='white'"
                    >
                        Settings
                    </a>
                    <a href="/TUBES_2_Toko/auth/logout.php" id="logout-link"
                    style="
                            display:block;
                            padding:10px 16px;
                            color:#d00;
                            text-decoration:none;
                            font-weight:500;"
                    onmouseover="this.style.background='#ffe9e9'"
                    onmouseout="this.style.background='white'"
                    >
                        Logout
                    </a>
                </div>
            </div>
        <?php else: ?>
            <a class="icon" 
            href="/TUBES_2_Toko/auth/login.php"
            style="text-decoration:none; color:#333; font-weight:500;">
            Login
            </a>
            <a class="icon" 
            href="/TUBES_2_Toko/cart/listCart.php"
            style="font-size:22px; text-decoration:none; color:#333;">
            🛒
            </a>
        <?php endif; ?>
    </div>
  </div>
</header>
<script>
  (function(){
    //profile dropdown
    document.addEventListener("click", function (e) {
        const menu = document.querySelector(".profile-menu");
        const dropdown = document.querySelector(".profile-dropdown");
        const toggle = document.querySelector(".profile-toggle");

        if (toggle.contains(e.target)) {
            dropdown.style.display = dropdown.style.display === "block" ? "none" : "block";
        } else if (!menu.contains(e.target)) {
            dropdown.style.display = "none";
        }
    });

    // logout confirmation
    document.addEventListener('click', function(e){
      var a = e.target.closest('#logout-link');
      if (a) {
        e.preventDefault();
        if (confirm('Anda yakin ingin logout?')) {
          window.location.href = a.href;
        }
      }
    });
  })();
</script>

