<?php
require __DIR__ . '/../config/kommo.php';

function kommoGet(string $path): void {
    $ch = curl_init(kommoApiBaseUrl() . $path);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . KOMMO_LONG_LIVED_TOKEN,
            'Content-Type: application/json',
        ],
        CURLOPT_TIMEOUT => 25,
    ]);
    $resp = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    echo "GET {$path} => {$code}\n";
    echo substr((string) $resp, 0, 600) . "\n\n";
}

kommoGet('/api/v4/talks?limit=3');
kommoGet('/api/v4/contacts?limit=2&with=leads');
kommoGet('/api/v4/contacts?query=3208748605&limit=5');
