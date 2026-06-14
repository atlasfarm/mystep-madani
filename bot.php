<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

$token = '8826486535:AAHlvCTGJU2k3-SHaOb-LNAvKSGwrNe0Lnc';
$admin_chat_id = '8899767542';
$api = 'https://api.telegram.org/bot' . $token . '/';

$update = json_decode(file_get_contents('php://input'), true);

function telegramRequest($method, $params) {
    global $api;

    $url = $api . $method;
    $options = [
        'http' => [
            'header' => "Content-Type: application/json\r\n",
            'method' => 'POST',
            'content' => json_encode($params)
        ]
    ];

    return file_get_contents($url, false, stream_context_create($options));
}

function sendContactMenu($chatId) {
    telegramRequest('sendMessage', [
        'chat_id' => $chatId,
        'text' => 'Sila tekan butang di bawah untuk kongsi nombor telefon Telegram anda.',
        'reply_markup' => [
            'keyboard' => [
                [
                    [
                        'text' => 'Kongsi No Telefon',
                        'request_contact' => true
                    ]
                ]
            ],
            'resize_keyboard' => true,
            'one_time_keyboard' => false
        ]
    ]);
}

if (!isset($update['message'])) {
    echo json_encode(['ok' => true]);
    exit;
}

$message = $update['message'];
$chatId = $message['chat']['id'];
$text = $message['text'] ?? '';

if ($text === '/start' || $text === '/menu') {
    sendContactMenu($chatId);
    echo json_encode(['ok' => true]);
    exit;
}

if (isset($message['contact'])) {
    $contact = $message['contact'];
    $from = $message['from'] ?? [];

    $phone = $contact['phone_number'] ?? '-';
    $firstName = $contact['first_name'] ?? '';
    $lastName = $contact['last_name'] ?? '';
    $username = $from['username'] ?? '';
    $telegramId = $from['id'] ?? '';
    $name = trim($firstName . ' ' . $lastName);

    $adminText = "Contact Telegram Baru\n\n" .
        "Nama: $name\n" .
        "Telefon: $phone\n" .
        "Telegram ID: $telegramId\n" .
        "Username: " . ($username ? '@' . $username : '-');

    telegramRequest('sendMessage', [
        'chat_id' => $admin_chat_id,
        'text' => $adminText
    ]);

    telegramRequest('sendMessage', [
        'chat_id' => $chatId,
        'text' => 'Terima kasih. Nombor telefon anda telah diterima.',
        'reply_markup' => [
            'remove_keyboard' => true
        ]
    ]);

    echo json_encode(['ok' => true]);
    exit;
}

sendContactMenu($chatId);
echo json_encode(['ok' => true]);

?>
