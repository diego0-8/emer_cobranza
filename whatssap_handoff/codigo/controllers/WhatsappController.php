<?php
/**
 * WhatsApp + Kommo — endpoints del CRM.
 */
require_once __DIR__ . '/../config/kommo.php';
require_once __DIR__ . '/../models/WhatsappConversacionModel.php';
require_once __DIR__ . '/../models/WhatsappMensajeModel.php';

class WhatsappController {
    private $pdo;
    private $convModel;
    private $msgModel;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
        $this->convModel = new WhatsappConversacionModel($pdo);
        $this->msgModel = new WhatsappMensajeModel($pdo);
    }

    private function jsonOut(array $payload, int $code = 200): void {
        if (ob_get_level()) {
            ob_clean();
        }
        http_response_code($code);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function readJsonBody(): array {
        $raw = file_get_contents('php://input');
        if ($raw === false || trim($raw) === '') {
            return $_POST ?: [];
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : ($_POST ?: []);
    }

    private function requireSesion(): void {
        if (empty($_SESSION['user_id'])) {
            $this->jsonOut(['success' => false, 'error' => 'No autenticado'], 401);
        }
    }

    private function requireRoles(array $roles): void {
        $this->requireSesion();
        $role = $_SESSION['user_role'] ?? '';
        // Normalizar typo histórico cordinador
        if ($role === 'cordinador') {
            $role = 'coordinador';
        }
        if (!in_array($role, $roles, true)) {
            $this->jsonOut(['success' => false, 'error' => 'Sin permiso'], 403);
        }
    }

    private function currentAsesorId(): string {
        return (string)($_SESSION['user_id'] ?? '');
    }

    public function estado(): void {
        $this->requireSesion();
        $this->jsonOut([
            'success' => true,
            'kommo_enabled' => kommoEnabled(),
            'waba_phone' => KOMMO_WABA_PHONE_E164,
            'mode' => kommoEnabled() ? 'live' : 'demo',
        ]);
    }

    public function misChats(): void {
        $this->requireRoles(['asesor', 'coordinador', 'administrador']);
        $limit = (int)($_GET['limit'] ?? 10);
        $asesorId = $this->currentAsesorId();
        // Admin/coord: si pasan asesor_id pueden ver; por defecto los propios
        if (in_array($_SESSION['user_role'] ?? '', ['administrador', 'coordinador', 'cordinador'], true)
            && !empty($_GET['asesor_id'])) {
            $asesorId = (string)$_GET['asesor_id'];
        }
        $chats = $this->convModel->listByAsesor($asesorId, $limit);
        $total = $this->convModel->countByAsesor($asesorId);
        $this->jsonOut([
            'success' => true,
            'chats' => $chats,
            'total' => $total,
            'limit' => $limit,
            'extra' => max(0, $total - count($chats)),
        ]);
    }

    public function conversacionCliente(): void {
        $this->requireRoles(['asesor', 'coordinador', 'administrador']);
        $clienteId = (int)($_GET['cliente_id'] ?? 0);
        $telefono = trim((string)($_GET['telefono'] ?? ''));
        if ($clienteId <= 0) {
            $this->jsonOut(['success' => false, 'error' => 'cliente_id requerido'], 400);
        }

        $telefonos = $this->convModel->getTelefonosPerfilCliente($clienteId);
        if (empty($telefonos)) {
            $this->jsonOut([
                'success' => true,
                'conversacion' => null,
                'telefonos' => [],
                'warning' => 'El cliente no tiene teléfonos en el perfil',
            ]);
        }

        if ($telefono === '') {
            $telefono = $telefonos[0]['raw'];
        }

        // Validar que el número pertenezca al perfil
        $allowed = false;
        $pickedE164 = kommoNormalizePhoneE164($telefono);
        foreach ($telefonos as $t) {
            if ($t['e164'] === $pickedE164 || $t['raw'] === $telefono) {
                $allowed = true;
                $telefono = $t['raw'];
                break;
            }
        }
        if (!$allowed) {
            $this->jsonOut(['success' => false, 'error' => 'Número no pertenece al perfil del cliente'], 400);
        }

        try {
            $asesorId = $this->currentAsesorId();
            $role = $_SESSION['user_role'] ?? '';
            if (!in_array($role, ['asesor'], true)) {
                // coord/admin pueden abrir sin forzar asesor
                $asesorId = $asesorId ?: null;
            }
            $conv = $this->convModel->getOrCreateForCliente($clienteId, $telefono, $asesorId);
            // Si el asesor abre su ficha, asegurar asignación
            if (($role === 'asesor') && empty($conv['asesor_id'])) {
                $this->convModel->update((int)$conv['id'], ['asesor_id' => $this->currentAsesorId()]);
                $conv = $this->convModel->getById((int)$conv['id']);
            }
            $this->jsonOut([
                'success' => true,
                'conversacion' => $conv,
                'telefonos' => $telefonos,
                'kommo_enabled' => kommoEnabled(),
            ]);
        } catch (Throwable $e) {
            $this->jsonOut(['success' => false, 'error' => $e->getMessage()], 400);
        }
    }

    public function mensajes(): void {
        $this->requireRoles(['asesor', 'coordinador', 'administrador']);
        $convId = (int)($_GET['conversacion_id'] ?? 0);
        $clienteId = (int)($_GET['cliente_id'] ?? 0);
        $afterId = (int)($_GET['after_id'] ?? 0);
        if ($convId <= 0) {
            $this->jsonOut(['success' => false, 'error' => 'conversacion_id requerido'], 400);
        }
        $conv = $this->convModel->getById($convId);
        if (!$conv) {
            $this->jsonOut(['success' => false, 'error' => 'Conversación no encontrada'], 404);
        }
        if ($clienteId > 0 && (int)($conv['cliente_id'] ?? 0) !== $clienteId) {
            $this->jsonOut(['success' => false, 'error' => 'Conversación no pertenece al cliente'], 403);
        }
        $role = $_SESSION['user_role'] ?? '';
        if ($role === 'asesor' && (string)($conv['asesor_id'] ?? '') !== ''
            && (string)$conv['asesor_id'] !== $this->currentAsesorId()) {
            $this->jsonOut(['success' => false, 'error' => 'Chat asignado a otro asesor'], 403);
        }

        // El webhook genérico de Kommo no emite mensajes del canal WABA.
        // Sincronizar el talk en cada poll para incorporar entrantes y estados.
        if (kommoEnabled() && !empty($conv['kommo_talk_id'])) {
            $this->sincronizarMensajesKommo($conv);
        }
        $mensajes = $this->msgModel->listByConversacion($convId, 100, $afterId);
        if ($afterId === 0) {
            $this->convModel->resetNoLeidos($convId);
        }
        $this->jsonOut([
            'success' => true,
            'conversacion' => $conv,
            'mensajes' => $mensajes,
        ]);
    }

    public function enviar(): void {
        $this->requireRoles(['asesor', 'coordinador', 'administrador']);
        $body = $this->readJsonBody();
        $convId = (int)($body['conversacion_id'] ?? 0);
        $clienteId = (int)($body['cliente_id'] ?? 0);
        $texto = trim((string)($body['texto'] ?? ''));
        $telefono = trim((string)($body['telefono'] ?? ''));

        if ($texto === '') {
            $this->jsonOut(['success' => false, 'error' => 'Texto vacío'], 400);
        }
        if (mb_strlen($texto) > 4000) {
            $this->jsonOut(['success' => false, 'error' => 'Mensaje demasiado largo'], 400);
        }

        try {
            if ($convId <= 0 && $clienteId > 0 && $telefono !== '') {
                $conv = $this->convModel->getOrCreateForCliente(
                    $clienteId,
                    $telefono,
                    $this->currentAsesorId()
                );
                $convId = (int)$conv['id'];
            } else {
                $conv = $this->convModel->getById($convId);
            }
            if (!$conv) {
                $this->jsonOut(['success' => false, 'error' => 'Conversación no encontrada'], 404);
            }
            if ($clienteId > 0 && (int)($conv['cliente_id'] ?? 0) !== $clienteId) {
                $this->jsonOut(['success' => false, 'error' => 'cliente_id no coincide'], 403);
            }

            $role = $_SESSION['user_role'] ?? '';
            if ($role === 'asesor') {
                if (empty($conv['asesor_id'])) {
                    $this->convModel->update((int)$conv['id'], ['asesor_id' => $this->currentAsesorId()]);
                } elseif ((string)$conv['asesor_id'] !== $this->currentAsesorId()) {
                    $this->jsonOut(['success' => false, 'error' => 'Chat asignado a otro asesor'], 403);
                }
            }

            $status = 'pendiente_envio';
            $kommoMsgId = null;
            $waActivo = $conv['wa_activo'] ?? 'desconocido';

            if (kommoEnabled()) {
                $send = $this->enviarViaKommo($conv, $texto);
                if (!empty($send['ok'])) {
                    $status = 'enviado';
                    $kommoMsgId = $send['kommo_message_id'] ?? null;
                    $waActivo = 'si';
                } else {
                    $status = 'error_envio';
                    // Si Kommo indica número inválido
                    if (!empty($send['invalid_number'])) {
                        $waActivo = 'no';
                    }
                }
            }

            $msgId = $this->msgModel->create([
                'conversacion_id' => (int)$conv['id'],
                'direccion' => 'out',
                'tipo' => 'text',
                'cuerpo' => $texto,
                'kommo_message_id' => $kommoMsgId,
                'status' => $status,
            ]);
            $this->convModel->touchPreview((int)$conv['id'], $texto);
            if ($waActivo !== ($conv['wa_activo'] ?? '')) {
                $this->convModel->update((int)$conv['id'], ['wa_activo' => $waActivo]);
            }

            // Reconsultar entrega en Kommo (a veces pasa de sent → delivered/error en segundos)
            if ($status === 'enviado' && kommoEnabled()) {
                usleep(400000);
                $conv = $this->convModel->getById((int)$conv['id']);
                $all = $this->msgModel->listByConversacion((int)$conv['id'], 50, 0);
                $this->sincronizarEntregaKommo($conv, $all);
                $row = null;
                foreach ($this->msgModel->listByConversacion((int)$conv['id'], 20, 0) as $m) {
                    if ((int)$m['id'] === $msgId) {
                        $row = $m;
                        break;
                    }
                }
                if ($row) {
                    $status = (string)$row['status'];
                }
            }

            $conv = $this->convModel->getById((int)$conv['id']);
            $this->jsonOut([
                'success' => $status !== 'error_envio',
                'mensaje_id' => $msgId,
                'status' => $status,
                'conversacion' => $conv,
                'kommo_enabled' => kommoEnabled(),
                'hint' => $status === 'enviado' || $status === 'delivered'
                    ? ('Revisa WhatsApp del número ' . ($conv['telefono_e164'] ?? ''))
                    : null,
                'error' => $status === 'error_envio' ? ($send['error'] ?? 'Error al enviar') : null,
            ]);
        } catch (Throwable $e) {
            $this->jsonOut(['success' => false, 'error' => $e->getMessage()], 400);
        }
    }

    /**
     * Envía por Kommo API v4. Si falta talk_id, lo resuelve por teléfono → contacto → talks.
     * @see https://developers.kommo.com/reference/send-message-to-conversation
     */
    private function enviarViaKommo(array $conv, string $texto): array {
        $talkId = trim((string)($conv['kommo_talk_id'] ?? ''));
        $chatId = trim((string)($conv['kommo_chat_id'] ?? ''));

        if ($talkId === '') {
            $resolved = $this->resolverTalkKommoPorTelefono((string)($conv['telefono_e164'] ?? ''));
            if (empty($resolved['ok'])) {
                return [
                    'ok' => false,
                    'error' => $resolved['error'] ?? 'No se pudo vincular la conversación con Kommo',
                    'invalid_number' => !empty($resolved['invalid_number']),
                ];
            }
            $talkId = (string)$resolved['talk_id'];
            $chatId = (string)($resolved['chat_id'] ?? $chatId);
            $updates = ['kommo_talk_id' => $talkId];
            if ($chatId !== '') {
                $updates['kommo_chat_id'] = $chatId;
            }
            $this->convModel->update((int)$conv['id'], $updates);
        }

        $url = kommoApiBaseUrl() . '/api/v4/talks/' . rawurlencode($talkId) . '/send_message';
        $payload = json_encode(['text' => $texto], JSON_UNESCAPED_UNICODE);
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . KOMMO_LONG_LIVED_TOKEN,
            ],
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_TIMEOUT => 25,
        ]);
        $resp = curl_exec($ch);
        $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        if ($resp === false) {
            return ['ok' => false, 'error' => $err ?: 'curl error', 'invalid_number' => false];
        }
        $data = json_decode($resp, true);
        // Kommo documenta 202 Accepted
        if ($http >= 200 && $http < 300) {
            return [
                'ok' => true,
                'kommo_message_id' => (string)($data['id'] ?? $data['message_id'] ?? ('kommo-' . $talkId . '-' . time())),
                'talk_id' => $talkId,
            ];
        }
        $msg = is_array($data) ? (string)($data['detail'] ?? $data['title'] ?? $resp) : (string)$resp;
        $invalid = stripos($msg, 'invalid') !== false || stripos($msg, 'not a whatsapp') !== false;
        return ['ok' => false, 'error' => "Kommo HTTP {$http}: {$msg}", 'invalid_number' => $invalid];
    }

    /**
     * Busca contacto por teléfono en Kommo y toma un talk abierto (preferible origin=waba).
     */
    private function resolverTalkKommoPorTelefono(string $telefonoE164): array {
        $e164 = kommoNormalizePhoneE164($telefonoE164);
        if (!$e164) {
            return ['ok' => false, 'error' => 'Teléfono inválido para vincular con Kommo', 'invalid_number' => true];
        }
        $last10 = kommoPhoneLast10($e164);
        $queries = array_unique(array_filter([$last10, $e164, ltrim($e164, '+')]));

        $contactId = null;
        foreach ($queries as $q) {
            [$code, $body] = $this->kommoApiRequest('GET', '/api/v4/contacts?query=' . rawurlencode($q) . '&limit=10');
            if ($code < 200 || $code >= 300) {
                continue;
            }
            $data = json_decode($body, true);
            $contacts = $data['_embedded']['contacts'] ?? [];
            foreach ($contacts as $c) {
                $phones = [];
                foreach (($c['custom_fields_values'] ?? []) as $cf) {
                    if (($cf['field_code'] ?? '') !== 'PHONE') {
                        continue;
                    }
                    foreach (($cf['values'] ?? []) as $v) {
                        $phones[] = (string)($v['value'] ?? '');
                    }
                }
                foreach ($phones as $p) {
                    if (kommoPhoneLast10($p) === $last10 || kommoNormalizePhoneE164($p) === $e164) {
                        $contactId = (int)$c['id'];
                        break 3;
                    }
                }
            }
            // Si solo hay un contacto y la query era el teléfono, usarlo
            if ($contactId === null && count($contacts) === 1 && strlen($q) >= 7) {
                $contactId = (int)$contacts[0]['id'];
                break;
            }
        }

        if (!$contactId) {
            return [
                'ok' => false,
                'error' => 'No hay contacto en Kommo con ese teléfono. El cliente debe escribir primero al WhatsApp Business o créalo/ábrelo en Kommo.',
                'invalid_number' => false,
            ];
        }

        [$code, $body] = $this->kommoApiRequest(
            'GET',
            '/api/v4/talks?filter[contact_id]=' . $contactId . '&limit=20'
        );
        if ($code < 200 || $code >= 300) {
            return ['ok' => false, 'error' => "No se pudieron listar talks de Kommo (HTTP {$code})", 'invalid_number' => false];
        }
        $data = json_decode($body, true);
        $talks = $data['_embedded']['talks'] ?? [];
        if (empty($talks)) {
            return [
                'ok' => false,
                'error' => 'El contacto existe en Kommo pero aún no tiene conversación WhatsApp (talk). Pide al cliente que escriba al Business o inicia el chat desde Kommo.',
                'invalid_number' => false,
            ];
        }

        // Preferir talk waba / en trabajo
        usort($talks, static function ($a, $b) {
            $score = static function ($t) {
                $s = 0;
                if (($t['origin'] ?? '') === 'waba') {
                    $s += 10;
                }
                if (!empty($t['is_in_work']) || ($t['status'] ?? '') === 'in_work') {
                    $s += 5;
                }
                $s += (int)($t['updated_at'] ?? 0) / 1000000000;
                return $s;
            };
            return $score($b) <=> $score($a);
        });
        $best = $talks[0];
        $talkId = (string)($best['talk_id'] ?? '');
        if ($talkId === '') {
            return ['ok' => false, 'error' => 'Talk de Kommo sin id', 'invalid_number' => false];
        }
        return [
            'ok' => true,
            'talk_id' => $talkId,
            'chat_id' => (string)($best['chat_id'] ?? ''),
            'contact_id' => $contactId,
        ];
    }

    private function kommoApiRequest(string $method, string $path, ?array $jsonBody = null): array {
        $ch = curl_init(kommoApiBaseUrl() . $path);
        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . KOMMO_LONG_LIVED_TOKEN,
                'Content-Type: application/json',
            ],
            CURLOPT_TIMEOUT => 25,
        ];
        if ($jsonBody !== null) {
            $opts[CURLOPT_POSTFIELDS] = json_encode($jsonBody, JSON_UNESCAPED_UNICODE);
        }
        curl_setopt_array($ch, $opts);
        $resp = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        if ($resp === false) {
            return [0, $err ?: 'curl error'];
        }
        return [$code, (string)$resp];
    }

    /**
     * Actualiza status local según delivery_status de Kommo (sent|delivered|error).
     */
    private function sincronizarEntregaKommo(array $conv, array $mensajesLocales): void {
        $talkId = trim((string)($conv['kommo_talk_id'] ?? ''));
        if ($talkId === '') {
            return;
        }
        $pending = [];
        foreach ($mensajesLocales as $m) {
            if (($m['direccion'] ?? '') !== 'out') {
                continue;
            }
            $kid = (string)($m['kommo_message_id'] ?? '');
            if ($kid === '') {
                continue;
            }
            $st = (string)($m['status'] ?? '');
            if (in_array($st, ['enviado', 'sent', 'pendiente_envio'], true)) {
                $pending[$kid] = (int)$m['id'];
            }
        }
        if (!$pending) {
            return;
        }
        [$code, $body] = $this->kommoApiRequest('GET', '/api/v4/talks/' . rawurlencode($talkId) . '/messages?limit=50');
        if ($code < 200 || $code >= 300) {
            return;
        }
        $data = json_decode($body, true);
        $remote = $data['_embedded']['messages'] ?? [];
        foreach ($remote as $rm) {
            $rid = (string)($rm['id'] ?? '');
            if ($rid === '' || !isset($pending[$rid])) {
                continue;
            }
            $del = strtolower((string)($rm['delivery_status'] ?? 'sent'));
            $newStatus = 'enviado';
            if ($del === 'delivered') {
                $newStatus = 'delivered';
            } elseif ($del === 'error') {
                $newStatus = 'error_envio';
            } elseif ($del === 'sent') {
                $newStatus = 'enviado';
            }
            $this->msgModel->updateStatus($pending[$rid], $newStatus);
        }
    }

    /**
     * Importa mensajes faltantes del talk y actualiza estados de entrega.
     *
     * El endpoint WEB HOOKS general de Kommo no publica eventos de WhatsApp.
     * El panel consulta esta API cada 5 segundos, por lo que esta sincronización
     * mantiene el espejo local actualizado sin depender del webhook de Chats API.
     */
    private function sincronizarMensajesKommo(array $conv): void {
        $talkId = trim((string)($conv['kommo_talk_id'] ?? ''));
        if ($talkId === '') {
            return;
        }

        [$code, $body] = $this->kommoApiRequest(
            'GET',
            '/api/v4/talks/' . rawurlencode($talkId) . '/messages?limit=100'
        );
        if ($code < 200 || $code >= 300) {
            return;
        }

        $data = json_decode($body, true);
        $remote = $data['_embedded']['messages'] ?? [];
        if (!$remote) {
            return;
        }

        // Kommo devuelve más recientes primero; insertar cronológicamente.
        usort($remote, static function (array $a, array $b): int {
            return ((int)($a['created_at'] ?? 0)) <=> ((int)($b['created_at'] ?? 0));
        });

        $lastPreview = null;
        $lastTimestamp = null;
        foreach ($remote as $message) {
            $messageId = trim((string)($message['id'] ?? ''));
            if ($messageId === '') {
                continue;
            }

            $delivery = strtolower((string)($message['delivery_status'] ?? 'sent'));
            $direction = ($message['type'] ?? '') === 'incoming' ? 'in' : 'out';
            $status = $direction === 'in' ? 'recibido' : 'enviado';
            if ($direction === 'out' && $delivery === 'delivered') {
                $status = 'delivered';
            } elseif ($direction === 'out' && $delivery === 'error') {
                $status = 'error_envio';
            }

            $existing = $this->msgModel->findByKommoMessageId($messageId);
            if ($existing) {
                if ((string)$existing['status'] !== $status) {
                    $this->msgModel->updateStatus((int)$existing['id'], $status);
                }
            } else {
                $attachment = $message['attachment'] ?? null;
                $mediaUrl = is_array($attachment)
                    ? (string)($attachment['link'] ?? '')
                    : null;
                $createdAt = !empty($message['created_at'])
                    ? date('Y-m-d H:i:s', (int)$message['created_at'])
                    : date('Y-m-d H:i:s');

                try {
                    $this->msgModel->create([
                        'conversacion_id' => (int)$conv['id'],
                        'direccion' => $direction,
                        'tipo' => (string)($message['message_type'] ?? 'text'),
                        'cuerpo' => (string)($message['text'] ?? ''),
                        'media_url' => $mediaUrl !== '' ? $mediaUrl : null,
                        'kommo_message_id' => $messageId,
                        'status' => $status,
                        'created_at' => $createdAt,
                    ]);
                } catch (PDOException $e) {
                    // Idempotencia ante dos polls simultáneos (índice único).
                    if ((string)$e->getCode() !== '23000') {
                        throw $e;
                    }
                }
            }

            $timestamp = (int)($message['created_at'] ?? 0);
            if ($timestamp >= (int)$lastTimestamp) {
                $lastTimestamp = $timestamp;
                $lastPreview = trim((string)($message['text'] ?? ''));
            }
        }

        if ($lastPreview !== null && $lastPreview !== '') {
            $this->convModel->touchPreview(
                (int)$conv['id'],
                $lastPreview,
                $lastTimestamp ? date('Y-m-d H:i:s', (int)$lastTimestamp) : null
            );
        }
    }

    public function webhookKommo(): void {
        $method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));

        // Kommo (y su validador) hace GET/HEAD para comprobar que la URL es pública.
        // Debe responder 200; los mensajes reales llegan por POST.
        if ($method === 'GET' || $method === 'HEAD') {
            if (ob_get_level()) {
                ob_clean();
            }
            http_response_code(200);
            header('Content-Type: application/json; charset=UTF-8');
            if ($method === 'GET') {
                echo json_encode([
                    'success' => true,
                    'service' => 'emer_cobranza_wa_webhook',
                    'message' => 'Webhook activo. Enviar mensajes por POST.',
                ], JSON_UNESCAPED_UNICODE);
            }
            exit;
        }

        // Público: validar secret opcional
        $secret = KOMMO_WEBHOOK_SECRET;
        if ($secret !== '') {
            $hdr = $_SERVER['HTTP_X_WA_WEBHOOK_SECRET'] ?? '';
            if (!hash_equals($secret, $hdr)) {
                $this->jsonOut(['success' => false, 'error' => 'Firma inválida'], 401);
            }
        }

        $body = $this->readJsonBody();
        // Formato simplificado del handoff + posibles wrappers Kommo
        $phone = (string)($body['phone'] ?? $body['telefono'] ?? '');
        $text = (string)($body['text'] ?? $body['message'] ?? $body['cuerpo'] ?? '');
        $direction = strtolower((string)($body['direction'] ?? $body['direccion'] ?? 'in'));
        if (!in_array($direction, ['in', 'out'], true)) {
            $direction = 'in';
        }
        $kommoMsgId = (string)($body['kommo_message_id'] ?? $body['message_id'] ?? '');
        $talkId = $body['kommo_talk_id'] ?? $body['talk_id'] ?? null;
        $chatId = $body['kommo_chat_id'] ?? $body['chat_id'] ?? null;

        if ($phone === '' && !empty($body['contact']['phone'])) {
            $phone = (string)$body['contact']['phone'];
        }
        if ($text === '' && !empty($body['message']['text'])) {
            $text = (string)$body['message']['text'];
        }

        // POST vacío / sin payload útil: responder 200 para health-check de algunos proveedores
        if ($phone === '' && $text === '' && $kommoMsgId === '' && empty($body)) {
            $this->jsonOut([
                'success' => true,
                'service' => 'emer_cobranza_wa_webhook',
                'message' => 'Webhook activo. Esperando payload de mensaje.',
            ]);
        }

        $e164 = kommoNormalizePhoneE164($phone);
        if (!$e164) {
            $this->jsonOut(['success' => false, 'error' => 'Teléfono inválido'], 400);
        }
        if ($kommoMsgId !== '' && $this->msgModel->findByKommoMessageId($kommoMsgId)) {
            $this->jsonOut(['success' => true, 'dedup' => true]);
        }

        $conv = $this->convModel->getByTelefonoE164($e164);
        if (!$conv) {
            $clienteId = $this->convModel->findClienteIdByPhone($e164);
            $estado = $clienteId ? 'abierta' : 'sin_cliente';
            $asesorId = null;
            // Sin columna asesor_id en clientes: se asigna al abrir ficha / emparejar
            $id = $this->convModel->create([
                'cliente_id' => $clienteId,
                'telefono_e164' => $e164,
                'kommo_talk_id' => $talkId,
                'kommo_chat_id' => $chatId,
                'asesor_id' => $asesorId,
                'estado' => $estado,
                'wa_activo' => 'si',
                'no_leidos' => $direction === 'in' ? 1 : 0,
                'ultimo_mensaje_at' => date('Y-m-d H:i:s'),
                'ultimo_preview' => mb_substr($text !== '' ? $text : '[mensaje]', 0, 250),
            ]);
            $conv = $this->convModel->getById($id);
        } else {
            $updates = ['wa_activo' => 'si'];
            if ($talkId && empty($conv['kommo_talk_id'])) {
                $updates['kommo_talk_id'] = $talkId;
            }
            if ($chatId && empty($conv['kommo_chat_id'])) {
                $updates['kommo_chat_id'] = $chatId;
            }
            if (empty($conv['cliente_id'])) {
                $clienteId = $this->convModel->findClienteIdByPhone($e164);
                if ($clienteId) {
                    $updates['cliente_id'] = $clienteId;
                    $updates['estado'] = 'abierta';
                }
            }
            $this->convModel->update((int)$conv['id'], $updates);
            $this->convModel->touchPreview((int)$conv['id'], $text !== '' ? $text : '[mensaje]');
            if ($direction === 'in') {
                $this->convModel->incrementNoLeidos((int)$conv['id']);
            }
            $conv = $this->convModel->getById((int)$conv['id']);
        }

        $msgId = $this->msgModel->create([
            'conversacion_id' => (int)$conv['id'],
            'direccion' => $direction,
            'tipo' => 'text',
            'cuerpo' => $text !== '' ? $text : null,
            'kommo_message_id' => $kommoMsgId !== '' ? $kommoMsgId : null,
            'status' => $direction === 'in' ? 'recibido' : 'enviado',
        ]);

        $this->jsonOut([
            'success' => true,
            'conversacion_id' => (int)$conv['id'],
            'cliente_id' => $conv['cliente_id'] ? (int)$conv['cliente_id'] : null,
            'mensaje_id' => $msgId,
            'estado' => $conv['estado'],
        ]);
    }

    public function sinCliente(): void {
        $this->requireRoles(['coordinador', 'administrador']);
        $list = $this->convModel->listSinCliente(100);
        $this->jsonOut(['success' => true, 'conversaciones' => $list]);
    }

    public function emparejar(): void {
        $this->requireRoles(['coordinador', 'administrador']);
        $body = $this->readJsonBody();
        $convId = (int)($body['conversacion_id'] ?? 0);
        $clienteId = (int)($body['cliente_id'] ?? 0);
        $asesorId = isset($body['asesor_id']) ? (string)$body['asesor_id'] : null;
        if ($convId <= 0 || $clienteId <= 0) {
            $this->jsonOut(['success' => false, 'error' => 'conversacion_id y cliente_id requeridos'], 400);
        }
        $conv = $this->convModel->getById($convId);
        if (!$conv) {
            $this->jsonOut(['success' => false, 'error' => 'Conversación no encontrada'], 404);
        }
        $fields = [
            'cliente_id' => $clienteId,
            'estado' => 'abierta',
        ];
        if ($asesorId !== null && $asesorId !== '') {
            $fields['asesor_id'] = $asesorId;
        }
        $this->convModel->update($convId, $fields);
        $stmt = $this->pdo->prepare(
            'INSERT INTO wa_asignaciones (conversacion_id, asesor_id, asignado_por) VALUES (?, ?, ?)'
        );
        $stmt->execute([
            $convId,
            $asesorId ?: ($conv['asesor_id'] ?: '0'),
            $this->currentAsesorId(),
        ]);
        $this->jsonOut([
            'success' => true,
            'conversacion' => $this->convModel->getById($convId),
            'open_url' => 'index.php?action=gestionar_cliente&id=' . $clienteId . '&wa=' . $convId,
        ]);
    }
}
