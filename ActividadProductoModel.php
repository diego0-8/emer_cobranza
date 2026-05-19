<?php
// Archivo: models/ActividadProductoModel.php
// Modelo para el registro automático de actividades sobre productos

class ActividadProductoModel {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Registra una actividad automáticamente
     */
    public function registrarActividad($datos) {
        try {
            $sql = "INSERT INTO actividades_productos (
                        historial_gestion_id, cliente_id, asesor_id, producto_id, 
                        numero_obligacion, tipo_actividad, accion_realizada, 
                        detalles_especificos, estado_anterior, estado_nuevo,
                        ip_address, user_agent, metadata
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->pdo->prepare($sql);
            
            $params = [
                $datos['historial_gestion_id'] ?? null,
                $datos['cliente_id'],
                $datos['asesor_id'],
                $datos['producto_id'] ?? null,
                $datos['numero_obligacion'] ?? null,
                $datos['tipo_actividad'],
                $datos['accion_realizada'],
                $datos['detalles_especificos'] ?? null,
                $datos['estado_anterior'] ?? null,
                $datos['estado_nuevo'] ?? null,
                $datos['ip_address'] ?? $_SERVER['REMOTE_ADDR'] ?? null,
                $datos['user_agent'] ?? $_SERVER['HTTP_USER_AGENT'] ?? null,
                $datos['metadata'] ? json_encode($datos['metadata']) : null
            ];
            
            $success = $stmt->execute($params);
            
            if ($success) {
                return $this->pdo->lastInsertId();
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log("Error en registrarActividad: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Registra un log del sistema en tiempo real.
     * Se adapta dinámicamente al esquema actual de la tabla logs_sistema_tiempo_real
     * para evitar errores como "Unknown column 'tipo_evento' in 'field list'".
     */
    public function registrarLogSistema($datos) {
        try {
            // Obtener columnas reales de la tabla
            $columnsStmt = $this->pdo->query("SHOW COLUMNS FROM logs_sistema_tiempo_real");
            $existingColumns = $columnsStmt->fetchAll(PDO::FETCH_COLUMN);

            if (empty($existingColumns)) {
                error_log("registrarLogSistema: la tabla logs_sistema_tiempo_real no tiene columnas o no existe");
                return false;
            }

            // Campos que este modelo sabe manejar (soporta ambos esquemas: con tipo_evento/entidad_* y con tabla_afectada/registro_id)
            $knownFields = [
                'tipo_evento',
                'entidad_afectada',
                'entidad_id',
                'tabla_afectada',
                'registro_id',
                'usuario_id',
                'accion',
                'descripcion',
                'datos_anteriores',
                'datos_nuevos',
                'ip_address',
                'user_agent',
                'session_id'
            ];

            // Intersección entre columnas existentes y campos conocidos,
            // excluyendo la PK autoincremental y timestamps automáticos
            $insertColumns = [];
            foreach ($knownFields as $field) {
                if (in_array($field, $existingColumns, true)) {
                    $insertColumns[] = $field;
                }
            }

            if (empty($insertColumns)) {
                error_log("registrarLogSistema: no hay columnas compatibles en logs_sistema_tiempo_real");
                return false;
            }

            // Construir lista de placeholders y parámetros en el mismo orden
            $placeholders = [];
            $params = [];

            foreach ($insertColumns as $col) {
                $placeholders[] = '?';

                switch ($col) {
                    case 'tipo_evento':
                        $params[] = $datos['tipo_evento'] ?? 'evento';
                        break;
                    case 'entidad_afectada':
                        $params[] = $datos['entidad_afectada'] ?? '';
                        break;
                    case 'entidad_id':
                        $params[] = $datos['entidad_id'] ?? null;
                        break;
                    case 'tabla_afectada':
                        $params[] = $datos['tabla_afectada'] ?? $datos['entidad_afectada'] ?? null;
                        break;
                    case 'registro_id':
                        $params[] = $datos['registro_id'] ?? $datos['entidad_id'] ?? null;
                        break;
                    case 'usuario_id':
                        $params[] = $datos['usuario_id'] ?? null;
                        break;
                    case 'accion':
                        $params[] = $datos['accion'] ?? '';
                        break;
                    case 'descripcion':
                        $params[] = $datos['descripcion'] ?? null;
                        break;
                    case 'datos_anteriores':
                        $params[] = isset($datos['datos_anteriores']) && $datos['datos_anteriores']
                            ? json_encode($datos['datos_anteriores'])
                            : null;
                        break;
                    case 'datos_nuevos':
                        $params[] = isset($datos['datos_nuevos']) && $datos['datos_nuevos']
                            ? json_encode($datos['datos_nuevos'])
                            : null;
                        break;
                    case 'ip_address':
                        $params[] = $datos['ip_address'] ?? ($_SERVER['REMOTE_ADDR'] ?? null);
                        break;
                    case 'user_agent':
                        $params[] = $datos['user_agent'] ?? ($_SERVER['HTTP_USER_AGENT'] ?? null);
                        break;
                    case 'session_id':
                        $params[] = $datos['session_id'] ?? (function_exists('session_id') ? session_id() : null);
                        break;
                    default:
                        // No debería llegar aquí porque filtramos por knownFields,
                        // pero se deja por seguridad.
                        $params[] = $datos[$col] ?? null;
                        break;
                }
            }

            $sql = sprintf(
                "INSERT INTO logs_sistema_tiempo_real (%s) VALUES (%s)",
                implode(', ', $insertColumns),
                implode(', ', $placeholders)
            );

            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($params);

        } catch (Exception $e) {
            error_log("Error en registrarLogSistema: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtiene el historial de actividades de un producto específico
     */
    public function getActividadesProducto($productoId, $clienteId = null) {
        try {
            $sql = "SELECT 
                        ap.*, 
                        u.nombre as asesor_nombre,
                        c.nombre as cliente_nombre,
                        hg.tipo_contacto AS tipo_gestion,
                        hg.resultado_contacto AS resultado,
                        hg.fecha_creacion AS fecha_gestion
                    FROM actividades_productos ap
                    JOIN usuarios u ON ap.asesor_id = u.cedula
                    JOIN clientes c ON ap.cliente_id = c.id_cliente
                    LEFT JOIN historial_gestiones hg ON ap.historial_gestion_id = hg.id_gestion
                    WHERE ap.producto_id = ?";
            
            $params = [$productoId];
            
            if ($clienteId) {
                $sql .= " AND ap.cliente_id = ?";
                $params[] = $clienteId;
            }
            
            $sql .= " ORDER BY ap.timestamp_actividad DESC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Error en getActividadesProducto: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtiene el historial completo de actividades de un cliente
     */
    public function getActividadesCliente($clienteId, $limit = 50) {
        try {
            $sql = "SELECT 
                        ap.*, 
                        u.nombre as asesor_nombre,
                        c.nombre as cliente_nombre,
                        hg.tipo_contacto AS tipo_gestion,
                        hg.resultado_contacto AS resultado,
                        hg.fecha_creacion AS fecha_gestion
                    FROM actividades_productos ap
                    JOIN usuarios u ON ap.asesor_id = u.cedula
                    JOIN clientes c ON ap.cliente_id = c.id_cliente
                    LEFT JOIN historial_gestiones hg ON ap.historial_gestion_id = hg.id_gestion
                    WHERE ap.cliente_id = ?
                    ORDER BY ap.timestamp_actividad DESC
                    LIMIT ?";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$clienteId, $limit]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Error en getActividadesCliente: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtiene actividades en tiempo real (últimas 24 horas)
     */
    public function getActividadesTiempoReal($asesorId = null, $limit = 100) {
        $sql = '';
        $params = [];
        try {
            // Asegurar que limit sea un entero
            $limit = (int)$limit;
            $asesorId = ($asesorId !== null && $asesorId !== '') ? (string)$asesorId : null;
            
            $sql = "SELECT 
                        ap.*, 
                        u.nombre as asesor_nombre,
                        c.nombre as cliente_nombre,
                        hg.tipo_contacto AS tipo_gestion,
                        hg.resultado_contacto AS resultado,
                        hg.fecha_creacion AS fecha_gestion
                    FROM actividades_productos ap
                    JOIN usuarios u ON ap.asesor_id = u.cedula
                    JOIN clientes c ON ap.cliente_id = c.id_cliente
                    LEFT JOIN historial_gestiones hg ON ap.historial_gestion_id = hg.id_gestion
                    WHERE ap.timestamp_actividad >= DATE_SUB(NOW(), INTERVAL 24 HOUR)";
            
            $params = [];
            
            if ($asesorId) {
                $sql .= " AND ap.asesor_id = ?";
                $params[] = $asesorId;
            }
            
            $sql .= " ORDER BY ap.timestamp_actividad DESC LIMIT " . $limit;
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Error en getActividadesTiempoReal: " . $e->getMessage());
            error_log("SQL: " . $sql);
            error_log("Params: " . json_encode($params));
            return [];
        }
    }
    
    /**
     * Obtiene estadísticas de actividades por tipo
     */
    public function getEstadisticasActividades($asesorId, $periodo = 'dia') {
        try {
            $fechaInicio = $this->getFechaInicio($periodo);
            
            $sql = "SELECT 
                        tipo_actividad,
                        COUNT(*) as total_actividades,
                        COUNT(DISTINCT cliente_id) as clientes_afectados,
                        COUNT(DISTINCT producto_id) as productos_afectados
                    FROM actividades_productos
                    WHERE asesor_id = ? AND timestamp_actividad >= ?
                    GROUP BY tipo_actividad
                    ORDER BY total_actividades DESC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([(string)$asesorId, $fechaInicio]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Error en getEstadisticasActividades: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtiene la fecha de inicio según el período
     */
    private function getFechaInicio($periodo) {
        switch ($periodo) {
            case 'hora':
                return date('Y-m-d H:00:00');
            case 'dia':
                return date('Y-m-d 00:00:00');
            case 'semana':
                return date('Y-m-d 00:00:00', strtotime('-7 days'));
            case 'mes':
                return date('Y-m-01 00:00:00');
            default:
                return date('Y-m-d 00:00:00');
        }
    }
    
    /**
     * Registra automáticamente una gestión de producto
     */
    public function registrarGestionProducto($historialGestionId, $clienteId, $asesorId, $datosGestion) {
        $actividad = [
            'historial_gestion_id' => $historialGestionId,
            'cliente_id' => $clienteId,
            'asesor_id' => $asesorId,
            'producto_id' => $datosGestion['obligacion_id'] ?? null,
            'numero_obligacion' => $datosGestion['numero_obligacion'] ?? null,
            'tipo_actividad' => 'gestion',
            'accion_realizada' => 'Gestión de producto realizada',
            'detalles_especificos' => $datosGestion['comentarios'] ?? null,
            'estado_anterior' => null,
            'estado_nuevo' => $datosGestion['resultado'] ?? null,
            'metadata' => [
                'tipo_gestion' => $datosGestion['tipo_gestion'] ?? null,
                'forma_contacto' => $datosGestion['forma_contacto'] ?? null,
                'producto_gestionado' => $datosGestion['producto_gestionado'] ?? null,
                'monto_obligacion' => $datosGestion['monto_obligacion'] ?? null
            ]
        ];
        
        return $this->registrarActividad($actividad);
    }
    
    /**
     * Registra automáticamente la autorización de canales
     */
    public function registrarCanalesAutorizados($historialGestionId, $clienteId, $asesorId, $canales) {
        $actividad = [
            'historial_gestion_id' => $historialGestionId,
            'cliente_id' => $clienteId,
            'asesor_id' => $asesorId,
            'tipo_actividad' => 'canal_autorizado',
            'accion_realizada' => 'Canales de comunicación autorizados',
            'detalles_especificos' => implode(', ', $canales),
            'metadata' => [
                'canales_autorizados' => $canales,
                'total_canales' => count($canales)
            ]
        ];
        
        return $this->registrarActividad($actividad);
    }
}
?>
