<?php
require __DIR__ . '/../config/kommo.php';

function req(string $method, string $path): void {
    $ch = curl_init(kommoApiBaseUrl() . $path);
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . KOMMO_LONG_LIVED_TOKEN,
            'Content-Type: application/json',
        ],
        CURLOPT_TIMEOUT => 30,
    ]);
    $r = curl_exec($ch);
    $c = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    echo "$method $path => $c\n";
    echo substr((string) $r, 0, 1200) . "\n\n";
}

// Mensajes recientes del talk 102 / chat
req('GET', '/api/v4/talks/102');
req('GET', '/api/v4/contacts/32380114?with=catalog_elements');

// Chats API addon - conversation messages
$chatId = 'cc8c0a86-287f-4c32-9539-e023cc532436';
req('GET', '/api/v4/chats/' . $chatId . '/messages?limit=10');
req('GET', '/api/v4/talks?filter[talk_id]=102&limit=1');

// Intentar history endpoint documentado en add-on
req('GET', '/api/v4/contacts/chats?contact_id=32380114');
