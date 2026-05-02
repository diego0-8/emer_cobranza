<?php
// Archivo: index.php
// Router principal de la aplicación optimizado

require_once __DIR__ . '/config.php';

// Obtener la acción antes de enviar headers
$action = $_GET['action'] ?? $_POST['action'] ?? 'login';

// #region agent log
error_log('[AGENTLOG a2fdce R0] index boot method=' . ($_SERVER['REQUEST_METHOD'] ?? 'unknown') . ' action=' . (string)$action);
// #endregion

// NO enviar headers HTML si es una acción de exportación o API (dejar que el controlador maneje los headers)
$accionesExportacion = [
    'exportar_gestion_asesor',
    'exportar_gestion_todos_asesores',
    'exportar_reporte_personalizado',
    'exportar_clientes',
    'exportar_cargas',
    'exportar_productos',
    'exportar_reporte_tmo'
];

$accionesAPI = [
    'buscar_clientes_por_termino',
    'buscar_cliente_por_cedula',
    'get_cliente_para_gestion',
    'get_tareas_pendientes',
    'obtener_siguiente_cliente',
    'obtener_historial_cliente',
    'obtener_datos_cliente',
    'obtener_contratos_cliente',
    'obtener_break_activo',
    'registrar_break',
    'verificar_contrasena_desbloqueo',
    'agregar_informacion_cliente',
    // Call log (softphone)
    'registrar_inicio_llamada',
    'registrar_fin_llamada',
    // Debug dashboard asesor (d54ef5)
    'client_debug_log_d54ef5',
    // JSON desde index (evitar Content-Type HTML previo)
    'get_telefono_data',
    'buscar_cliente',
    'obtener_actividades_tiempo_real',
    'obtener_actividades_cliente',
    'obtener_actividades_producto',
    'obtener_estadisticas_actividades',
    'obtener_historial_completo',
];

if (!in_array($action, $accionesExportacion) && !in_array($action, $accionesAPI)) {
    // Configurar headers para UTF-8 solo si NO es una exportación ni una API
    header('Content-Type: text/html; charset=UTF-8');
}

session_start();
require_once __DIR__ . '/models/UsuarioModel.php';
require_once __DIR__ . '/models/CargaExcelModel.php';
require_once __DIR__ . '/models/ClienteModel.php';
require_once __DIR__ . '/models/GestionModel.php';
require_once __DIR__ . '/models/TareaModel.php';
require_once __DIR__ . '/controllers/adminController.php';
require_once __DIR__ . '/controllers/CoordinadorController.php';
require_once __DIR__ . '/controllers/AsesorController.php';
require_once __DIR__ . '/controllers/ProductoClienteController.php';
require_once __DIR__ . '/controllers/ActividadController.php';

// Conexión a la base de datos
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    // Configurar charset para manejar correctamente caracteres especiales como Ñ
    $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("SET CHARACTER SET utf8mb4");
    error_log("Conexión a la base de datos exitosa con charset utf8mb4");
} catch (PDOException $e) {
    error_log("Error de conexión a la base de datos: " . $e->getMessage());
    die("Error de conexión: " . $e->getMessage());
}

$user_role = isset($_SESSION['user_role']) ? $_SESSION['user_role'] : '';

// Control de sesión
// Permitir acciones públicas sin sesión (login + procesamiento del login).
$accionesPublicas = ['login', 'process_login'];
if (!isset($_SESSION['user_id']) && !in_array($action, $accionesPublicas, true)) {
    // #region agent log
    error_log('[AGENTLOG a2fdce S1] redirect_to_login missing_user_id action=' . (string)$action);
    // #endregion
    header('Location: index.php?action=login');
    exit;
}

if (isset($_SESSION['user_id']) && empty($_SESSION['user_role'])) {
    // #region agent log
    error_log('[AGENTLOG a2fdce S2] destroy_session empty_user_role user_id_set=1 action=' . (string)$action);
    // #endregion
    session_unset();
    session_destroy();
    header('Location: index.php?action=login');
    exit;
}

// Función helper para redireccionar al login
if (!function_exists('redirectToLogin')) {
    function redirectToLogin() {
        header('Location: index.php?action=login');
        exit;
    }
}

// Función helper para verificar rol
if (!function_exists('requireRole')) {
    function requireRole($requiredRole) {
        global $user_role;
        if ($user_role !== $requiredRole) {
            redirectToLogin();
        }
    }
}

// Manejar acciones POST primero
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    // #region agent log
    error_log('[AGENTLOG a2fdce R1] index POST action=' . (string)$action . ' hasUsuario=' . (isset($_POST['usuario']) ? '1' : '0') . ' hasContrasena=' . (isset($_POST['contrasena']) ? '1' : '0'));
    // #endregion
    
    switch ($action) {
        case 'process_login':
            // #region agent log
            error_log('[AGENTLOG a2fdce R1] route=process_login enter');
            // #endregion
            $controller = new AdminController($pdo);
            $controller->processLogin();
            break;
            
        case 'process_create_usuario':
            requireRole('administrador');
            $controller = new AdminController($pdo);
            $controller->createUsuario();
            break;
            
        case 'process_update_usuario':
            requireRole('administrador');
            $controller = new AdminController($pdo);
            $controller->editUsuario($_POST['id']);
            break;

        case 'process_upload_bash':
            requireRole('administrador');
            $controller = new AdminController($pdo);
            $controller->procesarCargaBash();
            break;
            
        case 'process_asignar_asesor':
            requireRole('administrador');
            $controller = new AdminController($pdo);
            $controller->asignarAsesor();
            break;

        case 'crear_nueva_base':
            requireRole('coordinador');
            $controller = new CoordinadorController($pdo);
            $controller->crearNuevaBase();
            break;

        case 'agregar_a_base_existente':
            requireRole('coordinador');
            $controller = new CoordinadorController($pdo);
            $controller->agregarABaseExistente();
            break;

        case 'registrar_break':
        case 'verificar_contrasena_desbloqueo':
            requireRole('asesor');
            $controller = new AsesorController($pdo);
            if ($action === 'registrar_break') {
                $controller->registrarBreak();
            } elseif ($action === 'verificar_contrasena_desbloqueo') {
                $controller->verificarContrasenaDesbloqueo();
            }
            break;

        default:
            // #region agent log
            error_log('[AGENTLOG a2fdce R1] index POST default route action=' . (string)$action);
            // #endregion
            redirectToLogin();
    }
    exit;
}

// Router principal para GET requests
switch ($action) {
    case 'login':
        $controller = new AdminController($pdo);
        $controller->login();
        break;
        
    case 'logout':
        $controller = new AdminController($pdo);
        $controller->logout();
        break;
        
    case 'dashboard':
        // #region debug d200d9 index route
        try {
            @file_put_contents(__DIR__ . '/debug-d200d9.log', json_encode([
                'sessionId' => 'd200d9',
                'runId' => 'pre-fix',
                'hypothesisId' => 'R1',
                'location' => 'index.php:GET:dashboard',
                'message' => 'route',
                'data' => [
                    'action' => (string)$action,
                    'userRole' => (string)($user_role ?? ''),
                    'hasSessionUserId' => isset($_SESSION['user_id']) ? 1 : 0,
                ],
                'timestamp' => (int) round(microtime(true) * 1000),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n", FILE_APPEND);
        } catch (Throwable $e) {}
        // #endregion
        if ($user_role === 'administrador') {
            $controller = new AdminController($pdo);
        } elseif ($user_role === 'coordinador') {
            $controller = new CoordinadorController($pdo);
        } elseif ($user_role === 'asesor') {
            $controller = new AsesorController($pdo);
        } else {
            redirectToLogin();
        }
        $controller->dashboard();
        break;
        
    // Acciones de administrador
    case 'list_usuarios':
    case 'crear_usuario':
    case 'editar_usuario':
    case 'toggle_estado':
    case 'ver_actividades':
    case 'asignar_personal':
    case 'ver_gestion_coordinador':
    case 'ver_gestion_asesor':
    case 'asignar_asesor':
    case 'liberar_asesor':
    case 'descargar_plantilla_bash':
        requireRole('administrador');
        $controller = new AdminController($pdo);
        
        switch ($action) {
            case 'list_usuarios': $controller->listUsuarios(); break;
            case 'crear_usuario': $controller->createUsuario(); break;
            case 'editar_usuario': $controller->editUsuario($_GET['id']); break;
            case 'toggle_estado': $controller->toggleEstadoUsuario($_GET['id']); break;
            case 'ver_actividades': $controller->verActividades(); break;
            case 'asignar_personal': $controller->asignarPersonal(); break;
            case 'ver_gestion_coordinador': $controller->verGestionCoordinador($_GET['id']); break;
            case 'ver_gestion_asesor': $controller->verGestionAsesor($_GET['id']); break;
            case 'asignar_asesor': $controller->asignarAsesor(); break;
            case 'liberar_asesor': $controller->liberarAsesor($_GET['asesor_id'], $_GET['coordinador_id']); break;
            case 'descargar_plantilla_bash': $controller->descargarPlantillaBash(); break;
        }
        break;
        
    // Acciones de coordinador
    case 'tareas_coordinador':
    case 'gestionar_tareas':
    case 'crear_tarea':
    case 'crear_tarea_csv':
    case 'asignar_base_completa':
    case 'liberar_base':
    case 'get_clientes_carga':
    case 'get_asesores_disponibles_carga':
    case 'get_bases_asignadas_asesor':
    case 'actualizar_estado_tarea':
    case 'get_detalles_tarea':
    case 'get_asesores_base':
    case 'get_clientes_no_gestionados':
    case 'get_opciones_filtros_tarea':
    case 'gestionar_traspasos':
    case 'subir_excel':
    case 'crear_nueva_base':
    case 'gestion_cargas':
    case 'list_cargas':
    case 'descargas':
    case 'get_detalles_asesor':
    case 'ver_detalle_cliente':
    case 'ver_detalle_gestion_asesor':
    case 'agregar_a_base_existente':
    case 'liberar_clientes':
    case 'asignarClientes':
    case 'asignar_automatico':
    case 'resultados_equipo':
    case 'reportes_exportacion':
    case 'reporte_tmo':
    case 'exportar_reporte_tmo':
    case 'coord_call':
    case 'coord_call_gestion_modal':
    case 'ver_clientes':
    case 'buscar_clientes':
    case 'asignar_clientes':
    case 'ver_gestion_asesor':
    case 'get_asesores_disponibles':
    case 'get_asesores_asignados':
    case 'asignar_asesor_base':
    case 'liberar_asesor_base':
    case 'eliminar_base_datos':
    case 'gestionar_estado_bases':
    case 'cambiar_estado_base':
    case 'buscar_bases_datos':
    case 'transferir_recordatorio':
    case 'obtener_obligaciones_cliente':
        requireRole('coordinador');
        $controller = new CoordinadorController($pdo);
        
        switch ($action) {
            case 'tareas_coordinador':
            case 'gestionar_tareas': $controller->gestionarTareas(); break;
            case 'crear_tarea': $controller->crearTarea(); break;
            case 'crear_tarea_csv': $controller->crearTareaCsv(); break;
            case 'asignar_base_completa': $controller->asignarBaseCompleta(); break;
            case 'liberar_base': $controller->liberarBase(); break;
            case 'get_clientes_carga': $controller->getClientesCarga(); break;
            case 'get_asesores_disponibles_carga': $controller->getAsesoresDisponiblesCarga(); break;
            case 'get_bases_asignadas_asesor': $controller->getBasesAsignadasAsesor(); break;
            case 'actualizar_estado_tarea': $controller->actualizarEstadoTarea(); break;
            case 'get_detalles_tarea': $controller->getDetallesTarea(); break;
            case 'get_asesores_base': $controller->getAsesoresBase(); break;
            case 'get_clientes_no_gestionados': $controller->getClientesNoGestionados(); break;
            case 'get_opciones_filtros_tarea': $controller->getOpcionesFiltrosTarea(); break;
            case 'gestionar_traspasos': $controller->gestionarTraspasos(); break;
            case 'subir_excel': $controller->uploadExcel(); break;
            case 'crear_nueva_base': $controller->crearNuevaBase(); break;
            case 'gestion_cargas': $controller->gestionCargas(); break;
            case 'list_cargas': $controller->listCargas(); break;
            case 'descargas': $controller->descargas(); break;
            case 'get_detalles_asesor': $controller->getDetallesAsesor(); break;
            case 'ver_detalle_cliente': $controller->verDetalleCliente($_GET['id']); break;
            case 'ver_detalle_gestion_asesor': $controller->verDetalleGestionAsesor($_GET['cliente_id'], $_GET['asesor_id']); break;
            case 'agregar_a_base_existente': $controller->agregarABaseExistente(); break;
            case 'liberar_clientes': $controller->liberarTodosClientes(); break;
            case 'asignarClientes': $controller->asignarClientes(); break;
            case 'asignar_automatico': $controller->asignarAutomatico(); break;
            case 'resultados_equipo': $controller->resultadosEquipo(); break;
            case 'reportes_exportacion': $controller->reportesExportacion(); break;
            case 'reporte_tmo': $controller->reporteTMO(); break;
            case 'exportar_reporte_tmo': $controller->exportarReporteTMO(); break;
            case 'coord_call': $controller->coordCall(); break;
            case 'coord_call_gestion_modal': $controller->coordCallGestionModal(); break;
            case 'ver_clientes': $controller->verClientes(); break;
            case 'buscar_clientes': $controller->buscarClientes(); break;
            case 'asignar_clientes': $controller->asignarClientesVista(); break;
            case 'ver_gestion_asesor': $controller->verGestionAsesor(); break;
            case 'get_asesores_disponibles': $controller->getAsesoresDisponibles(); break;
            case 'get_asesores_asignados': $controller->getAsesoresAsignados(); break;
            case 'asignar_asesor_base': $controller->asignarAsesorBase(); break;
            case 'liberar_asesor_base': $controller->liberarAsesorBase(); break;
            case 'eliminar_base_datos': $controller->eliminarBaseDatos(); break;
            case 'gestionar_estado_bases': $controller->gestionarEstadoBases(); break;
            case 'cambiar_estado_base': $controller->cambiarEstadoBase(); break;
            case 'buscar_bases_datos': $controller->buscarBasesDatos(); break;
            case 'transferir_recordatorio': $controller->transferirRecordatorio(); break;
            case 'obtener_obligaciones_cliente': $controller->obtenerObligacionesCliente(); break;
        }
        break;
        
    // Acciones de asesor
    case 'mis_clientes':
    case 'mis_tareas':
    case 'gestionar_cliente':
    case 'guardar_tipificacion':
    case 'guardar_cliente_nuevo':
    case 'obtener_siguiente_cliente':
    case 'obtener_historial_cliente':
    case 'obtener_datos_cliente':
    case 'obtener_contratos_cliente':
    case 'gestionar_clientes':
    case 'buscar_cliente_por_cedula':
    case 'buscar_clientes_por_termino':
    case 'get_cliente_para_gestion':
    case 'get_tareas_pendientes':
    case 'completar_tarea':
    case 'gestionar_productos_cliente':
    case 'agregar_informacion_cliente':
    case 'registrar_break':
    case 'obtener_break_activo':
    case 'verificar_contrasena_desbloqueo':
    case 'client_debug_log':
    case 'client_debug_log_d54ef5':
    case 'registrar_inicio_llamada':
    case 'registrar_fin_llamada':
        requireRole('asesor');
        $controller = new AsesorController($pdo);
        
        switch ($action) {
            case 'mis_clientes': $controller->misClientes(); break;
            case 'mis_tareas': $controller->misTareas(); break;
            case 'gestionar_cliente': $controller->gestionarCliente($_GET['id']); break;
            case 'guardar_tipificacion': $controller->guardarTipificacion(); break;
            case 'guardar_cliente_nuevo': $controller->guardarClienteNuevo(); break;
            case 'obtener_siguiente_cliente': $controller->obtenerSiguienteCliente(); break;
            case 'obtener_historial_cliente': $controller->obtenerHistorialCliente(); break;
            case 'obtener_datos_cliente': $controller->obtenerDatosCliente(); break;
            case 'obtener_contratos_cliente': $controller->obtenerContratosCliente(); break;
            case 'gestionar_clientes': $controller->gestionarClientes(); break;
            case 'buscar_cliente_por_cedula': $controller->buscarClientePorCedula(); break;
            case 'buscar_clientes_por_termino': $controller->buscarClientesPorTermino(); break;
            case 'get_cliente_para_gestion': $controller->getClienteParaGestion(); break;
            case 'get_tareas_pendientes': $controller->getTareasPendientes(); break;
            case 'completar_tarea': $controller->completarTarea(); break;
            case 'gestionar_productos_cliente': $controller->gestionarProductosCliente(); break;
            case 'agregar_informacion_cliente': $controller->agregarInformacionCliente(); break;
            case 'registrar_break': $controller->registrarBreak(); break;
            case 'obtener_break_activo': $controller->obtenerBreakActivo(); break;
            case 'verificar_contrasena_desbloqueo': $controller->verificarContrasenaDesbloqueo(); break;
            case 'client_debug_log': $controller->clientDebugLog(); break;
            case 'client_debug_log_d54ef5': $controller->clientDebugLogD54ef5(); break;
            case 'registrar_inicio_llamada': $controller->registrarInicioLlamada(); break;
            case 'registrar_fin_llamada': $controller->registrarFinLlamada(); break;
        }
        break;
        
    // Acciones de productos
    case 'gestionar_productos':
    case 'crear_producto':
    case 'registrar_gestion_producto':
    case 'obtener_historial_producto':
    case 'obtener_productos_pendientes':
    case 'declinar_todos_productos':
    case 'obtener_estadisticas_productos':
        requireRole('asesor');
        $controller = new ProductoClienteController($pdo);
        
        switch ($action) {
            case 'gestionar_productos': $controller->gestionarProductos(); break;
            case 'crear_producto': $controller->crearProducto(); break;
            case 'registrar_gestion_producto': $controller->registrarGestionProducto(); break;
            case 'obtener_historial_producto': $controller->obtenerHistorialProducto(); break;
            case 'obtener_productos_pendientes': $controller->obtenerProductosPendientes(); break;
            case 'declinar_todos_productos': $controller->declinarTodosProductos(); break;
            case 'obtener_estadisticas_productos': $controller->obtenerEstadisticasProductos(); break;
        }
        break;
        
    // Acciones de exportación
    case 'exportar_gestion_asesor':
    case 'exportar_gestion_todos_asesores':
    case 'exportar_reporte_personalizado':
    case 'exportar_clientes':
    case 'exportar_cargas':
    case 'exportar_productos':
        requireRole('coordinador');
        $controller = new CoordinadorController($pdo);
        
        switch ($action) {
            case 'exportar_gestion_asesor':
                $filtros = [
                    'gestion' => $_GET['gestion'] ?? null,
                    'contacto' => $_GET['contacto'] ?? null,
                    'tipificacion' => $_GET['tipificacion'] ?? null,
                    'fecha_creacion_inicio' => $_GET['fecha_creacion_inicio'] ?? null,
                    'fecha_creacion_fin' => $_GET['fecha_creacion_fin'] ?? null
                ];
                $controller->exportarGestionAsesor($_GET['asesor_id'] ?? null, $_GET['fecha_inicio'] ?? null, $_GET['fecha_fin'] ?? null, $filtros);
                break;
            case 'exportar_gestion_todos_asesores': $controller->exportarGestionTodosAsesores($_GET['fecha_inicio'] ?? null, $_GET['fecha_fin'] ?? null); break;
            case 'exportar_reporte_personalizado': $controller->exportarReportePersonalizado($_GET); break;
            case 'exportar_clientes': $controller->exportarClientes($_GET['fecha_inicio'] ?? null, $_GET['fecha_fin'] ?? null, $_GET['estado_cliente'] ?? null); break;
            case 'exportar_cargas': $controller->exportarCargas($_GET['estado_carga'] ?? null); break;
            case 'exportar_productos':
                $controller = new ProductoClienteController($pdo);
                $controller->exportarProductos();
                break;
        }
        break;
        
    // Acciones de actividades
    case 'obtener_actividades_tiempo_real':
    case 'obtener_actividades_cliente':
    case 'obtener_actividades_producto':
    case 'obtener_estadisticas_actividades':
    case 'obtener_historial_completo':
        $controller = new ActividadController($pdo);
        
        switch ($action) {
            case 'obtener_actividades_tiempo_real': $controller->obtenerActividadesTiempoReal(); break;
            case 'obtener_actividades_cliente': $controller->obtenerActividadesCliente(); break;
            case 'obtener_actividades_producto': $controller->obtenerActividadesProducto(); break;
            case 'obtener_estadisticas_actividades': $controller->obtenerEstadisticasActividades(); break;
            case 'obtener_historial_completo': $controller->obtenerHistorialCompleto(); break;
        }
        break;
        
    // Acciones especiales
    case 'get_telefono_data':
        if (ob_get_level()) ob_clean();
        try {
            $usuarioModel = new UsuarioModel($pdo);
            $datosTelefono = $usuarioModel->getDatosTelefono($_SESSION['user_id']);
            $tieneTelefono = $usuarioModel->tieneTelefonoConfigurado($_SESSION['user_id']);
            
            header('Content-Type: application/json');
            header('Cache-Control: no-cache, must-revalidate');
            echo json_encode([
                'success' => true,
                'extension' => $datosTelefono['extension_telefono'] ?? '',
                'clave' => $datosTelefono['clave_webrtc'] ?? '',
                'tiene_telefono' => $tieneTelefono
            ]);
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => 'Error obteniendo datos de teléfono: ' . $e->getMessage()
            ]);
        }
        exit;
        break;
        
    case 'buscar_cliente':
        if (ob_get_level()) ob_clean();
        try {
            if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'asesor') {
                throw new Exception('Acceso no autorizado');
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input || !isset($input['tipo']) || !isset($input['termino'])) {
                throw new Exception('Datos de búsqueda incompletos');
            }
            
            $tipo = $input['tipo'];
            $termino = trim($input['termino']);
            
            if (empty($termino)) {
                throw new Exception('El término de búsqueda no puede estar vacío');
            }
            
            $clienteModel = new ClienteModel($pdo);
            
            $clientes = [];
            if ($tipo === 'telefono') {
                $clientes = $clienteModel->buscarPorTelefono($termino);
            } elseif ($tipo === 'cedula') {
                $clientes = $clienteModel->buscarPorCedula($termino);
            } else {
                throw new Exception('Tipo de búsqueda no válido');
            }
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'clientes' => $clientes
            ]);
            
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit;
        break;
        
    default:
        if (isset($_SESSION['user_id'])) {
            header('Location: index.php?action=dashboard');
        } else {
            header('Location: index.php?action=login');
        }
        exit;
}
?>
