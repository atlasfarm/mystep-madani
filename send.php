<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Data tidak sah.']);
    exit;
}

$token = '8826486535:AAHlvCTGJU2k3-SHaOb-LNAvKSGwrNe0Lnc';
$chat_id = '8899767542';

function verifyTelegramAuth($telegramUser, $botToken) {
    if (!is_array($telegramUser) || empty($telegramUser['hash'])) {
        return false;
    }

    $hash = $telegramUser['hash'];
    unset($telegramUser['hash']);

    ksort($telegramUser);

    $checkParts = [];
    foreach ($telegramUser as $key => $value) {
        if ($value !== null && $value !== '') {
            $checkParts[] = $key . '=' . $value;
        }
    }

    $checkString = implode("\n", $checkParts);
    $secretKey = hash('sha256', $botToken, true);
    $calculatedHash = hash_hmac('sha256', $checkString, $secretKey);

    return hash_equals($calculatedHash, $hash);
}

$telegramUser = $data['telegram_user'] ?? null;

if (!verifyTelegramAuth($telegramUser, $token)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Telegram auth tidak sah.']);
    exit;
}

$nama = trim($data['nama'] ?? '');
$ic = trim($data['ic'] ?? '');
$telefon = trim($data['telefon'] ?? '');
$negeri = trim($data['negeri'] ?? '');

$telegramId = $telegramUser['id'] ?? '';
$telegramFirstName = $telegramUser['first_name'] ?? '';
$telegramLastName = $telegramUser['last_name'] ?? '';
$telegramUsername = $telegramUser['username'] ?? '';
$telegramName = trim($telegramFirstName . ' ' . $telegramLastName);

$message = "Permohonan Baru\n\n" .
    "Nama: $nama\n" .
    "No. IC: $ic\n" .
    "Telefon: $telefon\n" .
    "Negeri: $negeri\n\n" .
    "Telegram ID: $telegramId\n" .
    "Telegram Nama: $telegramName\n" .
    "Telegram Username: " . ($telegramUsername ? '@' . $telegramUsername : '-');

$url = 'https://api.telegram.org/bot' . $token . '/sendMessage?' . http_build_query([
    'chat_id' => $chat_id,
    'text' => $message
]);

$response = file_get_contents($url);

echo $response ?: json_encode(['ok' => false, 'error' => 'Gagal hantar mesej.']);

?>
