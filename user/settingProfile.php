<?php
require_once __DIR__ . '/../config/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['user_id'])) {
    header('Location: /TUBES_2_Toko/auth/login.php'); exit;
}

$uid = (int) $_SESSION['user_id'];
$errors = [];

// load current
$stmt = $mysqli->prepare("SELECT username, email, phone, address, profile_photo FROM user WHERE id = ? LIMIT 1");
if ($stmt) {
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $res = $stmt->get_result();
    $me = $res->fetch_assoc();
    $stmt->close();
} else {
    die('DB error: ' . $mysqli->error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? $me['username']);
    $email = trim($_POST['email'] ?? $me['email']);
    $phone = trim($_POST['phone'] ?? $me['phone']);
    $address = trim($_POST['address'] ?? $me['address']);

    // handle profile photo
    $profileFile = $me['profile_photo'];
    if (!empty($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] !== UPLOAD_ERR_NO_FILE) {
        $f = $_FILES['profile_photo'];
        if ($f['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg','jpeg','png'])) $errors[] = 'Hanya gambar JPG/PNG.';
            elseif ($f['size'] > 2*1024*1024) $errors[] = 'Ukuran maksimal 2MB.';
            else {
                $profileFile = uniqid('pf_') . '.' . $ext;
                $dest = __DIR__ . '/../assets/profile/' . $profileFile;
                if (!is_dir(dirname($dest))) mkdir(dirname($dest), 0755, true);
                if (!move_uploaded_file($f['tmp_name'], $dest)) {
                    $errors[] = 'Gagal mengunggah foto.';
                } else {
                    // delete old file
                    if ($me['profile_photo']) {
                        @unlink(__DIR__ . '/../assets/profile/' . $me['profile_photo']);
                    }
                }
            }
        }
    }

    if (empty($errors)) {
        $stmt = $mysqli->prepare("UPDATE user SET username=?, email=?, phone=?, address=?, profile_photo=? WHERE id=?");
        if ($stmt) {
            $stmt->bind_param('sssssi', $username, $email, $phone, $address, $profileFile, $uid);
            if ($stmt->execute()) {
                $_SESSION['username'] = $username;
                header('Location: /TUBES_2_Toko/user/settingProfile.php'); exit;
            } else {
                $errors[] = 'Gagal menyimpan: ' . $stmt->error;
            }
            $stmt->close();
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Profile Settings — Aura</title>
    <link rel="stylesheet" href="../styles/SettingProfile.css">
</head>
<body>
<?php include __DIR__ . '/../includes/header.php'; ?>
<main class="container" style="padding:28px 20px;">
  <h2>Profile Settings</h2>
  <?php if (!empty($errors)): ?>
    <div class="muted" style="color:#b00;margin-bottom:12px;">
      <?php foreach ($errors as $e) echo '<div>' . htmlspecialchars($e) . '</div>'; ?>
    </div>
  <?php endif; ?>

  <form method="post" enctype="multipart/form-data" style="max-width:640px;">
    <label>Username<br><input name="username" value="<?=htmlspecialchars($me['username'])?>"></label><br>
    <label>Email<br><input name="email" value="<?=htmlspecialchars($me['email'])?>"></label><br>
    <label>Phone<br><input name="phone" value="<?=htmlspecialchars($me['phone'])?>"></label><br>
    <label>Address<br><textarea name="address"><?=htmlspecialchars($me['address'])?></textarea></label><br>
    <label>Profile Photo (replace)<br><input type="file" name="profile_photo" accept="image/*"></label><br>
    <button class="btn-primary" type="submit">Save</button>
  </form>
</main>
</body>
</html>
