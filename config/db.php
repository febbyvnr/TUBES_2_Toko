<?php
// db.php – koneksi MySQL dengan XAMPP

$DB_HOST = "localhost";   // default XAMPP
$DB_USER = "root";        // default XAMPP
$DB_PASS = "";            // default XAMPP (kosong)
$DB_NAME = "2_toko"; // sesuaikan dengan nama database kamu

$mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

// cek error
if ($mysqli->connect_error) {
    die("Koneksi database gagal: " . $mysqli->connect_error);
}

// pastikan charset UTF-8
$mysqli->set_charset("utf8mb4");
?>
