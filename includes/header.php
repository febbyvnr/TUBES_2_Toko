<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/db.php';

// ================== LOAD USER ==================
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

// siapkan displayName, initials, dan avatar
$displayName  = null;
$initials     = null;
$avatarUrl    = null;
$avatarFsPath = null;

if ($currentUser) {
    // username aman (tidak null)
    $usernameSafe = trim((string)($currentUser['username'] ?? ''));
    if ($usernameSafe === '') {
        $displayName = 'User';
        $initials    = 'US';
    } else {
        $displayName = $usernameSafe;
        if (function_exists('mb_substr')) {
            $initials = strtoupper(mb_substr($usernameSafe, 0, 2));
        } else {
            $initials = strtoupper(substr($usernameSafe, 0, 2));
        }
    }

    // path foto profil (URL & filesystem)
    if (!empty($currentUser['profile_photo'])) {
        $avatarUrl    = '/TUBES_2_Toko/assets/profile/' . $currentUser['profile_photo'];
        $avatarFsPath = __DIR__ . '/../assets/profile/' . $currentUser['profile_photo'];
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
        <img src="/TUBES_2_Toko/assets/Logo Feyora.png" 
             style="width:40px; height:auto;">
      </div>
    </a>

    <nav class="topnav">
        <a href="/TUBES_2_Toko/index.php">New Arrivals</a>
        <a href="/TUBES_2_Toko/product/listProduct.php">Shop Now</a>
        <a href="/TUBES_2_Toko/product/listProduct.php?collection=studio">Studio Collection</a>
        <a href="/TUBES_2_Toko/product/listProduct.php?laststock=1">Last Stock</a>
    </nav>

    <div class="actions" style="display:flex; align-items:center; gap:16px;">
      <?php if ($currentUser): ?>
        <!-- CART (logged in) -->
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
            <?php if ($avatarUrl && $avatarFsPath && file_exists($avatarFsPath)): ?>
              <img class="profile-circle" 
                   src="<?= htmlspecialchars($avatarUrl) ?>" 
                   alt="user"
                   style="
                      width:38px; 
                      height:38px; 
                      border-radius:50%; 
                      object-fit:cover;
                      border:2px solid #ddd;">
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
                <?= htmlspecialchars($initials ?? 'US') ?>
              </div>
            <?php endif; ?>
            <span class="user-name" style="color:#333; font-weight:500;">
              <?= htmlspecialchars($displayName ?? 'User') ?>
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
               onmouseout="this.style.background='white'">
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
               onmouseout="this.style.background='white'">
              Logout
            </a>
          </div>
        </div>
      <?php else: ?>
        <!-- GUEST (belum login) -->
        <a class="icon" 
           href="/TUBES_2_Toko/auth/login.php"
           style="text-decoration:none; color:#333; font-weight:500;">
           Login
        </a>

        <!-- CART UNTUK GUEST: trigger popup -->
        <a class="icon" 
           href="/TUBES_2_Toko/cart/listCart.php"
           id="guest-cart-link"
           style="font-size:22px; text-decoration:none; color:#333;">
           🛒
        </a>
      <?php endif; ?>
    </div>
  </div>
</header>

<?php if (!empty($_SESSION['flash_success'])): ?>
  <div style="
        max-width: var(--container);
        margin: 8px auto 0;
        padding: 10px 16px;
        border-radius: 8px;
        background: #e7f9ee;
        border: 1px solid #9bd4af;
        color: #236b3d;
        font-size: 14px;">
    <?= htmlspecialchars($_SESSION['flash_success']) ?>
  </div>
  <?php unset($_SESSION['flash_success']); ?>
<?php endif; ?>

<!-- POPUP LOGIN DULU UNTUK CART -->
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
      Silakan login terlebih dahulu untuk melihat keranjang belanja Anda.
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
  (function(){
    // PROFILE DROPDOWN
    document.addEventListener("click", function (e) {
        const menu = document.querySelector(".profile-menu");
        const dropdown = document.querySelector(".profile-dropdown");
        const toggle = document.querySelector(".profile-toggle");

        if (toggle && toggle.contains(e.target)) {
            dropdown.style.display = dropdown.style.display === "block" ? "none" : "block";
        } else if (menu && !menu.contains(e.target)) {
            if (dropdown) dropdown.style.display = "none";
        }
    });

    // LOGOUT CONFIRM
    document.addEventListener('click', function(e){
      var a = e.target.closest('#logout-link');
      if (a) {
        e.preventDefault();
        if (confirm('Anda yakin ingin logout?')) {
          window.location.href = a.href;
        }
      }
    });

    // GUEST CART POPUP
    var guestCart = document.getElementById('guest-cart-link');
    var popup     = document.getElementById('login-required-popup');
    var btnClose  = document.getElementById('login-popup-close');
    var btnGo     = document.getElementById('login-popup-go');

    if (guestCart && popup) {
        guestCart.addEventListener('click', function(e){
            e.preventDefault();
            popup.style.display = 'flex';
        });
    }

    if (btnClose && popup) {
        btnClose.addEventListener('click', function(){
            popup.style.display = 'none';
        });
    }

    if (btnGo) {
        btnGo.addEventListener('click', function(){
            window.location.href = '/TUBES_2_Toko/auth/login.php';
        });
    }

    if (popup) {
        popup.addEventListener('click', function(e){
            if (e.target === popup) {
                popup.style.display = 'none';
            }
        });
    }
  })();
</script>