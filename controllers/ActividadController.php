<?php
// Archivo: controllers/ActividadController.php
// Controlador para el manejo de actividades en tiempo real

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/ActividadProductoModel.php';

class ActividadController extends BaseController {
    private $actividadModel;
    
    public function __construct($pdo) {
        parent::__construct($pdo);
        $this->actividadModel = new ActividadProductoModel($pdo);
    }

    private function usuarioPuedeAccederCliente($clienteId) {
        $clienteId = (int)$clienteId;
        if ($clienteId <= 0) {
            return false;
        }

        $userId = $_SESSION['user_id'] ?? '';
        $rol = $_SESSION['user_role'] ?? '';

        if ($rol === 'administrador') {
            return true;
        }

        if ($rol === 'asesor') {
            $bases = $this->tareaModel->getBasesAsignadasByAsesor($userId);
            $baseIds = array_column($bases, 'base_id');
            if (empty($baseIds)) {
                return false;
            }
            $placeholders = implode(',', array_fill(0, count($baseIds), '?'));
            $sql = "SELECT 1 FROM clientes WHERE id_cliente = ? AND base_id IN ($placeholders) LIMIT 1";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(array_merge([$clienteId], $baseIds));
            return (bool)$stmt->fetch(PDO::FETCH_ASSOC);
        }

        if ($rol === 'coordinador') {
            $stmt = $this->pdo->prepare("
                SELECT 1
                FROM clientes c
                JOIN base_clientes b ON c.base_id = b.id_base
                WHERE c.id_cliente = ? AND b.creado_por = ?
                LIMIT 1
            ");
            $stmt->execute([$clienteId, (string)$userId]);
            return (bool)$stmt->fetch(PDO::FETCH_ASSOC);
        }

        return false;
    }
    
    /**
     * Obtiene actividades en tiempo real
     */
    public function obtenerActividadesTiempoReal() {
        try {
            // Establecer headers para JSON
            header('Content-Type: application/json; charset=utf-8');
            
            // Proteger el endpoint: solo usuarios autenticados pueden consultar
            if (!isset($_SESSION['user_id']) || empty($_SESSION['user_role'])) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Acceso no autorizado'
                ]);
                return;
            }

            $asesorId = $_SESSION['user_id'];
            $limit = $this->getGet('limit', 50);
            
            $actividades = $this->actividadModel->getActividadesTiempoReal($asesorId, $limit);
            
            echo json_encode([
                'success' => true,
                'actividades' => $actividades,
                'total' => count($actividades)
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Obtiene actividades de un cliente específico
     */
    public function obtenerActividadesCliente() {
        try {
            if (!headers_sent()) {
                header('Content-Type: application/json; charset=utf-8');
            }
            if (!isset($_SESSION['user_id']) || empty($_SESSION['user_role'])) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Acceso no autorizado'
                ]);
                return;
            }

            $clienteId = $this->getGet('cliente_id');
            $limit = $this->getGet('limit', 100);
            
            if (!$clienteId) {
                throw new Exception("ID de cliente no proporcionado");
            }

            if (!$this->usuarioPuedeAccederCliente($clienteId)) {
                throw new Exception("No tienes permisos para ver las actividades de este cliente");
            }
            
            $actividades = $this->actividadModel->getActividadesCliente($clienteId, $limit);
            
            echo json_encode([
                'success' => true,
                'actividades' => $actividades,
                'total' => count($actividades)
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Obtiene actividades de un producto específico
     */
    public function obtenerActividadesProducto() {
        try {
            if (!headers_sent()) {
                header('Content-Type: application/json; charset=utf-8');
            }
            if (!isset($_SESSION['user_id']) || empty($_SESSION['user_role'])) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Acceso no autorizado'
                ]);
                return;
            }

            $productoId = $this->getGet('producto_id');
            $clienteId = $this->getGet('cliente_id');
            
            if (!$productoId) {
                throw new Exception("ID de producto no proporcionado");
            }

            if ($clienteId && !$this->usuarioPuedeAccederCliente($clienteId)) {
                throw new Exception("No tienes permisos para ver las actividades de este cliente");
            }
            
            $actividades = $this->actividadModel->getActividadesProducto($productoId, $clienteId);
            
            echo json_encode([
                'success' => true,
                'actividades' => $actividades,
                'total' => count($actividades)
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Obtiene estadísticas de actividades
     */
    public function obtenerEstadisticasActividades() {
        try {
            if (!headers_sent()) {
                header('Content-Type: application/json; charset=utf-8');
            }
            if (!isset($_SESSION['user_id']) || empty($_SESSION['user_role'])) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Acceso no autorizado'
                ]);
                return;
            }

            $asesorId = $_SESSION['user_id'];
            $periodo = $this->getGet('periodo', 'dia');
            
            $estadisticas = $this->actividadModel->getEstadisticasActividades($asesorId, $periodo);
            
            echo json_encode([
                'success' => true,
                'estadisticas' => $estadisticas,
                'periodo' => $periodo
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Obtiene el historial completo de actividades con filtros
     */
    public function obtenerHistorialCompleto() {
        try {
            if (!headers_sent()) {
                header('Content-Type: application/json; charset=utf-8');
            }
            if (!isset($_SESSION['user_id']) || empty($_SESSION['user_role'])) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Acceso no autorizado'
                ]);
                return;
            }

            $asesorId = $_SESSION['user_id'];
            $clienteId = $this->getGet('cliente_id');
            $tipoActividad = $this->getGet('tipo_actividad');
            $fechaInicio = $this->getGet('fecha_inicio');
            $fechaFin = $this->getGet('fecha_fin');
            $limit = $this->getGet('limit', 100);
            
            if (!$asesorId) {
                throw new Exception("Usuario no autenticado");
            }
            
            // Construir consulta con filtros (esquema emermedica: usuarios.cedula, clientes.id_cliente, historial_gestiones)
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
                    WHERE ap.asesor_id = ?";
            
            $params = [$asesorId];
            
            if ($clienteId) {
                if (!$this->usuarioPuedeAccederCliente($clienteId)) {
                    throw new Exception("No tienes permisos para ver las actividades de este cliente");
                }
                $sql .= " AND ap.cliente_id = ?";
                $params[] = $clienteId;
            }
            
            if ($tipoActividad) {
                $sql .= " AND ap.tipo_actividad = ?";
                $params[] = $tipoActividad;
            }
            
            if ($fechaInicio) {
                $sql .= " AND ap.timestamp_actividad >= ?";
                $params[] = $fechaInicio;
            }
            
            if ($fechaFin) {
                $sql .= " AND ap.timestamp_actividad <= ?";
                $params[] = $fechaFin;
            }
            
            $sql .= " ORDER BY ap.timestamp_actividad DESC LIMIT " . (int)$limit;
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $actividades = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'actividades' => $actividades,
                'total' => count($actividades),
                'filtros' => [
                    'cliente_id' => $clienteId,
                    'tipo_actividad' => $tipoActividad,
                    'fecha_inicio' => $fechaInicio,
                    'fecha_fin' => $fechaFin
                ]
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
}
?>
