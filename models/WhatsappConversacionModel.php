<?php
/**
 * Conversaciones WhatsApp (espejo Kommo).
 */
require_once __DIR__ . '/../config/kommo.php';

class WhatsappConversacionModel {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function getById(int $id): ?array {
        $stmt = $this->pdo->prepare('SELECT * FROM wa_conversaciones WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function getByTelefonoE164(string $e164): ?array {
        $stmt = $this->pdo->prepare('SELECT * FROM wa_conversaciones WHERE telefono_e164 = ? LIMIT 1');
        $stmt->execute([$e164]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function getByKommoTalkId(string $talkId): ?array {
        $talkId = trim($talkId);
        if ($talkId === '') {
            return null;
        }
        $stmt = $this->pdo->prepare('SELECT * FROM wa_conversaciones WHERE kommo_talk_id = ? LIMIT 1');
        $stmt->execute([$talkId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function getByClienteAndTelefono(int $clienteId, string $e164): ?array {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM wa_conversaciones WHERE cliente_id = ? AND telefono_e164 = ? LIMIT 1'
        );
        $stmt->execute([$clienteId, $e164]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function listByAsesor(string $asesorId, int $limit = 10): array {
        $limit = max(1, min(50, $limit));
        $sql = "SELECT c.*, cl.nombre AS cliente_nombre, cl.cedula AS cliente_cedula
                FROM wa_conversaciones c
                LEFT JOIN clientes cl ON cl.id_cliente = c.cliente_id
                WHERE COALESCE(c.asesor_notificacion_id, c.asesor_id) = ?
                  AND c.estado IN ('abierta', 'cerrada')
                  AND c.cliente_id IS NOT NULL
                ORDER BY COALESCE(c.ultimo_mensaje_at, c.updated_at) DESC
                LIMIT {$limit}";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$asesorId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Burbujas activas (no dismissed) para el asesor, máx $limit.
     * Incluye pendiente_respuesta=1 si el último mensaje es del cliente.
     */
    public function listBubblesActivas(string $asesorId, int $limit = 10): array {
        $limit = max(1, min(50, $limit));
        $sql = "SELECT c.*, cl.nombre AS cliente_nombre, cl.cedula AS cliente_cedula,
                       CASE
                         WHEN (
                           SELECT m.direccion FROM wa_mensajes m
                           WHERE m.conversacion_id = c.id
                           ORDER BY COALESCE(m.created_at, '1970-01-01') DESC, m.id DESC
                           LIMIT 1
                         ) = 'in' THEN 1
                         ELSE 0
                       END AS pendiente_respuesta
                FROM wa_conversaciones c
                LEFT JOIN clientes cl ON cl.id_cliente = c.cliente_id
                LEFT JOIN wa_burbuja_dismiss d
                  ON d.conversacion_id = c.id AND d.asesor_id = ?
                WHERE COALESCE(c.asesor_notificacion_id, c.asesor_id) = ?
                  AND c.estado IN ('abierta', 'cerrada')
                  AND c.cliente_id IS NOT NULL
                  AND d.conversacion_id IS NULL
                ORDER BY
                  pendiente_respuesta DESC,
                  COALESCE(c.no_leidos, 0) DESC,
                  COALESCE(c.ultimo_mensaje_at, c.updated_at) DESC
                LIMIT {$limit}";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$asesorId, $asesorId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Overflow: dismissed o más allá del top-N activo, donde el asesor es notificado o último interactuante.
     */
    public function listOverflowCola(string $asesorId, int $visibleLimit = 10): array {
        $visibleLimit = max(1, min(50, $visibleLimit));
        // IDs de las burbujas visibles
        $visible = $this->listBubblesActivas($asesorId, $visibleLimit);
        $visibleIds = array_map(static fn($r) => (int)$r['id'], $visible);

        $sql = "SELECT c.*, cl.nombre AS cliente_nombre, cl.cedula AS cliente_cedula,
                       CASE WHEN d.conversacion_id IS NULL THEN 0 ELSE 1 END AS dismissed,
                       CASE
                         WHEN (
                           SELECT m.direccion FROM wa_mensajes m
                           WHERE m.conversacion_id = c.id
                           ORDER BY COALESCE(m.created_at, '1970-01-01') DESC, m.id DESC
                           LIMIT 1
                         ) = 'in' THEN 1
                         ELSE 0
                       END AS pendiente_respuesta
                FROM wa_conversaciones c
                LEFT JOIN clientes cl ON cl.id_cliente = c.cliente_id
                LEFT JOIN wa_burbuja_dismiss d
                  ON d.conversacion_id = c.id AND d.asesor_id = ?
                WHERE c.estado IN ('abierta', 'cerrada')
                  AND c.cliente_id IS NOT NULL
                  AND (
                    COALESCE(c.asesor_notificacion_id, c.asesor_id) = ?
                    OR c.ultimo_interactuante_id = ?
                  )
                ORDER BY
                  pendiente_respuesta DESC,
                  COALESCE(c.ultimo_mensaje_at, c.updated_at) DESC
                LIMIT 100";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$asesorId, $asesorId, $asesorId]);
        $all = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $out = [];
        foreach ($all as $row) {
            $id = (int)$row['id'];
            $isVisible = in_array($id, $visibleIds, true);
            $dismissed = (int)($row['dismissed'] ?? 0) === 1;
            if ($isVisible && !$dismissed) {
                continue;
            }
            // Solo contar en +N si dismissed O si no cabía en el top 10
            if ($dismissed || !$isVisible) {
                $out[] = $row;
            }
        }
        return $out;
    }

    public function countBubblesActivas(string $asesorId): int {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*)
             FROM wa_conversaciones c
             LEFT JOIN wa_burbuja_dismiss d
               ON d.conversacion_id = c.id AND d.asesor_id = ?
             WHERE COALESCE(c.asesor_notificacion_id, c.asesor_id) = ?
               AND c.estado IN ('abierta','cerrada')
               AND c.cliente_id IS NOT NULL
               AND d.conversacion_id IS NULL"
        );
        $stmt->execute([$asesorId, $asesorId]);
        return (int)$stmt->fetchColumn();
    }

    public function countByAsesor(string $asesorId): int {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM wa_conversaciones
             WHERE COALESCE(asesor_notificacion_id, asesor_id) = ?
               AND estado IN ('abierta','cerrada') AND cliente_id IS NOT NULL"
        );
        $stmt->execute([$asesorId]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Conversaciones del asesor a sincronizar con Kommo (activas Y dismissed).
     * Sin esto, un chat cerrado en burbuja nunca recibe inbound ni reabre notificación.
     */
    public function listParaSyncBurbujas(string $asesorId, int $limit = 20): array {
        $limit = max(1, min(40, $limit));
        $sql = "SELECT c.*, cl.nombre AS cliente_nombre, cl.cedula AS cliente_cedula,
                       CASE WHEN d.conversacion_id IS NULL THEN 0 ELSE 1 END AS dismissed
                FROM wa_conversaciones c
                LEFT JOIN clientes cl ON cl.id_cliente = c.cliente_id
                LEFT JOIN wa_burbuja_dismiss d
                  ON d.conversacion_id = c.id AND d.asesor_id = ?
                WHERE COALESCE(c.asesor_notificacion_id, c.asesor_id) = ?
                  AND c.estado IN ('abierta', 'cerrada')
                  AND c.cliente_id IS NOT NULL
                  AND c.kommo_talk_id IS NOT NULL
                  AND c.kommo_talk_id <> ''
                ORDER BY
                  CASE WHEN d.conversacion_id IS NOT NULL THEN 0 ELSE 1 END ASC,
                  COALESCE(c.ultimo_mensaje_at, c.updated_at) DESC
                LIMIT {$limit}";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$asesorId, $asesorId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Elige el teléfono del perfil con más actividad WA (respuesta del cliente / talk / preview).
     */
    public function pickBestTelefonoE164(int $clienteId, array $telefonos): ?array {
        if (!$telefonos) {
            return null;
        }
        $e164List = [];
        foreach ($telefonos as $t) {
            $e = (string)($t['e164'] ?? '');
            if ($e !== '') {
                $e164List[$e] = $t;
            }
        }
        if (!$e164List) {
            return $telefonos[0];
        }
        $placeholders = implode(',', array_fill(0, count($e164List), '?'));
        $sql = "SELECT telefono_e164, kommo_talk_id, ultimo_mensaje_at, ultimo_preview, no_leidos
                FROM wa_conversaciones
                WHERE cliente_id = ?
                  AND telefono_e164 IN ({$placeholders})
                ORDER BY
                  (ultimo_mensaje_at IS NOT NULL) DESC,
                  ultimo_mensaje_at DESC,
                  (kommo_talk_id IS NOT NULL AND kommo_talk_id <> '') DESC,
                  no_leidos DESC,
                  id DESC
                LIMIT 1";
        $params = array_merge([$clienteId], array_keys($e164List));
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $best = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($best && isset($e164List[$best['telefono_e164']])) {
            return $e164List[$best['telefono_e164']];
        }
        return $telefonos[0];
    }

    public function listSinCliente(int $limit = 50): array {
        $limit = max(1, min(200, $limit));
        $stmt = $this->pdo->prepare(
            "SELECT * FROM wa_conversaciones WHERE estado = 'sin_cliente'
             ORDER BY COALESCE(ultimo_mensaje_at, created_at) DESC LIMIT {$limit}"
        );
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Chats nuevos sin cédula pendientes de atender/amarrar en el inbox del coordinador.
     */
    public function countPendientesCoordinador(): int {
        $stmt = $this->pdo->query(
            "SELECT COUNT(*) FROM wa_conversaciones WHERE estado = 'sin_cliente'"
        );
        return (int)$stmt->fetchColumn();
    }

    /**
     * Resuelve un teléfono a una sola fila cliente.
     * Si hay varias filas, solo decide cuando alguna tiene una gestión previa;
     * en caso contrario devuelve null para que el coordinador elija la base.
     */
    public function resolveClienteByPhone(string $phoneRaw): ?array {
        $last10 = kommoPhoneLast10($phoneRaw);
        if ($last10 === '' || strlen($last10) < 7) {
            return null;
        }
        $sql = "SELECT c.id_cliente, c.base_id, c.cedula, c.nombre,
                       b.nombre AS base_nombre, b.campana_id,
                       MAX(hg.fecha_creacion) AS ultima_gestion
                FROM clientes c
                LEFT JOIN base_clientes b ON b.id_base = c.base_id
                LEFT JOIN historial_gestiones hg ON hg.cliente_id = c.id_cliente
                WHERE RIGHT(REPLACE(REPLACE(REPLACE(tel1,' ',''),'-',''),'+',''), 10) = ?
                   OR RIGHT(REPLACE(REPLACE(REPLACE(tel2,' ',''),'-',''),'+',''), 10) = ?
                   OR RIGHT(REPLACE(REPLACE(REPLACE(tel3,' ',''),'-',''),'+',''), 10) = ?
                   OR RIGHT(REPLACE(REPLACE(REPLACE(tel4,' ',''),'-',''),'+',''), 10) = ?
                   OR RIGHT(REPLACE(REPLACE(REPLACE(tel5,' ',''),'-',''),'+',''), 10) = ?
                   OR RIGHT(REPLACE(REPLACE(REPLACE(tel6,' ',''),'-',''),'+',''), 10) = ?
                   OR RIGHT(REPLACE(REPLACE(REPLACE(tel7,' ',''),'-',''),'+',''), 10) = ?
                   OR RIGHT(REPLACE(REPLACE(REPLACE(tel8,' ',''),'-',''),'+',''), 10) = ?
                   OR RIGHT(REPLACE(REPLACE(REPLACE(tel9,' ',''),'-',''),'+',''), 10) = ?
                   OR RIGHT(REPLACE(REPLACE(REPLACE(tel10,' ',''),'-',''),'+',''), 10) = ?
                GROUP BY c.id_cliente, c.base_id, c.cedula, c.nombre,
                         b.nombre, b.campana_id
                ORDER BY (MAX(hg.fecha_creacion) IS NOT NULL) DESC,
                         MAX(hg.fecha_creacion) DESC,
                         c.id_cliente DESC";
        $params = array_fill(0, 10, $last10);
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        if (count($rows) === 1) {
            return $rows[0];
        }
        if (!$rows) {
            return null;
        }

        // Un teléfono presente en cédulas distintas nunca se decide automáticamente.
        $cedulas = array_unique(array_map(static fn($row) => trim((string)$row['cedula']), $rows));
        if (count($cedulas) !== 1 || empty($rows[0]['ultima_gestion'])) {
            return null;
        }
        return $rows[0];
    }

    public function findClienteIdByPhone(string $phoneRaw): ?int {
        $row = $this->resolveClienteByPhone($phoneRaw);
        return $row ? (int)$row['id_cliente'] : null;
    }

    /**
     * Filas concretas de una cédula, una por base/cliente, para empareje manual.
     */
    public function listClientesByCedula(string $cedula, array $baseIds = []): array {
        $cedula = trim($cedula);
        if ($cedula === '') {
            return [];
        }
        $params = [$cedula];
        $baseFilter = '';
        if ($baseIds) {
            $baseIds = array_values(array_unique(array_filter(array_map('intval', $baseIds))));
            if (!$baseIds) {
                return [];
            }
            $baseFilter = ' AND c.base_id IN (' . implode(',', array_fill(0, count($baseIds), '?')) . ')';
            $params = array_merge($params, $baseIds);
        }
        $stmt = $this->pdo->prepare(
            "SELECT c.id_cliente, c.base_id, c.cedula, c.nombre,
                    b.nombre AS base_nombre, b.campana_id,
                    cp.nombre AS campana_nombre,
                    MAX(hg.fecha_creacion) AS ultima_gestion
             FROM clientes c
             INNER JOIN base_clientes b ON b.id_base = c.base_id
             LEFT JOIN campanas cp ON cp.id_campana = b.campana_id
             LEFT JOIN historial_gestiones hg ON hg.cliente_id = c.id_cliente
             WHERE c.cedula = ?{$baseFilter}
             GROUP BY c.id_cliente, c.base_id, c.cedula, c.nombre,
                      b.nombre, b.campana_id, cp.nombre
             ORDER BY b.nombre ASC, c.id_cliente ASC"
        );
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Agrega el E.164 únicamente a la fila cliente elegida.
     */
    public function addPhoneToCliente(int $clienteId, string $phoneRaw): string {
        $e164 = kommoNormalizePhoneE164($phoneRaw);
        if (!$e164) {
            throw new InvalidArgumentException('Teléfono inválido');
        }
        $stmt = $this->pdo->prepare(
            'SELECT tel1, tel2, tel3, tel4, tel5, tel6, tel7, tel8, tel9, tel10
             FROM clientes WHERE id_cliente = ? FOR UPDATE'
        );
        $stmt->execute([$clienteId]);
        $phones = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$phones) {
            throw new RuntimeException('Cliente no encontrado');
        }
        foreach ($phones as $value) {
            if (kommoNormalizePhoneE164((string)$value) === $e164) {
                return $e164;
            }
        }
        foreach (array_keys($phones) as $column) {
            $value = trim((string)$phones[$column]);
            if ($value === '' || $value === '0') {
                $this->pdo->prepare("UPDATE clientes SET `{$column}` = ? WHERE id_cliente = ?")
                    ->execute([$e164, $clienteId]);
                return $e164;
            }
        }
        throw new RuntimeException('El cliente seleccionado no tiene un campo de teléfono disponible');
    }

    /**
     * Números del perfil del cliente (tel1..tel10) normalizados.
     */
    public function getTelefonosPerfilCliente(int $clienteId): array {
        $stmt = $this->pdo->prepare(
            'SELECT tel1, tel2, tel3, tel4, tel5, tel6, tel7, tel8, tel9, tel10
             FROM clientes WHERE id_cliente = ? LIMIT 1'
        );
        $stmt->execute([$clienteId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return [];
        }
        $out = [];
        $seen = [];
        foreach ($row as $raw) {
            $raw = trim((string)$raw);
            if ($raw === '' || $raw === '0') {
                continue;
            }
            $e164 = kommoNormalizePhoneE164($raw);
            if (!$e164) {
                continue;
            }
            if (isset($seen[$e164])) {
                continue;
            }
            $seen[$e164] = true;
            $out[] = [
                'raw' => $raw,
                'e164' => $e164,
                'display' => $raw,
            ];
        }
        return $out;
    }

    public function create(array $data): int {
        $sql = "INSERT INTO wa_conversaciones
                (cliente_id, campana_id, origen, campana_masiva_id, telefono_e164, kommo_talk_id, kommo_chat_id,
                 asesor_id, asesor_notificacion_id, ultimo_interactuante_id, estado, wa_activo, no_leidos,
                 ultimo_mensaje_at, ultimo_preview)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        $asesor = $data['asesor_id'] ?? null;
        $notif = $data['asesor_notificacion_id'] ?? $asesor;
        $stmt->execute([
            $data['cliente_id'] ?? null,
            $data['campana_id'] ?? null,
            $data['origen'] ?? 'organico',
            $data['campana_masiva_id'] ?? null,
            $data['telefono_e164'],
            $data['kommo_talk_id'] ?? null,
            $data['kommo_chat_id'] ?? null,
            $asesor,
            $notif,
            $data['ultimo_interactuante_id'] ?? null,
            $data['estado'] ?? 'abierta',
            $data['wa_activo'] ?? 'desconocido',
            (int)($data['no_leidos'] ?? 0),
            $data['ultimo_mensaje_at'] ?? null,
            $data['ultimo_preview'] ?? null,
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function update(int $id, array $fields): bool {
        if (empty($fields)) {
            return false;
        }
        $allowed = [
            'cliente_id', 'campana_id', 'origen', 'campana_masiva_id',
            'telefono_e164', 'kommo_talk_id', 'kommo_chat_id',
            'asesor_id', 'asesor_notificacion_id', 'ultimo_interactuante_id',
            'estado', 'wa_activo', 'no_leidos',
            'ultimo_mensaje_at', 'ultimo_preview',
        ];
        // Mantener asesor_id sincronizado con notificacion cuando solo se setea uno
        if (isset($fields['asesor_notificacion_id']) && !isset($fields['asesor_id'])) {
            $fields['asesor_id'] = $fields['asesor_notificacion_id'];
        }
        if (isset($fields['asesor_id']) && !isset($fields['asesor_notificacion_id'])) {
            // solo si notificacion vacía en update parcial — no forzar
        }
        $sets = [];
        $params = [];
        foreach ($fields as $k => $v) {
            if (!in_array($k, $allowed, true)) {
                continue;
            }
            $sets[] = "`{$k}` = ?";
            $params[] = $v;
        }
        if (empty($sets)) {
            return false;
        }
        $params[] = $id;
        $sql = 'UPDATE wa_conversaciones SET ' . implode(', ', $sets) . ' WHERE id = ?';
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    public function touchPreview(int $id, string $preview, ?string $at = null): void {
        $this->update($id, [
            'ultimo_preview' => mb_substr($preview, 0, 250),
            'ultimo_mensaje_at' => $at ?: date('Y-m-d H:i:s'),
        ]);
    }

    public function incrementNoLeidos(int $id): void {
        $this->pdo->prepare('UPDATE wa_conversaciones SET no_leidos = no_leidos + 1 WHERE id = ?')->execute([$id]);
    }

    public function resetNoLeidos(int $id): void {
        $this->pdo->prepare('UPDATE wa_conversaciones SET no_leidos = 0 WHERE id = ?')->execute([$id]);
    }

    /**
     * Obtiene o crea conversación para cliente + teléfono del perfil.
     */
    public function getOrCreateForCliente(int $clienteId, string $telefonoRaw, ?string $asesorId = null): array {
        $e164 = kommoNormalizePhoneE164($telefonoRaw);
        if (!$e164) {
            throw new InvalidArgumentException('Teléfono inválido');
        }

        $existing = $this->getByTelefonoE164($e164);
        if ($existing) {
            $updates = [];
            if (empty($existing['cliente_id'])) {
                $updates['cliente_id'] = $clienteId;
                $updates['estado'] = 'abierta';
            } elseif ((int)$existing['cliente_id'] !== $clienteId) {
                // Teléfono ya amarrado a otro cliente
                throw new RuntimeException('Ese número ya está asociado a otro cliente');
            }
            if ($asesorId && empty($existing['asesor_id']) && empty($existing['asesor_notificacion_id'])) {
                $updates['asesor_id'] = $asesorId;
                $updates['asesor_notificacion_id'] = $asesorId;
            }
            if ($updates) {
                $this->update((int)$existing['id'], $updates);
                $existing = $this->getById((int)$existing['id']);
            }
            return $existing;
        }

        $id = $this->create([
            'cliente_id' => $clienteId,
            'telefono_e164' => $e164,
            'asesor_id' => $asesorId,
            'estado' => 'abierta',
            'wa_activo' => 'desconocido',
        ]);
        return $this->getById($id);
    }
}
