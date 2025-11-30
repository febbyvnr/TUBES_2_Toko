<?php
session_start();
require_once __DIR__ . '/../../config/db.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Email dan password wajib diisi.';
    } else {
        $stmt = $mysqli->prepare('SELECT id, name, email, password FROM users WHERE email = ? LIMIT 1');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            if (password_verify($password, $row['password'])) {
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['user_name'] = $row['name'];
                header('Location: /index.php');
                exit;
            } else {
                $error = 'Kredensial salah.';
            }
        } else {
            $error = 'Kredensial salah.';
        }
    }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Login - Aura</title>
  <style>
    body{font-family:Inter, Arial, sans-serif;background:#faf7f8}
    .wrap{max-width:420px;margin:48px auto;background:#fff;padding:24px;border-radius:10px}
    label{display:block;margin-top:12px}
    input[type=text],input[type=password],input[type=email]{width:100%;padding:10px;border:1px solid #ddd;border-radius:6px}
    button{margin-top:16px;padding:10px 14px;background:#ff2d7a;color:#fff;border:none;border-radius:8px}
    .small{font-size:14px;color:#666;margin-top:8px}
    .register-note{font-size:14px;color:#333;margin-bottom:8px}
    .error{color:#c00;margin-top:8px}
  </style>
</head>
<body>
<?php include __DIR__ . '/../../components/header.php'; ?>
<div class="wrap">
  <h2>Login</h2>
  <p class="register-note">Belum punya akun? <a href="register.php">Register account</a></p>
  <?php if (!empty($_GET['registered'])): ?>
    <div style="color:green;">Registrasi berhasil. Silakan login.</div>
  <?php endif; ?>
  <?php if ($error): ?><div class="error"><?=htmlspecialchars($error)?></div><?php endif; ?>

  <form method="post" action="">
    <label for="email">Email</label>
    <input type="email" id="email" name="email" required>

    <label for="password">Password</label>
    <input type="password" id="password" name="password" required>

    <button type="submit">Login</button>
  </form>
</div>
</body>
</html>
