<?php
require_once __DIR__ . '/../config/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    // phone: at least 10 digits
    $digits = preg_replace('/\D+/', '', $phone);
    // handle upload (optional)
    $profileFile = null;

    // basic required fields
    if ($username === '') $errors[] = 'Username tidak boleh kosong';
    if ($username !== '' && mb_strlen($username) < 3) $errors[] = 'Username harus minimal 3 karakter.';
    if ($username !== '') {
        $stmt = $mysqli->prepare("SELECT id FROM user WHERE username = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param('s', $username);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) $errors[] = 'Username sudah terdaftar.';
            $stmt->close();
        }
    }

    if ($email === '') $errors[] = 'Email wajib diisi.';
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email tidak valid.';
    if ($email !== '') {
        $stmt = $mysqli->prepare("SELECT id FROM user WHERE email = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) $errors[] = 'Email sudah terdaftar.';
            $stmt->close();
        }
    }

    if ($password === '') $errors[] = 'Password wajib diisi.';
    if (mb_strlen($password) < 8) $errors[] = 'Password minimal 8 karakter.';
    if (!preg_match('/\d/', $password)) $errors[] = 'Password harus mengandung setidaknya satu angka.';
    if (!preg_match('/[^a-zA-Z0-9]/', $password)) $errors[] = 'Password harus mengandung setidaknya satu simbol (mis. !@#$%).';
    if ($password2 === '') $errors[] = 'Konfirmasi password wajib diisi.';
    if ($password !== '' && $password2 !== '') {
        if ($password !== $password2) $errors[] = 'Password dan konfirmasi tidak sama.';
    }

    //telponnum
    if ($phone === '') {
        $errors[] = 'Phone wajib diisi.';
    } else if (strlen($digits) < 10) {
        $errors[] = 'Phone harus minimal 10 digit.';
    }
    
    if ($address === '') $errors[] = 'Address tidak boleh kosong.';

    if (!empty($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] !== UPLOAD_ERR_NO_FILE) {
        $f = $_FILES['profile_photo'];
        if ($f['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg','jpeg','png'])) $errors[] = 'Hanya diperbolehkan gambar JPG/PNG.';
            else if ($f['size'] > 64 * 1024 * 1024) $errors[] = 'Ukuran file maksimal 64MB.';
            else {
                $profileFile = uniqid('pf_') . '.' . $ext;
                $dest = __DIR__ . '/../assets/profile/' . $profileFile;
                if (!is_dir(dirname($dest))) mkdir(dirname($dest), 0755, true);
                if (!move_uploaded_file($f['tmp_name'], $dest)) {
                    $errors[] = 'Gagal mengunggah foto profil.';
                    $profileFile = null;
                }
            }
        }
    }

    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $role = 'user';
        $stmt = $mysqli->prepare("INSERT INTO user (username, email, password, phone, address, profile_photo, role) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param('sssssss', $username, $email, $hash, $phone, $address, $profileFile, $role);
            if ($stmt->execute()) {
                $_SESSION['user_id'] = $stmt->insert_id;
                $_SESSION['username'] = $username;
                header('Location: /TUBES_2_Toko/index.php');
                exit;
            } else {
                $errors[] = 'Gagal menyimpan ke database: ' . $stmt->error;
            }
            $stmt->close();
        } else {
            $errors[] = 'Query database gagal: ' . $mysqli->error;
        }
    }else {
        // jika ada error, hapus file yang sudah diupload
        if ($profileFile) {
            @unlink(__DIR__ . '/../assets/profile/' . $profileFile);
        }
        echo '<div class="error">'.$errors[0].'</div>';
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Register — Aura</title>
    <link rel="stylesheet" href="../styles/Register.css">
</head>
<body>
<?php include __DIR__ . '/../includes/header.php'; ?>
<main class="container" style="padding:28px 20px;">
  <h2>Register</h2>
  <?php if (!empty($errors)): ?>
    <div class="muted" style="color:#b00;margin-bottom:12px;">
      <?php foreach ($errors as $e) echo '<div>' . htmlspecialchars($e) . '</div>'; ?>
    </div>
  <?php endif; ?>
  <form method="post" enctype="multipart/form-data" style="max-width:540px;">
    <label>Username<br><input name="username" value="<?=htmlspecialchars($_POST['username'] ?? '')?>"></label><br>
    <label>Email<br><input type="email" name="email" value="<?=htmlspecialchars($_POST['email'] ?? '')?>"></label><br>
    <label>Password<br><input type="password" name="password"></label><br>
    <label>Confirm Password<br><input type="password" name="password2"></label><br>
    <label>Phone<br><input name="phone" value="<?=htmlspecialchars($_POST['phone'] ?? '')?>"></label><br>
    <label>Address<br><textarea name="address"><?=htmlspecialchars($_POST['address'] ?? '')?></textarea></label><br>
    <label>Profile Photo (optional)<br><input type="file" name="profile_photo" accept="image/*"></label><br>
    <button class="btn-primary" type="submit">Register</button>
  </form>
</main>
</body>
</html>
