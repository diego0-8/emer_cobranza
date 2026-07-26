<?php
require __DIR__ . '/../config/kommo.php';

function kommoReq(string $method, string $path, ?array $body = null): array {
    $ch = curl_init(kommoApiBaseUrl() . $path);
    $headers = [
        'Authorization: Bearer ' . KOMMO_LONG_LIVED_TOKEN,
        'Content-Type: application/json',
    ];
    $opts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 25,
        CURLOPT_CUSTOMREQUEST => $method,
    ];
    if ($body !== null) {
        $opts[CURLOPT_POSTFIELDS] = json_encode($body, JSON_UNESCAPED_UNICODE);
    }
    curl_setopt_array($ch, $opts);
    $resp = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$code, $resp];
}

[$c1, $r1] = kommoReq('GET', '/api/v4/talks?filter[contact_id]=32380114&limit=5');
echo "talks by contact => $c1\n$r1\n\n";

[$c2, $r2] = kommoReq('GET', '/api/v4/contacts/chats?contact_id=32380114');
echo "contact chats => $c2\n$r2\n\n";

// dry-run send? only if user wants - use a harmless test - skip actual send to avoid consuming limits unless needed
// Instead verify endpoint exists with OPTIONS or just document

echo "OK probe done\n";
