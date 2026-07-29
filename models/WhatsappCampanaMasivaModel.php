<?php
/**
 * Campañas WhatsApp masivas (plantillas Meta vía Kommo).
 */
class WhatsappCampanaMasivaModel {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function getById(int $id): ?array {
        $stmt = $this->pdo->prepare('SELECT * FROM wa_campanas_masivas WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function listByCoordinador(string $cedula, int $limit = 30): array {
        $limit = max(1, min(100, $limit));
        $stmt = $this->pdo->prepare(
            "SELECT cm.*, b.nombre AS base_nombre
             FROM wa_campanas_masivas cm
             LEFT JOIN base_clientes b ON b.id_base = cm.base_id
             WHERE cm.coordinador_cedula = ?
             ORDER BY cm.id DESC
             LIMIT {$limit}"
        );
        $stmt->execute([$cedula]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function create(array $data): int {
        $sql = "INSERT INTO wa_campanas_masivas
                (base_id, campana_id, coordinador_cedula, template_external_id, template_name,
                 template_language, body_preview, var_map, estado, total, enviados, errores)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 0)";
        $stmt = $this->pdo->prepare($sql);
        $varMap = $data['var_map'] ?? null;
        if (is_array($varMap)) {
            $varMap = json_encode($varMap, JSON_UNESCAPED_UNICODE);
        }
        $stmt->execute([
            (int)$data['base_id'],
            $data['campana_id'] ?? null,
            (string)$data['coordinador_cedula'],
            (string)($data['template_external_id'] ?? ''),
            (string)($data['template_name'] ?? ''),
            (string)($data['template_language'] ?? 'es'),
            $data['body_preview'] ?? null,
            $varMap,
            $data['estado'] ?? 'procesando',
            (int)($data['total'] ?? 0),
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function update(int $id, array $fields): bool {
        if (!$fields) {
            return false;
        }
        $allowed = [
            'estado', 'total', 'enviados', 'errores', 'body_preview',
            'template_external_id', 'template_name', 'template_language', 'var_map',
        ];
        $sets = [];
        $params = [];
        foreach ($fields as $k => $v) {
            if (!in_array($k, $allowed, true)) {
                continue;
            }
            if ($k === 'var_map' && is_array($v)) {
                $v = json_encode($v, JSON_UNESCAPED_UNICODE);
            }
            $sets[] = "`{$k}` = ?";
            $params[] = $v;
        }
        if (!$sets) {
            return false;
        }
        $params[] = $id;
        $stmt = $this->pdo->prepare('UPDATE wa_campanas_masivas SET ' . implode(', ', $sets) . ' WHERE id = ?');
        return $stmt->execute($params);
    }

    public function incrementCounter(int $id, string $field): void {
        if (!in_array($field, ['enviados', 'errores'], true)) {
            return;
        }
        $this->pdo->prepare("UPDATE wa_campanas_masivas SET `{$field}` = `{$field}` + 1 WHERE id = ?")->execute([$id]);
    }

    public function addDestinatario(array $data): int {
        $sql = "INSERT INTO wa_campana_destinatarios
                (campana_masiva_id, cliente_id, cedula, nombre, telefono_e164, conversacion_id, estado, error_msg)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            (int)$data['campana_masiva_id'],
            $data['cliente_id'] ?? null,
            (string)$data['cedula'],
            $data['nombre'] ?? null,
            $data['telefono_e164'] ?? null,
            $data['conversacion_id'] ?? null,
            $data['estado'] ?? 'pendiente',
            $data['error_msg'] ?? null,
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function listDestinatariosPendientes(int $campanaMasivaId, int $limit = 20): array {
        $limit = max(1, min(100, $limit));
        // Reencolar envíos colgados (p. ej. timeout entre marcar enviando y confirmar envío)
        $requeue = $this->pdo->prepare(
            "UPDATE wa_campana_destinatarios
             SET estado = 'pendiente'
             WHERE campana_masiva_id = ? AND estado = 'enviando'
               AND (kommo_message_id IS NULL OR kommo_message_id = '')"
        );
        $requeue->execute([$campanaMasivaId]);

        $stmt = $this->pdo->prepare(
            "SELECT * FROM wa_campana_destinatarios
             WHERE campana_masiva_id = ? AND estado IN ('pendiente', 'enviando')
             ORDER BY id ASC LIMIT {$limit}"
        );
        $stmt->execute([$campanaMasivaId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function updateDestinatario(int $id, array $fields): bool {
        $allowed = ['estado', 'kommo_message_id', 'error_msg', 'enviado_at', 'conversacion_id', 'telefono_e164'];
        $sets = [];
        $params = [];
        foreach ($fields as $k => $v) {
            if (!in_array($k, $allowed, true)) {
                continue;
            }
            $sets[] = "`{$k}` = ?";
            $params[] = $v;
        }
        if (!$sets) {
            return false;
        }
        $params[] = $id;
        $stmt = $this->pdo->prepare('UPDATE wa_campana_destinatarios SET ' . implode(', ', $sets) . ' WHERE id = ?');
        return $stmt->execute($params);
    }

    public function countDestinatariosByEstado(int $campanaMasivaId): array {
        $stmt = $this->pdo->prepare(
            'SELECT estado, COUNT(*) AS c FROM wa_campana_destinatarios WHERE campana_masiva_id = ? GROUP BY estado'
        );
        $stmt->execute([$campanaMasivaId]);
        $out = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $out[$row['estado']] = (int)$row['c'];
        }
        return $out;
    }

    /**
     * Resuelve cédulas contra clientes de una base.
     * @return array{encontrados:array,no_encontrados:array,sin_telefono:array}
     */
    public function previewCedulasEnBase(int $baseId, array $cedulas): array {
        require_once __DIR__ . '/../config/kommo.php';
        $norm = [];
        foreach ($cedulas as $c) {
            $c = preg_replace('/\D+/', '', (string)$c);
            if ($c !== '') {
                $norm[$c] = true;
            }
        }
        $cedulas = array_keys($norm);
        $encontrados = [];
        $sinTelefono = [];
        $foundSet = [];

        if ($cedulas) {
            $placeholders = implode(',', array_fill(0, count($cedulas), '?'));
            $sql = "SELECT id_cliente, cedula, nombre, tel1, tel2, tel3, tel4, tel5, tel6, tel7, tel8, tel9, tel10
                    FROM clientes WHERE base_id = ? AND REPLACE(REPLACE(cedula,'.',''),'-','') IN ({$placeholders})";
            // Also try raw cedula match
            $params = array_merge([$baseId], $cedulas);
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            // Fallback: match without stripping if first query sparse
            $stmt2 = $this->pdo->prepare(
                "SELECT id_cliente, cedula, nombre, tel1, tel2, tel3, tel4, tel5, tel6, tel7, tel8, tel9, tel10
                 FROM clientes WHERE base_id = ? AND cedula IN ({$placeholders})"
            );
            $stmt2->execute($params);
            foreach ($stmt2->fetchAll(PDO::FETCH_ASSOC) ?: [] as $r) {
                $rows[] = $r;
            }

            $byCed = [];
            foreach ($rows as $row) {
                $key = preg_replace('/\D+/', '', (string)$row['cedula']);
                if ($key === '' || isset($byCed[$key])) {
                    continue;
                }
                $byCed[$key] = $row;
            }

            foreach ($cedulas as $ced) {
                if (!isset($byCed[$ced])) {
                    continue;
                }
                $row = $byCed[$ced];
                $foundSet[$ced] = true;
                $e164 = null;
                foreach (['tel1','tel2','tel3','tel4','tel5','tel6','tel7','tel8','tel9','tel10'] as $f) {
                    $raw = trim((string)($row[$f] ?? ''));
                    if ($raw === '' || $raw === '0') {
                        continue;
                    }
                    $e164 = kommoNormalizePhoneE164($raw);
                    if ($e164) {
                        break;
                    }
                }
                $item = [
                    'cliente_id' => (int)$row['id_cliente'],
                    'cedula' => (string)$row['cedula'],
                    'nombre' => (string)($row['nombre'] ?? ''),
                    'telefono_e164' => $e164,
                ];
                if (!$e164) {
                    $sinTelefono[] = $item;
                } else {
                    $encontrados[] = $item;
                }
            }
        }

        $noEncontrados = [];
        foreach ($cedulas as $ced) {
            if (!isset($foundSet[$ced])) {
                $noEncontrados[] = $ced;
            }
        }

        return [
            'encontrados' => $encontrados,
            'sin_telefono' => $sinTelefono,
            'no_encontrados' => $noEncontrados,
        ];
    }
}
