<?php
session_start();

header('Content-Type: application/json');

$id = (int) ($_POST['cart_id'] ?? 0);
$checked = ($_POST['checked'] ?? '0') === '1';

$_SESSION['checkout_ids'] ??= [];

if ($checked) {
    $_SESSION['checkout_ids'][$id] = true;
} else {
    unset($_SESSION['checkout_ids'][$id]);
}

echo json_encode(['success' => true]);
