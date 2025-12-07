<?php
require_once __DIR__ . '/../config/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['user_id'])) {
    header('Location: /TUBES_2_Toko/auth/login.php'); exit;
}

$uid          = (int) $_SESSION['user_id'];
$errors       = [];   // error untuk update profile
$deleteError  = '';   // error khusus delete akun

// ambil data user (sekaligus password hash untuk verifikasi delete)
$stmt = $mysqli->prepare("SELECT username, email, phone, address, profile_photo, password FROM user WHERE id = ? LIMIT 1");
if ($stmt) {
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $res = $stmt->get_result();
    $me  = $res->fetch_assoc();
    $stmt->close();
} else {
    die('DB error: ' . $mysqli->error);
}

if (!$me) {
    // user tidak ditemukan (aneh tapi jaga-jaga)
    session_destroy();
    header('Location: /TUBES_2_Toko/index.php');
    exit;
}

$action = $_POST['action'] ?? 'update';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ==========================
    //  AKSI : HAPUS AKUN
    // ==========================
    if ($action === 'delete') {
        $password = $_POST['confirm_password'] ?? '';

        if ($password === '') {
            $deleteError = 'Password wajib diisi untuk konfirmasi penghapusan akun.';
        } else {
            // verifikasi password
            if (!password_verify($password, $me['password'])) {
                $deleteError = 'Password did not match. Cancel delete account!';
            } else {
                // hapus file foto profil kalau ada
                if (!empty($me['profile_photo'])) {
                    $oldPath = __DIR__ . '/../assets/profile/' . $me['profile_photo'];
                    if (is_file($oldPath)) {
                        @unlink($oldPath);
                    }
                }

                // hapus user dari database
                $delStmt = $mysqli->prepare("DELETE FROM user WHERE id = ? LIMIT 1");
                if ($delStmt) {
                    $delStmt->bind_param('i', $uid);
                    if ($delStmt->execute()) {
                      $delStmt->close();

                      $_SESSION['flash_success'] = 'Your account has been successfully deleted. Thank you for being with Feyora.';

                      unset($_SESSION['user_id'], $_SESSION['username']);

                      header('Location: /TUBES_2_Toko/index.php');
                      exit;
                  } else {
                        $deleteError = 'Gagal menghapus akun: ' . $delStmt->error;
                        $delStmt->close();
                    }
                } else {
                    $deleteError = 'Gagal mempersiapkan penghapusan akun.';
                }
            }
        }
    }

    if ($action === 'update') {
        $username = trim($_POST['username'] ?? $me['username']);
        $email    = trim($_POST['email'] ?? $me['email']);
        $phone    = trim($_POST['phone'] ?? $me['phone']);
        $address  = trim($_POST['address'] ?? $me['address']);

        $profileFile = $me['profile_photo'];

        // upload foto profil
        if (!empty($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] !== UPLOAD_ERR_NO_FILE) {
            $f = $_FILES['profile_photo'];
            if ($f['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
                if (!in_array($ext, ['jpg','jpeg','png'])) {
                    $errors[] = 'Hanya gambar JPG/PNG.';
                } else if ($f['size'] > 64*1024*1024) { // 64 MB
                    $errors[] = 'Ukuran maksimal 64MB.';
                } else {
                    $profileFile = uniqid('pf_') . '.' . $ext;
                    $dest = __DIR__ . '/../assets/profile/' . $profileFile;
                    if (!is_dir(dirname($dest))) mkdir(dirname($dest), 0755, true);
                    if (!move_uploaded_file($f['tmp_name'], $dest)) {
                        $errors[] = 'Gagal mengunggah foto.';
                    } else {
                        // hapus foto lama
                        if (!empty($me['profile_photo'])) {
                            $oldPath = __DIR__ . '/../assets/profile/' . $me['profile_photo'];
                            if (is_file($oldPath)) {
                                @unlink($oldPath);
                            }
                        }
                    }
                }
            } else {
                $errors[] = 'Upload error.';
            }
        }

        if (empty($errors)) {
            $stmt = $mysqli->prepare("UPDATE user SET username=?, email=?, phone=?, address=?, profile_photo=? WHERE id=?");
            if ($stmt) {
                $stmt->bind_param('sssssi', $username, $email, $phone, $address, $profileFile, $uid);
                if ($stmt->execute()) {
                    $_SESSION['username']      = $username;
                    $_SESSION['flash_success'] = 'Profil berhasil diperbarui.';
                    header('Location: /TUBES_2_Toko/index.php');
                    exit;
                } else {
                    $errors[] = 'Gagal menyimpan: ' . $stmt->error;
                }
                $stmt->close();
            } else {
                $errors[] = 'Gagal mempersiapkan query update.';
            }
        }

        $me['username']      = $username;
        $me['email']         = $email;
        $me['phone']         = $phone;
        $me['address']       = $address;
        $me['profile_photo'] = $profileFile;
    }
}

//  PP & Initials
$usernameSafe = trim((string)($me['username'] ?? ''));

if ($usernameSafe === '') {
    $initials = 'US';
} else {
    if (function_exists('mb_substr')) {
        $initials = strtoupper(mb_substr($usernameSafe, 0, 2));
    } else {
        $initials = strtoupper(substr($usernameSafe, 0, 2));
    }
}

$avatarUrl    = null;
$avatarFsPath = null;

if (!empty($me['profile_photo'])) {
    $avatarUrl    = '/TUBES_2_Toko/assets/profile/' . $me['profile_photo'];          // untuk browser
    $avatarFsPath = __DIR__ . '/../assets/profile/' . $me['profile_photo'];          // untuk file_exists
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Profile Settings — Feyora</title>
    <link rel="stylesheet" href="../styles/SettingProfile.css">
</head>
<body>
<?php include __DIR__ . '/../includes/header.php'; ?>

<main class="container" style="padding:28px 20px; max-width:800px;">
  <h2 style="margin-bottom:16px;">Profile Settings</h2>

  <!-- avatar bulat di tengah -->
  <div style="display:flex; justify-content:center; margin-bottom:20px;">
    <div class="avatar-wrapper"
         style="
            width:96px;
            height:96px;
            border-radius:50%;
            overflow:hidden;
            border:2px solid #ff2d7a;
            display:flex;
            align-items:center;
            justify-content:center;
            background:#f5f5f5;
            font-weight:600;
            font-size:32px;
            color:#555;">
      <?php if ($avatarUrl && file_exists($avatarFsPath)): ?>
        <img id="avatarPreview"
             src="<?= htmlspecialchars($avatarUrl) ?>"
             alt="Profile photo"
             style="width:100%; height:100%; object-fit:cover;">
      <?php else: ?>
        <span id="avatarFallback"><?= htmlspecialchars($initials) ?></span>
        <img id="avatarPreview"
             src=""
             alt="Profile photo"
             style="display:none; width:100%; height:100%; object-fit:cover;">
      <?php endif; ?>
    </div>
  </div>

  <?php if (!empty($errors)): ?>
    <div class="muted" style="color:#b00;margin-bottom:12px;">
      <?php foreach ($errors as $e) echo '<div>' . htmlspecialchars($e) . '</div>'; ?>
    </div>
  <?php endif; ?>

  <?php if ($deleteError): ?>
    <div class="muted" style="color:#b00;margin-bottom:12px;">
      <?= htmlspecialchars($deleteError) ?>
    </div>
  <?php endif; ?>

  <!-- FORM UPDATE PROFIL -->
  <form method="post" enctype="multipart/form-data" style="max-width:640px; margin:0 auto 12px;">
    <input type="hidden" name="action" value="update">

    <label>Username<br>
      <input name="username" value="<?=htmlspecialchars($me['username'])?>">
    </label><br>

    <label>Email<br>
      <input name="email" value="<?=htmlspecialchars($me['email'])?>">
    </label><br>

    <label>Phone<br>
      <input name="phone" value="<?=htmlspecialchars($me['phone'])?>">
    </label><br>

    <label>Address<br>
      <textarea name="address"><?=htmlspecialchars($me['address'])?></textarea>
    </label><br>

    <label>Profile Photo<br>
      <input type="file" name="profile_photo" accept="image/*" id="profileFileInput">
      <small style="color:#666;">Format: JPG/PNG, maks 64MB.</small>
    </label><br>

    <div style="display:flex; justify-content:space-between; gap:12px; align-items:center; margin-top:12px;">
      <button class="btn-primary" type="submit">Save</button>

      <!-- TOMBOL DELETE PROFILE -->
      <button type="button"
              id="btnDeleteAccount"
              style="
                padding:8px 14px;
                border-radius:6px;
                border:1px solid #e02424;
                background:#fff5f5;
                color:#b00000;
                cursor:pointer;
                font-size:13px;">
        Delete Profile
      </button>
    </div>
  </form>
</main>

<!-- MODAL KONFIRMASI DELETE -->
<div id="delete-modal"
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
        max-width:360px;
        width:90%;
        box-shadow:0 8px 30px rgba(0,0,0,0.25);">
    <h3 style="margin-top:0; margin-bottom:8px; color:#222;">Hapus Akun</h3>
    <p style="margin:0 0 10px; color:#555; font-size:14px;">
      Tindakan ini <strong>tidak dapat dibatalkan</strong>.<br>
      Masukkan password akun Anda untuk konfirmasi.
    </p>

    <form method="post" style="margin-top:10px;">
      <input type="hidden" name="action" value="delete">
      <label style="font-size:14px; color:#333;">
        Password<br>
        <input type="password" name="confirm_password" style="width:100%; padding:6px 8px; margin-top:4px;">
      </label>

      <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:14px;">
        <button type="button"
                id="btnCancelDelete"
                style="
                    padding:8px 14px;
                    border-radius:6px;
                    border:1px solid #ccc;
                    background:#f5f5f5;
                    cursor:pointer;
                    font-size:13px;">
          Batal
        </button>
        <button type="submit"
                style="
                    padding:8px 14px;
                    border-radius:6px;
                    border:none;
                    background:#e02424;
                    color:#fff;
                    cursor:pointer;
                    font-size:13px;
                    font-weight:600;">
          Hapus Akun
        </button>
      </div>
    </form>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  // Preview foto profil di avatar
  var input = document.getElementById('profileFileInput');
  if (input) {
    input.addEventListener('change', function (e) {
      var file = e.target.files[0];
      if (!file) return;

      var reader = new FileReader();
      reader.onload = function (ev) {
        var img      = document.getElementById('avatarPreview');
        var fallback = document.getElementById('avatarFallback');
        if (img) {
          img.src = ev.target.result;
          img.style.display = 'block';
        }
        if (fallback) fallback.style.display = 'none';
      };
      reader.readAsDataURL(file);
    });
  }

  // Modal delete account
  var btnDelete  = document.getElementById('btnDeleteAccount');
  var modal      = document.getElementById('delete-modal');
  var btnCancel  = document.getElementById('btnCancelDelete');

  if (btnDelete && modal) {
    btnDelete.addEventListener('click', function () {
      modal.style.display = 'flex';
    });
  }

  if (btnCancel && modal) {
    btnCancel.addEventListener('click', function () {
      modal.style.display = 'none';
    });
  }

  if (modal) {
    modal.addEventListener('click', function (e) {
      if (e.target === modal) {
        modal.style.display = 'none';
      }
    });
  }
});
</script>
</body>
</html>