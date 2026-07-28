<?php
/**
 * WhatsApp — Meta Cloud API con fallback Kommo.
 */
require_once __DIR__ . '/../config/kommo.php';
require_once __DIR__ . '/../config/meta.php';
require_once __DIR__ . '/../services/MetaCloudApiGateway.php';
require_once __DIR__ . '/../models/WhatsappConversacionModel.php';
require_once __DIR__ . '/../models/WhatsappMensajeModel.php';
require_once __DIR__ . '/../models/WhatsappCampanaMasivaModel.php';
require_once __DIR__ . '/../models/WhatsappColaModel.php';
require_once __DIR__ . '/../models/CampanaModel.php';
require_once __DIR__ . '/../models/CargaExcelModel.php';
require_once __DIR__ . '/../models/TareaModel.php';

class WhatsappController {
    private $pdo;
    private $convModel;
    private $msgModel;
    private $campanaMasivaModel;
    private $colaModel;
    private $campanaModel;
    private MetaCloudApiGateway $metaGateway;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
        $this->convModel = new WhatsappConversacionModel($pdo);
        $this->msgModel = new WhatsappMensajeModel($pdo);
        $this->campanaMasivaModel = new WhatsappCampanaMasivaModel($pdo);
        $this->colaModel = new WhatsappColaModel($pdo);
        $this->campanaModel = new CampanaModel($pdo);
        $this->metaGateway = new MetaCloudApiGateway();
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

    /**
     * Libera el lock de sesión PHP para no congelar softphone / buscador
     * mientras este request espera a Kommo u otras I/O.
     */
    private function releaseSessionLock(): void {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
    }

    public function estado(): void {
        $this->requireSesion();
        $provider = waProvider();
        $enabled = $provider === 'meta' ? metaEnabled() : kommoEnabled();
        $this->jsonOut([
            'success' => true,
            'kommo_enabled' => kommoEnabled(),
            'meta_enabled' => metaEnabled(),
            'provider' => $provider,
            'waba_phone' => $provider === 'meta' ? '' : KOMMO_WABA_PHONE_E164,
            'mode' => $enabled ? 'live' : 'demo',
        ]);
    }

    public function misChats(): void {
        $this->requireRoles(['asesor', 'coordinador', 'administrador']);
        $limit = (int)($_GET['limit'] ?? 10);
        $limit = max(1, min(10, $limit));
        $asesorId = $this->currentAsesorId();
        if (in_array($_SESSION['user_role'] ?? '', ['administrador', 'coordinador', 'cordinador'], true)
            && !empty($_GET['asesor_id'])) {
            $asesorId = (string)$_GET['asesor_id'];
        }
        $this->releaseSessionLock();

        // Sync ligero de burbujas: Kommo no emite webhook WA; sin esto,
        // el asesor en OTRA ficha nunca ve mensajes nuevos en las burbujas.
        $syncBubbles = (int)($_GET['sync_bubbles'] ?? 0) === 1;
        $syncMeta = ['synced' => 0, 'ms' => 0, 'new_inbound' => 0];
        if ($syncBubbles && waProvider() === 'kommo' && kommoEnabled()) {
            $syncMeta = $this->sincronizarBurbujasAsesor($asesorId, 3, 2500);
        }

        // Dispatcher después de soltar sesión (solo DB local)
        $this->dispatchColaAsignacion();

        $chats = $this->convModel->listBubblesActivas($asesorId, $limit);
        $overflow = $this->convModel->listOverflowCola($asesorId, $limit);
        $this->jsonOut([
            'success' => true,
            'chats' => $chats,
            'total' => $this->convModel->countByAsesor($asesorId),
            'limit' => $limit,
            'extra' => count($overflow),
            'overflow_count' => count($overflow),
            'overflow' => array_slice($overflow, 0, 30),
            'sync' => $syncMeta,
        ]);
    }

    public function burbujaDismiss(): void {
        $this->requireRoles(['asesor', 'coordinador', 'administrador']);
        $body = $this->readJsonBody();
        $convId = (int)($body['conversacion_id'] ?? $_GET['conversacion_id'] ?? 0);
        if ($convId <= 0) {
            $this->jsonOut(['success' => false, 'error' => 'conversacion_id requerido'], 400);
        }
        $conv = $this->convModel->getById($convId);
        if (!$conv) {
            $this->jsonOut(['success' => false, 'error' => 'Conversación no encontrada'], 404);
        }
        $asesorId = $this->currentAsesorId();
        $this->colaModel->dismiss($convId, $asesorId);
        $this->jsonOut(['success' => true]);
    }

    public function burbujaRestore(): void {
        $this->requireRoles(['asesor', 'coordinador', 'administrador']);
        $body = $this->readJsonBody();
        $convId = (int)($body['conversacion_id'] ?? 0);
        if ($convId <= 0) {
            $this->jsonOut(['success' => false, 'error' => 'conversacion_id requerido'], 400);
        }
        $asesorId = $this->currentAsesorId();
        // Capacidad: si ya tiene 10 activas, no restaurar a rail (sigue en overflow)
        if ($this->convModel->countBubblesActivas($asesorId) >= 10) {
            $this->jsonOut(['success' => false, 'error' => 'Ya tienes 10 burbujas visibles. Cierra una primero.'], 409);
        }
        $this->colaModel->undismiss($convId, $asesorId);
        // Asegurar que la notificación le pertenece
        $conv = $this->convModel->getById($convId);
        if ($conv && empty($conv['asesor_notificacion_id'])) {
            $this->convModel->update($convId, [
                'asesor_notificacion_id' => $asesorId,
                'asesor_id' => $asesorId,
            ]);
        }
        $this->jsonOut(['success' => true]);
    }

    public function conversacionCliente(): void {
        $this->requireRoles(['asesor', 'coordinador', 'administrador']);
        $clienteId = (int)($_GET['cliente_id'] ?? 0);
        $telefono = trim((string)($_GET['telefono'] ?? ''));
        $convIdParam = (int)($_GET['conversacion_id'] ?? 0);
        if ($clienteId <= 0) {
            $this->jsonOut(['success' => false, 'error' => 'cliente_id requerido'], 400);
        }

        $telefonos = $this->convModel->getTelefonosPerfilCliente($clienteId);
        if (empty($telefonos)) {
            $this->releaseSessionLock();
            $this->jsonOut([
                'success' => true,
                'conversacion' => null,
                'telefonos' => [],
                'warning' => 'El cliente no tiene teléfonos en el perfil',
            ]);
        }

        $convExistente = null;
        if ($convIdParam > 0) {
            $convExistente = $this->convModel->getById($convIdParam);
            if (!$convExistente || (int)($convExistente['cliente_id'] ?? 0) !== $clienteId) {
                $this->jsonOut(['success' => false, 'error' => 'Conversación no pertenece al cliente'], 403);
            }
            $telefono = $this->telefonoRawDesdeE164((string)$convExistente['telefono_e164'], $telefonos)
                ?: (string)$convExistente['telefono_e164'];
        }

        if ($telefono === '') {
            $best = $this->convModel->pickBestTelefonoE164($clienteId, $telefonos);
            $telefono = $best['raw'] ?? $telefonos[0]['raw'];
        }

        // Validar que el número pertenezca al perfil (excepto deep-link wa= con conversación existente)
        if (!$convExistente) {
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
        }

        try {
            $asesorId = $this->currentAsesorId();
            $role = $_SESSION['user_role'] ?? '';
            if (!in_array($role, ['asesor'], true)) {
                $asesorId = $asesorId ?: null;
            }
            // Lectura/apertura: no forzar claim de notificación al solo abrir
            if ($convExistente) {
                $conv = $convExistente;
            } else {
                $conv = $this->convModel->getOrCreateForCliente(
                    $clienteId,
                    $telefono,
                    null
                );
            }
            if ($role === 'asesor') {
                $notif = (string)($conv['asesor_notificacion_id'] ?? $conv['asesor_id'] ?? '');
                // Si el asesor abre desde su burbuja (wa=), marcar interacción
                $fromWa = (int)($_GET['claim'] ?? 0) === 1;
                if ($fromWa && ($notif === '' || $notif === $this->currentAsesorId())) {
                    $this->claimNotificacion((int)$conv['id'], $this->currentAsesorId(), true);
                    $conv = $this->convModel->getById((int)$conv['id']);
                }
            }
            $this->releaseSessionLock();
            $telefonoUi = $conv['telefono_e164'] ?? null;
            if ($telefonoUi) {
                $telefonoUi = kommoNormalizePhoneE164((string)$telefonoUi) ?: $telefonoUi;
            }
            $best = $this->convModel->pickBestTelefonoE164($clienteId, $telefonos);
            $this->jsonOut([
                'success' => true,
                'conversacion' => $conv,
                'telefonos' => $telefonos,
                'telefono_preferido' => $telefonoUi ? ['e164' => $telefonoUi] : $best,
                'kommo_enabled' => kommoEnabled(),
                'meta_enabled' => metaEnabled(),
                'provider' => waProvider(),
                'puede_enviar' => $this->asesorPuedeEnviar($conv),
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
        $asesorId = $this->currentAsesorId();
        // Lectura compartida: cualquier asesor/coord/admin autenticado puede leer el historial.
        if ($role === 'asesor' && !$this->asesorPuedeLeerCliente((int)($conv['cliente_id'] ?? 0))) {
            $this->jsonOut(['success' => false, 'error' => 'Sin acceso a la base de este cliente'], 403);
        }
        $this->releaseSessionLock();

        // Sync Kommo (puede tardar ~0.5–2s) sin retener session lock
        $skipSync = (int)($_GET['skip_sync'] ?? 0) === 1;
        if (!$skipSync && waProvider() === 'kommo' && kommoEnabled() && !empty($conv['kommo_talk_id'])) {
            $this->sincronizarMensajesKommo($conv);
            $conv = $this->convModel->getById($convId) ?: $conv;
        }
        $this->dispatchColaAsignacion();

        $mensajes = $this->msgModel->listByConversacion($convId, 100, $afterId);
        if ($afterId === 0) {
            $notif = (string)($conv['asesor_notificacion_id'] ?? $conv['asesor_id'] ?? '');
            if ($role !== 'asesor' || $notif === '' || $notif === $asesorId) {
                $this->convModel->resetNoLeidos($convId);
            }
        }
        $this->jsonOut([
            'success' => true,
            'conversacion' => $conv,
            'mensajes' => $mensajes,
            'puede_enviar' => $this->asesorPuedeEnviar($conv),
        ]);
    }

    /**
     * Proxy de adjuntos (PDF/imagen/audio) para previsualizar o reproducir en el panel.
     * No guarda el binario en disco ni en BD: solo retransmite la URL remota (Kommo).
     */
    public function media(): void {
        $this->requireRoles(['asesor', 'coordinador', 'administrador']);
        $msgId = (int)($_GET['mensaje_id'] ?? 0);
        if ($msgId <= 0) {
            http_response_code(400);
            header('Content-Type: text/plain; charset=UTF-8');
            echo 'mensaje_id requerido';
            exit;
        }
        $msg = $this->msgModel->getById($msgId);
        if (!$msg || (empty($msg['media_url']) && empty($msg['media_id']))) {
            http_response_code(404);
            header('Content-Type: text/plain; charset=UTF-8');
            echo 'Adjunto no encontrado';
            exit;
        }
        $conv = $this->convModel->getById((int)$msg['conversacion_id']);
        if (!$conv) {
            http_response_code(404);
            header('Content-Type: text/plain; charset=UTF-8');
            echo 'Conversación no encontrada';
            exit;
        }
        $role = $_SESSION['user_role'] ?? '';
        if ($role === 'asesor' && !$this->asesorPuedeLeerCliente((int)($conv['cliente_id'] ?? 0))) {
            http_response_code(403);
            header('Content-Type: text/plain; charset=UTF-8');
            echo 'Sin permiso';
            exit;
        }

        if (($conv['provider'] ?? waProvider()) === 'meta' && !empty($msg['media_id'])) {
            $download = $this->metaGateway->downloadMedia((string)$msg['media_id']);
            if (empty($download['ok'])) {
                http_response_code(502);
                header('Content-Type: text/plain; charset=UTF-8');
                echo (string)($download['error'] ?? 'No se pudo obtener el adjunto de Meta');
                exit;
            }
            $bin = (string)($download['body'] ?? '');
            $ctype = (string)($download['content_type'] ?? 'application/octet-stream');
            if (ob_get_level()) {
                ob_clean();
            }
            header('Content-Type: ' . $ctype);
            header('Content-Length: ' . strlen($bin));
            header('Cache-Control: private, max-age=300');
            header('X-Content-Type-Options: nosniff');
            header('Content-Disposition: inline');
            echo $bin;
            exit;
        }

        $url = trim((string)$msg['media_url']);
        $parts = parse_url($url);
        if (!$parts || empty($parts['scheme']) || !in_array(strtolower($parts['scheme']), ['http', 'https'], true)) {
            http_response_code(400);
            header('Content-Type: text/plain; charset=UTF-8');
            echo 'URL de media inválida';
            exit;
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_TIMEOUT => 45,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_USERAGENT => 'EmerCobranza-WA-Media/1.0',
        ]);
        $bin = curl_exec($ch);
        $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $ctype = (string)curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($bin === false || $http < 200 || $http >= 300) {
            http_response_code(502);
            header('Content-Type: text/plain; charset=UTF-8');
            echo 'No se pudo obtener el adjunto' . ($err ? ": {$err}" : " (HTTP {$http})");
            exit;
        }

        $name = trim((string)($msg['media_name'] ?? ''));
        $tipo = strtolower(trim((string)($msg['tipo'] ?? '')));
        $lowerName = strtolower($name);
        $lowerUrl = strtolower((string)strtok($url, '?'));
        $isPdf = str_ends_with($lowerName, '.pdf') || str_ends_with($lowerUrl, '.pdf')
            || stripos($ctype, 'pdf') !== false;
        $isAudio = in_array($tipo, ['voice', 'audio', 'ptt'], true)
            || preg_match('/\.(ogg|mp3|opus|m4a|wav|aac|amr|oga)(\?|$)/i', $lowerUrl)
            || preg_match('/\.(ogg|mp3|opus|m4a|wav|aac|amr|oga)$/i', $lowerName)
            || stripos($ctype, 'audio/') !== false;

        if ($isPdf) {
            $ctype = 'application/pdf';
        } elseif ($isAudio) {
            if ($ctype === '' || stripos($ctype, 'text/html') !== false || stripos($ctype, 'octet-stream') !== false) {
                if (preg_match('/\.ogg|\.oga|\.opus/i', $lowerUrl . $lowerName) || in_array($tipo, ['voice', 'ptt'], true)) {
                    $ctype = 'audio/ogg';
                } elseif (preg_match('/\.mp3/i', $lowerUrl . $lowerName)) {
                    $ctype = 'audio/mpeg';
                } elseif (preg_match('/\.m4a|\.aac/i', $lowerUrl . $lowerName)) {
                    $ctype = 'audio/mp4';
                } elseif (preg_match('/\.wav/i', $lowerUrl . $lowerName)) {
                    $ctype = 'audio/wav';
                } else {
                    $ctype = 'audio/ogg';
                }
            }
        } elseif ($ctype === '' || stripos($ctype, 'text/html') !== false) {
            $ctype = 'application/octet-stream';
        }

        if (ob_get_level()) {
            ob_clean();
        }
        header('Content-Type: ' . $ctype);
        header('Content-Length: ' . strlen($bin));
        header('Cache-Control: private, max-age=300');
        header('X-Content-Type-Options: nosniff');
        header('Accept-Ranges: bytes');
        if ($name !== '') {
            $safe = str_replace(['"', "\r", "\n"], '', $name);
            header('Content-Disposition: inline; filename="' . $safe . '"');
        } else {
            header('Content-Disposition: inline');
        }
        echo $bin;
        exit;
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
                if (!$this->asesorPuedeLeerCliente((int)($conv['cliente_id'] ?? $clienteId))) {
                    $this->jsonOut(['success' => false, 'error' => 'Sin acceso a la base de este cliente'], 403);
                }
                if (!$this->asesorPuedeEnviar($conv)) {
                    $this->jsonOut([
                        'success' => false,
                        'error' => 'Otro asesor tiene la notificación activa de este chat. Solo lectura hasta que esté offline o te lo asignen.',
                    ], 403);
                }
                $this->claimNotificacion((int)$conv['id'], $this->currentAsesorId(), true);
                $conv = $this->convModel->getById((int)$conv['id']) ?: $conv;
            }

            $this->releaseSessionLock();

            $status = 'pendiente_envio';
            $externalMsgId = null;
            $kommoMsgId = null;
            $waActivo = $conv['wa_activo'] ?? 'desconocido';
            $send = null;

            if (waProvider() === 'meta') {
                if (!metaEnabled()) {
                    $send = ['ok' => false, 'error' => 'Meta Cloud API no está configurada'];
                } else {
                    $lastInbound = $this->msgModel->lastInboundAt((int)$conv['id']);
                    $insideWindow = $lastInbound !== null
                        && strtotime($lastInbound) >= (time() - 24 * 60 * 60);
                    if (!$insideWindow) {
                        $send = [
                            'ok' => false,
                            'error' => 'La ventana de atención de 24 horas está cerrada. Envía una plantilla aprobada para iniciar o reabrir la conversación.',
                        ];
                    } else {
                        $send = $this->metaGateway->sendText((string)$conv['telefono_e164'], $texto);
                    }
                }
                if (!empty($send['ok'])) {
                    $status = 'enviado';
                    $externalMsgId = $send['external_message_id'] ?? null;
                    $waActivo = 'si';
                } else {
                    $status = 'error_envio';
                    if (!empty($send['invalid_number'])) {
                        $waActivo = 'no';
                    }
                }
            } elseif (kommoEnabled()) {
                $send = $this->enviarViaKommo($conv, $texto);
                if (!empty($send['ok'])) {
                    $status = 'enviado';
                    $kommoMsgId = $send['kommo_message_id'] ?? null;
                    $externalMsgId = $kommoMsgId;
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
                'external_message_id' => $externalMsgId,
                'status' => $status,
            ]);
            $this->convModel->touchPreview((int)$conv['id'], $texto);
            $convUpdates = [];
            if ($waActivo !== ($conv['wa_activo'] ?? '')) {
                $convUpdates['wa_activo'] = $waActivo;
            }
            if (waProvider() === 'meta' && $status === 'enviado') {
                $convUpdates['provider'] = 'meta';
                $convUpdates['meta_phone_number_id'] = META_PHONE_NUMBER_ID;
            }
            if ($convUpdates) {
                $this->convModel->update((int)$conv['id'], $convUpdates);
            }

            // Reconsultar entrega en Kommo (a veces pasa de sent → delivered/error en segundos)
            if ($status === 'enviado' && waProvider() === 'kommo' && kommoEnabled()) {
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
                'meta_enabled' => metaEnabled(),
                'provider' => waProvider(),
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
     * Si no hay contacto, lo crea. Si no hay talk, intenta abrirlo vía Chats API (si hay canal).
     */
    private function resolverTalkKommoPorTelefono(string $telefonoE164, string $contactName = ''): array {
        $e164 = kommoNormalizePhoneE164($telefonoE164);
        if (!$e164) {
            return ['ok' => false, 'error' => 'Teléfono inválido para vincular con Kommo', 'invalid_number' => true];
        }

        $ensured = $this->ensureKommoContact($e164, $contactName);
        if (empty($ensured['ok'])) {
            return [
                'ok' => false,
                'error' => $ensured['error'] ?? 'No se pudo resolver/crear contacto en Kommo',
                'invalid_number' => false,
            ];
        }
        $contactId = (int)$ensured['contact_id'];

        $talk = $this->findTalkByContactId($contactId);
        if (!empty($talk['ok'])) {
            return $talk;
        }

        // Intento crear/abrir chat WABA si hay canal Chats API configurado
        $created = $this->tryCreateTalkViaChatsApi($contactId, $e164, $contactName !== '' ? $contactName : $e164);
        if (!empty($created['ok'])) {
            // Reconsultar talks (Kommo puede tardar un instante)
            for ($i = 0; $i < 4; $i++) {
                usleep(400000);
                $talk = $this->findTalkByContactId($contactId);
                if (!empty($talk['ok'])) {
                    $talk['created_via'] = 'chats_api';
                    return $talk;
                }
            }
            if (!empty($created['chat_id'])) {
                return [
                    'ok' => true,
                    'talk_id' => (string)($created['talk_id'] ?? ''),
                    'chat_id' => (string)$created['chat_id'],
                    'contact_id' => $contactId,
                    'created_via' => 'chats_api',
                ];
            }
        }

        $hintCanal = (KOMMO_CHANNEL_ID === '' || KOMMO_CHANNEL_SECRET === '')
            ? ' Falta registrar canal Chats API en Kommo (KOMMO_CHANNEL_ID + KOMMO_CHANNEL_SECRET).'
            : '';
        $extra = !empty($created['error']) ? (' Detalle: ' . $created['error']) : '';

        return [
            'ok' => false,
            'error' => 'El contacto ya está en Kommo (id ' . $contactId . ') pero aún no tiene conversación WhatsApp (talk). '
                . 'Para el primer mensaje: envía la plantilla Utility una vez desde Kommo (contacto → WhatsApp → plantilla), '
                . 'o pide al cliente que escriba al Business; después el CRM gestiona solo.'
                . $hintCanal . $extra,
            'invalid_number' => false,
            'contact_id' => $contactId,
            'needs_first_talk' => true,
        ];
    }

    /**
     * @return array{ok:bool,contact_id?:int,created?:bool,error?:string}
     */
    private function ensureKommoContact(string $e164, string $name = ''): array {
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
                foreach (($c['custom_fields_values'] ?? []) as $cf) {
                    if (($cf['field_code'] ?? '') !== 'PHONE') {
                        continue;
                    }
                    foreach (($cf['values'] ?? []) as $v) {
                        $p = (string)($v['value'] ?? '');
                        if (kommoPhoneLast10($p) === $last10 || kommoNormalizePhoneE164($p) === $e164) {
                            return ['ok' => true, 'contact_id' => (int)$c['id'], 'created' => false];
                        }
                    }
                }
            }
            if ($contactId === null && count($contacts) === 1 && strlen((string)$q) >= 7) {
                $contactId = (int)$contacts[0]['id'];
            }
        }
        if ($contactId) {
            return ['ok' => true, 'contact_id' => $contactId, 'created' => false];
        }

        $display = trim($name) !== '' ? trim($name) : $e164;
        $payload = [[
            'name' => $display,
            'first_name' => explode(' ', $display)[0],
            'custom_fields_values' => [[
                'field_code' => 'PHONE',
                'values' => [['value' => $e164, 'enum_code' => 'WORK']],
            ]],
        ]];
        [$code, $body] = $this->kommoApiRequest('POST', '/api/v4/contacts', $payload);
        $data = json_decode($body, true);
        $newId = (int)($data['_embedded']['contacts'][0]['id'] ?? 0);
        if ($code >= 200 && $code < 300 && $newId > 0) {
            return ['ok' => true, 'contact_id' => $newId, 'created' => true];
        }
        $detail = is_array($data) ? (string)($data['detail'] ?? $data['title'] ?? $body) : (string)$body;
        return ['ok' => false, 'error' => "No se pudo crear contacto en Kommo (HTTP {$code}): {$detail}"];
    }

    /**
     * @return array{ok:bool,talk_id?:string,chat_id?:string,contact_id?:int,error?:string}
     */
    private function findTalkByContactId(int $contactId): array {
        [$code, $body] = $this->kommoApiRequest(
            'GET',
            '/api/v4/talks?filter[contact_id]=' . $contactId . '&limit=20'
        );
        if ($code === 204) {
            return ['ok' => false, 'error' => 'Sin talks', 'contact_id' => $contactId];
        }
        if ($code < 200 || $code >= 300) {
            return ['ok' => false, 'error' => "No se pudieron listar talks de Kommo (HTTP {$code})", 'contact_id' => $contactId];
        }
        $data = json_decode($body, true);
        $talks = $data['_embedded']['talks'] ?? [];
        if (empty($talks)) {
            return ['ok' => false, 'error' => 'Sin talks', 'contact_id' => $contactId];
        }
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
            return ['ok' => false, 'error' => 'Talk de Kommo sin id', 'contact_id' => $contactId];
        }
        return [
            'ok' => true,
            'talk_id' => $talkId,
            'chat_id' => (string)($best['chat_id'] ?? ''),
            'contact_id' => $contactId,
        ];
    }

    /**
     * Crea chat vía Chats API (amojo) si hay channel_id/secret (y opcionalmente scope_id).
     * Sin canal registrado en Kommo Support → ORIGIN_NOT_REGISTERED / Channel must be linked.
     *
     * @return array{ok:bool,chat_id?:string,talk_id?:string,error?:string}
     */
    private function tryCreateTalkViaChatsApi(int $contactId, string $e164, string $userName): array {
        $channelId = trim((string)KOMMO_CHANNEL_ID);
        $channelSecret = trim((string)KOMMO_CHANNEL_SECRET);
        if ($channelId === '' || $channelSecret === '') {
            return [
                'ok' => false,
                'error' => 'Chats API no configurada (CHANNEL_ID/SECRET vacíos). El token OAuth actual no puede crear talks WABA nuevos.',
            ];
        }

        $scopeId = trim((string)KOMMO_SCOPE_ID);
        if ($scopeId === '') {
            $amojoAccount = $this->getKommoAmojoId();
            if ($amojoAccount === '') {
                return ['ok' => false, 'error' => 'No se pudo obtener amojo_id de la cuenta Kommo'];
            }
            $conn = $this->kommoChatsApiRequest(
                'POST',
                '/v2/origin/custom/' . rawurlencode($channelId) . '/connect',
                [
                    'account_id' => $amojoAccount,
                    'title' => 'Emer CRM WhatsApp',
                    'hook_api_version' => 'v2',
                ],
                $channelSecret
            );
            $scopeId = (string)($conn['json']['scope_id'] ?? '');
            if ($conn['code'] < 200 || $conn['code'] >= 300 || $scopeId === '') {
                return [
                    'ok' => false,
                    'error' => 'No se pudo conectar canal Chats API: ' . ($conn['body'] ?? ''),
                ];
            }
        }

        $conversationId = preg_replace('/\D+/', '', $e164) ?: ('crm' . $contactId);
        $create = $this->kommoChatsApiRequest(
            'POST',
            '/v2/origin/custom/' . rawurlencode($scopeId) . '/chats',
            [
                'conversation_id' => $conversationId,
                'user' => [
                    'id' => $conversationId,
                    'name' => $userName !== '' ? $userName : $e164,
                    'phone' => $e164,
                    'client_id' => (string)$contactId,
                ],
            ],
            $channelSecret
        );
        if ($create['code'] < 200 || $create['code'] >= 300) {
            return [
                'ok' => false,
                'error' => 'Create chat HTTP ' . $create['code'] . ': ' . ($create['body'] ?? ''),
            ];
        }
        $chatId = (string)($create['json']['id'] ?? $create['json']['chat_id'] ?? '');

        // Vincular chat ↔ contacto (API v4)
        if ($chatId !== '') {
            $this->kommoApiRequest('POST', '/api/v4/contacts/chats', [[
                'contact_id' => $contactId,
                'chat_id' => $chatId,
                'request_id' => 'crm-' . $contactId,
            ]]);
        }

        return [
            'ok' => true,
            'chat_id' => $chatId,
            'scope_id' => $scopeId,
        ];
    }

    private function getKommoAmojoId(): string {
        [$code, $body] = $this->kommoApiRequest('GET', '/api/v4/account?with=amojo_id');
        if ($code < 200 || $code >= 300) {
            return '';
        }
        $data = json_decode($body, true);
        return (string)($data['amojo_id'] ?? '');
    }

    /**
     * Request firmado a amojo.kommo.com (Chats API).
     * @return array{code:int,body:string,json:?array}
     */
    private function kommoChatsApiRequest(string $method, string $path, ?array $jsonBody, string $channelSecret): array {
        $json = $jsonBody === null ? '' : (string)json_encode($jsonBody, JSON_UNESCAPED_UNICODE);
        $contentType = 'application/json';
        $date = gmdate('D, d M Y H:i:s O');
        $contentMd5 = base64_encode(md5($json, true));
        $check = strtoupper($method) . "\n"
            . $contentMd5 . "\n"
            . $contentType . "\n"
            . $date . "\n"
            . $path;
        $sig = hash_hmac('sha1', $check, $channelSecret);
        $ch = curl_init('https://amojo.kommo.com' . $path);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Date: ' . $date,
                'Content-Type: ' . $contentType,
                'Content-MD5: ' . $contentMd5,
                'X-Signature: ' . $sig,
            ],
            CURLOPT_POSTFIELDS => $json === '' ? null : $json,
            CURLOPT_TIMEOUT => 30,
        ]);
        $resp = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $body = $resp === false ? '' : (string)$resp;
        $decoded = json_decode($body, true);
        return [
            'code' => $code,
            'body' => $body,
            'json' => is_array($decoded) ? $decoded : null,
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
    private function sincronizarMensajesKommo(array $conv, int $msgLimit = 100): int {
        $talkId = trim((string)($conv['kommo_talk_id'] ?? ''));
        if ($talkId === '') {
            return 0;
        }
        $msgLimit = max(5, min(100, $msgLimit));

        [$code, $body] = $this->kommoApiRequest(
            'GET',
            '/api/v4/talks/' . rawurlencode($talkId) . '/messages?limit=' . $msgLimit
        );
        if ($code < 200 || $code >= 300) {
            return 0;
        }

        $data = json_decode($body, true);
        $remote = $data['_embedded']['messages'] ?? [];
        if (!$remote) {
            return 0;
        }

        // Kommo devuelve más recientes primero; insertar cronológicamente.
        usort($remote, static function (array $a, array $b): int {
            return ((int)($a['created_at'] ?? 0)) <=> ((int)($b['created_at'] ?? 0));
        });

        $newInbound = 0;
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
                    ? trim((string)($attachment['link'] ?? ''))
                    : '';
                $mediaName = is_array($attachment)
                    ? trim((string)($attachment['file_name'] ?? ''))
                    : '';
                // Tipo de mensaje: preferir el declarado; si no, deducir del attachment.
                $tipo = (string)($message['message_type'] ?? '');
                if ($tipo === '' && is_array($attachment)) {
                    $tipo = (string)($attachment['type'] ?? 'file');
                }
                if ($tipo === '') {
                    $tipo = 'text';
                }
                $createdAt = !empty($message['created_at'])
                    ? date('Y-m-d H:i:s', (int)$message['created_at'])
                    : date('Y-m-d H:i:s');

                try {
                    $this->msgModel->create([
                        'conversacion_id' => (int)$conv['id'],
                        'direccion' => $direction,
                        'tipo' => $tipo,
                        'cuerpo' => (string)($message['text'] ?? ''),
                        'media_url' => $mediaUrl !== '' ? $mediaUrl : null,
                        'media_name' => $mediaName !== '' ? $mediaName : null,
                        'kommo_message_id' => $messageId,
                        'status' => $status,
                        'created_at' => $createdAt,
                    ]);
                    if ($direction === 'in') {
                        $age = time() - (int)($message['created_at'] ?? time());
                        // Solo notificar/encolar mensajes recientes (evita flood al primer sync histórico)
                        if ($age >= 0 && $age <= 900) {
                            $this->convModel->incrementNoLeidos((int)$conv['id']);
                            $this->onInboundMessage($conv);
                            $newInbound++;
                        }
                    }
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
                $preview = trim((string)($message['text'] ?? ''));
                if ($preview === '') {
                    $att = $message['attachment'] ?? null;
                    $preview = $this->previewParaMedia(
                        (string)($message['message_type'] ?? ''),
                        is_array($att) ? (string)($att['type'] ?? '') : '',
                        is_array($att) ? (string)($att['file_name'] ?? '') : ''
                    );
                }
                $lastPreview = $preview;
            }
        }

        if ($lastPreview !== null && $lastPreview !== '') {
            $this->convModel->touchPreview(
                (int)$conv['id'],
                $lastPreview,
                $lastTimestamp ? date('Y-m-d H:i:s', (int)$lastTimestamp) : null
            );
        }
        return $newInbound;
    }

    /**
     * Sincroniza un subconjunto rotativo de burbujas del asesor (presupuesto de tiempo).
     * @return array{synced:int,ms:int,new_inbound:int}
     */
    private function sincronizarBurbujasAsesor(string $asesorId, int $maxTalks = 3, int $budgetMs = 2500): array {
        $t0 = microtime(true);
        $maxTalks = max(1, min(5, $maxTalks));
        $candidates = $this->convModel->listBubblesActivas($asesorId, 10);
        $withTalk = [];
        foreach ($candidates as $c) {
            if (!empty($c['kommo_talk_id'])) {
                $withTalk[] = $c;
            }
        }
        if (!$withTalk) {
            return ['synced' => 0, 'ms' => 0, 'new_inbound' => 0];
        }
        // Rotación cada 15s para no martillar siempre las mismas 3
        $n = count($withTalk);
        $offset = ((int)floor(time() / 15)) % $n;
        $synced = 0;
        $newInbound = 0;
        for ($i = 0; $i < $n && $synced < $maxTalks; $i++) {
            $elapsedMs = (microtime(true) - $t0) * 1000;
            if ($elapsedMs >= $budgetMs) {
                break;
            }
            $conv = $withTalk[($offset + $i) % $n];
            try {
                $newInbound += $this->sincronizarMensajesKommo($conv, 30);
                $synced++;
            } catch (Throwable $e) {
                error_log('sincronizarBurbujasAsesor conv#' . ($conv['id'] ?? '?') . ': ' . $e->getMessage());
            }
        }
        return [
            'synced' => $synced,
            'ms' => (int)round((microtime(true) - $t0) * 1000),
            'new_inbound' => $newInbound,
        ];
    }

    /**
     * Etiqueta corta para vista previa de mensajes multimedia (sin texto).
     */
    private function previewParaMedia(string $messageType, string $attachmentType, string $fileName): string {
        $t = strtolower($attachmentType !== '' ? $attachmentType : $messageType);
        $label = '[Archivo]';
        if (in_array($t, ['picture', 'image'], true)) {
            $label = '[Imagen]';
        } elseif ($t === 'video') {
            $label = '[Video]';
        } elseif (in_array($t, ['voice', 'audio'], true)) {
            $label = '[Audio]';
        } elseif ($t === 'sticker') {
            $label = '[Sticker]';
        } elseif ($t === 'location') {
            $label = '[Ubicación]';
        } elseif ($t === 'contact') {
            $label = '[Contacto]';
        }
        return $fileName !== '' ? $label . ' ' . $fileName : $label;
    }

    public function webhookKommo(): void {
        $method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));

        // Handshake oficial Meta.
        if ($method === 'GET' && isset($_GET['hub_mode'], $_GET['hub_verify_token'], $_GET['hub_challenge'])) {
            $valid = (string)$_GET['hub_mode'] === 'subscribe'
                && META_VERIFY_TOKEN !== ''
                && hash_equals(META_VERIFY_TOKEN, (string)$_GET['hub_verify_token']);
            if (!$valid) {
                http_response_code(403);
                echo 'Token de verificación inválido';
                exit;
            }
            if (ob_get_level()) {
                ob_clean();
            }
            http_response_code(200);
            header('Content-Type: text/plain; charset=UTF-8');
            echo (string)$_GET['hub_challenge'];
            exit;
        }

        // Health-check de navegador/Kommo.
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

        $rawBody = file_get_contents('php://input');
        $decodedBody = json_decode((string)$rawBody, true);
        if (is_array($decodedBody) && ($decodedBody['object'] ?? '') === 'whatsapp_business_account') {
            $signature = (string)($_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '');
            if (!$this->metaGateway->verifyWebhookSignature((string)$rawBody, $signature)) {
                $this->jsonOut(['success' => false, 'error' => 'Firma Meta inválida'], 401);
            }
            $result = $this->procesarWebhookMeta($decodedBody);
            $this->jsonOut(['success' => true] + $result);
        }

        // Webhook legacy Kommo: validar secret opcional.
        $secret = KOMMO_WEBHOOK_SECRET;
        if ($secret !== '') {
            $hdr = $_SERVER['HTTP_X_WA_WEBHOOK_SECRET'] ?? '';
            if (!hash_equals($secret, $hdr)) {
                $this->jsonOut(['success' => false, 'error' => 'Firma inválida'], 401);
            }
        }

        $body = is_array($decodedBody) ? $decodedBody : ($_POST ?: []);
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
            if ($direction === 'in') {
                $this->onInboundMessage($conv);
            }
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
            if ($direction === 'in') {
                $this->onInboundMessage($conv);
            }
        }

        $msgId = $this->msgModel->create([
            'conversacion_id' => (int)$conv['id'],
            'direccion' => $direction,
            'tipo' => 'text',
            'cuerpo' => $text !== '' ? $text : null,
            'kommo_message_id' => $kommoMsgId !== '' ? $kommoMsgId : null,
            'status' => $direction === 'in' ? 'recibido' : 'enviado',
        ]);

        if ($direction === 'in') {
            $this->dispatchColaAsignacion();
        }

        $this->jsonOut([
            'success' => true,
            'conversacion_id' => (int)$conv['id'],
            'cliente_id' => $conv['cliente_id'] ? (int)$conv['cliente_id'] : null,
            'mensaje_id' => $msgId,
            'estado' => $conv['estado'],
        ]);
    }

    /**
     * Procesa el payload oficial de WhatsApp Cloud API.
     * Meta reintenta webhooks: external_message_id garantiza idempotencia.
     */
    private function procesarWebhookMeta(array $payload): array {
        $received = 0;
        $statuses = 0;
        foreach (($payload['entry'] ?? []) as $entry) {
            foreach (($entry['changes'] ?? []) as $change) {
                $value = $change['value'] ?? [];
                if (!is_array($value)) {
                    continue;
                }
                $phoneNumberId = (string)($value['metadata']['phone_number_id'] ?? META_PHONE_NUMBER_ID);
                foreach (($value['statuses'] ?? []) as $status) {
                    $externalId = (string)($status['id'] ?? '');
                    $state = strtolower((string)($status['status'] ?? ''));
                    if ($externalId !== '' && in_array($state, ['sent', 'delivered', 'read', 'failed'], true)) {
                        $this->msgModel->updateStatusByExternalId($externalId, $state);
                        $statuses++;
                    }
                }
                foreach (($value['messages'] ?? []) as $message) {
                    if (!is_array($message)) {
                        continue;
                    }
                    $externalId = (string)($message['id'] ?? '');
                    if ($externalId !== '' && $this->msgModel->findByExternalMessageId($externalId)) {
                        continue;
                    }
                    $phone = kommoNormalizePhoneE164((string)($message['from'] ?? ''));
                    if (!$phone) {
                        continue;
                    }
                    $type = strtolower((string)($message['type'] ?? 'text'));
                    $content = $this->contenidoMensajeMeta($message, $type);
                    $preview = $content['text'] !== ''
                        ? $content['text']
                        : $this->previewParaMedia($type, $type, (string)$content['media_name']);
                    $conv = $this->convModel->getByTelefonoE164($phone);
                    if (!$conv) {
                        $clienteId = $this->convModel->findClienteIdByPhone($phone);
                        $convId = $this->convModel->create([
                            'cliente_id' => $clienteId,
                            'telefono_e164' => $phone,
                            'provider' => 'meta',
                            'meta_phone_number_id' => $phoneNumberId,
                            'estado' => $clienteId ? 'abierta' : 'sin_cliente',
                            'wa_activo' => 'si',
                            'no_leidos' => 1,
                            'ultimo_mensaje_at' => date('Y-m-d H:i:s'),
                            'ultimo_preview' => $preview,
                        ]);
                        $conv = $this->convModel->getById($convId);
                    } else {
                        $updates = [
                            'provider' => 'meta',
                            'meta_phone_number_id' => $phoneNumberId,
                            'wa_activo' => 'si',
                        ];
                        if (empty($conv['cliente_id'])) {
                            $clienteId = $this->convModel->findClienteIdByPhone($phone);
                            if ($clienteId) {
                                $updates['cliente_id'] = $clienteId;
                                $updates['estado'] = 'abierta';
                            }
                        }
                        $this->convModel->update((int)$conv['id'], $updates);
                        $this->convModel->touchPreview(
                            (int)$conv['id'],
                            $preview,
                            !empty($message['timestamp'])
                                ? date('Y-m-d H:i:s', (int)$message['timestamp'])
                                : null
                        );
                        $this->convModel->incrementNoLeidos((int)$conv['id']);
                        $conv = $this->convModel->getById((int)$conv['id']);
                    }
                    if (!$conv) {
                        continue;
                    }
                    $this->msgModel->create([
                        'conversacion_id' => (int)$conv['id'],
                        'direccion' => 'in',
                        'tipo' => $type,
                        'cuerpo' => $content['text'] !== '' ? $content['text'] : null,
                        'media_id' => $content['media_id'] ?: null,
                        'media_name' => $content['media_name'] ?: null,
                        'external_message_id' => $externalId !== '' ? $externalId : null,
                        'status' => 'recibido',
                        'created_at' => !empty($message['timestamp'])
                            ? date('Y-m-d H:i:s', (int)$message['timestamp'])
                            : null,
                    ]);
                    $this->onInboundMessage($conv);
                    $received++;
                }
            }
        }
        if ($received > 0) {
            $this->dispatchColaAsignacion();
        }
        return ['messages' => $received, 'statuses' => $statuses];
    }

    /** @return array{text:string,media_id:string,media_name:string} */
    private function contenidoMensajeMeta(array $message, string $type): array {
        $text = '';
        $mediaId = '';
        $mediaName = '';
        if ($type === 'text') {
            $text = (string)($message['text']['body'] ?? '');
        } elseif ($type === 'button') {
            $text = (string)($message['button']['text'] ?? $message['button']['payload'] ?? '');
        } elseif ($type === 'interactive') {
            $reply = $message['interactive']['button_reply']
                ?? $message['interactive']['list_reply']
                ?? [];
            $text = (string)($reply['title'] ?? $reply['id'] ?? '');
        } elseif (in_array($type, ['image', 'audio', 'video', 'document', 'sticker'], true)) {
            $media = is_array($message[$type] ?? null) ? $message[$type] : [];
            $mediaId = (string)($media['id'] ?? '');
            $mediaName = (string)($media['filename'] ?? '');
            $text = (string)($media['caption'] ?? '');
        } elseif ($type === 'location') {
            $latitude = (string)($message['location']['latitude'] ?? '');
            $longitude = (string)($message['location']['longitude'] ?? '');
            $text = trim("Ubicación {$latitude}, {$longitude}");
        } elseif ($type === 'contacts') {
            $text = '[Contacto]';
        } else {
            $text = '[' . ($type !== '' ? $type : 'mensaje') . ']';
        }
        return ['text' => $text, 'media_id' => $mediaId, 'media_name' => $mediaName];
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
            $fields['asesor_notificacion_id'] = $asesorId;
        }
        $this->convModel->update($convId, $fields);
        $asesorAsignacion = trim((string)($asesorId ?: ($conv['asesor_id'] ?? '')));
        if ($asesorAsignacion !== '' && $asesorAsignacion !== '0') {
            $stmt = $this->pdo->prepare(
                'INSERT INTO wa_asignaciones (conversacion_id, asesor_id, asignado_por) VALUES (?, ?, ?)'
            );
            $stmt->execute([
                $convId,
                $asesorAsignacion,
                $this->currentAsesorId(),
            ]);
        }
        $this->jsonOut([
            'success' => true,
            'conversacion' => $this->convModel->getById($convId),
            'open_url' => 'index.php?action=gestionar_cliente&id=' . $clienteId . '&wa=' . $convId,
        ]);
    }

    // ─── Campañas masivas / plantillas / dispatcher ─────────────────────────

    public function vistaCoordWhatsapp(): void {
        $role = $_SESSION['user_role'] ?? '';
        if ($role === 'cordinador') {
            $role = 'coordinador';
        }
        if (!in_array($role, ['coordinador', 'administrador'], true)) {
            header('Location: index.php?action=dashboard');
            exit;
        }
        try {
            $cargaModel = new CargaExcelModel($this->pdo);
            $bases = $cargaModel->getCargasByCoordinador((string)$_SESSION['user_id'], true);
            if ($role === 'administrador') {
                $bases = $cargaModel->getCargasActivas();
            }
            $campanas = $this->campanaMasivaModel->listByCoordinador((string)$_SESSION['user_id'], 20);
        } catch (PDOException $e) {
            error_log('vistaCoordWhatsapp DB: ' . $e->getMessage());
            $page_title = 'WhatsApp masivo';
            $wa_schema_error = 'Faltan tablas de WhatsApp en la base de datos. '
                . 'Ejecute: php scripts/ejecutar_migracion_wa.php';
            $bases = [];
            $campanas = [];
            require __DIR__ . '/../views/coordinador_whatsapp.php';
            return;
        }
        $page_title = 'WhatsApp masivo';
        require __DIR__ . '/../views/coordinador_whatsapp.php';
    }

    public function templatesList(): void {
        $this->requireRoles(['asesor', 'coordinador', 'administrador']);
        $templates = waProvider() === 'meta'
            ? $this->metaGateway->listTemplates()
            : $this->listarPlantillasKommo();
        $this->jsonOut([
            'success' => true,
            'templates' => $templates,
            'kommo_enabled' => kommoEnabled(),
            'meta_enabled' => metaEnabled(),
            'provider' => waProvider(),
            'hint' => empty($templates)
                ? 'No hay plantillas visibles. Revisa las credenciales y el WABA en Meta.'
                : null,
        ]);
    }

    public function templatesCrear(): void {
        $this->requireRoles(['coordinador', 'administrador']);
        if (waProvider() !== 'meta') {
            $this->jsonOut([
                'success' => false,
                'error' => 'La creación directa de plantillas requiere WA_PROVIDER=meta',
            ], 409);
        }
        $body = $this->readJsonBody();
        $result = $this->metaGateway->createTemplate([
            'name' => $body['name'] ?? '',
            'language' => $body['language'] ?? 'es',
            'category' => $body['category'] ?? 'UTILITY',
            'body' => $body['body'] ?? '',
            'examples' => $body['examples'] ?? [],
        ]);
        if (empty($result['ok'])) {
            $this->jsonOut([
                'success' => false,
                'error' => (string)($result['error'] ?? 'No se pudo enviar la plantilla a Meta'),
            ], 502);
        }
        $this->jsonOut([
            'success' => true,
            'template' => $result['template'] ?? null,
            'message' => 'Plantilla enviada a revisión de Meta',
        ]);
    }

    public function templatesSync(): void {
        $this->templatesList();
    }

    /**
     * Asesor/coord: envía plantilla WABA para iniciar o reabrir conversación con un cliente.
     */
    public function enviarPlantilla(): void {
        $this->requireRoles(['asesor', 'coordinador', 'administrador']);
        $body = $this->readJsonBody();
        $clienteId = (int)($body['cliente_id'] ?? 0);
        $convId = (int)($body['conversacion_id'] ?? 0);
        $telefono = trim((string)($body['telefono'] ?? ''));
        $templateId = trim((string)($body['template_id'] ?? $body['template_external_id'] ?? ''));
        $templateName = trim((string)($body['template_name'] ?? ''));
        $templateLang = trim((string)($body['template_language'] ?? 'es'));
        $paramsIn = $body['params'] ?? [];
        if (!is_array($paramsIn)) {
            $paramsIn = [];
        }

        if ($clienteId <= 0) {
            $this->jsonOut(['success' => false, 'error' => 'cliente_id requerido'], 400);
        }
        if ($templateId === '' && $templateName === '') {
            $this->jsonOut(['success' => false, 'error' => 'Selecciona una plantilla'], 400);
        }

        $role = $_SESSION['user_role'] ?? '';
        if ($role === 'asesor' && !$this->asesorPuedeLeerCliente($clienteId)) {
            $this->jsonOut(['success' => false, 'error' => 'Sin acceso a la base de este cliente'], 403);
        }

        if ($telefono === '') {
            $telefonos = $this->convModel->getTelefonosPerfilCliente($clienteId);
            $best = $this->convModel->pickBestTelefonoE164($clienteId, $telefonos);
            $telefono = (string)($best['raw'] ?? ($telefonos[0]['raw'] ?? ''));
        }
        if ($telefono === '') {
            $this->jsonOut(['success' => false, 'error' => 'El cliente no tiene teléfono en el perfil'], 400);
        }

        try {
            if ($convId > 0) {
                $conv = $this->convModel->getById($convId);
                if (!$conv || (int)($conv['cliente_id'] ?? 0) !== $clienteId) {
                    $this->jsonOut(['success' => false, 'error' => 'Conversación no pertenece al cliente'], 403);
                }
            } else {
                $conv = $this->convModel->getOrCreateForCliente($clienteId, $telefono, null);
            }
            if (!$conv) {
                $this->jsonOut(['success' => false, 'error' => 'No se pudo abrir conversación'], 400);
            }

            if ($role === 'asesor') {
                if (!$this->asesorPuedeEnviar($conv)) {
                    $this->jsonOut([
                        'success' => false,
                        'error' => 'Otro asesor tiene la notificación activa de este chat.',
                    ], 403);
                }
                $this->claimNotificacion((int)$conv['id'], $this->currentAsesorId(), true);
                $conv = $this->convModel->getById((int)$conv['id']) ?: $conv;
            }

            // Params numerados o lista ordenada
            $params = [];
            if ($paramsIn !== [] && array_keys($paramsIn) !== range(0, count($paramsIn) - 1)) {
                ksort($paramsIn, SORT_NATURAL);
                foreach ($paramsIn as $v) {
                    $params[] = (string)$v;
                }
            } else {
                foreach ($paramsIn as $v) {
                    $params[] = (string)$v;
                }
            }
            // Defaults útiles si la plantilla espera {{1}} {{2}}
            if (!$params) {
                $cli = $this->pdo->prepare('SELECT nombre, cedula FROM clientes WHERE id_cliente = ? LIMIT 1');
                $cli->execute([$clienteId]);
                $row = $cli->fetch(PDO::FETCH_ASSOC) ?: [];
                $nombre = trim((string)($row['nombre'] ?? ''));
                $cedula = trim((string)($row['cedula'] ?? ''));
                $primerNombre = $nombre !== '' ? explode(' ', $nombre)[0] : '';
                if ($primerNombre !== '') {
                    $params[] = $primerNombre;
                }
                if ($cedula !== '') {
                    $params[] = $cedula;
                }
            }

            $this->releaseSessionLock();

            if (waProvider() === 'meta' && !metaEnabled()) {
                $this->jsonOut(['success' => false, 'error' => 'Meta Cloud API no está configurada'], 503);
            }
            if (waProvider() === 'kommo' && !kommoEnabled()) {
                $this->jsonOut(['success' => false, 'error' => 'Kommo no está configurado'], 503);
            }

            $cliName = '';
            $cliStmt = $this->pdo->prepare('SELECT nombre FROM clientes WHERE id_cliente = ? LIMIT 1');
            $cliStmt->execute([$clienteId]);
            $cliName = trim((string)($cliStmt->fetchColumn() ?: ''));

            $cm = [
                'template_external_id' => $templateId !== '' ? $templateId : $templateName,
                'template_name' => $templateName !== '' ? $templateName : $templateId,
                'template_language' => $templateLang !== '' ? $templateLang : 'es',
            ];
            $send = $this->enviarPlantillaProveedor($conv, $cm, $params, $cliName);
            if (empty($send['ok'])) {
                $this->jsonOut([
                    'success' => false,
                    'error' => (string)($send['error'] ?? 'No se pudo enviar la plantilla'),
                    'needs_first_talk' => waProvider() === 'kommo' && !empty($send['needs_first_talk']),
                    'kommo_contact_id' => $send['contact_id'] ?? null,
                    'conversacion' => $this->convModel->getById((int)$conv['id']),
                ], 502);
            }

            $preview = '[Plantilla] ' . ($cm['template_name'] ?: $cm['template_external_id']);
            $msgId = $this->msgModel->create([
                'conversacion_id' => (int)$conv['id'],
                'direccion' => 'out',
                'tipo' => 'template',
                'cuerpo' => $preview,
                'kommo_message_id' => waProvider() === 'kommo' ? ($send['external_message_id'] ?? null) : null,
                'external_message_id' => $send['external_message_id'] ?? null,
                'status' => 'enviado',
            ]);
            $this->convModel->touchPreview((int)$conv['id'], $preview);
            $this->convModel->update((int)$conv['id'], [
                'wa_activo' => 'si',
                'provider' => waProvider(),
                'meta_phone_number_id' => waProvider() === 'meta' ? META_PHONE_NUMBER_ID : null,
            ]);

            $conv = $this->convModel->getById((int)$conv['id']);
            $this->jsonOut([
                'success' => true,
                'mensaje_id' => $msgId,
                'conversacion' => $conv,
                'external_message_id' => $send['external_message_id'] ?? null,
            ]);
        } catch (Throwable $e) {
            $this->jsonOut(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function campanaPreviewCedulas(): void {
        $this->requireRoles(['coordinador', 'administrador']);
        $body = $this->readJsonBody();
        $baseId = (int)($body['base_id'] ?? 0);
        $cedulasRaw = $body['cedulas'] ?? [];
        if ($baseId <= 0) {
            $this->jsonOut(['success' => false, 'error' => 'base_id requerido'], 400);
        }
        if (!$this->campanaModel->coordinadorTieneAccesoABase((string)$_SESSION['user_id'], $baseId)
            && ($_SESSION['user_role'] ?? '') !== 'administrador') {
            $this->jsonOut(['success' => false, 'error' => 'Sin acceso a esa base'], 403);
        }
        if (is_string($cedulasRaw)) {
            $cedulasRaw = preg_split('/[\s,;]+/', $cedulasRaw) ?: [];
        }
        if (!is_array($cedulasRaw)) {
            $cedulasRaw = [];
        }
        $preview = $this->campanaMasivaModel->previewCedulasEnBase($baseId, $cedulasRaw);
        $this->jsonOut([
            'success' => true,
            'preview' => $preview,
            'counts' => [
                'encontrados' => count($preview['encontrados']),
                'sin_telefono' => count($preview['sin_telefono']),
                'no_encontrados' => count($preview['no_encontrados']),
            ],
        ]);
    }

    public function campanaCrear(): void {
        $this->requireRoles(['coordinador', 'administrador']);
        $body = $this->readJsonBody();
        $baseId = (int)($body['base_id'] ?? 0);
        $templateId = trim((string)($body['template_external_id'] ?? ''));
        $templateName = trim((string)($body['template_name'] ?? ''));
        $templateLang = trim((string)($body['template_language'] ?? 'es'));
        $varMap = $body['var_map'] ?? ['1' => 'nombre', '2' => 'cedula'];
        $cedulasRaw = $body['cedulas'] ?? [];

        if ($baseId <= 0) {
            $this->jsonOut(['success' => false, 'error' => 'base_id requerido'], 400);
        }
        if ($templateId === '' && $templateName === '') {
            $this->jsonOut(['success' => false, 'error' => 'Selecciona una plantilla Meta'], 400);
        }
        if (!$this->campanaModel->coordinadorTieneAccesoABase((string)$_SESSION['user_id'], $baseId)
            && ($_SESSION['user_role'] ?? '') !== 'administrador') {
            $this->jsonOut(['success' => false, 'error' => 'Sin acceso a esa base'], 403);
        }
        if (is_string($cedulasRaw)) {
            $cedulasRaw = preg_split('/[\s,;]+/', $cedulasRaw) ?: [];
        }
        $preview = $this->campanaMasivaModel->previewCedulasEnBase($baseId, (array)$cedulasRaw);
        $destinos = array_merge($preview['encontrados'], $preview['sin_telefono']);
        if (!$destinos && empty($preview['no_encontrados'])) {
            $this->jsonOut(['success' => false, 'error' => 'No hay cédulas para procesar'], 400);
        }

        $campanaIdBase = $this->campanaModel->getCampanaIdByBase($baseId);
        $cmId = $this->campanaMasivaModel->create([
            'base_id' => $baseId,
            'campana_id' => $campanaIdBase,
            'coordinador_cedula' => (string)$_SESSION['user_id'],
            'template_external_id' => $templateId !== '' ? $templateId : $templateName,
            'template_name' => $templateName !== '' ? $templateName : $templateId,
            'template_language' => $templateLang !== '' ? $templateLang : 'es',
            'var_map' => is_array($varMap) ? $varMap : ['1' => 'nombre', '2' => 'cedula'],
            'estado' => 'procesando',
            'total' => count($preview['encontrados']) + count($preview['sin_telefono']) + count($preview['no_encontrados']),
        ]);

        foreach ($preview['encontrados'] as $item) {
            $this->campanaMasivaModel->addDestinatario([
                'campana_masiva_id' => $cmId,
                'cliente_id' => $item['cliente_id'],
                'cedula' => $item['cedula'],
                'nombre' => $item['nombre'],
                'telefono_e164' => $item['telefono_e164'],
                'estado' => 'pendiente',
            ]);
        }
        foreach ($preview['sin_telefono'] as $item) {
            $this->campanaMasivaModel->addDestinatario([
                'campana_masiva_id' => $cmId,
                'cliente_id' => $item['cliente_id'],
                'cedula' => $item['cedula'],
                'nombre' => $item['nombre'],
                'estado' => 'sin_telefono',
                'error_msg' => 'Sin teléfono E.164 válido',
            ]);
            $this->campanaMasivaModel->incrementCounter($cmId, 'errores');
        }
        foreach ($preview['no_encontrados'] as $ced) {
            $this->campanaMasivaModel->addDestinatario([
                'campana_masiva_id' => $cmId,
                'cedula' => $ced,
                'estado' => 'sin_cliente',
                'error_msg' => 'Cédula no encontrada en la base',
            ]);
            $this->campanaMasivaModel->incrementCounter($cmId, 'errores');
        }

        $processed = $this->procesarLoteCampana($cmId, 15);

        $this->jsonOut([
            'success' => true,
            'campana_masiva_id' => $cmId,
            'campana' => $this->campanaMasivaModel->getById($cmId),
            'lote' => $processed,
        ]);
    }

    public function campanaProcesarLote(): void {
        $this->requireRoles(['coordinador', 'administrador']);
        $body = $this->readJsonBody();
        $cmId = (int)($body['campana_masiva_id'] ?? $_GET['campana_masiva_id'] ?? 0);
        $limit = (int)($body['limit'] ?? 15);
        if ($cmId <= 0) {
            $this->jsonOut(['success' => false, 'error' => 'campana_masiva_id requerido'], 400);
        }
        $cm = $this->campanaMasivaModel->getById($cmId);
        if (!$cm) {
            $this->jsonOut(['success' => false, 'error' => 'Campaña no encontrada'], 404);
        }
        if ((string)$cm['coordinador_cedula'] !== (string)$_SESSION['user_id']
            && ($_SESSION['user_role'] ?? '') !== 'administrador') {
            $this->jsonOut(['success' => false, 'error' => 'Sin permiso'], 403);
        }
        $lote = $this->procesarLoteCampana($cmId, $limit);
        $this->jsonOut([
            'success' => true,
            'lote' => $lote,
            'campana' => $this->campanaMasivaModel->getById($cmId),
        ]);
    }

    public function campanaEstado(): void {
        $this->requireRoles(['coordinador', 'administrador']);
        $cmId = (int)($_GET['campana_masiva_id'] ?? 0);
        if ($cmId <= 0) {
            $this->jsonOut(['success' => false, 'error' => 'campana_masiva_id requerido'], 400);
        }
        $cm = $this->campanaMasivaModel->getById($cmId);
        if (!$cm) {
            $this->jsonOut(['success' => false, 'error' => 'No encontrada'], 404);
        }
        $this->jsonOut([
            'success' => true,
            'campana' => $cm,
            'por_estado' => $this->campanaMasivaModel->countDestinatariosByEstado($cmId),
        ]);
    }

    public function campanaList(): void {
        $this->requireRoles(['coordinador', 'administrador']);
        $list = $this->campanaMasivaModel->listByCoordinador((string)$_SESSION['user_id'], 30);
        $this->jsonOut(['success' => true, 'campanas' => $list]);
    }

    /**
     * Envía hasta $limit destinatarios pendientes (rate-limit / escalonado).
     */
    public function procesarLoteCampana(int $cmId, int $limit = 15): array {
        $cm = $this->campanaMasivaModel->getById($cmId);
        if (!$cm || ($cm['estado'] ?? '') === 'cancelada') {
            return ['enviados' => 0, 'errores' => 0, 'pendientes' => 0];
        }
        $this->campanaMasivaModel->update($cmId, ['estado' => 'procesando']);
        $varMap = $cm['var_map'] ?? null;
        if (is_string($varMap)) {
            $varMap = json_decode($varMap, true) ?: [];
        }
        if (!is_array($varMap) || !$varMap) {
            $varMap = ['1' => 'nombre', '2' => 'cedula'];
        }

        $pendientes = $this->campanaMasivaModel->listDestinatariosPendientes($cmId, $limit);
        $ok = 0;
        $err = 0;
        foreach ($pendientes as $dest) {
            if (($dest['estado'] ?? '') === 'enviando' && !empty($dest['external_message_id'])) {
                $this->campanaMasivaModel->updateDestinatario((int)$dest['id'], [
                    'estado' => 'enviado',
                    'enviado_at' => $dest['enviado_at'] ?: date('Y-m-d H:i:s'),
                ]);
                continue;
            }
            $this->campanaMasivaModel->updateDestinatario((int)$dest['id'], ['estado' => 'enviando']);
            $params = [];
            foreach ($varMap as $idx => $field) {
                $field = strtolower((string)$field);
                if ($field === 'nombre') {
                    $params[(string)$idx] = (string)($dest['nombre'] ?? '');
                } elseif ($field === 'cedula') {
                    $params[(string)$idx] = (string)($dest['cedula'] ?? '');
                } else {
                    $params[(string)$idx] = '';
                }
            }

            $telefono = (string)($dest['telefono_e164'] ?? '');
            if ($telefono === '') {
                $this->campanaMasivaModel->updateDestinatario((int)$dest['id'], [
                    'estado' => 'sin_telefono',
                    'error_msg' => 'Sin teléfono',
                ]);
                $this->campanaMasivaModel->incrementCounter($cmId, 'errores');
                $err++;
                continue;
            }

            try {
                $conv = $this->convModel->getOrCreateForCliente(
                    (int)$dest['cliente_id'],
                    $telefono,
                    null
                );
                $updates = [
                    'origen' => 'campana_masiva',
                    'campana_masiva_id' => $cmId,
                    'campana_id' => $cm['campana_id'] ?? null,
                    // Sin notificar hasta que respondan
                    'asesor_notificacion_id' => null,
                ];
                // No borrar asesor_id si ya existía interacción previa; limpiar notif
                $this->convModel->update((int)$conv['id'], $updates);

                $send = $this->enviarPlantillaProveedor($conv, $cm, $params);
                if (!empty($send['ok'])) {
                    $externalMessageId = $send['external_message_id'] ?? null;
                    $this->campanaMasivaModel->updateDestinatario((int)$dest['id'], [
                        'estado' => 'enviado',
                        'kommo_message_id' => waProvider() === 'kommo' ? $externalMessageId : null,
                        'external_message_id' => $externalMessageId,
                        'conversacion_id' => (int)$conv['id'],
                        'enviado_at' => date('Y-m-d H:i:s'),
                    ]);
                    $preview = '[Plantilla] ' . ($cm['template_name'] ?: $cm['template_external_id']);
                    $this->msgModel->create([
                        'conversacion_id' => (int)$conv['id'],
                        'direccion' => 'out',
                        'tipo' => 'template',
                        'cuerpo' => $preview . ' · ' . ($dest['nombre'] ?? '') . ' / ' . ($dest['cedula'] ?? ''),
                        'kommo_message_id' => waProvider() === 'kommo' ? $externalMessageId : null,
                        'external_message_id' => $externalMessageId,
                        'status' => 'enviado',
                    ]);
                    $this->convModel->touchPreview((int)$conv['id'], $preview);
                    $this->campanaMasivaModel->incrementCounter($cmId, 'enviados');
                    $ok++;
                } else {
                    $this->campanaMasivaModel->updateDestinatario((int)$dest['id'], [
                        'estado' => 'error',
                        'error_msg' => mb_substr((string)($send['error'] ?? 'Error envío'), 0, 500),
                        'conversacion_id' => (int)$conv['id'],
                    ]);
                    $this->campanaMasivaModel->incrementCounter($cmId, 'errores');
                    $err++;
                }
            } catch (Throwable $e) {
                $this->campanaMasivaModel->updateDestinatario((int)$dest['id'], [
                    'estado' => 'error',
                    'error_msg' => mb_substr($e->getMessage(), 0, 500),
                ]);
                $this->campanaMasivaModel->incrementCounter($cmId, 'errores');
                $err++;
            }
            // Pequeña pausa entre envíos del lote
            usleep(150000);
        }

        $counts = $this->campanaMasivaModel->countDestinatariosByEstado($cmId);
        $sinTerminar = ($counts['pendiente'] ?? 0) + ($counts['enviando'] ?? 0);
        if ($sinTerminar === 0) {
            $this->campanaMasivaModel->update($cmId, ['estado' => 'completada']);
        }

        return [
            'enviados' => $ok,
            'errores' => $err,
            'pendientes' => $sinTerminar,
        ];
    }

    /**
     * Lista plantillas WABA vía Kommo (varios endpoints posibles; vacío si no hay).
     */
    private function listarPlantillasKommo(): array {
        if (!kommoEnabled()) {
            return [];
        }
        $paths = [
            '/api/v4/chats/templates',
            '/api/v4/whatsapp/templates',
            '/api/v4/waba/templates',
        ];
        foreach ($paths as $path) {
            [$code, $body] = $this->kommoApiRequest('GET', $path . '?limit=50');
            if ($code < 200 || $code >= 300) {
                continue;
            }
            $data = json_decode($body, true);
            if (!is_array($data)) {
                continue;
            }
            // Kommo real: _embedded.chat_templates (no "templates")
            $raw = $data['_embedded']['chat_templates']
                ?? $data['_embedded']['templates']
                ?? $data['templates']
                ?? $data['_embedded']['items']
                ?? (isset($data[0]) ? $data : []);
            if (!is_array($raw) || !$raw) {
                continue;
            }
            $out = [];
            foreach ($raw as $t) {
                if (!is_array($t)) {
                    continue;
                }
                $type = strtolower((string)($t['type'] ?? ''));
                // Preferir WABA (Meta); incluir amocrm solo si no hay WABA en el lote
                $name = (string)($t['name'] ?? $t['template_name'] ?? $t['title'] ?? '');
                $id = (string)($t['id'] ?? $t['template_id'] ?? $t['external_id'] ?? $name);
                if ($id === '' && $name === '') {
                    continue;
                }
                $status = strtolower((string)($t['status'] ?? $t['state'] ?? 'approved'));
                $bodyText = (string)(
                    $t['waba_template_body']
                    ?? $t['content']
                    ?? $t['body']
                    ?? $t['text']
                    ?? ''
                );
                $header = (string)($t['waba_header'] ?? '');
                if ($header !== '' && $bodyText === '') {
                    $bodyText = $header;
                } elseif ($header !== '') {
                    $bodyText = trim($header) . "\n" . $bodyText;
                }
                $vars = [];
                if (preg_match_all('/\{\{(\d+)\}\}/', $bodyText, $m)) {
                    $vars = array_values(array_unique($m[1]));
                }
                // Variables Kommo tipo {{contact.first_name}} / catalog
                if (!$vars && preg_match_all('/\{\{[^}]+\}\}/', $bodyText . $header, $m2)) {
                    $vars = array_map(static function ($i) {
                        return (string)($i + 1);
                    }, array_keys($m2[0]));
                }
                $lang = (string)(
                    $t['waba_language']
                    ?? $t['language']
                    ?? $t['lang']
                    ?? 'es'
                );
                $out[] = [
                    'id' => $id,
                    'name' => $name !== '' ? $name : $id,
                    'language' => $lang !== '' ? $lang : 'es',
                    'status' => $status,
                    'type' => $type,
                    'category' => (string)($t['waba_category'] ?? ''),
                    'body' => $bodyText,
                    'variables' => $vars,
                    'external_id' => $t['external_id'] ?? null,
                ];
            }
            if (!$out) {
                continue;
            }
            // Si hay WABA, ocultar plantillas internas amocrm del selector operativo
            $waba = array_values(array_filter($out, static function ($row) {
                return ($row['type'] ?? '') === 'waba';
            }));
            return $waba ?: $out;
        }
        return [];
    }

    /**
     * Envía plantilla HSM. Si Kommo aún no expone el endpoint, devolver error claro.
     */
    private function enviarPlantillaProveedor(
        array $conv,
        array $cm,
        array $params,
        string $contactName = ''
    ): array {
        if (waProvider() === 'meta') {
            $name = trim((string)($cm['template_name'] ?? ''));
            if ($name === '') {
                return ['ok' => false, 'error' => 'Meta requiere el nombre interno de la plantilla'];
            }
            $send = $this->metaGateway->sendTemplate(
                (string)($conv['telefono_e164'] ?? ''),
                $name,
                (string)($cm['template_language'] ?? 'es'),
                array_values($params)
            );
            if (!empty($send['ok'])) {
                $this->convModel->update((int)$conv['id'], [
                    'provider' => 'meta',
                    'meta_phone_number_id' => META_PHONE_NUMBER_ID,
                ]);
            }
            return $send;
        }

        $send = $this->enviarPlantillaKommo($conv, $cm, $params, $contactName);
        if (!empty($send['kommo_message_id'])) {
            $send['external_message_id'] = $send['kommo_message_id'];
        }
        return $send;
    }

    private function enviarPlantillaKommo(array $conv, array $cm, array $params, string $contactName = ''): array {
        $talkId = trim((string)($conv['kommo_talk_id'] ?? ''));
        if ($talkId === '') {
            $resolved = $this->resolverTalkKommoPorTelefono(
                (string)($conv['telefono_e164'] ?? ''),
                $contactName
            );
            if (empty($resolved['ok'])) {
                return [
                    'ok' => false,
                    'error' => $resolved['error'] ?? 'Sin talk_id.',
                    'needs_first_talk' => !empty($resolved['needs_first_talk']),
                    'contact_id' => $resolved['contact_id'] ?? null,
                ];
            }
            $talkId = (string)$resolved['talk_id'];
            if ($talkId === '') {
                return [
                    'ok' => false,
                    'error' => 'Se creó/abrió chat en Kommo pero aún no hay talk_id. Reintenta en unos segundos.',
                ];
            }
            $this->convModel->update((int)$conv['id'], [
                'kommo_talk_id' => $talkId,
                'kommo_chat_id' => $resolved['chat_id'] ?? null,
            ]);
        }

        $templateName = (string)($cm['template_name'] ?? '');
        $templateId = (string)($cm['template_external_id'] ?? '');
        $lang = (string)($cm['template_language'] ?? 'es');

        $payloads = [
            // Forma típica Kommo chat_templates (id numérico interno)
            [
                'path' => '/api/v4/talks/' . rawurlencode($talkId) . '/send_message',
                'body' => [
                    'template_id' => ctype_digit($templateId) ? (int)$templateId : $templateId,
                ],
            ],
            [
                'path' => '/api/v4/talks/' . rawurlencode($talkId) . '/send_message',
                'body' => [
                    'message_type' => 'template',
                    'template_id' => ctype_digit($templateId) ? (int)$templateId : $templateId,
                    'template_name' => $templateName,
                    'language' => $lang,
                    'params' => array_values($params),
                ],
            ],
            [
                'path' => '/api/v4/talks/' . rawurlencode($talkId) . '/send_message',
                'body' => [
                    'template' => [
                        'id' => ctype_digit($templateId) ? (int)$templateId : $templateId,
                        'name' => $templateName,
                        'language' => ['code' => $lang],
                        'components' => $params === [] ? [] : [[
                            'type' => 'body',
                            'parameters' => array_map(static function ($v) {
                                return ['type' => 'text', 'text' => (string)$v];
                            }, array_values($params)),
                        ]],
                    ],
                ],
            ],
        ];

        $lastError = 'Sin plantillas disponibles o endpoint no soportado aún';
        foreach ($payloads as $attempt) {
            [$code, $resp] = $this->kommoApiRequest('POST', $attempt['path'], $attempt['body']);
            $data = json_decode((string)$resp, true);
            if ($code >= 200 && $code < 300) {
                return [
                    'ok' => true,
                    'kommo_message_id' => (string)($data['id'] ?? $data['message_id'] ?? ('tpl-' . time())),
                ];
            }
            $lastError = is_array($data)
                ? (string)($data['detail'] ?? $data['title'] ?? $resp)
                : (string)$resp;
            // Si es 404 del path, probar siguiente shape
            if ($code === 404 || $code === 400 || $code === 422) {
                continue;
            }
        }
        return ['ok' => false, 'error' => "Kommo plantilla: {$lastError}"];
    }

    private function onInboundMessage(array $conv): void {
        $notif = trim((string)($conv['asesor_notificacion_id'] ?? ''));
        if ($notif !== '') {
            // Ya hay dueño de notificación: no reencolar
            return;
        }
        $campanaId = isset($conv['campana_id']) ? (int)$conv['campana_id'] : null;
        if (!$campanaId && !empty($conv['cliente_id'])) {
            $campanaId = $this->campanaIdFromCliente((int)$conv['cliente_id']);
            if ($campanaId) {
                $this->convModel->update((int)$conv['id'], ['campana_id' => $campanaId]);
            }
        }
        // Encolar si es campaña masiva o no tiene notificado
        if (($conv['origen'] ?? '') === 'campana_masiva' || $notif === '') {
            $this->colaModel->enqueueIfNeeded((int)$conv['id'], $campanaId ?: null);
        }
    }

    /**
     * Asigna como máximo 1 chat nuevo por asesor en_linea de la campaña (por tick).
     */
    private function dispatchColaAsignacion(): void {
        $waiting = $this->colaModel->listEsperando(40);
        if (!$waiting) {
            return;
        }
        $assignedThisTick = []; // asesor => true (máx 1)
        foreach ($waiting as $row) {
            $campanaId = (int)($row['campana_id'] ?? 0);
            if ($campanaId <= 0 && !empty($row['cliente_id'])) {
                $campanaId = (int)$this->campanaIdFromCliente((int)$row['cliente_id']);
            }
            $online = [];
            if ($campanaId > 0) {
                $online = $this->asesoresEnLineaDeCampana($campanaId);
            }
            if (!$online && !empty($row['cliente_id'])) {
                $online = $this->asesoresEnLineaPorCliente((int)$row['cliente_id']);
            }
            if (!$online) {
                continue;
            }
            // Filtrar capacidad < 10 y no asignados en este tick
            $candidatos = [];
            foreach ($online as $ced) {
                if (isset($assignedThisTick[$ced])) {
                    continue;
                }
                if ($this->convModel->countBubblesActivas($ced) >= 10) {
                    continue;
                }
                $candidatos[] = $ced;
            }
            if (!$candidatos) {
                continue;
            }
            $pick = $candidatos[array_rand($candidatos)];
            $this->claimNotificacion((int)$row['conversacion_id'], $pick, false);
            $this->colaModel->markAsignado((int)$row['id'], $pick);
            $this->colaModel->undismiss((int)$row['conversacion_id'], $pick);
            $assignedThisTick[$pick] = true;
        }
    }

    private function claimNotificacion(int $convId, string $asesorId, bool $asInteraccion): void {
        $fields = [
            'asesor_notificacion_id' => $asesorId,
            'asesor_id' => $asesorId,
        ];
        if ($asInteraccion) {
            $fields['ultimo_interactuante_id'] = $asesorId;
        }
        $this->convModel->update($convId, $fields);
    }

    private function asesorPuedeEnviar(?array $conv): bool {
        if (!$conv) {
            return false;
        }
        $role = $_SESSION['user_role'] ?? '';
        if (in_array($role, ['coordinador', 'cordinador', 'administrador'], true)) {
            return true;
        }
        $me = $this->currentAsesorId();
        $notif = trim((string)($conv['asesor_notificacion_id'] ?? $conv['asesor_id'] ?? ''));
        if ($notif === '' || $notif === $me) {
            return true;
        }
        // Reclaim si el notificado está offline
        return !$this->asesorEstaEnLinea($notif);
    }

    private function asesorPuedeLeerCliente(int $clienteId): bool {
        if ($clienteId <= 0) {
            return true;
        }
        $role = $_SESSION['user_role'] ?? '';
        if (in_array($role, ['coordinador', 'cordinador', 'administrador'], true)) {
            return true;
        }
        $tareaModel = new TareaModel($this->pdo);
        $stmt = $this->pdo->prepare('SELECT cedula, base_id FROM clientes WHERE id_cliente = ? LIMIT 1');
        $stmt->execute([$clienteId]);
        $cli = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$cli) {
            return false;
        }
        $bases = $tareaModel->getBasesAsignadasByAsesor($this->currentAsesorId());
        foreach ($bases as $b) {
            if ((int)($b['id_base'] ?? $b['base_id'] ?? 0) === (int)$cli['base_id']) {
                return true;
            }
        }
        // También: misma campaña que el asesor
        $campanaCliente = $this->campanaIdFromCliente($clienteId);
        if ($campanaCliente) {
            $asesores = $this->campanaModel->getAsesoresByCampana($campanaCliente);
            foreach ($asesores as $a) {
                if ($this->cedulaAsesorCampana($a) === $this->currentAsesorId()) {
                    return true;
                }
            }
        }
        return false;
    }

    private function campanaIdFromCliente(int $clienteId): ?int {
        $stmt = $this->pdo->prepare(
            'SELECT b.campana_id FROM clientes c
             INNER JOIN base_clientes b ON b.id_base = c.base_id
             WHERE c.id_cliente = ? LIMIT 1'
        );
        $stmt->execute([$clienteId]);
        $id = $stmt->fetchColumn();
        return $id !== false && $id !== null ? (int)$id : null;
    }

    private function asesoresEnLineaDeCampana(int $campanaId): array {
        $asesores = $this->campanaModel->getAsesoresByCampana($campanaId);
        $online = [];
        foreach ($asesores as $a) {
            $ced = $this->cedulaAsesorCampana($a);
            if ($ced !== '' && $this->asesorEstaEnLinea($ced)) {
                $online[] = $ced;
            }
        }
        return $online;
    }

    private function telefonoRawDesdeE164(string $e164, array $telefonos): string {
        $norm = kommoNormalizePhoneE164($e164) ?: $e164;
        foreach ($telefonos as $t) {
            if (($t['e164'] ?? '') === $norm || ($t['raw'] ?? '') === $e164) {
                return (string)$t['raw'];
            }
        }
        return '';
    }

    private function asesoresEnLineaPorCliente(int $clienteId): array {
        $stmt = $this->pdo->prepare('SELECT base_id FROM clientes WHERE id_cliente = ? LIMIT 1');
        $stmt->execute([$clienteId]);
        $baseId = (int)$stmt->fetchColumn();
        if ($baseId <= 0) {
            return [];
        }
        $tareaModel = new TareaModel($this->pdo);
        $asesores = $tareaModel->getAsesoresByBase($baseId);
        $online = [];
        foreach ($asesores as $a) {
            $ced = $this->cedulaAsesorCampana($a);
            if ($ced !== '' && $this->asesorEstaEnLinea($ced)) {
                $online[] = $ced;
            }
        }
        return $online;
    }

    private function cedulaAsesorCampana(array $asesorRow): string {
        return (string)($asesorRow['cedula'] ?? $asesorRow['id'] ?? $asesorRow['asesor_cedula'] ?? '');
    }

    private function asesorEstaEnLinea(string $cedula): bool {
        if ($cedula === '') {
            return false;
        }
        $stmt = $this->pdo->prepare(
            "SELECT 1 FROM tiempos
             WHERE asesor_cedula = ? AND tipo_registro = 'jornada' AND estado = 'activa' LIMIT 1"
        );
        $stmt->execute([$cedula]);
        if (!$stmt->fetchColumn()) {
            return false;
        }
        $stmtP = $this->pdo->prepare(
            "SELECT 1 FROM tiempos
             WHERE asesor_cedula = ? AND estado = 'activa' AND tipo_registro != 'jornada' LIMIT 1"
        );
        $stmtP->execute([$cedula]);
        if ($stmtP->fetchColumn()) {
            return false;
        }
        try {
            $stmtC = $this->pdo->prepare(
                "SELECT 1 FROM call_log
                 WHERE asesor_cedula = ? AND fin IS NULL
                   AND inicio >= DATE_SUB(NOW(), INTERVAL 3 HOUR) LIMIT 1"
            );
            $stmtC->execute([$cedula]);
            if ($stmtC->fetchColumn()) {
                return false;
            }
        } catch (Throwable $e) {
            // sin call_log
        }
        return true;
    }
}
