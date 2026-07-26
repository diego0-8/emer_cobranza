<?php
/**
 * Inspecciona la estructura cruda de mensajes con adjuntos (imagen/pdf) del talk.
 */
require __DIR__ . '/../config/kommo.php';

$talkId = (int)($argv[1] ?? 102);
$ch = curl_init(kommoApiBaseUrl() . "/api/v4/talks/{$talkId}/messages?limit=100");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . KOMMO_LONG_LIVED_TOKEN,
        'Accept: application/hal+json',
    ],
    CURLOPT_TIMEOUT => 30,
]);
$resp = curl_exec($ch);
$http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
echo "HTTP {$http}\n";
$data = json_decode((string) $resp, true);
$msgs = $data['_embedded']['messages'] ?? [];
echo 'total=' . count($msgs) . "\n\n";
foreach ($msgs as $m) {
    $type = $m['message_type'] ?? '';
    if ($type === 'text') {
        continue;
    }
    echo "=== NO-TEXT message ===\n";
    echo json_encode($m, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n\n";
}
echo "(fin: solo se listan mensajes con message_type != text)\n";
