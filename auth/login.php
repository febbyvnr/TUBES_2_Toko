<?php
require_once __DIR__ . '/../config/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$error = '';
$activationLink = null; // NEW: untuk simpan link aktivasi kalau akun belum aktif

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier'] ?? ''); // username or email
    $password   = $_POST['password'] ?? '';

    if ($identifier === '' || $password === '') {
        $error = 'Isi username/email dan password.';
    } else {
        $stmt = $mysqli->prepare("
            SELECT id, username, password, profile_photo, is_active, activation_token  -- NEW
            FROM user 
            WHERE username = ? OR email = ? 
            LIMIT 1
        ");
        if ($stmt) {
            $stmt->bind_param('ss', $identifier, $identifier);
            $stmt->execute();
            $res  = $stmt->get_result();
            $user = $res->fetch_assoc();
            $stmt->close();

            if ($user && password_verify($password, $user['password'])) {

                // CEK SUDAH AKTIF ATAU BELUM
                if ((int)$user['is_active'] !== 1) {
                    // NEW: kalau punya activation_token, buat link aktivasi
                    if (!empty($user['activation_token'])) {
                        $baseUrl = 'http://localhost/TUBES_2_Toko/auth';
                        $activationLink = $baseUrl . '/activate.php?token=' . urlencode($user['activation_token']);
                        $error = 'Akun Anda belum aktif. Silakan aktivasi akun dengan tombol di bawah ini.';
                    } else {
                        // kalau token kosong (misal dihapus), suruh kontak admin
                        $error = 'Akun Anda belum aktif dan link aktivasi tidak tersedia. Silakan hubungi admin.';
                    }
                } else {
                    // aktif boleh login
                    $_SESSION['user_id']  = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    header('Location: /TUBES_2_Toko/index.php');
                    exit;
                }

            } else {
                $error = 'Login gagal: username/email atau password salah.';
            }
        } else {
            $error = 'Query gagal: ' . $mysqli->error;
        }
    }
}

$slides = [];
if ($res = $mysqli->query("
    SELECT image 
    FROM products 
    WHERE image IS NOT NULL AND image <> '' 
    ORDER BY added DESC 
    LIMIT 8
")) {
    while ($row = $res->fetch_assoc()) {
        $slides[] = '/TUBES_2_Toko/assets/products/' . rawurlencode($row['image']);
    }
    $res->free();
}
$slidesJson = json_encode($slides);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Login — Feyora</title>
  <link rel="stylesheet" href="../styles/Login.css?v=<?=time()?>">
</head>
<body>
<?php include __DIR__ . '/../includes/header.php'; ?>

<main class="auth-layout">
  <!-- KIRI: SLIDESHOW GAMBAR PRODUK -->
  <section class="auth-visual">
    <div id="login-hero"
      class="auth-visual-image"
      style="background-image:url('<?= htmlspecialchars($slides[0] ?? "/TUBES_2_Toko/assets/products/placeholder.png") ?>');">
    </div>

    <div class="auth-visual-overlay">
      <h1>Welcome Back</h1>
      <p>Sign in to continue your favorite collections and complete orders quickly</p>
    </div>
  </section>

  <!-- KANAN: FORM LOGIN -->
  <section class="auth-form">
    <div class="auth-form-inner">
       <div style="text-align:center; margin-bottom:18px;">
            <img src="/TUBES_2_Toko/assets/Logo Feyora.png" 
                alt="Feyora Logo"
                style="width:150px; height:auto; opacity:0.9;">
        </div>
      <h2 class="auth-title">Login</h2>

      <?php if ($error): ?>
        <div class="auth-error"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <?php if ($activationLink): ?>  <!--  OPSIONAL NNT MIKIRNY : tombol aktivasi kalau akun belum aktif -->
        <div class="auth-success" style="margin-bottom:14px;">
          <div>Belum menerima email aktivasi? Anda bisa aktivasi langsung lewat tombol berikut.</div>
          <div style="margin-top:10px;">
            <a href="<?= htmlspecialchars($activationLink) ?>"
               style="
                 display:inline-block;
                 background:#2BAF74;
                 padding:10px 22px;
                 color:#fff;
                 border-radius:999px;
                 text-decoration:none;
                 font-weight:600;
                 font-size:14px;
               ">
               Aktivasi Akun
            </a>
          </div>
        </div>
      <?php endif; ?>

      <form method="post" class="auth-form-fields">
        <label class="auth-label">
          Username or Email
          <input
            class="auth-input"
            name="identifier"
            value="<?= htmlspecialchars($_POST['identifier'] ?? '') ?>"
            autocomplete="username"
          >
        </label>

        <label class="auth-label">
          Password
          <input
            class="auth-input"
            type="password"
            name="password"
            autocomplete="current-password"
          >
        </label>

        <div class="auth-meta">
          <span>Don't have an account yet? <a href="register.php">Register now</a></span>
        </div>

        <button class="btn-primary auth-submit" type="submit">Login</button>
      </form>
    </div>
  </section>
</main>

<script>
(function () {
  const slides = <?= $slidesJson ?: '[]' ?>;
  const el = document.getElementById('login-hero');
  if (!el || !slides || slides.length <= 1) return;

  let idx = 0;
  setInterval(function () {
    idx = (idx + 1) % slides.length;

    el.style.opacity = 0;
    setTimeout(function () {
      el.style.backgroundImage = "url('" + slides[idx] + "')";
      el.style.opacity = 1;
    }, 400);
  }, 8000); // ganti tiap 8 detik
})();
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>