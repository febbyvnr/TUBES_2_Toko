<?php
session_start();
header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['ids']) || empty($data['ids'])) {
    echo json_encode(["ok" => false]);
    exit;
}

$_SESSION['checkout_ids'] = array_fill_keys($data['ids'], true);

echo json_encode(["ok" => true]);
