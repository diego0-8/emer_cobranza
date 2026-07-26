<?php
/**
 * Ejecuta una sincronización real para verificar Kommo → CRM.
 */
require __DIR__ . '/../config.php';
require __DIR__ . '/../controllers/WhatsappController.php';

$pdo = new PDO(
    'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
    DB_USER,
    DB_PASS,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

$talkId = (int)($argv[1] ?? 102);
$stmt = $pdo->prepare('SELECT * FROM wa_conversaciones WHERE kommo_talk_id = ? LIMIT 1');
$stmt->execute([$talkId]);
$conversation = $stmt->fetch();
if (!$conversation) {
    fwrite(STDERR, "Conversación local no encontrada para talk {$talkId}\n");
    exit(1);
}

$before = (int)$pdo->query(
    'SELECT COUNT(*) FROM wa_mensajes WHERE conversacion_id = ' . (int)$conversation['id']
)->fetchColumn();

$controller = new WhatsappController($pdo);
$method = new ReflectionMethod(WhatsappController::class, 'sincronizarMensajesKommo');
$method->setAccessible(true);
$method->invoke($controller, $conversation);

$after = (int)$pdo->query(
    'SELECT COUNT(*) FROM wa_mensajes WHERE conversacion_id = ' . (int)$conversation['id']
)->fetchColumn();

echo "Talk: {$talkId}\n";
echo "Antes: {$before}\n";
echo "Después: {$after}\n";
echo 'Importados: ' . ($after - $before) . "\n";

$list = $pdo->prepare(
    'SELECT direccion, cuerpo, status, kommo_message_id, created_at
     FROM wa_mensajes WHERE conversacion_id = ? ORDER BY created_at DESC, id DESC LIMIT 8'
);
$list->execute([(int)$conversation['id']]);
foreach ($list->fetchAll() as $message) {
    printf(
        "%s | %-10s | %s | %s\n",
        $message['created_at'],
        $message['status'],
        $message['direccion'],
        mb_substr((string)$message['cuerpo'], 0, 60)
    );
}
