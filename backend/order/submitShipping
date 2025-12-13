<?php
session_start();

header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["ok" => false]);
    exit;
}

$_SESSION["shipping"] = [
    "email"     => $_POST["email"] ?? "",
    "firstname" => $_POST["firstname"] ?? "",
    "lastname"  => $_POST["lastname"] ?? "",
    "address"   => $_POST["address"] ?? "",
    "city"      => $_POST["city"] ?? "",
    "state"     => $_POST["state"] ?? "",
    "zip"       => $_POST["zip"] ?? ""
];

echo json_encode(["ok" => true]);
