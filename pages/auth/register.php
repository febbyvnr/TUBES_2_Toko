<?php
session_start();
require_once __DIR__ . '/../../config/db.php';

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';

    if ($name === '' || $email === '' || $password === '') {
        $errors[] = 'Semua field wajib diisi.';
    }
    if ($password !== $password2) {
        $errors[] = 'Password dan konfirmasi tidak sama.';
    }

    if (empty($errors)) {
        // Pastikan tabel users ada
        $createSql = "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(150) NOT NULL,
            email VARCHAR(255) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        $mysqli->query($createSql);

        // cek email unik
        $stmt = $mysqli->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->fetch_assoc()) {
            $errors[] = 'Email sudah terdaftar.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $ins = $mysqli->prepare('INSERT INTO users (name, email, password) VALUES (?, ?, ?)');
            $ins->bind_param('sss', $name, $email, $hash);
            if ($ins->execute()) {
                header('Location: login.php?registered=1');
                exit;
            } else {
                $errors[] = 'Gagal membuat akun. Coba lagi.';
            }
        }
    }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Register - Aura</title>
  <style>
    body{font-family:Inter, Arial, sans-serif;background:#faf7f8}
    .wrap{max-width:520px;margin:40px auto;background:#fff;padding:24px;border-radius:10px}
    label{display:block;margin-top:12px}
    input[type=text],input[type=password],input[type=email]{width:100%;padding:10px;border:1px solid #ddd;border-radius:6px}
    button{margin-top:16px;padding:10px 14px;background:#ff2d7a;color:#fff;border:none;border-radius:8px}
    .error{color:#c00}
  </style>
</head>
<body>
<?php include __DIR__ . '/../../components/header.php'; ?>
<div class="wrap">
  <h2>Register</h2>
  <?php if (!empty($errors)): ?>
    <div class="error"><?=htmlspecialchars(implode('<br>', $errors))?></div>
  <?php endif; ?>

  <form method="post" action="">
    <label for="name">Nama</label>
    <input type="text" id="name" name="name" value="<?=htmlspecialchars($_POST['name'] ?? '')?>" required>

    <label for="email">Email</label>
    <input type="email" id="email" name="email" value="<?=htmlspecialchars($_POST['email'] ?? '')?>" required>

    <label for="password">Password</label>
    <input type="password" id="password" name="password" required>

    <label for="password2">Konfirmasi Password</label>
    <input type="password" id="password2" name="password2" required>

    <button type="submit">Buat Akun</button>
  </form>
</div>
</body>
</html>
