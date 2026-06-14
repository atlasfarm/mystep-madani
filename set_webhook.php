<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

$token = '8826486535:AAHlvCTGJU2k3-SHaOb-LNAvKSGwrNe0Lnc';

$host = $_SERVER['HTTP_HOST'] ?? '';
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';

if ($host === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Host tidak dijumpai.']);
    exit;
}

$webhookUrl = $scheme . '://' . $host . '/bot.php';
$telegramUrl = 'https://api.telegram.org/bot' . $token . '/setWebhook?' . http_build_query([
    'url' => $webhookUrl
]);

$response = file_get_contents($telegramUrl);

echo $response ?: json_encode(['ok' => false, 'error' => 'Gagal set webhook.']);

?>
