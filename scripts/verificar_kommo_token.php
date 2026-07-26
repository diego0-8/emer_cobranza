<?php
require __DIR__ . '/../config/kommo.php';
echo 'enabled=' . (kommoEnabled() ? 'YES' : 'NO') . PHP_EOL;
echo 'token_len=' . strlen(KOMMO_LONG_LIVED_TOKEN) . PHP_EOL;
echo 'account=' . KOMMO_ACCOUNT_ID . PHP_EOL;
echo 'api=' . kommoApiBaseUrl() . PHP_EOL;

$url = kommoApiBaseUrl() . '/api/v4/account';
$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . KOMMO_LONG_LIVED_TOKEN,
        'Content-Type: application/json',
    ],
    CURLOPT_TIMEOUT => 20,
]);
$resp = curl_exec($ch);
$code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
$err = curl_error($ch);
curl_close($ch);
echo 'account_http=' . $code . PHP_EOL;
if ($err) {
    echo 'curl_err=' . $err . PHP_EOL;
}
$j = json_decode((string) $resp, true);
if (is_array($j)) {
    echo 'name=' . ($j['name'] ?? '') . PHP_EOL;
    echo 'id=' . ($j['id'] ?? '') . PHP_EOL;
    echo 'top_keys=' . implode(',', array_slice(array_keys($j), 0, 10)) . PHP_EOL;
} else {
    echo 'body_prefix=' . substr((string) $resp, 0, 300) . PHP_EOL;
}
