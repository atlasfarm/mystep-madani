<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

$data = json_decode(file_get_contents("php://input"), true);

$nama = $data["nama"];
$ic = $data["ic"];
$telefon = $data["telefon"];
$negeri = $data["negeri"];

$token = "8826486535:AAHlvCTGJU2k3-SHaOb-LNAvKSGwrNe0Lnc";
$chat_id = "8899767542";

$message = "Permohonan Baru

Nama: $nama
No. IC: $ic
Telefon: $telefon
Negeri: $negeri";

$url = "https://api.telegram.org/bot$token/sendMessage?" . http_build_query([
    "chat_id" => $chat_id,
    "text" => $message
]);

$response = file_get_contents($url);

echo $response;
?>