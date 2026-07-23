<?php
class CampanaModel {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    private function mapCampanaRow(?array $row): ?array {
        if (!$row) {
            return null;
        }
        return [
            'id' => (int)($row['id_campana'] ?? 0),
            'id_campana' => (int)($row['id_campana'] ?? 0),
            'nombre' => $row['nombre'] ?? '',
            'descripcion' => $row['descripcion'] ?? '',
            'estado' => $row['estado'] ?? 'activa',
            'creado_por' => $row['creado_por'] ?? null,
            'fecha_creacion' => $row['fecha_creacion'] ?? null,
            'fecha_actualizacion' => $row['fecha_actualizacion'] ?? null,
            'creador_nombre' => $row['creador_nombre'] ?? null,
            'total_coordinadores' => (int)($row['total_coordinadores'] ?? 0),
            'total_asesores' => (int)($row['total_asesores'] ?? 0),
            'total_bases' => (int)($row['total_bases'] ?? 0),
        ];
    }

    public function createCampana(string $nombre, string $descripcion, string $creadoPor): int|false {
        $stmt = $this->pdo->prepare("
            INSERT INTO campanas (nombre, descripcion, estado, creado_por)
            VALUES (?, ?, 'activa', ?)
        ");
        if (!$stmt->execute([trim($nombre), trim($descripcion), (string)$creadoPor])) {
            return false;
        }
        return (int)$this->pdo->lastInsertId();
    }

    public function updateCampana(int $id, string $nombre, string $descripcion, string $estado): bool {
        $stmt = $this->pdo->prepare("
            UPDATE campanas
            SET nombre = ?, descripcion = ?, estado = ?
            WHERE id_campana = ?
        ");
        return $stmt->execute([trim($nombre), trim($descripcion), $estado, $id]);
    }

    public function getAllCampanas(): array {
        $stmt = $this->pdo->query("
            SELECT c.*,
                   u.nombre AS creador_nombre,
                   (SELECT COUNT(*) FROM campana_coordinadores cc
                    WHERE cc.campana_id = c.id_campana AND cc.estado = 'activo') AS total_coordinadores,
                   (SELECT COUNT(*) FROM campana_asesores ca
                    WHERE ca.campana_id = c.id_campana AND ca.estado = 'activo') AS total_asesores,
                   (SELECT COUNT(*) FROM base_clientes b
                    WHERE b.campana_id = c.id_campana AND b.estado = 'activo') AS total_bases
            FROM campanas c
            LEFT JOIN usuarios u ON u.cedula = c.creado_por
            ORDER BY c.fecha_creacion DESC
        ");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_values(array_filter(array_map([$this, 'mapCampanaRow'], $rows)));
    }

    public function getCampanaById(int $id): ?array {
        $stmt = $this->pdo->prepare("
            SELECT c.*,
                   u.nombre AS creador_nombre,
                   (SELECT COUNT(*) FROM campana_coordinadores cc
                    WHERE cc.campana_id = c.id_campana AND cc.estado = 'activo') AS total_coordinadores,
                   (SELECT COUNT(*) FROM campana_asesores ca
                    WHERE ca.campana_id = c.id_campana AND ca.estado = 'activo') AS total_asesores,
                   (SELECT COUNT(*) FROM base_clientes b
                    WHERE b.campana_id = c.id_campana AND b.estado = 'activo') AS total_bases
            FROM campanas c
            LEFT JOIN usuarios u ON u.cedula = c.creado_por
            WHERE c.id_campana = ?
            LIMIT 1
        ");
        $stmt->execute([$id]);
        return $this->mapCampanaRow($stmt->fetch(PDO::FETCH_ASSOC) ?: null);
    }

    public function getCampanasByCoordinador(string $coordinadorCedula): array {
        $stmt = $this->pdo->prepare("
            SELECT c.*, u.nombre AS creador_nombre
            FROM campanas c
            INNER JOIN campana_coordinadores cc ON cc.campana_id = c.id_campana
            LEFT JOIN usuarios u ON u.cedula = c.creado_por
            WHERE cc.coordinador_cedula = ?
              AND cc.estado = 'activo'
              AND c.estado = 'activa'
            ORDER BY c.nombre ASC
        ");
        $stmt->execute([(string)$coordinadorCedula]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_values(array_filter(array_map([$this, 'mapCampanaRow'], $rows)));
    }

    public function coordinadorPerteneceACampana(string $coordinadorCedula, int $campanaId): bool {
        $stmt = $this->pdo->prepare("
            SELECT 1 FROM campana_coordinadores
            WHERE coordinador_cedula = ? AND campana_id = ? AND estado = 'activo'
            LIMIT 1
        ");
        $stmt->execute([(string)$coordinadorCedula, $campanaId]);
        return (bool)$stmt->fetchColumn();
    }

    public function coordinadorTieneAccesoABase(string $coordinadorCedula, int $baseId): bool {
        $stmt = $this->pdo->prepare("
            SELECT 1
            FROM base_clientes b
            WHERE b.id_base = ?
              AND (
                b.creado_por = ?
                OR (
                    b.campana_id IS NOT NULL
                    AND EXISTS (
                        SELECT 1 FROM campana_coordinadores cc
                        WHERE cc.campana_id = b.campana_id
                          AND cc.coordinador_cedula = ?
                          AND cc.estado = 'activo'
                    )
                )
              )
            LIMIT 1
        ");
        $stmt->execute([(int)$baseId, (string)$coordinadorCedula, (string)$coordinadorCedula]);
        return (bool)$stmt->fetchColumn();
    }

    public function getCampanaIdByBase(int $baseId): ?int {
        $stmt = $this->pdo->prepare("SELECT campana_id FROM base_clientes WHERE id_base = ? LIMIT 1");
        $stmt->execute([(int)$baseId]);
        $val = $stmt->fetchColumn();
        return $val !== false && $val !== null ? (int)$val : null;
    }

    public function getCoordinadoresByCampana(int $campanaId): array {
        $stmt = $this->pdo->prepare("
            SELECT u.cedula AS id, u.cedula, u.nombre AS nombre_completo, u.usuario,
                   cc.fecha_asignacion, cc.estado AS estado_asignacion
            FROM campana_coordinadores cc
            INNER JOIN usuarios u ON u.cedula = cc.coordinador_cedula
            WHERE cc.campana_id = ? AND cc.estado = 'activo'
            ORDER BY u.nombre
        ");
        $stmt->execute([$campanaId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCoordinadoresDisponibles(int $campanaId): array {
        $stmt = $this->pdo->prepare("
            SELECT u.cedula AS id, u.cedula, u.nombre AS nombre_completo, u.usuario
            FROM usuarios u
            WHERE u.rol = 'cordinador' AND u.estado = 'activo'
              AND u.cedula NOT IN (
                SELECT cc.coordinador_cedula FROM campana_coordinadores cc
                WHERE cc.campana_id = ? AND cc.estado = 'activo'
              )
            ORDER BY u.nombre
        ");
        $stmt->execute([$campanaId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function asignarCoordinador(int $campanaId, string $coordinadorCedula, string $asignadoPor): bool {
        $stmt = $this->pdo->prepare("
            SELECT id_campana_coordinador FROM campana_coordinadores
            WHERE campana_id = ? AND coordinador_cedula = ? LIMIT 1
        ");
        $stmt->execute([$campanaId, (string)$coordinadorCedula]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            $stmt = $this->pdo->prepare("
                UPDATE campana_coordinadores
                SET estado = 'activo', asignado_por = ?, fecha_asignacion = CURRENT_TIMESTAMP
                WHERE id_campana_coordinador = ?
            ");
            return $stmt->execute([(string)$asignadoPor, (int)$existing['id_campana_coordinador']]);
        }

        $stmt = $this->pdo->prepare("
            INSERT INTO campana_coordinadores (campana_id, coordinador_cedula, estado, asignado_por)
            VALUES (?, ?, 'activo', ?)
        ");
        return $stmt->execute([$campanaId, (string)$coordinadorCedula, (string)$asignadoPor]);
    }

    public function liberarCoordinador(int $campanaId, string $coordinadorCedula): bool {
        $stmt = $this->pdo->prepare("
            UPDATE campana_coordinadores SET estado = 'inactivo'
            WHERE campana_id = ? AND coordinador_cedula = ? AND estado = 'activo'
        ");
        return $stmt->execute([$campanaId, (string)$coordinadorCedula]);
    }

    public function getAsesoresByCampana(int $campanaId): array {
        $stmt = $this->pdo->prepare("
            SELECT u.cedula AS id, u.cedula, u.nombre AS nombre_completo, u.usuario,
                   ca.fecha_asignacion, ca.estado AS estado_asignacion
            FROM campana_asesores ca
            INNER JOIN usuarios u ON u.cedula = ca.asesor_cedula
            WHERE ca.campana_id = ? AND ca.estado = 'activo' AND u.estado = 'activo'
            ORDER BY u.nombre
        ");
        $stmt->execute([$campanaId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAsesoresByCampanasDelCoordinador(string $coordinadorCedula): array {
        $stmt = $this->pdo->prepare("
            SELECT DISTINCT u.cedula AS id, u.cedula, u.nombre AS nombre_completo, u.usuario,
                   ca.fecha_asignacion, ca.estado AS estado_asignacion
            FROM campana_asesores ca
            INNER JOIN campana_coordinadores cc ON cc.campana_id = ca.campana_id
            INNER JOIN usuarios u ON u.cedula = ca.asesor_cedula
            WHERE cc.coordinador_cedula = ?
              AND cc.estado = 'activo'
              AND ca.estado = 'activo'
              AND u.estado = 'activo'
            ORDER BY u.nombre
        ");
        $stmt->execute([(string)$coordinadorCedula]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAsesoresDisponibles(int $campanaId): array {
        $stmt = $this->pdo->prepare("
            SELECT u.cedula AS id, u.cedula, u.nombre AS nombre_completo, u.usuario
            FROM usuarios u
            WHERE u.rol = 'asesor' AND u.estado = 'activo'
              AND u.cedula NOT IN (
                SELECT ca.asesor_cedula FROM campana_asesores ca
                WHERE ca.campana_id = ? AND ca.estado = 'activo'
              )
              AND u.cedula NOT IN (
                SELECT ca2.asesor_cedula FROM campana_asesores ca2
                WHERE ca2.estado = 'activo'
              )
            ORDER BY u.nombre
        ");
        $stmt->execute([$campanaId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function asignarAsesor(int $campanaId, string $asesorCedula, string $asignadoPor): bool {
        $stmt = $this->pdo->prepare("UPDATE campana_asesores SET estado = 'inactivo' WHERE asesor_cedula = ? AND estado = 'activo'");
        $stmt->execute([(string)$asesorCedula]);

        $stmt = $this->pdo->prepare("
            SELECT id_campana_asesor FROM campana_asesores
            WHERE campana_id = ? AND asesor_cedula = ? LIMIT 1
        ");
        $stmt->execute([$campanaId, (string)$asesorCedula]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            $stmt = $this->pdo->prepare("
                UPDATE campana_asesores
                SET estado = 'activo', asignado_por = ?, fecha_asignacion = CURRENT_TIMESTAMP
                WHERE id_campana_asesor = ?
            ");
            return $stmt->execute([(string)$asignadoPor, (int)$existing['id_campana_asesor']]);
        }

        $stmt = $this->pdo->prepare("
            INSERT INTO campana_asesores (campana_id, asesor_cedula, estado, asignado_por)
            VALUES (?, ?, 'activo', ?)
        ");
        return $stmt->execute([$campanaId, (string)$asesorCedula, (string)$asignadoPor]);
    }

    public function liberarAsesor(int $campanaId, string $asesorCedula): bool {
        $stmt = $this->pdo->prepare("
            UPDATE campana_asesores SET estado = 'inactivo'
            WHERE campana_id = ? AND asesor_cedula = ? AND estado = 'activo'
        ");
        return $stmt->execute([$campanaId, (string)$asesorCedula]);
    }

    public function registrarAuditoria(
        string $coordinadorCedula,
        ?int $campanaId,
        string $accion,
        string $entidad,
        ?int $entidadId = null,
        ?array $detalle = null
    ): bool {
        $stmt = $this->pdo->prepare("
            INSERT INTO auditoria_coordinadores
                (coordinador_cedula, campana_id, accion, entidad, entidad_id, detalle)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $json = $detalle !== null ? json_encode($detalle, JSON_UNESCAPED_UNICODE) : null;
        return $stmt->execute([
            (string)$coordinadorCedula,
            $campanaId,
            $accion,
            $entidad,
            $entidadId,
            $json,
        ]);
    }

    public function getAuditoriaByCampana(int $campanaId, int $limit = 200): array {
        $limit = max(1, min(500, (int)$limit));
        $stmt = $this->pdo->prepare("
            SELECT a.*, u.nombre AS coordinador_nombre
            FROM auditoria_coordinadores a
            LEFT JOIN usuarios u ON u.cedula = a.coordinador_cedula
            WHERE a.campana_id = ?
            ORDER BY a.fecha DESC
            LIMIT $limit
        ");
        $stmt->execute([$campanaId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
