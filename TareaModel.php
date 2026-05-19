<?php
/**
 * Modelo de tareas adaptado al dump `emermedica_cobranza.sql`.
 *
 * Tablas:
 * - `tareas`
 * - `detalle_tareas`
 * - `base_clientes` (equivalente a cargas)
 */
class TareaModel {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    private function mapEstadoTareaDbToUi(string $estadoDb): string {
        $estadoDb = trim($estadoDb);
        // DB enum: 'pendiente','en progreso','completa','cancelada'
        if ($estadoDb === 'en progreso') return 'en_proceso';
        if ($estadoDb === 'completa') return 'completada';
        return $estadoDb;
    }

    private function mapEstadoTareaUiToDb(string $estadoUi): string {
        $estadoUi = trim($estadoUi);
        if ($estadoUi === 'en_proceso') return 'en progreso';
        if ($estadoUi === 'completada') return 'completa';
        return $estadoUi;
    }

    public function crearTarea($datos) {
        $clienteIds = $datos['cliente_ids'] ?? [];
        if (!is_array($clienteIds)) $clienteIds = [];

        // Nota: en dump el campo es `obligaciones_asignadas`. Lo dejamos NULL por ahora.

        $stmt = $this->pdo->prepare("
            INSERT INTO tareas (nombre_tarea, base_id, coordinador_cedula, asesor_cedula, estado, clientes_asignados, obligaciones_asignadas, fecha_creacion, fecha_completa)
            VALUES (?, ?, ?, ?, 'pendiente', ?, NULL, NOW(), NOW())
        ");

        $nombre = (string)($datos['nombre_tarea'] ?? ($datos['descripcion'] ?? 'Tarea'));
        $baseId = (int)($datos['carga_id'] ?? $datos['base_id'] ?? 0);
        $coordinador = (string)($datos['coordinador_id'] ?? $datos['coordinador_cedula'] ?? '');
        $asesor = (string)($datos['asesor_id'] ?? $datos['asesor_cedula'] ?? '');

        // #region agent log b7eaa7 tareaModel crearTarea before execute
        try { @file_put_contents(__DIR__ . '/../debug-b7eaa7.log', json_encode([
            'sessionId'=>'b7eaa7','runId'=>'pre','hypothesisId'=>'TM1',
            'location'=>'models/TareaModel.php:crearTarea:before',
            'message'=>'insert_prepare',
            'data'=>[
                'baseId'=>(int)$baseId,
                'coordinadorLen'=>strlen((string)$coordinador),
                'asesorLen'=>strlen((string)$asesor),
                'nombreLen'=>strlen((string)$nombre),
                'clienteIdsCount'=>is_array($clienteIds)?count($clienteIds):-1
            ],
            'timestamp'=>(int) round(microtime(true)*1000)
        ], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n", FILE_APPEND); } catch (Throwable $e) {}
        // #endregion

        $ok = $stmt->execute([$nombre, $baseId, $coordinador, $asesor, json_encode(array_values($clienteIds))]);
        if (!$ok) return false;

        $tareaId = (int)$this->pdo->lastInsertId();
        // #region agent log b7eaa7 tareaModel crearTarea after execute
        try { @file_put_contents(__DIR__ . '/../debug-b7eaa7.log', json_encode([
            'sessionId'=>'b7eaa7','runId'=>'pre','hypothesisId'=>'TM2',
            'location'=>'models/TareaModel.php:crearTarea:after',
            'message'=>'insert_ok',
            'data'=>['tareaId'=>(int)$tareaId],
            'timestamp'=>(int) round(microtime(true)*1000)
        ], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n", FILE_APPEND); } catch (Throwable $e) {}
        // #endregion

        // Poblar detalle_tareas (opcional)
        if (!empty($clienteIds)) {
            $stmtDet = $this->pdo->prepare("INSERT INTO detalle_tareas (tarea_id, cliente_id, gestionado) VALUES (?, ?, 'no')");
            foreach ($clienteIds as $cid) {
                $stmtDet->execute([$tareaId, (int)$cid]);
            }
        }

        return $tareaId;
    }

    public function getTareasByAsesor($asesorId, $estado = null) {
        $sql = "
            SELECT t.*, b.nombre as nombre_cargue, u.nombre as coordinador_nombre
            FROM tareas t
            JOIN base_clientes b ON t.base_id = b.id_base
            LEFT JOIN usuarios u ON t.coordinador_cedula = u.cedula
            WHERE t.asesor_cedula = ?
        ";
        $params = [(string)$asesorId];

        if ($estado) {
            $sql .= " AND t.estado = ?";
            $params[] = (string)$estado;
        }

        $sql .= " ORDER BY t.fecha_creacion DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $tareas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($tareas as &$t) {
            $t['id'] = $t['id_tarea'];
            $t['carga_id'] = $t['base_id'];
            $t['cliente_ids'] = json_decode((string)($t['clientes_asignados'] ?? '[]'), true) ?: [];
            $t['total_clientes'] = is_array($t['cliente_ids']) ? count($t['cliente_ids']) : 0;
        }

        return $tareas;
    }

    public function getTareasPendientesByAsesor($asesorId) {
        return $this->getTareasByAsesor($asesorId, 'pendiente');
    }

    public function actualizarEstadoTarea($tareaId, $nuevoEstado, $usuarioId) {
        $estadoDb = $this->mapEstadoTareaUiToDb((string)$nuevoEstado);
        $stmt = $this->pdo->prepare("UPDATE tareas SET estado = ?, fecha_completa = NOW() WHERE id_tarea = ?");
        return $stmt->execute([(string)$estadoDb, (int)$tareaId]);
    }

    public function tieneTareasPendientes($asesorId) {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) as total FROM tareas WHERE asesor_cedula = ? AND estado = 'pendiente'");
        $stmt->execute([(string)$asesorId]);
        return ((int)($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0)) > 0;
    }

    public function getClientesByTarea($tareaId) {
        $stmt = $this->pdo->prepare("
            SELECT
                c.*,
                dt.gestionado,
                b.nombre as nombre_base
            FROM detalle_tareas dt
            JOIN clientes c ON c.id_cliente = dt.cliente_id
            JOIN base_clientes b ON b.id_base = c.base_id
            WHERE dt.tarea_id = ?
            ORDER BY c.nombre ASC
        ");
        $stmt->execute([(int)$tareaId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getTareaByIdAndCoordinador($tareaId, $coordinadorCedula) {
        $stmt = $this->pdo->prepare("
            SELECT
                t.*,
                b.nombre as nombre_cargue,
                u.nombre as asesor_nombre
            FROM tareas t
            JOIN base_clientes b ON t.base_id = b.id_base
            LEFT JOIN usuarios u ON t.asesor_cedula = u.cedula
            WHERE t.id_tarea = ?
              AND t.coordinador_cedula = ?
            LIMIT 1
        ");
        $stmt->execute([(int)$tareaId, (string)$coordinadorCedula]);
        $t = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        if (!$t) return null;
        $t['id'] = $t['id_tarea'];
        $t['carga_id'] = $t['base_id'];
        $t['estado_ui'] = $this->mapEstadoTareaDbToUi((string)($t['estado'] ?? ''));

        // Conteos desde detalle_tareas (fuente de verdad)
        $stmt2 = $this->pdo->prepare("
            SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN gestionado = 'si' THEN 1 ELSE 0 END) AS gestionados
            FROM detalle_tareas
            WHERE tarea_id = ?
        ");
        $stmt2->execute([(int)$tareaId]);
        $row = $stmt2->fetch(PDO::FETCH_ASSOC) ?: [];
        $t['total_clientes'] = (int)($row['total'] ?? 0);
        $t['clientes_gestionados'] = (int)($row['gestionados'] ?? 0);
        return $t;
    }

    public function getTareasByCoordinador($coordinadorCedula) {
        $sql = "
            SELECT
                t.*,
                b.nombre as nombre_cargue,
                u.nombre as asesor_nombre,
                (SELECT COUNT(*) FROM detalle_tareas dt WHERE dt.tarea_id = t.id_tarea) AS total_clientes,
                (SELECT SUM(CASE WHEN dt.gestionado = 'si' THEN 1 ELSE 0 END) FROM detalle_tareas dt WHERE dt.tarea_id = t.id_tarea) AS clientes_gestionados
            FROM tareas t
            JOIN base_clientes b ON t.base_id = b.id_base
            LEFT JOIN usuarios u ON t.asesor_cedula = u.cedula
            WHERE t.coordinador_cedula = ?
            ORDER BY t.fecha_creacion DESC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([(string)$coordinadorCedula]);
        $tareas = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        foreach ($tareas as &$t) {
            $t['id'] = $t['id_tarea'];
            $t['carga_id'] = $t['base_id'];
            $t['estado'] = $this->mapEstadoTareaDbToUi((string)($t['estado'] ?? ''));
            // Mantener compatibilidad, aunque la fuente real ahora es detalle_tareas:
            $t['cliente_ids'] = json_decode((string)($t['clientes_asignados'] ?? '[]'), true) ?: [];
            $t['total_clientes'] = (int)($t['total_clientes'] ?? 0);
            $t['clientes_gestionados'] = (int)($t['clientes_gestionados'] ?? 0);
        }
        unset($t);
        return $tareas;
    }

    public function getEstadisticasTareas($coordinadorCedula) {
        $sql = "
            SELECT
                COUNT(*) as total_tareas,
                SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) as pendientes,
                SUM(CASE WHEN estado = 'en progreso' THEN 1 ELSE 0 END) as en_proceso,
                SUM(CASE WHEN estado = 'completa' THEN 1 ELSE 0 END) as completadas,
                SUM(CASE WHEN estado = 'cancelada' THEN 1 ELSE 0 END) as canceladas
            FROM tareas
            WHERE coordinador_cedula = ?
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([(string)$coordinadorCedula]);
        $r = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        return [
            'total_tareas' => (int)($r['total_tareas'] ?? 0),
            'pendientes' => (int)($r['pendientes'] ?? 0),
            'en_proceso' => (int)($r['en_proceso'] ?? 0),
            'completadas' => (int)($r['completadas'] ?? 0),
            'canceladas' => (int)($r['canceladas'] ?? 0),
        ];
    }

    /**
     * Marca como gestionado='si' cualquier cliente de detalle_tareas cuando ya exista al menos una gestión.
     * Se usa después de insertar en historial_gestiones.
     */
    public function marcarClienteGestionadoEnTareas($asesorCedula, $clienteId): void {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE detalle_tareas dt
                JOIN tareas t ON t.id_tarea = dt.tarea_id
                SET dt.gestionado = 'si'
                WHERE t.asesor_cedula = ?
                  AND t.estado IN ('pendiente','en progreso')
                  AND dt.cliente_id = ?
            ");
            $stmt->execute([(string)$asesorCedula, (int)$clienteId]);
        } catch (Throwable $e) {
            // Silencioso: no debe romper el flujo de guardado de gestión.
        }
    }

    public function getBasesAsignadasByAsesor($asesorId) {
        // En el dump, las asignaciones de base están en `asignacion_base_asesores`.
        $stmt = $this->pdo->prepare("
            SELECT
                aba.*,
                b.nombre as nombre_cargue,
                b.fecha_actualizacion as fecha_cargue,
                b.estado as estado_base,
                u.nombre as coordinador_nombre
            FROM asignacion_base_asesores aba
            JOIN base_clientes b ON aba.base_id = b.id_base
            LEFT JOIN usuarios u ON b.creado_por = u.cedula
            WHERE aba.asesor_cedula = ?
              AND aba.estado = 'activa'
              AND b.estado = 'activo'
            ORDER BY aba.fecha_asignacion DESC
        ");
        $stmt->execute([(string)$asesorId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$r) {
            $r['carga_id'] = $r['base_id'];
        }
        return $rows;
    }

    /**
     * Buscar un cliente por cédula dentro de las bases asignadas al asesor.
     * Devuelve filas compatibles con el frontend (id_cliente como id).
     */
    public function buscarClienteEnBasesAsignadas($asesorCedula, $cedula) {
        $bases = $this->getBasesAsignadasByAsesor($asesorCedula);
        $baseIds = array_values(array_filter(array_map(function ($b) {
            return (int)($b['base_id'] ?? $b['carga_id'] ?? 0);
        }, $bases)));

        if (empty($baseIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($baseIds), '?'));
        $sql = "
            SELECT
                c.id_cliente as id,
                c.id_cliente,
                c.base_id as carga_excel_id,
                c.base_id,
                c.cedula,
                c.nombre,
                c.email,
                c.ciudad,
                c.tel1 as telefono,
                c.tel2 as celular2,
                c.tel3 as cel3,
                c.tel4 as cel4,
                c.tel5 as cel5,
                c.tel6 as cel6,
                c.tel7 as cel7,
                c.tel8 as cel8,
                c.tel9 as cel9,
                c.tel10 as cel10,
                c.estado as estado_cliente,
                b.nombre as nombre_cargue
            FROM clientes c
            JOIN base_clientes b ON c.base_id = b.id_base
            WHERE c.cedula = ?
              AND c.base_id IN ($placeholders)
            ORDER BY c.nombre ASC
            LIMIT 50
        ";

        $params = array_merge([(string)$cedula], $baseIds);
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Buscar clientes por término (nombre/cedula/teléfono) dentro de las bases asignadas al asesor.
     */
    public function buscarClientesPorTermino($asesorCedula, $termino, $limit = 20) {
        $bases = $this->getBasesAsignadasByAsesor($asesorCedula);
        $baseIds = array_values(array_filter(array_map(function ($b) {
            return (int)($b['base_id'] ?? $b['carga_id'] ?? 0);
        }, $bases)));

        if (empty($baseIds)) {
            return [];
        }

        $limit = max(1, min(200, (int)$limit));
        $termino = trim((string)$termino);
        if ($termino === '') return [];

        $placeholders = implode(',', array_fill(0, count($baseIds), '?'));

        // Buscar por cedula exacta si es numérico
        if (ctype_digit($termino)) {
            $sql = "
                SELECT
                    c.id_cliente as id,
                    c.id_cliente,
                    c.base_id as carga_excel_id,
                    c.base_id,
                    c.cedula,
                    c.nombre,
                    c.email,
                    c.ciudad,
                    c.tel1 as telefono,
                    c.tel2 as celular2,
                    c.estado as estado_cliente,
                    b.nombre as nombre_cargue
                FROM clientes c
                JOIN base_clientes b ON c.base_id = b.id_base
                WHERE c.base_id IN ($placeholders)
                  AND (c.cedula = ? OR c.tel1 = ? OR c.tel2 = ? OR c.tel3 = ? OR c.tel4 = ?)
                ORDER BY c.nombre ASC
                LIMIT $limit
            ";
            $params = array_merge($baseIds, [$termino, $termino, $termino, $termino, $termino]);
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($rows)) return $rows;
        }

        $like = '%' . $termino . '%';
        $sql = "
            SELECT
                c.id_cliente as id,
                c.id_cliente,
                c.base_id as carga_excel_id,
                c.base_id,
                c.cedula,
                c.nombre,
                c.email,
                c.ciudad,
                c.tel1 as telefono,
                c.tel2 as celular2,
                c.estado as estado_cliente,
                b.nombre as nombre_cargue
            FROM clientes c
            JOIN base_clientes b ON c.base_id = b.id_base
            WHERE c.base_id IN ($placeholders)
              AND (
                c.nombre LIKE ?
                OR c.cedula LIKE ?
                OR c.tel1 LIKE ?
                OR c.tel2 LIKE ?
                OR c.email LIKE ?
              )
            ORDER BY c.nombre ASC
            LIMIT $limit
        ";
        $params = array_merge($baseIds, [$like, $like, $like, $like, $like]);
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAsesoresByBase($baseId) {
        $sql = "
            SELECT
                u.cedula as id,
                u.cedula,
                u.nombre as nombre_completo,
                u.nombre,
                aba.estado,
                aba.fecha_asignacion
            FROM asignacion_base_asesores aba
            JOIN usuarios u ON aba.asesor_cedula = u.cedula
            WHERE aba.base_id = ?
              AND aba.estado = 'activa'
              AND u.rol = 'asesor'
            ORDER BY u.nombre ASC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([(int)$baseId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getClientesNoGestionadosBase($baseId, $limit = 100) {
        $limit = max(1, min(500, (int)$limit));
        $sql = "
            SELECT
                c.id_cliente as id,
                c.id_cliente,
                c.base_id,
                c.cedula,
                c.nombre,
                c.email,
                c.ciudad,
                c.tel1 as telefono,
                c.tel2 as celular2,
                c.estado as estado_cliente
            FROM clientes c
            LEFT JOIN historial_gestiones hg ON hg.cliente_id = c.id_cliente
            WHERE c.base_id = ?
              AND hg.id_gestion IS NULL
            ORDER BY c.id_cliente ASC
            LIMIT $limit
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([(int)$baseId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getEstadisticasClientesBase($baseId) {
        $sql = "
            SELECT
                COUNT(*) as total,
                SUM(CASE WHEN hg.id_gestion IS NULL THEN 1 ELSE 0 END) as sin_gestion,
                SUM(CASE WHEN hg.id_gestion IS NOT NULL THEN 1 ELSE 0 END) as con_gestion
            FROM clientes c
            LEFT JOIN historial_gestiones hg ON hg.cliente_id = c.id_cliente
            WHERE c.base_id = ?
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([(int)$baseId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        return [
            'total' => (int)($row['total'] ?? 0),
            'con_gestion' => (int)($row['con_gestion'] ?? 0),
            'sin_gestion' => (int)($row['sin_gestion'] ?? 0),
        ];
    }

    public function asignarBaseCompleta($cargaId, $asesorId, $coordinadorId) {
        // Delegar en CargaExcelModel en el flujo actual; aquí sólo devolvemos true si ya existe o se insertó.
        $baseId = (int)$cargaId;
        $asesorCedula = (string)$asesorId;

        $stmt = $this->pdo->prepare("SELECT id_base_asesor FROM asignacion_base_asesores WHERE base_id = ? AND asesor_cedula = ? LIMIT 1");
        $stmt->execute([$baseId, $asesorCedula]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            $stmt = $this->pdo->prepare("UPDATE asignacion_base_asesores SET estado = 'activa', fecha_actualizacion = CURRENT_TIMESTAMP WHERE id_base_asesor = ?");
            return $stmt->execute([(int)$existing['id_base_asesor']]);
        }

        $stmt = $this->pdo->prepare("INSERT INTO asignacion_base_asesores (base_id, asesor_cedula, estado, fecha_asignacion) VALUES (?, ?, 'activa', CURRENT_TIMESTAMP)");
        return $stmt->execute([$baseId, $asesorCedula]);
    }

    public function liberarBase($cargaId, $asesorId) {
        $stmt = $this->pdo->prepare("UPDATE asignacion_base_asesores SET estado = 'inactiva', fecha_actualizacion = CURRENT_TIMESTAMP WHERE base_id = ? AND asesor_cedula = ?");
        return $stmt->execute([(int)$cargaId, (string)$asesorId]);
    }
}

