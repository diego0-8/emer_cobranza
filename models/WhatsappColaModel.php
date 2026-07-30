<?php
/**
 * Cola de asignación de replies WA + dismiss de burbujas.
 */
class WhatsappColaModel {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function enqueueIfNeeded(int $conversacionId, ?int $campanaId): void {
        $stmt = $this->pdo->prepare(
            "SELECT id FROM wa_cola_asignacion
             WHERE conversacion_id = ? AND estado = 'esperando_asesor' LIMIT 1"
        );
        $stmt->execute([$conversacionId]);
        if ($stmt->fetchColumn()) {
            return;
        }
        $ins = $this->pdo->prepare(
            "INSERT INTO wa_cola_asignacion (conversacion_id, campana_id, estado)
             VALUES (?, ?, 'esperando_asesor')"
        );
        $ins->execute([$conversacionId, $campanaId]);
    }

    public function listEsperando(int $limit = 50): array {
        $limit = max(1, min(200, $limit));
        $stmt = $this->pdo->prepare(
            "SELECT q.*, c.asesor_notificacion_id, c.cliente_id, c.origen
             FROM wa_cola_asignacion q
             INNER JOIN wa_conversaciones c ON c.id = q.conversacion_id
             WHERE q.estado = 'esperando_asesor'
               AND (c.asesor_notificacion_id IS NULL OR c.asesor_notificacion_id = '')
               AND c.cliente_id IS NOT NULL
               AND c.estado IN ('abierta', 'cerrada')
             ORDER BY q.id ASC
             LIMIT {$limit}"
        );
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function markAsignado(int $colaId, string $asesorId): void {
        $stmt = $this->pdo->prepare(
            "UPDATE wa_cola_asignacion
             SET estado = 'asignado', asignado_a = ?, asignado_at = NOW()
             WHERE id = ?"
        );
        $stmt->execute([$asesorId, $colaId]);
    }

    public function dismiss(int $conversacionId, string $asesorId): void {
        $this->ensureDismissLiberadoColumn();
        // Ocultar del rail → sigue en Cola (+N). Resetea liberado.
        $stmt = $this->pdo->prepare(
            "INSERT INTO wa_burbuja_dismiss (conversacion_id, asesor_id, liberado, liberado_at)
             VALUES (?, ?, 0, NULL)
             ON DUPLICATE KEY UPDATE
               dismissed_at = CURRENT_TIMESTAMP,
               liberado = 0,
               liberado_at = NULL"
        );
        $stmt->execute([$conversacionId, $asesorId]);
    }

    /**
     * Saca el chat de la Cola (+N): no rail ni overflow hasta nuevo inbound / Mostrar.
     */
    public function liberarCola(int $conversacionId, string $asesorId): void {
        $this->ensureDismissLiberadoColumn();
        $stmt = $this->pdo->prepare(
            "INSERT INTO wa_burbuja_dismiss (conversacion_id, asesor_id, liberado, liberado_at)
             VALUES (?, ?, 1, CURRENT_TIMESTAMP)
             ON DUPLICATE KEY UPDATE
               dismissed_at = CURRENT_TIMESTAMP,
               liberado = 1,
               liberado_at = CURRENT_TIMESTAMP"
        );
        $stmt->execute([$conversacionId, $asesorId]);
    }

    public function undismiss(int $conversacionId, string $asesorId): void {
        $stmt = $this->pdo->prepare(
            'DELETE FROM wa_burbuja_dismiss WHERE conversacion_id = ? AND asesor_id = ?'
        );
        $stmt->execute([$conversacionId, $asesorId]);
    }

    public function isDismissed(int $conversacionId, string $asesorId): bool {
        $stmt = $this->pdo->prepare(
            'SELECT 1 FROM wa_burbuja_dismiss WHERE conversacion_id = ? AND asesor_id = ? LIMIT 1'
        );
        $stmt->execute([$conversacionId, $asesorId]);
        return (bool)$stmt->fetchColumn();
    }

    public function isLiberadoCola(int $conversacionId, string $asesorId): bool {
        $this->ensureDismissLiberadoColumn();
        $stmt = $this->pdo->prepare(
            'SELECT 1 FROM wa_burbuja_dismiss
             WHERE conversacion_id = ? AND asesor_id = ? AND liberado = 1 LIMIT 1'
        );
        $stmt->execute([$conversacionId, $asesorId]);
        return (bool)$stmt->fetchColumn();
    }

    public function ensureSchema(): void {
        $this->ensureDismissLiberadoColumn();
    }

    private $dismissLiberadoReady = false;

    private function ensureDismissLiberadoColumn(): void {
        if ($this->dismissLiberadoReady) {
            return;
        }
        try {
            $col = $this->pdo->query(
                "SHOW COLUMNS FROM wa_burbuja_dismiss LIKE 'liberado'"
            )->fetch(PDO::FETCH_ASSOC);
            if (!$col) {
                $this->pdo->exec(
                    "ALTER TABLE wa_burbuja_dismiss
                     ADD COLUMN liberado TINYINT(1) NOT NULL DEFAULT 0 AFTER dismissed_at,
                     ADD COLUMN liberado_at TIMESTAMP NULL DEFAULT NULL AFTER liberado"
                );
            }
        } catch (Throwable $e) {
            // Si falla, los callers verán el error al usar la columna.
        }
        $this->dismissLiberadoReady = true;
    }
}
