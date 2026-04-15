<?php
/**
 * Modelo para manejar facturas de clientes
 * Permite múltiples facturas por cliente con cédulas duplicadas
 */

class FacturacionModel {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Crear una nueva factura
     */
    public function crearFactura($datos) {
        $sql = "INSERT INTO facturas (cliente_id, numero_factura, cedula, nombre, saldo, dias_mora, rmt, numero_contrato, telefono2, telefono3, franja, estado_factura, fecha_creacion, fecha_actualizacion)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
        $stmt = $this->pdo->prepare($sql);
        
        if ($stmt->execute([
            $datos['cliente_id'],
            $datos['numero_factura'] ?? null,
            $datos['cedula'] ?? null,
            $datos['nombre'] ?? null,
            $datos['saldo'] ?? null,
            $datos['dias_mora'] ?? null,
            $datos['rmt'] ?? null,
            $datos['numero_contrato'] ?? null,
            $datos['telefono2'] ?? null,
            $datos['telefono3'] ?? null,
            $datos['franja'] ?? null,
            $datos['estado_factura'] ?? 'pendiente'
        ])) {
            return $this->pdo->lastInsertId();
        }
        return false;
    }
    
    /**
     * Obtener una factura por su ID
     */
    public function getFacturaById($facturaId) {
        $sql = "SELECT * FROM facturas WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$facturaId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtener todas las facturas de un cliente por cédula
     */
    public function getFacturasByCedula($cedula) {
        $sql = "SELECT c.*, f.*, ce.nombre_cargue
                FROM clientes c
                LEFT JOIN facturas f ON c.id = f.cliente_id
                LEFT JOIN cargas_excel ce ON c.carga_excel_id = ce.id
                WHERE c.cedula = ?
                ORDER BY f.fecha_creacion DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$cedula]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtener todas las facturas de un cliente por ID
     * Si se proporciona carga_excel_id, solo se obtienen las facturas de esa base de datos
     */
    public function getFacturasByClienteId($cliente_id, $carga_excel_id = null) {
        $sql = "SELECT f.*, c.nombre, c.cedula, c.telefono, c.email, ce.nombre_cargue
                FROM facturas f
                LEFT JOIN clientes c ON f.cliente_id = c.id
                LEFT JOIN cargas_excel ce ON c.carga_excel_id = ce.id
                WHERE f.cliente_id = ?";
        
        $params = [$cliente_id];
        
        // CRÍTICO: Filtrar por carga_excel_id si se proporciona
        // Esto asegura que solo se muestren las facturas de la base de datos asignada al asesor
        if ($carga_excel_id !== null) {
            $sql .= " AND c.carga_excel_id = ?";
            $params[] = $carga_excel_id;
        }
        
        $sql .= " ORDER BY f.fecha_creacion DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtener una factura específica por número de factura
     */
    public function getFacturaByNumero($numero_factura) {
        $sql = "SELECT f.*, c.nombre, c.cedula, c.telefono, c.email, ce.nombre_cargue
                FROM facturas f
                LEFT JOIN clientes c ON f.cliente_id = c.id
                LEFT JOIN cargas_excel ce ON c.carga_excel_id = ce.id
                WHERE f.numero_factura = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$numero_factura]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtener una factura específica por número de factura y cliente_id
     */
    public function getFacturaByNumeroAndCliente($numero_factura, $cliente_id) {
        $sql = "SELECT f.*, c.nombre, c.cedula, c.telefono, c.email, ce.nombre_cargue
                FROM facturas f
                LEFT JOIN clientes c ON f.cliente_id = c.id
                LEFT JOIN cargas_excel ce ON c.carga_excel_id = ce.id
                WHERE f.numero_factura = ? AND f.cliente_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$numero_factura, $cliente_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Verificar si una factura ya existe
     * @param string $numero_factura Número de factura
     * @param int|null $cliente_id ID del cliente (opcional, pero recomendado para evitar duplicados entre bases)
     * @return bool True si la factura existe para ese cliente, False si no existe
     */
    public function facturaExiste($numero_factura, $cliente_id = null) {
        if ($cliente_id !== null) {
            // Verificar si la factura existe para este cliente específico
            // Esto permite que el mismo número de factura exista para diferentes clientes (en diferentes bases)
            $sql = "SELECT COUNT(*) FROM facturas WHERE numero_factura = ? AND cliente_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$numero_factura, $cliente_id]);
        } else {
            // Verificación global (mantener compatibilidad con código antiguo)
            // PERO: Esto puede causar problemas si hay múltiples clientes con la misma cédula
            $sql = "SELECT COUNT(*) FROM facturas WHERE numero_factura = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$numero_factura]);
        }
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Actualizar una factura
     * Permite actualizar cualquier campo de la factura
     */
    public function actualizarFactura($id, $datos) {
        if (empty($datos)) {
            return false;
        }
        
        // Construir la consulta dinámicamente para permitir actualizar cualquier campo
        $campos = [];
        $valores = [];
        
        // Campos permitidos para actualizar
        $camposPermitidos = [
            'cedula', 'nombre', 'telefono', 'telefono2', 'telefono3',
            'saldo', 'dias_mora', 'rmt', 'numero_contrato', 'franja',
            'propiedad', 'producto', 'medicion', 'estado_factura'
        ];
        
        foreach ($datos as $campo => $valor) {
            if (in_array($campo, $camposPermitidos)) {
                $campos[] = "$campo = ?";
                $valores[] = $valor;
            }
        }
        
        if (empty($campos)) {
            return false;
        }
        
        // Agregar fecha_actualizacion automáticamente
        $campos[] = "fecha_actualizacion = CURRENT_TIMESTAMP";
        $valores[] = $id;
        
        $sql = "UPDATE facturas SET " . implode(", ", $campos) . " WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        
        return $stmt->execute($valores);
    }
    
    /**
     * Eliminar una factura
     */
    public function eliminarFactura($id) {
        $sql = "DELETE FROM facturas WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$id]);
    }
    
    /**
     * Eliminar facturas de un cliente que no están en la lista de números de factura proporcionada
     * Útil para reemplazar facturas cuando se actualiza una base existente
     * 
     * @param int $cliente_id ID del cliente
     * @param array $numerosFactura Array de números de factura que deben mantenerse
     * @return int Número de facturas eliminadas
     */
    public function eliminarFacturasNoIncluidas($cliente_id, $numerosFactura) {
        if (empty($numerosFactura)) {
            // Si no hay facturas en el CSV, eliminar todas las facturas del cliente
            $sql = "DELETE FROM facturas WHERE cliente_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$cliente_id]);
            return $stmt->rowCount();
        }
        
        // Crear placeholders para la consulta IN
        $placeholders = str_repeat('?,', count($numerosFactura) - 1) . '?';
        
        // Eliminar facturas que no están en la lista
        $sql = "DELETE FROM facturas WHERE cliente_id = ? AND numero_factura NOT IN ($placeholders)";
        $params = array_merge([$cliente_id], $numerosFactura);
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->rowCount();
    }
    
    /**
     * Obtener estadísticas de facturas por cliente
     * Si se proporciona carga_excel_id, solo se calculan estadísticas de esa base de datos
     */
    public function getEstadisticasFacturas($cedula, $carga_excel_id = null) {
        $sql = "SELECT 
                    COUNT(*) as total_facturas,
                    SUM(saldo) as saldo_total,
                    COUNT(CASE WHEN f.estado_factura = 'pendiente' THEN 1 END) as facturas_activas,
                    COUNT(CASE WHEN f.estado_factura = 'pagada' THEN 1 END) as facturas_pagadas,
                    COUNT(CASE WHEN f.estado_factura = 'eliminada' THEN 1 END) as facturas_canceladas
                FROM facturas f
                LEFT JOIN clientes c ON f.cliente_id = c.id
                WHERE c.cedula = ?";
        
        $params = [$cedula];
        
        // CRÍTICO: Filtrar por carga_excel_id si se proporciona
        // Esto asegura que las estadísticas solo incluyan facturas de la base asignada
        if ($carga_excel_id !== null) {
            $sql .= " AND c.carga_excel_id = ?";
            $params[] = $carga_excel_id;
        }
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Buscar clientes con múltiples facturas
     */
    public function getClientesConMultiplesFacturas() {
        $sql = "SELECT c.cedula, c.nombre, COUNT(f.id) as total_facturas
                FROM clientes c
                INNER JOIN facturas f ON c.id = f.cliente_id
                GROUP BY c.cedula, c.nombre
                HAVING COUNT(f.id) > 1
                ORDER BY total_facturas DESC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtener facturas por estado
     */
    public function getFacturasByEstado($estado) {
        $sql = "SELECT f.*, c.nombre, c.cedula, c.telefono, c.email
                FROM facturas f
                LEFT JOIN clientes c ON f.cliente_id = c.id
                WHERE f.estado_factura = ?
                ORDER BY f.fecha_creacion DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$estado]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtener facturas con mora alta
     */
    public function getFacturasMoraAlta($dias_mora = 30) {
        $sql = "SELECT f.*, c.nombre, c.cedula, c.telefono, c.email
                FROM facturas f
                LEFT JOIN clientes c ON f.cliente_id = c.id
                WHERE f.dias_mora > ? AND f.estado_factura = 'pendiente'
                ORDER BY f.dias_mora DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$dias_mora]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtener resumen de facturas para dashboard
     */
    public function getResumenFacturas() {
        $sql = "SELECT 
                    COUNT(*) as total_facturas,
                    COUNT(CASE WHEN estado_factura = 'pendiente' THEN 1 END) as facturas_activas,
                    COUNT(CASE WHEN estado_factura = 'pagada' THEN 1 END) as facturas_pagadas,
                    COUNT(CASE WHEN estado_factura = 'eliminada' THEN 1 END) as facturas_canceladas,
                    COUNT(CASE WHEN dias_mora > 30 THEN 1 END) as facturas_mora_alta,
                    SUM(saldo) as saldo_total,
                    AVG(dias_mora) as mora_promedio
                FROM facturas";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtener múltiples facturas por número y cliente_id en una sola consulta (BULK)
     * Optimizado para verificar existencia de múltiples facturas
     * 
     * @param array $facturas Array de arrays con 'numero_factura' y 'cliente_id'
     * @return array Array asociativo ['numero_factura-cliente_id' => factura_data]
     */
    public function getFacturasByNumeroAndClienteBulk($facturas) {
        if (empty($facturas)) {
            return [];
        }
        
        // Construir condiciones OR para múltiples facturas
        $conditions = [];
        $params = [];
        
        foreach ($facturas as $factura) {
            $conditions[] = "(numero_factura = ? AND cliente_id = ?)";
            $params[] = $factura['numero_factura'];
            $params[] = $factura['cliente_id'];
        }
        
        $sql = "SELECT * FROM facturas WHERE " . implode(' OR ', $conditions);
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $indexados = [];
        
        foreach ($resultados as $factura) {
            $key = $factura['numero_factura'] . '-' . $factura['cliente_id'];
            $indexados[$key] = $factura;
        }
        
        return $indexados;
    }
    
    /**
     * Crear múltiples facturas en una sola operación (BULK INSERT)
     * Optimizado para cargas masivas de hasta 1 millón de registros
     * 
     * @param array $facturas Array de arrays con datos de facturas
     * @return int Número de facturas creadas
     */
    public function crearFacturasBulk($facturas) {
        if (empty($facturas)) {
            return 0;
        }
        
        // Construir query de INSERT múltiple
        $sql = "INSERT INTO facturas (cliente_id, numero_factura, cedula, nombre, saldo, dias_mora, rmt, numero_contrato, telefono2, telefono3, franja, estado_factura, fecha_creacion, fecha_actualizacion) VALUES ";
        $values = [];
        $params = [];
        
        foreach ($facturas as $factura) {
            $values[] = "(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
            $params[] = $factura['cliente_id'] ?? null;
            $params[] = $factura['numero_factura'] ?? null;
            $params[] = $factura['cedula'] ?? null;
            $params[] = $factura['nombre'] ?? null;
            $params[] = $factura['saldo'] ?? null;
            $params[] = $factura['dias_mora'] ?? null;
            $params[] = $factura['rmt'] ?? null;
            $params[] = $factura['numero_contrato'] ?? null;
            $params[] = $factura['telefono2'] ?? null;
            $params[] = $factura['telefono3'] ?? null;
            $params[] = $factura['franja'] ?? null;
            $params[] = $factura['estado_factura'] ?? 'pendiente';
        }
        
        $sql .= implode(', ', $values);
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute($params);
            
            if (!$result) {
                $errorInfo = $stmt->errorInfo();
                error_log("Error en crearFacturasBulk execute: " . print_r($errorInfo, true));
                throw new PDOException("Error ejecutando bulk insert: " . $errorInfo[2]);
            }
            
            // Retornar el número de facturas intentadas (más confiable que rowCount para INSERT)
            return count($facturas);
        } catch (PDOException $e) {
            error_log("Error en crearFacturasBulk: " . $e->getMessage());
            error_log("SQL (primeros 1000 chars): " . substr($sql, 0, 1000));
            throw $e;
        }
    }
}
?>
