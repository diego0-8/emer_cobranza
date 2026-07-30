<?php
/**
 * Historial de eventos WhatsApp (masivos + emparejes sin cédula).
 */
class WhatsappHistorialModel {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function create(array $data): int {
        $payload = $data['payload'] ?? null;
        if (is_array($payload)) {
            $payload = json_encode($payload, JSON_UNESCAPED_UNICODE);
        }
        $stmt = $this->pdo->prepare(
            'INSERT INTO wa_historial_eventos (tipo, actor_cedula, actor_nombre, resumen, payload)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            (string)$data['tipo'],
            (string)($data['actor_cedula'] ?? ''),
            (string)($data['actor_nombre'] ?? ''),
            (string)($data['resumen'] ?? ''),
            $payload,
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function findCampanaMasivaEventId(int $campanaMasivaId): ?int {
        if ($campanaMasivaId <= 0) {
            return null;
        }
        $stmt = $this->pdo->prepare(
            "SELECT id FROM wa_historial_eventos
             WHERE tipo = 'campana_masiva'
               AND JSON_UNQUOTE(JSON_EXTRACT(payload, '$.campana_masiva_id')) = ?
             ORDER BY id DESC
             LIMIT 1"
        );
        $stmt->execute([(string)$campanaMasivaId]);
        $id = $stmt->fetchColumn();
        return $id !== false ? (int)$id : null;
    }

    public function update(int $id, array $fields): bool {
        if ($id <= 0 || !$fields) {
            return false;
        }
        $allowed = ['resumen', 'payload', 'actor_nombre', 'actor_cedula'];
        $sets = [];
        $params = [];
        foreach ($fields as $k => $v) {
            if (!in_array($k, $allowed, true)) {
                continue;
            }
            if ($k === 'payload' && is_array($v)) {
                $v = json_encode($v, JSON_UNESCAPED_UNICODE);
            }
            $sets[] = "`{$k}` = ?";
            $params[] = $v;
        }
        if (!$sets) {
            return false;
        }
        $params[] = $id;
        $stmt = $this->pdo->prepare(
            'UPDATE wa_historial_eventos SET ' . implode(', ', $sets) . ' WHERE id = ?'
        );
        return $stmt->execute($params);
    }

    /**
     * @return array{items:array,total:int,page:int,per_page:int,total_pages:int}
     */
    public function listPaginado(int $page, int $perPage, ?string $actorCedula = null): array {
        $page = max(1, $page);
        $perPage = max(1, min(50, $perPage));
        $offset = ($page - 1) * $perPage;

        $where = '1=1';
        $params = [];
        if ($actorCedula !== null && $actorCedula !== '') {
            $where = 'actor_cedula = ?';
            $params[] = $actorCedula;
        }

        $countStmt = $this->pdo->prepare("SELECT COUNT(*) FROM wa_historial_eventos WHERE {$where}");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $sql = "SELECT * FROM wa_historial_eventos
                WHERE {$where}
                ORDER BY created_at DESC, id DESC
                LIMIT {$perPage} OFFSET {$offset}";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        foreach ($rows as &$row) {
            if (!empty($row['payload']) && is_string($row['payload'])) {
                $decoded = json_decode($row['payload'], true);
                $row['payload'] = is_array($decoded) ? $decoded : null;
            }
            if (($row['tipo'] ?? '') === 'empareje_sin_cliente') {
                $row = $this->enrichEmparejeRow($row);
            }
        }
        unset($row);

        return [
            'items' => $rows,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => max(1, (int)ceil($total / $perPage)),
        ];
    }

    /**
     * Completa cédula/base en eventos viejos y normaliza resumen a "CC x → Base".
     */
    private function enrichEmparejeRow(array $row): array {
        $payload = is_array($row['payload'] ?? null) ? $row['payload'] : [];
        $cedula = preg_replace('/\D+/', '', (string)($payload['cliente_cedula'] ?? ''));
        $baseNombre = trim((string)($payload['base_nombre'] ?? ''));
        $clienteId = (int)($payload['cliente_id'] ?? 0);

        if (($cedula === '' || $baseNombre === '') && $clienteId > 0) {
            $st = $this->pdo->prepare(
                'SELECT c.cedula, c.nombre, b.nombre AS base_nombre, c.base_id
                 FROM clientes c
                 LEFT JOIN base_clientes b ON b.id_base = c.base_id
                 WHERE c.id_cliente = ? LIMIT 1'
            );
            $st->execute([$clienteId]);
            $cli = $st->fetch(PDO::FETCH_ASSOC) ?: [];
            if ($cedula === '') {
                $cedula = preg_replace('/\D+/', '', (string)($cli['cedula'] ?? '')) ?: '';
            }
            if ($baseNombre === '') {
                $baseNombre = trim((string)($cli['base_nombre'] ?? ''));
            }
            if (empty($payload['cliente_nombre']) && !empty($cli['nombre'])) {
                $payload['cliente_nombre'] = trim((string)$cli['nombre']);
            }
            if (empty($payload['base_id']) && !empty($cli['base_id'])) {
                $payload['base_id'] = (int)$cli['base_id'];
            }
        }

        if ($cedula !== '') {
            $payload['cliente_cedula'] = $cedula;
        }
        if ($baseNombre !== '') {
            $payload['base_nombre'] = $baseNombre;
        }

        $ccLabel = $cedula !== '' ? ('CC ' . $cedula) : (
            $clienteId > 0 ? ('Cliente #' . $clienteId) : 'Cédula no registrada'
        );
        $baseLabel = $baseNombre !== '' ? $baseNombre : 'Base no registrada';
        $row['resumen'] = $ccLabel . ' → ' . $baseLabel;
        $row['payload'] = $payload;
        return $row;
    }

    /**
     * Inserta eventos faltantes desde wa_campanas_masivas.
     */
    public function backfillCampanasMasivas(): int {
        $sql = "SELECT cm.*, b.nombre AS base_nombre, u.nombre AS coord_nombre
                FROM wa_campanas_masivas cm
                LEFT JOIN base_clientes b ON b.id_base = cm.base_id
                LEFT JOIN usuarios u ON u.cedula COLLATE utf8mb4_unicode_ci = cm.coordinador_cedula
                ORDER BY cm.id ASC";
        $rows = $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $created = 0;
        foreach ($rows as $cm) {
            $cmId = (int)$cm['id'];
            if ($this->findCampanaMasivaEventId($cmId)) {
                continue;
            }
            $enviados = (int)($cm['enviados'] ?? 0);
            $total = (int)($cm['total'] ?? 0);
            $nombre = trim((string)($cm['coord_nombre'] ?? ''));
            if ($nombre === '') {
                $nombre = (string)($cm['coordinador_cedula'] ?? 'Coordinador');
            }
            $base = (string)($cm['base_nombre'] ?? ('Base #' . (int)($cm['base_id'] ?? 0)));
            $tpl = (string)($cm['template_name'] ?? '');
            $resumen = sprintf(
                'Masivo #%d · %d enviados de %d · %s',
                $cmId,
                $enviados,
                $total,
                $nombre
            );
            $eventId = $this->create([
                'tipo' => 'campana_masiva',
                'actor_cedula' => (string)($cm['coordinador_cedula'] ?? ''),
                'actor_nombre' => $nombre,
                'resumen' => $resumen,
                'payload' => [
                    'campana_masiva_id' => $cmId,
                    'base_id' => (int)($cm['base_id'] ?? 0),
                    'base_nombre' => $base,
                    'template_name' => $tpl,
                    'enviados' => $enviados,
                    'total' => $total,
                    'errores' => (int)($cm['errores'] ?? 0),
                    'estado' => (string)($cm['estado'] ?? ''),
                ],
            ]);
            if (!empty($cm['created_at']) && $eventId > 0) {
                $upd = $this->pdo->prepare(
                    'UPDATE wa_historial_eventos SET created_at = ? WHERE id = ?'
                );
                $upd->execute([(string)$cm['created_at'], $eventId]);
            }
            $created++;
        }
        return $created;
    }

    public function ensureTableExists(): bool {
        try {
            $this->pdo->query('SELECT 1 FROM wa_historial_eventos LIMIT 1');
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }
}
