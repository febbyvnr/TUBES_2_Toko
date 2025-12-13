<?php
session_start();
require_once __DIR__ . '/../config/db.php';

$id = (int) $_GET['id'];

$stmt = $mysqli->prepare("DELETE FROM cart WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->close();

if (isset($_SESSION['checkout'][$id])) {
    unset($_SESSION['checkout'][$id]);
}

header("Location: listCart.php");
exit;
