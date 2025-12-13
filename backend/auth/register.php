<?php
require_once __DIR__ . '/../config/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

require __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$errors = [];
$successMessage = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username  = trim($_POST['username']  ?? '');
    $email     = trim($_POST['email']     ?? '');
    $password  = $_POST['password']       ?? '';
    $password2 = $_POST['password2']      ?? '';
    $phone     = trim($_POST['phone']     ?? '');
    $address   = trim($_POST['address']   ?? '');

    $digits = preg_replace('/\D+/', '', $phone);
    $profileFile = null;

    // username
    if ($username === '') {
        $errors[] = 'Username is required.';
    } elseif (mb_strlen($username) < 3) {
        $errors[] = 'Username must be at least 3 characters.';
    } else {
        $stmt = $mysqli->prepare("SELECT id FROM user WHERE username = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param('s', $username);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) $errors[] = 'Username is already taken.';
            $stmt->close();
        }
    }

    // email
    if ($email === '') {
        $errors[] = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    } else {
        $stmt = $mysqli->prepare("SELECT id FROM user WHERE email = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) $errors[] = 'Email is already registered.';
            $stmt->close();
        }
    }

    // password
    if ($password === '') {
        $errors[] = 'Password is required.';
    } elseif (mb_strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    } elseif (!preg_match('/\d/', $password)) {
        $errors[] = 'Password must contain at least one number.';
    } elseif (!preg_match('/[^a-zA-Z0-9]/', $password)) {
        $errors[] = 'Password must contain at least one symbol (e.g. !@#$%).';
    }

    if ($password2 === '') {
        $errors[] = 'Please confirm your password.';
    } elseif ($password !== '' && $password2 !== '' && $password !== $password2) {
        $errors[] = 'Password and confirmation do not match.';
    }

    // phone
    if ($phone === '') {
        $errors[] = 'Phone number is required.';
    } elseif (strlen($digits) < 10) {
        $errors[] = 'Phone number must be at least 10 digits.';
    }

    // address
    if ($address === '') {
        $errors[] = 'Address is required.';
    }

    // profile photo (optional)
    if (!empty($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] !== UPLOAD_ERR_NO_FILE) {
        $f = $_FILES['profile_photo'];
        if ($f['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg','jpeg','png'])) {
                $errors[] = 'Only JPG and PNG images are allowed.';
            } elseif ($f['size'] > 64 * 1024 * 1024) {
                $errors[] = 'Maximum file size is 64MB.';
            } else {
                $profileFile = uniqid('pf_') . '.' . $ext;
                $dest = __DIR__ . '/../assets/profile/' . $profileFile;
                if (!is_dir(dirname($dest))) mkdir(dirname($dest), 0755, true);
                if (!move_uploaded_file($f['tmp_name'], $dest)) {
                    $errors[] = 'Failed to upload profile photo.';
                    $profileFile = null;
                }
            }
        } else {
            $errors[] = 'Profile photo upload error.';
        }
    }

    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $role = 'user';

        // === AKTIVASI ===
        $activation_token = bin2hex(random_bytes(32)); // token random
        $is_active = 0;

        $stmt = $mysqli->prepare("
            INSERT INTO user (username, email, password, phone, address, profile_photo, role, activation_token, is_active)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        if ($stmt) {
            $stmt->bind_param(
                'ssssssssi',
                $username,
                $email,
                $hash,
                $phone,
                $address,
                $profileFile,
                $role,
                $activation_token,
                $is_active
            );

            if ($stmt->execute()) {
                // === GBS LOGIN, EMAIL AKTIVASI DL ===

                // base URL sesuai project
                $baseUrl = '/TUBES_2_Toko/frontend/auth';
                $activationLink = $baseUrl . '/activate.php?token=' . urlencode($activation_token) . "&return=register";

                $subject = 'Account Activation';
                $message = "Hi $username,\n\n"
                    . "Thank you for joining us at Feyora!\n"
                    . "To activate your account, please click the link below : \n\n"
                    . $activationLink . "\n\n"
                    . "If you don't feel like signing up for Feyora, please ignore this email.\n\n"
                    . "Regards,\nFeyora Team";

                // alamat  pengirim
                $headers  = "From: Feyora <no-reply@feyora.test>\r\n";
                $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
                $mail = new PHPMailer(true);

                try {
                    // Server settings ==== PAS HOSTING INI DIUBAH ====
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.gmail.com';      // ganti kl pk SMTP lain
                    $mail->SMTPAuth   = true;
                    $mail->Username   = 'febiann819@gmail.com';
                    $mail->Password   = 'mlfdppxgjrxjhtob';      // App Password Gmail / password SMTP
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port       = 587;

                    // Pengirim & penerima
                    $mail->setFrom('febiann819@gmail.com', 'Feyora'); // pengirim
                    $mail->addAddress($email, $username);            // penerima (user)

                    // Konten
                    $mail->Subject = $subject;
                    $mail->Body    = $message;
                    $mail->AltBody = $message;

                    $mail->send();

                    // kalau sukses kirim email
                    $successMessage = 'Registration successful. Please check your email for account activation.';
                    $_POST = [];

                } catch (Exception $e) {
                    // kalau gagal kirim email, tetap buat akun & kasih link manual
                    $successMessage = '
                        The account was successfully created, but the activation email could not be sent.
                        Please activate your account by clicking the link below:
                        <div style="margin-top:12px;">
                            <a href="' . $activationLink . '" 
                                class="btn-primary" 
                                style="display:inline-block; background:#DEBB3; padding:10px 20px; border-radius:999px; text-decoration:none; color:#fff;">
                                Account Activation
                            </a>
                        </div>
                        <div style="margin-top:8px; font-size:12px; color:#e4007f;">
                            (Error mailer: ' . htmlspecialchars($mail->ErrorInfo) . ')
                        </div>
                    ';
                    $_POST = [];
                }
            } else {
                $errors[] = 'Failed to save to database: ' . $stmt->error;
            }
            $stmt->close();
        } else {
            $errors[] = 'Database query failed: ' . $mysqli->error;
        }
    } else {
        // error, delete uploaded file
        if ($profileFile) {
            @unlink(__DIR__ . '/../assets/profile/' . $profileFile);
        }
    }
}

/* --- slideshow images (kyk login) --- */
$slides = [];
if ($res = $mysqli->query("
    SELECT image
    FROM products
    WHERE image IS NOT NULL AND image <> ''
    ORDER BY added DESC
    LIMIT 20
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
      <!-- Google Fonts -->
      <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=Poppins:wght@400;600&display=swap" rel="stylesheet">
      <title>FEYORA - Register</title>
      <!-- Bootstrap Icons -->
      <link rel="stylesheet"
            href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
      <!-- CSS Utama -->
      <link rel="stylesheet" href="../../frontend/styles/HomePage.css">
      <link rel="stylesheet" href="../../frontend/styles/header.css">
      <link rel="stylesheet" href="../../frontend/styles/Register.css">
</head>
<body>
<div id="header"></div>
<main class="auth-layout">
    <!-- LEFT: REGISTER FORM -->
    <section class="auth-form">
        <div class="auth-form-inner">

        <div style="text-align:center; margin-bottom:18px;">
            <img src="/TUBES_2_Toko/assets/Logo Feyora.png"
                alt="Feyora Logo"
                style="width:150px; height:auto; opacity:0.9;">
        </div>

        <?php if (!empty($errors)): ?>
            <div class="auth-error">
            <?php foreach ($errors as $e): ?>
                <div><?= htmlspecialchars($e) ?></div>
            <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($successMessage)): ?>
            <div class="auth-success">
                <?= $successMessage ?>
            </div>
        <?php endif; ?>

        <?php if (empty($successMessage)):?>
        <form method="post" enctype="multipart/form-data" class="auth-form-fields">

            <!-- username + email -->
            <div class="form-row">
            <label class="auth-label">
                Username
                <input
                class="auth-input"
                name="username"
                value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                autocomplete="username">
            </label>

            <label class="auth-label">
                Email
                <input
                class="auth-input"
                type="email"
                name="email"
                value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                autocomplete="email">
            </label>
            </div>

            <!-- password + confirm -->
            <div class="form-row">
            <label class="auth-label">
                Password
                <input
                class="auth-input"
                type="password"
                name="password"
                autocomplete="new-password">
            </label>

            <label class="auth-label">
                Confirm Password
                <input
                class="auth-input"
                type="password"
                name="password2"
                autocomplete="new-password">
            </label>
            </div>

            <!-- phone + profile photo -->
            <div class="form-row">
            <label class="auth-label">
                Phone
                <input
                class="auth-input"
                name="phone"
                value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>"
                autocomplete="tel">
            </label>

            <label class="auth-label">
                Profile Photo (optional)
                <input
                class="auth-input"
                type="file"
                name="profile_photo"
                accept="image/*">
            </label>
            </div>

            <!-- Address full -->
            <label class="auth-label">
            Address
            <textarea
                class="auth-input auth-textarea"
                name="address"
                rows="3"><?= htmlspecialchars($_POST['address'] ?? '') ?></textarea>
            </label>

            <div class="auth-meta">
            <span>Already have an account? <a href="/TUBES_2_Toko/frontend/auth/login.html">Sign in</a></span>
            </div>

            <button class="btn-primary auth-submit" type="submit">Register</button>
        </form>
        <?php endif; ?>

        </div>
    </section>
    <!-- RIGHT: SLIDESHOW -->
    <section class="auth-visual">
        <div id="register-hero"
            class="auth-visual-image"
            style="background-image:url('<?= htmlspecialchars($slides[0] ?? "/TUBES_2_Toko/assets/products/placeholder.png") ?>');">
        </div>
        <div class="auth-visual-overlay">
        <h1>Create Your Account</h1>
        <p>Join Feyora to save your favorite looks, track orders, and enjoy a more personal shopping experience.</p>
        </div>
    </section>
</main>
<div id="footer"></div>
<script>
(function () {
  const slides = <?= $slidesJson ?: '[]' ?>;
  const el = document.getElementById('register-hero');
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
<script>
    async function loadPart(id, url) {
        const el = document.getElementById(id);
        if (!el) return;

        const res = await fetch(url, { cache: "no-cache" });
        const html = await res.text();
        el.innerHTML = html;

        // paksa execute script dari include
        el.querySelectorAll("script").forEach(oldScript => {
        const s = document.createElement("script");
        if (oldScript.src) s.src = oldScript.src;
        s.textContent = oldScript.textContent;
        document.body.appendChild(s);
        oldScript.remove();
        });

        // setelah header masuk + scriptnya sudah dieksekusi
        if (id === "header" && window.initHeaderAuth) {
        await window.initHeaderAuth();
        }
    }

    (async function () {
        await loadPart("header", "/TUBES_2_Toko/frontend/includes/header.html");
        await loadPart("footer", "/TUBES_2_Toko/frontend/includes/footer.html");
    })();
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>