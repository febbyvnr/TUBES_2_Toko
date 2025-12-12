<?php
require_once __DIR__ . '/../config/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

ini_set('display_errors', 0);
error_reporting(0);

// ===== CORS =====
$FRONTEND_ORIGIN = "http://localhost/TUBES_2_Toko/frontend";
header('Content-Type: application/json; charset=UTF-8');
header("Access-Control-Allow-Origin: $FRONTEND_ORIGIN");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(204);
  exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['ok'=>false,'errors'=>['Method not allowed']]);
  exit;
}

$errors = [];

// ===== input =====
$username  = trim($_POST['username']  ?? '');
$email     = trim($_POST['email']     ?? '');
$password  = $_POST['password']       ?? '';
$password2 = $_POST['password2']      ?? '';
$phone     = trim($_POST['phone']     ?? '');
$address   = trim($_POST['address']   ?? '');

$digits = preg_replace('/\D+/', '', $phone);
$profileFile = null;

// ===== validation =====
// username
if ($username === '') {
  $errors[] = 'Username is required.';
} elseif (mb_strlen($username) < 3) {
  $errors[] = 'Username must be at least 3 characters.';
} else {
  $stmt = $mysqli->prepare("SELECT id FROM `user` WHERE username = ? LIMIT 1");
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
  $stmt = $mysqli->prepare("SELECT id FROM `user` WHERE email = ? LIMIT 1");
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
} elseif ($password !== $password2) {
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

// ===== profile photo (optional) =====
// error code: 0 OK, 4 NO FILE
if (isset($_FILES['profile_photo']) && (int)$_FILES['profile_photo']['error'] !== 4) {
  $f = $_FILES['profile_photo'];
  if ((int)$f['error'] === 0) {
    $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg','jpeg','png'])) {
      $errors[] = 'Only JPG and PNG images are allowed.';
    } elseif ($f['size'] > 64 * 1024 * 1024) {
      $errors[] = 'Maximum file size is 64MB.';
    } else {
      $profileFile = uniqid('pf_') . '.' . $ext;
      $destDir = __DIR__ . '/../assets/profile/';
      $dest = $destDir . $profileFile;
      if (!is_dir($destDir)) mkdir($destDir, 0755, true);
      if (!move_uploaded_file($f['tmp_name'], $dest)) {
        $errors[] = 'Failed to upload profile photo.';
        $profileFile = null;
      }
    }
  } else {
    $errors[] = 'Profile photo upload error.';
  }
}

if (!empty($errors)) {
  if ($profileFile) @unlink(__DIR__ . '/../assets/profile/' . $profileFile);
  http_response_code(422);
  echo json_encode(['ok'=>false,'errors'=>$errors]);
  exit;
}

// ===== save user =====
$hash = password_hash($password, PASSWORD_DEFAULT);
$role = 'user';
$activation_token = bin2hex(random_bytes(32));
$is_active = 0;

$stmt = $mysqli->prepare("
  INSERT INTO `user`
  (username, email, password, phone, address, profile_photo, role, activation_token, is_active)
  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
");
if (!$stmt) {
  if ($profileFile) @unlink(__DIR__ . '/../assets/profile/' . $profileFile);
  http_response_code(500);
  echo json_encode(['ok'=>false,'errors'=>['Database query failed.']]);
  exit;
}

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

if (!$stmt->execute()) {
  $stmt->close();
  if ($profileFile) @unlink(__DIR__ . '/../assets/profile/' . $profileFile);
  http_response_code(500);
  echo json_encode(['ok'=>false,'errors'=>['Failed to save to database.']]);
  exit;
}
$stmt->close();

// ===== activation link =====
$activationLink =
  "http://localhost/TUBES_2_Toko/backend/auth/activate.php?token=" .
  urlencode($activation_token);

// ===== email (optional) =====
$sent = false;
$errMailer = '';

$autoload = __DIR__ . '/../../vendor/autoload.php';
if (is_file($autoload)) {
  require $autoload;
  try {
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'YOUR_EMAIL@gmail.com';
    $mail->Password   = 'YOUR_APP_PASSWORD';
    $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    $mail->setFrom('YOUR_EMAIL@gmail.com', 'Feyora');
    $mail->addAddress($email, $username);
    $mail->Subject = 'Account Activation';
    $mail->Body    =
      "Hi $username,\n\nActivate your account:\n$activationLink\n\nRegards,\nFeyora Team";
    $mail->send();
    $sent = true;
  } catch (Throwable $e) {
    $errMailer = $e->getMessage();
  }
}

if ($sent) {
  echo json_encode([
    'ok' => true,
    'message' => 'Registration successful. Please check your email for account activation.'
  ]);
} else {
  echo json_encode([
    'ok' => true,
    'message' =>
      'Account created. Activation email could not be sent.<br>' .
      'Activate here:<br><br>' .
      '<a href="'.htmlspecialchars($activationLink).'" ' .
      'style="display:inline-block;background:#5b38ff;color:#fff;padding:10px 16px;border-radius:999px;text-decoration:none;">' .
      'Account Activation</a>'
  ]);
}