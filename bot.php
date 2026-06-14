<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

$token = '8826486535:AAHlvCTGJU2k3-SHaOb-LNAvKSGwrNe0Lnc';
$admin_chat_id = '8899767542';
$api = 'https://api.telegram.org/bot' . $token . '/';
$contactsFile = __DIR__ . '/contacts.json';
$settingsFile = __DIR__ . '/settings.json';

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

function readJsonFile($file, $default) {
    if (!file_exists($file)) {
        return $default;
    }

    $data = json_decode(file_get_contents($file), true);
    return is_array($data) ? $data : $default;
}

function writeJsonFile($file, $data) {
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
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

function sendAdminMenu($chatId) {
    telegramRequest('sendMessage', [
        'chat_id' => $chatId,
        'text' => "Menu owner bot.\n\nUntuk set link group, hantar:\n/setgroup https://t.me/+linkgroup",
        'reply_markup' => [
            'keyboard' => [
                [
                    ['text' => 'Senarai Contact'],
                    ['text' => 'Hantar Link Group']
                ],
                [
                    ['text' => 'Tunggu Contact User']
                ]
            ],
            'resize_keyboard' => true,
            'one_time_keyboard' => false
        ]
    ]);
}

function sendAdminContactDetail($chatId, $contact) {
    $name = trim(($contact['first_name'] ?? '') . ' ' . ($contact['last_name'] ?? ''));
    $username = $contact['username'] ? '@' . $contact['username'] : '-';

    $adminText = "Contact Telegram Baru\n\n" .
        "Nama: $name\n" .
        "Telefon: {$contact['phone']}\n" .
        "Telegram ID: {$contact['telegram_id']}\n" .
        "Username: $username\n\n" .
        "Menu owner tersedia di bawah.";

    telegramRequest('sendMessage', [
        'chat_id' => $chatId,
        'text' => $adminText,
        'reply_markup' => [
            'keyboard' => [
                [
                    ['text' => 'Senarai Contact'],
                    ['text' => 'Hantar Link Group']
                ],
                [
                    ['text' => 'Tunggu Contact User']
                ]
            ],
            'resize_keyboard' => true,
            'one_time_keyboard' => false
        ]
    ]);
}

function saveContact($message) {
    global $contactsFile;

    $contact = $message['contact'];
    $from = $message['from'] ?? [];
    $telegramId = (string) ($from['id'] ?? $message['chat']['id']);

    $contacts = readJsonFile($contactsFile, []);
    $contacts[$telegramId] = [
        'chat_id' => $message['chat']['id'],
        'telegram_id' => $telegramId,
        'phone' => $contact['phone_number'] ?? '-',
        'first_name' => $contact['first_name'] ?? ($from['first_name'] ?? ''),
        'last_name' => $contact['last_name'] ?? ($from['last_name'] ?? ''),
        'username' => $from['username'] ?? '',
        'saved_at' => date('c')
    ];

    writeJsonFile($contactsFile, $contacts);
    return $contacts[$telegramId];
}

function sendContactList($chatId) {
    global $contactsFile;

    $contacts = readJsonFile($contactsFile, []);

    if (count($contacts) === 0) {
        telegramRequest('sendMessage', [
            'chat_id' => $chatId,
            'text' => 'Belum ada contact yang dikongsi.'
        ]);
        return;
    }

    $lines = ['Senarai Contact Masuk:'];
    $number = 1;

    foreach ($contacts as $contact) {
        $name = trim(($contact['first_name'] ?? '') . ' ' . ($contact['last_name'] ?? ''));
        $username = $contact['username'] ? '@' . $contact['username'] : '-';
        $lines[] = "\n$number. $name\nTelefon: {$contact['phone']}\nTelegram ID: {$contact['telegram_id']}\nUsername: $username";
        $number++;
    }

    telegramRequest('sendMessage', [
        'chat_id' => $chatId,
        'text' => implode("\n", $lines)
    ]);
}

function setGroupLink($chatId, $text) {
    global $settingsFile;

    $link = trim(substr($text, strlen('/setgroup')));

    if (!preg_match('/^https:\/\/t\.me\/.+/i', $link)) {
        telegramRequest('sendMessage', [
            'chat_id' => $chatId,
            'text' => "Format link tidak sah.\n\nContoh:\n/setgroup https://t.me/+abcdef123"
        ]);
        return;
    }

    $settings = readJsonFile($settingsFile, []);
    $settings['group_link'] = $link;
    writeJsonFile($settingsFile, $settings);

    telegramRequest('sendMessage', [
        'chat_id' => $chatId,
        'text' => 'Link group berjaya disimpan.'
    ]);
}

function sendGroupLinkToContacts($chatId) {
    global $contactsFile, $settingsFile;

    $settings = readJsonFile($settingsFile, []);
    $contacts = readJsonFile($contactsFile, []);
    $groupLink = $settings['group_link'] ?? '';

    if ($groupLink === '') {
        telegramRequest('sendMessage', [
            'chat_id' => $chatId,
            'text' => "Link group belum diset.\n\nHantar:\n/setgroup https://t.me/+linkgroup"
        ]);
        return;
    }

    if (count($contacts) === 0) {
        telegramRequest('sendMessage', [
            'chat_id' => $chatId,
            'text' => 'Belum ada contact untuk dihantar link group.'
        ]);
        return;
    }

    $sent = 0;

    foreach ($contacts as $contact) {
        if (empty($contact['chat_id'])) {
            continue;
        }

        telegramRequest('sendMessage', [
            'chat_id' => $contact['chat_id'],
            'text' => "Sila tekan link di bawah untuk masuk ke group:\n$groupLink"
        ]);

        $sent++;
    }

    telegramRequest('sendMessage', [
        'chat_id' => $chatId,
        'text' => "Link group telah dihantar kepada $sent contact."
    ]);
}

if (!isset($update['message'])) {
    echo json_encode(['ok' => true]);
    exit;
}

$message = $update['message'];
$chatId = $message['chat']['id'];
$text = $message['text'] ?? '';
$isAdmin = (string) $chatId === $admin_chat_id;

if ($isAdmin && ($text === '/start' || $text === '/admin' || $text === 'Tunggu Contact User')) {
    sendAdminMenu($chatId);
    echo json_encode(['ok' => true]);
    exit;
}

if ($isAdmin && $text === 'Senarai Contact') {
    sendContactList($chatId);
    echo json_encode(['ok' => true]);
    exit;
}

if ($isAdmin && strpos($text, '/setgroup') === 0) {
    setGroupLink($chatId, $text);
    echo json_encode(['ok' => true]);
    exit;
}

if ($isAdmin && $text === 'Hantar Link Group') {
    sendGroupLinkToContacts($chatId);
    echo json_encode(['ok' => true]);
    exit;
}

if (strpos($text, '/start') === 0 || $text === '/menu') {
    sendContactMenu($chatId);
    echo json_encode(['ok' => true]);
    exit;
}

if (isset($message['contact'])) {
    $contact = saveContact($message);
    sendAdminContactDetail($admin_chat_id, $contact);

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

if ($isAdmin) {
    sendAdminMenu($chatId);
    echo json_encode(['ok' => true]);
    exit;
}

sendContactMenu($chatId);
echo json_encode(['ok' => true]);

?>
