<?php
session_start();
require_once "../config/db.php";

if (!isset($_POST['order_id']) || !isset($_POST['status'])) {
    die("Data tidak lengkap.");
}

$order_id = intval($_POST['order_id']);
$status   = $mysqli->real_escape_string($_POST['status']);

$mysqli->query("
    UPDATE orders 
    SET status = '$status'
    WHERE id = $order_id
");

header("Location: view.php?id=" . $order_id . "&update=success");
exit;
?>
