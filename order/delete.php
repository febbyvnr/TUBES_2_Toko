<?php
session_start();
require_once "../config/db.php";

if (!isset($_GET['id'])) {
    die("Order tidak ditemukan.");
}

$order_id = intval($_GET['id']);

// hapus items 
$mysqli->query("DELETE FROM order_items WHERE order_id = $order_id");

// hapus order utama
$mysqli->query("DELETE FROM orders WHERE id = $order_id");

header("Location: listOrder.php?delete=success");
exit;
?>
