<?php
/**
 * Modelo CallLog
 * Registra eventos de llamada (inicio/fin) y los enlaza 1:1 con historial_gestiones.
 */
class CallLogModel {
    /** @var PDO */
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Intenta inferir coordinador del asesor desde asignaciones_cordinador (si existe registro activo).
     */
    public function inferirCoordinadorCedulaPorAsesor(string $asesorCedula): ?string {
        try {
            $stmt = $this->pdo->prepare("
                SELECT ac.cordinador_cedula
                FROM asignaciones_cordinador ac
                WHERE ac.asesor_cedula = ?
                  AND ac.estado = 'activo'
                ORDER BY ac.id_asignacion DESC
                LIMIT 1
            ");
            $stmt->execute([$asesorCedula]);
            $c = $stmt->fetchColumn();
            $c = is_string($c) ? trim($c) : '';
            return $c !== '' ? $c : null;
        } catch (Throwable $e) {
            return null;
        }
    }

    /**
     * Registra el inicio (idempotente por call_id).
     * Si call_id ya existe, actualiza campos básicos (cliente/telefono/inicio) sin tocar fin.
     */
    public function registrarInicio(array $data): void {
        $callId = trim((string)($data['call_id'] ?? ''));
        $clienteId = (int)($data['cliente_id'] ?? 0);
        $asesorCedula = trim((string)($data['asesor_cedula'] ?? ''));
        $telefono = preg_replace('/\D+/', '', (string)($data['telefono_contacto'] ?? ''));
        $telefono = $telefono !== null ? (string)$telefono : '';
        if (strlen($telefono) > 10) $telefono = substr($telefono, -10);
        if ($telefono === '') $telefono = '0000000000';

        $coordinadorCedula = trim((string)($data['coordinador_cedula'] ?? ''));
        if ($coordinadorCedula === '') {
            $coordinadorCedula = (string)($this->inferirCoordinadorCedulaPorAsesor($asesorCedula) ?? '');
        }
        if ($coordinadorCedula === '') $coordinadorCedula = null;

        if ($callId === '' || $clienteId <= 0 || $asesorCedula === '') {
            throw new Exception('Datos insuficientes para registrar inicio de llamada.');
        }

        $inicio = (string)($data['inicio'] ?? '');
        if ($inicio === '') {
            $inicio = date('Y-m-d H:i:s');
        }

        $stmt = $this->pdo->prepare("
            INSERT INTO call_log (
                call_id, gestion_id, cliente_id, asesor_cedula, coordinador_cedula,
                telefono_contacto, hangup_by, inicio, fin, duracion_segundos
            ) VALUES (
                ?, NULL, ?, ?, ?, ?, 'sistema', ?, NULL, 0
            )
            ON DUPLICATE KEY UPDATE
                cliente_id = VALUES(cliente_id),
                asesor_cedula = VALUES(asesor_cedula),
                coordinador_cedula = VALUES(coordinador_cedula),
                telefono_contacto = VALUES(telefono_contacto),
                inicio = VALUES(inicio)
        ");
        $stmt->execute([
            $callId,
            $clienteId,
            $asesorCedula,
            $coordinadorCedula,
            $telefono,
            $inicio,
        ]);
    }

    public function registrarFin(array $data): void {
        $callId = trim((string)($data['call_id'] ?? ''));
        if ($callId === '') {
            throw new Exception('call_id requerido.');
        }

        $hangupBy = (string)($data['hangup_by'] ?? 'sistema');
        if (!in_array($hangupBy, ['asesor', 'cliente', 'sistema'], true)) $hangupBy = 'sistema';

        $fin = (string)($data['fin'] ?? '');
        if ($fin === '') $fin = date('Y-m-d H:i:s');

        $duracion = (int)($data['duracion_segundos'] ?? 0);
        if ($duracion < 0) $duracion = 0;

        if ($duracion <= 0) {
            $stmtDur = $this->pdo->prepare("
                SELECT GREATEST(0, TIMESTAMPDIFF(SECOND, inicio, ?))
                FROM call_log
                WHERE call_id = ?
                LIMIT 1
            ");
            $stmtDur->execute([$fin, $callId]);
            $duracion = (int)($stmtDur->fetchColumn() ?: 0);
        }

        $stmt = $this->pdo->prepare("
            UPDATE call_log
            SET fin = ?,
                duracion_segundos = ?,
                hangup_by = ?
            WHERE call_id = ?
            LIMIT 1
        ");
        $stmt->execute([$fin, $duracion, $hangupBy, $callId]);
        // #region agent log 058b8a registrarFin result
        try { @file_put_contents(__DIR__ . '/../debug-058b8a.log', json_encode([
            'sessionId'=>'058b8a',
            'runId'=>'post-fix',
            'hypothesisId'=>'FIX1',
            'location'=>'models/CallLogModel.php:registrarFin',
            'message'=>'updated',
            'data'=>[
                'callIdLen'=>strlen($callId),
                'duracionFinal'=>(int)$duracion,
                'affected'=>method_exists($stmt, 'rowCount') ? (int)$stmt->rowCount() : -1,
            ],
            'timestamp'=>(int) round(microtime(true)*1000),
        ], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n", FILE_APPEND); } catch (Throwable $e) {}
        // #endregion
    }

    public function enlazarGestion(string $callId, int $gestionId): void {
        $callId = trim($callId);
        if ($callId === '' || $gestionId <= 0) return;
        // #region agent log 058b8a enlazarGestion entry
        try { @file_put_contents(__DIR__ . '/../debug-058b8a.log', json_encode([
            'sessionId'=>'058b8a',
            'runId'=>'pre',
            'hypothesisId'=>'H1',
            'location'=>'models/CallLogModel.php:enlazarGestion',
            'message'=>'entry',
            'data'=>[
                'callIdLen'=>strlen($callId),
                'gestionId'=>(int)$gestionId,
            ],
            'timestamp'=>(int) round(microtime(true)*1000),
        ], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n", FILE_APPEND); } catch (Throwable $e) {}
        // #endregion
        $stmt = $this->pdo->prepare("UPDATE call_log SET gestion_id = ? WHERE call_id = ? LIMIT 1");
        $stmt->execute([(int)$gestionId, $callId]);
        $affected = method_exists($stmt, 'rowCount') ? (int)$stmt->rowCount() : -1;
        // #region agent log 058b8a enlazarGestion result
        try { @file_put_contents(__DIR__ . '/../debug-058b8a.log', json_encode([
            'sessionId'=>'058b8a',
            'runId'=>'pre',
            'hypothesisId'=>'H3',
            'location'=>'models/CallLogModel.php:enlazarGestion',
            'message'=>'updated',
            'data'=>[
                'affected'=>(int)$affected,
            ],
            'timestamp'=>(int) round(microtime(true)*1000),
        ], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n", FILE_APPEND); } catch (Throwable $e) {}
        // #endregion
    }

    /**
     * Listado paginado para coordinador.
     * Devuelve ['total'=>int,'rows'=>array]
     */
    public function listarPorCoordinador(array $filters): array {
        $coordinadorCedula = trim((string)($filters['coordinador_cedula'] ?? ''));
        if ($coordinadorCedula === '') {
            throw new Exception('coordinador_cedula requerido.');
        }

        $fechaInicio = (string)($filters['fecha_inicio'] ?? '');
        $fechaFin = (string)($filters['fecha_fin'] ?? '');
        $hangupBy = (string)($filters['hangup_by'] ?? '');
        $asesorCedula = (string)($filters['asesor_cedula'] ?? '');
        $telefono = preg_replace('/\D+/', '', (string)($filters['telefono'] ?? ''));
        $telefono = $telefono !== null ? (string)$telefono : '';
        if (strlen($telefono) > 10) $telefono = substr($telefono, -10);

        $page = (int)($filters['page'] ?? 1);
        if ($page < 1) $page = 1;
        $limit = 10;
        $offset = ($page - 1) * $limit;

        $where = ["cl.coordinador_cedula = ?"];
        $params = [$coordinadorCedula];

        // Solo llamadas efectivas (evitar buzón / intentos muy cortos)
        $where[] = "cl.duracion_segundos > 40";

        // #region agent log 058b8a listar where-base
        try { @file_put_contents(__DIR__ . '/../debug-058b8a.log', json_encode([
            'sessionId'=>'058b8a',
            'runId'=>'pre',
            'hypothesisId'=>'H1',
            'location'=>'models/CallLogModel.php:listarPorCoordinador',
            'message'=>'filters',
            'data'=>[
                'coordinadorCedulaLen'=>strlen($coordinadorCedula),
                'fechaInicio'=>(string)($fechaInicio ?? ''),
                'fechaFin'=>(string)($fechaFin ?? ''),
                'telefonoLen'=>strlen((string)($telefono ?? '')),
                'page'=>(int)$page,
            ],
            'timestamp'=>(int) round(microtime(true)*1000),
        ], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n", FILE_APPEND); } catch (Throwable $e) {}
        // #endregion

        if ($fechaInicio !== '') {
            $where[] = "cl.inicio >= ?";
            $params[] = $fechaInicio . " 00:00:00";
        }
        if ($fechaFin !== '') {
            $where[] = "cl.inicio <= ?";
            $params[] = $fechaFin . " 23:59:59";
        }
        if ($hangupBy !== '' && in_array($hangupBy, ['asesor', 'cliente', 'sistema'], true)) {
            $where[] = "cl.hangup_by = ?";
            $params[] = $hangupBy;
        }
        if ($asesorCedula !== '') {
            $where[] = "cl.asesor_cedula = ?";
            $params[] = $asesorCedula;
        }
        if ($telefono !== '') {
            $where[] = "cl.telefono_contacto LIKE ?";
            $params[] = "%" . $telefono . "%";
        }

        $whereSql = "WHERE " . implode(" AND ", $where);

        // #region agent log 898d3b callLog listar pre-count
        try { @file_put_contents(__DIR__ . '/../debug-898d3b.log', json_encode([
            'sessionId'=>'898d3b',
            'runId'=>'pre',
            'hypothesisId'=>'H1',
            'location'=>'models/CallLogModel.php:listarPorCoordinador:preCount',
            'message'=>'count',
            'data'=>[
                'wherePartsCount'=>count($where),
                'paramsCount'=>count($params),
                'page'=>(int)$page,
                'telefonoLen'=>strlen((string)$telefono),
            ],
            'timestamp'=>(int) round(microtime(true)*1000),
        ], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n", FILE_APPEND); } catch (Throwable $e) {}
        // #endregion

        try {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM call_log cl $whereSql");
            $stmt->execute($params);
            $total = (int)$stmt->fetchColumn();
        } catch (Throwable $e) {
            // #region agent log 898d3b callLog listar count exception
            try { @file_put_contents(__DIR__ . '/../debug-898d3b.log', json_encode([
                'sessionId'=>'898d3b',
                'runId'=>'pre',
                'hypothesisId'=>'H1',
                'location'=>'models/CallLogModel.php:listarPorCoordinador:countException',
                'message'=>'exception',
                'data'=>[
                    'type'=>get_class($e),
                    'code'=>(int)$e->getCode(),
                    'msg'=>substr((string)$e->getMessage(),0,300),
                ],
                'timestamp'=>(int) round(microtime(true)*1000),
            ], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n", FILE_APPEND); } catch (Throwable $ex) {}
            // #endregion
            throw $e;
        }

        $sql = "
            SELECT
                cl.*,
                c.cedula AS cliente_cedula,
                c.nombre AS cliente_nombre,
                ua.nombre AS asesor_nombre
            FROM call_log cl
            JOIN clientes c ON c.id_cliente = cl.cliente_id
            LEFT JOIN usuarios ua ON ua.cedula = cl.asesor_cedula
            $whereSql
            ORDER BY cl.inicio DESC, cl.id_call DESC
            LIMIT $limit OFFSET $offset
        ";
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            // #region agent log 898d3b callLog listar select exception
            try { @file_put_contents(__DIR__ . '/../debug-898d3b.log', json_encode([
                'sessionId'=>'898d3b',
                'runId'=>'pre',
                'hypothesisId'=>'H2',
                'location'=>'models/CallLogModel.php:listarPorCoordinador:selectException',
                'message'=>'exception',
                'data'=>[
                    'type'=>get_class($e),
                    'code'=>(int)$e->getCode(),
                    'msg'=>substr((string)$e->getMessage(),0,300),
                ],
                'timestamp'=>(int) round(microtime(true)*1000),
            ], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n", FILE_APPEND); } catch (Throwable $ex) {}
            // #endregion
            throw $e;
        }

        return ['total' => $total, 'rows' => $rows, 'page' => $page, 'limit' => $limit];
    }
}

