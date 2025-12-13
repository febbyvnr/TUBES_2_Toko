<?php
header('Content-Type: application/json; charset=utf-8');

$lat = $_GET['lat'] ?? null;
$lon = $_GET['lon'] ?? null;

if (!$lat || !$lon) {
  echo json_encode(["ok" => false, "message" => "Missing lat/lon"]);
  exit;
}

// BigDataCloud reverse geocode (FREE)
$url = "https://api.bigdatacloud.net/data/reverse-geocode-client"
     . "?latitude={$lat}&longitude={$lon}&localityLanguage=en";

$ch = curl_init($url);
curl_setopt_array($ch, [
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_TIMEOUT => 15,
]);

curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

$res = curl_exec($ch);
curl_close($ch);

$data = json_decode($res, true);

if (!$data || empty($data['locality'])) {
  echo json_encode(["ok" => false, "message" => "No address returned"]);
  exit;
}

// susun alamat
$address = implode(", ", array_filter([
  $data['locality'] ?? null,
  $data['city'] ?? null,
  $data['principalSubdivision'] ?? null,
  $data['countryName'] ?? null
]));

echo json_encode([
  "ok" => true,
  "data" => [
    "display_name" => $address
  ]
]);