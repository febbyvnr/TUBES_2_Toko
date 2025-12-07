<?php
require_once __DIR__ . '/../config/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$errors = [];

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
        $stmt = $mysqli->prepare("
            INSERT INTO user (username, email, password, phone, address, profile_photo, role)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        if ($stmt) {
            $stmt->bind_param('sssssss', $username, $email, $hash, $phone, $address, $profileFile, $role);
            if ($stmt->execute()) {
                $_SESSION['user_id']  = $stmt->insert_id;
                $_SESSION['username'] = $username;
                header('Location: /TUBES_2_Toko/index.php');
                exit;
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
  <title>Register — Feyora</title>
  <link rel="stylesheet" href="../styles/Register.css?v=<?=time()?>">
</head>
<body>
<?php include __DIR__ . '/../includes/header.php'; ?>

<main class="auth-layout">
    <!-- LEFT: REGISTER FORM -->
    <section class="auth-form">
        <div class="auth-form-inner">

        <div style="text-align:center; margin-bottom:18px;">
            <img src="/TUBES_2_Toko/assets/Logo Feyora.png"
                alt="Feyora Logo"
                style="width:150px; height:auto; opacity:0.9;">
        </div>

        <h2 class="auth-title">Create Account</h2>

        <?php if (!empty($errors)): ?>
            <div class="auth-error">
            <?php foreach ($errors as $e): ?>
                <div><?= htmlspecialchars($e) ?></div>
            <?php endforeach; ?>
            </div>
        <?php endif; ?>

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
            <span>Already have an account? <a href="login.php">Sign in</a></span>
            </div>

            <button class="btn-primary auth-submit" type="submit">Register</button>
        </form>
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
</body>
</html>
