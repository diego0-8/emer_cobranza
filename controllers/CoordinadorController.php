<?php 
// Archivo: CoordinadorController.php
// Lógica para el coordinador

require_once __DIR__ . '/BaseController.php';

class CoordinadorController extends BaseController {
    public function __construct($pdo) {
        parent::__construct($pdo);
    }

    public function dashboard() {
        // #region agent log b7eaa7 coord dashboard entry
        try { @file_put_contents(__DIR__ . '/../debug-b7eaa7.log', json_encode(['sessionId'=>'b7eaa7','runId'=>'pre','hypothesisId'=>'H0','location'=>'controllers/CoordinadorController.php:dashboard:entry','message'=>'enter','data'=>['hasSessionUserId'=>isset($_SESSION['user_id'])?1:0,'userIdLen'=>strlen((string)($_SESSION['user_id']??'')),'periodo'=>(string)($_GET['periodo']??'total'),'hasFechaIni'=>isset($_GET['fecha_inicio'])?1:0,'hasFechaFin'=>isset($_GET['fecha_fin'])?1:0,'buscarLen'=>strlen((string)($_GET['buscar']??''))],'timestamp'=>(int) round(microtime(true)*1000)], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n", FILE_APPEND); } catch (Throwable $e) {}
        // #endregion

        $page_title = "Dashboard Coordinador";
        $coordinador_id = $_SESSION['user_id'];
        
        // Obtener filtros de fechas o período
        $fecha_inicio = $this->getGet('fecha_inicio');
        $fecha_fin = $this->getGet('fecha_fin');
        $periodo = $this->getGet('periodo', 'total'); // Usar 'total' por defecto para mostrar todas las gestiones
        
        // Si hay fechas específicas, usar esas; si no, usar el período
        if ($fecha_inicio && $fecha_fin) {
            // Usar fechas específicas para las métricas
            $metricas_equipo = $this->gestionModel->getMetricasEquipoConFechas($coordinador_id, $fecha_inicio, $fecha_fin);
        } else {
            // Usar período predefinido
            $metricas_equipo = $this->gestionModel->getMetricasEquipo($coordinador_id, $periodo);
        }
        
        // Obtener asesores asignados al coordinador
        $asesores = $this->usuarioModel->getAsesoresByCoordinador($coordinador_id);

        // #region agent log b7eaa7 asesores source count
        try {
            @file_put_contents(__DIR__ . '/../debug-b7eaa7.log', json_encode([
                'sessionId'=>'b7eaa7','runId'=>'pre','hypothesisId'=>'H1',
                'location'=>'controllers/CoordinadorController.php:dashboard:asesores',
                'message'=>'getAsesoresByCoordinador',
                'data'=>[
                    'count'=>is_array($asesores)?count($asesores):-1,
                    'firstKeys'=>(is_array($asesores)&&isset($asesores[0])&&is_array($asesores[0]))?array_slice(array_keys($asesores[0]),0,20):[],
                    'firstIdLen'=>(is_array($asesores)&&isset($asesores[0]['id']))?strlen((string)$asesores[0]['id']):null,
                ],
                'timestamp'=>(int) round(microtime(true)*1000)
            ], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n", FILE_APPEND);
        } catch (Throwable $e) {}
        // #endregion
        
        // Filtrar por término de búsqueda si se proporciona
        $terminoBusqueda = $this->getGet('buscar');
        if (!empty($terminoBusqueda)) {
            $asesores = array_filter($asesores, function($asesor) use ($terminoBusqueda) {
                return stripos($asesor['nombre_completo'], $terminoBusqueda) !== false ||
                       stripos($asesor['usuario'], $terminoBusqueda) !== false;
            });
        }

        // #region agent log b7eaa7 asesores after search filter
        try { @file_put_contents(__DIR__ . '/../debug-b7eaa7.log', json_encode(['sessionId'=>'b7eaa7','runId'=>'pre','hypothesisId'=>'H2','location'=>'controllers/CoordinadorController.php:dashboard:asesores','message'=>'after_search_filter','data'=>['buscarProvided'=>!empty($terminoBusqueda)?1:0,'count'=>is_array($asesores)?count($asesores):-1],'timestamp'=>(int) round(microtime(true)*1000)], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n", FILE_APPEND); } catch (Throwable $e) {}
        // #endregion
        
        // Calcular métricas para cada asesor usando el nuevo método
        foreach ($asesores as $key => $asesor) {
            try {
                // Inicializar variables por defecto
                $asesores[$key]['tareas_pendientes'] = 0;
                $asesores[$key]['gestiones_hoy'] = 0;
                $asesores[$key]['contactos_efectivos_hoy'] = 0;
                $asesores[$key]['acuerdos_hoy'] = 0;
                $asesores[$key]['clientes_pendientes_tareas'] = 0;
                
                // Si hay fechas específicas, usar métricas con fechas; si no, usar período
                if ($fecha_inicio && $fecha_fin) {
                    $asesores[$key]['metricas'] = $this->gestionModel->getMetricasAsesor($asesor['id'], $periodo, $fecha_inicio, $fecha_fin);
                } else {
                    $asesores[$key]['metricas'] = $this->gestionModel->getMetricasAsesor($asesor['id'], $periodo);
                }

                // #region agent log b7eaa7 metricas per-asesor sample (solo primeros 3)
                if ((int)$key < 3) {
                    try {
                        $m = $asesores[$key]['metricas'] ?? null;
                        @file_put_contents(__DIR__ . '/../debug-b7eaa7.log', json_encode([
                            'sessionId'=>'b7eaa7','runId'=>'pre','hypothesisId'=>'H3',
                            'location'=>'controllers/CoordinadorController.php:dashboard:loop',
                            'message'=>'metricas_sample',
                            'data'=>[
                                'idx'=>(int)$key,
                                'asesorIdLen'=>strlen((string)($asesor['id']??'')),
                                'metricasType'=>is_array($m)?'array':gettype($m),
                                'metricasKeys'=>is_array($m)?array_slice(array_keys($m),0,20):[],
                                'total_clientes'=>is_array($m)?(int)($m['total_clientes']??-1):null,
                                'total_gestiones'=>is_array($m)?(int)($m['total_gestiones']??-1):null,
                                'ventas_exitosas'=>is_array($m)?(int)($m['ventas_exitosas']??-1):null,
                            ],
                            'timestamp'=>(int) round(microtime(true)*1000)
                        ], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n", FILE_APPEND);
                    } catch (Throwable $e) {}
                }
                // #endregion
                
                // Obtener información de tareas pendientes
                $tareasPendientes = $this->tareaModel->getTareasPendientesByAsesor($asesor['id']);
                $asesores[$key]['tareas_pendientes'] = count($tareasPendientes);
                
                // Calcular clientes pendientes de tareas
                $clientesPendientesTareas = 0;
                foreach ($tareasPendientes as $tarea) {
                    $clientesPendientesTareas += count($tarea['cliente_ids']);
                }
                $asesores[$key]['clientes_pendientes_tareas'] = $clientesPendientesTareas;

                // Resumen de actividad HOY (siempre, tenga o no tareas) desde historial_gestiones (reglas solicitadas)
                $hoy = $this->gestionModel->getResumenActividadHoyAsesor($asesor['id']);
                $asesores[$key]['gestiones_hoy'] = (int)($hoy['gestiones_hoy'] ?? 0);
                $asesores[$key]['contactos_efectivos_hoy'] = (int)($hoy['contactos_efectivos_hoy'] ?? 0);
                $asesores[$key]['acuerdos_hoy'] = (int)($hoy['acuerdos_hoy'] ?? 0);

                // #region agent log b7eaa7 actividad hoy sample (solo primeros 3)
                if ((int)$key < 3) {
                    try {
                        @file_put_contents(__DIR__ . '/../debug-b7eaa7.log', json_encode([
                            'sessionId' => 'b7eaa7',
                            'runId' => 'pre',
                            'hypothesisId' => 'H6',
                            'location' => 'controllers/CoordinadorController.php:dashboard:loop',
                            'message' => 'actividad_hoy',
                            'data' => [
                                'idx' => (int)$key,
                                'gestiones_hoy' => (int)$asesores[$key]['gestiones_hoy'],
                                'contactos_efectivos_hoy' => (int)$asesores[$key]['contactos_efectivos_hoy'],
                                'acuerdos_hoy' => (int)$asesores[$key]['acuerdos_hoy'],
                                'tareas_pendientes' => (int)$asesores[$key]['tareas_pendientes'],
                            ],
                            'timestamp' => (int) round(microtime(true) * 1000),
                        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n", FILE_APPEND);
                    } catch (Throwable $e) {}
                }
                // #endregion
                
                // LÓGICA CORREGIDA SEGÚN REQUERIMIENTOS:
                // 1. Total de clientes: Si tiene tareas, mostrar clientes en tareas; si no, mostrar clientes gestionados
                if ($asesores[$key]['tareas_pendientes'] > 0) {
                    // Si tiene tareas, usar clientes de las tareas
                    $asesores[$key]['total_clientes'] = $clientesPendientesTareas;
                } else {
                    // Si no tiene tareas, usar clientes gestionados (que tienen historial de gestiones)
                    $asesores[$key]['total_clientes'] = $asesores[$key]['metricas']['total_clientes'] ?? 0;
                }
                
                // 2. Gestiones: Número de gestiones realizadas en tareas o gestiones generales
                if ($asesores[$key]['tareas_pendientes'] > 0) {
                    // Si tiene tareas, contar gestiones de los clientes en tareas
                    $gestionesEnTareas = 0;
                    foreach ($tareasPendientes as $tarea) {
                        foreach ($tarea['cliente_ids'] as $clienteId) {
                            // Obtener gestiones de este cliente en las tareas
                            $gestionesCliente = $this->gestionModel->getGestionesClienteEnTarea($clienteId, $asesor['id'], $fecha_inicio, $fecha_fin);
                            $gestionesEnTareas += count($gestionesCliente);
                        }
                    }
                    $asesores[$key]['llamadas_realizadas'] = $gestionesEnTareas;
                } else {
                    // Si no tiene tareas, usar gestiones generales
                    $asesores[$key]['llamadas_realizadas'] = $asesores[$key]['metricas']['total_gestiones'] ?? 0;
                }
                
                // 3. Contactos efectivos: Solo contactos tipificados como exitosos
                $asesores[$key]['contactos_efectivos'] = $asesores[$key]['metricas']['contactos_efectivos'] ?? 0;
                
                // Verificar que las métricas se obtuvieron correctamente
                if ($asesores[$key]['metricas'] && is_array($asesores[$key]['metricas'])) {
                    // Mantener compatibilidad con el código existente
                    $asesores[$key]['ventas_realizadas'] = $asesores[$key]['metricas']['ventas_exitosas'] ?? 0;
                    
                    // Calcular porcentaje de llamadas
                    if ($asesores[$key]['total_clientes'] > 0) {
                        $asesores[$key]['porcentaje_llamadas'] = round(($asesores[$key]['llamadas_realizadas'] / $asesores[$key]['total_clientes']) * 100, 1);
                    } else {
                        $asesores[$key]['porcentaje_llamadas'] = 0;
                    }
                } else {
                    // Si no se pudieron obtener métricas, establecer valores por defecto
                    $asesores[$key]['metricas'] = [
                        'total_clientes' => 0,
                        'total_gestiones' => 0,
                        'ventas_exitosas' => 0,
                        'tasa_conversion' => 0,
                        'tasa_contacto_efectivo' => 0,
                        'tiempo_promedio_conversacion' => 0,
                        'total_ventas_monto' => 0,
                        'promedio_venta' => 0
                    ];
                    
                    $asesores[$key]['total_clientes'] = 0;
                    $asesores[$key]['llamadas_realizadas'] = 0;
                    $asesores[$key]['ventas_realizadas'] = 0;
                    $asesores[$key]['porcentaje_llamadas'] = 0;
                    
                    // Log del error para debugging
                    error_log("No se pudieron obtener métricas para el asesor ID: " . $asesor['id'] . " - Nombre: " . $asesor['nombre_completo']);
                }
            } catch (Exception $e) {
                // En caso de error, establecer valores por defecto y log del error
                error_log("Error al obtener métricas del asesor ID: " . $asesor['id'] . " - Error: " . $e->getMessage());
                
                $asesores[$key]['metricas'] = [
                    'total_clientes' => 0,
                    'total_gestiones' => 0,
                    'ventas_exitosas' => 0,
                    'tasa_conversion' => 0,
                    'tasa_contacto_efectivo' => 0,
                    'tiempo_promedio_conversacion' => 0,
                    'total_ventas_monto' => 0,
                    'promedio_venta' => 0
                ];
                
                $asesores[$key]['total_clientes'] = 0;
                $asesores[$key]['llamadas_realizadas'] = 0;
                $asesores[$key]['ventas_realizadas'] = 0;
                $asesores[$key]['porcentaje_llamadas'] = 0;
            }
        }
        
        // Usar métricas del equipo para estadísticas generales (con defaults para evitar notices)
        if (!is_array($metricas_equipo)) {
            $metricas_equipo = [];
        }

        $total_asesores = (int)($metricas_equipo['total_asesores'] ?? count($asesores));
        $total_clientes = (int)($metricas_equipo['total_clientes'] ?? 0);
        $total_llamadas = (int)($metricas_equipo['total_gestiones'] ?? 0);
        $total_ventas = (int)($metricas_equipo['ventas_exitosas'] ?? 0);
        
        // Obtener recordatorios pendientes del equipo
        $llamadasPendientes = $this->gestionModel->getLlamadasPendientesCoordinador($coordinador_id);
        $totalLlamadasPendientesHoy = count($llamadasPendientes);

        // Obtener asesores disponibles para transferencias
        $asesoresDisponibles = $this->usuarioModel->getAsesoresByCoordinador($coordinador_id);

        // Datos adicionales para el dashboard
        $datos_dashboard = [
            'total_asesores' => $total_asesores,
            'total_clientes' => $total_clientes,
            'total_llamadas' => $total_llamadas,
            'total_ventas' => $total_ventas,
            'tasa_conversion' => (float)($metricas_equipo['tasa_conversion'] ?? 0),
            'tasa_contacto_efectivo' => (float)($metricas_equipo['tasa_contacto_efectivo'] ?? 0),
            'tiempo_promedio_conversacion' => (float)($metricas_equipo['tiempo_promedio_conversacion'] ?? 0),
            'total_ventas_monto' => (float)($metricas_equipo['total_ventas_monto'] ?? 0),
            'promedio_venta' => (float)($metricas_equipo['promedio_venta'] ?? 0),
            'periodo' => $periodo,
            'llamadas_pendientes' => $llamadasPendientes,
            'total_llamadas_pendientes_hoy' => $totalLlamadasPendientesHoy,
            'asesores_disponibles' => $asesoresDisponibles
        ];

        // #region agent log b7eaa7 before render table
        try {
            $countAs = is_array($asesores) ? count($asesores) : -1;
            $sumTotalClientes = 0;
            $sumLlamadas = 0;
            if (is_array($asesores)) {
                foreach ($asesores as $a) {
                    $sumTotalClientes += (int)($a['total_clientes'] ?? 0);
                    $sumLlamadas += (int)($a['llamadas_realizadas'] ?? 0);
                }
            }
            @file_put_contents(__DIR__ . '/../debug-b7eaa7.log', json_encode([
                'sessionId'=>'b7eaa7','runId'=>'pre','hypothesisId'=>'H4',
                'location'=>'controllers/CoordinadorController.php:dashboard:render',
                'message'=>'view_vars',
                'data'=>[
                    'asesoresCount'=>$countAs,
                    'sumTotalClientes'=>$sumTotalClientes,
                    'sumLlamadas'=>$sumLlamadas,
                    'total_asesores'=>(int)($total_asesores??-1),
                    'total_clientes'=>(int)($total_clientes??-1),
                    'total_llamadas'=>(int)($total_llamadas??-1),
                    'total_ventas'=>(int)($total_ventas??-1),
                ],
                'timestamp'=>(int) round(microtime(true)*1000)
            ], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n", FILE_APPEND);
        } catch (Throwable $e) {}
        // #endregion
        
        require __DIR__ . '/../views/coordinador_dashboard.php';
    }

    /**
     * Vista: Llamadas (call_log) para coordinador con filtros y paginación.
     * GET params: fecha_inicio, fecha_fin, asesor_cedula, hangup_by, telefono, page
     */
    public function coordCall() {
        $page_title = "Llamadas (Call Log)";
        $coordinadorId = (string)($_SESSION['user_id'] ?? '');

        // #region agent log 898d3b coordCall entry
        try { @file_put_contents(__DIR__ . '/../debug-898d3b.log', json_encode([
            'sessionId'=>'898d3b',
            'runId'=>'pre',
            'hypothesisId'=>'H4',
            'location'=>'controllers/CoordinadorController.php:coordCall:entry',
            'message'=>'enter',
            'data'=>[
                'hasUserId'=>($coordinadorId!==''?1:0),
                'userIdLen'=>strlen($coordinadorId),
                'role'=>(string)($_SESSION['user_role'] ?? ''),
                'qsKeys'=>array_slice(array_keys($_GET ?? []),0,20),
            ],
            'timestamp'=>(int) round(microtime(true)*1000),
        ], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n", FILE_APPEND); } catch (Throwable $e) {}
        // #endregion

        try {
            // Filtros simplificados:
            // - periodo: dia|semana|mes (por defecto: semana)
            // - telefono
            $periodo = $this->getGet('periodo', 'semana');
            $periodo = strtolower(trim((string)$periodo));
            if (!in_array($periodo, ['dia', 'semana', 'mes'], true)) {
                $periodo = 'semana';
            }

            $hoy = new DateTime();
            $fechaFin = $hoy->format('Y-m-d');
            if ($periodo === 'dia') {
                $fechaInicio = $hoy->format('Y-m-d');
            } elseif ($periodo === 'mes') {
                $fechaInicio = $hoy->format('Y-m-01');
            } else {
                $fechaInicio = (clone $hoy)->modify('-7 days')->format('Y-m-d');
            }

            // Nota: dejamos estos filtros vacíos para la UI nueva (solo periodo + telefono)
            $asesorCedula = '';
            $hangupBy = '';
            $telefono = $this->getGet('telefono', '');
            $page = (int)($this->getGet('page', 1) ?? 1);
            if ($page < 1) $page = 1;

            // #region agent log 898d3b coordCall filters
            try { @file_put_contents(__DIR__ . '/../debug-898d3b.log', json_encode([
                'sessionId'=>'898d3b',
                'runId'=>'pre',
                'hypothesisId'=>'H3',
                'location'=>'controllers/CoordinadorController.php:coordCall:filters',
                'message'=>'filters',
                'data'=>[
                    'fechaInicio'=>(string)$fechaInicio,
                    'fechaFin'=>(string)$fechaFin,
                    'asesorCedulaLen'=>strlen((string)$asesorCedula),
                    'hangupBy'=>(string)$hangupBy,
                    'telefonoLen'=>strlen((string)$telefono),
                    'page'=>(int)$page,
                ],
                'timestamp'=>(int) round(microtime(true)*1000),
            ], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n", FILE_APPEND); } catch (Throwable $e) {}
            // #endregion

            $asesores = $this->usuarioModel->getAsesoresByCoordinador($coordinadorId);

            // #region agent log 898d3b coordCall asesores count
            try { @file_put_contents(__DIR__ . '/../debug-898d3b.log', json_encode([
                'sessionId'=>'898d3b',
                'runId'=>'pre',
                'hypothesisId'=>'H4',
                'location'=>'controllers/CoordinadorController.php:coordCall:asesores',
                'message'=>'getAsesoresByCoordinador',
                'data'=>[
                    'count'=>is_array($asesores)?count($asesores):-1,
                ],
                'timestamp'=>(int) round(microtime(true)*1000),
            ], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n", FILE_APPEND); } catch (Throwable $e) {}
            // #endregion

            $result = $this->callLogModel->listarPorCoordinador([
                'coordinador_cedula' => $coordinadorId,
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
                'asesor_cedula' => $asesorCedula,
                'hangup_by' => $hangupBy,
                'telefono' => $telefono,
                'page' => $page,
            ]);

            $total = (int)($result['total'] ?? 0);
            $limit = (int)($result['limit'] ?? 10);
            $page = (int)($result['page'] ?? $page);
            $totalPages = (int)ceil($total / max(1, $limit));
            if ($totalPages < 1) $totalPages = 1;

            $rows = (array)($result['rows'] ?? []);

            // #region agent log 898d3b coordCall result
            try { @file_put_contents(__DIR__ . '/../debug-898d3b.log', json_encode([
                'sessionId'=>'898d3b',
                'runId'=>'pre',
                'hypothesisId'=>'H2',
                'location'=>'controllers/CoordinadorController.php:coordCall:result',
                'message'=>'ok',
                'data'=>[
                    'total'=>(int)$total,
                    'rowsCount'=>is_array($rows)?count($rows):-1,
                    'totalPages'=>(int)$totalPages,
                ],
                'timestamp'=>(int) round(microtime(true)*1000),
            ], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n", FILE_APPEND); } catch (Throwable $e) {}
            // #endregion

            require __DIR__ . '/../views/coord_call.php';
        } catch (PDOException $e) {
            // Si la tabla call_log aún no existe, mostrar pantalla informativa en vez de 500.
            $msg = (string)$e->getMessage();
            if (strpos($msg, 'call_log') !== false && (strpos($msg, '42S02') !== false || strpos($msg, "doesn't exist") !== false)) {
                $callLogMissing = true;
                $callLogMissingMessage = "La tabla call_log no existe en la base de datos. Ejecuta la migración: sql/migrations/20260429_call_log.sql en emermedica_db.";
                $rows = [];
                $total = 0;
                $limit = 10;
                $totalPages = 1;

                // #region agent log 898d3b coordCall missing_table handled
                try { @file_put_contents(__DIR__ . '/../debug-898d3b.log', json_encode([
                    'sessionId'=>'898d3b',
                    'runId'=>'post-fix',
                    'hypothesisId'=>'H1',
                    'location'=>'controllers/CoordinadorController.php:coordCall:missing_table',
                    'message'=>'handled_missing_call_log',
                    'data'=>['shown'=>1],
                    'timestamp'=>(int) round(microtime(true)*1000),
                ], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n", FILE_APPEND); } catch (Throwable $ex) {}
                // #endregion

                require __DIR__ . '/../views/coord_call.php';
                return;
            }

            // #region agent log 898d3b coordCall exception
            try { @file_put_contents(__DIR__ . '/../debug-898d3b.log', json_encode([
                'sessionId'=>'898d3b',
                'runId'=>'post-fix',
                'hypothesisId'=>'H2',
                'location'=>'controllers/CoordinadorController.php:coordCall:exception',
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
        } catch (Throwable $e) {
            // #region agent log 898d3b coordCall exception
            try { @file_put_contents(__DIR__ . '/../debug-898d3b.log', json_encode([
                'sessionId'=>'898d3b',
                'runId'=>'post-fix',
                'hypothesisId'=>'H2',
                'location'=>'controllers/CoordinadorController.php:coordCall:exception',
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
    }

    /**
     * Devuelve HTML para modal de "Ver gestión" desde coord_call (por gestion_id).
     * GET: gestion_id
     */
    public function coordCallGestionModal() {
        $coordinadorId = (string)($_SESSION['user_id'] ?? '');
        $gestionId = (int)($this->getGet('gestion_id', 0) ?? 0);
        // #region agent log 058b8a coordCallGestionModal entry
        try { @file_put_contents(__DIR__ . '/../debug-058b8a.log', json_encode([
            'sessionId'=>'058b8a',
            'runId'=>'pre',
            'hypothesisId'=>'M1',
            'location'=>'controllers/CoordinadorController.php:coordCallGestionModal',
            'message'=>'entry',
            'data'=>[
                'gestionId'=>(int)$gestionId,
                'coordLen'=>strlen((string)$coordinadorId),
            ],
            'timestamp'=>(int) round(microtime(true)*1000),
        ], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n", FILE_APPEND); } catch (Throwable $e) {}
        // #endregion

        try {
            if ($gestionId <= 0) {
                http_response_code(400);
                echo "gestión inválida";
                return;
            }

            // Validar acceso: la gestión debe estar enlazada a una llamada del coordinador.
            $stmt = $this->pdo->prepare("
                SELECT cl.*
                FROM call_log cl
                WHERE cl.coordinador_cedula = ?
                  AND cl.gestion_id = ?
                ORDER BY cl.inicio DESC, cl.id_call DESC
                LIMIT 1
            ");
            $stmt->execute([$coordinadorId, $gestionId]);
            $call = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$call) {
                http_response_code(404);
                echo "No tienes acceso a esta gestión.";
                return;
            }

            $gestion = $this->gestionModel->getGestionById($gestionId);
            if (!$gestion) {
                http_response_code(404);
                echo "No se encontró la gestión.";
                return;
            }

            // Cliente (esquema real: clientes.id_cliente + base_clientes.creado_por)
            $cliente = null;
            $clienteId = (int)($call['cliente_id'] ?? 0);
            if ($clienteId > 0) {
                $stmtCli = $this->pdo->prepare("
                    SELECT c.*
                    FROM clientes c
                    JOIN base_clientes b ON b.id_base = c.base_id
                    WHERE c.id_cliente = ?
                      AND b.creado_por = ?
                    LIMIT 1
                ");
                $stmtCli->execute([(int)$clienteId, (string)$coordinadorId]);
                $cliente = $stmtCli->fetch(PDO::FETCH_ASSOC) ?: null;
            }

            // #region agent log 058b8a coordCallGestionModal ok
            try { @file_put_contents(__DIR__ . '/../debug-058b8a.log', json_encode([
                'sessionId'=>'058b8a',
                'runId'=>'pre',
                'hypothesisId'=>'M1',
                'location'=>'controllers/CoordinadorController.php:coordCallGestionModal',
                'message'=>'ok',
                'data'=>[
                    'hasCliente'=>$cliente?1:0,
                    'clienteId'=>(int)$clienteId,
                    'hasGestion'=>$gestion?1:0,
                ],
                'timestamp'=>(int) round(microtime(true)*1000),
            ], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n", FILE_APPEND); } catch (Throwable $e) {}
            // #endregion

            // Respuesta HTML liviana para modal (sin layout/nav).
            header('Content-Type: text/html; charset=UTF-8');
            require __DIR__ . '/../views/coord_call_gestion_modal.php';
        } catch (Throwable $e) {
            // #region agent log 058b8a coordCallGestionModal exception
            try { @file_put_contents(__DIR__ . '/../debug-058b8a.log', json_encode([
                'sessionId'=>'058b8a',
                'runId'=>'pre',
                'hypothesisId'=>'M1',
                'location'=>'controllers/CoordinadorController.php:coordCallGestionModal',
                'message'=>'exception',
                'data'=>[
                    'type'=>get_class($e),
                    'code'=>(int)$e->getCode(),
                    'msg'=>substr((string)$e->getMessage(),0,300),
                ],
                'timestamp'=>(int) round(microtime(true)*1000),
            ], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n", FILE_APPEND); } catch (Throwable $ex) {}
            // #endregion
            http_response_code(500);
            echo "server_error";
        }
    }
    
    public function listCargas() {
        $page_title = "Gestión de Bases de Datos";
        $agentLogPath = __DIR__ . '/../debug-a2fdce.log';

        try {
            $coordinador_id = $_SESSION['user_id'] ?? null;

            // #region agent log
            @file_put_contents($agentLogPath, json_encode([
                'sessionId' => 'a2fdce',
                'runId' => 'pre-fix',
                'hypothesisId' => 'C1',
                'location' => 'controllers/CoordinadorController.php:listCargas:entry',
                'message' => 'listCargas entry',
                'data' => [
                    'hasUserId' => $coordinador_id !== null && $coordinador_id !== '',
                    'userIdLen' => strlen((string)$coordinador_id),
                    'userRole' => (string)($_SESSION['user_role'] ?? ''),
                ],
                'timestamp' => (int) round(microtime(true) * 1000),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n", FILE_APPEND);
            // #endregion

            $cargas = $this->clienteModel->getCargasByCoordinador($coordinador_id, true); // Solo bases habilitadas

            // #region agent log
            @file_put_contents($agentLogPath, json_encode([
                'sessionId' => 'a2fdce',
                'runId' => 'pre-fix',
                'hypothesisId' => 'C1',
                'location' => 'controllers/CoordinadorController.php:listCargas:after_getCargas',
                'message' => 'getCargasByCoordinador result',
                'data' => [
                    'count' => is_array($cargas) ? count($cargas) : null,
                    'type' => gettype($cargas),
                ],
                'timestamp' => (int) round(microtime(true) * 1000),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n", FILE_APPEND);
            // #endregion

            // Obtener información del coordinador
            $coordinador = $this->usuarioModel->getUsuarioById($coordinador_id);

            // #region agent log
            @file_put_contents($agentLogPath, json_encode([
                'sessionId' => 'a2fdce',
                'runId' => 'pre-fix',
                'hypothesisId' => 'C2',
                'location' => 'controllers/CoordinadorController.php:listCargas:after_getUsuario',
                'message' => 'getUsuarioById result',
                'data' => [
                    'found' => is_array($coordinador) && !empty($coordinador),
                    'hasNombre' => is_array($coordinador) && !empty($coordinador['nombre_completo']),
                ],
                'timestamp' => (int) round(microtime(true) * 1000),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n", FILE_APPEND);
            // #endregion

            // Calcular estadísticas para cada carga
            $cargas_con_stats = [];
            foreach ((array)$cargas as $carga) {
                $cargaId = $carga['id'] ?? null;

                // #region agent log
                @file_put_contents($agentLogPath, json_encode([
                    'sessionId' => 'a2fdce',
                    'runId' => 'pre-fix',
                    'hypothesisId' => 'C3',
                    'location' => 'controllers/CoordinadorController.php:listCargas:loop',
                    'message' => 'processing carga',
                    'data' => [
                        'hasId' => $cargaId !== null && $cargaId !== '',
                        'idType' => gettype($cargaId),
                    ],
                    'timestamp' => (int) round(microtime(true) * 1000),
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n", FILE_APPEND);
                // #endregion

                $carga['total_clientes'] = $this->clienteModel->getTotalClientsByCargaIdAndCoordinador($cargaId, $coordinador_id);
                $carga['clientes_asignados'] = $this->clienteModel->getTotalClientsAsignadosByCargaIdAndCoordinador($cargaId, $coordinador_id);
                $carga['clientes_pendientes'] = $carga['total_clientes'] - $carga['clientes_asignados'];
                $carga['coordinador_nombre'] = $coordinador['nombre_completo'] ?? 'Coordinador';
                $cargas_con_stats[] = $carga;
            }
            $cargas = $cargas_con_stats;

            // #region agent log
            @file_put_contents($agentLogPath, json_encode([
                'sessionId' => 'a2fdce',
                'runId' => 'pre-fix',
                'hypothesisId' => 'C4',
                'location' => 'controllers/CoordinadorController.php:listCargas:before_view',
                'message' => 'about to require view',
                'data' => [
                    'cargasCount' => is_array($cargas) ? count($cargas) : null,
                ],
                'timestamp' => (int) round(microtime(true) * 1000),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n", FILE_APPEND);
            // #endregion

            require __DIR__ . '/../views/cargas_excel_list.php';
        } catch (Throwable $e) {
            // #region agent log
            @file_put_contents($agentLogPath, json_encode([
                'sessionId' => 'a2fdce',
                'runId' => 'pre-fix',
                'hypothesisId' => 'C5',
                'location' => 'controllers/CoordinadorController.php:listCargas:catch',
                'message' => 'listCargas exception',
                'data' => [
                    'type' => get_class($e),
                    'code' => (int) $e->getCode(),
                    'message' => substr((string)$e->getMessage(), 0, 300),
                    'file' => $e->getFile(),
                    'line' => (int) $e->getLine(),
                ],
                'timestamp' => (int) round(microtime(true) * 1000),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n", FILE_APPEND);
            // #endregion

            throw $e;
        }
    }

    public function gestionCargas() {
        $coordinador_id = $_SESSION['user_id'] ?? null;
        // Solo bases habilitadas del coordinador (estado = activo)
        $cargas = $this->cargaExcelModel->getCargasByCoordinador($coordinador_id, true);
        require __DIR__ . '/../views/gestion_cargas_integrada.php';
    }

    public function uploadExcel() {
        $page_title = "Subir Nuevo Archivo CSV";

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['archivo_excel'])) {
            $action = $this->getPost('action', 'consolidada');
            $usuarioCoordinadorId = $_SESSION['user_id'];
            
            // Determinar el nombre según la acción
            if ($action === 'consolidada') {
                $nombreCargue = 'BASE_DATOS_CONSOLIDADA';
            } else {
                $nombreCargue = $this->getPost('nombre_cargue', 'BASE_DATOS_CONSOLIDADA');
            }
            
            // Verificar el tamaño del archivo
            $fileSize = $_FILES['archivo_excel']['size'];
            $maxFileSize = 500 * 1024 * 1024; // 500MB para archivos CSV grandes
            
            if ($fileSize > $maxFileSize) {
                $_SESSION['error_message'] = "❌ Error en la carga: El archivo es demasiado grande. El tamaño máximo permitido es 500MB.";
                header('Location: index.php?action=gestion_cargas');
                exit;
            }
            
            // Verificar tipo de archivo
            $fileType = strtolower(pathinfo($_FILES['archivo_excel']['name'], PATHINFO_EXTENSION));
            if ($fileType !== 'csv') {
                $_SESSION['error_message'] = "❌ Error en la carga: Solo se permiten archivos CSV.";
                header('Location: index.php?action=gestion_cargas');
                exit;
            }
            
            // Usar el método leerArchivoCSV que procesa correctamente todos los campos de facturas
            $clientes = $this->leerArchivoCSV($_FILES['archivo_excel']['tmp_name']);
            
            if (empty($clientes)) {
                $_SESSION['error_message'] = "❌ Error en la carga: No se encontraron clientes válidos en el archivo CSV.";
                header('Location: index.php?action=gestion_cargas');
                exit;
            }
            
            // Siempre usar la misma carga consolidada para el coordinador
            $cargaExistente = $this->cargaExcelModel->getCargaConsolidada($usuarioCoordinadorId);
            
                        if ($cargaExistente) {
                // Usar carga existente
                $cargaId = $cargaExistente['id'];
                $esNuevaBase = false;
            } else {
                // Crear única carga consolidada
                $cargaId = $this->cargaExcelModel->crearCargaConsolidada($usuarioCoordinadorId);
                if (!$cargaId) {
                    $_SESSION['error_message'] = "❌ Error en la carga: No se pudo crear la base de datos consolidada.";
                    header('Location: index.php?action=gestion_cargas');
                    exit;
                }
                $esNuevaBase = true;
            }
            
            // Procesar clientes usando el método que maneja obligaciones (facturas)
            $resultado = $this->procesarClientesCSV($clientes, $cargaId, $usuarioCoordinadorId);
            
            $clientesNuevos = $resultado['nuevos'];
            $clientesDuplicados = $resultado['duplicados'];
            $obligacionesCreadas = $resultado['obligaciones_creadas'];
            $obligacionesDuplicadas = $resultado['obligaciones_duplicadas'];
            $clientesAgregados = $clientesNuevos + $clientesDuplicados;
            $errores = 0; // El método procesarClientesCSV maneja los errores internamente
            
            // Verificar si hubo errores críticos
            if ($errores > 0 && $clientesAgregados == 0) {
                $error_message = "❌ <strong>Error en la carga: No se pudo procesar el archivo correctamente.</strong><br><br>
                <strong>Detalles del error:</strong><br>
                📊 <strong>Total de filas procesadas:</strong> " . count($clientes) . "<br>
                ❌ <strong>Errores encontrados:</strong> {$errores}<br>
                ✅ <strong>Clientes agregados:</strong> {$clientesAgregados}<br><br>
                <strong>Posibles causas:</strong><br>
                ⚠️ Columnas obligatorias vacías (Nombre, Cédula, Teléfono)<br>
                ⚠️ Formato incorrecto del CSV<br>
                ⚠️ Datos malformados en las filas<br>
                ⚠️ Problemas de permisos en el servidor<br><br>
                <strong>Recomendaciones:</strong><br>
                ✅ Verifique que las columnas obligatorias tengan datos<br>
                ✅ Revise el formato del archivo CSV<br>
                ✅ Asegúrese de que no haya filas completamente vacías<br>
                ✅ Contacte al administrador si el problema persiste";
            } else {
                // Mostrar mensaje según el resultado
                if ($clientesNuevos > 0) {
                    // Hay contactos nuevos - mostrar éxito
                    $mensajeBase = $esNuevaBase ? "🏗️ ¡Base de datos creada exitosamente! Se agregaron nuevos contactos y facturas." : "✅ ¡Carga exitosa! Se agregaron nuevos contactos y facturas a la base de datos.";
                    $success_message = "
                        <strong>{$mensajeBase}</strong><br><br>
                        <strong>Resumen del archivo:</strong><br>
                        • <strong>Clientes nuevos agregados:</strong> {$clientesNuevos}<br>
                        • <strong>Clientes duplicados encontrados:</strong> {$clientesDuplicados}<br>
                        • <strong>Total clientes agregados:</strong> {$clientesAgregados}<br>
                        • <strong>Facturas creadas:</strong> {$obligacionesCreadas}<br>
                        • <strong>Facturas duplicadas:</strong> {$obligacionesDuplicadas}<br>
                        • <strong>Errores encontrados:</strong> {$errores}<br><br>
                        <strong>📊 Base de Datos Consolidada:</strong><br>
                        📊 <strong>Total de clientes en la base:</strong> " . $this->clienteModel->getTotalClientsByCargaId($cargaId) . "<br>
                        👥 <strong>Clientes únicos totales:</strong> " . $this->clienteModel->getTotalClientesUnicos() . "<br><br>
                        <em>ℹ️ " . ($esNuevaBase ? "Se creó una nueva base de datos consolidada." : "Todos los archivos CSV se consolidan en una sola base de datos para facilitar la gestión.") . "</em>
                    ";
                } elseif ($clientesAgregados > 0) {
                    // Solo se agregaron contactos existentes - mostrar información
                    $info_message = "
                        <strong>ℹ️ Archivo procesado correctamente</strong><br><br>
                        <strong>Resumen del archivo:</strong><br>
                        • <strong>Clientes nuevos agregados:</strong> {$clientesNuevos}<br>
                        • <strong>Clientes duplicados encontrados:</strong> {$clientesDuplicados}<br>
                        • <strong>Total clientes agregados:</strong> {$clientesAgregados}<br>
                        • <strong>Facturas creadas:</strong> {$obligacionesCreadas}<br>
                        • <strong>Facturas duplicadas:</strong> {$obligacionesDuplicadas}<br>
                        • <strong>Errores encontrados:</strong> {$errores}<br><br>
                        <strong>📊 Base de Datos Consolidada:</strong><br>
                        • <strong>Total de clientes en la base:</strong> " . $this->clienteModel->getTotalClientsByCargaId($cargaId) . "<br>
                        👥 <strong>Clientes únicos totales:</strong> " . $this->clienteModel->getTotalClientesUnicos() . "<br><br>
                        <em>💡 No se agregaron contactos nuevos porque todos ya estaban en la base de datos, pero se procesaron las facturas.</em>
                    ";
                } else {
                    // No se agregó ningún contacto - mostrar advertencia
                    $warning_message = "
                        <strong>⚠️ Archivo procesado pero no se agregaron contactos</strong><br><br>
                        <strong>Resumen del archivo:</strong><br>
                        📊 <strong>Total de filas procesadas:</strong> " . count($clientes) . "<br>
                        📊 <strong>Clientes nuevos agregados:</strong> {$clientesNuevos}<br>
                        🔄 <strong>Clientes duplicados encontrados:</strong> {$clientesDuplicados}<br>
                        👥 <strong>Total clientes agregados:</strong> {$clientesAgregados}<br>
                        ❌ <strong>Errores encontrados:</strong> {$errores}<br><br>
                        <em>ℹ️ Todos los contactos del archivo ya estaban en la base de datos.</em>
                    ";
                }
            }
            
            // Establecer mensajes de sesión según el resultado
            if (isset($success_message)) {
                $_SESSION['success_message'] = $success_message;
                $_SESSION['success_auto_hide'] = true; // Flag para auto-ocultar mensaje
            } elseif (isset($info_message)) {
                $_SESSION['info_message'] = $info_message;
                $_SESSION['success_auto_hide'] = true;
            } elseif (isset($warning_message)) {
                $_SESSION['warning_message'] = $warning_message;
                $_SESSION['success_auto_hide'] = true;
            } elseif (isset($error_message)) {
                $_SESSION['error_message'] = $error_message;
            }
            
            // Redirigir a la gestión de cargas
            header('Location: index.php?action=gestion_cargas');
            exit;
            
        } else {
            // Si no es POST, redirigir a la gestión integrada
            header('Location: index.php?action=gestion_cargas');
            exit;
        }
    }

    /**
     * Lee un archivo CSV y retorna un array de clientes
     * Optimizado para archivos grandes
     */
    private function leerArchivoCSV($archivo_path) {
        // Configurar límites para archivos grandes
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '2048M');
        
        $clientes = [];
        
        if (($handle = fopen($archivo_path, "r")) !== FALSE) {
            // Leer la primera línea (encabezados)
            // Detectar el delimitador automáticamente
            $primera_linea = fgets($handle);
            $delimitador = $this->detectarDelimitador($primera_linea);
            
            // Volver al inicio del archivo
            rewind($handle);
            
            // Leer la primera línea (encabezados)
            $encabezados = fgetcsv($handle, 0, $delimitador);
            
            // Mapear encabezados a índices
            $indices = $this->mapearEncabezadosCSV($encabezados);
            
            // Leer cada línea de datos en lotes
            $linea = 2; // Empezar en línea 2 (después de encabezados)
            $lote = 0;
            $tamañoLote = 1000;
            
            while (($data = fgetcsv($handle, 0, $delimitador)) !== FALSE) {
                if (count($data) >= 2) { // Mínimo: cedula y numero_factura (obligatorios en negocio)
                    $idx = function (string $k) use ($indices) {
                        return (int)($indices[$k] ?? -1);
                    };
                    $cell = function (array $row, int $i) {
                        return $i >= 0 ? trim((string)($row[$i] ?? '')) : '';
                    };
                    $telefonoCol = $cell($data, $idx('telefono'));
                    $tel1Col = $cell($data, $idx('tel1'));
                    $tel2Col = $cell($data, $idx('tel2'));
                    $telefonoPrincipal = $telefonoCol !== '' ? $telefonoCol : $tel1Col;
                    $diasMoraVal = 0;
                    if ($idx('dias_en_mora') >= 0) {
                        $diasMoraVal = (int)($data[$idx('dias_en_mora')] ?? 0);
                    } elseif ($idx('dias_mora') >= 0) {
                        $diasMoraVal = (int)($data[$idx('dias_mora')] ?? 0);
                    }
                    $cliente = [
                        // Campos obligatorios nuevos
                        'cedula' => $cell($data, $idx('cedula')),
                        // En el esquema nuevo, clientes.nombre es NOT NULL
                        'nombre' => $cell($data, $idx('nombre')) ?: 'SIN NOMBRE',
                        'numero_factura' => $cell($data, $idx('numero_factura')),
                        // Campos opcionales nuevos
                        'rmt' => $cell($data, $idx('rmt')),
                        'telefono' => $telefonoPrincipal,
                        'numero_contrato' => $cell($data, $idx('numero_contrato')),
                        'saldo' => $this->limpiarNumero($idx('saldo') >= 0 ? ($data[$idx('saldo')] ?? '') : ''),
                        'dias_mora' => $diasMoraVal,
                        'franja' => $cell($data, $idx('franja')),
                        'telefono2' => $cell($data, $idx('telefono2')) !== '' ? $cell($data, $idx('telefono2')) : $tel2Col,
                        'telefonos_3' => $cell($data, $idx('telefonos_3')),
                        // Campos legacy para compatibilidad
                        'obligacion' => $cell($data, $idx('obligacion')),
                        'saldo_k_obligacion' => $this->limpiarNumero($idx('saldo_k_obligacion') >= 0 ? ($data[$idx('saldo_k_obligacion')] ?? '') : ''),
                        'capital_cliente' => $this->limpiarNumero($idx('capital_cliente') >= 0 ? ($data[$idx('capital_cliente')] ?? '') : ''),
                        'pago_total_obligacion' => $this->limpiarNumero($idx('pago_total_obligacion') >= 0 ? ($data[$idx('pago_total_obligacion')] ?? '') : ''),
                        'mora_actual' => $idx('mora_actual') >= 0 ? (int)($data[$idx('mora_actual')] ?? 0) : 0,
                        'propiedad' => $cell($data, $idx('propiedad')),
                        'producto' => $cell($data, $idx('producto')),
                        'medicion' => $cell($data, $idx('medicion')),
                        'celular2' => $cell($data, $idx('celular2')),
                        'email' => $this->procesarEmail($cell($data, $idx('email'))),
                        'direccion' => $cell($data, $idx('direccion')),
                        'ciudad' => $cell($data, $idx('ciudad')),
                        'linea' => $linea
                    ];
                    // Columnas tel1..tel10 alineadas con tabla `clientes` (opcionales)
                    for ($ti = 1; $ti <= 10; $ti++) {
                        $k = 'tel' . $ti;
                        if ($idx($k) >= 0) {
                            $cliente['tel' . $ti] = $cell($data, $idx($k));
                        }
                    }
                    
                    // Solo agregar si tiene datos mínimos obligatorios: cédula + número de factura
                    // (teléfonos opcionales: crearCliente rellena vacíos; obligaciones usan defaults en FacturacionModel)
                    if ($cliente['cedula'] !== '' && $cliente['numero_factura'] !== '') {
                        $clientes[] = $cliente;
                    }
                }
                $linea++;
                
                // Procesar lote cuando alcance el tamaño
                if (count($clientes) >= $tamañoLote) {
                    $lote++;
                    error_log("Procesando lote de lectura $lote con " . count($clientes) . " clientes");
                    
                    // Liberar memoria periódicamente
                    if (function_exists('gc_collect_cycles')) {
                        gc_collect_cycles();
                    }
                }
            }
            fclose($handle);
            
            error_log("Lectura de CSV completada. Total de clientes leídos: " . count($clientes));
        }
        
        return $clientes;
    }

    /**
     * Mapea los encabezados del CSV a índices
     */
    private function mapearEncabezadosCSV($encabezados) {
        $indices = [
            'cedula' => -1,
            'nombre' => -1,
            'numero_factura' => -1,
            'rmt' => -1,
            'telefono' => -1,
            'numero_contrato' => -1,
            'saldo' => -1,
            // Compatibilidad: a veces el código usa dias_en_mora, otras dias_mora
            'dias_mora' => -1,
            'dias_en_mora' => -1,
            'franja' => -1,
            'telefono2' => -1,
            'telefonos_3' => -1,
            // Campos legacy para compatibilidad
            'obligacion' => -1,
            'saldo_k_obligacion' => -1,
            'capital_cliente' => -1,
            'pago_total_obligacion' => -1,
            'mora_actual' => -1,
            'propiedad' => -1,
            'producto' => -1,
            'medicion' => -1,
            'celular2' => -1,
            'email' => -1,
            'direccion' => -1,
            'ciudad' => -1
        ];
        for ($ti = 1; $ti <= 10; $ti++) {
            $indices['tel' . $ti] = -1;
        }
        // Columnas tel1..tel10 alineadas con `clientes.tel1`..`tel10` (prioridad sobre "telefono" genérico)
        foreach ($encabezados as $index => $encabezado) {
            if (!is_string($encabezado)) {
                continue;
            }
            $encabezado = preg_replace('/^\xEF\xBB\xBF/', '', $encabezado);
            $encabezado_limpio = strtolower(preg_replace('/\s+/', ' ', trim($encabezado)));
            $normCompact = preg_replace('/[^a-z0-9]/', '', $encabezado_limpio);
            if (preg_match('/^tel(10|[1-9])$/', $normCompact, $mm)) {
                $indices['tel' . $mm[1]] = $index;
            }
        }
        
        // Buscar encabezados específicos
        foreach ($encabezados as $index => $encabezado) {
            // Quitar BOM UTF-8 si el CSV lo trae (Excel suele agregarlo)
            if (is_string($encabezado)) {
                $encabezado = preg_replace('/^\xEF\xBB\xBF/', '', $encabezado);
            }
            // Limpiar el encabezado: quitar espacios extra y convertir a minúsculas
            $encabezado_limpio = strtolower(preg_replace('/\s+/', ' ', trim($encabezado)));
            $normCompact = preg_replace('/[^a-z0-9]/', '', $encabezado_limpio);
            
            // Campos obligatorios nuevos
            if (strpos($encabezado_limpio, 'cedula') !== false || strpos($encabezado_limpio, 'dni') !== false) {
                $indices['cedula'] = $index;
            } elseif (strpos($encabezado_limpio, 'nombre') !== false) {
                $indices['nombre'] = $index;
            } elseif (strpos($encabezado_limpio, 'numero factura') !== false || strpos($encabezado_limpio, 'numero_factura') !== false || strpos($encabezado_limpio, 'factura') !== false) {
                $indices['numero_factura'] = $index;
            }
            // Campos opcionales nuevos
            elseif (strpos($encabezado_limpio, 'rmt') !== false) {
                $indices['rmt'] = $index;
            } elseif (
                $indices['telefono'] === -1
                && !preg_match('/^tel(10|[1-9])$/', $normCompact)
                && strpos($encabezado_limpio, 'telefono2') === false
                && strpos($encabezado_limpio, 'telefono 2') === false
                && strpos($encabezado_limpio, 'telefono 3') === false
                && strpos($encabezado_limpio, 'telefonos 3') === false
                && strpos($encabezado_limpio, 'telefonos_3') === false
                && (strpos($encabezado_limpio, 'telefono') !== false || strpos($encabezado_limpio, 'tel') !== false)
            ) {
                $indices['telefono'] = $index;
            } elseif (strpos($encabezado_limpio, 'numero contrato') !== false || strpos($encabezado_limpio, 'numero_contrato') !== false || strpos($encabezado_limpio, 'contrato') !== false) {
                $indices['numero_contrato'] = $index;
            } elseif (strpos($encabezado_limpio, 'saldo') !== false) {
                $indices['saldo'] = $index;
            } elseif (strpos($encabezado_limpio, 'dias en mora') !== false || strpos($encabezado_limpio, 'dias_en_mora') !== false || strpos($encabezado_limpio, 'dias_mora') !== false) {
                $indices['dias_en_mora'] = $index;
                $indices['dias_mora'] = $index;
            } elseif (strpos($encabezado_limpio, 'franja') !== false) {
                $indices['franja'] = $index;
            }
            if (strpos($encabezado_limpio, 'telefono2') !== false || strpos($encabezado_limpio, 'telefono 2') !== false) {
                $indices['telefono2'] = $index;
            }
            if (strpos($encabezado_limpio, 'telefono 3') !== false || strpos($encabezado_limpio, 'telefonos 3') !== false || strpos($encabezado_limpio, 'telefonos_3') !== false) {
                $indices['telefonos_3'] = $index;
            }
            // Campos legacy para compatibilidad
            elseif (strpos($encabezado_limpio, 'obligacion') !== false) {
                $indices['obligacion'] = $index;
            } elseif (strpos($encabezado_limpio, 'saldo k obl') !== false || strpos($encabezado_limpio, 'saldo_k_obl') !== false) {
                $indices['saldo_k_obligacion'] = $index;
            } elseif (strpos($encabezado_limpio, 'capital cliente') !== false || strpos($encabezado_limpio, 'capital_cliente') !== false) {
                $indices['capital_cliente'] = $index;
            } elseif (strpos($encabezado_limpio, 'pago total obl') !== false || strpos($encabezado_limpio, 'pago_total_obl') !== false) {
                $indices['pago_total_obligacion'] = $index;
            } elseif (strpos($encabezado_limpio, 'mora actual') !== false || strpos($encabezado_limpio, 'mora_actual') !== false) {
                $indices['mora_actual'] = $index;
            } elseif (strpos($encabezado_limpio, 'propiedad') !== false) {
                $indices['propiedad'] = $index;
            } elseif (strpos($encabezado_limpio, 'producto') !== false) {
                $indices['producto'] = $index;
            } elseif (strpos($encabezado_limpio, 'medicion') !== false) {
                $indices['medicion'] = $index;
            } elseif (strpos($encabezado_limpio, 'celular') !== false || strpos($encabezado_limpio, 'movil') !== false) {
                $indices['celular2'] = $index;
            } elseif (strpos($encabezado_limpio, 'email') !== false || strpos($encabezado_limpio, 'correo') !== false) {
                $indices['email'] = $index;
            } elseif (strpos($encabezado_limpio, 'direccion') !== false || strpos($encabezado_limpio, 'dir') !== false) {
                $indices['direccion'] = $index;
            } elseif (strpos($encabezado_limpio, 'ciudad') !== false || strpos($encabezado_limpio, 'municipio') !== false) {
                $indices['ciudad'] = $index;
            }
        }
        
        return $indices;
    }

    /**
     * Detecta el delimitador del CSV automáticamente
     */
    private function detectarDelimitador($primera_linea) {
        $delimitadores = [',', ';', '\t', '|'];
        $max_campos = 0;
        $mejor_delimitador = ',';
        
        foreach ($delimitadores as $delimitador) {
            $campos = str_getcsv($primera_linea, $delimitador);
            if (count($campos) > $max_campos) {
                $max_campos = count($campos);
                $mejor_delimitador = $delimitador;
            }
        }
        
        return $mejor_delimitador;
    }

    /**
     * Limpia y convierte un valor a número decimal
     */
    private function limpiarNumero($valor) {
        if (empty($valor)) return null;
        
        // Remover caracteres no numéricos excepto punto y coma
        $valor = preg_replace('/[^0-9.,]/', '', $valor);
        
        if (empty($valor)) return null;
        
        // Convertir coma a punto para decimales
        $valor = str_replace(',', '.', $valor);
        
        return (float) $valor;
    }

    /**
     * Procesa y valida un email del CSV
     */
    private function procesarEmail($email) {
        if (empty($email)) {
            return null;
        }
        
        // Convertir a mayúsculas para verificar patrones
        $emailUpper = strtoupper(trim($email));
        
        // Filtrar emails que no son válidos
        $patronesNoValidos = [
            'NO REGISTRA',
            'NO REGISTRA MAIL',
            'NO REGISTRA EMAIL',
            'SIN EMAIL',
            'SIN CORREO',
            'N/A',
            ' NULL',
            'VACIO',
            'VACÍO'
        ];
        
        foreach ($patronesNoValidos as $patron) {
            if (strpos($emailUpper, $patron) !== false) {
                return null;
            }
        }
        
        // Limpiar el email
        $email = trim($email);
        
        // Intentar corregir emails comunes sin punto en el dominio
        $email = preg_replace('/@hotmailcom$/i', '@hotmail.com', $email);
        $email = preg_replace('/@gmailcom$/i', '@gmail.com', $email);
        $email = preg_replace('/@yahoocom$/i', '@yahoo.com', $email);
        $email = preg_replace('/@outlookcom$/i', '@outlook.com', $email);
        $email = preg_replace('/@livecom$/i', '@live.com', $email);
        
        // Validar formato básico de email
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $email;
        }
        
        // Si no es válido, retornar null
        return null;
    }

    /**
     * Determina en qué columna de teléfono guardar un número adicional
     * Busca la primera columna vacía disponible (cel3 hasta cel11)
     * 
     * @param array $clienteDatos Datos del cliente con sus teléfonos actuales
     * @param string $telefono Telefono a agregar
     * @return string|null Nombre de la columna donde guardar, o null si todas están ocupadas
     */
    private function obtenerColumnaTelefonoDisponible($clienteDatos, $telefono) {
        // Verificar si el teléfono ya existe
        $columnasTelefono = ['telefono', 'celular2', 'cel3', 'cel4', 'cel5', 'cel6', 'cel7', 'cel8', 'cel9', 'cel10', 'cel11'];
        
        foreach ($columnasTelefono as $columna) {
            if (!empty($clienteDatos[$columna]) && trim($clienteDatos[$columna]) === trim($telefono)) {
                // Teléfono duplicado, no agregar
                return null;
            }
        }
        
        // Buscar primera columna vacía desde cel3 (telefono y celular2 ya están ocupados o son principales)
        $columnasAdicionales = ['cel3', 'cel4', 'cel5', 'cel6', 'cel7', 'cel8', 'cel9', 'cel10', 'cel11'];
        
        foreach ($columnasAdicionales as $columna) {
            if (empty($clienteDatos[$columna]) || trim($clienteDatos[$columna]) === '') {
                return $columna;
            }
        }
        
        // Todas las columnas están ocupadas
        return null;
    }

    /**
     * Procesa los clientes del CSV y detecta duplicados
     * Optimizado para cargas grandes de más de 20,000 clientes
     * @param bool $actualizarExistentes Si es true, actualiza los datos de clientes existentes en lugar de solo agregar facturas
     */
    private function procesarClientesCSV($clientes, $cargaId, $coordinadorId, $actualizarExistentes = false) {
        $total = count($clientes);
        
        // OPTIMIZACIÓN: Usar método optimizado para archivos grandes (30,000+ registros)
        if ($total >= 30000) {
            error_log("Archivo grande detectado ($total registros). Usando método optimizado.");
            return $this->procesarClientesCSVOptimizado($clientes, $cargaId, $coordinadorId, $actualizarExistentes);
        }
        
        // Configurar límites de tiempo y memoria para cargas grandes
        ini_set('max_execution_time', 0); // Sin límite de tiempo
        ini_set('memory_limit', '2048M'); // 2GB de memoria
        
        $nuevos = 0;
        $duplicados = 0;
        $obligacionesDuplicadas = 0;
        $obligacionesCreadas = 0;
        
        // Log del inicio del procesamiento
        error_log("Iniciando procesamiento de $total clientes para carga ID: $cargaId");
        
        // MEJORA: Agrupar datos por cédula antes de procesar
        $datosAgrupados = [];
        foreach ($clientes as $cliente) {
            $cedula = $cliente['cedula'];
            if (!isset($datosAgrupados[$cedula])) {
                // Preparar datos básicos del cliente
                $infoCliente = [
                    'cedula' => $cliente['cedula'],
                    'nombre' => $cliente['nombre'],
                    'telefono' => $cliente['telefono'] ?? null,
                    'celular2' => $cliente['telefono2'] ?? $cliente['celular2'] ?? null,
                    'email' => $cliente['email'] ?? null,
                    'direccion' => $cliente['direccion'] ?? null,
                    'ciudad' => $cliente['ciudad'] ?? null
                ];
                if (!empty($cliente['tel1']) && empty($infoCliente['telefono'])) {
                    $infoCliente['telefono'] = $cliente['tel1'];
                }
                if (!empty($cliente['tel2']) && empty($infoCliente['celular2'])) {
                    $infoCliente['celular2'] = $cliente['tel2'];
                }
                for ($ti = 3; $ti <= 10; $ti++) {
                    $tk = 'tel' . $ti;
                    if (!empty($cliente[$tk])) {
                        $infoCliente[$tk] = $cliente[$tk];
                    }
                }
                
                // Procesar teléfono3 (telefonos_3) y agregarlo a la primera columna disponible
                $telefono3 = $cliente['telefonos_3'] ?? null;
                if (!empty($telefono3) && trim($telefono3) !== '') {
                    $telefono3 = trim($telefono3);
                    // Buscar primera columna vacía desde cel3
                    $columnasAdicionales = ['cel3', 'cel4', 'cel5', 'cel6', 'cel7', 'cel8', 'cel9', 'cel10', 'cel11'];
                    foreach ($columnasAdicionales as $columna) {
                        if (empty($infoCliente[$columna])) {
                            $infoCliente[$columna] = $telefono3;
                            break;
                        }
                    }
                }
                
                $datosAgrupados[$cedula] = [
                    'info_cliente' => $infoCliente,
                    'obligaciones' => []
                ];
            }
            
            // Agregar obligación al grupo (usando numero_factura como obligacion)
            $datosAgrupados[$cedula]['obligaciones'][] = [
                'obligacion' => $cliente['numero_factura'] ?? $cliente['obligacion'],
                'numero_factura' => $cliente['numero_factura'] ?? null,
                'saldo_k_obligacion' => $cliente['saldo_k_obligacion'] ?? null,
                'saldo' => $cliente['saldo'] ?? null,
                'capital_cliente' => $cliente['capital_cliente'] ?? null,
                'pago_total_obligacion' => $cliente['pago_total_obligacion'] ?? null,
                'mora_actual' => $cliente['mora_actual'] ?? null,
                'dias_mora' => $cliente['dias_mora'] ?? null,
                'franja' => $cliente['franja'] ?? null,
                'propiedad' => $cliente['propiedad'] ?? null,
                'producto' => $cliente['producto'] ?? null,
                'medicion' => $cliente['medicion'] ?? null,
                // Campos adicionales nuevos
                'rmt' => $cliente['rmt'] ?? null,
                'numero_contrato' => $cliente['numero_contrato'] ?? null,
                'telefono2' => $cliente['telefono2'] ?? null,
                'telefono3' => $cliente['telefonos_3'] ?? null
            ];
        }
        
        // Procesar cada grupo (un cliente por cédula) en lotes para optimizar memoria
        $lote = 0;
        $tamañoLote = 100; // Procesar de 100 en 100
        $gruposArray = array_chunk($datosAgrupados, $tamañoLote, true);
        
        foreach ($gruposArray as $loteGrupos) {
            $lote++;
            error_log("Procesando lote $lote de " . count($loteGrupos) . " clientes");
            
            // Iniciar transacción para el lote
            $this->pdo->beginTransaction();
            
            try {
                foreach ($loteGrupos as $cedula => $grupo) {
                    // CORRECCIÓN: Buscar cliente por cédula Y carga_excel_id
                    // Esto evita mover clientes entre bases y mezclar facturas
                    $clienteExistente = $this->clienteModel->getClienteByCedulaYCarga($cedula, $cargaId);
                    
                    if ($clienteExistente) {
                        // Cliente ya existe en ESTA carga específica
                        $duplicados++;
                        $clienteId = $clienteExistente['id'];
                        
                        // Si se debe actualizar, actualizar datos del cliente (excepto la cédula)
                        if ($actualizarExistentes) {
                            $datosActualizacion = [];
                            
                            // Campos reales en tabla `clientes`: nombre/email/ciudad son editables
                            if (!empty($grupo['info_cliente']['nombre'])) $datosActualizacion['nombre'] = $grupo['info_cliente']['nombre'];
                            if (!empty($grupo['info_cliente']['email'])) $datosActualizacion['email'] = $grupo['info_cliente']['email'];
                            if (!empty($grupo['info_cliente']['ciudad'])) $datosActualizacion['ciudad'] = $grupo['info_cliente']['ciudad'];

                            // Teléfonos reales: tel1..tel10 (NOT NULL). Solo actualizamos si llega valor.
                            $tel1 = (string)($grupo['info_cliente']['telefono'] ?? '');
                            $tel2 = (string)($grupo['info_cliente']['celular2'] ?? '');
                            if ($tel1 !== '') $datosActualizacion['tel1'] = $tel1;
                            if ($tel2 !== '') $datosActualizacion['tel2'] = $tel2;
                            
                            // Teléfono 3: en el parser viene como `telefonos_3`
                            $tel3 = '';
                            foreach ($clientes as $clienteCSV) {
                                if (($clienteCSV['cedula'] ?? null) == $cedula) {
                                    $tel3 = trim((string)($clienteCSV['telefonos_3'] ?? ''));
                                    break;
                                }
                            }
                            if ($tel3 !== '') $datosActualizacion['tel3'] = $tel3;
                            
                            // Actualizar el cliente
                            if (!empty($datosActualizacion)) {
                                $this->clienteModel->actualizarCliente($clienteId, $datosActualizacion);
                            }
                            // NOTA: Las facturas se actualizan durante el procesamiento de obligaciones más abajo
                        }
                    } else {
                        // Cliente nuevo en esta carga - crear NUEVO cliente
                        // Aunque tenga la misma cédula que otro cliente en otra base,
                        // creamos un cliente nuevo para mantener las facturas separadas
                        $clienteId = $this->clienteModel->crearCliente(array_merge($grupo['info_cliente'], [
                            'carga_excel_id' => $cargaId
                        ]));
                        
                        if ($clienteId) {
                            $nuevos++;
                        } else {
                            error_log("Error al crear cliente con cédula: $cedula en carga: $cargaId");
                            continue;
                        }
                    }
                    
                    // Si estamos actualizando y el cliente existe, primero eliminar facturas que no están en el CSV
                    if ($actualizarExistentes && $clienteExistente) {
                        // Obtener todos los números de factura del CSV para este cliente
                        $numerosFacturaCSV = array_map(function($obligacion) {
                            return $obligacion['numero_factura'] ?? null;
                        }, $grupo['obligaciones']);
                        
                        // Filtrar valores nulos
                        $numerosFacturaCSV = array_filter($numerosFacturaCSV, function($numero) {
                            return !empty($numero);
                        });
                        
                        // Eliminar facturas que no están en el CSV
                        if (!empty($numerosFacturaCSV)) {
                            $facturasEliminadas = $this->facturacionModel->eliminarFacturasNoIncluidas($clienteId, array_values($numerosFacturaCSV));
                            if ($facturasEliminadas > 0) {
                                error_log("Eliminadas $facturasEliminadas facturas del cliente $clienteId que no estaban en el CSV");
                            }
                        } else {
                            // Si no hay facturas en el CSV, eliminar todas las facturas del cliente
                            $facturasEliminadas = $this->facturacionModel->eliminarFacturasNoIncluidas($clienteId, []);
                            if ($facturasEliminadas > 0) {
                                error_log("Eliminadas todas las facturas ($facturasEliminadas) del cliente $clienteId porque el CSV no tiene facturas");
                            }
                        }
                    }
                    
                    // Procesar todas las obligaciones de este cliente
                    foreach ($grupo['obligaciones'] as $obligacion) {
                        // CORRECCIÓN: Verificar si la factura ya existe para ESTE cliente específico
                        // Esto permite que el mismo número de factura exista para diferentes clientes
                        // (cuando hay clientes con la misma cédula en diferentes bases)
                        $facturaExistente = $this->facturacionModel->getFacturaByNumeroAndCliente($obligacion['numero_factura'], $clienteId);
                        
                        if ($facturaExistente) {
                            // Si la factura existe y estamos actualizando, actualizar la factura
                            if ($actualizarExistentes) {
                                $datosFacturaUpdate = [];
                                
                                // Actualizar datos financieros de la factura
                                if (isset($obligacion['saldo']) && $obligacion['saldo'] !== null) {
                                    $datosFacturaUpdate['saldo'] = $obligacion['saldo'];
                                }
                                if (isset($obligacion['dias_mora']) && $obligacion['dias_mora'] !== null) {
                                    $datosFacturaUpdate['dias_mora'] = $obligacion['dias_mora'];
                                }
                                if (isset($obligacion['rmt']) && !empty($obligacion['rmt'])) {
                                    $datosFacturaUpdate['rmt'] = $obligacion['rmt'];
                                }
                                if (isset($obligacion['numero_contrato']) && !empty($obligacion['numero_contrato'])) {
                                    $datosFacturaUpdate['numero_contrato'] = $obligacion['numero_contrato'];
                                }
                                if (isset($obligacion['franja']) && !empty($obligacion['franja'])) {
                                    $datosFacturaUpdate['franja'] = $obligacion['franja'];
                                }
                                
                                // Actualizar la factura
                                if (!empty($datosFacturaUpdate)) {
                                    $this->facturacionModel->actualizarFactura($facturaExistente['id_obligacion'], $datosFacturaUpdate);
                                    $obligacionesCreadas++; // Contar como procesada (actualizada)
                                } else {
                                    $obligacionesDuplicadas++; // No había cambios
                                }
                            } else {
                                // Si no estamos actualizando, marcar como duplicada
                                $obligacionesDuplicadas++;
                            }
                            continue;
                        }
                    
                        // Crear factura nueva
                        if ($this->facturacionModel->crearFactura(array_merge($obligacion, [
                            'base_id' => $cargaId,
                            'cliente_id' => $clienteId,
                            'estado_factura' => 'pendiente'
                        ]))) {
                            $obligacionesCreadas++;
                        } else {
                            error_log("Error al crear obligación: {$obligacion['obligacion']}");
                        }
                    }
                }
                
                // Confirmar transacción del lote
                $this->pdo->commit();
                error_log("Lote $lote completado exitosamente");
                
            } catch (Exception $e) {
                // Revertir transacción en caso de error
                $this->pdo->rollBack();
                error_log("Error en lote $lote: " . $e->getMessage());
                continue;
            }
            
            // Liberar memoria después de cada lote
            if (function_exists('gc_collect_cycles')) {
                gc_collect_cycles();
            }
        }
        
        return [
            'success' => true,
            'nuevos' => $nuevos,
            'duplicados' => $duplicados,
            'obligaciones_duplicadas' => $obligacionesDuplicadas,
            'obligaciones_creadas' => $obligacionesCreadas,
            'total' => $total
        ];
    }

    public function listClientsByCarga($cargaId) {
        $page_title = "Clientes de la Carga";
        $coordinador_id = $_SESSION['user_id'];
        
        // Verificar que la carga pertenezca al coordinador
        $carga = $this->cargaExcelModel->getCargaByIdAndCoordinador($cargaId, $coordinador_id);
        if (!$carga) {
            $_SESSION['error_message'] = "No tienes acceso a esta carga o no existe.";
            header('Location: index.php?action=list_cargas');
            exit;
        }
        
        // Se implementa la paginación.
        $clientesPorPagina = 25;
        $paginaActual = $this->getGet('pagina', 1);
        $paginaActual = $this->validarId($paginaActual, 'página');
        
        // Se usa la función del modelo para obtener el total de clientes por carga.
        $totalClientes = $this->clienteModel->getTotalClientsByCargaIdAndCoordinador($cargaId, $coordinador_id);
        $totalPaginas = ceil($totalClientes / $clientesPorPagina);

        $offset = ($paginaActual - 1) * $clientesPorPagina;
        
        // Se llama a la función corregida en el modelo para obtener los clientes paginados.
        $clientes = $this->clienteModel->getClientsByCargaIdAndCoordinador($cargaId, $coordinador_id, $clientesPorPagina, $offset);
        $asesores = $this->usuarioModel->getUsuariosByRol('asesor');
        $carga_id = $cargaId;
        
        require __DIR__ . '/../views/clientes_list.php';
    }
    
    public function assignClients($cargaId) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clientes']) && isset($_POST['asesor_id'])) {
            $clienteIds = $_POST['clientes']; // Array de IDs, validar cada uno
            $asesorId = $this->getPost('asesor_id');
            $asesorId = $this->validarId($asesorId, 'asesor');
            $this->clienteModel->assignClientsToAsesor($clienteIds, $asesorId);
            header('Location: index.php?action=ver_clientes&carga_id=' . $cargaId);
            exit;
        }
    }

    public function viewAsesorProgress($asesorId) {
        $page_title = "Progreso del Asesor";
        $asesor = $this->usuarioModel->getUsuarioById($asesorId);
        $gestiones = $this->gestionModel->getGestionByAsesor($asesorId);
        $clientes = $this->clienteModel->getAssignedClientsForAsesor($asesorId);

        // Contar el número de gestiones por cliente
        $gestiones_por_cliente = [];
        foreach ($gestiones as $gestion) {
            $clienteId = $gestion['cliente_id'];
            if (!isset($gestiones_por_cliente[$clienteId])) {
                $gestiones_por_cliente[$clienteId] = 0;
            }
            $gestiones_por_cliente[$clienteId]++;
        }
        
        require __DIR__ . '/../views/asesor_progreso.php';
    }

    public function tareas() {
        $page_title = "Tareas del Coordinador";
        $coordinador_id = $_SESSION['user_id'];
        $cargas = $this->clienteModel->getCargasByCoordinador($coordinador_id, true); // Solo bases habilitadas
        $asesores = $this->usuarioModel->getAsesoresByCoordinador($coordinador_id);
        
        // Calcular estadísticas para cada carga
        $cargas_con_stats = [];
        foreach ($cargas as $carga) {
            $carga['total_clientes'] = $this->clienteModel->getTotalClientsByCargaIdAndCoordinador($carga['id'], $coordinador_id);
            $carga['clientes_asignados'] = $this->clienteModel->getTotalClientsAsignadosByCargaIdAndCoordinador($carga['id'], $coordinador_id);
            $carga['clientes_pendientes'] = $carga['total_clientes'] - $carga['clientes_asignados'];
            $cargas_con_stats[] = $carga;
        }
        $cargas = $cargas_con_stats;
        
        // Calcular clientes asignados por asesor para cada carga
        $asesores_con_clientes = [];
        foreach ($asesores as $asesor) {
            $asesor['clientes_por_carga'] = [];
            foreach ($cargas as $carga) {
                $asesor['clientes_por_carga'][$carga['id']] = $this->clienteModel->getTotalClientsByAsesorAndCarga($asesor['id'], $carga['id'], $coordinador_id);
            }
            $asesores_con_clientes[] = $asesor;
        }
        $asesores = $asesores_con_clientes;
        
        require __DIR__ . '/../views/tareas_coordinador.php';
    }

    public function asignarClientes() {
        $cargaId = $this->getPost('carga_id');
        $cargaId = $this->validarId($cargaId, 'carga');
        $coordinador_id = $_SESSION['user_id'];
        
        // Verificar que la carga pertenezca al coordinador
        $carga = $this->cargaExcelModel->getCargaByIdAndCoordinador($cargaId, $coordinador_id);
        if (!$carga) {
            $_SESSION['error_message'] = "No tienes acceso a esta carga o no existe.";
            header("Location: index.php?action=tareas_coordinador");
            exit;
        }
        
        $asignaciones = $_POST['asignaciones']; // array: asesor_id => cantidad - validar cada clave y valor
        $clientes = $this->clienteModel->getUnassignedClientsByCargaAndCoordinador($cargaId, $coordinador_id);
        
        if (empty($clientes)) {
            $_SESSION['error_message'] = "No hay clientes disponibles para asignar.";
            header("Location: index.php?action=tareas_coordinador");
            exit;
        }

        $totalAsignados = 0;
        $clientesDisponibles = $clientes;
        
        foreach ($asignaciones as $asesorId => $cantidad) {
            if ($cantidad > 0 && !empty($clientesDisponibles)) {
                // Tomar solo la cantidad especificada de clientes disponibles
                $aAsignar = array_slice($clientesDisponibles, 0, $cantidad);
                $clienteIds = array_column($aAsignar, 'id');
                
                if (!empty($clienteIds)) {
                    $this->clienteModel->assignClientsToAsesor($clienteIds, $asesorId);
                    $totalAsignados += count($clienteIds);
                    
                    // Remover los clientes asignados de la lista disponible
                    $clientesDisponibles = array_slice($clientesDisponibles, $cantidad);
                }
            }
        }
        
        if ($totalAsignados > 0) {
            $_SESSION['success_message'] = "Se asignaron $totalAsignados clientes correctamente.";
        } else {
            $_SESSION['error_message'] = "No se pudo asignar ningún cliente.";
        }
        
        header("Location: index.php?action=tareas_coordinador");
        exit;
    }

    public function asignarAutomatico() {
        $cargaId = $this->getPost('carga_id');
        $cargaId = $this->validarId($cargaId, 'carga');
        $coordinador_id = $_SESSION['user_id'];
        
        // Verificar que la carga pertenezca al coordinador
        $carga = $this->cargaExcelModel->getCargaByIdAndCoordinador($cargaId, $coordinador_id);
        if (!$carga) {
            $_SESSION['error_message'] = "No tienes acceso a esta carga o no existe.";
            header("Location: index.php?action=tareas_coordinador");
            exit;
        }
        
        $asesores = $this->usuarioModel->getAsesoresByCoordinador($coordinador_id);
        if (empty($asesores)) {
            $_SESSION['error_message'] = "No tienes asesores asignados. Contacta al administrador.";
            header("Location: index.php?action=tareas_coordinador");
            exit;
        }
        
        $clientes = $this->clienteModel->getUnassignedClientsByCargaAndCoordinador($cargaId, $coordinador_id);

        $totalAsesores = count($asesores);
        $index = 0;
        foreach ($clientes as $cliente) {
            $asesorId = $asesores[$index % $totalAsesores]['id'];
            $this->clienteModel->assignClientsToAsesor([$cliente['id']], $asesorId);
            $index++;
        }
        $_SESSION['success_message'] = "Clientes asignados automáticamente.";
        header("Location: index.php?action=tareas_coordinador");
        exit;
    }
    
    /**
     * Gestiona la asignación de asesores al coordinador
     */
    public function gestionarAsesores() {
        $page_title = "Gestión de Asesores";
        $coordinador_id = $_SESSION['user_id'];
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['action'])) {
                switch ($_POST['action']) {
                    case 'asignar':
                        if (isset($_POST['asesor_id'])) {
                            $asesorId = $this->validarId($_POST['asesor_id'], 'asesor');
                            $this->usuarioModel->asignarAsesorACoordinador($asesorId, $coordinador_id);
                            $_SESSION['success_message'] = "Asesor asignado correctamente.";
                        }
                        break;
                        
                    case 'liberar':
                        if (isset($_POST['asesor_id'])) {
                            $asesorId = $this->validarId($_POST['asesor_id'], 'asesor');
                            $this->usuarioModel->liberarAsesorDeCoordinador($asesorId, $coordinador_id);
                            $_SESSION['success_message'] = "Asesor liberado correctamente.";
                        }
                        break;
                }
                header("Location: index.php?action=gestionar_asesores");
                exit;
            }
        }
        
        $asesoresAsignados = $this->usuarioModel->getAsesoresByCoordinador($coordinador_id);
        $asesoresDisponibles = $this->usuarioModel->getAsesoresDisponibles();
        
        require __DIR__ . '/../views/coordinador_gestionar_asesores.php';
    }
    
    /**
     * Gestiona el traspaso de clientes entre asesores
     */
    public function gestionarTraspasos() {
        $page_title = "Gestión de Traspasos de Clientes";
        $coordinador_id = $_SESSION['user_id'];
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['action'])) {
                try {
                    switch ($_POST['action']) {
                        case 'traspasar':
                            if (isset($_POST['cliente_id']) && isset($_POST['nuevo_asesor_id']) && isset($_POST['asesor_origen_id'])) {
                                $clienteId = $this->validarId($_POST['cliente_id'], 'cliente');
                                $nuevoAsesorId = $this->validarId($_POST['nuevo_asesor_id'], 'nuevo asesor');
                                $asesorOrigenId = $this->validarId($_POST['asesor_origen_id'], 'asesor origen');
                                
                                $this->clienteModel->traspasarCliente($clienteId, $nuevoAsesorId, $asesorOrigenId);
                                $_SESSION['success_message'] = "Cliente traspasado correctamente.";
                            }
                            break;
                            
                        case 'liberar':
                            if (isset($_POST['cliente_id']) && isset($_POST['asesor_id'])) {
                                $clienteId = $this->validarId($_POST['cliente_id'], 'cliente');
                                $asesorId = $this->validarId($_POST['asesor_id'], 'asesor');
                                
                                $this->clienteModel->liberarCliente($clienteId, $asesorId);
                                $_SESSION['success_message'] = "Cliente liberado correctamente.";
                            }
                            break;
                    }
                } catch (Exception $e) {
                    $_SESSION['error_message'] = "Error: " . $e->getMessage();
                }
                header("Location: index.php?action=gestionar_traspasos");
                exit;
            }
        }
        
        // Obtener asesores del coordinador
        $asesores = $this->usuarioModel->getAsesoresByCoordinador($coordinador_id);
        
        // Obtener clientes de cada asesor
        $clientesPorAsesor = [];
        foreach ($asesores as $asesor) {
            $clientesPorAsesor[$asesor['id']] = [
                'asesor' => $asesor,
                'clientes' => $this->clienteModel->getClientesByAsesor($asesor['id'])
            ];
        }
        
        require __DIR__ . '/../views/coordinador_gestionar_traspasos.php';
    }
    
    /**
     * Muestra los detalles de un cliente específico para el coordinador
     */
    public function verDetalleCliente($clienteId) {
        $page_title = "Detalle del Cliente";
        $coordinador_id = $_SESSION['user_id'];
        
        // Verificar que el cliente pertenezca a una carga del coordinador
        $cliente = $this->clienteModel->getClienteByIdAndCoordinador($clienteId, $coordinador_id);
        
        if (!$cliente) {
            $_SESSION['error_message'] = "No tienes acceso a este cliente o no existe.";
            header("Location: index.php?action=tareas_coordinador");
            exit;
        }
        
        // Obtener el historial de gestiones del cliente
        $gestiones = $this->gestionModel->getGestionByAsesorAndCliente($cliente['asesor_id'] ?? null, $clienteId);
        
        // Obtener información del asesor asignado
        $asesor = null;
        if ($cliente['asesor_id']) {
            $asesor = $this->usuarioModel->getUsuarioById($cliente['asesor_id']);
        }
        
        require __DIR__ . '/../views/coordinador_detalle_cliente.php';
    }

    /**
     * Muestra los detalles de gestión de un cliente específico de un asesor para el coordinador
     */
    public function verDetalleGestionAsesor($clienteId, $asesorId) {
        $page_title = "Detalle de Gestión del Asesor";
        $coordinador_id = $_SESSION['user_id'];
        
        // Verificar que el cliente pertenezca a una carga del coordinador
        $cliente = $this->clienteModel->getClienteByIdAndCoordinador($clienteId, $coordinador_id);
        
        if (!$cliente) {
            $_SESSION['error_message'] = "No tienes acceso a este cliente o no existe.";
            header("Location: index.php?action=tareas_coordinador");
            exit;
        }
        
        // Verificar que el asesor esté asignado al coordinador
        $asesor = $this->usuarioModel->getUsuarioById($asesorId);
        if (!$asesor || $asesor['rol'] !== 'asesor') {
            $_SESSION['error_message'] = "El asesor especificado no existe o no es válido.";
            header("Location: index.php?action=tareas_coordinador");
            exit;
        }
        
        // Obtener el historial de gestiones del cliente por ese asesor específico
        $gestiones = $this->gestionModel->getGestionByAsesorAndCliente($asesorId, $clienteId);
        
        require __DIR__ . '/../views/coordinador_detalle_gestion_asesor.php';
    }

    /**
     * Obtiene la clase CSS para la fila de gestión basada en el resultado
     */
    private function getGestionRowClass($resultado) {
        if (empty($resultado)) return '';
        
        if (in_array($resultado, ['Venta Exitosa', 'Venta en Frío', 'Venta con Seguimiento', 'Venta Cruzada'])) {
            return 'venta';
        } elseif (in_array($resultado, ['Rechazo por Precio', 'Rechazo por Competencia', 'No Interesado', 'No Califica', 'Necesita Pensarlo'])) {
            return 'rechazado';
        } elseif (in_array($resultado, ['No Contesta', 'Número Equivocado', 'Buzón de Voz', 'Número Fuera de Servicio', 'Cliente Ocupado'])) {
            return 'sin-contacto';
        } elseif (in_array($resultado, ['Agenda Llamada de Seguimiento'])) {
            return 'seguimiento';
        }
        
        return '';
    }

    /**
     * Obtiene la clase CSS para el badge de resultado
     */
    private function getResultadoClass($resultado) {
        if (empty($resultado)) return 'sin-resultado';
        
        if (in_array($resultado, ['Venta Exitosa', 'Venta en Frío', 'Venta con Seguimiento', 'Venta Cruzada'])) {
            return 'venta';
        } elseif (in_array($resultado, ['Rechazo por Precio', 'Rechazo por Competencia', 'No Interesado', 'No Califica', 'Necesita Pensarlo'])) {
            return 'rechazo';
        } elseif (in_array($resultado, ['No Contesta', 'Número Equivocado', 'Buzón de Voz', 'Número Fuera de Servicio', 'Cliente Ocupado'])) {
            return 'sin-contacto';
        } elseif (in_array($resultado, ['Agenda Llamada de Seguimiento'])) {
            return 'seguimiento';
        }
        
        return 'sin-resultado';
    }

    /**
     * Normaliza la cédula del asesor (identificador real en BD y en mapUsuarioRow['id']).
     */
    private function sanitizeAsesorCedulaParam($raw): ?string {
        $s = preg_replace('/\D+/', '', (string)$raw);
        if ($s === '' || strlen($s) < 4 || strlen($s) > 12) {
            return null;
        }
        return $s;
    }

    /**
     * Exporta la gestión de un asesor específico a CSV
     */
    public function exportarGestionAsesor($asesorId, $fechaInicio = null, $fechaFin = null, $filtros = []) {
        // Limpiar cualquier output previo
        if (ob_get_level()) ob_clean();
        
        try {
            // Desactivar la salida de errores para evitar que se mezclen con el CSV
            error_reporting(0);
            ini_set('display_errors', 0);
            
            $coordinador_id = $_SESSION['user_id'];
        
        // Verificar que el asesor esté asignado al coordinador
        $asesor = $this->usuarioModel->getUsuarioById($asesorId);
        if (!$asesor || !$this->usuarioModel->isAsesorAsignadoACoordinador($asesorId, $coordinador_id)) {
            $_SESSION['error_message'] = "No tienes acceso a este asesor.";
            header('Location: index.php?action=tareas_coordinador');
            exit;
        }
        
        // Si no se especifican fechas, usar el mes actual
        if (!$fechaInicio) { $fechaInicio = date('Y-m-01'); }
        if (!$fechaFin) { $fechaFin = date('Y-m-t'); }
        
        // Aplicar filtros del modal y obtener historial COMPLETO con todos los campos requeridos
        // Usar el nuevo método que incluye tipificaciones de 2 y 3 nivel, canales autorizados y base de datos
        $gestiones = $this->gestionModel->getHistorialCompletoParaExportacion(
            $asesorId, 
            $fechaInicio, 
            $fechaFin
        );
        
        // Si se aplican filtros adicionales, filtrar los resultados
        if (!empty($filtros)) {
            $gestiones = $this->filtrarGestiones($gestiones, $filtros);
        }
        
        if (empty($gestiones)) {
            // En lugar de redirigir, crear un CSV vacío con mensaje
            $nombreAsesor = str_replace(' ', '_', $asesor['nombre_completo']);
            $filename = "Gestion_Asesor_{$nombreAsesor}_{$fechaInicio}_a_{$fechaFin}_SIN_DATOS.csv";
            
            // Configurar headers para descarga CSV
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Cache-Control: max-age=0');
            
            // Crear archivo CSV
            $output = fopen('php://output', 'w');
            
            // BOM para UTF-8
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Encabezados del CSV (mismo formato que el reporte normal)
            $headers = [
                'Fecha de Gestión',
                'Asesor',
                'Cédula del Cliente',
                'Nombre del Cliente',
                'Nombre de la Base',
                'Teléfono de Contacto',
                'Número de Factura',
                'Franja del Cliente',
                'Forma de Contacto',
                'Tipo de Contacto',
                'Resultado de Contacto',
                'Razón Específica',
                'Fecha de Pago',
                'Monto de Acuerdo',
                'Observaciones',
                'Canales Autorizados',
                'Celular',
                'Celular 2',
                'Celular 3',
                'Celular 4',
                'Celular 5',
                'Celular 6',
                'Celular 7',
                'Celular 8',
                'Celular 9',
                'Celular 10'
            ];
            fputcsv($output, $headers, ';');
            
            // Agregar mensaje de que no hay datos
            $row = array_fill(0, 26, '');
            $row[0] = 'NO HAY DATOS PARA EXPORTAR EN EL PERÍODO SELECCIONADO';
            $row[1] = 'ASESOR: ' . ($asesor['nombre_completo'] ?? '');
            $row[2] = 'PERÍODO: ' . $fechaInicio . ' A ' . $fechaFin;
            fputcsv($output, $row, ';');
            
            fclose($output);
            exit;
        }
        
        // Generar nombre del archivo
        $nombreAsesor = str_replace(' ', '_', $asesor['nombre_completo']);
        $filename = "Gestion_Asesor_{$nombreAsesor}_{$fechaInicio}_a_{$fechaFin}.csv";
        
        // Limpiar cualquier salida previa
        if (ob_get_level()) {
            ob_end_clean();
        }
        
        // Configurar headers para descarga CSV
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        
        // Crear archivo CSV
        $output = fopen('php://output', 'w');
        
        // BOM para UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Encabezados del CSV con todos los campos requeridos
        $headers = [
            'Fecha de Gestión',
            'Asesor',
            'Cédula del Cliente',
            'Nombre del Cliente',
            'Nombre de la Base',
            'Teléfono de Contacto',
            'Número de Factura',
            'Franja del Cliente',
            'Forma de Contacto',
            'Tipo de Contacto',
            'Resultado de Contacto',
            'Razón Específica',
            'Fecha de Pago',
            'Monto de Acuerdo',
            'Observaciones',
            'Canales Autorizados',
            'Celular',
            'Celular 2',
            'Celular 3',
            'Celular 4',
            'Celular 5',
            'Celular 6',
            'Celular 7',
            'Celular 8',
            'Celular 9',
            'Celular 10'
        ];
        fputcsv($output, $headers, ';');
        
        // Datos de las gestiones con todos los campos requeridos
        foreach ($gestiones as $gestion) {
            $row = $this->formatearFilaGestionCSV($gestion);
            fputcsv($output, $row, ';');
        }
        
        fclose($output);
        exit;
        
        } catch (Exception $e) {
            // Log del error
            error_log("Error en exportarGestionAsesor: " . $e->getMessage());
            
            // Limpiar cualquier output previo
            if (ob_get_level()) {
                ob_end_clean();
            }
            
            // Configurar headers para descarga CSV de error
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="error_exportacion_asesor.csv"');
            header('Cache-Control: max-age=0');
            
            // Crear archivo CSV con mensaje de error
            $output = fopen('php://output', 'w');
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
            
            $headers = ['Error', 'Mensaje', 'Fecha'];
            fputcsv($output, $headers, ';');
            
            $row = [
                'Error de Exportación del Asesor',
                'Ocurrió un error al exportar los datos del asesor: ' . $e->getMessage(),
                date('Y-m-d H:i:s')
            ];
            fputcsv($output, $row, ';');
            
            fclose($output);
            exit;
        }
    }

    /**
     * Exporta la gestión de todos los asesores a CSV con información de base de datos
     */
    public function exportarGestionTodosAsesores($fechaInicio = null, $fechaFin = null) {
        if (ob_get_level()) ob_clean();
        
        try {
            // Desactivar la salida de errores para evitar que se mezclen con el CSV
            error_reporting(0);
            ini_set('display_errors', 0);
            
            $coordinador_id = $_SESSION['user_id'];
        
        // Normalizar fechas: trim y tratar cadena vacía como null (evita usar mes completo por error)
        $fechaInicio = trim((string)($fechaInicio ?? '')) ?: null;
        $fechaFin = trim((string)($fechaFin ?? '')) ?: null;
        
        // Si no se especifican fechas, usar el mes actual
        if (!$fechaInicio) { $fechaInicio = date('Y-m-01'); }
        if (!$fechaFin) { $fechaFin = date('Y-m-t'); }
        
        // Asesores formalmente asignados al coordinador (asignaciones_cordinador en emermedica_cobranza.sql)
        $asesores = $this->usuarioModel->getAsesoresByCoordinador($coordinador_id);

        if (empty($asesores)) {
            $filename = "Gestion_Equipo_Completo_{$fechaInicio}_a_{$fechaFin}_SIN_ASESORES.csv";

            // Configurar headers para descarga CSV
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Cache-Control: max-age=0');

            // Crear archivo CSV
            $output = fopen('php://output', 'w');

            // BOM para UTF-8
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

            // Encabezados del CSV (mismo formato que el reporte normal)
            $headers = [
                'Fecha de Gestión',
                'Asesor',
                'Cédula del Cliente',
                'Nombre del Cliente',
                'Nombre de la Base',
                'Teléfono de Contacto',
                'Número de Factura',
                'Franja del Cliente',
                'Forma de Contacto',
                'Tipo de Contacto',
                'Resultado de Contacto',
                'Razón Específica',
                'Fecha de Pago',
                'Monto de Acuerdo',
                'Observaciones',
                'Canales Autorizados',
                'Celular',
                'Celular 2',
                'Celular 3',
                'Celular 4',
                'Celular 5',
                'Celular 6',
                'Celular 7',
                'Celular 8',
                'Celular 9',
                'Celular 10'
            ];
            fputcsv($output, $headers, ';');

            $row = array_fill(0, 26, '');
            $row[0] = 'NO HAY ASESORES ACTIVOS ASIGNADOS A SU COORDINACIÓN';
            $row[1] = 'PERÍODO: ' . $fechaInicio . ' A ' . $fechaFin;
            fputcsv($output, $row, ';');

            fclose($output);
            exit;
        }
        
        // Generar nombre del archivo
        $filename = "Gestion_Equipo_Completo_{$fechaInicio}_a_{$fechaFin}.csv";
        
        // Limpiar cualquier salida previa
        if (ob_get_level()) {
            ob_end_clean();
        }
        
        // Configurar headers para descarga CSV
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        
        // Crear archivo CSV
        $output = fopen('php://output', 'w');
        
        // BOM para UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Encabezados del CSV con orden correcto
        $headers = [
            'Fecha de Gestión',
            'Asesor',
            'Cédula del Cliente',
            'Nombre del Cliente',
            'Nombre de la Base',
            'Teléfono de Contacto',
            'Número de Factura',
            'Franja del Cliente',
            'Forma de Contacto',
            'Tipo de Contacto',
            'Resultado de Contacto',
            'Razón Específica',
            'Fecha de Pago',
            'Monto de Acuerdo',
            'Observaciones',
            'Canales Autorizados',
            'Celular',
            'Celular 2',
            'Celular 3',
            'Celular 4',
            'Celular 5',
            'Celular 6',
            'Celular 7',
            'Celular 8',
            'Celular 9',
            'Celular 10'
        ];
        fputcsv($output, $headers, ';');
        
        // Recolectar todas las gestiones de todos los asesores
        $todasLasGestiones = [];
        foreach ($asesores as $asesor) {
            $gestiones = $this->gestionModel->getHistorialCompletoParaExportacion($asesor['id'], $fechaInicio, $fechaFin);
            foreach ($gestiones as $gestion) {
                $todasLasGestiones[] = $gestion;
            }
        }
        
        // Ordenar por fecha y hora de la última gestión (más reciente primero)
        usort($todasLasGestiones, function ($a, $b) {
            $fechaA = strtotime($a['fecha_gestion'] ?? '1970-01-01');
            $fechaB = strtotime($b['fecha_gestion'] ?? '1970-01-01');
            return $fechaB - $fechaA; // DESC: más reciente primero
        });

        if (empty($todasLasGestiones)) {
            $row = array_fill(0, 26, '');
            $row[0] = 'NO HAY GESTIONES EN EL PERÍODO PARA LOS ASESORES DE SU EQUIPO';
            $row[1] = 'PERÍODO: ' . $fechaInicio . ' A ' . $fechaFin;
            fputcsv($output, $row, ';');
            fclose($output);
            exit;
        }
        
        // Escribir todas las filas ya ordenadas
        foreach ($todasLasGestiones as $gestion) {
            $row = $this->formatearFilaGestionCSV($gestion);
            fputcsv($output, $row, ';');
        }
        
        fclose($output);
        exit;
        
        } catch (Exception $e) {
            // Log del error
            error_log("Error en exportarGestionTodosAsesores: " . $e->getMessage());
            
            // Limpiar cualquier output previo
            if (ob_get_level()) {
                ob_end_clean();
            }
            
            // Configurar headers para descarga CSV de error
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="error_exportacion_equipo.csv"');
            header('Cache-Control: max-age=0');
            
            // Crear archivo CSV con mensaje de error
            $output = fopen('php://output', 'w');
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
            
            $headers = ['Error', 'Mensaje', 'Fecha'];
            fputcsv($output, $headers, ';');
            
            $row = [
                'Error de Exportación del Equipo',
                'Ocurrió un error al exportar los datos del equipo: ' . $e->getMessage(),
                date('Y-m-d H:i:s')
            ];
            fputcsv($output, $row, ';');
            
            fclose($output);
            exit;
        }
    }

    /**
     * Exporta un reporte personalizado a CSV
     */
    public function exportarReportePersonalizado($filtros = []) {
        // Desactivar la salida de errores para evitar que se mezclen con el CSV
        error_reporting(0);
        ini_set('display_errors', 0);
        
        $coordinador_id = $_SESSION['user_id'];
        
        // Aplicar filtros por defecto si no se especifican
        if (empty($filtros['fecha_inicio'])) { $filtros['fecha_inicio'] = date('Y-m-01'); }
        if (empty($filtros['fecha_fin'])) { $filtros['fecha_fin'] = date('Y-m-t'); }
        
        $gestiones = $this->gestionModel->getGestionFiltrada(
            $coordinador_id,
            $filtros['fecha_inicio'],
            $filtros['fecha_fin'],
            $filtros['asesor_id'] ?? null,
            $filtros['resultado'] ?? null,
            $filtros['tipo_gestion'] ?? null
        );
        
        if (empty($gestiones)) {
            $_SESSION['error_message'] = "No hay datos que coincidan con los filtros seleccionados.";
            header('Location: index.php?action=reportes_exportacion');
            exit;
        }
        
        // Generar nombre del archivo
        $filename = "Reporte_Personalizado_{$filtros['fecha_inicio']}_a_{$filtros['fecha_fin']}.csv";
        
        // Limpiar cualquier salida previa
        if (ob_get_level()) {
            ob_end_clean();
        }
        
        // Configurar headers para descarga CSV
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        
        // Crear archivo CSV
        $output = fopen('php://output', 'w');
        
        // BOM para UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Encabezados del CSV con orden correcto (mismo formato que el reporte normal)
        $headers = [
            'Fecha de Gestión',
            'Asesor',
            'Cédula del Cliente',
            'Nombre del Cliente',
            'Nombre de la Base',
            'Teléfono de Contacto',
            'Número de Factura',
            'Franja del Cliente',
            'Forma de Contacto',
            'Tipo de Contacto',
            'Resultado de Contacto',
            'Razón Específica',
            'Fecha de Pago',
            'Monto de Acuerdo',
            'Observaciones',
            'Canales Autorizados',
            'Celular',
            'Celular 2',
            'Celular 3',
            'Celular 4',
            'Celular 5',
            'Celular 6',
            'Celular 7',
            'Celular 8',
            'Celular 9',
            'Celular 10'
        ];
        fputcsv($output, $headers, ';');
        
        // Datos filtrados con orden correcto
        foreach ($gestiones as $gestion) {
            $row = $this->formatearFilaGestionCSV($gestion);
            fputcsv($output, $row, ';');
        }
        
        fclose($output);
        exit;
    }

    // Métodos de estilo y descarga eliminados - Ahora usamos CSV

    /**
     * Muestra la vista de reportes y exportación CSV simplificada
     */
    public function reportesExportacion() {
        $page_title = "Exportación CSV - Gestión del Equipo";
        $coordinador_id = $_SESSION['user_id'];
        
        // Obtener asesores asignados al coordinador
        $asesores = $this->usuarioModel->getAsesoresByCoordinador($coordinador_id);
        
        // #region agent log
        @file_put_contents(__DIR__ . '/../debug-5e8407.log', json_encode([
            'sessionId' => '5e8407',
            'hypothesisId' => 'H1',
            'runId' => 'post-fix',
            'location' => 'CoordinadorController.php:reportesExportacion:before_require',
            'message' => 'asesores antes de incluir vista',
            'data' => [
                'isset_asesores' => isset($asesores),
                'is_array' => is_array($asesores ?? null),
                'count' => is_array($asesores ?? null) ? count($asesores) : null,
            ],
            'timestamp' => (int) round(microtime(true) * 1000),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n", FILE_APPEND);
        // #endregion

        require __DIR__ . '/../views/coordinador_reportes_exportacion_simplificado.php';
    }
    
    /**
     * Exporta la lista de clientes a CSV
     */
    public function exportarClientes($fechaInicio = null, $fechaFin = null, $estadoCliente = null) {
        // Desactivar la salida de errores para evitar que se mezclen con el CSV
        error_reporting(0);
        ini_set('display_errors', 0);
        
        $coordinador_id = $_SESSION['user_id'];
        
        // Si no se especifican fechas, usar el mes actual
        if (!$fechaInicio) { $fechaInicio = date('Y-m-01'); }
        if (!$fechaFin) { $fechaFin = date('Y-m-t'); }
        
        // Obtener clientes del coordinador con filtros
        $clientes = $this->clienteModel->getClientesByCoordinadorWithFilters(
            $coordinador_id, 
            $fechaInicio, 
            $fechaFin, 
            $estadoCliente
        );
        
        if (empty($clientes)) {
            // En lugar de redirigir, crear un CSV vacío con mensaje
            $filename = "Clientes_Coordinador_{$fechaInicio}_a_{$fechaFin}_SIN_DATOS.csv";
            
            // Limpiar cualquier salida previa
            if (ob_get_level()) {
                ob_end_clean();
            }
            
            // Configurar headers para descarga CSV
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Cache-Control: max-age=0');
            
            // Crear archivo CSV
            $output = fopen('php://output', 'w');
            
            // BOM para UTF-8
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Encabezados del CSV
            $headers = [
                'ID Cliente',
                'Nombre',
                'Cédula',
                'Teléfono',
                'Celular',
                'Email',
                'Ciudad',
                'Estado Cliente',
                'Asesor Asignado',
                'Fecha Creación',
                'Carga Excel'
            ];
            fputcsv($output, $headers);
            
            // Agregar mensaje de que no hay datos
            $row = [
                'No hay clientes para exportar con los filtros seleccionados',
                'Período: ' . $fechaInicio . ' a ' . $fechaFin,
                'Estado: ' . ($estadoCliente ?: 'Todos los estados'),
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                ''
            ];
            fputcsv($output, $row);
            
            fclose($output);
            exit;
        }
        
        // Generar nombre del archivo
        $filename = "Clientes_Coordinador_{$fechaInicio}_a_{$fechaFin}.csv";
        
        // Limpiar cualquier salida previa
        if (ob_get_level()) {
            ob_end_clean();
        }
        
        // Configurar headers para descarga CSV
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        
        // Crear archivo CSV
        $output = fopen('php://output', 'w');
        
        // BOM para UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Encabezados del CSV
        $headers = [
            'ID Cliente',
            'Nombre',
            'Cédula',
            'Teléfono',
            'Celular',
            'Email',
            'Ciudad',
            'Estado Cliente',
            'Asesor Asignado',
            'Fecha Creación',
            'Carga Excel'
        ];
        fputcsv($output, $headers);
        
        // Datos de los clientes
        foreach ($clientes as $cliente) {
            $asesor = $this->usuarioModel->getUsuarioById($cliente['asesor_id']);
            $asesorNombre = $asesor ? $asesor['nombre_completo'] : 'Sin asignar';
            
            $row = [
                $this->limpiarDatoCSV($cliente['id']),
                $this->limpiarDatoCSV($cliente['nombre']),
                $this->limpiarDatoCSV($cliente['cedula']),
                $this->limpiarDatoCSV($cliente['telefono']),
                $this->limpiarDatoCSV($cliente['celular2'] ?? ''),
                $this->limpiarDatoCSV($cliente['email'] ?? ''),
                $this->limpiarDatoCSV($cliente['ciudad'] ?? ''),
                $this->limpiarDatoCSV($cliente['estado_cliente']),
                $this->limpiarDatoCSV($asesorNombre),
                $this->limpiarDatoCSV($cliente['fecha_creacion']),
                $this->limpiarDatoCSV($cliente['carga_excel_id'])
            ];
            fputcsv($output, $row);
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Exporta la información de cargas de Excel a CSV
     */
    public function exportarCargas($estadoCarga = null) {
        $coordinador_id = $_SESSION['user_id'];
        
        // Obtener cargas del coordinador
        $cargas = $this->cargaExcelModel->getCargasByCoordinador($coordinador_id);
        
        if (empty($cargas)) {
            $_SESSION['error_message'] = "No hay cargas para exportar.";
            header('Location: index.php?action=reportes_exportacion');
            exit;
        }
        
        // Filtrar por estado si se especifica
        if ($estadoCarga) {
            $cargas = array_filter($cargas, function($carga) use ($estadoCarga) {
                return $carga['estado'] === $estadoCarga;
            });
        }
        
        if (empty($cargas)) {
            $_SESSION['error_message'] = "No hay cargas con el estado seleccionado.";
            header('Location: index.php?action=reportes_exportacion');
            exit;
        }
        
        // Generar nombre del archivo
        $filename = "Cargas_Excel_Coordinador_" . date('Y-m-d') . ".csv";
        
        // Configurar headers para descarga CSV
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        
        // Crear archivo CSV
        $output = fopen('php://output', 'w');
        
        // BOM para UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Encabezados del CSV
        $headers = [
            'ID Carga',
            'Nombre Cargue',
            'Fecha Cargue',
            'Estado',
            'Total Clientes',
            'Clientes Asignados',
            'Clientes Pendientes',
            'Coordinador'
        ];
        fputcsv($output, $headers);
        
        // Datos de las cargas
        foreach ($cargas as $carga) {
            // Calcular estadísticas para cada carga
            $totalClientes = $this->clienteModel->getTotalClientsByCargaIdAndCoordinador($carga['id'], $coordinador_id);
            $clientesAsignados = $this->clienteModel->getTotalClientsAsignadosByCargaIdAndCoordinador($carga['id'], $coordinador_id);
            $clientesPendientes = $totalClientes - $clientesAsignados;
            
            $row = [
                $carga['id'],
                $carga['nombre_cargue'],
                $carga['fecha_cargue'],
                $carga['estado'],
                $totalClientes,
                $clientesAsignados,
                $clientesPendientes,
                'Coordinador ID: ' . $carga['usuario_coordinador_id']
            ];
            fputcsv($output, $row);
        }
        
        fclose($output);
        exit;
    }

    public function resultadosEquipo() {
        // #region agent log d200d9 acuerdos_pago
        $dbgPath = __DIR__ . '/../debug-d200d9.log';
        $dbg = function($hypothesisId, $location, $message, $data = []) use ($dbgPath) {
            try {
                @file_put_contents($dbgPath, json_encode([
                    'sessionId' => 'd200d9',
                    'runId' => 'post-fix',
                    'hypothesisId' => $hypothesisId,
                    'location' => $location,
                    'message' => $message,
                    'data' => $data,
                    'timestamp' => (int) round(microtime(true) * 1000),
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n", FILE_APPEND | LOCK_EX);
            } catch (Throwable $e) {}
        };
        $dbg('C1', 'controllers/CoordinadorController.php:resultadosEquipo:entry', 'entry', [
            'has_user_id' => isset($_SESSION['user_id']) ? 1 : 0,
            'user_id_suffix' => substr((string)($_SESSION['user_id'] ?? ''), -3),
        ]);
        // #endregion
        $page_title = "Resultados del Equipo";
        $coordinador_id = $_SESSION['user_id'];

        // Asesores del equipo (cédula en campo id por mapUsuarioRow)
        $asesores = $this->usuarioModel->getAsesoresByCoordinador($coordinador_id);
        // #region agent log d200d9 acuerdos_pago
        $dbg('C2', 'controllers/CoordinadorController.php:resultadosEquipo:asesores', 'asesores cargados', [
            'count' => is_array($asesores) ? count($asesores) : -1,
        ]);
        // #endregion

        foreach ($asesores as $key => $asesor) {
            $asesores[$key]['total_clientes'] = $this->clienteModel->getTotalClientesByAsesor($asesor['id']);
            // Misma ventana que tipificaciones del mes (rango calendario mes actual)
            $asesores[$key]['llamadas_realizadas'] = $this->gestionModel->getTotalLlamadasByAsesor($asesor['id'], 'mes');
            $asesores[$key]['acuerdos_mes'] = $this->gestionModel->getTotalAcuerdosByAsesor($asesor['id'], 'mes');
            // #region agent log d200d9 acuerdos_pago
            if ($key < 3) {
                $dbg('C3', 'controllers/CoordinadorController.php:resultadosEquipo:per_asesor', 'acuerdos_mes por asesor (muestra)', [
                    'idx' => (int)$key,
                    'asesor_id_suffix' => substr((string)($asesor['id'] ?? ''), -3),
                    'acuerdos_mes' => (int)($asesores[$key]['acuerdos_mes'] ?? -1),
                    'llamadas_mes' => (int)($asesores[$key]['llamadas_realizadas'] ?? -1),
                ]);
            }
            // #endregion

            if ($asesores[$key]['total_clientes'] > 0) {
                $asesores[$key]['porcentaje_llamadas'] = round(
                    ($asesores[$key]['llamadas_realizadas'] / $asesores[$key]['total_clientes']) * 100,
                    1
                );
            } else {
                $asesores[$key]['porcentaje_llamadas'] = 0;
            }

            $asesores[$key]['tipificaciones'] = $this->gestionModel->getTipificacionesPorResultado($asesor['id'], 'mes');
            $asesores[$key]['estadisticas_ventas'] = $this->gestionModel->getEstadisticasPorTipoVenta($asesor['id'], 'mes');
            $asesores[$key]['estadisticas_rechazos'] = $this->gestionModel->getEstadisticasPorRechazo($asesor['id'], 'mes');
        }

        $total_asesores = count($asesores);
        $total_clientes = array_sum(array_column($asesores, 'total_clientes'));
        $total_llamadas = array_sum(array_column($asesores, 'llamadas_realizadas'));
        $total_acuerdos = array_sum(array_column($asesores, 'acuerdos_mes'));
        // #region agent log d200d9 acuerdos_pago
        $dbg('C4', 'controllers/CoordinadorController.php:resultadosEquipo:totales', 'totales calculados', [
            'total_asesores' => (int)$total_asesores,
            'total_llamadas' => (int)$total_llamadas,
            'total_acuerdos' => (int)$total_acuerdos,
        ]);
        // #endregion

        $porcentajes_llamadas = array_column($asesores, 'porcentaje_llamadas');
        $promedio_cumplimiento = count($porcentajes_llamadas) > 0
            ? round(array_sum($porcentajes_llamadas) / count($porcentajes_llamadas), 1)
            : 0;

        require __DIR__ . '/../views/coordinador_resultados_equipo.php';
    }


    /**
     * Obtiene los detalles completos de un asesor para mostrar en modal
     * CORREGIDO para usar los nuevos métodos de métricas
     */
    public function getDetallesAsesor() {
        try {
            if (ob_get_level()) {
                ob_clean();
            }

            if (!isset($_GET['asesor_id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'ID de asesor no proporcionado']);
                return;
            }

            $asesor_cedula = $this->sanitizeAsesorCedulaParam($_GET['asesor_id']);
            if ($asesor_cedula === null) {
                http_response_code(400);
                echo json_encode(['error' => 'Identificador de asesor no válido']);
                return;
            }

            $coordinador_id = $_SESSION['user_id'];

            $asesoresEquipo = $this->usuarioModel->getAsesoresByCoordinador($coordinador_id);
            $asesor = null;
            foreach ($asesoresEquipo as $a) {
                if ((string)($a['id'] ?? '') === $asesor_cedula) {
                    $asesor = $a;
                    break;
                }
            }

            if (!$asesor) {
                http_response_code(403);
                echo json_encode(['error' => 'No tienes permisos para ver este asesor']);
                return;
            }

            $fecha_inicio = $this->getGet('fecha_inicio');
            $fecha_fin = $this->getGet('fecha_fin');
            $filtro_gestion = $this->getGet('gestion');
            $filtro_contacto = $this->getGet('contacto');
            $filtro_tipificacion = $this->getGet('tipificacion');
            $filtro_tipificacion_especifica = $this->getGet('tipificacion_especifica');
            $busqueda = $this->getGet('busqueda');

            if ($filtro_gestion === null || $filtro_gestion === '') {
                $filtro_gestion = 'gestionado';
            }

            $filtros = [
                'fecha_inicio' => $fecha_inicio,
                'fecha_fin' => $fecha_fin,
                'gestion' => $filtro_gestion,
                'contacto' => $filtro_contacto,
                'tipificacion' => $filtro_tipificacion,
                'tipificacion_especifica' => $filtro_tipificacion_especifica,
                'busqueda' => $busqueda,
            ];

            $gestiones = $this->gestionModel->getGestionByAsesorAndFechasConFiltros(
                $asesor_cedula,
                $fecha_inicio,
                $fecha_fin,
                $filtros
            );

            if (!is_array($gestiones)) {
                $gestiones = [];
            }

            foreach ($gestiones as $key => $gestion) {
                $gid = (int)($gestion['id_gestion'] ?? $gestion['id'] ?? 0);
                if ($gid > 0) {
                    $observaciones = $this->gestionModel->getObservacionesGestion($gid);
                    $gestiones[$key]['observaciones'] = is_array($observaciones)
                        ? ($observaciones['comentarios'] ?? '')
                        : '';
                    $gestiones[$key]['proxima_fecha'] = is_array($observaciones)
                        ? ($observaciones['proxima_fecha'] ?? '')
                        : '';
                    $gestiones[$key]['proxima_hora'] = is_array($observaciones)
                        ? ($observaciones['proxima_hora'] ?? '')
                        : '';
                } else {
                    $gestiones[$key]['observaciones'] = '';
                    $gestiones[$key]['proxima_fecha'] = '';
                    $gestiones[$key]['proxima_hora'] = '';
                }
            }

            $estadisticas = $this->gestionModel->getMetricasAsesor($asesor_cedula, 'total');

            if (!is_array($estadisticas)) {
                $estadisticas = [
                    'total_clientes' => 0,
                    'total_gestiones' => 0,
                    'ventas_exitosas' => 0,
                    'tasa_conversion' => 0,
                    'contactos_efectivos' => 0,
                    'tiempo_promedio_conversacion' => 0,
                    'total_ventas_monto' => 0,
                    'promedio_venta' => 0,
                ];
            }

            $totalAsignados = (int)$this->clienteModel->getTotalClientesByAsesor($asesor_cedula);
            $estadisticas['total_clientes'] = $totalAsignados;

            if ($estadisticas['total_clientes'] > 0) {
                $estadisticas['porcentaje_llamadas'] = round(
                    ($estadisticas['total_gestiones'] / $estadisticas['total_clientes']) * 100,
                    1
                );
            } else {
                $estadisticas['porcentaje_llamadas'] = 0;
            }

            $response = [
                'asesor' => $asesor,
                'clientes' => $gestiones,
                'estadisticas' => $estadisticas,
                'metricas' => [
                    'clientes_filtrados' => count($gestiones),
                    'total_gestionados' => $estadisticas['total_gestiones'] ?? 0,
                    'total_asignados' => $totalAsignados,
                    'porcentaje' => $totalAsignados > 0
                        ? round((count($gestiones) / $totalAsignados) * 100, 1)
                        : 0,
                ],
                'filtros' => [
                    'gestion' => $filtro_gestion ?? 'todos',
                    'tipificacion_especifica' => $filtro_tipificacion_especifica ?? 'todos',
                ],
            ];

            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            error_log("Error in getDetallesAsesor: " . $e->getMessage());
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'Error interno del servidor: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
    }
    
    /**
     * Muestra la vista de descargas y reportes del coordinador
     */
    public function descargas() {
        $page_title = "Descargas y Reportes";
        $coordinador_id = $_SESSION['user_id'];
        
        // Obtener asesores para los filtros
        $asesores = $this->usuarioModel->getAsesoresByCoordinador($coordinador_id);
        
        // Calcular estadísticas generales
        $total_asesores = count($asesores);
        $total_clientes = 0;
        $total_gestiones = 0;
        $total_acuerdos = 0;
        
        foreach ($asesores as $asesor) {
            $total_clientes += $this->clienteModel->getTotalClientesByAsesor($asesor['id']);
            $total_gestiones += $this->gestionModel->getTotalLlamadasByAsesor($asesor['id'], 'total');
            $total_acuerdos += $this->gestionModel->getTotalAcuerdosByAsesor($asesor['id'], 'total');
        }
        
        require __DIR__ . '/../views/coordinador_descargas.php';
    }

    /**
     * Muestra la vista del reporte TMO (Tiempo de Sesión y Pausas)
     */
    public function reporteTMO() {
        $page_title = "Reporte TMO - Tiempo de Sesión y Pausas";
        
        // Obtener TODOS los asesores activos (no solo los asignados al coordinador)
        $asesores = $this->usuarioModel->getAsesores();
        
        // Obtener filtros de fechas
        $fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-d', strtotime('-30 days'));
        $fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d');
        $asesor_id = $_GET['asesor_id'] ?? '';
        
        require __DIR__ . '/../views/coordinador_reporte_tmo.php';
    }

    /**
     * Exporta el reporte TMO en formato CSV
     */
    public function exportarReporteTMO() {
        // IMPORTANTE: Limpiar TODOS los niveles de output buffering
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        try {
            // Desactivar la salida de errores para evitar que se mezclen con el CSV
            error_reporting(0);
            ini_set('display_errors', 0);
            
            // Verificar que la sesión esté iniciada
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            
            // Verificar que el usuario tenga el rol correcto
            if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'coordinador') {
                throw new Exception('Acceso denegado. Se requiere rol de coordinador.');
            }
            
            // Obtener filtros
            $fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-d', strtotime('-30 days'));
            $fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d');
            $asesor_id = $_GET['asesor_id'] ?? '';
        
        // Detectar qué columna usa la tabla (usuario_id o asesor_id)
        $sqlCheckColumn = "SHOW COLUMNS FROM sesiones_trabajo LIKE 'asesor_id'";
        $stmtCheck = $this->pdo->query($sqlCheckColumn);
        $usaAsesorId = $stmtCheck->rowCount() > 0;
        $columnaAsesor = $usaAsesorId ? 'asesor_id' : 'usuario_id';
        
        // Verificar qué columnas de tiempo existen
        $sqlCheckTiempo = "SHOW COLUMNS FROM sesiones_trabajo";
        $stmtCheckTiempo = $this->pdo->query($sqlCheckTiempo);
        $columnasTiempo = $stmtCheckTiempo->fetchAll(PDO::FETCH_COLUMN);
        $tieneTiempoSegundos = in_array('tiempo_total_segundos', $columnasTiempo);
        $tieneTiempoMinutos = in_array('tiempo_total_minutos', $columnasTiempo);
        $tieneDuracionMinutos = in_array('duracion_minutos', $columnasTiempo);
        $tieneEstado = in_array('estado', $columnasTiempo);
        
        // Construir consulta adaptada a la estructura real de la base de datos
        $sql = "SELECT 
                    s.id as sesion_id,
                    s.$columnaAsesor as asesor_id,
                    u.nombre_completo as asesor_nombre,
                    u.cedula as asesor_cedula,
                    s.fecha_inicio,
                    s.fecha_fin,";
        
        // Agregar columnas de tiempo según lo que exista
        if ($tieneTiempoSegundos) {
            $sql .= " s.tiempo_total_segundos,";
        } else {
            $sql .= " NULL as tiempo_total_segundos,";
        }
        
        if ($tieneTiempoMinutos) {
            $sql .= " s.tiempo_total_minutos,";
        } elseif ($tieneDuracionMinutos) {
            $sql .= " s.duracion_minutos as tiempo_total_minutos,";
        } else {
            $sql .= " NULL as tiempo_total_minutos,";
        }
        
        if ($tieneEstado) {
            $sql .= " s.estado";
        } else {
            $sql .= " CASE WHEN s.fecha_fin IS NULL THEN 'activa' ELSE 'finalizada' END as estado";
        }
        
        $sql .= " FROM sesiones_trabajo s
                INNER JOIN usuarios u ON s.$columnaAsesor = u.id
                WHERE u.rol = 'asesor'
                AND DATE(s.fecha_inicio) BETWEEN ? AND ?";
        
        $params = [$fecha_inicio, $fecha_fin];
        
        // Filtrar por asesor específico si se seleccionó
        if (!empty($asesor_id)) {
            $sql .= " AND s.$columnaAsesor = ?";
            $params[] = $asesor_id;
        }
        
        $sql .= " ORDER BY s.fecha_inicio DESC, u.nombre_completo";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $sesiones = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Generar nombre del archivo
        $filename = "Reporte_TMO_{$fecha_inicio}_a_{$fecha_fin}.csv";
        
        // IMPORTANTE: Asegurarse de que no haya output antes de los headers
        // Limpiar cualquier salida previa nuevamente
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        // Configurar headers para descarga CSV (ANTES de cualquier output)
        // Estos headers DEBEN enviarse antes de cualquier contenido
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Crear archivo CSV
        $output = fopen('php://output', 'w');
        
        // BOM para UTF-8 (igual que otros exportes)
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Encabezados del CSV (estructura simplificada)
        $headers = [
            'Fecha y Hora Inicio Sesión',
            'Asesor',
            'Hora Fin Sesión',
            'Tiempo en Sesión (HH:MM:SS)',
            'Motivo',
            'Duración Pausa (HH:MM:SS)'
        ];
        fputcsv($output, $headers);
        
        // Mapear tipos de break (definir antes de usarlo)
        $tiposBreak = [
            'baño' => 'Baño',
            'almuerzo' => 'Almuerzo',
            'break' => 'Break',
            'mantenimiento' => 'Mantenimiento',
            'actividad_extra' => 'Actividad Extra',
            'pausa_activa' => 'Pausa Activa'
        ];
        
        // Verificar qué columnas tiene la tabla breaks_asesor
        $sqlCheckBreaks = "SHOW COLUMNS FROM breaks_asesor";
        $stmtCheckBreaks = $this->pdo->query($sqlCheckBreaks);
        $columnasBreaks = $stmtCheckBreaks->fetchAll(PDO::FETCH_COLUMN);
        $tieneSesionTrabajoId = in_array('sesion_trabajo_id', $columnasBreaks);
        $tieneDuracionSegundos = in_array('duracion_segundos', $columnasBreaks);
        $tieneEstadoBreak = in_array('estado', $columnasBreaks);
        
        // Obtener TODOS los breaks en el rango de fechas (independientemente de si tienen sesión o no)
        $sqlTodosBreaks = "SELECT 
                            b.id,
                            b.tipo,
                            b.fecha_inicio,
                            b.fecha_fin,";
        
        // Agregar columnas según lo que exista
        if ($tieneDuracionSegundos) {
            $sqlTodosBreaks .= " b.duracion_segundos,";
        } else {
            $sqlTodosBreaks .= " NULL as duracion_segundos,";
        }
        
        $sqlTodosBreaks .= " b.duracion_minutos,";
        
        if ($tieneEstadoBreak) {
            $sqlTodosBreaks .= " b.estado,";
        } else {
            $sqlTodosBreaks .= " CASE WHEN b.fecha_fin IS NULL THEN 'activo' ELSE 'finalizado' END as estado,";
        }
        
        $sqlTodosBreaks .= " b.asesor_id,";
        
        if ($tieneSesionTrabajoId) {
            $sqlTodosBreaks .= " b.sesion_trabajo_id,";
        } else {
            $sqlTodosBreaks .= " NULL as sesion_trabajo_id,";
        }
        
        $sqlTodosBreaks .= " u.nombre_completo as asesor_nombre,
                            u.cedula as asesor_cedula
                         FROM breaks_asesor b
                         INNER JOIN usuarios u ON b.asesor_id = u.id
                         WHERE u.rol = 'asesor'
                         AND DATE(b.fecha_inicio) BETWEEN ? AND ?";
        
        $paramsBreaks = [$fecha_inicio, $fecha_fin];
        
        // Filtrar por asesor específico si se seleccionó
        if (!empty($asesor_id)) {
            $sqlTodosBreaks .= " AND b.asesor_id = ?";
            $paramsBreaks[] = $asesor_id;
        }
        
        $sqlTodosBreaks .= " ORDER BY b.fecha_inicio, b.asesor_id";
        
        $stmtTodosBreaks = $this->pdo->prepare($sqlTodosBreaks);
        $stmtTodosBreaks->execute($paramsBreaks);
        $todosBreaks = $stmtTodosBreaks->fetchAll(PDO::FETCH_ASSOC);
        
        // Crear un mapa de sesiones por ID para acceso rápido
        $sesionesMap = [];
        foreach ($sesiones as $sesion) {
            $sesionesMap[$sesion['sesion_id']] = $sesion;
        }
        
        // Crear un mapa de breaks por sesion_trabajo_id
        $breaksPorSesion = [];
        $breaksSinSesion = [];
        
        foreach ($todosBreaks as $break) {
            if (!empty($break['sesion_trabajo_id']) && isset($sesionesMap[$break['sesion_trabajo_id']])) {
                // Break tiene sesión relacionada
                if (!isset($breaksPorSesion[$break['sesion_trabajo_id']])) {
                    $breaksPorSesion[$break['sesion_trabajo_id']] = [];
                }
                $breaksPorSesion[$break['sesion_trabajo_id']][] = $break;
            } else {
                // Break sin sesión relacionada - intentar relacionarlo por fecha
                $breakRelacionado = false;
                foreach ($sesiones as $sesion) {
                    if ($break['asesor_id'] == $sesion['asesor_id']) {
                        $fechaInicioSesion = new DateTime($sesion['fecha_inicio']);
                        $fechaFinSesion = $sesion['fecha_fin'] ? new DateTime($sesion['fecha_fin']) : new DateTime();
                        $fechaInicioBreak = new DateTime($break['fecha_inicio']);
                        
                        if ($fechaInicioBreak >= $fechaInicioSesion && $fechaInicioBreak <= $fechaFinSesion) {
                            // El break ocurrió durante esta sesión
                            if (!isset($breaksPorSesion[$sesion['sesion_id']])) {
                                $breaksPorSesion[$sesion['sesion_id']] = [];
                            }
                            $breaksPorSesion[$sesion['sesion_id']][] = $break;
                            $breakRelacionado = true;
                            break;
                        }
                    }
                }
                
                if (!$breakRelacionado) {
                    // Break sin sesión relacionada - se mostrará independientemente
                    $breaksSinSesion[] = $break;
                }
            }
        }
        
        // Procesar cada sesión con sus breaks
        foreach ($sesiones as $sesion) {
            // Obtener breaks de esta sesión
            $breaks = $breaksPorSesion[$sesion['sesion_id']] ?? [];
            
            // Calcular duración total de sesión en HH:MM:SS
            $duracionSesionSegundos = (int)($sesion['tiempo_total_segundos'] ?? 0);
            if ($duracionSesionSegundos == 0 && $sesion['fecha_fin']) {
                $fechaInicio = new DateTime($sesion['fecha_inicio']);
                $fechaFin = new DateTime($sesion['fecha_fin']);
                $duracionSesionSegundos = $fechaFin->getTimestamp() - $fechaInicio->getTimestamp();
            }
            $horasSesion = floor($duracionSesionSegundos / 3600);
            $minutosSesion = floor(($duracionSesionSegundos % 3600) / 60);
            $segundosSesion = $duracionSesionSegundos % 60;
            $duracionSesionFormateada = sprintf("%02d:%02d:%02d", $horasSesion, $minutosSesion, $segundosSesion);
            
            // Formatear fecha y hora de inicio (combinadas)
            $fechaHoraInicioSesion = $sesion['fecha_inicio'] ? date('Y-m-d H:i:s', strtotime($sesion['fecha_inicio'])) : '';
            
            // Formatear hora de fin (solo hora)
            $horaFinSesion = $sesion['fecha_fin'] ? date('H:i:s', strtotime($sesion['fecha_fin'])) : '';
            
            if (count($breaks) > 0) {
                // Si hay breaks, crear una fila por cada break
                foreach ($breaks as $break) {
                    // Calcular duración del break en HH:MM:SS
                    $duracionBreakSegundos = (int)($break['duracion_segundos'] ?? 0);
                    if ($duracionBreakSegundos == 0 && $break['fecha_fin']) {
                        $fechaInicioBreak = new DateTime($break['fecha_inicio']);
                        $fechaFinBreak = new DateTime($break['fecha_fin']);
                        $duracionBreakSegundos = $fechaFinBreak->getTimestamp() - $fechaInicioBreak->getTimestamp();
                    }
                    $horasBreak = floor($duracionBreakSegundos / 3600);
                    $minutosBreak = floor(($duracionBreakSegundos % 3600) / 60);
                    $segundosBreak = $duracionBreakSegundos % 60;
                    $duracionBreakFormateada = sprintf("%02d:%02d:%02d", $horasBreak, $minutosBreak, $segundosBreak);
                    
                    $row = [
                        $fechaHoraInicioSesion,
                        $sesion['asesor_nombre'],
                        $horaFinSesion,
                        $duracionSesionFormateada,
                        $tiposBreak[$break['tipo']] ?? $break['tipo'],
                        $duracionBreakFormateada
                    ];
                    fputcsv($output, $row);
                }
            } else {
                // Si no hay breaks, crear una fila solo con la sesión
                $row = [
                    $fechaHoraInicioSesion,
                    $sesion['asesor_nombre'],
                    $horaFinSesion,
                    $duracionSesionFormateada,
                    'Sin pausas',
                    '00:00:00'
                ];
                fputcsv($output, $row);
            }
        }
        
        // Procesar breaks sin sesión relacionada (mostrarlos independientemente)
        foreach ($breaksSinSesion as $break) {
            // Calcular duración del break en HH:MM:SS
            $duracionBreakSegundos = (int)($break['duracion_segundos'] ?? 0);
            if ($duracionBreakSegundos == 0 && $break['fecha_fin']) {
                $fechaInicioBreak = new DateTime($break['fecha_inicio']);
                $fechaFinBreak = new DateTime($break['fecha_fin']);
                $duracionBreakSegundos = $fechaFinBreak->getTimestamp() - $fechaInicioBreak->getTimestamp();
            }
            $horasBreak = floor($duracionBreakSegundos / 3600);
            $minutosBreak = floor(($duracionBreakSegundos % 3600) / 60);
            $segundosBreak = $duracionBreakSegundos % 60;
            $duracionBreakFormateada = sprintf("%02d:%02d:%02d", $horasBreak, $minutosBreak, $segundosBreak);
            
            // Para breaks sin sesión, usar la fecha de inicio del break como referencia
            $fechaHoraInicioBreak = $break['fecha_inicio'] ? date('Y-m-d H:i:s', strtotime($break['fecha_inicio'])) : '';
            $horaFinBreak = $break['fecha_fin'] ? date('H:i:s', strtotime($break['fecha_fin'])) : '';
            
            // Calcular tiempo de "sesión" desde el inicio del break hasta el fin (o ahora si está activo)
            $fechaInicioBreak = new DateTime($break['fecha_inicio']);
            $fechaFinBreak = $break['fecha_fin'] ? new DateTime($break['fecha_fin']) : new DateTime();
            $duracionSesionBreakSegundos = $fechaFinBreak->getTimestamp() - $fechaInicioBreak->getTimestamp();
            $horasSesionBreak = floor($duracionSesionBreakSegundos / 3600);
            $minutosSesionBreak = floor(($duracionSesionBreakSegundos % 3600) / 60);
            $segundosSesionBreak = $duracionSesionBreakSegundos % 60;
            $duracionSesionBreakFormateada = sprintf("%02d:%02d:%02d", $horasSesionBreak, $minutosSesionBreak, $segundosSesionBreak);
            
            $row = [
                $fechaHoraInicioBreak,
                $break['asesor_nombre'],
                $horaFinBreak,
                $duracionSesionBreakFormateada,
                $tiposBreak[$break['tipo']] ?? $break['tipo'],
                $duracionBreakFormateada
            ];
            fputcsv($output, $row);
        }
        
        fclose($output);
        exit;
        
        } catch (Exception $e) {
            // En caso de error, limpiar output y redirigir
            while (ob_get_level()) {
                ob_end_clean();
            }
            
            // Log del error completo
            error_log("Error al exportar reporte TMO: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            
            // Verificar que la sesión esté iniciada antes de usar $_SESSION
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            
            $_SESSION['error_message'] = "Error al generar el reporte TMO: " . $e->getMessage() . ". Por favor, intenta nuevamente.";
            header('Location: index.php?action=reporte_tmo');
            exit;
        }
    }

    /**
     * Muestra la lista de clientes de una carga específica
     */
    public function verClientes() {
        $page_title = "Clientes de la Carga";
        $coordinador_id = $_SESSION['user_id'];
        $carga_id = $this->getGet('carga_id');
        
        if (!$carga_id) {
            header('Location: index.php?action=list_cargas');
            exit;
        }
        
        // Verificar que la carga pertenezca al coordinador
        $carga = $this->cargaExcelModel->getCargaByIdAndCoordinador($carga_id, $coordinador_id);
        if (!$carga) {
            header('Location: index.php?action=list_cargas');
            exit;
        }
        
        // Obtener todos los clientes de la carga
        $clientes = $this->clienteModel->getUnassignedClientsByCargaAndCoordinador($carga_id, $coordinador_id);
        $total_clientes = count($clientes);
        
        // Para la vista inicial, mostrar los primeros 200 clientes
        $clientes_vista = array_slice($clientes, 0, 200);
        
        // Obtener asesores para asignación
        $asesores = $this->usuarioModel->getAsesoresByCoordinador($coordinador_id);
        
        require __DIR__ . '/../views/coordinador_ver_clientes.php';
    }

    /**
     * Busca clientes por término de búsqueda (AJAX)
     */
    public function buscarClientes() {
        // Limpiar cualquier salida previa
        if (ob_get_level()) {
            if (ob_get_level()) ob_clean();
        }
        
        // Establecer headers para JSON solo si no se han enviado headers
        if (!headers_sent()) {
            header('Content-Type: application/json');
        }
        
        try {
            $coordinador_id = $_SESSION['user_id'] ?? null;
            $carga_id = $this->getGet('carga_id');
            $search_term = $this->getGet('search');
            
            // Log para debugging
            error_log("buscarClientes - Coordinador ID: " . $coordinador_id);
            error_log("buscarClientes - Carga ID: " . $carga_id);
            error_log("buscarClientes - Search term: " . $search_term);
            
            if (!$coordinador_id) {
                echo json_encode(['success' => false, 'error' => 'No hay sesión activa']);
                exit;
            }
            
            if (!$carga_id) {
                echo json_encode(['success' => false, 'error' => 'Carga no especificada']);
                exit;
            }
            
            // Verificar que la carga pertenezca al coordinador
            $carga = $this->cargaExcelModel->getCargaByIdAndCoordinador($carga_id, $coordinador_id);
            if (!$carga) {
                echo json_encode(['success' => false, 'error' => 'No tienes acceso a esta carga']);
                exit;
            }
            
            // Buscar clientes
            $clientes = $this->clienteModel->buscarClientesPorTermino($carga_id, $coordinador_id, $search_term);
            
            // Preparar datos para la respuesta
            $resultados = [];
            foreach ($clientes as $cliente) {
                $resultados[] = [
                    'id' => $cliente['id'],
                    'nombre' => $cliente['nombre'] ?? 'N/A',
                    'cedula' => $cliente['cedula'] ?? 'N/A',
                    'telefono' => $cliente['telefono'] ?? 'N/A',
                    'celular' => $cliente['celular2'] ?? 'N/A',
                    'email' => $cliente['email'] ?? 'N/A',
                    'estado' => isset($cliente['asesor_id']) && $cliente['asesor_id'] ? 'Asignado' : 'Pendiente'
                ];
            }
            
            $response = [
                'success' => true,
                'clientes' => $resultados,
                'total' => count($resultados),
                'termino' => $search_term
            ];
            
            error_log("buscarClientes - Respuesta: " . json_encode($response));
            echo json_encode($response);
            exit; // Terminar la ejecución después de enviar la respuesta
            
        } catch (Exception $e) {
            error_log("Error en buscarClientes: " . $e->getMessage());
            echo json_encode(['success' => false, 'error' => 'Error interno del servidor']);
            exit; // Terminar la ejecución después de enviar el error
        }
    }

    public function obtenerObligacionesCliente() {
        if (ob_get_level()) ob_clean();
        if (!headers_sent()) header('Content-Type: application/json');

        $logPath = __DIR__ . '/../debug-d200d9.log';
        $writeLog = function($payload) use ($logPath) {
            try { @file_put_contents($logPath, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n", FILE_APPEND); } catch (Throwable $e) {}
        };

        try {
            $coordinador_id = $_SESSION['user_id'] ?? '';
            $carga_id = (int)($this->getGet('carga_id') ?? 0);
            $cliente_id = (int)($this->getGet('cliente_id') ?? 0);

            $writeLog([
                'sessionId' => 'd200d9',
                'runId' => 'pre-modal',
                'hypothesisId' => 'M2',
                'location' => 'controllers/CoordinadorController.php:obtenerObligacionesCliente:entry',
                'message' => 'request',
                'data' => ['carga_id' => $carga_id, 'cliente_id' => $cliente_id, 'coordinadorLen' => strlen((string)$coordinador_id)],
                'timestamp' => (int) round(microtime(true) * 1000),
            ]);

            if ($coordinador_id === '' || $carga_id <= 0 || $cliente_id <= 0) {
                echo json_encode(['success' => false, 'error' => 'Parámetros inválidos']);
                exit;
            }

            $carga = $this->cargaExcelModel->getCargaByIdAndCoordinador($carga_id, $coordinador_id);
            if (!$carga) {
                echo json_encode(['success' => false, 'error' => 'No tienes acceso a esta base']);
                exit;
            }

            // Verificar que el cliente pertenezca a esta base
            $stmt = $this->pdo->prepare("SELECT id_cliente FROM clientes WHERE id_cliente = ? AND base_id = ? LIMIT 1");
            $stmt->execute([$cliente_id, $carga_id]);
            $okCliente = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$okCliente) {
                echo json_encode(['success' => false, 'error' => 'Cliente no pertenece a esta base']);
                exit;
            }

            $obligaciones = $this->facturacionModel->getFacturasByClienteId($cliente_id, $carga_id);
            $rows = array_map(function($o) {
                return [
                    'id_obligacion' => $o['id_obligacion'] ?? null,
                    'numero_factura' => $o['numero_factura'] ?? null,
                    'rmt' => $o['rmt'] ?? null,
                    'numero_contrato' => $o['numero_contrato'] ?? null,
                    'saldo' => $o['saldo'] ?? null,
                    'dias_mora' => $o['dias_mora'] ?? null,
                    'franja' => $o['franja'] ?? null,
                ];
            }, (array)$obligaciones);

            echo json_encode(['success' => true, 'obligaciones' => $rows]);
            exit;
        } catch (Throwable $e) {
            $writeLog([
                'sessionId' => 'd200d9',
                'runId' => 'pre-modal',
                'hypothesisId' => 'M4',
                'location' => 'controllers/CoordinadorController.php:obtenerObligacionesCliente:catch',
                'message' => 'exception',
                'data' => ['type' => get_class($e), 'message' => substr((string)$e->getMessage(), 0, 300)],
                'timestamp' => (int) round(microtime(true) * 1000),
            ]);
            echo json_encode(['success' => false, 'error' => 'Error interno del servidor']);
            exit;
        }
    }

    /**
     * Muestra la vista para asignar clientes de una carga específica
     */
    public function asignarClientesVista() {
        $page_title = "Asignar Clientes";
        $coordinador_id = $_SESSION['user_id'];
        $carga_id = $this->getGet('carga_id');
        
        if (!$carga_id) {
            header('Location: index.php?action=list_cargas');
            exit;
        }
        
        // Verificar que la carga pertenezca al coordinador
        $carga = $this->cargaExcelModel->getCargaByIdAndCoordinador($carga_id, $coordinador_id);
        if (!$carga) {
            header('Location: index.php?action=list_cargas');
            exit;
        }
        
        // Obtener clientes pendientes de asignación
        $clientes_pendientes = $this->clienteModel->getUnassignedClientsByCargaAndCoordinador($carga_id, $coordinador_id);
        
        // Obtener asesores disponibles
        $asesores = $this->usuarioModel->getAsesoresByCoordinador($coordinador_id);
        
        require __DIR__ . '/../views/coordinador_asignar_clientes.php';
    }

    /**
     * Muestra la gestión de un asesor específico
     */
    public function verGestionAsesor() {
        $page_title = "Gestión del Asesor";
        $coordinador_id = $_SESSION['user_id'];
        $asesor_id = $this->getGet('asesor_id');
        
        if (!$asesor_id) {
            header('Location: index.php?action=list_cargas');
            exit;
        }
        
        // Verificar que el asesor esté asignado al coordinador
        $asesor = $this->usuarioModel->getUsuarioById($asesor_id);
        if (!$asesor || !$this->usuarioModel->isAsesorAsignadoACoordinador($asesor_id, $coordinador_id)) {
            header('Location: index.php?action=list_cargas');
            exit;
        }
        
        // Obtener métricas del asesor
        $metricas = $this->gestionModel->getMetricasAsesor($asesor_id, 'mes');
        
        // Obtener clientes asignados al asesor
        $clientes = $this->clienteModel->getAssignedClientsForAsesor($asesor_id);
        
        // Obtener gestiones del asesor
        $gestiones = $this->gestionModel->getUltimasGestiones($asesor_id, 50);
        
        require __DIR__ . '/../views/coordinador_ver_gestion_asesor.php';
    }

    /**
     * Libera todos los clientes de un asesor específico
     */
    public function liberarTodosClientes() {
        try {
            // Verificar que sea un coordinador
            if ($_SESSION['user_role'] !== 'coordinador') {
                throw new Exception("Acceso denegado.");
            }
            
            $coordinador_id = $_SESSION['user_id'];
            $asesor_id = $this->getPost('asesor_id');
            
            if (!$asesor_id) {
                throw new Exception("ID de asesor no proporcionado.");
            }
            
            // Verificar que el asesor esté asignado al coordinador
            $asesor = $this->usuarioModel->getUsuarioById($asesor_id);
            if (!$asesor || $asesor['rol'] !== 'asesor') {
                throw new Exception("El asesor especificado no existe o no es válido.");
            }
            
            // Verificar que el asesor esté asignado al coordinador
            if (!$this->usuarioModel->isAsesorAsignadoACoordinador($asesor_id, $coordinador_id)) {
                throw new Exception("El asesor no está asignado a tu coordinación.");
            }
            
            // Obtener todos los clientes del asesor
            $clientes = $this->clienteModel->getClientesByAsesor($asesor_id);
            
            if (empty($clientes)) {
                echo json_encode([
                    'success' => true,
                    'message' => 'El asesor no tiene clientes asignados para liberar.'
                ]);
                return;
            }
            
            // Contador de clientes liberados
            $clientesLiberados = 0;
            $errores = [];
            
            // Liberar cada cliente
            foreach ($clientes as $cliente) {
                try {
                    $resultado = $this->clienteModel->liberarCliente($cliente['id'], $asesor_id);
                    if ($resultado) {
                        $clientesLiberados++;
                    } else {
                        $errores[] = "Error al liberar cliente ID: " . $cliente['id'];
                    }
                } catch (Exception $e) {
                    $errores[] = "Error al liberar cliente ID: " . $cliente['id'] . ": " . $e->getMessage();
                }
            }
            
            // Log de la acción
            error_log("Liberación masiva de clientes - Coordinador ID: {$coordinador_id}, Asesor ID: {$asesor_id}, Clientes liberados: {$clientesLiberados}, Total clientes: " . count($clientes));
            
            if (empty($errores)) {
                echo json_encode([
                    'success' => true,
                    'message' => "Se liberaron exitosamente {$clientesLiberados} clientes del asesor.",
                    'clientes_liberados' => $clientesLiberados,
                    'total_clientes' => count($clientes)
                ]);
            } else {
                echo json_encode([
                    'success' => true,
                    'message' => "Se liberaron {$clientesLiberados} clientes, pero hubo algunos errores.",
                    'clientes_liberados' => $clientesLiberados,
                    'total_clientes' => count($clientes),
                    'errores' => $errores
                ]);
            }
            
        } catch (Exception $e) {
            error_log("Error en liberación masiva de clientes: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Filtra las gestiones según los criterios especificados
     */
    private function filtrarGestiones($gestiones, $filtros) {
        $gestionesFiltradas = $gestiones;
        
        // Filtro de gestión (gestionado, no gestionado)
        if (!empty($filtros['gestion'])) {
            if ($filtros['gestion'] === 'gestionado') {
                $gestionesFiltradas = array_filter($gestionesFiltradas, function($g) {
                    return !empty($g['resultado']);
                });
            } elseif ($filtros['gestion'] === 'no_gestionado') {
                $gestionesFiltradas = array_filter($gestionesFiltradas, function($g) {
                    return empty($g['resultado']);
                });
            }
        }
        
        // Filtro de contacto (contactado, no contactado)
        if (!empty($filtros['contacto'])) {
            if ($filtros['contacto'] === 'contactado') {
                $gestionesFiltradas = array_filter($gestionesFiltradas, function($g) {
                    return !empty($g['resultado']);
                });
            } elseif ($filtros['contacto'] === 'no_contactado') {
                $gestionesFiltradas = array_filter($gestionesFiltradas, function($g) {
                    return empty($g['resultado']);
                });
            }
        }
        
        // Filtro de tipificación específica
        if (!empty($filtros['tipificacion']) && $filtros['tipificacion'] !== 'todos') {
            $gestionesFiltradas = array_filter($gestionesFiltradas, function($g) use ($filtros) {
                return ($g['resultado'] ?? '') === $filtros['tipificacion'];
            });
        }
        
        // Filtro de fechas de creación del cliente
        if (!empty($filtros['fecha_creacion_inicio'])) {
            $gestionesFiltradas = array_filter($gestionesFiltradas, function($g) use ($filtros) {
                return !empty($g['fecha_creacion']) && $g['fecha_creacion'] >= $filtros['fecha_creacion_inicio'];
            });
        }
        
        if (!empty($filtros['fecha_creacion_fin'])) {
            $gestionesFiltradas = array_filter($gestionesFiltradas, function($g) use ($filtros) {
                return !empty($g['fecha_creacion']) && $g['fecha_creacion'] <= $filtros['fecha_creacion_fin'];
            });
        }
        
        return array_values($gestionesFiltradas);
    }
    
    /**
     * Formatea una fila de gestión para exportación CSV
     */
    private function formatearFilaGestionCSV($gestion) {
        // Orden debe coincidir exactamente con los encabezados del CSV:
        // Fecha, Asesor, Cédula, Nombre Cliente, Nombre Base, Teléfono de Contacto, Número Factura, Franja, Forma Contacto,
        // Tipo Contacto, Resultado Contacto, Razón Específica, Fecha Pago, Monto Acuerdo, Observaciones, Canales Autorizados,
        // Celular, Celular2..Celular10
        return [
            $this->limpiarDatoCSV($gestion['fecha_gestion']),
            $this->limpiarDatoCSV($gestion['asesor_nombre'] ?? 'No asignado'),
            $this->limpiarDatoCSV($gestion['cedula']),
            $this->limpiarDatoCSV($gestion['cliente_nombre']),
            $this->limpiarDatoCSV($gestion['base_datos_nombre'] ?? 'No especificada'),
            $this->limpiarDatoCSV($gestion['telefono_contacto'] ?? ''),
            $this->formatearNumeroFacturaCSV($gestion['obligacion_texto'] ?? ''),
            $this->normalizarMayusSinUnderscoreCSV($this->obtenerPrimeraFranja($gestion['franja_cliente'] ?? '')),
            $this->formatearFormaContactoCSV($gestion['forma_contacto'] ?? 'llamada'),
            $this->formatearTipoContactoCSV($gestion),
            $this->formatearResultadoContactoCSV($gestion['tipificacion_2_nivel'] ?? ''),
            $this->formatearRazonEspecificaCSV($gestion['tipificacion_3_nivel'] ?? ''),
            $this->limpiarDatoCSV($gestion['fecha_acuerdo'] ?? ''),
            $this->limpiarDatoCSV($gestion['monto_acuerdo'] ?? ''),
            $this->limpiarDatoCSV($gestion['comentarios'] ?? ''),
            $this->normalizarMayusSinUnderscoreCSV($gestion['canales_autorizados_texto'] ?? ''),
            $this->limpiarDatoCSV($gestion['telefono'] ?? ''),
            $this->limpiarDatoCSV($gestion['celular2'] ?? ''),
            $this->limpiarDatoCSV($gestion['cel3'] ?? ''),
            $this->limpiarDatoCSV($gestion['cel4'] ?? ''),
            $this->limpiarDatoCSV($gestion['cel5'] ?? ''),
            $this->limpiarDatoCSV($gestion['cel6'] ?? ''),
            $this->limpiarDatoCSV($gestion['cel7'] ?? ''),
            $this->limpiarDatoCSV($gestion['cel8'] ?? ''),
            $this->limpiarDatoCSV($gestion['cel9'] ?? ''),
            $this->limpiarDatoCSV($gestion['cel10'] ?? '')
        ];
    }

    /**
     * Normaliza texto para CSV:
     * - quita '_' (lo convierte a espacios)
     * - comprime espacios
     * - devuelve en mayúsculas
     */
    private function normalizarMayusSinUnderscoreCSV($valor) {
        $valor = $this->limpiarDatoCSV($valor);
        if ($valor === '') {
            return '';
        }

        $valor = str_replace('_', ' ', (string) $valor);
        $valor = preg_replace('/\s+/', ' ', trim($valor));

        return function_exists('mb_strtoupper')
            ? mb_strtoupper($valor, 'UTF-8')
            : strtoupper($valor);
    }

    /**
     * Normaliza "Forma de Contacto" para CSV.
     */
    private function formatearFormaContactoCSV($formaContacto) {
        return $this->normalizarMayusSinUnderscoreCSV($formaContacto);
    }

    /**
     * Deriva "Tipo de Contacto" (CONTACTO EXITOSO / CONTACTO CON TERCERO / SIN CONTACTO).
     * Nota: el sistema guarda un campo 'tipo_gestion' (2do nivel) y 'resultado' (3er nivel),
     * por lo que este valor se infiere por patrones sobre esos códigos.
     */
    private function formatearTipoContactoCSV($gestion) {
        $tipoGestionRaw = (string)($gestion['tipo_gestion'] ?? $gestion['tipificacion_2_nivel'] ?? '');
        if (preg_match('/^(contacto_exitoso|contacto_tercero|sin_contacto)\|/u', $tipoGestionRaw, $m)) {
            $etiquetasN1 = [
                'contacto_exitoso' => 'CONTACTO EXITOSO',
                'contacto_tercero' => 'CONTACTO CON TERCERO',
                'sin_contacto' => 'SIN CONTACTO',
            ];
            return $etiquetasN1[$m[1]] ?? 'CONTACTO EXITOSO';
        }
        $tipoGestion = $tipoGestionRaw;
        $resultado = (string)($gestion['resultado'] ?? $gestion['tipificacion_3_nivel'] ?? '');

        // Unificar para comparación
        $texto = $tipoGestion . ' ' . $resultado;
        $texto = $this->limpiarDatoCSV($texto);
        $texto = str_replace('_', ' ', $texto);

        $textoParaMatch = $texto;
        if (function_exists('iconv')) {
            $textoParaMatch = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $textoParaMatch);
            if ($textoParaMatch === false) {
                $textoParaMatch = $texto;
            }
        }

        $textoLower = function_exists('mb_strtolower')
            ? mb_strtolower($textoParaMatch, 'UTF-8')
            : strtolower($textoParaMatch);

        // SIN CONTACTO
        $sinContactoPats = [
            'no contesta',
            'buzon',
            'telefono danado',
            'localizacion',
            'envio estado de cuenta',
            'venta con novedad analisis',
            'sin contacto',
        ];
        foreach ($sinContactoPats as $pat) {
            if (strpos($textoLower, $pat) !== false) {
                return 'SIN CONTACTO';
            }
        }

        // CONTACTO CON TERCERO
        $terceroPats = [
            'aqui no vive',
            'mensaje tercero',
            'fallecido otro',
            'contacto con tercero',
        ];
        foreach ($terceroPats as $pat) {
            if (strpos($textoLower, $pat) !== false) {
                return 'CONTACTO CON TERCERO';
            }
        }

        // CONTACTO EXITOSO (default)
        return 'CONTACTO EXITOSO';
    }

    /**
     * Normaliza el valor del "Resultado del Contacto" para el CSV.
     * Reglas:
     * - Sale en mayúsculas.
     * - Sustituye '_' por espacios.
     * - Si corresponde a "NO CONTACTADO" (o variantes), debe salir como "NO CONTACTO".
     */
    private function formatearResultadoContactoCSV($resultado) {
        $resultado = $this->limpiarDatoCSV($resultado);
        if ($resultado === '') {
            return '';
        }

        $resultado = str_replace('_', ' ', (string) $resultado);
        $resultado = preg_replace('/\s+/', ' ', trim($resultado));

        $resultadoLower = function_exists('mb_strtolower')
            ? mb_strtolower($resultado, 'UTF-8')
            : strtolower($resultado);

        if (
            strpos($resultadoLower, 'no contact') !== false ||
            strpos($resultadoLower, 'no contesta') !== false ||
            strpos($resultadoLower, 'no_contact') !== false
        ) {
            return 'NO CONTACTO';
        }

        return function_exists('mb_strtoupper')
            ? mb_strtoupper($resultado, 'UTF-8')
            : strtoupper($resultado);
    }

    /**
     * Normaliza el valor de "Razón Específica" para el CSV.
     * Reglas:
     * - Sale en mayúsculas.
     * - Sustituye '_' por espacios.
     * - Si corresponde a "buzón de mensajes" (o variantes), debe salir como "BUZON DE MENSAJES".
     */
    private function formatearRazonEspecificaCSV($razon) {
        $razon = $this->limpiarDatoCSV($razon);
        if ($razon === '') {
            return '';
        }

        $razon = str_replace('_', ' ', (string) $razon);
        $razon = preg_replace('/\s+/', ' ', trim($razon));

        // Para comparar ignorando acentos
        $razonParaMatch = $razon;
        if (function_exists('iconv')) {
            $razonParaMatch = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $razonParaMatch);
            if ($razonParaMatch === false) {
                $razonParaMatch = $razon;
            }
        }

        $razonParaMatchLower = function_exists('mb_strtolower')
            ? mb_strtolower($razonParaMatch, 'UTF-8')
            : strtolower($razonParaMatch);

        if (
            strpos($razonParaMatchLower, 'buzon') !== false &&
            (strpos($razonParaMatchLower, 'mensaje') !== false || strpos($razonParaMatchLower, 'mensajes') !== false)
        ) {
            return 'BUZON DE MENSAJES';
        }

        return function_exists('mb_strtoupper')
            ? mb_strtoupper($razon, 'UTF-8')
            : strtoupper($razon);
    }

    /**
     * Normaliza el número de factura para el CSV:
     * - mayúsculas
     * - sin '_' (lo reemplaza por espacios)
     */
    private function formatearNumeroFacturaCSV($valor) {
        $valor = $this->limpiarDatoCSV($valor);
        if ($valor === '') {
            return '';
        }

        $valor = str_replace('_', ' ', (string) $valor);
        $valor = preg_replace('/\s+/', ' ', trim($valor));

        return function_exists('mb_strtoupper')
            ? mb_strtoupper($valor, 'UTF-8')
            : strtoupper($valor);
    }

    /**
     * Obtiene el primer teléfono de una lista separada por comas
     */
    private function obtenerPrimerTelefono($telefonos) {
        if (empty($telefonos)) {
            return '';
        }
        
        $telefonosArray = explode(',', $telefonos);
        return trim($telefonosArray[0]);
    }

    /**
     * Obtiene la primera franja de una lista separada por comas
     */
    private function obtenerPrimeraFranja($franjas) {
        if (empty($franjas)) {
            return 'No especificada';
        }
        
        $franjasArray = explode(',', $franjas);
        return trim($franjasArray[0]);
    }
    
    /**
     * Limpia los datos para exportación CSV - Elimina espacios extra y caracteres problemáticos
     */
    private function limpiarDatoCSV($dato) {
        if ($dato === null || $dato === '') {
            return '';
        }
        
        // Convertir a string si no lo es
        $dato = (string) $dato;
        
        // Eliminar espacios al inicio y final
        $dato = trim($dato);
        
        // Eliminar espacios múltiples
        $dato = preg_replace('/\s+/', ' ', $dato);
        
        // Eliminar caracteres problemáticos para Excel
        $dato = str_replace(["\r", "\n", "\t"], ' ', $dato);
        
        // Eliminar espacios extra que puedan quedar
        $dato = preg_replace('/\s+/', ' ', $dato);
        
        return $dato;
    }

    public function crearNuevaBase() {
        $page_title = "Crear Nueva Base de Datos";
        $agentLogPath = __DIR__ . '/../debug-a2fdce.log';

        if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['archivo_excel_nueva'])) {
            $nombreBaseDatos = $this->getPost('nombre_base_datos');
            $usuarioCoordinadorId = $_SESSION['user_id'];

            // #region agent log
            @file_put_contents($agentLogPath, json_encode([
                'sessionId' => 'a2fdce',
                'runId' => 'pre-fix',
                'hypothesisId' => 'B1',
                'location' => 'controllers/CoordinadorController.php:crearNuevaBase:entry',
                'message' => 'crearNuevaBase POST received',
                'data' => [
                    'hasNombre' => trim((string)$nombreBaseDatos) !== '',
                    'nombreLen' => strlen(trim((string)$nombreBaseDatos)),
                    'hasFileKey' => isset($_FILES['archivo_excel_nueva']),
                    'fileNameExt' => isset($_FILES['archivo_excel_nueva']['name']) ? strtolower(pathinfo((string)$_FILES['archivo_excel_nueva']['name'], PATHINFO_EXTENSION)) : null,
                    'fileSize' => isset($_FILES['archivo_excel_nueva']['size']) ? (int)$_FILES['archivo_excel_nueva']['size'] : null,
                    'fileError' => isset($_FILES['archivo_excel_nueva']['error']) ? (int)$_FILES['archivo_excel_nueva']['error'] : null,
                ],
                'timestamp' => (int) round(microtime(true) * 1000),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n", FILE_APPEND);
            // #endregion
            
            // Verificar el tamaño del archivo
            $fileSize = $_FILES['archivo_excel_nueva']['size'];
            $maxFileSize = 500 * 1024 * 1024; // 500MB para archivos CSV grandes
            
            if ($fileSize > $maxFileSize) {
                $_SESSION['error_message'] = "❌ Error en la carga: El archivo es demasiado grande. El tamaño máximo permitido es 500MB.";
                header('Location: index.php?action=gestion_cargas');
                exit;
            }
            
            // Verificar tipo de archivo
            $fileType = strtolower(pathinfo($_FILES['archivo_excel_nueva']['name'], PATHINFO_EXTENSION));
            if ($fileType !== 'csv') {
                $_SESSION['error_message'] = "❌ Error en la carga: Solo se permiten archivos CSV.";
                header('Location: index.php?action=gestion_cargas');
                exit;
            }
            
            // Validar o crear base: si ya existe con ese nombre para este coordinador, reutilizarla.
            $cargaExistente = $this->cargaExcelModel->getCargaByNombre($nombreBaseDatos, $usuarioCoordinadorId);
            $esNuevaBase = false;

            // #region agent log
            @file_put_contents($agentLogPath, json_encode([
                'sessionId' => 'a2fdce',
                'runId' => 'pre-fix',
                'hypothesisId' => 'B2',
                'location' => 'controllers/CoordinadorController.php:crearNuevaBase:pre_create_base',
                'message' => 'name ok, about to create base',
                'data' => [
                    'nombreLen' => strlen(trim((string)$nombreBaseDatos)),
                ],
                'timestamp' => (int) round(microtime(true) * 1000),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n", FILE_APPEND);
            // #endregion
            
            // Procesar el archivo CSV
            $handle = fopen($_FILES['archivo_excel_nueva']['tmp_name'], 'r');
            if (!$handle) {
                $_SESSION['error_message'] = "❌ Error en la carga: No se pudo abrir el archivo CSV.";
                header('Location: index.php?action=gestion_cargas');
                exit;
            }
            
            // Detectar delimitador automáticamente
            $first_line = fgets($handle);
            rewind($handle);
            
            $delimiters = [',', ';', "\t"];
            $delimiter = ',';
            $max_count = 0;
            
            foreach ($delimiters as $d) {
                $count = substr_count($first_line, $d);
                if ($count > $max_count) {
                    $max_count = $count;
                    $delimiter = $d;
                }
            }
            
            // Leer encabezados con el delimitador detectado
            $headers = fgetcsv($handle, 0, $delimiter);
            if (!$headers) {
                $_SESSION['error_message'] = "❌ Error en la carga: El archivo CSV está vacío o no tiene encabezados válidos.";
                fclose($handle);
                header('Location: index.php?action=gestion_cargas');
                exit;
            }
            
            // Mapear columnas por nombre (insensible a mayúsculas/minúsculas)
            $columnMap = [];
            foreach ($headers as $index => $header) {
                $headerClean = trim(strtolower($header));
                $columnMap[$headerClean] = $index;
            }
            
            // Verificar columnas obligatorias con variaciones (solo cédula + número de factura; tel opcional)
            $columnasObligatorias = [
                'cedula' => ['cedula', 'cedula ', 'cédula', 'cedula identidad', 'dni'],
                'numero_factura' => ['numero_factura', 'numero factura', 'número factura', 'num factura', 'factura'],
            ];
            $columnasFaltantes = [];
            
            foreach ($columnasObligatorias as $campo => $variaciones) {
                $encontrada = false;
                foreach ($columnMap as $header => $index) {
                    foreach ($variaciones as $variacion) {
                        if (strpos($header, strtolower(trim($variacion))) !== false) {
                            $encontrada = true;
                            break 2;
                        }
                    }
                }
                if (!$encontrada) {
                    $columnasFaltantes[] = $campo;
                }
            }
            
            if (!empty($columnasFaltantes)) {
                $_SESSION['error_message'] = "❌ Error en la carga: El archivo CSV debe contener las columnas obligatorias: Cédula y Número de Factura.";
                fclose($handle);
                header('Location: index.php?action=gestion_cargas');
                exit;
            }
            
            if ($cargaExistente) {
                $cargaId = (int)($cargaExistente['id'] ?? 0);
                $esNuevaBase = false;
            } else {
                // Crear nueva base de datos independiente
                $cargaId = $this->cargaExcelModel->crearBaseDatosIndependiente($nombreBaseDatos, $usuarioCoordinadorId);
                if (!$cargaId) {
                    $_SESSION['error_message'] = "❌ Error en la carga: No se pudo crear la nueva base de datos.";
                    fclose($handle);
                    header('Location: index.php?action=gestion_cargas');
                    exit;
                }
                $esNuevaBase = true;
            }

            if ((int)$cargaId <= 0) {
                $_SESSION['error_message'] = "❌ Error en la carga: Base de datos inválida.";
                fclose($handle);
                header('Location: index.php?action=gestion_cargas');
                exit;
            }

            // #region agent log
            @file_put_contents($agentLogPath, json_encode([
                'sessionId' => 'a2fdce',
                'runId' => 'pre-fix',
                'hypothesisId' => 'B3',
                'location' => 'controllers/CoordinadorController.php:crearNuevaBase:created_base',
                'message' => 'base created',
                'data' => [
                    'cargaId' => (int)$cargaId,
                ],
                'timestamp' => (int) round(microtime(true) * 1000),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n", FILE_APPEND);
            // #endregion
            
            // Contadores para el resumen
            $clientesNuevos = 0;
            $clientesDuplicados = 0;
            $clientesAgregados = 0;
            $errores = 0;
            
            // Usar el método existente para procesar clientes con obligaciones
            $clientes = $this->leerArchivoCSV($_FILES['archivo_excel_nueva']['tmp_name']);
            
            if (empty($clientes)) {
                $_SESSION['error_message'] = "❌ Error en la carga: No se encontraron clientes válidos en el archivo CSV.";
                fclose($handle);
                header('Location: index.php?action=gestion_cargas');
                exit;
            }
            
            // Procesar clientes usando el método que maneja obligaciones
            $resultado = $this->procesarClientesCSV($clientes, $cargaId, $usuarioCoordinadorId);
            
            $clientesNuevos = $resultado['nuevos'];
            $clientesDuplicados = $resultado['duplicados'];
            $obligacionesCreadas = $resultado['obligaciones_creadas'];
            $obligacionesDuplicadas = $resultado['obligaciones_duplicadas'];
            $clientesAgregados = $clientesNuevos + $clientesDuplicados;

            // Sincronizar contadores en base_clientes (tablas correctas: base_clientes/clientes/obligaciones)
            try {
                $stmtTotales = $this->pdo->prepare("
                    UPDATE base_clientes
                    SET total_clientes = (SELECT COUNT(*) FROM clientes WHERE base_id = ?),
                        total_obligaciones = (SELECT COUNT(*) FROM obligaciones WHERE base_id = ?)
                    WHERE id_base = ?
                ");
                $stmtTotales->execute([(int)$cargaId, (int)$cargaId, (int)$cargaId]);
            } catch (Throwable $e) {
                error_log("No se pudieron actualizar totales de base_clientes ($cargaId): " . $e->getMessage());
            }
            
            fclose($handle);
            
            // Mensaje de éxito
            $mensaje = $esNuevaBase
                ? "✅ Base de datos '$nombreBaseDatos' creada exitosamente!<br>"
                : "✅ Base de datos '$nombreBaseDatos' actualizada (reutilizada) exitosamente!<br>";
            $mensaje .= "📊 <strong>Resumen:</strong><br>";
            $mensaje .= "• Clientes nuevos: $clientesNuevos<br>";
            $mensaje .= "• Clientes duplicados: $clientesDuplicados<br>";
            $mensaje .= "• Total procesados: $clientesAgregados<br>";
            $mensaje .= "• Facturas creadas: $obligacionesCreadas<br>";
            if ($obligacionesDuplicadas > 0) {
                $mensaje .= "• Facturas duplicadas: $obligacionesDuplicadas<br>";
            }
            
            $_SESSION['success_message'] = $mensaje;
            header('Location: index.php?action=list_cargas');
            exit;
        }
        
        // Si no es POST, mostrar la vista de gestión de cargas
        $coordinador_id = $_SESSION['user_id'];
        $cargas = $this->cargaExcelModel->getCargasByCoordinador($coordinador_id);
        require __DIR__ . '/../views/gestion_cargas_integrada.php';
    }

    /**
     * Importación desde ruta de archivo (CLI / tareas): misma lógica que crear nueva base vía formulario.
     * No usa sesión ni redirecciones.
     *
     * @param string $rutaCsv Ruta absoluta al CSV
     * @param string $nombreBase Nombre en base_clientes; si ya existe para el coordinador, se reutiliza
     * @param string $coordinadorCedula Cédula del coordinador (creado_por)
     * @return array{ok:bool,carga_id?:int,nombre_base?:string,filas_csv?:int,resultado?:array,error?:string}
     */
    public function importarCsvNuevaBaseDesdeRuta(string $rutaCsv, string $nombreBase, string $coordinadorCedula): array {
        $nombreBase = trim($nombreBase);
        $coordinadorCedula = trim($coordinadorCedula);
        if ($nombreBase === '' || $coordinadorCedula === '') {
            return ['ok' => false, 'error' => 'nombre_base y cedula_coordinador son obligatorios.'];
        }
        if (!is_readable($rutaCsv)) {
            return ['ok' => false, 'error' => 'No se puede leer el archivo: ' . $rutaCsv];
        }
        $size = filesize($rutaCsv);
        if ($size !== false && $size > 500 * 1024 * 1024) {
            return ['ok' => false, 'error' => 'El archivo supera el límite de 500MB.'];
        }
        $ext = strtolower(pathinfo($rutaCsv, PATHINFO_EXTENSION));
        if ($ext !== 'csv') {
            return ['ok' => false, 'error' => 'Solo se permiten archivos .csv'];
        }

        try {
            $cargaExistente = $this->cargaExcelModel->getCargaByNombre($nombreBase, $coordinadorCedula);
            if ($cargaExistente && (int)($cargaExistente['id'] ?? 0) > 0) {
                $cargaId = (int)$cargaExistente['id'];
            } else {
                $cargaId = (int)$this->cargaExcelModel->crearBaseDatosIndependiente($nombreBase, $coordinadorCedula);
                if ($cargaId <= 0) {
                    return ['ok' => false, 'error' => 'No se pudo crear la base en base_clientes.'];
                }
            }

            $clientes = $this->leerArchivoCSV($rutaCsv);
            if (empty($clientes)) {
                return ['ok' => false, 'error' => 'No se encontraron filas válidas (cédula + número de factura).', 'carga_id' => $cargaId];
            }

            $resultado = $this->procesarClientesCSV($clientes, $cargaId, $coordinadorCedula, false);

            try {
                $stmtTotales = $this->pdo->prepare("
                    UPDATE base_clientes
                    SET total_clientes = (SELECT COUNT(*) FROM clientes WHERE base_id = ?),
                        total_obligaciones = (SELECT COUNT(*) FROM obligaciones WHERE base_id = ?)
                    WHERE id_base = ?
                ");
                $stmtTotales->execute([$cargaId, $cargaId, $cargaId]);
            } catch (Throwable $e) {
                error_log('importarCsvNuevaBaseDesdeRuta totales: ' . $e->getMessage());
            }

            return [
                'ok' => true,
                'carga_id' => $cargaId,
                'nombre_base' => $nombreBase,
                'filas_csv' => count($clientes),
                'resultado' => $resultado,
            ];
        } catch (Throwable $e) {
            error_log('importarCsvNuevaBaseDesdeRuta: ' . $e->getMessage());
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    public function getAsesoresDisponibles() {
        $cargaId = $this->getGet('carga_id', 0);
        $coordinadorId = $_SESSION['user_id'];
        
        // Pasar el cargaId para excluir asesores que ya tienen acceso a esta base
        $asesores = $this->cargaExcelModel->getAsesoresDisponibles($coordinadorId, $cargaId);
        
        header('Content-Type: application/json');
        echo json_encode($asesores);
        exit;
    }

    public function getAsesoresAsignados() {
        if (ob_get_level()) ob_clean();
        
        $cargaId = $this->getGet('carga_id', 0);
        $coordinadorId = $_SESSION['user_id'];
        
        $carga = $this->cargaExcelModel->getCargaByIdAndCoordinador($cargaId, $coordinadorId);
        if (!$carga) {
            header('Content-Type: application/json');
            echo json_encode([]);
            exit;
        }
        
        // Solo asesores asignados al coordinador Y con acceso a esta base
        $asesores = $this->cargaExcelModel->getAsesoresAsignadosABaseParaCoordinador($cargaId, $coordinadorId);
        
        header('Content-Type: application/json');
        echo json_encode($asesores);
        exit;
    }

    public function asignarAsesorBase() {
        if (ob_get_level()) ob_clean();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $cargaId = (int) $this->getPost('carga_id', 0);
            $coordinadorId = $_SESSION['user_id'];
            
            // Aceptar uno o varios: asesor_id (legacy) o asesor_ids[] (múltiple)
            $asesorIds = $_POST['asesor_ids'] ?? null;
            if (is_array($asesorIds)) {
                $asesorIds = array_map('intval', array_filter($asesorIds));
            } elseif (!empty($_POST['asesor_id'])) {
                $asesorIds = [(int) $_POST['asesor_id']];
            } else {
                $asesorIds = [];
            }
            
            $carga = $this->cargaExcelModel->getCargaByIdAndCoordinador($cargaId, $coordinadorId);
            if (!$carga) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Base de datos no encontrada']);
                exit;
            }
            
            // Solo asesores del coordinador que NO tienen acceso a esta base
            $disponibles = $this->cargaExcelModel->getAsesoresDisponibles($coordinadorId, $cargaId);
            $idsDisponibles = array_column($disponibles, 'id');
            $asesorIds = array_intersect($asesorIds, $idsDisponibles);
            $asesorIds = array_values(array_unique($asesorIds));
            
            if (empty($asesorIds)) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Selecciona al menos un asesor válido (asignado a ti y sin acceso a esta base).']);
                exit;
            }
            
            $totalAsignaciones = 0;
            foreach ($asesorIds as $asesorId) {
                $totalAsignaciones += $this->cargaExcelModel->asignarAsesorABaseDatos($cargaId, $asesorId);
            }
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'asesores_asignados' => count($asesorIds),
                'asignaciones_creadas' => $totalAsignaciones,
                'message' => count($asesorIds) === 1
                    ? "Se asignaron $totalAsignaciones clientes al asesor."
                    : count($asesorIds) . " asesores asignados. Total: $totalAsignaciones asignaciones."
            ]);
            exit;
        }
    }

    public function liberarAsesorBase() {
        // Limpiar cualquier output previo
        if (ob_get_level()) ob_clean();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $cargaId = $this->getPost('carga_id', 0);
                $asesorId = $this->getPost('asesor_id', 0);
                $coordinadorId = $_SESSION['user_id'];

                // Verificar que la carga pertenece al coordinador
                $carga = $this->cargaExcelModel->getCargaByIdAndCoordinador($cargaId, $coordinadorId);
                if (!$carga) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Base de datos no encontrada']);
                    exit;
                }

                $asignacionesActualizadas = $this->cargaExcelModel->liberarAsesorDeBaseDatos($cargaId, $asesorId);
    
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'asignaciones_actualizadas' => $asignacionesActualizadas,
                    'message' => "Se liberaron $asignacionesActualizadas asignaciones del asesor"
                ]);
                exit;
            } catch (Exception $e) {
                error_log("Error al liberar asesor de base de datos: " . $e->getMessage());
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Error interno del servidor al liberar el asesor'
                ]);
                exit;
            }
        }
    }


    /**
     * Agrega clientes a una base de datos existente
     */
    public function agregarABaseExistente() {
        $page_title = "Agregar Clientes a Base Existente";

        if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['archivo_excel_existente'])) {
            $cargaId = (int)($this->getPost('carga_id', 0));
            $usuarioCoordinadorId = $_SESSION['user_id'];

            if ($cargaId <= 0) {
                $_SESSION['error_message'] = "❌ Error: Debes seleccionar una base de datos válida.";
                header('Location: index.php?action=gestion_cargas');
                exit;
            }
            
            // Verificar que la carga pertenezca al coordinador
            $carga = $this->cargaExcelModel->getCargaByIdAndCoordinador($cargaId, $usuarioCoordinadorId);
            if (!$carga) {
                $_SESSION['error_message'] = "❌ Error: No tienes acceso a esta base de datos o no existe.";
                header('Location: index.php?action=gestion_cargas');
                exit;
            }
            
            // Verificar el tamaño del archivo
            $fileSize = $_FILES['archivo_excel_existente']['size'];
            $maxFileSize = 100 * 1024 * 1024; // 100MB
            
            if ($fileSize > $maxFileSize) {
                $_SESSION['error_message'] = "❌ Error en la carga: El archivo es demasiado grande. El tamaño máximo permitido es 100MB.";
                header('Location: index.php?action=gestion_cargas');
                exit;
            }
            
            // Verificar tipo de archivo
            $fileType = strtolower(pathinfo($_FILES['archivo_excel_existente']['name'], PATHINFO_EXTENSION));
            if ($fileType !== 'csv') {
                $_SESSION['error_message'] = "❌ Error en la carga: Solo se permiten archivos CSV.";
                header('Location: index.php?action=gestion_cargas');
                exit;
            }
            
            // Procesar el archivo CSV
            $handle = fopen($_FILES['archivo_excel_existente']['tmp_name'], 'r');
            if (!$handle) {
                $_SESSION['error_message'] = "❌ Error en la carga: No se pudo abrir el archivo CSV.";
                header('Location: index.php?action=gestion_cargas');
                exit;
            }
            
            // Detectar delimitador automáticamente
            $first_line = fgets($handle);
            rewind($handle);
            
            $delimiters = [',', ';', "\t"];
            $delimiter = ',';
            $max_count = 0;
            
            foreach ($delimiters as $d) {
                $count = substr_count($first_line, $d);
                if ($count > $max_count) {
                    $max_count = $count;
                    $delimiter = $d;
                }
            }
            
            // Leer encabezados con el delimitador detectado
            $headers = fgetcsv($handle, 0, $delimiter);
            if (!$headers) {
                $_SESSION['error_message'] = "❌ Error en la carga: El archivo CSV está vacío o no tiene encabezados válidos.";
                fclose($handle);
                header('Location: index.php?action=gestion_cargas');
                exit;
            }
            
            // Mapear columnas por nombre (insensible a mayúsculas/minúsculas)
            $columnMap = [];
            foreach ($headers as $index => $header) {
                $headerClean = trim(strtolower($header));
                $columnMap[$headerClean] = $index;
            }
            
            // Verificar columnas obligatorias (solo cédula + número de factura; tel opcional)
            $columnasObligatorias = [
                'cedula' => ['cedula', 'cedula ', 'CÉDULA', 'Cedula'],
                'numero_factura' => ['numero_factura', 'numero factura', 'NUMERO FACTURA', 'número factura', 'num factura', 'factura'],
            ];
            $columnasFaltantes = [];
            
            foreach ($columnasObligatorias as $campo => $variaciones) {
                $encontrada = false;
                foreach ($columnMap as $header => $index) {
                    foreach ($variaciones as $variacion) {
                        if (strpos($header, strtolower(trim($variacion))) !== false) {
                            $encontrada = true;
                            break 2;
                        }
                    }
                }
                if (!$encontrada) {
                    $columnasFaltantes[] = $campo;
                }
            }
            
            if (!empty($columnasFaltantes)) {
                $_SESSION['error_message'] = "❌ Error en la carga: El archivo CSV debe contener las columnas obligatorias: Cédula y Número de Factura.";
                fclose($handle);
                header('Location: index.php?action=gestion_cargas');
                exit;
            }
            
            // Usar el método existente para procesar clientes con obligaciones
            $clientes = $this->leerArchivoCSV($_FILES['archivo_excel_existente']['tmp_name']);
            
            if (empty($clientes)) {
                $_SESSION['error_message'] = "❌ Error en la carga: No se encontraron clientes válidos en el archivo CSV.";
                fclose($handle);
                header('Location: index.php?action=gestion_cargas');
                exit;
            }
            
            // Procesar clientes usando el método que maneja obligaciones
            // IMPORTANTE: Pasar true para actualizar clientes existentes cuando se agrega a base existente
            $resultado = $this->procesarClientesCSV($clientes, $cargaId, $usuarioCoordinadorId, true);
            
            $clientesNuevos = $resultado['nuevos'];
            $clientesDuplicados = $resultado['duplicados'];
            $obligacionesCreadas = $resultado['obligaciones_creadas'];
            $obligacionesDuplicadas = $resultado['obligaciones_duplicadas'];
            $clientesAgregados = $clientesNuevos + $clientesDuplicados;

            try {
                $stmtTotales = $this->pdo->prepare("
                    UPDATE base_clientes
                    SET total_clientes = (SELECT COUNT(*) FROM clientes WHERE base_id = ?),
                        total_obligaciones = (SELECT COUNT(*) FROM obligaciones WHERE base_id = ?)
                    WHERE id_base = ?
                ");
                $stmtTotales->execute([(int)$cargaId, (int)$cargaId, (int)$cargaId]);
            } catch (Throwable $e) {
                error_log('agregarABaseExistente totales base_clientes: ' . $e->getMessage());
            }
            
            fclose($handle);

            if ($clientesAgregados === 0 && (int)$obligacionesCreadas === 0 && count($clientes) > 0) {
                $_SESSION['error_message'] = "❌ No se insertó ningún cliente ni factura aunque el CSV tiene " . count($clientes) . " filas válidas. Revise los logs del servidor (error_log / MySQL). Si acaba de actualizar el sistema, vuelva a cargar el archivo.";
                header('Location: index.php?action=list_cargas');
                exit;
            }
            
            // Mensaje de éxito con resumen
            $mensaje = "✅ <strong>Clientes agregados exitosamente a '{$carga['nombre_cargue']}'</strong><br><br>";
            $mensaje .= "📊 <strong>Resumen de la carga:</strong><br>";
            $mensaje .= "• <strong>Clientes nuevos:</strong> $clientesNuevos<br>";
            $mensaje .= "• <strong>Clientes actualizados (ya existían):</strong> $clientesDuplicados<br>";
            $mensaje .= "• <strong>Total procesados:</strong> $clientesAgregados<br>";
            $mensaje .= "• <strong>Facturas creadas:</strong> $obligacionesCreadas<br>";
            if ($obligacionesDuplicadas > 0) {
                $mensaje .= "• <strong>Facturas duplicadas:</strong> $obligacionesDuplicadas<br>";
            }
            
            $_SESSION['success_message'] = $mensaje;
            header('Location: index.php?action=list_cargas');
            exit;
        }
        
        // Si no es POST, mostrar la vista de gestión de cargas
        $coordinador_id = $_SESSION['user_id'] ?? null;
        $cargas = $this->cargaExcelModel->getCargasByCoordinador($coordinador_id, true);
        require __DIR__ . '/../views/gestion_cargas_integrada.php';
    }

    /**
     * Gestiona el estado de las bases de datos (habilitar/deshabilitar)
     */
    public function gestionarEstadoBases() {
        $page_title = "Gestionar Estado de Bases de Datos";
        $coordinador_id = $_SESSION['user_id'];
        
        // Obtener todas las bases de datos del coordinador (habilitadas y deshabilitadas)
        $bases_datos = $this->clienteModel->getCargasByCoordinador($coordinador_id, false);
        $pdo = $this->pdo;

        // #region debug d200d9 gestionarEstadoBases totals
        try {
            $sample = array_slice((array)$bases_datos, 0, 12);
            $rows = array_map(function($b) {
                return [
                    'id' => (int)($b['id'] ?? 0),
                    'nombreLen' => strlen((string)($b['nombre_cargue'] ?? $b['nombre'] ?? '')),
                    'estado' => (string)($b['estado_habilitado'] ?? $b['estado'] ?? ''),
                    'total_clientes' => (int)($b['total_clientes'] ?? -1),
                    'total_obligaciones' => (int)($b['total_obligaciones'] ?? -1),
                ];
            }, $sample);
            @file_put_contents(__DIR__ . '/../debug-d200d9.log', json_encode([
                'sessionId' => 'd200d9',
                'runId' => 'pre-fix',
                'hypothesisId' => 'T1',
                'location' => 'controllers/CoordinadorController.php:gestionarEstadoBases:bases_sample',
                'message' => 'bases loaded',
                'data' => ['count' => count((array)$bases_datos), 'sample' => $rows],
                'timestamp' => (int) round(microtime(true) * 1000),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n", FILE_APPEND);
        } catch (Throwable $e) {}
        // #endregion
        
        require __DIR__ . '/../views/gestionar_estado_bases.php';
    }

    /**
     * Cambia el estado de una base de datos
     */
    public function cambiarEstadoBase() {
        // Limpiar cualquier output previo
        if (ob_get_level()) ob_clean();
        
        try {
            $coordinador_id = $_SESSION['user_id'];
            $carga_id = (int)$_POST['carga_id'];
            $nuevo_estado = (string)($_POST['nuevo_estado'] ?? '');

            // Normalizar compatibilidad: UI vieja enviaba habilitado/deshabilitado
            if ($nuevo_estado === 'habilitado') $nuevo_estado = 'activo';
            if ($nuevo_estado === 'deshabilitado') $nuevo_estado = 'inactivo';

            // #region debug d200d9 cambiarEstadoBase
            try {
                @file_put_contents(__DIR__ . '/../debug-d200d9.log', json_encode([
                    'sessionId' => 'd200d9',
                    'runId' => 'pre-fix',
                    'hypothesisId' => 'B2',
                    'location' => 'controllers/CoordinadorController.php:cambiarEstadoBase:input',
                    'message' => 'post',
                    'data' => [
                        'carga_id' => $carga_id,
                        'nuevo_estado' => $nuevo_estado,
                    ],
                    'timestamp' => (int) round(microtime(true) * 1000),
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n", FILE_APPEND);
            } catch (Throwable $e) {}
            // #endregion
            
            // Verificar que la carga pertenezca al coordinador
            $carga = $this->cargaExcelModel->getCargaByIdAndCoordinador($carga_id, $coordinador_id);
            if (!$carga) {
                throw new Exception("La base de datos no es válida.");
            }
            
            // Cambiar el estado
            if ($this->clienteModel->cambiarEstadoCarga($carga_id, $nuevo_estado)) {
                $_SESSION['success_message'] = "Estado de la base de datos actualizado correctamente.";
            } else {
                throw new Exception("Error al actualizar el estado de la base de datos.");
            }
            
        } catch (Exception $e) {
            $_SESSION['error_message'] = $e->getMessage();
        }
        
        header("Location: index.php?action=gestionar_estado_bases");
        exit;
    }

    /**
     * Busca bases de datos por nombre
     */
    public function buscarBasesDatos() {
        // Limpiar cualquier output previo
        if (ob_get_level()) ob_clean();
        
        $coordinador_id = $_SESSION['user_id'];
        $termino_busqueda = $_POST['termino_busqueda'] ?? '';
        $solo_habilitadas = $_POST['solo_habilitadas'] ?? 'true';
        
        $bases_datos = $this->clienteModel->buscarCargasPorNombre(
            $coordinador_id, 
            $termino_busqueda, 
            $solo_habilitadas === 'true'
        );
        
        header('Content-Type: application/json');
        echo json_encode($bases_datos);
        exit;
    }

    /**
     * Transfiere un recordatorio de un asesor a otro
     */
    public function transferirRecordatorio() {
        try {
            // Verificar que sea un coordinador
            if ($_SESSION['user_role'] !== 'coordinador') {
                throw new Exception("Acceso denegado.");
            }

            $coordinador_id = $_SESSION['user_id'];
            $cliente_id = $_POST['cliente_id'] ?? null;
            $asesor_origen_id = $_POST['asesor_origen_id'] ?? null;
            $asesor_destino_id = $_POST['asesor_destino_id'] ?? null;

            if (!$cliente_id || !$asesor_origen_id || !$asesor_destino_id) {
                throw new Exception("Datos incompletos para la transferencia.");
            }

            // Verificar que el asesor origen esté asignado al coordinador
            if (!$this->usuarioModel->isAsesorAsignadoACoordinador($asesor_origen_id, $coordinador_id)) {
                throw new Exception("El asesor origen no está asignado a tu coordinación.");
            }

            // Verificar que el asesor destino esté asignado al coordinador
            if (!$this->usuarioModel->isAsesorAsignadoACoordinador($asesor_destino_id, $coordinador_id)) {
                throw new Exception("El asesor destino no está asignado a tu coordinación.");
            }

            // Verificar que el cliente pertenezca a una carga del coordinador
            $cliente = $this->clienteModel->getClienteByIdAndCoordinador($cliente_id, $coordinador_id);
            if (!$cliente) {
                throw new Exception("El cliente no pertenece a tus cargas.");
            }

            // Realizar la transferencia
            $resultado = $this->clienteModel->traspasarCliente($cliente_id, $asesor_destino_id, $asesor_origen_id);

            if ($resultado) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Recordatorio transferido exitosamente.'
                ]);
            } else {
                throw new Exception("Error al transferir el recordatorio.");
            }

        } catch (Exception $e) {
            error_log("Error en transferirRecordatorio: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    // ===== NUEVOS MÉTODOS PARA EL SISTEMA DE TAREAS =====

    /**
     * Vista para gestionar tareas específicas
     */
    public function gestionarTareas() {
        $page_title = "Gestión de Tareas Específicas";
        $coordinador_id = $_SESSION['user_id'];
        
        // Obtener cargas del coordinador
        $cargas = $this->cargaExcelModel->getCargasByCoordinador($coordinador_id);
        
        // Calcular estadísticas para cada carga
        foreach ($cargas as &$carga) {
            $carga['total_clientes'] = $this->clienteModel->getTotalClientsByCargaId($carga['id']);
        }
        unset($carga); // Liberar la referencia para evitar problemas
        
        // Obtener asesores asignados
        $asesores = $this->usuarioModel->getAsesoresByCoordinador($coordinador_id);
        
        // Obtener tareas existentes (fuente: tabla tareas + detalle_tareas)
        $tareas = $this->tareaModel->getTareasByCoordinador($coordinador_id);
        
        // Obtener estadísticas (fuente: tabla tareas)
        $estadisticas = $this->tareaModel->getEstadisticasTareas($coordinador_id);
        
        require __DIR__ . '/../views/coordinador_gestionar_tareas.php';
    }

    public function getDetallesTarea() {
        if (ob_get_level()) ob_clean();
        header('Content-Type: application/json; charset=UTF-8');

        try {
            $coordinador_id = $_SESSION['user_id'] ?? '';
            $tareaId = (int)($_GET['tarea_id'] ?? 0);
            if ($tareaId <= 0) {
                echo json_encode(['success' => false, 'error' => 'tarea_id requerido']);
                exit;
            }

            $tarea = $this->tareaModel->getTareaByIdAndCoordinador($tareaId, $coordinador_id);
            if (!$tarea) {
                echo json_encode(['success' => false, 'error' => 'No tienes permisos o la tarea no existe']);
                exit;
            }

            $clientes = $this->tareaModel->getClientesByTarea($tareaId);
            echo json_encode([
                'success' => true,
                'tarea' => [
                    'id' => (int)($tarea['id'] ?? $tareaId),
                    'asesor_nombre' => (string)($tarea['asesor_nombre'] ?? ''),
                    'nombre_cargue' => (string)($tarea['nombre_cargue'] ?? ''),
                    'estado' => (string)($tarea['estado_ui'] ?? $tarea['estado'] ?? ''),
                    'total_clientes' => (int)($tarea['total_clientes'] ?? 0),
                    'clientes_gestionados' => (int)($tarea['clientes_gestionados'] ?? 0),
                    'fecha_creacion' => (string)($tarea['fecha_creacion'] ?? ''),
                ],
                'clientes' => array_map(function ($c) {
                    return [
                        'id_cliente' => (int)($c['id_cliente'] ?? 0),
                        'cedula' => (string)($c['cedula'] ?? ''),
                        'nombre' => (string)($c['nombre'] ?? ''),
                        'telefono' => (string)($c['tel1'] ?? ''),
                        'gestionado' => (string)($c['gestionado'] ?? 'no'),
                    ];
                }, $clientes),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        } catch (Throwable $e) {
            echo json_encode(['success' => false, 'error' => 'server_error']);
            exit;
        }
    }

    /**
     * Crear nueva tarea específica
     */
    public function crearTarea() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?action=gestionar_tareas');
            exit;
        }

        $coordinador_id = $_SESSION['user_id'];
        
        // Validar datos
        $nombre_tarea = isset($_POST['nombre_tarea']) ? trim((string)$_POST['nombre_tarea']) : '';
        $asesor_id = $_POST['asesor_id'] ?? null;
        $carga_id = $_POST['carga_id'] ?? null;
        $cantidad_clientes = intval($_POST['cantidad_clientes'] ?? 0);

        // Filtros (obligaciones)
        $saldo_min = isset($_POST['saldo_min']) && $_POST['saldo_min'] !== '' ? (float)$_POST['saldo_min'] : null;
        $saldo_max = isset($_POST['saldo_max']) && $_POST['saldo_max'] !== '' ? (float)$_POST['saldo_max'] : null;
        $mora_min = isset($_POST['mora_min']) && $_POST['mora_min'] !== '' ? (int)$_POST['mora_min'] : null;
        $mora_max = isset($_POST['mora_max']) && $_POST['mora_max'] !== '' ? (int)$_POST['mora_max'] : null;
        $franja = isset($_POST['franja']) ? trim((string)$_POST['franja']) : '';

        // Filtros (gestión)
        $solo_no_gestionados = isset($_POST['solo_no_gestionados']) ? (int)$_POST['solo_no_gestionados'] : 1;
        $forma_contacto = isset($_POST['forma_contacto']) ? trim((string)$_POST['forma_contacto']) : '';
        $tipo_contacto = isset($_POST['tipo_contacto']) ? trim((string)$_POST['tipo_contacto']) : '';
        $resultado_contacto = isset($_POST['resultado_contacto']) ? trim((string)$_POST['resultado_contacto']) : '';
        $razon_especifica = isset($_POST['razon_especifica']) ? trim((string)$_POST['razon_especifica']) : '';

        // #region agent log b7eaa7 crear_tarea entry
        try { @file_put_contents(__DIR__ . '/../debug-b7eaa7.log', json_encode([
            'sessionId'=>'b7eaa7','runId'=>'pre','hypothesisId'=>'CT1',
            'location'=>'controllers/CoordinadorController.php:crearTarea:entry',
            'message'=>'post_received',
            'data'=>[
                'hasSessionUserId'=>isset($_SESSION['user_id'])?1:0,
                'coordinadorIdLen'=>strlen((string)($coordinador_id??'')),
                'asesorIdLen'=>strlen((string)($asesor_id??'')),
                'baseId'=>(int)($carga_id??0),
                'cantidad'=>(int)$cantidad_clientes,
                'solo_no_gestionados'=>isset($_POST['solo_no_gestionados']) ? (int)$_POST['solo_no_gestionados'] : 1,
                'hasObligFilters'=>((isset($_POST['saldo_min'])&&$_POST['saldo_min']!=='')||(isset($_POST['saldo_max'])&&$_POST['saldo_max']!=='')||(isset($_POST['mora_min'])&&$_POST['mora_min']!=='')||(isset($_POST['mora_max'])&&$_POST['mora_max']!=='')||((string)($_POST['franja']??'')!==''))?1:0,
                'hasGestionFilters'=>(((string)($_POST['forma_contacto']??'')!=='')||((string)($_POST['tipo_contacto']??'')!=='')||((string)($_POST['resultado_contacto']??'')!=='')||((string)($_POST['razon_especifica']??'')!==''))?1:0,
            ],
            'timestamp'=>(int) round(microtime(true)*1000)
        ], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n", FILE_APPEND); } catch (Throwable $e) {}
        // #endregion

        if (!$nombre_tarea || !$asesor_id || !$carga_id || $cantidad_clientes <= 0) {
            // #region agent log b7eaa7 crear_tarea invalid required
            try { @file_put_contents(__DIR__ . '/../debug-b7eaa7.log', json_encode(['sessionId'=>'b7eaa7','runId'=>'pre','hypothesisId'=>'CT2','location'=>'controllers/CoordinadorController.php:crearTarea:req','message'=>'missing_required','data'=>['asesorOk'=>!empty($asesor_id)?1:0,'baseOk'=>!empty($carga_id)?1:0,'cantidadOk'=>$cantidad_clientes>0?1:0],'timestamp'=>(int) round(microtime(true)*1000)], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n", FILE_APPEND); } catch (Throwable $e) {}
            // #endregion
            $_SESSION['error_message'] = 'Faltan datos requeridos para crear la tarea';
            header('Location: index.php?action=gestionar_tareas');
            exit;
        }

        // Verificar que el asesor está asignado a esta base
        $asesoresBase = $this->getAsesoresByBase($carga_id);
        $asesorValido = false;
        foreach ($asesoresBase as $asesor) {
            if ($asesor['id'] == $asesor_id) {
                $asesorValido = true;
                break;
            }
        }

        if (!$asesorValido) {
            // #region agent log b7eaa7 crear_tarea asesor no valido
            try { @file_put_contents(__DIR__ . '/../debug-b7eaa7.log', json_encode(['sessionId'=>'b7eaa7','runId'=>'pre','hypothesisId'=>'CT3','location'=>'controllers/CoordinadorController.php:crearTarea:asesor','message'=>'asesor_no_valido','data'=>['baseId'=>(int)$carga_id,'asesorIdLen'=>strlen((string)$asesor_id),'asesoresBaseCount'=>is_array($asesoresBase)?count($asesoresBase):-1],'timestamp'=>(int) round(microtime(true)*1000)], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n", FILE_APPEND); } catch (Throwable $e) {}
            // #endregion
            $_SESSION['error_message'] = 'El asesor seleccionado no está asignado a esta base';
            header('Location: index.php?action=gestionar_tareas');
            exit;
        }

        $filtros = [
            'saldo_min' => $saldo_min,
            'saldo_max' => $saldo_max,
            'mora_min' => $mora_min,
            'mora_max' => $mora_max,
            'franja' => $franja,
            'solo_no_gestionados' => $solo_no_gestionados ? 1 : 0,
            'forma_contacto' => $forma_contacto,
            'tipo_contacto' => $tipo_contacto,
            'resultado_contacto' => $resultado_contacto,
            'razon_especifica' => $razon_especifica,
        ];

        // Validar que el número solicitado no supere el total del filtro
        $totalFiltrados = $this->contarClientesFiltradosBase((int)$carga_id, $filtros);
        // #region agent log b7eaa7 crear_tarea total filtrados
        try { @file_put_contents(__DIR__ . '/../debug-b7eaa7.log', json_encode(['sessionId'=>'b7eaa7','runId'=>'pre','hypothesisId'=>'CT4','location'=>'controllers/CoordinadorController.php:crearTarea:count','message'=>'total_filtrados','data'=>['baseId'=>(int)$carga_id,'cantidad'=>(int)$cantidad_clientes,'totalFiltrados'=>(int)$totalFiltrados],'timestamp'=>(int) round(microtime(true)*1000)], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n", FILE_APPEND); } catch (Throwable $e) {}
        // #endregion
        if ($totalFiltrados <= 0) {
            $_SESSION['error_message'] = 'No hay clientes que cumplan el filtro seleccionado en esta base';
            header('Location: index.php?action=gestionar_tareas');
            exit;
        }
        if ($cantidad_clientes > $totalFiltrados) {
            // #region agent log b7eaa7 crear_tarea cantidad mayor
            try { @file_put_contents(__DIR__ . '/../debug-b7eaa7.log', json_encode(['sessionId'=>'b7eaa7','runId'=>'pre','hypothesisId'=>'CT5','location'=>'controllers/CoordinadorController.php:crearTarea:count','message'=>'cantidad_supera_total','data'=>['cantidad'=>(int)$cantidad_clientes,'totalFiltrados'=>(int)$totalFiltrados],'timestamp'=>(int) round(microtime(true)*1000)], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n", FILE_APPEND); } catch (Throwable $e) {}
            // #endregion
            $_SESSION['error_message'] = "La cantidad a asignar ({$cantidad_clientes}) no puede ser mayor al total del filtro ({$totalFiltrados}).";
            header('Location: index.php?action=gestionar_tareas');
            exit;
        }

        // Obtener clientes filtrados de la base (aleatorio)
        $clientesNoGestionados = $this->getClientesFiltradosBase((int)$carga_id, $filtros, $cantidad_clientes);

        if (empty($clientesNoGestionados)) {
            $_SESSION['error_message'] = 'No hay clientes disponibles con el filtro seleccionado en esta base';
            header('Location: index.php?action=gestionar_tareas');
            exit;
        }

        $datos = [
            'asesor_id' => $asesor_id,
            'carga_id' => $carga_id,
            'cliente_ids' => array_column($clientesNoGestionados, 'id'),
            'nombre_tarea' => $nombre_tarea,
            'descripcion' => "Tarea de {$cantidad_clientes} clientes de la base",
            'prioridad' => 'media',
            'fecha_vencimiento' => null,
            'coordinador_id' => $coordinador_id
        ];

        $tarea_id = $this->tareaModel->crearTarea($datos);
        // #region agent log b7eaa7 crear_tarea insert result
        try { @file_put_contents(__DIR__ . '/../debug-b7eaa7.log', json_encode([
            'sessionId'=>'b7eaa7','runId'=>'pre','hypothesisId'=>'CT6',
            'location'=>'controllers/CoordinadorController.php:crearTarea:insert',
            'message'=>'tareaModel_crearTarea_return',
            'data'=>[
                'tareaId'=>(int)($tarea_id?:0),
                'clienteIdsCount'=>is_array($datos['cliente_ids']??null)?count($datos['cliente_ids']):-1,
                'coordinadorIdLen'=>strlen((string)$coordinador_id),
                'asesorIdLen'=>strlen((string)$asesor_id),
                'baseId'=>(int)$carga_id,
            ],
            'timestamp'=>(int) round(microtime(true)*1000)
        ], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n", FILE_APPEND); } catch (Throwable $e) {}
        // #endregion

        if ($tarea_id) {
            $_SESSION['success_message'] = "Tarea creada exitosamente. Se asignaron {$cantidad_clientes} clientes no gestionados al asesor.";
        } else {
            $_SESSION['error_message'] = 'Error al crear la tarea';
        }

        header('Location: index.php?action=gestionar_tareas');
        exit;
    }

    /**
     * Crear tarea mediante CSV (cédulas)
     *
     * Reglas:
     * - CSV debe contener una columna "cedula" (header).
     * - Solo se asignan clientes cuya cédula exista en la base seleccionada.
     * - Se registra el cargue en `carga_csv_tareas`.
     */
    public function crearTareaCsv() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?action=gestionar_tareas');
            exit;
        }

        $coordinador_id = $_SESSION['user_id'];
        $nombre_tarea = isset($_POST['nombre_tarea']) ? trim((string)$_POST['nombre_tarea']) : '';
        $asesor_id = isset($_POST['asesor_id']) ? (string)$_POST['asesor_id'] : '';
        $carga_id = isset($_POST['carga_id']) ? (int)$_POST['carga_id'] : 0;

        // Reporte detallado para UI (por qué falló / qué se procesó)
        $csvReport = [
            'ok' => false,
            'base_id' => $carga_id,
            'asesor' => $asesor_id,
            'coordinador' => (string)$coordinador_id,
            'nombre_tarea' => $nombre_tarea,
            'archivo' => null,
            'delimiter' => null,
            'header_detected' => null,
            'cedula_column_index' => null,
            'rows_read' => 0,
            'cedulas_raw_count' => 0,
            'cedulas_valid_unique' => 0,
            'cedulas_invalid_count' => 0,
            'cedulas_found_count' => 0,
            'cedulas_not_found_count' => 0,
            'samples' => [
                'invalid' => [],
                'not_found' => [],
            ],
            'error' => null,
            'error_detail' => null,
        ];

        if (!$nombre_tarea || !$asesor_id || $carga_id <= 0) {
            $csvReport['error'] = 'missing_required';
            $csvReport['error_detail'] = 'Faltan nombre_tarea, asesor_id o base (carga_id).';
            $_SESSION['csv_import_report'] = $csvReport;
            $_SESSION['error_message'] = 'Faltan datos requeridos para crear la tarea por CSV';
            header('Location: index.php?action=gestionar_tareas');
            exit;
        }

        // Verificar que el asesor está asignado a esta base
        $asesoresBase = $this->getAsesoresByBase($carga_id);
        $asesorValido = false;
        foreach ($asesoresBase as $asesor) {
            if (($asesor['id'] ?? null) == $asesor_id) { $asesorValido = true; break; }
        }
        if (!$asesorValido) {
            $csvReport['error'] = 'asesor_not_assigned_to_base';
            $csvReport['error_detail'] = 'El asesor no pertenece a la base seleccionada.';
            $_SESSION['csv_import_report'] = $csvReport;
            $_SESSION['error_message'] = 'El asesor seleccionado no está asignado a esta base';
            header('Location: index.php?action=gestionar_tareas');
            exit;
        }

        if (!isset($_FILES['csv_cedulas']) || !is_array($_FILES['csv_cedulas'])) {
            $csvReport['error'] = 'missing_file';
            $csvReport['error_detail'] = 'No llegó el archivo en $_FILES[csv_cedulas].';
            $_SESSION['csv_import_report'] = $csvReport;
            $_SESSION['error_message'] = 'Debes subir un archivo CSV';
            header('Location: index.php?action=gestionar_tareas');
            exit;
        }

        $f = $_FILES['csv_cedulas'];
        $tmp = (string)($f['tmp_name'] ?? '');
        $origName = (string)($f['name'] ?? 'cedulas.csv');
        $err = (int)($f['error'] ?? UPLOAD_ERR_NO_FILE);
        $csvReport['archivo'] = $origName;

        if ($err !== UPLOAD_ERR_OK || !$tmp || !is_file($tmp)) {
            $csvReport['error'] = 'upload_error';
            $csvReport['error_detail'] = 'Error de carga: code=' . $err . '.';
            $_SESSION['csv_import_report'] = $csvReport;
            $_SESSION['error_message'] = 'No se pudo leer el CSV (error de carga)';
            header('Location: index.php?action=gestionar_tareas');
            exit;
        }

        // Leer cédulas del CSV
        $cedulas = [];
        $cedulasRaw = [];
        $cedulasInvalidRaw = [];
        $cedulaIndex = null;
        $delimiter = ',';

        $fh = @fopen($tmp, 'rb');
        if (!$fh) {
            $csvReport['error'] = 'open_failed';
            $csvReport['error_detail'] = 'No se pudo abrir el archivo temporal.';
            $_SESSION['csv_import_report'] = $csvReport;
            $_SESSION['error_message'] = 'No se pudo abrir el CSV';
            header('Location: index.php?action=gestionar_tareas');
            exit;
        }

        $firstRow = fgetcsv($fh, 0, $delimiter);
        if (is_array($firstRow) && count($firstRow) === 1 && strpos((string)$firstRow[0], ';') !== false) {
            // Probable CSV con ';'
            $delimiter = ';';
            rewind($fh);
            $firstRow = fgetcsv($fh, 0, $delimiter);
        }
        $csvReport['delimiter'] = $delimiter;

        if (!is_array($firstRow) || empty($firstRow)) {
            fclose($fh);
            $csvReport['error'] = 'empty_or_invalid_csv';
            $csvReport['error_detail'] = 'No se pudo leer la primera fila.';
            $_SESSION['csv_import_report'] = $csvReport;
            $_SESSION['error_message'] = 'El CSV está vacío o no es válido';
            header('Location: index.php?action=gestionar_tareas');
            exit;
        }

        // Detectar header
        $header = array_map(function ($v) {
            $v = is_string($v) ? $v : (string)$v;
            $v = trim($v);
            $v = mb_strtolower($v, 'UTF-8');
            $v = str_replace([' ', '-', '.'], '_', $v);
            return $v;
        }, $firstRow);

        foreach ($header as $i => $h) {
            if ($h === 'cedula' || $h === 'cedulas' || $h === 'documento' || $h === 'documento_identidad') {
                $cedulaIndex = (int)$i;
                break;
            }
        }
        if ($cedulaIndex === null) {
            // Si no hay header, asumimos primera columna como cédula
            $cedulaIndex = 0;
            $csvReport['header_detected'] = false;
            // Procesar también la primera fila como dato
            $firstVal = $firstRow[$cedulaIndex] ?? '';
            $cedulasRaw[] = (string)$firstVal;
        }
        if ($csvReport['header_detected'] === null) $csvReport['header_detected'] = true;
        $csvReport['cedula_column_index'] = $cedulaIndex;

        while (($row = fgetcsv($fh, 0, $delimiter)) !== false) {
            if (!is_array($row) || empty($row)) continue;
            $v = $row[$cedulaIndex] ?? '';
            $cedulasRaw[] = (string)$v;
        }
        fclose($fh);
        $csvReport['rows_read'] = count($cedulasRaw) + 1; // aprox: incluye primera fila
        $csvReport['cedulas_raw_count'] = count($cedulasRaw);

        foreach ($cedulasRaw as $v) {
            $c = preg_replace('/\D+/', '', (string)$v);
            $c = trim((string)$c);
            if ($c === '' || strlen($c) < 5 || strlen($c) > 12) {
                if (count($cedulasInvalidRaw) < 12 && trim((string)$v) !== '') {
                    $cedulasInvalidRaw[] = (string)$v;
                }
                continue;
            }
            $cedulas[$c] = true;
        }

        $cedulasList = array_keys($cedulas);
        $totalSubidas = count($cedulasList);
        $csvReport['cedulas_valid_unique'] = $totalSubidas;
        $csvReport['cedulas_invalid_count'] = max(0, count($cedulasRaw) - $totalSubidas);
        $csvReport['samples']['invalid'] = array_slice($cedulasInvalidRaw, 0, 10);

        if ($totalSubidas <= 0) {
            $this->insertCargaCsvTarea($carga_id, $asesor_id, (string)$coordinador_id, $origName, 0, 0, 0, null);
            $csvReport['error'] = 'no_valid_cedulas';
            $csvReport['error_detail'] = 'No se detectaron cédulas válidas (solo números, 5-12 dígitos).';
            $_SESSION['csv_import_report'] = $csvReport;
            $_SESSION['error_message'] = 'El CSV no contiene cédulas válidas';
            header('Location: index.php?action=gestionar_tareas');
            exit;
        }

        // Buscar clientes que existan en la base con esas cédulas
        $clienteIds = [];
        $cedulasEncontradas = [];
        $chunkSize = 900;
        for ($i = 0; $i < $totalSubidas; $i += $chunkSize) {
            $chunk = array_slice($cedulasList, $i, $chunkSize);
            if (empty($chunk)) continue;
            $in = implode(',', array_fill(0, count($chunk), '?'));
            $sql = "SELECT id_cliente, cedula FROM clientes WHERE base_id = ? AND cedula IN ($in)";
            $stmt = $this->pdo->prepare($sql);
            $params = array_merge([(int)$carga_id], array_values($chunk));
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            foreach ($rows as $r) {
                $idc = (int)($r['id_cliente'] ?? 0);
                $ced = (string)($r['cedula'] ?? '');
                if ($idc > 0) $clienteIds[] = $idc;
                $ced = preg_replace('/\D+/', '', $ced);
                if ($ced !== '') $cedulasEncontradas[$ced] = true;
            }
        }
        $clienteIds = array_values(array_unique(array_map('intval', $clienteIds)));
        $totalEncontradas = count($clienteIds);
        $totalNoEncontradas = max(0, $totalSubidas - count($cedulasEncontradas));
        $csvReport['cedulas_found_count'] = $totalEncontradas;
        $csvReport['cedulas_not_found_count'] = $totalNoEncontradas;

        $notFoundSamples = [];
        if ($totalNoEncontradas > 0) {
            foreach ($cedulasList as $c) {
                if (!isset($cedulasEncontradas[$c])) {
                    $notFoundSamples[] = $c;
                    if (count($notFoundSamples) >= 10) break;
                }
            }
        }
        $csvReport['samples']['not_found'] = $notFoundSamples;

        if ($totalEncontradas <= 0) {
            $this->insertCargaCsvTarea($carga_id, $asesor_id, (string)$coordinador_id, $origName, $totalSubidas, 0, $totalSubidas, null);
            $csvReport['error'] = 'none_found_in_base';
            $csvReport['error_detail'] = 'Ninguna cédula del CSV coincide con clientes de la base seleccionada.';
            $_SESSION['csv_import_report'] = $csvReport;
            $_SESSION['error_message'] = 'Ninguna cédula del CSV existe en la base seleccionada';
            header('Location: index.php?action=gestionar_tareas');
            exit;
        }

        // Crear tarea con clientes encontrados
        $datos = [
            'asesor_id' => $asesor_id,
            'carga_id' => $carga_id,
            'cliente_ids' => $clienteIds,
            'nombre_tarea' => $nombre_tarea,
            'descripcion' => "Tarea por CSV ({$totalEncontradas} clientes encontrados)",
            'prioridad' => 'media',
            'fecha_vencimiento' => null,
            'coordinador_id' => $coordinador_id,
        ];

        $tarea_id = $this->tareaModel->crearTarea($datos);
        $this->insertCargaCsvTarea($carga_id, $asesor_id, (string)$coordinador_id, $origName, $totalSubidas, $totalEncontradas, $totalNoEncontradas, $tarea_id ?: null);

        if ($tarea_id) {
            $csvReport['ok'] = true;
            $_SESSION['csv_import_report'] = $csvReport;
            $_SESSION['success_message'] = "Tarea CSV creada. Subidas: {$totalSubidas}. Encontradas: {$totalEncontradas}. No encontradas: {$totalNoEncontradas}.";
        } else {
            $csvReport['error'] = 'tarea_create_failed';
            $csvReport['error_detail'] = 'El CSV se procesó, pero TareaModel::crearTarea() devolvió false/0.';
            $_SESSION['csv_import_report'] = $csvReport;
            $_SESSION['error_message'] = 'Se procesó el CSV, pero ocurrió un error al crear la tarea';
        }

        header('Location: index.php?action=gestionar_tareas');
        exit;
    }

    private function insertCargaCsvTarea(int $baseId, string $asesorCedula, string $coordinadorCedula, string $nombreArchivo, int $subidas, int $encontradas, int $noEncontradas, $tareaId): void {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO carga_csv_tareas
                    (base_id, asesor_cedula, coordinador_cedula, nombre_archivo, cedulas_subidas, cedulas_encontradas, cedulas_no_encontradas, tarea_id)
                VALUES
                    (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                (int)$baseId,
                (string)$asesorCedula,
                (string)$coordinadorCedula,
                (string)$nombreArchivo,
                (int)$subidas,
                (int)$encontradas,
                (int)$noEncontradas,
                $tareaId !== null ? (int)$tareaId : null,
            ]);
        } catch (Throwable $e) {
            // No romper el flujo por fallo de logging de carga
        }
    }

    /**
     * Asignar base completa a asesor
     */
    public function asignarBaseCompleta() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?action=list_cargas');
            exit;
        }

        $coordinador_id = $_SESSION['user_id'];
        $carga_id = $_POST['carga_id'] ?? null;
        $asesor_id = $_POST['asesor_id'] ?? null;

        if (!$carga_id || !$asesor_id) {
            $_SESSION['error_message'] = 'Faltan datos requeridos';
            header('Location: index.php?action=list_cargas');
            exit;
        }

        $resultado = $this->cargaExcelModel->asignarAsesorABaseDatos($carga_id, $asesor_id);

        if ($resultado) {
            $_SESSION['success_message'] = 'Base asignada exitosamente. El asesor tendrá acceso completo a todos los clientes de esta base.';
        } else {
            $_SESSION['error_message'] = 'Error al asignar la base';
        }

        header('Location: index.php?action=list_cargas');
        exit;
    }

    /**
     * Liberar base de asesor
     */
    public function liberarBase() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?action=list_cargas');
            exit;
        }

        $carga_id = $_POST['carga_id'] ?? null;
        $asesor_id = $_POST['asesor_id'] ?? null;

        if (!$carga_id || !$asesor_id) {
            $_SESSION['error_message'] = 'Faltan datos requeridos';
            header('Location: index.php?action=list_cargas');
            exit;
        }

        $resultado = $this->cargaExcelModel->liberarAsesorDeBaseDatos($carga_id, $asesor_id);

        if ($resultado) {
            $_SESSION['success_message'] = 'Base liberada exitosamente';
        } else {
            $_SESSION['error_message'] = 'Error al liberar la base';
        }

        header('Location: index.php?action=list_cargas');
        exit;
    }

    /**
     * Obtener clientes de una carga para selección en tareas
     */
    public function getClientesCarga() {
        $carga_id = $_GET['carga_id'] ?? null;
        $coordinador_id = $_SESSION['user_id'];

        if (!$carga_id) {
            echo json_encode(['error' => 'ID de carga requerido']);
            exit;
        }

        // Verificar que la carga pertenece al coordinador
        $carga = $this->cargaExcelModel->getCargaByIdAndCoordinador($carga_id, $coordinador_id);
        if (!$carga) {
            echo json_encode(['error' => 'No tienes permisos para acceder a esta carga']);
            exit;
        }

        $clientes = $this->clienteModel->getClientsByCargaId($carga_id);
        
        echo json_encode([
            'success' => true,
            'clientes' => $clientes,
            'total' => count($clientes)
        ]);
        exit;
    }

    /**
     * Obtener asesores disponibles para una carga
     * Excluye los asesores que ya tienen acceso a esta base
     */
    public function getAsesoresDisponiblesCarga() {
        if (ob_get_level()) ob_clean();
        
        $carga_id = $_GET['carga_id'] ?? null;
        $coordinador_id = $_SESSION['user_id'];

        if (!$carga_id) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'ID de carga requerido']);
            exit;
        }

        // Obtener asesores del coordinador que NO tienen acceso a esta base
        $asesores = $this->cargaExcelModel->getAsesoresDisponibles($coordinador_id, $carga_id);
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'asesores' => $asesores
        ]);
        exit;
    }

    /**
     * Obtener asesores asignados a una base específica
     */
    public function getAsesoresBase() {
        // Limpiar cualquier output previo
        if (ob_get_level()) ob_clean();
        
        // Configurar headers para JSON
        header('Content-Type: application/json');
        
        try {
            $carga_id = $_GET['carga_id'] ?? null;
            $coordinador_id = $_SESSION['user_id'];

            if (!$carga_id) {
                echo json_encode(['error' => 'ID de carga requerido']);
                exit;
            }

            // Verificar que la carga pertenece al coordinador
            $carga = $this->cargaExcelModel->getCargaByIdAndCoordinador($carga_id, $coordinador_id);
            if (!$carga) {
                echo json_encode(['error' => 'No tienes permisos para acceder a esta carga']);
                exit;
            }

            // Obtener asesores asignados a esta base específica
            $asesores = $this->getAsesoresByBase($carga_id);
            
            echo json_encode([
                'success' => true,
                'asesores' => $asesores
            ]);
            exit;
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => 'Error interno del servidor: ' . $e->getMessage()
            ]);
            exit;
        }
    }

    /**
     * Obtener información de clientes no gestionados en una base
     */
    public function getClientesNoGestionados() {
        // Limpiar cualquier output previo
        if (ob_get_level()) ob_clean();
        
        // Configurar headers para JSON
        header('Content-Type: application/json');
        
        try {
            $carga_id = $_GET['carga_id'] ?? null;
            $coordinador_id = $_SESSION['user_id'];

            if (!$carga_id) {
                echo json_encode(['error' => 'ID de carga requerido']);
                exit;
            }

            // Verificar que la carga pertenece al coordinador
            $carga = $this->cargaExcelModel->getCargaByIdAndCoordinador($carga_id, $coordinador_id);
            if (!$carga) {
                echo json_encode(['error' => 'No tienes permisos para acceder a esta carga']);
                exit;
            }

            // Obtener estadísticas de clientes
            $estadisticas = $this->getEstadisticasClientesBase($carga_id);

            // Aplicar filtro opcional (mismo payload que el modal)
            $filtros = [
                'saldo_min' => isset($_GET['saldo_min']) && $_GET['saldo_min'] !== '' ? (float)$_GET['saldo_min'] : null,
                'saldo_max' => isset($_GET['saldo_max']) && $_GET['saldo_max'] !== '' ? (float)$_GET['saldo_max'] : null,
                'mora_min' => isset($_GET['mora_min']) && $_GET['mora_min'] !== '' ? (int)$_GET['mora_min'] : null,
                'mora_max' => isset($_GET['mora_max']) && $_GET['mora_max'] !== '' ? (int)$_GET['mora_max'] : null,
                'franja' => isset($_GET['franja']) ? trim((string)$_GET['franja']) : '',
                'solo_no_gestionados' => isset($_GET['solo_no_gestionados']) ? (int)$_GET['solo_no_gestionados'] : 1,
                'forma_contacto' => isset($_GET['forma_contacto']) ? trim((string)$_GET['forma_contacto']) : '',
                'tipo_contacto' => isset($_GET['tipo_contacto']) ? trim((string)$_GET['tipo_contacto']) : '',
                'resultado_contacto' => isset($_GET['resultado_contacto']) ? trim((string)$_GET['resultado_contacto']) : '',
                'razon_especifica' => isset($_GET['razon_especifica']) ? trim((string)$_GET['razon_especifica']) : '',
            ];
            $totalFiltrados = $this->contarClientesFiltradosBase((int)$carga_id, $filtros);
            
            echo json_encode([
                'success' => true,
                'total_clientes' => (int)($estadisticas['total_clientes'] ?? 0),
                'total_no_gestionados' => (int)($estadisticas['total_no_gestionados'] ?? 0),
                'total_filtrados' => (int)$totalFiltrados
            ]);
            exit;
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => 'Error interno del servidor: ' . $e->getMessage()
            ]);
            exit;
        }
    }

    /**
     * Obtener bases asignadas a un asesor
     */
    public function getBasesAsignadasAsesor() {
        $asesor_id = $_GET['asesor_id'] ?? null;
        $coordinador_id = $_SESSION['user_id'];

        if (!$asesor_id) {
            echo json_encode(['error' => 'ID de asesor requerido']);
            exit;
        }

        // Verificar que el asesor pertenece al coordinador
        $asesor = $this->usuarioModel->getUsuarioById($asesor_id);
        if (!$asesor || $asesor['coordinador_id'] != $coordinador_id) {
            echo json_encode(['error' => 'No tienes permisos para acceder a este asesor']);
            exit;
        }

        $bases = $this->tareaModel->getBasesAsignadasByAsesor($asesor_id);
        
        echo json_encode([
            'success' => true,
            'bases' => $bases
        ]);
        exit;
    }

    /**
     * Actualizar estado de tarea
     */
    public function actualizarEstadoTarea() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['error' => 'Método no permitido']);
            exit;
        }

        $tarea_id = $_POST['tarea_id'] ?? null;
        $nuevo_estado = $_POST['estado'] ?? null;
        $coordinador_id = $_SESSION['user_id'];

        if (!$tarea_id || !$nuevo_estado) {
            echo json_encode(['error' => 'Faltan datos requeridos']);
            exit;
        }

        // Verificar que la tarea pertenece al coordinador
        $tareas = $this->getTareasByCoordinador($coordinador_id);
        $tarea_existe = false;
        foreach ($tareas as $tarea) {
            if ($tarea['id'] == $tarea_id) {
                $tarea_existe = true;
                break;
            }
        }

        if (!$tarea_existe) {
            echo json_encode(['error' => 'No tienes permisos para modificar esta tarea']);
            exit;
        }

        $resultado = $this->tareaModel->actualizarEstadoTarea($tarea_id, $nuevo_estado, $coordinador_id);

        if ($resultado) {
            echo json_encode(['success' => true, 'message' => 'Estado actualizado correctamente']);
        } else {
            echo json_encode(['error' => 'Error al actualizar el estado']);
        }
        exit;
    }

    /**
     * ===== Helpers internos (evita dependencia de métodos legacy en modelos) =====
     */
    private function getTareasByCoordinador($coordinadorCedula) {
        $sql = "
            SELECT
                t.*,
                b.nombre as nombre_cargue,
                u.nombre as asesor_nombre
            FROM tareas t
            JOIN base_clientes b ON t.base_id = b.id_base
            LEFT JOIN usuarios u ON t.asesor_cedula = u.cedula
            WHERE t.coordinador_cedula = ?
            ORDER BY t.fecha_creacion DESC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([(string)$coordinadorCedula]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$t) {
            $t['id'] = $t['id_tarea'];
            $t['carga_id'] = $t['base_id'];
            $t['cliente_ids'] = json_decode((string)($t['clientes_asignados'] ?? '[]'), true) ?: [];
            $t['total_clientes'] = is_array($t['cliente_ids']) ? count($t['cliente_ids']) : 0;
        }
        return $rows;
    }

    private function getEstadisticasTareas($coordinadorCedula) {
        $sql = "
            SELECT
                COUNT(*) as total,
                SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) as pendientes,
                SUM(CASE WHEN estado = 'en progreso' THEN 1 ELSE 0 END) as en_progreso,
                SUM(CASE WHEN estado = 'completa' THEN 1 ELSE 0 END) as completas,
                SUM(CASE WHEN estado = 'cancelada' THEN 1 ELSE 0 END) as canceladas
            FROM tareas
            WHERE coordinador_cedula = ?
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([(string)$coordinadorCedula]);
        $r = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        return [
            'total' => (int)($r['total'] ?? 0),
            'pendientes' => (int)($r['pendientes'] ?? 0),
            'en_progreso' => (int)($r['en_progreso'] ?? 0),
            'completas' => (int)($r['completas'] ?? 0),
            'canceladas' => (int)($r['canceladas'] ?? 0),
        ];
    }

    private function getAsesoresByBase($baseId) {
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

    private function getClientesNoGestionadosBase($baseId, $limit = 100) {
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

    private function buildSqlClientesFiltradosBase(int $baseId, array $filtros): array {
        $wheres = ["c.base_id = ?"];
        $params = [$baseId];

        $soloNoGestionados = (int)($filtros['solo_no_gestionados'] ?? 1) === 1;
        if ($soloNoGestionados) {
            $wheres[] = "NOT EXISTS (SELECT 1 FROM historial_gestiones hg0 WHERE hg0.cliente_id = c.id_cliente)";
        }

        // Obligaciones (si hay algún filtro de obligaciones, debe existir al menos una obligación que cumpla)
        $saldoMin = $filtros['saldo_min'] ?? null;
        $saldoMax = $filtros['saldo_max'] ?? null;
        $moraMin = $filtros['mora_min'] ?? null;
        $moraMax = $filtros['mora_max'] ?? null;
        $franja = isset($filtros['franja']) ? trim((string)$filtros['franja']) : '';

        $hasObligFilters = ($saldoMin !== null) || ($saldoMax !== null) || ($moraMin !== null) || ($moraMax !== null) || ($franja !== '');
        if ($hasObligFilters) {
            $ob = ["o.cliente_id = c.id_cliente", "o.base_id = c.base_id", "o.saldo > 0"];
            if ($saldoMin !== null) { $ob[] = "o.saldo >= ?"; $params[] = (float)$saldoMin; }
            if ($saldoMax !== null) { $ob[] = "o.saldo <= ?"; $params[] = (float)$saldoMax; }
            if ($moraMin !== null)  { $ob[] = "o.dias_mora >= ?"; $params[] = (int)$moraMin; }
            if ($moraMax !== null)  { $ob[] = "o.dias_mora <= ?"; $params[] = (int)$moraMax; }
            if ($franja !== '')     { $ob[] = "o.franja = ?"; $params[] = $franja; }
            $wheres[] = "EXISTS (SELECT 1 FROM obligaciones o WHERE " . implode(" AND ", $ob) . ")";
        }

        // Gestión (si hay algún filtro de gestión, debe existir al menos una gestión que cumpla)
        $forma = isset($filtros['forma_contacto']) ? trim((string)$filtros['forma_contacto']) : '';
        $tipo = isset($filtros['tipo_contacto']) ? trim((string)$filtros['tipo_contacto']) : '';
        $resultado = isset($filtros['resultado_contacto']) ? trim((string)$filtros['resultado_contacto']) : '';
        $razon = isset($filtros['razon_especifica']) ? trim((string)$filtros['razon_especifica']) : '';
        $hasGestionFilters = ($forma !== '') || ($tipo !== '') || ($resultado !== '') || ($razon !== '');
        if ($hasGestionFilters) {
            $hg = ["hg.cliente_id = c.id_cliente"];
            if ($forma !== '')     { $hg[] = "UPPER(TRIM(hg.forma_contacto)) = UPPER(?)"; $params[] = $forma; }
            if ($tipo !== '')      { $hg[] = "UPPER(TRIM(hg.tipo_contacto)) = UPPER(?)"; $params[] = $tipo; }
            if ($resultado !== '') { $hg[] = "UPPER(TRIM(hg.resultado_contacto)) = UPPER(?)"; $params[] = $resultado; }
            if ($razon !== '')     { $hg[] = "UPPER(TRIM(hg.razon_especifica)) = UPPER(?)"; $params[] = $razon; }
            $wheres[] = "EXISTS (SELECT 1 FROM historial_gestiones hg WHERE " . implode(" AND ", $hg) . ")";
        }

        return [
            'where' => implode(" AND ", $wheres),
            'params' => $params
        ];
    }

    private function contarClientesFiltradosBase(int $baseId, array $filtros): int {
        $built = $this->buildSqlClientesFiltradosBase($baseId, $filtros);
        $sql = "SELECT COUNT(*) as total FROM clientes c WHERE " . $built['where'];
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($built['params']);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        return (int)($row['total'] ?? 0);
    }

    private function getClientesFiltradosBase(int $baseId, array $filtros, int $limit = 100): array {
        $limit = max(1, min(500, (int)$limit));
        $built = $this->buildSqlClientesFiltradosBase($baseId, $filtros);
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
            WHERE {$built['where']}
            ORDER BY RAND()
            LIMIT {$limit}
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($built['params']);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Opciones para filtros del modal (franjas y valores de gestión)
     */
    public function getOpcionesFiltrosTarea() {
        if (ob_get_level()) ob_clean();
        header('Content-Type: application/json; charset=UTF-8');

        try {
            $carga_id = $_GET['carga_id'] ?? null;
            $coordinador_id = $_SESSION['user_id'];
            if (!$carga_id) {
                echo json_encode(['success' => false, 'error' => 'ID de carga requerido']);
                exit;
            }
            $carga = $this->cargaExcelModel->getCargaByIdAndCoordinador($carga_id, $coordinador_id);
            if (!$carga) {
                echo json_encode(['success' => false, 'error' => 'No tienes permisos para acceder a esta carga']);
                exit;
            }

            $baseId = (int)$carga_id;

            $franjas = [];
            $stmt = $this->pdo->prepare("SELECT DISTINCT franja FROM obligaciones WHERE base_id = ? ORDER BY franja ASC");
            $stmt->execute([$baseId]);
            $franjas = array_values(array_filter(array_map('strval', array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'franja'))));

            $op = function(string $col) use ($baseId) {
                $sql = "
                    SELECT DISTINCT hg.{$col} as v
                    FROM historial_gestiones hg
                    JOIN clientes c ON c.id_cliente = hg.cliente_id
                    WHERE c.base_id = ?
                      AND hg.{$col} IS NOT NULL
                      AND TRIM(hg.{$col}) <> ''
                    ORDER BY hg.{$col} ASC
                    LIMIT 250
                ";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$baseId]);
                return array_values(array_filter(array_map('strval', array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'v'))));
            };

            echo json_encode([
                'success' => true,
                'franjas' => $franjas,
                'forma_contacto' => $op('forma_contacto'),
                'tipo_contacto' => $op('tipo_contacto'),
                'resultado_contacto' => $op('resultado_contacto'),
                'razon_especifica' => $op('razon_especifica'),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        } catch (Throwable $e) {
            echo json_encode(['success' => false, 'error' => 'server_error']);
            exit;
        }
    }

    private function getEstadisticasClientesBase($baseId) {
        $sql = "
            SELECT
                COUNT(*) as total_clientes,
                SUM(CASE WHEN hg.id_gestion IS NULL THEN 1 ELSE 0 END) as total_no_gestionados
            FROM clientes c
            LEFT JOIN historial_gestiones hg ON hg.cliente_id = c.id_cliente
            WHERE c.base_id = ?
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([(int)$baseId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: ['total_clientes' => 0, 'total_no_gestionados' => 0];
    }

    /**
     * Eliminar base de datos de clientes
     */
    public function eliminarBaseDatos() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?action=list_cargas');
            exit;
        }

        $carga_id = $_POST['carga_id'] ?? null;
        $coordinador_id = $_SESSION['user_id'];

        if (!$carga_id) {
            echo json_encode(['success' => false, 'message' => 'ID de carga no proporcionado']);
            exit;
        }

        try {
            // Verificar que la carga pertenece al coordinador
            $carga = $this->cargaExcelModel->getCargaByIdAndCoordinador($carga_id, $coordinador_id);
            if (!$carga) {
                echo json_encode(['success' => false, 'message' => 'No tienes permisos para eliminar esta base de datos']);
                exit;
            }

            // Verificar si hay asesores asignados a esta base
            $asesoresAsignados = $this->cargaExcelModel->getAsesoresAsignadosABase($carga_id);
            
            if (!empty($asesoresAsignados)) {
                // Intentar liberar automáticamente a todos los asesores
                $asesoresLiberados = $this->liberarTodosLosAsesoresDeBase($carga_id);
                
                if ($asesoresLiberados === 0) {
                echo json_encode([
                    'success' => false, 
                        'message' => 'No se puede eliminar la base de datos porque tiene asesores asignados y no se pudieron liberar automáticamente. Primero debes liberar a todos los asesores manualmente.'
                ]);
                exit;
                }
            }

            // Eliminar la base de datos
            $resultado = $this->cargaExcelModel->eliminarBaseDatos($carga_id);

            if ($resultado) {
                echo json_encode(['success' => true, 'message' => 'Base de datos eliminada exitosamente']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al eliminar la base de datos']);
            }

        } catch (Exception $e) {
            error_log("Error al eliminar base de datos: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
        }
        exit;
    }

    /**
     * Libera automáticamente a todos los asesores de una base de datos
     */
    private function liberarTodosLosAsesoresDeBase($cargaId) {
        try {
            $asesoresLiberados = 0;
            
            // Liberar asesores de asignaciones_clientes
            $sql1 = "UPDATE asignaciones_clientes ac 
                     INNER JOIN clientes c ON ac.cliente_id = c.id 
                     SET ac.estado = 'liberado' 
                     WHERE c.carga_excel_id = ? AND ac.estado = 'asignado'";
            $stmt1 = $this->pdo->prepare($sql1);
            $stmt1->execute([$cargaId]);
            $asesoresLiberados += $stmt1->rowCount();
            
            // Liberar asesores de asignaciones_base_asesor
            $sql2 = "UPDATE asignaciones_base_asesor 
                     SET estado = 'inactiva' 
                     WHERE carga_id = ? AND estado = 'activa'";
            $stmt2 = $this->pdo->prepare($sql2);
            $stmt2->execute([$cargaId]);
            $asesoresLiberados += $stmt2->rowCount();
            
            return $asesoresLiberados;
            
        } catch (Exception $e) {
            error_log("Error al liberar asesores de base: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Lee clientes desde un archivo CSV
     * Optimizado para archivos grandes
     */
    private function leerClientesCSV($archivo_path) {
        try {
            // Configurar límites para archivos grandes
            ini_set('max_execution_time', 0);
            ini_set('memory_limit', '2048M');
            
            $clientes = [];
            
            if (!file_exists($archivo_path)) {
                throw new Exception("El archivo no existe: " . $archivo_path);
            }
            
            $handle = fopen($archivo_path, 'r');
            if (!$handle) {
                throw new Exception("No se pudo abrir el archivo: " . $archivo_path);
            }
            
            // Leer encabezados
            $encabezados = fgetcsv($handle, 0, ',');
            if (!$encabezados) {
                fclose($handle);
                throw new Exception("No se pudieron leer los encabezados del archivo");
            }
            
            // Mapear encabezados
            $mapeo = $this->mapearEncabezadosCSV($encabezados);
            
            // Leer datos en lotes para optimizar memoria
            $lote = 0;
            $tamañoLote = 1000; // Leer de 1000 en 1000
            $linea = 1;
            
            while (($data = fgetcsv($handle, 0, ',')) !== FALSE) {
                $linea++;
                
                if (count($data) < count($encabezados)) {
                    continue; // Saltar líneas incompletas
                }
                
                $cliente = [];
                foreach ($mapeo as $campo => $indice) {
                    $cliente[$campo] = isset($data[$indice]) ? $this->limpiarDatoCSV($data[$indice]) : '';
                }
                
                // Validar datos mínimos
                if (!empty($cliente['nombre']) && !empty($cliente['cedula'])) {
                    $clientes[] = $cliente;
                }
                
                // Procesar lote cuando alcance el tamaño
                if (count($clientes) >= $tamañoLote) {
                    $lote++;
                    error_log("Procesando lote de lectura $lote con " . count($clientes) . " clientes");
                    
                    // Liberar memoria periódicamente
                    if (function_exists('gc_collect_cycles')) {
                        gc_collect_cycles();
                    }
                }
            }
            
            fclose($handle);
            error_log("Lectura completada. Total de clientes leídos: " . count($clientes));
            return $clientes;
            
        } catch (Exception $e) {
            error_log("Error en leerClientesCSV: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Procesar clientes CSV de forma optimizada para grandes volúmenes (30,000+ registros)
     * Usa bulk inserts y procesamiento en lotes grandes
     */
    private function procesarClientesCSVOptimizado($clientes, $cargaId, $coordinadorId, $actualizarExistentes = false) {
        // Configurar límites para archivos grandes
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '4096M'); // 4GB para archivos muy grandes
        
        $nuevos = 0;
        $duplicados = 0;
        $obligacionesDuplicadas = 0;
        $obligacionesCreadas = 0;
        $total = count($clientes);
        
        error_log("Iniciando procesamiento OPTIMIZADO de $total clientes para carga ID: $cargaId");
        
        // Agrupar datos por cédula
        $datosAgrupados = [];
        foreach ($clientes as $cliente) {
            $cedula = $cliente['cedula'] ?? '';
            if (empty($cedula)) continue; // Saltar si no hay cédula
            
            if (!isset($datosAgrupados[$cedula])) {
                // Preparar datos básicos del cliente
                $infoCliente = [
                    'cedula' => $cliente['cedula'],
                    'nombre' => $cliente['nombre'],
                    'telefono' => $cliente['telefono'] ?? null,
                    'celular2' => $cliente['telefono2'] ?? $cliente['celular2'] ?? null,
                    'email' => $cliente['email'] ?? null,
                    'direccion' => $cliente['direccion'] ?? null,
                    'ciudad' => $cliente['ciudad'] ?? null
                ];
                if (!empty($cliente['tel1']) && empty($infoCliente['telefono'])) {
                    $infoCliente['telefono'] = $cliente['tel1'];
                }
                if (!empty($cliente['tel2']) && empty($infoCliente['celular2'])) {
                    $infoCliente['celular2'] = $cliente['tel2'];
                }
                for ($ti = 3; $ti <= 10; $ti++) {
                    $tk = 'tel' . $ti;
                    if (!empty($cliente[$tk])) {
                        $infoCliente[$tk] = $cliente[$tk];
                    }
                }
                
                // Procesar teléfono3 (telefonos_3) y agregarlo a la primera columna disponible
                $telefono3 = $cliente['telefonos_3'] ?? null;
                if (!empty($telefono3) && trim($telefono3) !== '') {
                    $telefono3 = trim($telefono3);
                    // Buscar primera columna vacía desde cel3
                    $columnasAdicionales = ['cel3', 'cel4', 'cel5', 'cel6', 'cel7', 'cel8', 'cel9', 'cel10', 'cel11'];
                    foreach ($columnasAdicionales as $columna) {
                        if (empty($infoCliente[$columna])) {
                            $infoCliente[$columna] = $telefono3;
                            break;
                        }
                    }
                }
                
                $datosAgrupados[$cedula] = [
                    'info_cliente' => $infoCliente,
                    'obligaciones' => []
                ];
            }
            
            $datosAgrupados[$cedula]['obligaciones'][] = [
                'obligacion' => $cliente['numero_factura'] ?? $cliente['obligacion'],
                'numero_factura' => $cliente['numero_factura'] ?? null,
                'saldo' => $cliente['saldo'] ?? null,
                'dias_mora' => $cliente['dias_mora'] ?? null,
                'rmt' => $cliente['rmt'] ?? null,
                'numero_contrato' => $cliente['numero_contrato'] ?? null,
                'franja' => $cliente['franja'] ?? null,
                'telefono2' => $cliente['telefono2'] ?? null,
                'telefono3' => $cliente['telefonos_3'] ?? null,
                'propiedad' => $cliente['propiedad'] ?? null,
                'producto' => $cliente['producto'] ?? null,
                'medicion' => $cliente['medicion'] ?? null
            ];
        }
        
        // Procesar en lotes grandes (1000 clientes por lote)
        $lote = 0;
        $tamañoLote = 1000; // Lote más grande para mejor rendimiento
        $gruposArray = array_chunk($datosAgrupados, $tamañoLote, true);
        $totalLotes = count($gruposArray);
        
        foreach ($gruposArray as $loteGrupos) {
            // Mantener la conexión viva en procesos largos (evita HY000/2006).
            $this->asegurarConexionDB();

            $lote++;
            $progreso = round(($lote / $totalLotes) * 100, 2);
            error_log("Procesando lote $lote/$totalLotes ($progreso%) - " . count($loteGrupos) . " clientes");
            
            $this->pdo->beginTransaction();
            
            try {
                // OPTIMIZACIÓN 1: Verificar existencia de clientes en bulk
                $cedulasLote = array_keys($loteGrupos);
                $clientesExistentes = $this->clienteModel->getClientesByCedulasYCarga($cedulasLote, $cargaId);
                
                // Separar clientes nuevos y existentes
                $clientesNuevos = [];
                $clientesActualizar = [];
                $mapaClienteId = []; // [cedula => cliente_id]
                
                foreach ($loteGrupos as $cedula => $grupo) {
                    if (isset($clientesExistentes[$cedula])) {
                        $duplicados++;
                        $clienteId = $clientesExistentes[$cedula]['id'];
                        $mapaClienteId[$cedula] = $clienteId;
                        
                        if ($actualizarExistentes) {
                            $clientesActualizar[$cedula] = [
                                'id' => $clienteId,
                                'datos' => $grupo['info_cliente']
                            ];
                        }
                    } else {
                        // Preparar para bulk insert
                        $clientesNuevos[$cedula] = array_merge($grupo['info_cliente'], [
                            'carga_excel_id' => $cargaId
                        ]);
                    }
                }
                
                // OPTIMIZACIÓN 2: Crear clientes nuevos en bulk
                if (!empty($clientesNuevos)) {
                    $clientesNuevosArray = array_values($clientesNuevos);
                    $idsCreados = $this->clienteModel->crearClientesBulk($clientesNuevosArray);
                    
                    // Mapear IDs creados a cédulas
                    $cedulasNuevas = array_keys($clientesNuevos);
                    foreach ($idsCreados as $index => $clienteId) {
                        if (isset($cedulasNuevas[$index])) {
                            $mapaClienteId[$cedulasNuevas[$index]] = $clienteId;
                        }
                    }
                    
                    $nuevos += count($idsCreados);
                    error_log("Creados " . count($idsCreados) . " clientes nuevos en bulk");
                }
                
                // OPTIMIZACIÓN 3: Actualizar clientes existentes (si es necesario)
                if ($actualizarExistentes && !empty($clientesActualizar)) {
                    foreach ($clientesActualizar as $cedula => $info) {
                        $datosActualizacion = [];

                        if (!empty($info['datos']['nombre'])) $datosActualizacion['nombre'] = $info['datos']['nombre'];
                        if (!empty($info['datos']['email'])) $datosActualizacion['email'] = $info['datos']['email'];
                        if (!empty($info['datos']['ciudad'])) $datosActualizacion['ciudad'] = $info['datos']['ciudad'];

                        // Tabla real: tel1..tel10
                        if (!empty($info['datos']['telefono'])) $datosActualizacion['tel1'] = $info['datos']['telefono'];
                        if (!empty($info['datos']['celular2'])) $datosActualizacion['tel2'] = $info['datos']['celular2'];
                        if (!empty($info['datos']['telefono3'])) $datosActualizacion['tel3'] = $info['datos']['telefono3'];
                        
                        if (!empty($datosActualizacion)) {
                            $this->clienteModel->actualizarCliente($info['id'], $datosActualizacion);
                        }
                    }
                }
                
                // OPTIMIZACIÓN 4: Procesar facturas en bulk
                $facturasNuevas = [];
                $facturasActualizar = [];
                
                // Preparar verificación de facturas existentes en bulk
                $facturasVerificar = [];
                foreach ($loteGrupos as $cedula => $grupo) {
                    if (!isset($mapaClienteId[$cedula])) continue;
                    
                    $clienteId = $mapaClienteId[$cedula];
                    
                    foreach ($grupo['obligaciones'] as $obligacion) {
                        if (!empty($obligacion['numero_factura'])) {
                            $facturasVerificar[] = [
                                'numero_factura' => $obligacion['numero_factura'],
                                'cliente_id' => $clienteId
                            ];
                        }
                    }
                }
                
                // Verificar existencia de facturas en bulk
                $facturasExistentes = [];
                if (!empty($facturasVerificar)) {
                    $facturasExistentes = $this->facturacionModel->getFacturasByNumeroAndClienteBulk($facturasVerificar);
                }
                
                // Separar facturas nuevas y existentes
                foreach ($loteGrupos as $cedula => $grupo) {
                    if (!isset($mapaClienteId[$cedula])) continue;
                    
                    $clienteId = $mapaClienteId[$cedula];
                    $infoCliente = $grupo['info_cliente'];
                    
                    // Eliminar facturas que no están en CSV (solo si actualizarExistentes)
                    if ($actualizarExistentes && isset($clientesExistentes[$cedula])) {
                        $numerosFacturaCSV = array_filter(array_column($grupo['obligaciones'], 'numero_factura'));
                        if (!empty($numerosFacturaCSV)) {
                            $this->facturacionModel->eliminarFacturasNoIncluidas($clienteId, array_values($numerosFacturaCSV));
                        }
                    }
                    
                    foreach ($grupo['obligaciones'] as $obligacion) {
                        if (empty($obligacion['numero_factura'])) continue;
                        
                        $key = $obligacion['numero_factura'] . '-' . $clienteId;
                        
                        if (isset($facturasExistentes[$key])) {
                            // Factura existe - actualizar si es necesario
                            if ($actualizarExistentes) {
                                $facturaExistente = $facturasExistentes[$key];
                                $datosUpdate = [];
                                
                                if (isset($obligacion['saldo'])) $datosUpdate['saldo'] = $obligacion['saldo'];
                                if (isset($obligacion['dias_mora'])) $datosUpdate['dias_mora'] = $obligacion['dias_mora'];
                                if (!empty($obligacion['rmt'])) $datosUpdate['rmt'] = $obligacion['rmt'];
                                if (!empty($obligacion['numero_contrato'])) $datosUpdate['numero_contrato'] = $obligacion['numero_contrato'];
                                if (!empty($obligacion['franja'])) $datosUpdate['franja'] = $obligacion['franja'];
                                // En esquema real de obligaciones no existen campos de teléfono/cedula/nombre.
                                
                                if (!empty($datosUpdate)) {
                                    $this->facturacionModel->actualizarFactura($facturaExistente['id_obligacion'] ?? $facturaExistente['id'] ?? 0, $datosUpdate);
                                    $obligacionesCreadas++;
                                } else {
                                    $obligacionesDuplicadas++;
                                }
                            } else {
                                $obligacionesDuplicadas++;
                            }
                        } else {
                            // Factura nueva - preparar para bulk insert
                            $facturasNuevas[] = [
                                'base_id' => $cargaId,
                                'cliente_id' => $clienteId,
                                'numero_factura' => $obligacion['numero_factura'],
                                'saldo' => $obligacion['saldo'] ?? null,
                                'dias_mora' => $obligacion['dias_mora'] ?? null,
                                'rmt' => $obligacion['rmt'] ?? null,
                                'numero_contrato' => $obligacion['numero_contrato'] ?? null,
                                'franja' => $obligacion['franja'] ?? null,
                                'estado_factura' => 'pendiente'
                            ];
                        }
                    }
                }
                
                // OPTIMIZACIÓN 5: Crear facturas en bulk
                if (!empty($facturasNuevas)) {
                    // Procesar facturas en sub-lotes de 500 para evitar queries muy grandes
                    $subLotesFacturas = array_chunk($facturasNuevas, 500);
                    foreach ($subLotesFacturas as $subLote) {
                        $creadas = $this->facturacionModel->crearFacturasBulk($subLote);
                        $obligacionesCreadas += $creadas;
                    }
                    error_log("Creadas " . count($facturasNuevas) . " facturas nuevas en bulk");
                }
                
                $this->pdo->commit();
                error_log("Lote $lote/$totalLotes completado exitosamente");
                
            } catch (Exception $e) {
                $this->pdo->rollBack();
                error_log("Error en lote $lote: " . $e->getMessage());
                error_log("Stack trace: " . $e->getTraceAsString());
                continue;
            }
            
            // Liberar memoria después de cada lote
            if (function_exists('gc_collect_cycles')) {
                gc_collect_cycles();
            }
        }
        
        return [
            'nuevos' => $nuevos,
            'duplicados' => $duplicados,
            'obligaciones_creadas' => $obligacionesCreadas,
            'obligaciones_duplicadas' => $obligacionesDuplicadas
        ];
    }
}

