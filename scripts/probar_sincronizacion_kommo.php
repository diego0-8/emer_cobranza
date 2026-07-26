<?php
/**
 * Diagnóstico de sincronización Kommo → wa_mensajes.
 * No modifica datos.
 */
require __DIR__ . '/../config.php';
require __DIR__ . '/../config/kommo.php';

$pdo = new PDO(
    'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
    DB_USER,
    DB_PASS,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

$talkId = (int)($argv[1] ?? 102);
$stmt = $pdo->prepare(
    'SELECT id, telefono_e164, kommo_talk_id, kommo_chat_id
     FROM wa_conversaciones WHERE kommo_talk_id = ? LIMIT 1'
);
$stmt->execute([$talkId]);
$conv = $stmt->fetch();
if (!$conv) {
    fwrite(STDERR, "No existe conversación local para talk_id={$talkId}\n");
    exit(1);
}

$ch = curl_init(kommoApiBaseUrl() . "/api/v4/talks/{$talkId}/messages?limit=100");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . KOMMO_LONG_LIVED_TOKEN,
        'Accept: application/hal+json',
    ],
    CURLOPT_TIMEOUT => 30,
]);
$response = curl_exec($ch);
$http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "Kommo HTTP: {$http}\n";
if ($response === false || $http !== 200) {
    fwrite(STDERR, "Error Kommo: " . ($error ?: $response) . "\n");
    exit(1);
}

$json = json_decode($response, true);
$remote = $json['_embedded']['messages'] ?? [];
$localStmt = $pdo->prepare(
    'SELECT id, direccion, cuerpo, status, kommo_message_id
     FROM wa_mensajes WHERE conversacion_id = ? ORDER BY id'
);
$localStmt->execute([(int)$conv['id']]);
$local = $localStmt->fetchAll();
$localByKommo = [];
foreach ($local as $message) {
    if (!empty($message['kommo_message_id'])) {
        $localByKommo[(string)$message['kommo_message_id']] = $message;
    }
}

echo "Conversación local: {$conv['id']} | teléfono: {$conv['telefono_e164']} | talk: {$talkId}\n";
echo 'Mensajes Kommo: ' . count($remote) . ' | mensajes locales: ' . count($local) . "\n\n";
echo "DIRECCION | ESTADO     | LOCAL | TEXTO\n";
echo str_repeat('-', 78) . "\n";

$missing = [];
foreach ($remote as $message) {
    $id = (string)($message['id'] ?? '');
    $direction = (string)($message['type'] ?? '');
    $status = (string)($message['delivery_status'] ?? '');
    $text = trim((string)($message['text'] ?? ''));
    $exists = isset($localByKommo[$id]);
    printf(
        "%-9s | %-10s | %-5s | %s\n",
        $direction,
        $status,
        $exists ? 'SÍ' : 'NO',
        mb_substr($text, 0, 50)
    );
    if (!$exists) {
        $missing[] = $message;
    }
}

echo "\nFaltantes en CRM: " . count($missing) . "\n";
$missingIncoming = array_filter(
    $missing,
    static fn(array $m): bool => ($m['type'] ?? '') === 'incoming'
);
echo "Entrantes faltantes: " . count($missingIncoming) . "\n";
if ($missingIncoming) {
    echo "CAUSA CONFIRMADA: Kommo tiene mensajes entrantes que el webhook genérico no insertó en wa_mensajes.\n";
}
