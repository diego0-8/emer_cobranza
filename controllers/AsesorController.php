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
        $page_title = "Dashboard Profesional del Asesor";
        $asesor_id = $_SESSION['user_id'];
        
        // Obtener período seleccionado (día, semana, mes)
        $periodo = $_GET['periodo'] ?? 'dia';
        
        // Obtener métricas del dashboard para el período seleccionado
        $metricas = $this->gestionModel->getMetricasDashboard($asesor_id, $periodo);
        $tipificaciones = $this->gestionModel->getTipificacionesPorResultado($asesor_id, $periodo);
        $gestionesPorDia = $this->gestionModel->getGestionesUltimosDias($asesor_id, 7);
        
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
        $contactos_efectivos_hoy = $this->gestionModel->getContactosEfectivosHoy($asesor_id);
        $acuerdos_hoy = $this->gestionModel->getAcuerdosHoy($asesor_id);
        
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
            'gestiones_hoy' => $gestiones_hoy,
            'contactos_efectivos_hoy' => $contactos_efectivos_hoy,
            'acuerdos_hoy' => $acuerdos_hoy,
            'gestiones_semana' => $metricas_semana['gestiones_semana'] ?? 0,
            'contactos_efectivos_semana' => $metricas_semana['contactos_efectivos_semana'] ?? 0,
            'acuerdos_semana' => $metricas_semana['acuerdos_semana'] ?? 0,
            'gestiones_mes' => $metricas_mes['gestiones_mes'] ?? 0,
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

        require __DIR__ . '/../views/asesor_dashboard.php';
    }

    public function misClientes() {
        $page_title = "Mis Clientes";
        $asesorId = $_SESSION['user_id'];
        
        // Verificar si el asesor tiene tareas pendientes
        $tieneTareasPendientes = $this->tareaModel->tieneTareasPendientes($asesorId);
        
        if ($tieneTareasPendientes) {
            // Si tiene tareas pendientes, mostrar solo los clientes de las tareas
            $tareasPendientes = $this->tareaModel->getTareasPendientesByAsesor($asesorId);
            $clientesTareas = [];
            
            foreach ($tareasPendientes as $tarea) {
                $clientesTarea = $this->tareaModel->getClientesByTarea($tarea['id']);
                foreach ($clientesTarea as $cliente) {
                    // Calcular total de gestiones para este cliente
                    $totalGestiones = $this->gestionModel->getTotalGestionesByAsesorAndCliente($asesorId, $cliente['id']);
                    
                    $cliente['tarea_id'] = $tarea['id'];
                    $cliente['tarea_descripcion'] = $tarea['descripcion'];
                    $cliente['tarea_prioridad'] = $tarea['prioridad'];
                    $cliente['total_gestiones'] = $totalGestiones;
                    $clientesTareas[] = $cliente;
                }
            }
            
            $todosClientes = $clientesTareas;
        } else {
            // Si no tiene tareas pendientes, mostrar mensaje de "No tienes tareas pendientes"
            $todosClientes = [];
        }
        
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
            return $cliente['total_gestiones'] == 0;
        });
        
        $clientes_gestionados = array_filter($todosClientes, function($cliente) {
            return $cliente['total_gestiones'] > 0;
        });
        
        $clientes_con_ventas = array_filter($todosClientes, function($cliente) {
            return !empty($cliente['ultimo_resultado']) && 
                   in_array($cliente['ultimo_resultado'], ['Venta Exitosa', 'Venta en Frío', 'Venta con Seguimiento', 'Venta Cruzada']);
        });
        
        // Calcular estadísticas
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
        
        // Determinar qué pestaña está activa
        $pestaña_activa = isset($_GET['filter']) ? $_GET['filter'] : 'todos';
        
        // Calcular paginación para la pestaña activa
        switch ($pestaña_activa) {
            case 'pendientes':
                $total_clientes = count($clientes_pendientes);
                $clientesAsignados = array_slice($clientes_pendientes, $offset, $por_pagina);
                $total_paginas = ceil($total_clientes / $por_pagina);
                break;
            case 'gestionados':
                // Aplicar filtros adicionales para clientes gestionados
                $filtro_resultado = $_GET['filtro_resultado'] ?? 'todos';
                $clientes_gestionados_filtrados = $this->filtrarClientesGestionados($clientes_gestionados, $filtro_resultado);
                
                $total_clientes = count($clientes_gestionados_filtrados);
                $clientesAsignados = array_slice($clientes_gestionados_filtrados, $offset, $por_pagina);
                $total_paginas = ceil($total_clientes / $por_pagina);
                break;
            case 'ventas':
                $total_clientes = count($clientes_con_ventas);
                $clientesAsignados = array_slice($clientes_con_ventas, $offset, $por_pagina);
                $total_paginas = ceil($total_clientes / $por_pagina);
                break;
            case 'seguimiento':
                // Obtener clientes que necesitan seguimiento (con llamadas pendientes)
                $clientesSeguimiento = [];
                foreach ($llamadasPendientes as $llamada) {
                    // Buscar el cliente correspondiente
                    foreach ($todosClientes as $cliente) {
                        if ($cliente['id'] == $llamada['cliente_id']) {
                            $cliente['proxima_fecha'] = $llamada['proxima_fecha'];
                            $cliente['comentarios_seguimiento'] = $llamada['comentarios'];
                            $clientesSeguimiento[] = $cliente;
                            break;
                        }
                    }
                }
                $total_clientes = count($clientesSeguimiento);
                $clientesAsignados = array_slice($clientesSeguimiento, $offset, $por_pagina);
                $total_paginas = ceil($total_clientes / $por_pagina);
                break;
            default: // 'todos'
                $total_clientes = count($todosClientes);
                $clientesAsignados = array_slice($todosClientes, $offset, $por_pagina);
                $total_paginas = ceil($total_clientes / $por_pagina);
                break;
        }
        
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
        
        // Verificar que el cliente pertenece a una base asignada al asesor
        $basesAsignadas = $this->tareaModel->getBasesAsignadasByAsesor($asesorId);
        $cargaIds = array_column($basesAsignadas, 'carga_id');
        
        if (empty($cargaIds)) {
            $_SESSION['error_message'] = "No tienes bases asignadas para gestionar clientes.";
            header('Location: index.php?action=gestionar_clientes');
            exit;
        }
        
        // Verificar que el cliente pertenece a una de las bases asignadas
        $sql = "SELECT c.*, ce.nombre_cargue 
                FROM clientes c 
                JOIN cargas_excel ce ON c.carga_excel_id = ce.id 
                WHERE c.id = ? AND c.carga_excel_id IN (" . implode(',', array_fill(0, count($cargaIds), '?')) . ")";
        
        $params = array_merge([$clienteId], $cargaIds);
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$cliente) {
            $_SESSION['error_message'] = "No tienes permisos para gestionar este cliente o el cliente no existe.";
            header('Location: index.php?action=gestionar_clientes');
            exit;
        }
        
        // Obtener el ID de la asignación (si existe) o crear uno temporal
        $asignacionId = $this->clienteModel->getAsignacionId($asesorId, $clienteId);
        if (!$asignacionId) {
            // Crear una asignación temporal para el cliente
            $asignacionId = $this->clienteModel->createTemporaryAsignacion($asesorId, $clienteId);
        }
        
        // Verificar si el asesor tiene tareas pendientes
        $tieneTareasPendientes = $this->tareaModel->tieneTareasPendientes($asesorId);
        
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
        $carga_excel_id = $cliente['carga_excel_id'] ?? null;
        $facturas = $this->facturacionModel->getFacturasByClienteId($clienteId, $carga_excel_id);
        
        // Obtener estadísticas de facturas (solo de la base de datos asignada)
        // Modificar para filtrar por carga_excel_id si es necesario
        $estadisticasFacturas = $this->facturacionModel->getEstadisticasFacturas($cliente['cedula'], $carga_excel_id);
        
        // Obtener historial de gestiones (usar método existente)
        $historial = $this->gestionModel->getGestionByAsesorAndCliente($asesorId, $clienteId);
        
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

            $asesorId = $_SESSION['user_id'];

            // Obtener el siguiente cliente no gestionado
            $siguienteCliente = $this->clienteModel->getSiguienteClienteAsesor($asesorId);

            if ($siguienteCliente) {
                echo json_encode([
                    'success' => true,
                    'siguiente_cliente' => $siguienteCliente
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'No hay más clientes en tu lista'
                ]);
            }

        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
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
            $tipoGestionParaGuardar = ($tipoContactoArbol !== '' && $tipificacion !== '')
                ? $tipoContactoArbol . '|' . $tipificacion
                : $tipificacion;
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
            
            // Obtener el ID de asignación
            $asignacionId = $this->clienteModel->getAsignacionId($asesorId, $clienteId);
            
            if (!$asignacionId) {
                throw new Exception("No se encontró la asignación del cliente para este asesor.");
            }
            
            // Obtener información de la factura a gestionar
            $facturaGestionar = $_POST['factura_gestionar'] ?? null;
            $obligacionId = $_POST['obligacion_id'] ?? null;
            $productoGestionado = $_POST['producto_gestionado'] ?? null;
            $montoObligacion = $_POST['monto_obligacion'] ?? null;
            $numeroObligacion = $_POST['numero_obligacion'] ?? null;
            $estadoObligacion = $_POST['estado_obligacion'] ?? null;
            $facturasIds = $_POST['facturas_ids'] ?? null;
            
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
                        $gestionDataIndividual = [
                            'asignacion_id' => $asignacionId,
                            'tipo_gestion' => $tipoGestionParaGuardar,
                            'resultado' => $subTipificacion ?: $tipificacion,
                            'comentarios' => $comentarios . "\n\n[GESTIÓN APLICADA A TODAS LAS FACTURAS - Factura ID: " . $facturaId . "]",
                            'monto_venta' => $montoVenta,
                            'duracion_llamada' => $duracionLlamada,
                            'edad' => $edadCliente,
                            'num_personas' => $numPersonas,
                            'valor_cotizacion' => $valorCotizacion,
                            'whatsapp_enviado' => $whatsappEnviado,
                            'proxima_fecha' => $fechaProximaLlamada,
                            'forma_contacto' => $formaContacto,
                            'factura_gestionar' => $facturaGestionar,
                            'obligacion_id' => $facturaId,
                            'producto_gestionado' => $productoGestionado,
                            'monto_obligacion' => $facturaInfo['saldo'] ?? 0,
                            'numero_obligacion' => $facturaInfo['numero_factura'] ?? '',
                            'estado_obligacion' => $facturaInfo['estado_factura'] ?? 'pendiente',
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
                            'telefono_contacto' => $telefonoContacto
                        ];
                        
                        // Guardar gestión individual
                        $gestionIdIndividual = $this->gestionModel->crearGestion($gestionDataIndividual);
                        
                        // Guardar canales autorizados para cada gestión
                        if (!empty($canalesAutorizados) && $gestionIdIndividual) {
                            $this->gestionModel->guardarCanalesAutorizados($gestionIdIndividual, $canalesAutorizados);
                        }
                    }
                }
                
                // Crear gestión principal que representa "Todas las facturas"
                $gestionData = [
                    'asignacion_id' => $asignacionId,
                    'tipo_gestion' => $tipoGestionParaGuardar,
                    'resultado' => $subTipificacion ?: $tipificacion,
                    'comentarios' => $comentarios . "\n\n[GESTIÓN APLICADA A TODAS LAS FACTURAS - Total: " . count($facturasIdsArray) . " facturas]",
                    'monto_venta' => $montoVenta,
                    'duracion_llamada' => $duracionLlamada,
                    'edad' => $edadCliente,
                    'num_personas' => $numPersonas,
                    'valor_cotizacion' => $valorCotizacion,
                    'whatsapp_enviado' => $whatsappEnviado,
                    'proxima_fecha' => $fechaProximaLlamada,
                    'forma_contacto' => $formaContacto,
                    'factura_gestionar' => $facturaGestionar,
                    'obligacion_id' => $obligacionId,
                    'producto_gestionado' => $productoGestionado,
                    'monto_obligacion' => $montoObligacion,
                    'numero_obligacion' => $numeroObligacion,
                    'estado_obligacion' => $estadoObligacion,
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
                    'telefono_contacto' => $telefonoContacto
                ];
            } else {
                // Procesamiento normal para factura individual
                $gestionData = [
                    'asignacion_id' => $asignacionId,
                    'tipo_gestion' => $tipoGestionParaGuardar,
                    'resultado' => $subTipificacion ?: $tipificacion,
                    'comentarios' => $comentarios,
                    'monto_venta' => $montoVenta,
                    'duracion_llamada' => $duracionLlamada,
                    'edad' => $edadCliente,
                    'num_personas' => $numPersonas,
                    'valor_cotizacion' => $valorCotizacion,
                    'whatsapp_enviado' => $whatsappEnviado,
                    'proxima_fecha' => $fechaProximaLlamada,
                    'forma_contacto' => $formaContacto,
                    'factura_gestionar' => $facturaGestionar,
                    'obligacion_id' => $obligacionId,
                    'producto_gestionado' => $productoGestionado,
                    'monto_obligacion' => $montoObligacion,
                    'numero_obligacion' => $numeroObligacion,
                    'estado_obligacion' => $estadoObligacion,
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
                    'telefono_contacto' => $telefonoContacto
                ];
            }
            
            // Guardar en historial_gestion
            $gestionId = $this->gestionModel->crearGestion($gestionData);
            
            if (!$gestionId) {
                throw new Exception("Error al guardar la gestión.");
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
            
            if (!$clienteId) {
                throw new Exception("ID de cliente no proporcionado.");
            }
            
            // Obtener el historial del cliente
            $historial = $this->gestionModel->getGestionByAsesorAndCliente($asesorId, $clienteId);
            
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
        
        $clientes = $this->tareaModel->buscarClienteEnBasesAsignadas($asesorId, $cedula);
        
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
            
            $clientes = $this->tareaModel->buscarClientesPorTermino($asesorId, $termino, 20);
            
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
        
        $sql = "SELECT c.*, ce.nombre_cargue 
                FROM clientes c 
                JOIN cargas_excel ce ON c.carga_excel_id = ce.id 
                WHERE c.id = ? AND c.carga_excel_id IN (" . implode(',', array_fill(0, count($cargaIds), '?')) . ")";
        
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

            // 1) ¿El asesor tiene acceso por base de datos?
            $cargaId = $cliente['carga_excel_id'] ?? null;
            if ($cargaId) {
                $stmt = $this->pdo->prepare("
                    SELECT 1 
                    FROM asignaciones_base_asesor 
                    WHERE carga_id = ? AND asesor_id = ? AND estado = 'activa'
                    LIMIT 1
                ");
                $stmt->execute([$cargaId, $asesorId]);
                if ($stmt->fetchColumn()) {
                    $clienteAsignado = true;
                }
            }

            // 2) Si no tiene acceso por base, verificar acceso por asignación directa de cliente
            if (!$clienteAsignado) {
                $clientesAsignados = $this->clienteModel->getAssignedClientsForAsesor($asesorId);
                foreach ($clientesAsignados as $clienteAsig) {
                    if ((int)$clienteAsig['id'] === (int)$clienteId) {
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
            
            // Dirección y ciudad (si vienen desde FormData)
            if (!empty($inputData['nueva_direccion'])) {
                $actualizacionesCliente['direccion'] = trim($inputData['nueva_direccion']);
            }
            
            if (!empty($inputData['nueva_ciudad'])) {
                $actualizacionesCliente['ciudad'] = trim($inputData['nueva_ciudad']);
            }
            
            if (!empty($actualizacionesCliente)) {
                $actualizacionesCliente['id'] = $clienteId;
                $resultado = $this->clienteModel->updateCliente($actualizacionesCliente);
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
     * Agregar teléfono adicional a la tabla clientes
     * Busca la primera columna vacía (telefono, celular2, cel3, cel4, cel5, etc. hasta cel11) y guarda el teléfono ahí
     * No reemplaza números existentes, solo busca columnas vacías
     * 
     * @param int $clienteId ID del cliente
     * @param string $telefono Número de teléfono a agregar
     * @return array|false Retorna array con información del resultado o false si hay error
     */
    private function agregarTelefonoAdicional($clienteId, $telefono) {
        // Obtener datos del cliente directamente desde la BD para asegurar que tenemos todas las columnas
        // Usar consulta explícita para garantizar que todas las columnas estén presentes
        $sql = "SELECT id, telefono, celular2, cel3, cel4, cel5, cel6, cel7, cel8, cel9, cel10, cel11 
                FROM clientes WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$clienteId]);
        $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$cliente) {
            error_log("Cliente no encontrado con ID: $clienteId");
            return false;
        }
        
        // Normalizar el teléfono a agregar
        $telefono = trim($telefono);
        if (empty($telefono)) {
            error_log("Teléfono vacío proporcionado para cliente ID: $clienteId");
            return false;
        }
        
        // Lista completa de columnas de teléfonos (telefono, celular2, cel3 hasta cel11)
        // Total: 11 columnas (telefono, celular2, cel3, cel4, cel5, cel6, cel7, cel8, cel9, cel10, cel11)
        $columnasTelefono = ['telefono', 'celular2', 'cel3', 'cel4', 'cel5', 'cel6', 'cel7', 'cel8', 'cel9', 'cel10', 'cel11'];
        
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
        $sql = "UPDATE clientes SET $columnaVacia = ? WHERE id = ?";
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
        // Obtener la asignación del cliente
        $sql = "SELECT id FROM asignaciones_clientes WHERE cliente_id = ? AND asesor_id = ? AND estado = 'asignado' LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$clienteId, $asesorId]);
        $asignacion = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($asignacion) {
            // Usar crearGestionSimple para evitar conflictos de transacciones
            $this->gestionModel->crearGestionSimple(
                $asignacion['id'],
                'informacion_adicional',
                'INFORMACIÓN ADICIONAL AGREGADA: ' . $observaciones,
                'completado'
            );
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
            error_log("Error en registrarBreak: " . $e->getMessage());
            $this->enviarJSONError('Error al procesar la solicitud: ' . $e->getMessage(), 'GENERAL_ERROR', 500);
        }
    }

    /**
     * Verifica si hay un break activo para el asesor
     */
    private function verificarBreakActivo($asesorId) {
        try {
            // Verificar si hay un break activo (sin fecha_fin o con estado 'activo')
            $sql = "SELECT id, tipo, fecha_inicio, estado
                    FROM breaks_asesor 
                    WHERE asesor_id = ? 
                    AND (fecha_fin IS NULL OR estado = 'activo')
                    ORDER BY fecha_inicio DESC 
                    LIMIT 1";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$asesorId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            // Si la tabla no existe, crearla y retornar false
            if (strpos($e->getMessage(), "doesn't exist") !== false || strpos($e->getMessage(), "no existe") !== false) {
                $this->crearTablaBreaksSiNoExiste();
                return false;
            }
            error_log("Error en verificarBreakActivo: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Registra el inicio de un break
     */
    private function registrarInicioBreak($asesorId, $tipo) {
        try {
            // Verificar si la tabla existe, si no, crearla
            $this->crearTablaBreaksSiNoExiste();

            // Verificar si la tabla tiene el campo 'estado' (estructura nueva)
            $sqlCheck = "SHOW COLUMNS FROM breaks_asesor LIKE 'estado'";
            $stmtCheck = $this->pdo->query($sqlCheck);
            $tieneEstado = $stmtCheck->rowCount() > 0;

            if ($tieneEstado) {
                // Tabla con estructura nueva (incluye estado)
                $sql = "INSERT INTO breaks_asesor (asesor_id, tipo, fecha_inicio, estado) 
                        VALUES (?, ?, NOW(), 'activo')";
            } else {
                // Tabla con estructura antigua
                $sql = "INSERT INTO breaks_asesor (asesor_id, tipo, fecha_inicio) 
                        VALUES (?, ?, NOW())";
            }

            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([$asesorId, $tipo]);

        } catch (Exception $e) {
            error_log("Error en registrarInicioBreak: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Registra el fin de un break
     */
    private function registrarFinBreak($asesorId, $tipo, $breakId) {
        try {
            // Verificar si la tabla tiene los campos adicionales (estructura nueva)
            $sqlCheck = "SHOW COLUMNS FROM breaks_asesor LIKE 'estado'";
            $stmtCheck = $this->pdo->query($sqlCheck);
            $tieneEstado = $stmtCheck->rowCount() > 0;
            
            $sqlCheck2 = "SHOW COLUMNS FROM breaks_asesor LIKE 'duracion_segundos'";
            $stmtCheck2 = $this->pdo->query($sqlCheck2);
            $tieneDuracionSegundos = $stmtCheck2->rowCount() > 0;

            // Calcular duración en segundos primero para mayor precisión
            // Luego convertir a minutos con decimales para guardar en la BD
            if ($tieneEstado && $tieneDuracionSegundos) {
                // Tabla con estructura nueva
                $sql = "UPDATE breaks_asesor 
                        SET fecha_fin = NOW(),
                            duracion_segundos = TIMESTAMPDIFF(SECOND, fecha_inicio, NOW()),
                            duracion_minutos = TIMESTAMPDIFF(SECOND, fecha_inicio, NOW()) / 60.0,
                            estado = 'finalizado',
                            updated_at = NOW()
                        WHERE id = ? AND asesor_id = ? AND tipo = ?";
            } elseif ($tieneEstado) {
                // Tabla con estado pero sin duracion_segundos
                $sql = "UPDATE breaks_asesor 
                        SET fecha_fin = NOW(),
                            duracion_minutos = TIMESTAMPDIFF(SECOND, fecha_inicio, NOW()) / 60.0,
                            estado = 'finalizado'
                        WHERE id = ? AND asesor_id = ? AND tipo = ?";
            } else {
                // Tabla con estructura antigua
                $sql = "UPDATE breaks_asesor 
                        SET fecha_fin = NOW(),
                            duracion_minutos = TIMESTAMPDIFF(SECOND, fecha_inicio, NOW()) / 60.0
                        WHERE id = ? AND asesor_id = ? AND tipo = ?";
            }

            $stmt = $this->pdo->prepare($sql);
            $resultado = $stmt->execute([$breakId, $asesorId, $tipo]);

            if ($resultado) {
                // Obtener la duración (ahora con decimales)
                $sqlDuracion = "SELECT duracion_minutos, duracion_segundos FROM breaks_asesor WHERE id = ?";
                $stmtDuracion = $this->pdo->prepare($sqlDuracion);
                $stmtDuracion->execute([$breakId]);
                $duracion = $stmtDuracion->fetch(PDO::FETCH_ASSOC);

                return [
                    'success' => true,
                    'duracion' => $duracion['duracion_minutos'] ?? 0,
                    'duracion_segundos' => $duracion['duracion_segundos'] ?? null
                ];
            }

            return false;

        } catch (Exception $e) {
            error_log("Error en registrarFinBreak: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Crea la tabla de breaks si no existe
     */
    private function crearTablaBreaksSiNoExiste() {
        try {
            $sql = "CREATE TABLE IF NOT EXISTS breaks_asesor (
                id INT AUTO_INCREMENT PRIMARY KEY,
                asesor_id INT NOT NULL,
                tipo ENUM('baño', 'almuerzo', 'break', 'mantenimiento', 'actividad_extra', 'pausa_activa') NOT NULL,
                fecha_inicio DATETIME NOT NULL,
                fecha_fin DATETIME NULL,
                duracion_minutos DECIMAL(10,2) NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_asesor (asesor_id),
                INDEX idx_fecha_inicio (fecha_inicio),
                INDEX idx_tipo (tipo),
                FOREIGN KEY (asesor_id) REFERENCES usuarios(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

            $this->pdo->exec($sql);

        } catch (Exception $e) {
            error_log("Error al crear tabla breaks_asesor: " . $e->getMessage());
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

