<?php
/**
 * Prueba resolución teléfono → talk_id (misma lógica que el controller).
 */
require __DIR__ . '/../config/kommo.php';

$phone = $argv[1] ?? '+573208748605';
$e164 = kommoNormalizePhoneE164($phone);
$last10 = kommoPhoneLast10($e164);
echo "phone=$phone e164=$e164 last10=$last10\n";

function req(string $path): array {
    $ch = curl_init(kommoApiBaseUrl() . $path);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . KOMMO_LONG_LIVED_TOKEN,
            'Content-Type: application/json',
        ],
        CURLOPT_TIMEOUT => 25,
    ]);
    $r = curl_exec($ch);
    $c = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$c, (string) $r];
}

[$c, $body] = req('/api/v4/contacts?query=' . rawurlencode($last10) . '&limit=10');
echo "contacts HTTP $c\n";
$data = json_decode($body, true);
$contactId = null;
foreach (($data['_embedded']['contacts'] ?? []) as $ct) {
    foreach (($ct['custom_fields_values'] ?? []) as $cf) {
        if (($cf['field_code'] ?? '') !== 'PHONE') continue;
        foreach (($cf['values'] ?? []) as $v) {
            if (kommoPhoneLast10((string)$v['value']) === $last10) {
                $contactId = (int)$ct['id'];
                break 3;
            }
        }
    }
}
echo "contact_id=" . ($contactId ?: 'NONE') . "\n";
if (!$contactId) {
    exit(1);
}

[$c2, $body2] = req('/api/v4/talks?filter[contact_id]=' . $contactId . '&limit=10');
echo "talks HTTP $c2\n";
$talks = json_decode($body2, true)['_embedded']['talks'] ?? [];
foreach ($talks as $t) {
    echo "talk_id={$t['talk_id']} origin={$t['origin']} status={$t['status']} chat={$t['chat_id']}\n";
}
echo count($talks) ? "RESOLVE_OK\n" : "RESOLVE_FAIL\n";
