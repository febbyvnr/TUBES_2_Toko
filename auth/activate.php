<?php
require_once __DIR__ . '/../config/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$status  = 'error';   // success | error | info
$message = '';

// ambil token dari URL
$token = $_GET['token'] ?? '';
$token = trim($token);

if ($token === '') {
    $message = 'Token aktivasi tidak ditemukan. Silakan cek kembali link pada email Anda.';
} else {
    // cari user berdasarkan token
    $stmt = $mysqli->prepare("
        SELECT id, is_active
        FROM user
        WHERE activation_token = ?
        LIMIT 1
    ");
    if ($stmt) {
        $stmt->bind_param('s', $token);
        $stmt->execute();
        $res  = $stmt->get_result();
        $user = $res->fetch_assoc();
        $stmt->close();

        if (!$user) {
            $status  = 'error';
            $message = 'Token aktivasi tidak valid atau akun sudah diaktivasi.';
        } else {
            if ((int)$user['is_active'] === 1) {
                // sudah aktif
                $status  = 'info';
                $message = 'Akun Anda sudah aktif. Silakan login.';
            } else {
                // update jadi aktif
                $stmt = $mysqli->prepare("
                    UPDATE user
                    SET is_active = 1, activation_token = NULL
                    WHERE id = ?
                ");
                if ($stmt) {
                    $stmt->bind_param('i', $user['id']);
                    if ($stmt->execute()) {
                        $status  = 'success';
                        $message = 'Akun Anda berhasil diaktivasi. Silakan login.';
                    } else {
                        $status  = 'error';
                        $message = 'Terjadi kesalahan saat mengaktivasi akun. Silakan coba lagi.';
                    }
                    $stmt->close();
                } else {
                    $status  = 'error';
                    $message = 'Query gagal: ' . $mysqli->error;
                }
            }
        }
    } else {
        $message = 'Query gagal: ' . $mysqli->error;
    }
}

/* --- slideshow images (sama seperti login/register) --- */
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
  <title>Account Activation — Feyora</title>
  <!-- pakai CSS register (sudah ada auth-layout, auth-success, auth-error, dll) -->
  <link rel="stylesheet" href="../styles/Register.css?v=<?= time() ?>">
</head>
<body>
<?php include __DIR__ . '/../includes/header.php'; ?>

<main class="auth-layout">
  <!-- KIRI: SLIDESHOW -->
  <section class="auth-visual">
    <div id="activate-hero"
      class="auth-visual-image"
      style="background-image:url('<?= htmlspecialchars($slides[0] ?? "/TUBES_2_Toko/assets/products/placeholder.png") ?>');">
    </div>

    <div class="auth-visual-overlay">
      <h1>Activate Your Account</h1>
      <p>Thank you for joining Feyora. Finish your activation to start shopping with a more personal experience.</p>
    </div>
  </section>

  <!-- KANAN: INFO AKTIVASI -->
  <section class="auth-form">
    <div class="auth-form-inner">
      <div style="text-align:center; margin-bottom:18px;">
        <img src="/TUBES_2_Toko/assets/Logo Feyora.png"
             alt="Feyora Logo"
             style="width:150px; height:auto; opacity:0.9;">
      </div>

      <h2 class="auth-title">Account Activation</h2>

      <?php if ($status === 'success'): ?>
        <div class="auth-success">
          <?= htmlspecialchars($message) ?>
        </div>
        <a href="login.php" class="btn-primary auth-submit" style="display:block; text-align:center; text-decoration:none;">
          Go to Login
        </a>

      <?php elseif ($status === 'info'): ?>
        <div class="auth-success">
          <?= htmlspecialchars($message) ?>
        </div>
        <a href="login.php" class="btn-primary auth-submit" style="display:block; text-align:center; text-decoration:none;">
          Go to Login
        </a>

      <?php else: ?>
        <div class="auth-error">
          <?= htmlspecialchars($message) ?>
        </div>
        <div class="auth-meta" style="margin-top:10px;">
          <span>
            Jika perlu, Anda bisa <a href="login.php">kembali ke halaman login</a>
            atau <a href="register.php">buat akun baru</a>.
          </span>
        </div>
      <?php endif; ?>
    </div>
  </section>
</main>

<script>
(function () {
  const slides = <?= $slidesJson ?: '[]' ?>;
  const el = document.getElementById('activate-hero');
  if (!el || !slides || slides.length <= 1) return;

  let idx = 0;
  setInterval(function () {
    idx = (idx + 1) % slides.length;
    el.style.opacity = 0;
    setTimeout(function () {
      el.style.backgroundImage = "url('" + slides[idx] + "')";
      el.style.opacity = 1;
    }, 400);
  }, 8000);
})();
</script>
</body>
</html>
