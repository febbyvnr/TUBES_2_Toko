<?php
require_once __DIR__ . '/../config/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier'] ?? ''); // username or email
    $password = $_POST['password'] ?? '';
    if ($identifier === '' || $password === '') {
        $error = 'Isi username/email dan password.';
    } else {
        $stmt = $mysqli->prepare("SELECT id, username, password, profile_photo FROM user WHERE username = ? OR email = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param('ss', $identifier, $identifier);
            $stmt->execute();
            $res = $stmt->get_result();
            $user = $res->fetch_assoc();
            $stmt->close();
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                header('Location: /TUBES_2_Toko/index.php'); exit;
            } else {
                $error = 'Login gagal: username/email atau password salah.';
            }
        } else {
            $error = 'Query gagal: ' . $mysqli->error;
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Login — Aura</title>
  <link rel="stylesheet" href="../styles/Login.css">
</head>
<body>
<?php include __DIR__ . '/../includes/header.php'; ?>
<main class="container" style="padding:28px 20px;">
  <h2>Login</h2>
  <?php if ($error): ?>
    <div class="muted" style="color:#b00;margin-bottom:12px;"><?=htmlspecialchars($error)?></div>
  <?php endif; ?>
  <form method="post" style="max-width:420px;">
    <label>Username or Email<br><input name="identifier" value="<?=htmlspecialchars($_POST['identifier'] ?? '')?>"></label><br>
    <label>Password<br><input type="password" name="password"></label><br>
    <div style="margin:8px 0; color:#666;">Belum punya akun? <a href="register.php">Daftar</a></div>
    <button class="btn-primary" type="submit">Login</button>
  </form>
</main>
</body>
</html>
