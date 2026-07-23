<?php 
require_once __DIR__ . '/CampanaModel.php';

class CargaExcelModel {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * El sistema histórico usaba `cargas_excel`. En el dump nuevo la entidad equivalente es `base_clientes`.
     * Para no tocar vistas/controladores, devolvemos aliases con los nombres antiguos.
     */
    private function mapBaseRow(?array $row): ?array {
        if (!$row) return null;

        $estadoDb = $row['estado'] ?? 'activo';
        $estadoUi = $estadoDb === 'activo' ? 'activa' : ($estadoDb === 'inactivo' ? 'inactiva' : $estadoDb);

        return [
            'id' => (int)($row['id_base'] ?? 0),
            'nombre_cargue' => $row['nombre'] ?? null,
            'usuario_id' => $row['creado_por'] ?? null,
            'usuario_coordinador_id' => $row['creado_por'] ?? null,
            'fecha_cargue' => $row['fecha_actualizacion'] ?? null,
            'estado' => $estadoUi,
            // En el dump no existe; asumimos habilitado si la base está activa.
            'estado_habilitado' => ($estadoDb === 'activo') ? 'habilitado' : 'deshabilitado',

            // Campos propios del dump
            'total_clientes' => (int)($row['total_clientes'] ?? 0),
            'total_obligaciones' => (int)($row['total_obligaciones'] ?? 0),
            'estado_db' => $estadoDb,
        ];
    }

    public function getAllCargas() {
        $stmt = $this->pdo->query("
            SELECT b.*, u.nombre as coordinador_nombre
            FROM base_clientes b
            LEFT JOIN usuarios u ON b.creado_por = u.cedula
            ORDER BY b.fecha_actualizacion DESC
        ");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $mapped = array_values(array_filter(array_map([$this, 'mapBaseRow'], $rows)));
        // Inyectar coordinador_nombre para compatibilidad con vistas existentes si lo usan.
        foreach ($mapped as $i => $m) {
            $mapped[$i]['coordinador_nombre'] = $rows[$i]['coordinador_nombre'] ?? null;
        }
        return $mapped;
    }

    /**
     * Obtiene solo las cargas del coordinador específico
     */
    public function getCargasByCoordinador($coordinadorId, $soloHabilitadas = true) {
        $sql = "
            SELECT DISTINCT b.*, u.nombre as coordinador_nombre
            FROM base_clientes b
            LEFT JOIN usuarios u ON b.creado_por = u.cedula
            LEFT JOIN campana_coordinadores cc ON cc.campana_id = b.campana_id
                AND cc.coordinador_cedula = ?
                AND cc.estado = 'activo'
            WHERE (b.creado_por = ? OR cc.id_campana_coordinador IS NOT NULL)
        ";

        if ($soloHabilitadas) {
            $sql .= " AND b.estado = 'activo'";
        }

        $sql .= " ORDER BY b.fecha_actualizacion DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([(string)$coordinadorId, (string)$coordinadorId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $mapped = array_values(array_filter(array_map([$this, 'mapBaseRow'], $rows)));
        foreach ($mapped as $i => $m) {
            $mapped[$i]['coordinador_nombre'] = $rows[$i]['coordinador_nombre'] ?? null;
        }
        return $mapped;
    }

    /**
     * Obtiene todas las bases activas y habilitadas del sistema.
     * Se usa desde el dashboard del administrador para cargas masivas de gestión.
     */
    public function getCargasActivas() {
        $sql = "
            SELECT b.*, u.nombre as coordinador_nombre
            FROM base_clientes b
            LEFT JOIN usuarios u ON b.creado_por = u.cedula
            WHERE b.estado = 'activo'
            ORDER BY b.nombre ASC, b.fecha_actualizacion DESC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $mapped = array_values(array_filter(array_map([$this, 'mapBaseRow'], $rows)));
        foreach ($mapped as $i => $m) {
            $mapped[$i]['coordinador_nombre'] = $rows[$i]['coordinador_nombre'] ?? null;
        }
        return $mapped;
    }

    /**
     * Obtiene una carga específica verificando que pertenezca al coordinador
     */
    public function getCargaByIdAndCoordinador($cargaId, $coordinadorId) {
        $stmt = $this->pdo->prepare("
            SELECT b.*, u.nombre as coordinador_nombre
            FROM base_clientes b
            LEFT JOIN usuarios u ON b.creado_por = u.cedula
            LEFT JOIN campana_coordinadores cc ON cc.campana_id = b.campana_id
                AND cc.coordinador_cedula = ?
                AND cc.estado = 'activo'
            WHERE b.id_base = ?
              AND (b.creado_por = ? OR cc.id_campana_coordinador IS NOT NULL)
            LIMIT 1
        ");
        $stmt->execute([(string)$coordinadorId, (int)$cargaId, (string)$coordinadorId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $mapped = $this->mapBaseRow($row);
        if ($mapped) {
            $mapped['coordinador_nombre'] = $row['coordinador_nombre'] ?? null;
        }
        return $mapped;
    }

    /**
     * Obtiene una carga por nombre y coordinador
     */
    public function getCargaByNombre($nombreCargue, $coordinadorId) {
        $sql = "SELECT * FROM base_clientes WHERE nombre = ? AND creado_por = ? LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([(string)$nombreCargue, (string)$coordinadorId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $this->mapBaseRow(is_array($row) ? $row : null);
    }

    /**
     * Obtiene la carga consolidada del coordinador (solo una)
     */
    public function getCargaConsolidada($coordinadorId) {
        $sql = "SELECT * FROM base_clientes WHERE creado_por = ? AND nombre = 'BASE_DATOS_CONSOLIDADA' LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([(string)$coordinadorId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $this->mapBaseRow(is_array($row) ? $row : null);
    }

    /**
     * Crea la carga consolidada del coordinador
     */
    public function crearCargaConsolidada($coordinadorId) {
        $sql = "INSERT INTO base_clientes (nombre, total_clientes, total_obligaciones, creado_por, estado)
                VALUES ('BASE_DATOS_CONSOLIDADA', 0, 0, ?, 'activo')";
        $stmt = $this->pdo->prepare($sql);
        if ($stmt->execute([(string)$coordinadorId])) {
            return (int)$this->pdo->lastInsertId();
        }
        return false;
    }

    /**
     * Crea una nueva carga
     */
    public function crearCarga($nombreCargue, $coordinadorId, $campanaId = null) {
        if ($campanaId !== null) {
            $sql = "INSERT INTO base_clientes (nombre, total_clientes, total_obligaciones, creado_por, campana_id, estado)
                    VALUES (?, 0, 0, ?, ?, 'activo')";
            $stmt = $this->pdo->prepare($sql);
            $ok = $stmt->execute([(string)$nombreCargue, (string)$coordinadorId, (int)$campanaId]);
        } else {
            $sql = "INSERT INTO base_clientes (nombre, total_clientes, total_obligaciones, creado_por, estado)
                    VALUES (?, 0, 0, ?, 'activo')";
            $stmt = $this->pdo->prepare($sql);
            $ok = $stmt->execute([(string)$nombreCargue, (string)$coordinadorId]);
        }
        if ($ok) {
            return (int)$this->pdo->lastInsertId();
        }
        return false;
    }

    /**
     * Crea una nueva base de datos independiente
     */
    public function crearBaseDatosIndependiente($nombreBaseDatos, $coordinadorId, $campanaId = null) {
        return $this->crearCarga($nombreBaseDatos, $coordinadorId, $campanaId);
    }

    /**
     * Obtiene estadísticas de una base de datos
     */
    public function getEstadisticasBaseDatos($cargaId) {
        $baseId = (int)$cargaId;

        $stmt = $this->pdo->prepare("SELECT COUNT(*) as total_clientes FROM clientes WHERE base_id = ?");
        $stmt->execute([$baseId]);
        $totalClientes = (int)($stmt->fetch(PDO::FETCH_ASSOC)['total_clientes'] ?? 0);

        $stmt = $this->pdo->prepare("SELECT COUNT(*) as total_obligaciones FROM obligaciones WHERE base_id = ?");
        $stmt->execute([$baseId]);
        $totalObligaciones = (int)($stmt->fetch(PDO::FETCH_ASSOC)['total_obligaciones'] ?? 0);

        // En el dump no existe asignación por cliente. Usamos 0 para compatibilidad.
        $clientesAsignados = 0;
        $clientesPorAsignar = $totalClientes;

        $stmt = $this->pdo->prepare("
            SELECT u.cedula as id, u.nombre as nombre_completo, u.usuario
            FROM asignacion_base_asesores aba
            JOIN usuarios u ON aba.asesor_cedula = u.cedula
            WHERE aba.base_id = ? AND aba.estado = 'activa'
            ORDER BY u.nombre
        ");
        $stmt->execute([$baseId]);
        $asesoresAsignados = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'total_clientes' => $totalClientes,
            'total_obligaciones' => $totalObligaciones,
            'clientes_asignados' => $clientesAsignados,
            'clientes_por_asignar' => $clientesPorAsignar,
            'asesores_asignados' => $asesoresAsignados
        ];
    }

    /**
     * Asigna un asesor a una base de datos
     */
    public function asignarAsesorABaseDatos($cargaId, $asesorId, $asignadoPor = null) {
        $baseId = (int)$cargaId;
        $asesorCedula = (string)$asesorId;

        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare("
                SELECT id_base_asesor
                FROM asignacion_base_asesores
                WHERE base_id = ? AND asesor_cedula = ?
                LIMIT 1
            ");
            $stmt->execute([$baseId, $asesorCedula]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                $stmt = $this->pdo->prepare("
                    UPDATE asignacion_base_asesores
                    SET estado = 'activa', asignado_por = ?, fecha_actualizacion = CURRENT_TIMESTAMP
                    WHERE id_base_asesor = ?
                ");
                $stmt->execute([$asignadoPor ? (string)$asignadoPor : null, (int)$existing['id_base_asesor']]);
            } else {
                $stmt = $this->pdo->prepare("
                    INSERT INTO asignacion_base_asesores (base_id, asesor_cedula, asignado_por, estado, fecha_asignacion)
                    VALUES (?, ?, ?, 'activa', CURRENT_TIMESTAMP)
                ");
                $stmt->execute([$baseId, $asesorCedula, $asignadoPor ? (string)$asignadoPor : null]);
            }

            if ($asignadoPor) {
                $campanaModel = new CampanaModel($this->pdo);
                $campanaId = $campanaModel->getCampanaIdByBase($baseId);
                $campanaModel->registrarAuditoria(
                    (string)$asignadoPor,
                    $campanaId,
                    'asignar_asesor_base',
                    'asignacion_base_asesores',
                    $baseId,
                    ['asesor_cedula' => $asesorCedula, 'base_id' => $baseId]
                );
            }

            $this->pdo->commit();
            return 1;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Error al asignar asesor a base de datos: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Libera un asesor de una base de datos
     */
    public function liberarAsesorDeBaseDatos($cargaId, $asesorId, $liberadoPor = null) {
        $this->pdo->beginTransaction();
        try {
            $baseId = (int)$cargaId;
            $asignacionesActualizadas = 0;

            if ($asesorId === null) {
                $stmt = $this->pdo->prepare("
                    UPDATE asignacion_base_asesores
                    SET estado = 'inactiva', fecha_actualizacion = CURRENT_TIMESTAMP
                    WHERE base_id = ? AND estado = 'activa'
                ");
                $stmt->execute([$baseId]);
                $asignacionesActualizadas = $stmt->rowCount();
            } else {
                $stmt = $this->pdo->prepare("
                    UPDATE asignacion_base_asesores
                    SET estado = 'inactiva', fecha_actualizacion = CURRENT_TIMESTAMP
                    WHERE base_id = ? AND asesor_cedula = ? AND estado = 'activa'
                ");
                $stmt->execute([$baseId, (string)$asesorId]);
                $asignacionesActualizadas = $stmt->rowCount();
            }

            if ($liberadoPor && $asignacionesActualizadas > 0) {
                $campanaModel = new CampanaModel($this->pdo);
                $campanaId = $campanaModel->getCampanaIdByBase($baseId);
                $campanaModel->registrarAuditoria(
                    (string)$liberadoPor,
                    $campanaId,
                    'liberar_asesor_base',
                    'asignacion_base_asesores',
                    $baseId,
                    ['asesor_cedula' => $asesorId !== null ? (string)$asesorId : 'todos', 'base_id' => $baseId]
                );
            }

            $this->pdo->commit();
            return $asignacionesActualizadas;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Error al liberar asesor de base de datos: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Obtiene asesores disponibles para asignar
     * Excluye los asesores que ya tienen acceso a la base de datos especificada
     */
    public function getAsesoresDisponibles($coordinadorId, $cargaId = null) {
        $coordinadorCedula = (string)$coordinadorId;

        if ($cargaId) {
            $baseId = (int)$cargaId;
            $stmt = $this->pdo->prepare("
                SELECT u.cedula as id, u.nombre as nombre_completo, u.usuario
                FROM usuarios u
                INNER JOIN campana_asesores ca ON u.cedula = ca.asesor_cedula
                INNER JOIN base_clientes b ON b.id_base = ?
                WHERE ca.campana_id = b.campana_id
                  AND ca.estado = 'activo'
                  AND u.rol = 'asesor'
                  AND u.estado = 'activo'
                  AND b.campana_id IS NOT NULL
                  AND u.cedula NOT IN (
                    SELECT DISTINCT aba.asesor_cedula
                    FROM asignacion_base_asesores aba
                    WHERE aba.base_id = ? AND aba.estado = 'activa'
                  )
                ORDER BY u.nombre
            ");
            $stmt->execute([$baseId, $baseId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        $stmt = $this->pdo->prepare("
            SELECT DISTINCT u.cedula as id, u.nombre as nombre_completo, u.usuario
            FROM usuarios u
            INNER JOIN campana_asesores ca ON u.cedula = ca.asesor_cedula
            INNER JOIN campana_coordinadores cc ON cc.campana_id = ca.campana_id
            WHERE cc.coordinador_cedula = ?
              AND cc.estado = 'activo'
              AND ca.estado = 'activo'
              AND u.rol = 'asesor'
              AND u.estado = 'activo'
            ORDER BY u.nombre
        ");
        $stmt->execute([$coordinadorCedula]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene una carga por ID
     */
    public function getCargaById($cargaId) {
        $stmt = $this->pdo->prepare("SELECT * FROM base_clientes WHERE id_base = ? LIMIT 1");
        $stmt->execute([(int)$cargaId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $this->mapBaseRow(is_array($row) ? $row : null);
    }

    /**
     * Obtiene asesores asignados a una base de datos
     */
    public function getAsesoresAsignadosABase($cargaId) {
        $stmt = $this->pdo->prepare("
            SELECT DISTINCT u.cedula as id, u.nombre as nombre_completo, u.usuario
            FROM usuarios u
            INNER JOIN asignacion_base_asesores aba ON u.cedula = aba.asesor_cedula
            WHERE aba.base_id = ? AND aba.estado = 'activa'
            ORDER BY u.nombre
        ");
        $stmt->execute([(int)$cargaId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene asesores asignados al coordinador Y con acceso a esta base (para "Ver todos").
     * Filtra por asignaciones_asesor_coordinador y asignaciones_base_asesor.
     */
    public function getAsesoresAsignadosABaseParaCoordinador($cargaId, $coordinadorId) {
        $stmt = $this->pdo->prepare("
            SELECT DISTINCT u.cedula as id, u.nombre as nombre_completo, u.usuario
            FROM usuarios u
            INNER JOIN campana_asesores ca ON u.cedula = ca.asesor_cedula
            INNER JOIN campana_coordinadores cc ON cc.campana_id = ca.campana_id
            INNER JOIN asignacion_base_asesores aba ON u.cedula = aba.asesor_cedula
            WHERE cc.coordinador_cedula = ?
              AND cc.estado = 'activo'
              AND ca.estado = 'activo'
              AND aba.base_id = ?
              AND aba.estado = 'activa'
              AND u.rol = 'asesor'
              AND u.estado = 'activo'
            ORDER BY u.nombre
        ");
        $stmt->execute([(string)$coordinadorId, (int)$cargaId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Elimina una base de datos de clientes
     */
    public function eliminarBaseDatos($cargaId) {
        $this->pdo->beginTransaction();
        try {
            $baseId = (int)$cargaId;

            // 1. Eliminar asignaciones de asesores a la base.
            $stmt = $this->pdo->prepare("DELETE FROM asignacion_base_asesores WHERE base_id = ?");
            $stmt->execute([$baseId]);

            // 2. Eliminar historial de gestiones asociado a obligaciones/clientes de la base.
            $stmt = $this->pdo->prepare("
                DELETE hg
                FROM historial_gestiones hg
                INNER JOIN obligaciones o ON hg.obligacion_id = o.id_obligacion
                WHERE o.base_id = ?
            ");
            $stmt->execute([$baseId]);

            // 3. Eliminar acuerdos asociados a gestiones (si aplica por FK).
            // En dump: acuerdos.gestion_id referencia historial? (según FK fk_acuerdo_gestion).
            // Para evitar errores por FK, borramos por join si existe coincidencia directa.
            $stmt = $this->pdo->prepare("
                DELETE a
                FROM acuerdos a
                LEFT JOIN historial_gestiones hg ON a.gestion_id = hg.id_gestion
                LEFT JOIN obligaciones o ON hg.obligacion_id = o.id_obligacion
                WHERE o.base_id = ?
            ");
            $stmt->execute([$baseId]);

            // 4. Eliminar obligaciones y clientes.
            $stmt = $this->pdo->prepare("DELETE FROM obligaciones WHERE base_id = ?");
            $stmt->execute([$baseId]);

            $stmt = $this->pdo->prepare("DELETE FROM clientes WHERE base_id = ?");
            $stmt->execute([$baseId]);

            // 5. Eliminar tareas asociadas.
            $stmt = $this->pdo->prepare("DELETE FROM tareas WHERE base_id = ?");
            $stmt->execute([$baseId]);

            // 6. Eliminar la base.
            $stmt = $this->pdo->prepare("DELETE FROM base_clientes WHERE id_base = ?");
            $stmt->execute([$baseId]);

            $this->pdo->commit();
            return true;

        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Error al eliminar base de datos: " . $e->getMessage());
            throw $e;
        }
    }
}
?>
