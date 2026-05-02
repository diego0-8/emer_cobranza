<?php
// Archivo: AsesorController.php
// Lógica para el asesor

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/ProductoClienteModel.php';

class AsesorController extends BaseController {
    public function __construct($pdo) {
        parent::__construct($pdo);
    }
    
    public function dashboard() {
        // #region agent log d54ef5 dashboard
        $logPathD54 = __DIR__ . '/../debug-d54ef5.log';
        $dbgD54 = function($location, $message, $data = [], $hypothesisId = 'H0', $runId = 'pre') use ($logPathD54) {
            try {
                @file_put_contents($logPathD54, json_encode([
                    'sessionId' => 'd54ef5',
                    'runId' => $runId,
                    'hypothesisId' => $hypothesisId,
                    'location' => $location,
                    'message' => $message,
                    'data' => $data,
                    'timestamp' => (int) round(microtime(true) * 1000),
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n", FILE_APPEND);
            } catch (Throwable $e) {}
        };
        $dbgD54('controllers/AsesorController.php:dashboard:entry', 'enter', [
            'method' => (string)($_SERVER['REQUEST_METHOD'] ?? ''),
            'action' => (string)($_GET['action'] ?? ''),
            'periodo' => (string)($_GET['periodo'] ?? 'dia'),
            'hasSessionUserId' => isset($_SESSION['user_id']) ? 1 : 0,
            'userRole' => (string)($_SESSION['user_role'] ?? ''),
            'userIdLen' => strlen((string)($_SESSION['user_id'] ?? '')),
        ], 'H3', 'pre');
        // #endregion

        // #region debug d200d9 asesor dashboard
        $logPath = __DIR__ . '/../debug-d200d9.log';
        $dbg = function($location, $message, $data = [], $hypothesisId = 'A0', $runId = 'pre-fix') use ($logPath) {
            try {
                @file_put_contents($logPath, json_encode([
                    'sessionId' => 'd200d9',
                    'runId' => $runId,
                    'hypothesisId' => $hypothesisId,
                    'location' => $location,
                    'message' => $message,
                    'data' => $data,
                    'timestamp' => (int) round(microtime(true) * 1000),
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n", FILE_APPEND);
            } catch (Throwable $e) {}
        };
        $dbg('controllers/AsesorController.php:dashboard:entry', 'enter', [
            'hasSession' => isset($_SESSION) ? 1 : 0,
            'userRole' => (string)($_SESSION['user_role'] ?? ''),
            'userIdLen' => strlen((string)($_SESSION['user_id'] ?? '')),
            'periodo' => (string)($_GET['periodo'] ?? 'dia'),
        ], 'A1');

        // Capturar fatales que no pasen por try/catch (shutdown)
        register_shutdown_function(function() use ($dbg) {
            $err = error_get_last();
            if (!$err) return;
            $dbg('controllers/AsesorController.php:dashboard:shutdown', 'last_error', [
                'type' => (int)($err['type'] ?? 0),
                'file' => (string)($err['file'] ?? ''),
                'line' => (int)($err['line'] ?? 0),
                'message' => substr((string)($err['message'] ?? ''), 0, 400),
            ], 'A5');
        });
        // #endregion

        $page_title = "Dashboard Profesional del Asesor";
        $asesor_id = $_SESSION['user_id'];
        
        // Obtener período seleccionado (día, semana, mes)
        $periodo = $_GET['periodo'] ?? 'dia';
        
        try {
            // Obtener métricas del dashboard para el período seleccionado
            $metricas = $this->gestionModel->getMetricasDashboard($asesor_id, $periodo);
            $tipificaciones = $this->gestionModel->getTipificacionesPorResultado($asesor_id, $periodo);
            $gestionesPorDia = $this->gestionModel->getGestionesUltimosDias($asesor_id, 7);
            // #region agent log d54ef5 dashboard data shapes
            $sumGestiones7d = 0;
            $maxGestionesDia = 0;
            if (is_array($gestionesPorDia)) {
                foreach ($gestionesPorDia as $row) {
                    $c = (int)($row['cantidad'] ?? 0);
                    $sumGestiones7d += $c;
                    if ($c > $maxGestionesDia) $maxGestionesDia = $c;
                }
            }
            $dbgD54('controllers/AsesorController.php:dashboard:data', 'shapes', [
                'metricasType' => is_array($metricas) ? 'array' : gettype($metricas),
                'metricasKeys' => is_array($metricas) ? array_slice(array_keys($metricas), 0, 30) : [],
                'metricasGestiones' => (int)($metricas['gestiones'] ?? -1),
                'metricasInicio' => (string)($metricas['inicio'] ?? ''),
                'metricasFin' => (string)($metricas['fin'] ?? ''),
                'tipificacionesCount' => is_array($tipificaciones) ? count($tipificaciones) : -1,
                'tipificacionesFirstKeys' => (is_array($tipificaciones) && isset($tipificaciones[0]) && is_array($tipificaciones[0])) ? array_keys($tipificaciones[0]) : [],
                'gestionesPorDiaCount' => is_array($gestionesPorDia) ? count($gestionesPorDia) : -1,
                'gestionesPorDiaFirstKeys' => (is_array($gestionesPorDia) && isset($gestionesPorDia[0]) && is_array($gestionesPorDia[0])) ? array_keys($gestionesPorDia[0]) : [],
                'gestiones7dSum' => $sumGestiones7d,
                'gestiones7dMaxDia' => $maxGestionesDia,
            ], 'H1', 'pre');
            // #endregion
        } catch (Throwable $e) {
            // #region agent log d54ef5 dashboard exception
            $dbgD54('controllers/AsesorController.php:dashboard:metrics', 'exception', [
                'type' => get_class($e),
                'code' => (int)$e->getCode(),
                'message' => substr((string)$e->getMessage(), 0, 350),
            ], 'H4', 'pre');
            // #endregion
            $dbg('controllers/AsesorController.php:dashboard:metrics', 'exception', [
                'type' => get_class($e),
                'message' => substr((string)$e->getMessage(), 0, 300),
            ], 'A2');
            throw $e;
        }
        
        // Obtener estadísticas de tareas pendientes
        $tareasPendientes = $this->tareaModel->getTareasPendientesByAsesor($asesor_id);
        $totalTareasPendientes = count($tareasPendientes);
        
        // Calcular clientes pendientes de tareas
        $clientesPendientesTareas = 0;
        foreach ($tareasPendientes as $tarea) {
            $clientesPendientesTareas += count($tarea['cliente_ids']);
        }
        
        // Obtener datos de seguimiento y últimas gestiones (sin filtro de fecha)
        $clientesSeguimiento = $this->gestionModel->getClientesConSeguimiento($asesor_id);
        $ultimasGestiones = $this->gestionModel->getUltimasGestiones($asesor_id, 5);
        
        // Obtener clientes con tipificación "volver a llamar" para el día actual
        $llamadasPendientes = $this->gestionModel->getLlamadasPendientesHoy($asesor_id);
        $totalLlamadasPendientesHoy = $this->gestionModel->getTotalLlamadasPendientesHoy($asesor_id);
        
        // Obtener clientes asignados para las tarjetas de resumen
        $clientes = $this->clienteModel->getAssignedClientsForAsesor($asesor_id);
        $total_clientes = count($clientes);
        
        // Obtener métricas REALES del asesor (no solo asignaciones)
        $clientes_gestionados = $this->gestionModel->getClientesGestionados($asesor_id);
        $total_recaudado = $this->gestionModel->getTotalRecaudado($asesor_id);
        
        // Calcular métricas específicas para el dashboard
        $gestiones_hoy = $this->gestionModel->getGestionesHoy($asesor_id);
        $gestiones_mes_actual = $this->gestionModel->getGestionesMesActual($asesor_id);
        $contactos_efectivos_hoy = $this->gestionModel->getContactosEfectivosHoy($asesor_id);
        $acuerdos_hoy = $this->gestionModel->getAcuerdosHoy($asesor_id);
        $recaudo_acuerdos_dia = $this->gestionModel->getSumaValorAcuerdosAsesorDia($asesor_id);
        $recaudo_acuerdos_mes = $this->gestionModel->getSumaValorAcuerdosAsesorMes($asesor_id);
        // #region agent log d54ef5 dashboard recaudo hoy
        try {
            @file_put_contents(__DIR__ . '/../debug-d54ef5.log', json_encode([
                'sessionId' => 'd54ef5',
                'runId' => 'pre',
                'hypothesisId' => 'H1',
                'location' => 'controllers/AsesorController.php:dashboard:recaudo',
                'message' => 'recaudo_values',
                'data' => [
                    'recaudoDia' => (float)$recaudo_acuerdos_dia,
                    'recaudoMes' => (float)$recaudo_acuerdos_mes,
                ],
                'timestamp' => (int) round(microtime(true) * 1000),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n", FILE_APPEND);
        } catch (Throwable $e) {}
        // #endregion
        
        // Obtener métricas adicionales de la semana y mes
        $metricas_semana = $this->gestionModel->getMetricasSemana($asesor_id);
        $metricas_mes = $this->gestionModel->getMetricasMes($asesor_id);
        
        // Debug: Log de datos para verificar
        error_log("=== DEBUG DASHBOARD ASESOR ===");
        error_log("Asesor ID: " . $asesor_id);
        error_log("Gestiones Hoy: " . $gestiones_hoy);
        error_log("Contactos Efectivos Hoy: " . $contactos_efectivos_hoy);
        error_log("Acuerdos Hoy: " . $acuerdos_hoy);
        error_log("Gestiones por Día: " . json_encode($gestionesPorDia));
        error_log("Tipificaciones: " . json_encode($tipificaciones));
        
        // Datos del dashboard con métricas reales
        $datos_dashboard = [
            'total_clientes' => $total_clientes,
            'gestiones_mes_actual' => $gestiones_mes_actual,
            'gestiones_hoy' => $gestiones_hoy,
            'contactos_efectivos_hoy' => $contactos_efectivos_hoy,
            'acuerdos_hoy' => $acuerdos_hoy,
            'recaudo_acuerdos_dia' => $recaudo_acuerdos_dia,
            'recaudo_acuerdos_mes' => $recaudo_acuerdos_mes,
            'gestiones_semana' => $metricas_semana['gestiones_semana'] ?? 0,
            'contactos_efectivos_semana' => $metricas_semana['contactos_efectivos_semana'] ?? 0,
            'acuerdos_semana' => $metricas_semana['acuerdos_semana'] ?? 0,
            'gestiones_mes' => $gestiones_mes_actual,
            'contactos_efectivos_mes' => $metricas_mes['contactos_efectivos_mes'] ?? 0,
            'acuerdos_mes' => $metricas_mes['acuerdos_mes'] ?? 0,
            'clientes_gestionados' => $clientes_gestionados,
            'total_recaudado' => $total_recaudado,
            'periodo' => $periodo,
            'llamadas_pendientes' => $llamadasPendientes,
            'total_llamadas_pendientes_hoy' => $totalLlamadasPendientesHoy,
            'total_tareas_pendientes' => $totalTareasPendientes,
            'clientes_pendientes_tareas' => $clientesPendientesTareas
        ];

        try {
            // #region agent log d54ef5 dashboard view vars
            $dbgD54('controllers/AsesorController.php:dashboard:view', 'vars_ready', [
                'hasDatosDashboard' => isset($datos_dashboard) ? 1 : 0,
                'datosDashboardKeys' => isset($datos_dashboard) && is_array($datos_dashboard) ? array_slice(array_keys($datos_dashboard), 0, 50) : [],
                'tipificacionesCount' => isset($tipificaciones) && is_array($tipificaciones) ? count($tipificaciones) : -1,
                'gestionesPorDiaCount' => isset($gestionesPorDia) && is_array($gestionesPorDia) ? count($gestionesPorDia) : -1,
                'view' => 'views/asesor_dashboard.php',
            ], 'H3', 'pre');
            // #endregion
            $dbg('controllers/AsesorController.php:dashboard:view', 'requiring_view', [
                'viewPath' => __DIR__ . '/../views/asesor_dashboard.php',
            ], 'A3');
            require __DIR__ . '/../views/asesor_dashboard.php';
        } catch (Throwable $e) {
            // #region agent log d54ef5 dashboard view exception
            $dbgD54('controllers/AsesorController.php:dashboard:view', 'exception', [
                'type' => get_class($e),
                'code' => (int)$e->getCode(),
                'message' => substr((string)$e->getMessage(), 0, 350),
            ], 'H3', 'pre');
            // #endregion
            $dbg('controllers/AsesorController.php:dashboard:view', 'exception', [
                'type' => get_class($e),
                'message' => substr((string)$e->getMessage(), 0, 300),
            ], 'A4');
            throw $e;
        }
    }

    public function clientDebugLog() {
        if (ob_get_level()) ob_clean();
        if (!headers_sent()) header('Content-Type: application/json');

        try {
            if (($_SESSION['user_role'] ?? '') !== 'asesor') {
                echo json_encode(['success' => false, 'error' => 'forbidden']);
                exit;
            }

            $raw = file_get_contents('php://input');
            $data = null;
            try { $data = json_decode($raw, true); } catch (Throwable $e) { $data = null; }

            $payload = [
                'sessionId' => 'd200d9',
                'runId' => (string)($data['runId'] ?? 'pre-fix'),
                'hypothesisId' => (string)($data['hypothesisId'] ?? 'TIP-SRV'),
                'location' => (string)($data['location'] ?? 'client'),
                'message' => (string)($data['message'] ?? 'log'),
                'data' => (array)($data['data'] ?? []),
                'timestamp' => (int)($data['timestamp'] ?? round(microtime(true) * 1000)),
            ];

            @file_put_contents(__DIR__ . '/../debug-d200d9.log', json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n", FILE_APPEND);
            echo json_encode(['success' => true]);
            exit;
        } catch (Throwable $e) {
            echo json_encode(['success' => false, 'error' => 'server_error']);
            exit;
        }
    }

    public function clientDebugLogD54ef5() {
        if (ob_get_level()) ob_clean();
        if (!headers_sent()) header('Content-Type: application/json');

        try {
            if (($_SESSION['user_role'] ?? '') !== 'asesor') {
                echo json_encode(['success' => false, 'error' => 'forbidden']);
                exit;
            }

            $raw = file_get_contents('php://input');
            $data = null;
            try { $data = json_decode($raw, true); } catch (Throwable $e) { $data = null; }

            // Sanitizar para evitar PII / payloads gigantes
            $location = (string)($data['location'] ?? 'client');
            $message = (string)($data['message'] ?? 'log');
            $hypothesisId = (string)($data['hypothesisId'] ?? 'H2');
            $runId = (string)($data['runId'] ?? 'pre');
            $payloadData = (array)($data['data'] ?? []);

            // Eliminar posibles campos sensibles si llegan por accidente
            foreach (['cedula','telefono','email','nombre','password','token'] as $k) {
                if (array_key_exists($k, $payloadData)) unset($payloadData[$k]);
            }

            $payload = [
                'sessionId' => 'd54ef5',
                'runId' => $runId,
                'hypothesisId' => $hypothesisId,
                'location' => $location,
                'message' => $message,
                'data' => $payloadData,
                'timestamp' => (int)($data['timestamp'] ?? round(microtime(true) * 1000)),
            ];

            @file_put_contents(__DIR__ . '/../debug-d54ef5.log', json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n", FILE_APPEND);
            echo json_encode(['success' => true]);
            exit;
        } catch (Throwable $e) {
            echo json_encode(['success' => false, 'error' => 'server_error']);
            exit;
        }
    }

    /**
     * API: registrar inicio de llamada (call_log).
     * Espera JSON: { call_id, cliente_id, telefono_contacto, inicio? }
     */
    public function registrarInicioLlamada() {
        if (ob_get_level()) ob_clean();
        if (!headers_sent()) header('Content-Type: application/json; charset=utf-8');

        try {
            if (($_SESSION['user_role'] ?? '') !== 'asesor') {
                echo json_encode(['success' => false, 'message' => 'forbidden']);
                exit;
            }

            $raw = file_get_contents('php://input');
            $data = json_decode($raw ?: '[]', true);
            if (!is_array($data)) $data = [];

            $asesorCedula = (string)($_SESSION['user_id'] ?? '');
            // #region agent log 058b8a call_log inicio
            try { @file_put_contents(__DIR__ . '/../debug-058b8a.log', json_encode([
                'sessionId'=>'058b8a',
                'runId'=>'pre',
                'hypothesisId'=>'H4',
                'location'=>'controllers/AsesorController.php:registrarInicioLlamada',
                'message'=>'payload',
                'data'=>[
                    'asesorCedulaLen'=>strlen($asesorCedula),
                    'callIdLen'=>strlen((string)($data['call_id'] ?? '')),
                    'clienteId'=>(int)($data['cliente_id'] ?? 0),
                    'telLen'=>strlen((string)($data['telefono_contacto'] ?? '')),
                ],
                'timestamp'=>(int) round(microtime(true)*1000),
            ], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n", FILE_APPEND); } catch (Throwable $e) {}
            // #endregion
            $this->callLogModel->registrarInicio([
                'call_id' => (string)($data['call_id'] ?? ''),
                'cliente_id' => (int)($data['cliente_id'] ?? 0),
                'asesor_cedula' => $asesorCedula,
                'telefono_contacto' => (string)($data['telefono_contacto'] ?? ''),
                'inicio' => (string)($data['inicio'] ?? ''),
            ]);

            echo json_encode(['success' => true]);
            exit;
        } catch (Throwable $e) {
            echo json_encode(['success' => false, 'message' => 'server_error']);
            exit;
        }
    }

    /**
     * API: registrar fin de llamada (call_log).
     * Espera JSON: { call_id, hangup_by, hangup_reason, fin?, duracion_segundos? }
     */
    public function registrarFinLlamada() {
        if (ob_get_level()) ob_clean();
        if (!headers_sent()) header('Content-Type: application/json; charset=utf-8');

        try {
            if (($_SESSION['user_role'] ?? '') !== 'asesor') {
                echo json_encode(['success' => false, 'message' => 'forbidden']);
                exit;
            }

            $raw = file_get_contents('php://input');
            $data = json_decode($raw ?: '[]', true);
            if (!is_array($data)) $data = [];

            // #region agent log 058b8a call_log fin
            try { @file_put_contents(__DIR__ . '/../debug-058b8a.log', json_encode([
                'sessionId'=>'058b8a',
                'runId'=>'pre',
                'hypothesisId'=>'H2',
                'location'=>'controllers/AsesorController.php:registrarFinLlamada',
                'message'=>'payload',
                'data'=>[
                    'callIdLen'=>strlen((string)($data['call_id'] ?? '')),
                    'hangup_by'=>(string)($data['hangup_by'] ?? ''),
                    'duracion_segundos'=>(int)($data['duracion_segundos'] ?? 0),
                ],
                'timestamp'=>(int) round(microtime(true)*1000),
            ], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n", FILE_APPEND); } catch (Throwable $e) {}
            // #endregion
            $this->callLogModel->registrarFin([
                'call_id' => (string)($data['call_id'] ?? ''),
                'hangup_by' => (string)($data['hangup_by'] ?? 'sistema'),
                'fin' => (string)($data['fin'] ?? ''),
                'duracion_segundos' => (int)($data['duracion_segundos'] ?? 0),
            ]);

            // #region agent log 058b8a call_log fin saved
            try { @file_put_contents(__DIR__ . '/../debug-058b8a.log', json_encode([
                'sessionId'=>'058b8a',
                'runId'=>'pre',
                'hypothesisId'=>'H1',
                'location'=>'controllers/AsesorController.php:registrarFinLlamada',
                'message'=>'saved',
                'data'=>['ok'=>1],
                'timestamp'=>(int) round(microtime(true)*1000),
            ], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n", FILE_APPEND); } catch (Throwable $e) {}
            // #endregion
            echo json_encode(['success' => true]);
            exit;
        } catch (Throwable $e) {
            echo json_encode(['success' => false, 'message' => 'server_error']);
            exit;
        }
    }

    public function misClientes() {
        $page_title = "Mis Clientes";
        $asesorId = $_SESSION['user_id'];

        // #region agent log b7eaa7 misClientes entry
        try { @file_put_contents(__DIR__ . '/../debug-b7eaa7.log', json_encode([
            'sessionId'=>'b7eaa7','runId'=>'pre','hypothesisId'=>'MC1',
            'location'=>'controllers/AsesorController.php:misClientes:entry',
            'message'=>'enter',
            'data'=>[
                'asesorIdLen'=>strlen((string)$asesorId),
                'filter'=>(string)($_GET['filter'] ?? 'todos'),
                'pagina'=>(int)($_GET['pagina'] ?? 1),
                'buscarLen'=>strlen((string)($_GET['buscar'] ?? '')),
            ],
            'timestamp'=>(int) round(microtime(true)*1000)
        ], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n", FILE_APPEND); } catch (Throwable $e) {}
        // #endregion
        
        // Verificar si el asesor tiene tareas pendientes
        $tieneTareasPendientes = $this->tareaModel->tieneTareasPendientes($asesorId);
        
        if ($tieneTareasPendientes) {
            // Si tiene tareas pendientes, mostrar SOLO los clientes de la tarea pendiente activa (primera por fecha_creacion DESC)
            $tareasPendientes = $this->tareaModel->getTareasPendientesByAsesor($asesorId);
            $clientesTareas = [];
            $tareaActiva = (is_array($tareasPendientes) && !empty($tareasPendientes)) ? $tareasPendientes[0] : null;

            // Preparar statements para evitar N+1 excesivo (seguimos siendo N+1, pero al menos reusamos prepare)
            $stmtUlt = $this->pdo->prepare("
                SELECT hg.resultado_contacto, hg.fecha_creacion
                FROM historial_gestiones hg
                WHERE hg.asesor_cedula = ? AND hg.cliente_id = ?
                ORDER BY hg.fecha_creacion DESC, hg.id_gestion DESC
                LIMIT 1
            ");
            $stmtAcuerdo = $this->pdo->prepare("
                SELECT 1
                FROM historial_gestiones hg
                WHERE hg.asesor_cedula = ?
                  AND hg.cliente_id = ?
                  AND LOWER(REPLACE(TRIM(hg.resultado_contacto), '_', ' ')) IN ('acuerdo de pago','acuerdo pago')
                LIMIT 1
            ");
            
            if ($tareaActiva) {
                $clientesTarea = $this->tareaModel->getClientesByTarea($tareaActiva['id']);
                foreach ($clientesTarea as $cliente) {
                    // Normalizar campos esperados por la vista
                    $cliente['id'] = $cliente['id'] ?? ($cliente['id_cliente'] ?? null);
                    $cliente['telefono'] = $cliente['telefono'] ?? ($cliente['tel1'] ?? '');
                    $cliente['celular2'] = $cliente['celular2'] ?? ($cliente['tel2'] ?? '');

                    // Fuente de verdad: gestionado por detalle_tareas
                    $cliente['gestionado_detalle'] = (string)($cliente['gestionado'] ?? 'no');

                    // Siempre asociar a la tarea activa
                    $cliente['tarea_id'] = $tareaActiva['id'];
                    $cliente['tarea_descripcion'] = $tareaActiva['descripcion'];
                    $cliente['tarea_prioridad'] = $tareaActiva['prioridad'];

                    // Regla: si aún NO está gestionado en detalle_tareas, TODO debe verse como "pendiente"
                    if (($cliente['gestionado_detalle'] ?? 'no') !== 'si') {
                        $cliente['total_gestiones'] = 0;
                        $cliente['ultimo_resultado'] = '';
                        $cliente['ultima_gestion'] = null;
                        $cliente['tiene_acuerdo'] = 0;
                    } else {
                        // Ya gestionado: ahora sí mostrar qué gestión tuvo (último resultado del asesor) y conteos
                        $totalGestiones = $this->gestionModel->getTotalGestionesByAsesorAndCliente($asesorId, $cliente['id']);
                        $cliente['total_gestiones'] = $totalGestiones;

                        $ultimo_resultado = '';
                        $ultima_gestion = null;
                        try {
                            $stmtUlt->execute([(string)$asesorId, (int)($cliente['id'] ?? 0)]);
                            $u = $stmtUlt->fetch(PDO::FETCH_ASSOC) ?: [];
                            $ultimo_resultado = (string)($u['resultado_contacto'] ?? '');
                            $ultima_gestion = $u['fecha_creacion'] ?? null;
                        } catch (Throwable $e) {}

                        $tiene_acuerdo = false;
                        try {
                            $stmtAcuerdo->execute([(string)$asesorId, (int)($cliente['id'] ?? 0)]);
                            $tiene_acuerdo = (bool)$stmtAcuerdo->fetchColumn();
                        } catch (Throwable $e) {}

                        $cliente['ultimo_resultado'] = $ultimo_resultado;
                        $cliente['ultima_gestion'] = $ultima_gestion;
                        $cliente['tiene_acuerdo'] = $tiene_acuerdo ? 1 : 0;
                    }

                    $clientesTareas[] = $cliente;
                }
            }
            
            $todosClientes = $clientesTareas;
        } else {
            // Si no tiene tareas pendientes, mostrar mensaje de "No tienes tareas pendientes"
            $todosClientes = [];
        }

        // Total real de clientes de tareas (para mostrar contadores en la vista sin depender del filtro actual)
        $total_todos_clientes = is_array($todosClientes) ? count($todosClientes) : 0;

        // #region agent log b7eaa7 misClientes after build todosClientes
        try {
            $sample = [];
            if (is_array($todosClientes)) {
                foreach (array_slice($todosClientes, 0, 3) as $c) {
                    $sample[] = [
                        'id'=>(int)($c['id'] ?? $c['id_cliente'] ?? 0),
                        'gestionado_detalle'=>(string)($c['gestionado_detalle'] ?? $c['gestionado'] ?? ''),
                        'total_gestiones'=>(int)($c['total_gestiones'] ?? -1),
                        'ultimo_resultado'=>(string)($c['ultimo_resultado'] ?? ''),
                        'tiene_acuerdo'=>(int)($c['tiene_acuerdo'] ?? 0),
                    ];
                }
            }
            @file_put_contents(__DIR__ . '/../debug-b7eaa7.log', json_encode([
                'sessionId'=>'b7eaa7','runId'=>'pre','hypothesisId'=>'MC2',
                'location'=>'controllers/AsesorController.php:misClientes:lists',
                'message'=>'todosClientes_built',
                'data'=>[
                    'count'=>is_array($todosClientes)?count($todosClientes):-1,
                    'sample'=>$sample,
                ],
                'timestamp'=>(int) round(microtime(true)*1000)
            ], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n", FILE_APPEND);
        } catch (Throwable $e) {}
        // #endregion
        
        // Parámetros de paginación
        $por_pagina = 10; // 10 clientes por página
        $pagina_actual = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
        $offset = ($pagina_actual - 1) * $por_pagina;
        
        // Filtrar por cédula si se proporciona un término de búsqueda
        $terminoBusqueda = $_GET['buscar'] ?? '';
        if (!empty($terminoBusqueda)) {
            $todosClientes = array_filter($todosClientes, function($cliente) use ($terminoBusqueda) {
                return stripos($cliente['cedula'], $terminoBusqueda) !== false;
            });
        }
        
        // Separar clientes por estado para las pestañas
        $clientes_pendientes = array_filter($todosClientes, function($cliente) {
            return (($cliente['gestionado_detalle'] ?? 'no') !== 'si');
        });
        
        $clientes_gestionados = array_filter($todosClientes, function($cliente) {
            return (($cliente['gestionado_detalle'] ?? 'no') === 'si');
        });
        
        // "Acuerdos" (antes "ventas"): existe gestión con resultado_contacto acuerdo de pago
        $clientes_con_ventas = array_filter($todosClientes, function($cliente) {
            return !empty($cliente['tiene_acuerdo']);
        });

        // #region agent log b7eaa7 misClientes computed counts
        try { @file_put_contents(__DIR__ . '/../debug-b7eaa7.log', json_encode([
            'sessionId'=>'b7eaa7','runId'=>'pre','hypothesisId'=>'MC3',
            'location'=>'controllers/AsesorController.php:misClientes:lists',
            'message'=>'counts',
            'data'=>[
                'pendientes'=>is_array($clientes_pendientes)?count($clientes_pendientes):-1,
                'gestionados'=>is_array($clientes_gestionados)?count($clientes_gestionados):-1,
                'acuerdos'=>is_array($clientes_con_ventas)?count($clientes_con_ventas):-1,
            ],
            'timestamp'=>(int) round(microtime(true)*1000)
        ], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n", FILE_APPEND); } catch (Throwable $e) {}
        // #endregion

        // #region agent log b7eaa7 misClientes volver_a_llamar breakdown
        try {
            $matches = 0;
            $sampleNorms = [];
            foreach (array_slice($todosClientes, 0, 20) as $c) {
                $ur = (string)($c['ultimo_resultado'] ?? '');
                $norm = strtoupper(trim(str_replace(['_', '-'], ' ', $ur)));
                $norm = preg_replace('/\\s+/', ' ', $norm);
                if (in_array($norm, ['VOLVER A LLAMAR', 'VOLVER LLAMAR', 'AGENDA LLAMADA DE SEGUIMIENTO'], true)) $matches++;
                if (count($sampleNorms) < 8) $sampleNorms[] = ['raw' => substr($ur, 0, 40), 'norm' => substr($norm, 0, 40)];
            }
            @file_put_contents(__DIR__ . '/../debug-b7eaa7.log', json_encode([
                'sessionId'=>'b7eaa7','runId'=>'pre','hypothesisId'=>'MC5',
                'location'=>'controllers/AsesorController.php:misClientes:volver',
                'message'=>'volver_a_llamar_sample',
                'data'=>[
                    'todosCount'=>is_array($todosClientes)?count($todosClientes):-1,
                    'sampleChecked'=>min(20, is_array($todosClientes)?count($todosClientes):0),
                    'matchesInSample'=>$matches,
                    'sampleNorms'=>$sampleNorms,
                ],
                'timestamp'=>(int) round(microtime(true)*1000)
            ], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n", FILE_APPEND);
        } catch (Throwable $e) {}
        // #endregion
        
        // Calcular estadísticas
        // Compat con la vista (usa $clientesGestionados como contador de "ventas")
        $clientesGestionados = count($clientes_con_ventas);
        $clientesPendientes = count($clientes_pendientes);
        $clientesConGestiones = count($clientes_gestionados);
        $totalVentas = 0;
        
        foreach ($todosClientes as $cliente) {
            if (!empty($cliente['monto_venta'])) {
                $totalVentas += $cliente['monto_venta'];
            }
        }
        
        // Obtener datos de llamadas pendientes para las notificaciones
        $llamadasPendientes = $this->gestionModel->getLlamadasPendientesHoy($asesorId);
        $totalLlamadasPendientesHoy = $this->gestionModel->getTotalLlamadasPendientesHoy($asesorId);
        
        // Crear array de datos del dashboard para las notificaciones
        $datos_dashboard = [
            'llamadas_pendientes' => $llamadasPendientes,
            'total_llamadas_pendientes_hoy' => $totalLlamadasPendientesHoy
        ];
        
        // La UI ahora cambia pestañas sin recargar (cliente-side).
        // Por eso SIEMPRE enviamos la lista completa de clientes de tareas a la vista,
        // y la vista filtra por pestaña (Pendientes/Gestionados/Acuerdos/Volver a llamar).
        $pestaña_activa = isset($_GET['filter']) ? (string)$_GET['filter'] : 'todos';
        $clientesAsignados = $todosClientes;
        $total_clientes = is_array($clientesAsignados) ? count($clientesAsignados) : 0;
        $total_paginas = 1;
        $pagina_actual = 1;

        // #region agent log b7eaa7 misClientes final slice for view
        try { @file_put_contents(__DIR__ . '/../debug-b7eaa7.log', json_encode([
            'sessionId'=>'b7eaa7','runId'=>'pre','hypothesisId'=>'MC4',
            'location'=>'controllers/AsesorController.php:misClientes:view',
            'message'=>'slice',
            'data'=>[
                'tab'=>(string)$pestaña_activa,
                'total_clientes'=>(int)$total_clientes,
                'slice_count'=>is_array($clientesAsignados)?count($clientesAsignados):-1,
                'total_paginas'=>(int)$total_paginas,
            ],
            'timestamp'=>(int) round(microtime(true)*1000)
        ], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n", FILE_APPEND); } catch (Throwable $e) {}
        // #endregion
        
        require __DIR__ . '/../views/asesor_clientes_list.php';
    }
    
    /**
     * Muestra las tareas asignadas al asesor
     */
    public function misTareas() {
        $page_title = "Mis Tareas";
        $asesorId = $_SESSION['user_id'];
        
        // Configuración de paginación
        $por_pagina = 5;
        $pagina_actual = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
        $offset = ($pagina_actual - 1) * $por_pagina;
        
        // Obtener todas las tareas pendientes del asesor
        $todasTareasPendientes = $this->tareaModel->getTareasPendientesByAsesor($asesorId);
        
        // Calcular estadísticas
        $totalTareasPendientes = count($todasTareasPendientes);
        
        // Aplicar paginación
        $tareasPendientes = array_slice($todasTareasPendientes, $offset, $por_pagina);
        $total_paginas = ceil($totalTareasPendientes / $por_pagina);
        
        // Obtener clientes para cada tarea paginada
        $clientesTareasPendientes = [];
        foreach ($tareasPendientes as $tarea) {
            $clientesTarea = $this->tareaModel->getClientesByTarea($tarea['id']);
            $tarea['cliente_ids'] = array_column($clientesTarea, 'id');
            $clientesTareasPendientes[] = $tarea;
        }
        
        require __DIR__ . '/../views/asesor_tareas.php';
    }
    
    /**
     * Filtra los clientes gestionados según el resultado de la gestión
     */
    private function filtrarClientesGestionados($clientesGestionados, $filtroResultado) {
        if ($filtroResultado === 'todos') {
            return $clientesGestionados;
        }
        
        $clientesFiltrados = [];
        
        foreach ($clientesGestionados as $cliente) {
            $ultimoResultado = $cliente['ultimo_resultado'] ?? '';
            
            switch ($filtroResultado) {
                case 'volver_llamar':
                    if (in_array($ultimoResultado, ['VOLVER A LLAMAR', 'Agenda Llamada de Seguimiento'])) {
                        $clientesFiltrados[] = $cliente;
                    }
                    break;
                    
                case 'interesados':
                    if (in_array($ultimoResultado, ['INTERESADO', 'Cliente Interesado', 'Necesita Pensarlo'])) {
                        $clientesFiltrados[] = $cliente;
                    }
                    break;
                    
                case 'ventas_positivas':
                    if (in_array($ultimoResultado, ['VENTA INGRESADA', 'Venta Exitosa', 'Venta en Frío', 'Venta con Seguimiento', 'Venta Cruzada'])) {
                        $clientesFiltrados[] = $cliente;
                    }
                    break;
                    
                case 'rechazos':
                    if (in_array($ultimoResultado, ['Rechazo por Precio', 'Rechazo por Competencia', 'No Interesado', 'No Califica'])) {
                        $clientesFiltrados[] = $cliente;
                    }
                    break;
                    
                case 'contactos_no_efectivos':
                    if (in_array($ultimoResultado, ['No Contesta', 'Número Equivocado', 'Buzón de Voz', 'Número Fuera de Servicio', 'Cliente Ocupado'])) {
                        $clientesFiltrados[] = $cliente;
                    }
                    break;
                    
                case 'otros':
                    if (!in_array($ultimoResultado, [
                        'VOLVER A LLAMAR', 'Agenda Llamada de Seguimiento',
                        'INTERESADO', 'Cliente Interesado', 'Necesita Pensarlo',
                        'VENTA INGRESADA', 'Venta Exitosa', 'Venta en Frío', 'Venta con Seguimiento', 'Venta Cruzada',
                        'Rechazo por Precio', 'Rechazo por Competencia', 'No Interesado', 'No Califica',
                        'No Contesta', 'Número Equivocado', 'Buzón de Voz', 'Número Fuera de Servicio', 'Cliente Ocupado'
                    ]) && !empty($ultimoResultado)) {
                        $clientesFiltrados[] = $cliente;
                    }
                    break;
            }
        }
        
        return $clientesFiltrados;
    }
    
    /**
     * Determina la clase CSS para el resultado de la gestión
     */
    private function getClaseResultado($resultado) {
        if (empty($resultado)) return '';
        
        if (in_array($resultado, ['VOLVER A LLAMAR', 'Agenda Llamada de Seguimiento'])) {
            return 'volver-llamar';
        } elseif (in_array($resultado, ['INTERESADO', 'Cliente Interesado', 'Necesita Pensarlo'])) {
            return 'interesado';
        } elseif (in_array($resultado, ['VENTA INGRESADA', 'Venta Exitosa', 'Venta en Frío', 'Venta con Seguimiento', 'Venta Cruzada'])) {
            return 'venta';
        } elseif (in_array($resultado, ['Rechazo por Precio', 'Rechazo por Competencia', 'No Interesado', 'No Califica'])) {
            return 'rechazo';
        } elseif (in_array($resultado, ['No Contesta', 'Número Equivocado', 'Buzón de Voz', 'Número Fuera de Servicio', 'Cliente Ocupado'])) {
            return 'contacto-no-efectivo';
        } else {
            return 'otro';
        }
    }


    public function gestionarCliente($clienteId) {
        $page_title = "Gestionar Cliente";
        $asesorId = $_SESSION['user_id'];

        // #region agent log b7eaa7 gestionarCliente entry
        try { @file_put_contents(__DIR__ . '/../debug-b7eaa7.log', json_encode([
            'sessionId'=>'b7eaa7','runId'=>'pre','hypothesisId'=>'AG1',
            'location'=>'controllers/AsesorController.php:gestionarCliente:entry',
            'message'=>'enter',
            'data'=>[
                'asesorIdLen'=>strlen((string)$asesorId),
                'clienteIdRawType'=>gettype($clienteId),
                'clienteIdInt'=>(int)$clienteId,
                'hasGetId'=>isset($_GET['id'])?1:0,
                'getIdLen'=>strlen((string)($_GET['id']??'')),
            ],
            'timestamp'=>(int) round(microtime(true)*1000)
        ], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n", FILE_APPEND); } catch (Throwable $e) {}
        // #endregion

        $agentLogPath = __DIR__ . '/../debug-d200d9.log';
        // #region agent log
        @file_put_contents($agentLogPath, json_encode([
            'sessionId' => 'd200d9',
            'runId' => 'pre-fix',
            'hypothesisId' => 'HIST1',
            'location' => 'controllers/AsesorController.php:gestionarCliente:entry',
            'message' => 'entry',
            'data' => [
                'asesorId' => (string)$asesorId,
                'clienteId' => (int)$clienteId,
            ],
            'timestamp' => (int) round(microtime(true) * 1000),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n", FILE_APPEND);
        // #endregion
        
        // Verificar que el cliente pertenece a una base asignada al asesor
        $basesAsignadas = $this->tareaModel->getBasesAsignadasByAsesor($asesorId);
        $cargaIds = array_column($basesAsignadas, 'carga_id');
        
        if (empty($cargaIds)) {
            // #region agent log b7eaa7 gestionarCliente no bases
            try { @file_put_contents(__DIR__ . '/../debug-b7eaa7.log', json_encode(['sessionId'=>'b7eaa7','runId'=>'pre','hypothesisId'=>'AG2','location'=>'controllers/AsesorController.php:gestionarCliente:bases','message'=>'no_bases','data'=>['asesorIdLen'=>strlen((string)$asesorId)],'timestamp'=>(int) round(microtime(true)*1000)], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n", FILE_APPEND); } catch (Throwable $e) {}
            // #endregion
            $_SESSION['error_message'] = "No tienes bases asignadas para gestionar clientes.";
            header('Location: index.php?action=gestionar_clientes');
            exit;
        }
        
        // Verificar que el cliente pertenece a una de las bases asignadas (nuevo esquema: base_clientes + clientes.base_id).
        $sql = "SELECT c.*, b.nombre as nombre_cargue
                FROM clientes c
                JOIN base_clientes b ON c.base_id = b.id_base
                WHERE c.id_cliente = ? AND c.base_id IN (" . implode(',', array_fill(0, count($cargaIds), '?')) . ")";
        
        $params = array_merge([$clienteId], $cargaIds);
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$cliente) {
            // #region agent log b7eaa7 gestionarCliente cliente no permitido
            try { @file_put_contents(__DIR__ . '/../debug-b7eaa7.log', json_encode(['sessionId'=>'b7eaa7','runId'=>'pre','hypothesisId'=>'AG3','location'=>'controllers/AsesorController.php:gestionarCliente:cliente','message'=>'cliente_not_found_or_not_allowed','data'=>['clienteIdInt'=>(int)$clienteId,'basesCount'=>count($cargaIds)],'timestamp'=>(int) round(microtime(true)*1000)], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n", FILE_APPEND); } catch (Throwable $e) {}
            // #endregion
            $_SESSION['error_message'] = "No tienes permisos para gestionar este cliente o el cliente no existe.";
            header('Location: index.php?action=gestionar_clientes');
            exit;
        }

        // #region agent log
        @file_put_contents($agentLogPath, json_encode([
            'sessionId' => 'd200d9',
            'runId' => 'pre-fix',
            'hypothesisId' => 'HIST2',
            'location' => 'controllers/AsesorController.php:gestionarCliente:cliente',
            'message' => 'cliente cargado',
            'data' => [
                'clienteId' => (int)($cliente['id_cliente'] ?? 0),
                'cedula' => (string)($cliente['cedula'] ?? ''),
                'baseId' => (int)($cliente['base_id'] ?? 0),
                'nombreBase' => (string)($cliente['nombre_cargue'] ?? ''),
            ],
            'timestamp' => (int) round(microtime(true) * 1000),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n", FILE_APPEND);
        // #endregion

        // Compatibilidad con la vista `gestionar_cliente.php` (espera claves legacy)
        // Esquema real: clientes.tel1..tel10, id_cliente, (sin direccion)
        $cliente['id'] = $cliente['id'] ?? ($cliente['id_cliente'] ?? null);
        $cliente['telefono'] = $cliente['telefono'] ?? ($cliente['tel1'] ?? '');
        $cliente['celular2'] = $cliente['celular2'] ?? ($cliente['tel2'] ?? '');
        $cliente['cel3'] = $cliente['cel3'] ?? ($cliente['tel3'] ?? '');
        $cliente['cel4'] = $cliente['cel4'] ?? ($cliente['tel4'] ?? '');
        $cliente['cel5'] = $cliente['cel5'] ?? ($cliente['tel5'] ?? '');
        $cliente['cel6'] = $cliente['cel6'] ?? ($cliente['tel6'] ?? '');
        $cliente['cel7'] = $cliente['cel7'] ?? ($cliente['tel7'] ?? '');
        $cliente['cel8'] = $cliente['cel8'] ?? ($cliente['tel8'] ?? '');
        $cliente['cel9'] = $cliente['cel9'] ?? ($cliente['tel9'] ?? '');
        $cliente['cel10'] = $cliente['cel10'] ?? ($cliente['tel10'] ?? '');
        $cliente['cel11'] = $cliente['cel11'] ?? '';
        if (!isset($cliente['direccion'])) $cliente['direccion'] = 'No registrada';
        
        // En el esquema nuevo no existe `asignaciones_clientes`. Se gestiona directo por `historial_gestiones`.
        $asignacionId = null;
        
        // Verificar si el asesor tiene tareas pendientes
        $tieneTareasPendientes = $this->tareaModel->tieneTareasPendientes($asesorId);

        // #region agent log b7eaa7 gestionarCliente tareas pendientes flag
        try { @file_put_contents(__DIR__ . '/../debug-b7eaa7.log', json_encode([
            'sessionId'=>'b7eaa7','runId'=>'pre','hypothesisId'=>'NX1',
            'location'=>'controllers/AsesorController.php:gestionarCliente:tareas',
            'message'=>'tieneTareasPendientes',
            'data'=>[
                'asesorIdLen'=>strlen((string)$asesorId),
                'tieneTareasPendientes'=>$tieneTareasPendientes?1:0,
            ],
            'timestamp'=>(int) round(microtime(true)*1000)
        ], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n", FILE_APPEND); } catch (Throwable $e) {}
        // #endregion
        
        // Obtener estadísticas básicas del cliente
        $total_gestiones = 0; // Se puede implementar después
        $ultima_gestion = 'N/A'; // Se puede implementar después
        $estado_actual = 'Nuevo'; // Se puede implementar después
        
        // Procesar formulario si se envía
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->procesarGestionCliente($asesorId, $clienteId, $asignacionId);
        }
        
        // CRÍTICO: Obtener solo las facturas del cliente que pertenecen a la base de datos asignada
        // Esto asegura que el asesor solo vea las obligaciones de la base a la que tiene acceso
        $carga_excel_id = $cliente['base_id'] ?? null;
        $facturas = $this->facturacionModel->getFacturasByClienteId($clienteId, $carga_excel_id);
        
        // Obtener estadísticas de facturas (solo de la base de datos asignada)
        // Modificar para filtrar por carga_excel_id si es necesario
        $estadisticasFacturas = $this->facturacionModel->getEstadisticasFacturas($cliente['cedula'], $carga_excel_id);
        
        // Obtener historial completo por cédula (todas las bases / todos los asesores) para trazabilidad.
        $historial = $this->gestionModel->getGestionesByCedula((string)($cliente['cedula'] ?? ''));

        // #region agent log b7eaa7 gestionarCliente before view
        try { @file_put_contents(__DIR__ . '/../debug-b7eaa7.log', json_encode([
            'sessionId'=>'b7eaa7','runId'=>'pre','hypothesisId'=>'AG4',
            'location'=>'controllers/AsesorController.php:gestionarCliente:view',
            'message'=>'ready',
            'data'=>[
                'clienteIdDb'=>(int)($cliente['id_cliente']??0),
                'baseId'=>(int)($cliente['base_id']??0),
                'historialCount'=>is_array($historial)?count($historial):-1
            ],
            'timestamp'=>(int) round(microtime(true)*1000)
        ], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n", FILE_APPEND); } catch (Throwable $e) {}
        // #endregion

        // #region agent log
        @file_put_contents($agentLogPath, json_encode([
            'sessionId' => 'd200d9',
            'runId' => 'pre-fix',
            'hypothesisId' => 'HIST3',
            'location' => 'controllers/AsesorController.php:gestionarCliente:historial',
            'message' => 'historial cargado (por cedula)',
            'data' => [
                'count' => is_array($historial) ? count($historial) : -1,
            ],
            'timestamp' => (int) round(microtime(true) * 1000),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n", FILE_APPEND);
        // #endregion
        
        // CRÍTICO: Obtener solo los productos del cliente que pertenecen a la base de datos asignada
        $productoModel = new ProductoClienteModel($this->pdo);
        $productos = $productoModel->getProductosByCliente($clienteId, $carga_excel_id);
        
        // Obtener datos del teléfono del usuario para el softphone
        $datosTelefono = $this->usuarioModel->getDatosTelefono($asesorId);
        $tieneTelefono = $this->usuarioModel->tieneTelefonoConfigurado($asesorId);
        
        // Cargar configuración de Asterisk desde config/asterisk.php
        require_once __DIR__ . '/../config/asterisk.php';
        
        // Obtener configuración del PBX usando la función getWebRTCConfig()
        $asteriskConfig = getWebRTCConfig();
        
        // Configuración del softphone combinando configuración del PBX con datos del usuario
        $webrtcConfig = [
            'wss_server' => $asteriskConfig['wss_server'],
            'sip_domain' => $asteriskConfig['sip_domain'],
            'iceServers' => $asteriskConfig['iceServers'],
            'debug_mode' => $asteriskConfig['debug_mode']
        ];
        $basePath = '';
        
        require __DIR__ . '/../views/gestionar_cliente.php';
    }
    
    private function procesarGestionCliente($asesorId, $clienteId, $asignacionId) {
        try {
            // Validar datos requeridos
            if (empty($_POST['sub_tipificacion']) || empty($_POST['comentarios'])) {
                throw new Exception("Todos los campos obligatorios deben ser completados.");
            }
            
            $sub_tipificacion = $_POST['sub_tipificacion'];
            $comentarios = $_POST['comentarios'];
            
            // Determinar el tipo de gestión basado en la tipificación
            $tipo_gestion = 'Llamada de Venta'; // Por defecto
            
            // Campos específicos según la tipificación
            $monto_venta = null;
            $producto_vendido = null;
            $fecha_agendamiento = null;
            $fecha_nueva_llamada = null;
            $motivo_nueva_llamada = null;
            
            // Procesar según la tipificación seleccionada
            if ($sub_tipificacion === 'INTERESADO') {
                // Cliente interesado - información completa
                $tipo_gestion = 'Cliente Interesado';
                $edad = $_POST['edad'] ?? null;
                $num_personas = $_POST['num_personas'] ?? null;
                $valor_cotizacion = $_POST['valor_cotizacion'] ?? null;
                $whatsapp_enviado = $_POST['whatsapp_enviado'] ?? null;
                
                if (empty($edad) || empty($num_personas) || empty($valor_cotizacion) || empty($whatsapp_enviado)) {
                    throw new Exception("Para clientes interesados, todos los campos son obligatorios.");
                }
                
                // Agregar información adicional a los comentarios
                $comentarios .= "\n\n📊 INFORMACIÓN DEL CLIENTE INTERESADO:\n";
                $comentarios .= "Edad: " . $edad . " años\n";
                $comentarios .= "Personas a cubrir: " . $num_personas . "\n";
                $comentarios .= "Valor cotización: $" . number_format($valor_cotizacion, 0, ',', '.') . "\n";
                $comentarios .= "WhatsApp: " . $whatsapp_enviado;
                
            } elseif ($sub_tipificacion === 'VENTA INGRESADA') {
                // Venta ingresada - información completa
                $tipo_gestion = 'Venta Ingresada';
                $edad = $_POST['edad'] ?? null;
                $num_personas = $_POST['num_personas'] ?? null;
                $monto_venta = $_POST['monto_venta'] ?? null;
                $whatsapp_enviado = $_POST['whatsapp_enviado'] ?? null;
                
                if (empty($edad) || empty($num_personas) || empty($monto_venta) || empty($whatsapp_enviado)) {
                    throw new Exception("Para ventas ingresadas, todos los campos son obligatorios.");
                }
                
                // Agregar información adicional a los comentarios
                $comentarios .= "\n\n💰 INFORMACIÓN DE LA VENTA INGRESADA:\n";
                $comentarios .= "Edad: " . $edad . " años\n";
                $comentarios .= "Personas a cubrir: " . $num_personas . "\n";
                $comentarios .= "Valor venta: $" . number_format($monto_venta, 0, ',', '.') . "\n";
                $comentarios .= "WhatsApp: " . $whatsapp_enviado;
                
            } elseif ($sub_tipificacion === 'VOLVER A LLAMAR') {
                // Agendar nueva llamada
                $fecha_nueva_llamada = $_POST['fecha_nueva_llamada'] ?? null;
                $motivo_nueva_llamada = $_POST['motivo_nueva_llamada'] ?? null;
                
                if (empty($fecha_nueva_llamada) || empty($motivo_nueva_llamada)) {
                    throw new Exception("Para agendar nueva llamada, fecha y motivo son obligatorios.");
                }
                
                $tipo_gestion = 'Llamada de Seguimiento';
                $comentarios .= "\n\n📅 NUEVA LLAMADA AGENDADA:\nFecha: " . date('d/m/Y H:i', strtotime($fecha_nueva_llamada)) . "\nMotivo: " . $motivo_nueva_llamada;
                
            } else {
                // Otras tipificaciones - solo observaciones
                $tipo_gestion = 'Llamada de Gestión';
            }
            
            // Procesar información adicional del cliente si se proporciona
            $this->procesarInformacionAdicional($clienteId, $_POST);
            
            // Crear la gestión
            $gestionData = [
                'asignacion_id' => $asignacionId,
                'tipo_gestion' => $tipo_gestion,
                'resultado' => $sub_tipificacion,
                'comentarios' => $comentarios,
                // Campos específicos de tipificación que sí existen en la tabla
                'edad' => $_POST['edad'] ?? null,
                'num_personas' => $_POST['num_personas'] ?? null,
                'valor_cotizacion' => $_POST['valor_cotizacion'] ?? null,
                'whatsapp_enviado' => $_POST['whatsapp_enviado'] ?? null,
                'monto_venta' => ($sub_tipificacion === 'VENTA INGRESADA') ? ($_POST['monto_venta'] ?? null) : null,
                'proxima_fecha' => $fecha_nueva_llamada
            ];
            
            $gestionId = $this->gestionModel->crearGestion($gestionData);
            
            if ($gestionId) {
                            // Actualizar estado del cliente según la tipificación
            $nuevoEstado = $this->determinarNuevoEstadoCliente($sub_tipificacion);
            $this->clienteModel->actualizarCliente($clienteId, ['estado_cliente' => $nuevoEstado]);
                
                $_SESSION['success_message'] = "Gestión guardada exitosamente. El cliente ha sido marcado como: " . $nuevoEstado;
                
                // Redirigir de vuelta a la gestión del cliente usando el ID REAL del cliente
                header('Location: index.php?action=gestionar_cliente&id=' . $clienteId . '&gestion_guardada=1');
                exit;
            } else {
                throw new Exception("Error al guardar la gestión.");
            }
            
        } catch (Exception $e) {
            $_SESSION['error_message'] = $e->getMessage();
            header('Location: index.php?action=gestionar_cliente&id=' . $clienteId);
            exit;
        }
    }
    
    /**
     * Guarda un cliente nuevo durante la llamada
     */
    public function guardarClienteNuevo() {
        try {
            // Verificar que sea un asesor
            if ($_SESSION['user_role'] !== 'asesor') {
                throw new Exception("Acceso denegado.");
            }
            
            $asesorId = $_SESSION['user_id'];
            
            // Validar campos obligatorios
            if (empty($_POST['nuevo_nombre']) || empty($_POST['nuevo_cedula']) || empty($_POST['nuevo_telefono'])) {
                throw new Exception("Los campos Nombre, Cédula y Teléfono son obligatorios.");
            }
            
            // Preparar datos del cliente
            $clienteData = [
                'nombre' => trim($_POST['nuevo_nombre']),
                'cedula' => trim($_POST['nuevo_cedula']),
                'telefono' => trim($_POST['nuevo_telefono']),
                'celular2' => trim($_POST['nuevo_celular'] ?? ''),
                'email' => trim($_POST['nuevo_email'] ?? ''),
                'ciudad' => trim($_POST['nuevo_ciudad'] ?? ''),
                'estado_cliente' => 'Nuevo',
                'asesor_id' => $asesorId,
                'coordinador_id' => $_SESSION['user_coordinador_id'] ?? null,
                'carga_excel_id' => null // Cliente nuevo no viene de carga
            ];
            
            // Crear el cliente
            $clienteId = $this->clienteModel->crearCliente($clienteData);
            
            if ($clienteId) {
                // Crear gestión inicial para el cliente nuevo
                $gestionData = [
                    'cliente_id' => $clienteId,
                    'asesor_id' => $asesorId,
                    'coordinador_id' => $_SESSION['user_coordinador_id'] ?? null,
                    'tipo_gestion' => 'Llamada',
                    'resultado' => 'INTERESADO',
                    'comentarios' => "Cliente nuevo captado durante llamada. " . ($_POST['nuevo_observaciones'] ?? ''),
                    'estado' => 'Completada'
                ];
                
                $this->gestionModel->crearGestion($gestionData);
                
                // Respuesta exitosa
                echo json_encode([
                    'success' => true,
                    'message' => 'Cliente nuevo guardado exitosamente',
                    'cliente_id' => $clienteId
                ]);
            } else {
                throw new Exception("Error al crear el cliente.");
            }
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Obtiene los datos de un cliente específico para carga AJAX
     */
    public function obtenerDatosCliente() {
        try {
            // Verificar que sea un asesor
            if ($_SESSION['user_role'] !== 'asesor') {
                throw new Exception("Acceso denegado.");
            }

            $asesorId = $_SESSION['user_id'];
            $clienteId = $_GET['id'] ?? null;

            if (!$clienteId) {
                throw new Exception("ID de cliente no proporcionado.");
            }

            // Verificar que el cliente esté asignado al asesor
            $asignacionId = $this->clienteModel->getAsignacionId($asesorId, $clienteId);
            if (!$asignacionId) {
                throw new Exception("No tienes permisos para acceder a este cliente.");
            }

            // Obtener información del cliente
            $cliente = $this->clienteModel->getClienteById($clienteId);

            if (!$cliente) {
                throw new Exception("Cliente no encontrado.");
            }

            // Obtener historial de gestiones
            $historial = $this->gestionModel->getGestionByAsesorAndCliente($asesorId, $clienteId);

            // Preparar respuesta
            $clienteData = [
                'id' => $cliente['id'],
                'nombre' => $cliente['nombre'],
                'cedula' => $cliente['cedula'],
                'telefono' => $cliente['telefono'],
                'celular2' => $cliente['celular2'],
                'email' => $cliente['email'],
                'direccion' => $cliente['direccion'],
                'ciudad' => $cliente['ciudad'],
                'estado_cliente' => $cliente['estado_cliente']
            ];

            echo json_encode([
                'success' => true,
                'cliente' => $clienteData,
                'historial' => $historial ?: []
            ]);

        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Obtiene obligaciones/contratos (facturas) de un cliente específico para carga AJAX
     * (usado por cambiarClienteSinRecargar para evitar recargas y mantener el softphone activo)
     */
    public function obtenerContratosCliente()
    {
        try {
            // Verificar que sea un asesor
            if (($_SESSION['user_role'] ?? '') !== 'asesor') {
                throw new Exception("Acceso denegado.");
            }

            $asesorId = $_SESSION['user_id'] ?? null;
            $clienteId = $_GET['id'] ?? null;

            if (!$asesorId || !$clienteId) {
                throw new Exception("ID de cliente no proporcionado.");
            }

            // Verificar que el cliente esté asignado al asesor
            $asignacionId = $this->clienteModel->getAsignacionId($asesorId, $clienteId);
            if (!$asignacionId) {
                throw new Exception("No tienes permisos para acceder a este cliente.");
            }

            // CRÍTICO: Obtener solo las facturas del cliente que pertenecen a la base de datos asignada
            // Primero obtener el cliente para saber su carga_excel_id
            $sqlCliente = "SELECT carga_excel_id FROM clientes WHERE id = ?";
            $stmtCliente = $this->pdo->prepare($sqlCliente);
            $stmtCliente->execute([$clienteId]);
            $clienteData = $stmtCliente->fetch(PDO::FETCH_ASSOC);
            $carga_excel_id = $clienteData['carga_excel_id'] ?? null;
            
            // Obtener facturas del cliente filtradas por base de datos
            $facturas = $this->facturacionModel->getFacturasByClienteId($clienteId, $carga_excel_id);

            // Convertir facturas a formato de obligaciones para compatibilidad con el frontend
            // El frontend espera: id, producto, saldo_k_obligacion, obligacion, propiedad
            $obligaciones = [];
            if ($facturas && is_array($facturas)) {
                foreach ($facturas as $factura) {
                    $obligaciones[] = [
                        'id' => $factura['id'] ?? null,
                        'producto' => $factura['producto'] ?? 'N/A',
                        'saldo_k_obligacion' => $factura['saldo'] ?? 0,
                        'obligacion' => $factura['numero_factura'] ?? $factura['id'] ?? 'N/A',
                        'propiedad' => $factura['propiedad'] ?? 'N/A'
                    ];
                }
            }

            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => true,
                'obligaciones' => $obligaciones ?: []
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;

        } catch (Exception $e) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage(),
                'obligaciones' => []
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
    }

    /**
     * Obtiene el siguiente cliente en la lista del asesor
     */
    public function obtenerSiguienteCliente() {
        try {
            // Verificar que sea un asesor
            if ($_SESSION['user_role'] !== 'asesor') {
                throw new Exception("Acceso denegado.");
            }

            if (!headers_sent()) header('Content-Type: application/json; charset=utf-8');
            $asesorId = $_SESSION['user_id'];

            // #region agent log b7eaa7 obtenerSiguienteCliente entry
            try { @file_put_contents(__DIR__ . '/../debug-b7eaa7.log', json_encode([
                'sessionId'=>'b7eaa7','runId'=>'pre','hypothesisId'=>'NX2',
                'location'=>'controllers/AsesorController.php:obtenerSiguienteCliente:entry',
                'message'=>'enter',
                'data'=>[
                    'asesorIdLen'=>strlen((string)$asesorId),
                    'tieneTareasPendientes'=>$this->tareaModel->tieneTareasPendientes($asesorId)?1:0,
                ],
                'timestamp'=>(int) round(microtime(true)*1000)
            ], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n", FILE_APPEND); } catch (Throwable $e) {}
            // #endregion

            // Obtener el siguiente cliente no gestionado
            $siguienteCliente = $this->clienteModel->getSiguienteClienteAsesor($asesorId);

            // #region agent log b7eaa7 obtenerSiguienteCliente result shape
            try { @file_put_contents(__DIR__ . '/../debug-b7eaa7.log', json_encode([
                'sessionId'=>'b7eaa7','runId'=>'pre','hypothesisId'=>'NX3',
                'location'=>'controllers/AsesorController.php:obtenerSiguienteCliente:result',
                'message'=>'model_return',
                'data'=>[
                    'hasNext'=>$siguienteCliente?1:0,
                    'nextKeys'=>is_array($siguienteCliente)?array_slice(array_keys($siguienteCliente),0,25):[],
                    'id_cliente'=>$siguienteCliente['id_cliente']??null,
                    'id'=>$siguienteCliente['id']??null,
                ],
                'timestamp'=>(int) round(microtime(true)*1000)
            ], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n", FILE_APPEND); } catch (Throwable $e) {}
            // #endregion

            if ($siguienteCliente) {
                echo json_encode([
                    'success' => true,
                    'siguiente_cliente' => $siguienteCliente
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'No hay más clientes en tu lista'
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }

        } catch (Exception $e) {
            // #region agent log b7eaa7 obtenerSiguienteCliente exception
            try { @file_put_contents(__DIR__ . '/../debug-b7eaa7.log', json_encode([
                'sessionId'=>'b7eaa7','runId'=>'pre','hypothesisId'=>'NX4',
                'location'=>'controllers/AsesorController.php:obtenerSiguienteCliente:catch',
                'message'=>'exception',
                'data'=>[
                    'type'=>get_class($e),
                    'message'=>substr((string)$e->getMessage(),0,200),
                ],
                'timestamp'=>(int) round(microtime(true)*1000)
            ], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n", FILE_APPEND); } catch (Throwable $e2) {}
            // #endregion
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
    }
    
    /**
     * Determina el nuevo estado del cliente según la tipificación
     */
    private function determinarNuevoEstadoCliente($tipificacion) {
        // Tipificaciones de CON INTENCION DE PAGO
        if (strpos($tipificacion, '1.1.') === 0) {
            return 'En Proceso';
        }
        
        // Tipificaciones de SIN INTENCION DE PAGO
        if (strpos($tipificacion, '1.2.') === 0) {
            return 'No Interesado';
        }
        
        // Tipificaciones de NO COLABORA
        if (strpos($tipificacion, '1.3.') === 0) {
            return 'No Colabora';
        }
        
        // Tipificaciones de YA PAGO
        if (strpos($tipificacion, '1.4.') === 0) {
            return 'Pagado';
        }
        
        // Tipificaciones de NO CONTACTADO
        if (strpos($tipificacion, '2.') === 0) {
            return 'No Contactado';
        }
        
        // Fallback para tipificaciones antiguas
        switch ($tipificacion) {
            case 'VENDIDO':
                return 'Vendido';
            case 'INTERESADO':
                return 'En Proceso';
            case 'NO LE INTERESA':
                return 'No Interesado';
            case 'VOLVER A LLAMAR':
                return 'En Proceso';
            default:
                return 'Contactado';
        }
    }
    
    private function procesarInformacionAdicional($clienteId, $postData) {
        try {
            $nuevo_telefono = $postData['nuevo_telefono'] ?? null;
            $nuevo_celular = $postData['nuevo_celular'] ?? null;
            $nuevo_email = $postData['nuevo_email'] ?? null;
            $nueva_direccion = $postData['nueva_direccion'] ?? null;
            $nueva_ciudad = $postData['nueva_ciudad'] ?? null;
            
            // Campos opcionales de información de pago
            $fechaPagoEsperada = $postData['fecha_pago_esperada'] ?? null;
            $montoPendiente = $postData['monto_pendiente'] ?? null;
            $detallesPago = $postData['detalles_pago'] ?? null;
            $motivoNuevaLlamada = $postData['motivo_nueva_llamada'] ?? null;
            
            // Solo procesar si hay información nueva
            if ($nuevo_telefono || $nuevo_celular || $nuevo_email || $nueva_direccion || $nueva_ciudad || 
                $fechaPagoEsperada || $montoPendiente || $detallesPago || $motivoNuevaLlamada) {
                // Preparar datos para actualizar
                $datosActualizar = [];
                
                if ($nuevo_telefono) {
                    $datosActualizar['telefono'] = $nuevo_telefono;
                }
                
                if ($nuevo_celular) {
                    $datosActualizar['celular2'] = $nuevo_celular;
                }
                
                if ($nuevo_email) {
                    $datosActualizar['email'] = $nuevo_email;
                }
                
                if ($nueva_direccion) {
                    $datosActualizar['direccion'] = $nueva_direccion;
                }
                
                if ($nueva_ciudad) {
                    $datosActualizar['ciudad'] = $nueva_ciudad;
                }
                
                // Actualizar cliente si hay datos nuevos
                if (!empty($datosActualizar)) {
                    $this->clienteModel->actualizarCliente($clienteId, $datosActualizar);
                    
                    error_log("Información adicional actualizada para cliente $clienteId: " . json_encode($datosActualizar));
                }
                
                // Procesar campos opcionales de información de pago
                $camposOpcionales = [];
                
                if ($fechaPagoEsperada) {
                    $camposOpcionales['fecha_pago_esperada'] = $fechaPagoEsperada;
                }
                
                if ($montoPendiente) {
                    // Procesar monto pendiente (remover formato de pesos)
                    $montoPendiente = (int) str_replace(['.', ','], '', $montoPendiente);
                    $camposOpcionales['monto_pendiente'] = $montoPendiente;
                }
                
                if ($detallesPago) {
                    $camposOpcionales['detalles_pago'] = $detallesPago;
                }
                
                if ($motivoNuevaLlamada) {
                    $camposOpcionales['motivo_nueva_llamada'] = $motivoNuevaLlamada;
                }
                
                // Guardar campos opcionales en la base de datos si existen
                if (!empty($camposOpcionales)) {
                    // Aquí podrías guardar en una tabla específica para información adicional
                    // Por ahora, los agregamos a los comentarios o los guardamos en el historial
                    error_log("Campos opcionales procesados para cliente $clienteId: " . json_encode($camposOpcionales));
                }
            }
        } catch (Exception $e) {
            error_log("Error procesando información adicional: " . $e->getMessage());
        }
    }
    
    /**
     * Guarda la tipificación de un cliente
     */
    public function guardarTipificacion() {
        try {
            // Verificar que sea un asesor
            if ($_SESSION['user_role'] !== 'asesor') {
                throw new Exception("Acceso denegado.");
            }
            
            $asesorId = $_SESSION['user_id'];
            $clienteId = $_POST['cliente_id'] ?? null;
            
            if (!$clienteId) {
                throw new Exception("ID de cliente no proporcionado.");
            }
            
            // Obtener datos del formulario
            $formaContacto = $_POST['forma_contacto'] ?? 'llamada';
            $telefonoContacto = trim($_POST['telefono_contacto'] ?? '');
            $canalesAutorizados = $_POST['canales_autorizados'] ?? [];
            $tipificacion = $_POST['tipificacion'] ?? '';
            $subTipificacion = $_POST['sub_tipificacion'] ?? '';
            $tipoContactoArbol = strtolower(preg_replace('/[^a-z_]/', '', (string) ($_POST['tipo_contacto_arbol'] ?? '')));
            if (!in_array($tipoContactoArbol, ['contacto_exitoso', 'contacto_tercero', 'sin_contacto'], true)) {
                $tipoContactoArbol = '';
            }
            $comentarios = trim($_POST['comentarios'] ?? '');
            
            // Obtener campos adicionales específicos
            $fechaAcuerdo = $_POST['fecha_acuerdo'] ?? null;
            $montoAcuerdo = $_POST['monto_acuerdo'] ?? null;
            $fechaNuevaLlamada = $_POST['fecha_nueva_llamada'] ?? null;
            $motivoNuevaLlamada = $_POST['motivo_nueva_llamada'] ?? null;
            $nuevoTelefono = $_POST['nuevo_telefono'] ?? null;
            $observacionesTercero = $_POST['observaciones_tercero'] ?? null;
            
            // Nuevos campos para las opciones actualizadas
            $mensajeTercero = $_POST['mensaje_tercero'] ?? null;
            $nombreTercero = $_POST['nombre_tercero'] ?? null;
            $nuevaDireccion = $_POST['nueva_direccion'] ?? null;
            $emailEnvio = $_POST['email_envio'] ?? null;
            $observacionesEnvio = $_POST['observaciones_envio'] ?? null;
            $tipoNovedad = $_POST['tipo_novedad'] ?? null;
            $descripcionNovedad = $_POST['descripcion_novedad'] ?? null;
            $motivoFallecido = $_POST['motivo_fallecido'] ?? null;
            $observacionesFallecido = $_POST['observaciones_fallecido'] ?? null;
            // Asegurar codificación UTF-8 correcta
            $comentarios = mb_convert_encoding($comentarios, 'UTF-8', 'auto');
            $edadCliente = $_POST['edad'] ?? null;
            $numPersonas = $_POST['num_personas'] ?? null;
            $valorCotizacion = $_POST['valor_cotizacion'] ?? null;
            $whatsappEnviado = $_POST['whatsapp_enviado'] ?? null;
            $montoVenta = $_POST['monto_venta'] ?? null;
            $duracionLlamada = $_POST['duracion_llamada'] ?? null;
            $fechaProximaLlamada = $_POST['fecha_nueva_llamada'] ?? null;
            $horaProximaLlamada = null;
            
            // Campos opcionales de información de pago
            $fechaPagoEsperada = $_POST['fecha_pago_esperada'] ?? null;
            $montoPendiente = $_POST['monto_pendiente'] ?? null;
            $detallesPago = $_POST['detalles_pago'] ?? null;
            $motivoNuevaLlamada = $_POST['motivo_nueva_llamada'] ?? null;
            
            // Procesar campos de valor (remover formato de pesos)
            if ($valorCotizacion) {
                $valorCotizacion = (int) str_replace(['.', ','], '', $valorCotizacion);
            }
            if ($montoVenta) {
                $montoVenta = (int) str_replace(['.', ','], '', $montoVenta);
            }
            if ($montoAcuerdo) {
                $montoAcuerdo = (int) str_replace(['.', ','], '', $montoAcuerdo);
            }
            
            // Validar campos obligatorios
            if (empty($tipificacion) || empty($comentarios)) {
                throw new Exception("La tipificación y los comentarios son obligatorios.");
            }
            
            // Validar campos específicos para acuerdo de pago
            if ($tipificacion === 'acuerdo_pago') {
                if (empty($fechaAcuerdo)) {
                    throw new Exception("La fecha de pago es obligatoria para acuerdos de pago.");
                }
                if (empty($montoAcuerdo) || $montoAcuerdo <= 0) {
                    throw new Exception("El monto del acuerdo debe ser mayor a cero.");
                }
                
                // Validar que la fecha de pago sea futura o actual
                $fechaAcuerdoObj = new DateTime($fechaAcuerdo);
                $hoy = new DateTime();
                $hoy->setTime(0, 0, 0); // Establecer hora a medianoche para comparar solo fechas
                
                if ($fechaAcuerdoObj < $hoy) {
                    throw new Exception("La fecha de pago debe ser hoy o una fecha futura.");
                }
            }
            
            // En el esquema actual no existe `asignaciones_clientes`.
            // Validamos acceso: el cliente debe pertenecer a una base asignada (por tareas) al asesor.
            $basesAsignadas = $this->tareaModel->getBasesAsignadasByAsesor($asesorId);
            $cargaIds = array_column((array)$basesAsignadas, 'carga_id');
            if (empty($cargaIds)) {
                throw new Exception("No tienes bases asignadas para gestionar clientes.");
            }
            $stmtAcc = $this->pdo->prepare(
                "SELECT 1 FROM clientes c WHERE c.id_cliente = ? AND c.base_id IN (" . implode(',', array_fill(0, count($cargaIds), '?')) . ") LIMIT 1"
            );
            $stmtAcc->execute(array_merge([(int)$clienteId], $cargaIds));
            if (!$stmtAcc->fetch(PDO::FETCH_ASSOC)) {
                throw new Exception("No tienes permisos para gestionar este cliente.");
            }

            $asignacionId = null;
            
            // Obtener información de la factura a gestionar
            $facturaGestionar = $_POST['factura_gestionar'] ?? null;
            $obligacionId = $_POST['obligacion_id'] ?? null;
            $productoGestionado = $_POST['producto_gestionado'] ?? null;
            $montoObligacion = $_POST['monto_obligacion'] ?? null;
            $numeroObligacion = $_POST['numero_obligacion'] ?? null;
            $estadoObligacion = $_POST['estado_obligacion'] ?? null;
            $facturasIds = $_POST['facturas_ids'] ?? null;

            $crearPayloadGestion = function(array $extra = []) use (
                $asesorId,
                $clienteId,
                $tipoContactoArbol,
                $tipificacion,
                $subTipificacion,
                $comentarios,
                $montoVenta,
                $duracionLlamada,
                $edadCliente,
                $numPersonas,
                $valorCotizacion,
                $whatsappEnviado,
                $fechaProximaLlamada,
                $formaContacto,
                $fechaAcuerdo,
                $montoAcuerdo,
                $fechaNuevaLlamada,
                $motivoNuevaLlamada,
                $nuevoTelefono,
                $observacionesTercero,
                $mensajeTercero,
                $nombreTercero,
                $nuevaDireccion,
                $emailEnvio,
                $observacionesEnvio,
                $tipoNovedad,
                $descripcionNovedad,
                $motivoFallecido,
                $observacionesFallecido,
                $telefonoContacto
            ) {
                return array_merge([
                    'asesor_cedula' => $asesorId,
                    'cliente_id' => (int)$clienteId,
                    'tipo_contacto' => $tipoContactoArbol,
                    'resultado_contacto' => $tipificacion,
                    'razon_especifica' => $subTipificacion ?: '',
                    'comentarios' => $comentarios,
                    'monto_venta' => $montoVenta,
                    'duracion_llamada' => $duracionLlamada,
                    'edad' => $edadCliente,
                    'num_personas' => $numPersonas,
                    'valor_cotizacion' => $valorCotizacion,
                    'whatsapp_enviado' => $whatsappEnviado,
                    'proxima_fecha' => $fechaProximaLlamada,
                    'forma_contacto' => $formaContacto,
                    'fecha_acuerdo' => $fechaAcuerdo,
                    'monto_acuerdo' => $montoAcuerdo,
                    'fecha_nueva_llamada' => $fechaNuevaLlamada,
                    'motivo_nueva_llamada' => $motivoNuevaLlamada,
                    'nuevo_telefono' => $nuevoTelefono,
                    'observaciones_tercero' => $observacionesTercero,
                    'mensaje_tercero' => $mensajeTercero,
                    'nombre_tercero' => $nombreTercero,
                    'nueva_direccion' => $nuevaDireccion,
                    'email_envio' => $emailEnvio,
                    'observaciones_envio' => $observacionesEnvio,
                    'tipo_novedad' => $tipoNovedad,
                    'descripcion_novedad' => $descripcionNovedad,
                    'motivo_fallecido' => $motivoFallecido,
                    'observaciones_fallecido' => $observacionesFallecido,
                    'telefono_contacto' => $telefonoContacto,
                ], $extra);
            };
            
            // Si se seleccionaron todas las facturas, procesar cada una
            if ($obligacionId === 'todas_las_facturas' && !empty($facturasIds)) {
                $facturasIdsArray = explode(',', $facturasIds);
                
                // Crear una gestión para cada factura individual
                foreach ($facturasIdsArray as $facturaId) {
                    $facturaId = trim($facturaId);
                    if (empty($facturaId)) continue;
                    
                    // Obtener información de la factura específica
                    $facturaInfo = $this->facturacionModel->getFacturaById($facturaId);
                    
                    if ($facturaInfo) {
                        // Crear registro individual para cada factura
                        $gestionDataIndividual = $crearPayloadGestion([
                            'comentarios' => $comentarios . "\n\n[GESTIÓN APLICADA A TODAS LAS FACTURAS - Factura ID: " . $facturaId . "]",
                            'factura_gestionar' => $facturaGestionar,
                            'obligacion_id' => $facturaId,
                            'producto_gestionado' => $productoGestionado,
                            'monto_obligacion' => $facturaInfo['saldo'] ?? 0,
                            'numero_obligacion' => $facturaInfo['numero_factura'] ?? '',
                            'estado_obligacion' => $facturaInfo['estado_factura'] ?? 'pendiente',
                        ]);
                        
                        // Guardar gestión individual
                        $gestionIdIndividual = $this->gestionModel->crearGestion($gestionDataIndividual);
                        
                        // Guardar canales autorizados para cada gestión
                        if (!empty($canalesAutorizados) && $gestionIdIndividual) {
                            $this->gestionModel->guardarCanalesAutorizados($gestionIdIndividual, $canalesAutorizados);
                        }
                    }
                }
                
                // Crear gestión principal que representa "Todas las facturas"
                $gestionData = $crearPayloadGestion([
                    'comentarios' => $comentarios . "\n\n[GESTIÓN APLICADA A TODAS LAS FACTURAS - Total: " . count($facturasIdsArray) . " facturas]",
                    'factura_gestionar' => $facturaGestionar,
                    'obligacion_id' => $obligacionId,
                    'producto_gestionado' => $productoGestionado,
                    'monto_obligacion' => $montoObligacion,
                    'numero_obligacion' => $numeroObligacion,
                    'estado_obligacion' => $estadoObligacion
                ]);
            } else {
                // Procesamiento normal para factura individual
                $gestionData = $crearPayloadGestion([
                    'factura_gestionar' => $facturaGestionar,
                    'obligacion_id' => $obligacionId,
                    'producto_gestionado' => $productoGestionado,
                    'monto_obligacion' => $montoObligacion,
                    'numero_obligacion' => $numeroObligacion,
                    'estado_obligacion' => $estadoObligacion
                ]);
            }

            // La tabla `historial_gestiones` requiere obligacion_id NOT NULL.
            // Si el usuario selecciona "ninguna", aplicamos la gestión a la primera obligación del cliente.
            if ($gestionData['obligacion_id'] === 'ninguna' || $gestionData['obligacion_id'] === null || $gestionData['obligacion_id'] === '') {
                $baseId = (int)($this->pdo->query("SELECT base_id FROM clientes WHERE id_cliente = " . (int)$clienteId)->fetchColumn() ?? 0);
                $obs = $this->facturacionModel->getFacturasByClienteId((int)$clienteId, $baseId ?: null);
                $first = $obs[0]['id_obligacion'] ?? $obs[0]['id'] ?? null;
                if ($first) {
                    $gestionData['obligacion_id'] = (int)$first;
                }
            }
            
            // Guardar en historial_gestion
            $gestionId = $this->gestionModel->crearGestion($gestionData);
            
            if (!$gestionId) {
                throw new Exception("Error al guardar la gestión.");
            }

            // Enlazar la gestión con la llamada del softphone (1:1) si llega call_id desde la vista.
            $callId = isset($_POST['call_id']) ? trim((string)$_POST['call_id']) : '';
            // #region agent log 058b8a guardarTipificacion call_id
            try { @file_put_contents(__DIR__ . '/../debug-058b8a.log', json_encode([
                'sessionId'=>'058b8a',
                'runId'=>'pre',
                'hypothesisId'=>'H1',
                'location'=>'controllers/AsesorController.php:guardarTipificacion',
                'message'=>'call_id',
                'data'=>[
                    'hasCallId'=>($callId!==''?1:0),
                    'callIdLen'=>strlen($callId),
                    'gestionId'=>(int)$gestionId,
                    'clienteId'=>(int)$clienteId,
                ],
                'timestamp'=>(int) round(microtime(true)*1000),
            ], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n", FILE_APPEND); } catch (Throwable $e) {}
            // #endregion
            if ($callId !== '') {
                try {
                    $this->callLogModel->enlazarGestion($callId, (int)$gestionId);
                } catch (Throwable $e) {}
            }
            
            // Guardar canales autorizados múltiples
            if (!empty($canalesAutorizados)) {
                $this->gestionModel->guardarCanalesAutorizados($gestionId, $canalesAutorizados);
                
                // Registrar actividad de canales autorizados
                $this->registrarActividadCanales($gestionId, $clienteId, $asesorId, $canalesAutorizados);
            }
            
            // Actualizar estado del cliente
            $nuevoEstado = $this->determinarNuevoEstadoCliente($subTipificacion ?: $tipificacion);
            $this->clienteModel->actualizarCliente($clienteId, ['estado_cliente' => $nuevoEstado]);
            
            // Procesar información adicional si existe
            $this->procesarInformacionAdicional($clienteId, $_POST);
            
            // Respuesta exitosa
            echo json_encode([
                'success' => true,
                'message' => 'Tipificación guardada exitosamente',
                'gestion_id' => $gestionId,
                'redirect_url' => 'index.php?action=gestionar_cliente&id=' . $clienteId . '&gestion_guardada=1'
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Obtiene el historial de gestiones de un cliente específico
     */
    public function obtenerHistorialCliente() {
        try {
            // Verificar que sea un asesor
            if ($_SESSION['user_role'] !== 'asesor') {
                throw new Exception("Acceso denegado.");
            }
            
            $asesorId = $_SESSION['user_id'];
            $clienteId = $_GET['id'] ?? null;

            // #region agent log b7eaa7 obtenerHistorialCliente entry
            try { @file_put_contents(__DIR__ . '/../debug-b7eaa7.log', json_encode([
                'sessionId'=>'b7eaa7','runId'=>'pre','hypothesisId'=>'AH1',
                'location'=>'controllers/AsesorController.php:obtenerHistorialCliente:entry',
                'message'=>'enter',
                'data'=>[
                    'asesorIdLen'=>strlen((string)$asesorId),
                    'clienteIdLen'=>strlen((string)($clienteId??'')),
                    'role'=>(string)($_SESSION['user_role']??'')
                ],
                'timestamp'=>(int) round(microtime(true)*1000)
            ], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n", FILE_APPEND); } catch (Throwable $e) {}
            // #endregion
            
            if (!$clienteId) {
                throw new Exception("ID de cliente no proporcionado.");
            }
            
            // Obtener el historial del cliente
            $historial = $this->gestionModel->getGestionByAsesorAndCliente($asesorId, $clienteId);

            // #region agent log b7eaa7 obtenerHistorialCliente result
            try { @file_put_contents(__DIR__ . '/../debug-b7eaa7.log', json_encode([
                'sessionId'=>'b7eaa7','runId'=>'pre','hypothesisId'=>'AH2',
                'location'=>'controllers/AsesorController.php:obtenerHistorialCliente:result',
                'message'=>'fetched',
                'data'=>[
                    'isFalse'=>$historial===false?1:0,
                    'count'=>is_array($historial)?count($historial):-1
                ],
                'timestamp'=>(int) round(microtime(true)*1000)
            ], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n", FILE_APPEND); } catch (Throwable $e) {}
            // #endregion
            
            if ($historial === false) {
                throw new Exception("Error al obtener el historial del cliente.");
            }
            
            // Respuesta exitosa
            echo json_encode([
                'success' => true,
                'historial' => $historial
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    // ===== NUEVOS MÉTODOS PARA EL SISTEMA DE TAREAS =====

    /**
     * Vista de gestión de clientes (búsqueda por cédula)
     */
    public function gestionarClientes() {
        $page_title = "Gestión de Clientes";
        $asesorId = $_SESSION['user_id'];
        
        // Obtener bases asignadas al asesor
        $basesAsignadas = $this->tareaModel->getBasesAsignadasByAsesor($asesorId);
        
        // Verificar si tiene tareas pendientes
        $tieneTareasPendientes = $this->tareaModel->tieneTareasPendientes($asesorId);
        
        require __DIR__ . '/../views/asesor_gestionar_clientes.php';
    }

    /**
     * Buscar cliente por cédula en las bases asignadas
     */
    public function buscarClientePorCedula() {
        $asesorId = $_SESSION['user_id'];
        $cedula = $_GET['cedula'] ?? '';
        
        if (empty($cedula)) {
            echo json_encode([
                'success' => false,
                'message' => 'Cédula requerida'
            ]);
            exit;
        }
        
        $agentLogPath = __DIR__ . '/../debug-a2fdce.log';
        // #region agent log
        @file_put_contents($agentLogPath, json_encode([
            'sessionId' => 'a2fdce',
            'runId' => 'pre-fix',
            'hypothesisId' => 'H1',
            'location' => 'controllers/AsesorController.php:buscarClientePorCedula',
            'message' => 'Buscar cliente por cédula en bases asignadas',
            'data' => [
                'hasAsesorId' => $asesorId !== null && $asesorId !== '',
                'cedulaLen' => strlen((string)$cedula),
            ],
            'timestamp' => (int) round(microtime(true) * 1000),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n", FILE_APPEND);
        // #endregion

        $clientes = $this->tareaModel->buscarClienteEnBasesAsignadas($asesorId, $cedula);

        // #region agent log
        @file_put_contents($agentLogPath, json_encode([
            'sessionId' => 'a2fdce',
            'runId' => 'pre-fix',
            'hypothesisId' => 'H1',
            'location' => 'controllers/AsesorController.php:buscarClientePorCedula',
            'message' => 'Resultado búsqueda cédula',
            'data' => [
                'resultCount' => is_array($clientes) ? count($clientes) : -1,
            ],
            'timestamp' => (int) round(microtime(true) * 1000),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n", FILE_APPEND);
        // #endregion
        
        echo json_encode([
            'success' => true,
            'clientes' => $clientes,
            'total' => count($clientes)
        ]);
        exit;
    }
    
    /**
     * Buscar clientes por término general (nombre, cédula, teléfono) en bases asignadas
     */
    public function buscarClientesPorTermino() {
        // Limpiar cualquier output previo
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        // Establecer headers JSON
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            // Verificar sesión
            if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'asesor') {
                echo json_encode([
                    'success' => false,
                    'message' => 'Acceso no autorizado',
                    'clientes' => []
                ]);
                exit;
            }
            
            $asesorId = $_SESSION['user_id'];
            $termino = $_GET['termino'] ?? $_POST['termino'] ?? '';
            
            if (empty($termino) || strlen($termino) < 2) {
                echo json_encode([
                    'success' => false,
                    'message' => 'El término de búsqueda debe tener al menos 2 caracteres',
                    'clientes' => []
                ]);
                exit;
            }
            
            $agentLogPath = __DIR__ . '/../debug-a2fdce.log';
            // #region agent log
            @file_put_contents($agentLogPath, json_encode([
                'sessionId' => 'a2fdce',
                'runId' => 'pre-fix',
                'hypothesisId' => 'H2',
                'location' => 'controllers/AsesorController.php:buscarClientesPorTermino',
                'message' => 'Buscar clientes por término en bases asignadas',
                'data' => [
                    'hasAsesorId' => $asesorId !== null && $asesorId !== '',
                    'terminoLen' => strlen((string)$termino),
                ],
                'timestamp' => (int) round(microtime(true) * 1000),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n", FILE_APPEND);
            // #endregion

            $clientes = $this->tareaModel->buscarClientesPorTermino($asesorId, $termino, 20);

            // #region agent log
            @file_put_contents($agentLogPath, json_encode([
                'sessionId' => 'a2fdce',
                'runId' => 'pre-fix',
                'hypothesisId' => 'H2',
                'location' => 'controllers/AsesorController.php:buscarClientesPorTermino',
                'message' => 'Resultado búsqueda término',
                'data' => [
                    'resultCount' => is_array($clientes) ? count($clientes) : -1,
                ],
                'timestamp' => (int) round(microtime(true) * 1000),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n", FILE_APPEND);
            // #endregion
            
            echo json_encode([
                'success' => true,
                'clientes' => $clientes,
                'total' => count($clientes)
            ], JSON_UNESCAPED_UNICODE);
            exit;
            
        } catch (Exception $e) {
            error_log("Error en buscarClientesPorTermino: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Error al buscar clientes: ' . $e->getMessage(),
                'clientes' => []
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    /**
     * Obtener información de un cliente específico para gestión
     */
    public function getClienteParaGestion() {
        $asesorId = $_SESSION['user_id'];
        $clienteId = $_GET['cliente_id'] ?? null;
        
        if (!$clienteId) {
            echo json_encode([
                'success' => false,
                'message' => 'ID de cliente requerido'
            ]);
            exit;
        }
        
        // Verificar que el cliente pertenece a una base asignada al asesor
        $basesAsignadas = $this->tareaModel->getBasesAsignadasByAsesor($asesorId);
        $cargaIds = array_column($basesAsignadas, 'carga_id');
        
        $sql = "SELECT c.*, b.nombre as nombre_cargue
                FROM clientes c
                JOIN base_clientes b ON c.base_id = b.id_base
                WHERE c.id_cliente = ? AND c.base_id IN (" . implode(',', array_fill(0, count($cargaIds), '?')) . ")";
        
        $params = array_merge([$clienteId], $cargaIds);
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$cliente) {
            echo json_encode([
                'success' => false,
                'message' => 'Cliente no encontrado o no tienes acceso a él'
            ]);
            exit;
        }
        
        // Obtener historial de gestiones del cliente
        $historial = $this->gestionModel->getGestionByAsesorAndCliente($asesorId, $clienteId);
        
        echo json_encode([
            'success' => true,
            'cliente' => $cliente,
            'historial' => $historial
        ]);
        exit;
    }

    /**
     * Obtener tareas pendientes del asesor
     */
    public function getTareasPendientes() {
        $asesorId = $_SESSION['user_id'];
        $tareas = $this->tareaModel->getTareasPendientesByAsesor($asesorId);
        
        echo json_encode([
            'success' => true,
            'tareas' => $tareas
        ]);
        exit;
    }

    /**
     * Marcar tarea como completada
     */
    public function completarTarea() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['error' => 'Método no permitido']);
            exit;
        }
        
        $asesorId = $_SESSION['user_id'];
        $tareaId = $_POST['tarea_id'] ?? null;
        
        if (!$tareaId) {
            echo json_encode(['error' => 'ID de tarea requerido']);
            exit;
        }
        
        // Verificar que la tarea pertenece al asesor
        $tareas = $this->tareaModel->getTareasByAsesor($asesorId);
        $tareaExiste = false;
        foreach ($tareas as $tarea) {
            if ($tarea['id'] == $tareaId) {
                $tareaExiste = true;
                break;
            }
        }
        
        if (!$tareaExiste) {
            echo json_encode(['error' => 'No tienes permisos para modificar esta tarea']);
            exit;
        }
        
        $resultado = $this->tareaModel->actualizarEstadoTarea($tareaId, 'completada', $asesorId);
        
        if ($resultado) {
            echo json_encode(['success' => true, 'message' => 'Tarea completada correctamente']);
        } else {
            echo json_encode(['error' => 'Error al completar la tarea']);
        }
        exit;
    }
    
    /**
     * Muestra la interfaz de gestión de productos para un cliente específico
     */
    public function gestionarProductosCliente() {
        $this->verificarRol('asesor');
        
        $clienteId = $this->getGet('cliente_id');
        $clienteId = $this->validarId($clienteId, 'cliente');
        
        // Verificar que el cliente esté asignado al asesor
        $cliente = $this->clienteModel->getClienteById($clienteId);
        if (!$cliente || $cliente['asesor_id'] != $_SESSION['user_id']) {
            $this->redirigirConError('index.php?action=mis_clientes', 'Cliente no encontrado o no asignado');
            return;
        }
        
        // Redirigir a la interfaz de gestión de productos
        header('Location: index.php?action=gestionar_productos&cliente_id=' . $clienteId);
        exit;
    }
    
    
    /**
     * Obtiene los productos pendientes de un cliente específico
     */
    public function obtenerProductosPendientes() {
        try {
            $this->verificarRol('asesor');
            
            $clienteId = $this->getGet('cliente_id');
            $clienteId = $this->validarId($clienteId, 'cliente');
            
            // Verificar que el cliente esté asignado al asesor
            $cliente = $this->clienteModel->getClienteById($clienteId);
            if (!$cliente || $cliente['asesor_id'] != $_SESSION['user_id']) {
                throw new Exception('Cliente no encontrado o no asignado');
            }
            
            // Obtener productos del cliente
            $productoModel = new ProductoClienteModel($this->pdo);
            $productos = $productoModel->getProductosByCliente($clienteId);
            
            echo json_encode([
                'success' => true,
                'productos' => $productos
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Registra automáticamente la actividad de canales autorizados
     */
    private function registrarActividadCanales($gestionId, $clienteId, $asesorId, $canales) {
        try {
            require_once __DIR__ . '/../models/ActividadProductoModel.php';
            $actividadModel = new ActividadProductoModel($this->pdo);
            
            $actividadModel->registrarCanalesAutorizados($gestionId, $clienteId, $asesorId, $canales);
            
        } catch (Exception $e) {
            error_log("Error en registrarActividadCanales: " . $e->getMessage());
        }
    }
    
    /**
     * Agregar información adicional del cliente (teléfonos, email)
     * Soporta tanto datos JSON como FormData
     */
    public function agregarInformacionCliente() {
        try {
            // Limpiar buffer de salida para evitar problemas con JSON
            if (ob_get_level()) ob_clean();
            header('Content-Type: application/json; charset=UTF-8');
            
            // Leer datos JSON si el Content-Type es application/json
            $inputData = [];
            $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
            
            if (strpos($contentType, 'application/json') !== false) {
                $jsonInput = file_get_contents('php://input');
                $inputData = json_decode($jsonInput, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new Exception('Error al decodificar JSON: ' . json_last_error_msg());
                }
            } else {
                // Si es FormData, usar $_POST directamente
                $inputData = $_POST;
            }
            
            $clienteId = $inputData['cliente_id'] ?? null;
            
            if (!$clienteId) {
                throw new Exception('ID de cliente no proporcionado');
            }
            
            // Verificar que el cliente existe
            $cliente = $this->clienteModel->getClienteById($clienteId);
            if (!$cliente) {
                throw new Exception('Cliente no encontrado');
            }
            
            // Verificar que el asesor tiene acceso al cliente
            // Regla nueva:
            // 1) Tiene acceso si está asignado a la BASE (asignaciones_base_asesor) de este cliente
            // 2) O si tiene una asignación directa del cliente (getAssignedClientsForAsesor / asignaciones_clientes / clientes.asesor_id)
            $asesorId = $_SESSION['user_id'];
            $clienteAsignado = false;

            // 1) ¿El asesor tiene acceso por base? (emermedica_cobranza.sql: asignacion_base_asesores)
            $baseId = isset($cliente['base_id']) ? (int)$cliente['base_id'] : 0;
            if ($baseId > 0) {
                $stmt = $this->pdo->prepare("
                    SELECT 1
                    FROM asignacion_base_asesores
                    WHERE base_id = ? AND asesor_cedula = ? AND estado = 'activa'
                    LIMIT 1
                ");
                $stmt->execute([$baseId, (string)$asesorId]);
                if ($stmt->fetchColumn()) {
                    $clienteAsignado = true;
                }
            }

            // 2) Si no tiene acceso por base, verificar acceso por tarea/detalle_tareas
            if (!$clienteAsignado) {
                $clientesAsignados = $this->clienteModel->getAssignedClientsForAsesor($asesorId);
                foreach ($clientesAsignados as $clienteAsig) {
                    $cid = (int)($clienteAsig['id_cliente'] ?? $clienteAsig['id'] ?? 0);
                    if ($cid === (int)$clienteId) {
                        $clienteAsignado = true;
                        break;
                    }
                }
            }
            
            if (!$clienteAsignado) {
                throw new Exception('No tienes acceso a este cliente');
            }
            
            // Iniciar transacción si no hay una activa
            if (!$this->pdo->inTransaction()) {
                $this->pdo->beginTransaction();
            }
            
            // Actualizar información del cliente (email)
            // El JavaScript envía 'email', pero también puede venir como 'nuevo_email' desde FormData
            $actualizacionesCliente = [];
            
            $email = $inputData['email'] ?? $inputData['nuevo_email'] ?? null;
            if (!empty($email) && trim($email) !== '') {
                $actualizacionesCliente['email'] = trim($email);
            }
            
            // Ciudad (tabla clientes no tiene columna dirección en el esquema actual)
            if (!empty($inputData['nueva_ciudad'])) {
                $actualizacionesCliente['ciudad'] = trim($inputData['nueva_ciudad']);
            }
            
            if (!empty($actualizacionesCliente)) {
                $resultado = $this->clienteModel->actualizarCliente((int)$clienteId, $actualizacionesCliente);
                if (!$resultado) {
                    throw new Exception('Error al actualizar la información del cliente');
                }
                error_log("Información del cliente actualizada: " . json_encode($actualizacionesCliente));
            }
            
            // Agregar teléfonos adicionales
            // El JavaScript envía 'telefonos' como array, pero también puede venir como 'telefonos_adicionales' desde FormData
            $telefonos = $inputData['telefonos'] ?? $inputData['telefonos_adicionales'] ?? [];
            
            $telefonosGuardados = 0;
            $telefonosError = 0;
            $telefonosDuplicados = 0;
            $telefonosNoGuardados = [];
            $todasColumnasOcupadas = false;
            
            if (!empty($telefonos) && is_array($telefonos)) {
                foreach ($telefonos as $telefono) {
                    $telefono = trim($telefono);
                    if (!empty($telefono)) {
                        $resultado = $this->agregarTelefonoAdicional($clienteId, $telefono);
                        
                        if (is_array($resultado)) {
                            if ($resultado['success']) {
                                $telefonosGuardados++;
                            } else {
                                if ($resultado['message'] === 'todas_ocupadas') {
                                    $todasColumnasOcupadas = true;
                                    $telefonosNoGuardados[] = $telefono;
                                    $telefonosError++;
                                } elseif ($resultado['message'] === 'duplicado') {
                                    $telefonosDuplicados++;
                                } else {
                                    $telefonosNoGuardados[] = $telefono;
                                    $telefonosError++;
                                }
                            }
                        } else {
                            // Fallback para compatibilidad con código antiguo
                            if ($resultado) {
                                $telefonosGuardados++;
                            } else {
                                $telefonosNoGuardados[] = $telefono;
                                $telefonosError++;
                            }
                        }
                    }
                }
                
                if ($telefonosGuardados > 0) {
                    error_log("Teléfonos guardados: $telefonosGuardados para cliente ID: $clienteId");
                }
                if ($telefonosError > 0) {
                    error_log("Advertencia: $telefonosError teléfonos no pudieron guardarse para cliente ID: $clienteId");
                }
                if ($telefonosDuplicados > 0) {
                    error_log("Advertencia: $telefonosDuplicados teléfonos duplicados detectados para cliente ID: $clienteId");
                }
            }
            
            $this->pdo->commit();
            
            // Construir mensaje de respuesta
            $mensaje = 'Información adicional guardada exitosamente';
            $mensajeDetallado = [];
            
            if ($telefonosGuardados > 0) {
                $mensajeDetallado[] = "$telefonosGuardados teléfono(s) agregado(s) correctamente";
            }
            
            if ($telefonosDuplicados > 0) {
                $mensajeDetallado[] = "$telefonosDuplicados teléfono(s) ya existían y no se agregaron";
            }
            
            if ($todasColumnasOcupadas) {
                $mensajeDetallado[] = "Todas las columnas de teléfono están ocupadas. Contacte al administrador para agregar más números.";
            } elseif ($telefonosError > 0 && !$todasColumnasOcupadas) {
                $mensajeDetallado[] = "$telefonosError teléfono(s) no pudieron guardarse";
            }
            
            if (!empty($mensajeDetallado)) {
                $mensaje = implode('. ', $mensajeDetallado);
            }
            
            echo json_encode([
                'success' => true,
                'message' => $mensaje,
                'telefonos_guardados' => $telefonosGuardados,
                'telefonos_error' => $telefonosError,
                'telefonos_duplicados' => $telefonosDuplicados,
                'todas_columnas_ocupadas' => $todasColumnasOcupadas,
                'telefonos_no_guardados' => $telefonosNoGuardados
            ], JSON_UNESCAPED_UNICODE);
            
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            
            error_log("Error en agregarInformacionCliente: " . $e->getMessage());
            
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
    }
    
    /**
     * Agregar teléfono adicional en clientes.tel1..tel10 (esquema emermedica_cobranza.sql).
     * Busca la primera columna vacía y no reemplaza números existentes.
     *
     * @param int $clienteId id_cliente
     * @param string $telefono Número de teléfono a agregar
     * @return array|false
     */
    private function agregarTelefonoAdicional($clienteId, $telefono) {
        $sql = "SELECT tel1, tel2, tel3, tel4, tel5, tel6, tel7, tel8, tel9, tel10
                FROM clientes WHERE id_cliente = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([(int)$clienteId]);
        $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$cliente) {
            error_log("Cliente no encontrado con ID: $clienteId");
            return false;
        }
        
        $telefono = trim($telefono);
        if ($telefono === '') {
            error_log("Teléfono vacío proporcionado para cliente ID: $clienteId");
            return false;
        }
        
        $columnasTelefono = ['tel1', 'tel2', 'tel3', 'tel4', 'tel5', 'tel6', 'tel7', 'tel8', 'tel9', 'tel10'];
        
        // Verificar si el teléfono ya existe en alguna columna (evitar duplicados)
        foreach ($columnasTelefono as $columna) {
            // Verificar si la columna existe en el array y tiene un valor no vacío
            $valorColumna = isset($cliente[$columna]) ? trim((string)$cliente[$columna]) : '';
            if (!empty($valorColumna) && $valorColumna === $telefono) {
                error_log("El teléfono $telefono ya existe en la columna $columna para cliente ID: $clienteId");
                return ['success' => false, 'message' => 'duplicado', 'columna' => $columna];
            }
        }
        
        // Función helper para verificar si una columna está vacía (NULL, vacío, o solo espacios)
        $columnaEstaVacia = function($columna) use ($cliente) {
            // Verificar si la clave existe en el array
            if (!array_key_exists($columna, $cliente)) {
                return true; // Si no existe la clave, considerarla vacía
            }
            
            $valor = $cliente[$columna];
            
            // Si es NULL, está vacía
            if ($valor === null) {
                return true;
            }
            
            // Convertir a string y verificar si está vacío o solo espacios
            $valorTrim = trim((string)$valor);
            return empty($valorTrim);
        };
        
        // Buscar la primera columna vacía (no reemplazar números existentes)
        $columnaVacia = null;
        foreach ($columnasTelefono as $columna) {
            if ($columnaEstaVacia($columna)) {
                $columnaVacia = $columna;
                error_log("Columna vacía encontrada: $columna para cliente ID: $clienteId");
                break;
            }
        }
        
        // Si no hay columnas vacías, todas están ocupadas
        if (!$columnaVacia) {
            error_log("Todas las columnas de teléfono están ocupadas para cliente ID: $clienteId");
            // Log detallado de las columnas ocupadas
            foreach ($columnasTelefono as $col) {
                $valor = isset($cliente[$col]) ? $cliente[$col] : 'NO EXISTE';
                error_log("  - $col: " . ($valor === null ? 'NULL' : $valor));
            }
            return ['success' => false, 'message' => 'todas_ocupadas'];
        }
        
        // Actualizar la columna vacía con el nuevo teléfono
        $sql = "UPDATE clientes SET $columnaVacia = ? WHERE id_cliente = ?";
        $stmt = $this->pdo->prepare($sql);
        $resultado = $stmt->execute([$telefono, $clienteId]);
        
        if ($resultado) {
            error_log("Teléfono agregado exitosamente en columna $columnaVacia para cliente ID: $clienteId");
            return ['success' => true, 'columna' => $columnaVacia];
        } else {
            $errorInfo = $stmt->errorInfo();
            error_log("Error al agregar teléfono en columna $columnaVacia para cliente ID: $clienteId - " . print_r($errorInfo, true));
            return ['success' => false, 'message' => 'error_actualizacion', 'error' => $errorInfo[2] ?? 'Error desconocido'];
        }
    }
    
    /**
     * Registrar observaciones adicionales como una gestión
     */
    private function registrarObservacionesAdicionales($clienteId, $asesorId, $observaciones) {
        // En el esquema nuevo no existe `asignaciones_clientes`.
        // Registramos una gestión simple contra la primera obligación del cliente (si existe).
        try {
            $stmt = $this->pdo->prepare("SELECT id_obligacion FROM obligaciones WHERE cliente_id = ? ORDER BY id_obligacion ASC LIMIT 1");
            $stmt->execute([(int)$clienteId]);
            $obligacionId = (int)($stmt->fetch(PDO::FETCH_ASSOC)['id_obligacion'] ?? 0);
            if ($obligacionId <= 0) {
                return;
            }

            $this->gestionModel->crearGestion([
                'asesor_cedula' => (string)$asesorId,
                'cliente_id' => (int)$clienteId,
                'obligacion_id' => $obligacionId,
                'tipo_contacto' => 'informacion_adicional',
                'resultado_contacto' => 'INFORMACIÓN ADICIONAL',
                'razon_especifica' => '',
                'observaciones' => 'INFORMACIÓN ADICIONAL AGREGADA: ' . (string)$observaciones,
                'telefono_contacto' => '',
                'forma_contacto' => ''
            ]);
        } catch (Exception $e) {
            error_log("Error registrando observaciones adicionales: " . $e->getMessage());
        }
    }

    /**
     * Registra un break o descanso del asesor
     */
    public function registrarBreak() {
        $this->limpiarOutputBuffers();
        $this->configurarHeadersJSON();

        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->enviarJSONError('Método no permitido', 'METHOD_NOT_ALLOWED', 405);
                return;
            }

            $asesorId = $_SESSION['user_id'] ?? null;
            if (!$asesorId) {
                $this->enviarJSONError('Usuario no autenticado', 'UNAUTHORIZED', 401);
                return;
            }

            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input || !isset($input['tipo']) || !isset($input['accion'])) {
                $this->enviarJSONError('Datos incompletos', 'INVALID_DATA', 400);
                return;
            }

            $tipo = $input['tipo'];
            $accion = $input['accion']; // 'iniciar' o 'finalizar'

            // #region agent log
            @file_put_contents(__DIR__ . '/../debug-d200d9.log', json_encode(['sessionId' => 'd200d9', 'hypothesisId' => 'H4', 'location' => 'AsesorController::registrarBreak:parsed', 'message' => 'input_ok', 'data' => ['tipo' => $tipo, 'accion' => $accion, 'asesor_len' => strlen((string)$asesorId)], 'timestamp' => (int) round(microtime(true) * 1000)]) . "\n", FILE_APPEND | LOCK_EX);
            // #endregion

            $tiposPermitidos = ['baño', 'almuerzo', 'break', 'mantenimiento', 'actividad_extra', 'pausa_activa'];
            if (!in_array($tipo, $tiposPermitidos)) {
                $this->enviarJSONError('Tipo de break no válido', 'INVALID_TYPE', 400);
                return;
            }

            if ($accion === 'iniciar') {
                $breakActivo = $this->verificarBreakActivo($asesorId);
                if ($breakActivo) {
                    $this->enviarJSONError('Ya tienes un descanso activo. Finaliza el descanso actual antes de iniciar uno nuevo.', 'BREAK_ACTIVE', 400);
                    return;
                }

                // Registrar inicio de break
                $resultado = $this->registrarInicioBreak($asesorId, $tipo);

                if ($resultado) {
                    $this->enviarJSONExito([
                        'message' => 'Descanso iniciado correctamente',
                        'tipo' => $tipo,
                        'fecha_inicio' => date('Y-m-d H:i:s')
                    ]);
                } else {
                    // #region agent log
                    @file_put_contents(__DIR__ . '/../debug-d200d9.log', json_encode(['sessionId' => 'd200d9', 'hypothesisId' => 'H3', 'location' => 'AsesorController::registrarBreak:iniciar_false', 'message' => 'registrarInicioBreak returned false', 'data' => ['tipo' => $tipo], 'timestamp' => (int) round(microtime(true) * 1000)]) . "\n", FILE_APPEND | LOCK_EX);
                    // #endregion
                    $this->enviarJSONError('Error al registrar el descanso', 'DB_ERROR', 500);
                }

            } elseif ($accion === 'finalizar') {
                $breakActivo = $this->verificarBreakActivo($asesorId);
                if (!$breakActivo || $breakActivo['tipo'] !== $tipo) {
                    $this->enviarJSONError('No hay un descanso activo de este tipo para finalizar', 'NO_BREAK_ACTIVE', 400);
                    return;
                }

                // Registrar fin de break
                $resultado = $this->registrarFinBreak($asesorId, $tipo, $breakActivo['id']);

                if ($resultado) {
                    $this->enviarJSONExito([
                        'message' => 'Descanso finalizado correctamente',
                        'tipo' => $tipo,
                        'fecha_fin' => date('Y-m-d H:i:s'),
                        'duracion' => $resultado['duracion'] ?? 0
                    ]);
                } else {
                    $this->enviarJSONError('Error al finalizar el descanso', 'DB_ERROR', 500);
                }

            } else {
                $this->enviarJSONError('Acción no válida', 'INVALID_ACTION', 400);
            }

        } catch (Exception $e) {
            // #region agent log
            @file_put_contents(__DIR__ . '/../debug-d200d9.log', json_encode(['sessionId' => 'd200d9', 'hypothesisId' => 'H5', 'location' => 'AsesorController::registrarBreak:outer_catch', 'message' => $e->getMessage(), 'data' => ['class' => get_class($e)], 'timestamp' => (int) round(microtime(true) * 1000)]) . "\n", FILE_APPEND | LOCK_EX);
            // #endregion
            error_log("Error en registrarBreak: " . $e->getMessage());
            $this->enviarJSONError('Error al procesar la solicitud: ' . $e->getMessage(), 'GENERAL_ERROR', 500);
        }
    }

    /**
     * Mapea el tipo del UI al enum `tipo_registro` de la tabla `tiempos` (emermedica_cobranza.sql).
     */
    private function mapTipoBreakUiATipoRegistro(string $tipoUi): string {
        $map = [
            'baño' => 'baño',
            'almuerzo' => 'almuerzo',
            'break' => 'break',
            'mantenimiento' => 'capacitacion',
            'actividad_extra' => 'retroalimentacion',
            'pausa_activa' => 'sesion',
        ];
        return $map[$tipoUi] ?? 'break';
    }

    /**
     * Revierte `tipo_registro` hacia el código que usa el dashboard / JS.
     */
    private function mapTipoRegistroATipoUi(string $tipoReg): string {
        $map = [
            'capacitacion' => 'mantenimiento',
            'retroalimentacion' => 'actividad_extra',
            'sesion' => 'pausa_activa',
        ];
        return $map[$tipoReg] ?? $tipoReg;
    }

    /**
     * Verifica si hay un break activo para el asesor (tabla `tiempos`).
     */
    private function verificarBreakActivo($asesorId) {
        try {
            $sql = "SELECT id_tiempo, tipo_registro, hora_inicio, estado
                    FROM tiempos
                    WHERE asesor_cedula = ?
                      AND estado = 'activa'
                    ORDER BY id_tiempo DESC
                    LIMIT 1";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([(string)$asesorId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                return false;
            }

            return [
                'id' => $row['id_tiempo'],
                'tipo' => $this->mapTipoRegistroATipoUi((string)$row['tipo_registro']),
                'fecha_inicio' => $row['hora_inicio'],
                'estado' => $row['estado'],
            ];
        } catch (Exception $e) {
            // #region agent log
            @file_put_contents(__DIR__ . '/../debug-d200d9.log', json_encode(['sessionId' => 'd200d9', 'hypothesisId' => 'H1', 'location' => 'AsesorController::verificarBreakActivo:catch', 'message' => $e->getMessage(), 'data' => ['class' => get_class($e)], 'timestamp' => (int) round(microtime(true) * 1000)]) . "\n", FILE_APPEND | LOCK_EX);
            // #endregion
            error_log("Error en verificarBreakActivo: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Registra el inicio de un break en `tiempos`.
     */
    private function registrarInicioBreak($asesorId, $tipo) {
        try {
            $tipoReg = $this->mapTipoBreakUiATipoRegistro((string)$tipo);
            $sql = "INSERT INTO tiempos (asesor_cedula, tipo_registro, hora_inicio, estado)
                    VALUES (?, ?, NOW(), 'activa')";
            $stmt = $this->pdo->prepare($sql);
            $ok = $stmt->execute([(string)$asesorId, $tipoReg]);
            if ($ok) {
                // #region agent log
                @file_put_contents(__DIR__ . '/../debug-d200d9.log', json_encode(['sessionId' => 'd200d9', 'runId' => 'post-fix', 'hypothesisId' => 'FIX', 'location' => 'AsesorController::registrarInicioBreak:ok', 'message' => 'tiempos_insert_ok', 'data' => ['id_tiempo' => (int)$this->pdo->lastInsertId(), 'tipo_registro' => $tipoReg], 'timestamp' => (int) round(microtime(true) * 1000)]) . "\n", FILE_APPEND | LOCK_EX);
                // #endregion
            }
            return $ok;
        } catch (Exception $e) {
            // #region agent log
            @file_put_contents(__DIR__ . '/../debug-d200d9.log', json_encode(['sessionId' => 'd200d9', 'hypothesisId' => 'H3', 'location' => 'AsesorController::registrarInicioBreak:catch', 'message' => $e->getMessage(), 'data' => ['class' => get_class($e)], 'timestamp' => (int) round(microtime(true) * 1000)]) . "\n", FILE_APPEND | LOCK_EX);
            // #endregion
            error_log("Error en registrarInicioBreak: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Registra el fin de un break en `tiempos`.
     */
    private function registrarFinBreak($asesorId, $tipo, $breakId) {
        try {
            $sql = "UPDATE tiempos
                    SET hora_fin = NOW(), estado = 'finalizada'
                    WHERE id_tiempo = ?
                      AND asesor_cedula = ?
                      AND estado = 'activa'";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([(int)$breakId, (string)$asesorId]);

            $chk = $this->pdo->prepare("SELECT hora_inicio, hora_fin, estado FROM tiempos WHERE id_tiempo = ? AND asesor_cedula = ? LIMIT 1");
            $chk->execute([(int)$breakId, (string)$asesorId]);
            $row = $chk->fetch(PDO::FETCH_ASSOC);
            if (!$row || ($row['estado'] ?? '') !== 'finalizada' || empty($row['hora_fin'])) {
                return false;
            }

            $sd = $this->pdo->prepare("SELECT TIMESTAMPDIFF(SECOND, hora_inicio, hora_fin) AS sec FROM tiempos WHERE id_tiempo = ?");
            $sd->execute([(int)$breakId]);
            $sec = (int)($sd->fetch(PDO::FETCH_ASSOC)['sec'] ?? 0);

            return [
                'success' => true,
                'duracion' => $sec > 0 ? $sec / 60.0 : 0,
                'duracion_segundos' => $sec,
            ];
        } catch (Exception $e) {
            // #region agent log
            @file_put_contents(__DIR__ . '/../debug-d200d9.log', json_encode(['sessionId' => 'd200d9', 'hypothesisId' => 'H3', 'location' => 'AsesorController::registrarFinBreak:catch', 'message' => $e->getMessage(), 'data' => ['class' => get_class($e)], 'timestamp' => (int) round(microtime(true) * 1000)]) . "\n", FILE_APPEND | LOCK_EX);
            // #endregion
            error_log("Error en registrarFinBreak: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene el break activo del asesor (si existe)
     */
    public function obtenerBreakActivo() {
        $this->limpiarOutputBuffers();
        $this->configurarHeadersJSON();

        try {
            $asesorId = $_SESSION['user_id'] ?? null;
            if (!$asesorId) {
                $this->enviarJSONError('Usuario no autenticado', 'UNAUTHORIZED', 401);
                return;
            }

            $breakActivo = $this->verificarBreakActivo($asesorId);
            
            if ($breakActivo) {
                // Mapear tipos de break a nombres legibles
                $tiposBreak = [
                    'baño' => 'Baño',
                    'almuerzo' => 'Almuerzo',
                    'break' => 'Break',
                    'mantenimiento' => 'Mantenimiento',
                    'actividad_extra' => 'Actividad Extra',
                    'pausa_activa' => 'Pausa Activa'
                ];
                
                $this->enviarJSONExito([
                    'break_activo' => true,
                    'tipo' => $breakActivo['tipo'],
                    'tipo_nombre' => $tiposBreak[$breakActivo['tipo']] ?? $breakActivo['tipo'],
                    'fecha_inicio' => $breakActivo['fecha_inicio'],
                    'id' => $breakActivo['id']
                ]);
            } else {
                $this->enviarJSONExito([
                    'break_activo' => false
                ]);
            }

        } catch (Exception $e) {
            error_log("Error en obtenerBreakActivo: " . $e->getMessage());
            $this->enviarJSONError('Error al verificar el break activo: ' . $e->getMessage(), 'GENERAL_ERROR', 500);
        }
    }

    /**
     * Verifica la contraseña para desbloquear la pantalla
     */
    public function verificarContrasenaDesbloqueo() {
        $this->limpiarOutputBuffers();
        $this->configurarHeadersJSON();

        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->enviarJSONError('Método no permitido', 'METHOD_NOT_ALLOWED', 405);
                return;
            }

            $asesorId = $_SESSION['user_id'] ?? null;
            if (!$asesorId) {
                $this->enviarJSONError('Usuario no autenticado', 'UNAUTHORIZED', 401);
                return;
            }

            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input || !isset($input['contrasena'])) {
                $this->enviarJSONError('Contraseña requerida', 'INVALID_DATA', 400);
                return;
            }

            $contrasena = trim($input['contrasena']);
            if (empty($contrasena)) {
                $this->enviarJSONError('La contraseña no puede estar vacía', 'INVALID_DATA', 400);
                return;
            }

            // Verificar contraseña
            $esValida = $this->usuarioModel->verificarContrasena($asesorId, $contrasena);

            if ($esValida) {
                $this->enviarJSONExito([
                    'message' => 'Contraseña correcta',
                    'desbloqueado' => true
                ]);
            } else {
                $this->enviarJSONError('Contraseña incorrecta', 'INVALID_PASSWORD', 401);
            }

        } catch (Exception $e) {
            error_log("Error en verificarContrasenaDesbloqueo: " . $e->getMessage());
            $this->enviarJSONError('Error al verificar la contraseña: ' . $e->getMessage(), 'GENERAL_ERROR', 500);
        }
    }
}

