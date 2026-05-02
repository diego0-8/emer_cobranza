<?php
/**
 * Modelo de "facturación" en el sistema legado.
 * En la nueva BD (`emermedica_cobranza.sql`) la entidad equivalente es `obligaciones`.
 */

class FacturacionModel {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Crear una nueva obligación (equivalente a factura)
     */
    public function crearFactura($datos) {
        $agentLogPath = __DIR__ . '/../debug-a2fdce.log';

        // En el dump: rmt / numero_contrato / franja / dias_mora son NOT NULL.
        $rmt = trim((string)($datos['rmt'] ?? ''));
        if ($rmt === '') $rmt = '0';
        $contrato = trim((string)($datos['numero_contrato'] ?? ''));
        if ($contrato === '') $contrato = '0';
        $franja = trim((string)($datos['franja'] ?? ''));
        if ($franja === '') $franja = 'N/A';
        $diasMora = (int)($datos['dias_mora'] ?? 0);

        $sql = "INSERT INTO obligaciones (base_id, cliente_id, numero_factura, rmt, numero_contrato, saldo, dias_mora, franja, fecha_creacion, fecha_actualizacion)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
        $stmt = $this->pdo->prepare($sql);
        
        try {
            if ($stmt->execute([
                (int)($datos['base_id'] ?? $datos['carga_excel_id'] ?? 0),
                (int)($datos['cliente_id'] ?? 0),
                (string)($datos['numero_factura'] ?? ''),
                $rmt,
                $contrato,
                (float)($datos['saldo'] ?? 0),
                $diasMora,
                $franja
            ])) {
                return $this->pdo->lastInsertId();
            }
            return false;
        } catch (Throwable $e) {
            // #region agent log
            @file_put_contents($agentLogPath, json_encode([
                'sessionId' => 'a2fdce',
                'runId' => 'pre-fix',
                'hypothesisId' => 'IC2',
                'location' => 'models/FacturacionModel.php:crearFactura:catch',
                'message' => 'crearFactura exception',
                'data' => [
                    'type' => get_class($e),
                    'code' => (int)$e->getCode(),
                    'message' => substr((string)$e->getMessage(), 0, 300),
                    'baseId' => (int)($datos['base_id'] ?? $datos['carga_excel_id'] ?? 0),
                    'clienteId' => (int)($datos['cliente_id'] ?? 0),
                    'facturaLen' => strlen((string)($datos['numero_factura'] ?? '')),
                    'rmtWasDefault' => $rmt === '0',
                    'contratoWasDefault' => $contrato === '0',
                    'franjaWasDefault' => $franja === 'N/A',
                ],
                'timestamp' => (int) round(microtime(true) * 1000),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n", FILE_APPEND);
            // #endregion
            throw $e;
        }
    }
    
    /**
     * Obtener una obligación por su ID
     */
    public function getFacturaById($facturaId) {
        $sql = "SELECT o.*,
                       o.id_obligacion AS id,
                       CASE WHEN o.saldo > 0 THEN 'pendiente' ELSE 'pagada' END AS estado_factura
                FROM obligaciones o
                WHERE o.id_obligacion = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([(int)$facturaId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtener obligaciones de un cliente por cédula (todas las bases)
     */
    public function getFacturasByCedula($cedula) {
        $sql = "SELECT c.cedula, c.nombre, b.nombre as nombre_cargue, o.*
                FROM clientes c
                JOIN base_clientes b ON c.base_id = b.id_base
                LEFT JOIN obligaciones o ON c.id_cliente = o.cliente_id
                WHERE c.cedula = ?
                ORDER BY o.fecha_creacion DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([(string)$cedula]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtener todas las facturas de un cliente por ID
     * Si se proporciona carga_excel_id, solo se obtienen las facturas de esa base de datos
     */
    public function getFacturasByClienteId($cliente_id, $carga_excel_id = null) {
        $sql = "SELECT o.*,
                       o.id_obligacion AS id,
                       CASE WHEN o.saldo > 0 THEN 'pendiente' ELSE 'pagada' END AS estado_factura,
                       c.nombre, c.cedula, c.tel1 as telefono, c.email, b.nombre as nombre_cargue
                FROM obligaciones o
                JOIN clientes c ON o.cliente_id = c.id_cliente
                JOIN base_clientes b ON o.base_id = b.id_base
                WHERE o.cliente_id = ?";
        $params = [(int)$cliente_id];

        if ($carga_excel_id !== null) {
            $sql .= " AND o.base_id = ?";
            $params[] = (int)$carga_excel_id;
        }

        $sql .= " ORDER BY o.fecha_creacion DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtener una factura específica por número de factura
     */
    public function getFacturaByNumero($numero_factura) {
        $sql = "SELECT o.*, c.nombre, c.cedula, c.tel1 as telefono, c.email, b.nombre as nombre_cargue
                FROM obligaciones o
                JOIN clientes c ON o.cliente_id = c.id_cliente
                JOIN base_clientes b ON o.base_id = b.id_base
                WHERE o.numero_factura = ?
                LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([(string)$numero_factura]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtener una factura específica por número de factura y cliente_id
     */
    public function getFacturaByNumeroAndCliente($numero_factura, $cliente_id) {
        $sql = "SELECT o.*, c.nombre, c.cedula, c.tel1 as telefono, c.email, b.nombre as nombre_cargue
                FROM obligaciones o
                JOIN clientes c ON o.cliente_id = c.id_cliente
                JOIN base_clientes b ON o.base_id = b.id_base
                WHERE o.numero_factura = ? AND o.cliente_id = ?
                LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([(string)$numero_factura, (int)$cliente_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Primera obligación del cliente en una base (para importaciones sin factura explícita).
     */
    public function getPrimeraObligacionClienteEnBase($clienteId, $baseId) {
        $stmt = $this->pdo->prepare("
            SELECT o.*, c.nombre, c.cedula, c.tel1 as telefono, c.email, b.nombre as nombre_cargue
            FROM obligaciones o
            JOIN clientes c ON o.cliente_id = c.id_cliente
            JOIN base_clientes b ON o.base_id = b.id_base
            WHERE o.cliente_id = ? AND o.base_id = ?
            ORDER BY o.id_obligacion ASC
            LIMIT 1
        ");
        $stmt->execute([(int)$clienteId, (int)$baseId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
    
    /**
     * Verificar si una factura ya existe
     * @param string $numero_factura Número de factura
     * @param int|null $cliente_id ID del cliente (opcional, pero recomendado para evitar duplicados entre bases)
     * @return bool True si la factura existe para ese cliente, False si no existe
     */
    public function facturaExiste($numero_factura, $cliente_id = null) {
        if ($cliente_id !== null) {
            $sql = "SELECT COUNT(*) FROM obligaciones WHERE numero_factura = ? AND cliente_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([(string)$numero_factura, (int)$cliente_id]);
        } else {
            $sql = "SELECT COUNT(*) FROM obligaciones WHERE numero_factura = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([(string)$numero_factura]);
        }
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Actualizar una factura
     * Permite actualizar cualquier campo de la factura
     */
    public function actualizarFactura($id, $datos) {
        if (empty($datos)) return false;

        $campos = [];
        $valores = [];
        $permitidos = ['numero_factura', 'rmt', 'numero_contrato', 'saldo', 'dias_mora', 'franja'];

        foreach ($datos as $campo => $valor) {
            if (in_array($campo, $permitidos, true)) {
                $campos[] = "$campo = ?";
                $valores[] = $valor;
            }
        }

        if (empty($campos)) return false;

        $campos[] = "fecha_actualizacion = CURRENT_TIMESTAMP";
        $valores[] = (int)$id;

        $sql = "UPDATE obligaciones SET " . implode(", ", $campos) . " WHERE id_obligacion = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($valores);
    }
    
    /**
     * Eliminar una factura
     */
    public function eliminarFactura($id) {
        $sql = "DELETE FROM obligaciones WHERE id_obligacion = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([(int)$id]);
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
            $sql = "DELETE FROM obligaciones WHERE cliente_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([(int)$cliente_id]);
            return $stmt->rowCount();
        }
        
        // Crear placeholders para la consulta IN
        $placeholders = str_repeat('?,', count($numerosFactura) - 1) . '?';
        
        // Eliminar facturas que no están en la lista
        $sql = "DELETE FROM obligaciones WHERE cliente_id = ? AND numero_factura NOT IN ($placeholders)";
        $params = array_merge([(int)$cliente_id], $numerosFactura);
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
                    SUM(o.saldo) as saldo_total
                FROM obligaciones o
                JOIN clientes c ON o.cliente_id = c.id_cliente
                WHERE c.cedula = ?";
        
        $params = [$cedula];
        
        if ($carga_excel_id !== null) {
            $sql .= " AND o.base_id = ?";
            $params[] = (int)$carga_excel_id;
        }
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Buscar clientes con múltiples facturas
     */
    public function getClientesConMultiplesFacturas() {
        $sql = "SELECT c.cedula, c.nombre, COUNT(o.id_obligacion) as total_facturas
                FROM clientes c
                INNER JOIN obligaciones o ON c.id_cliente = o.cliente_id
                GROUP BY c.cedula, c.nombre
                HAVING COUNT(o.id_obligacion) > 1
                ORDER BY total_facturas DESC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtener facturas por estado
     */
    public function getFacturasByEstado($estado) {
        // En el esquema actual no existe `estado_factura`. Se infiere:
        // - pendiente: saldo > 0
        // - pagada: saldo <= 0
        // - eliminada: no aplica (retorna vacío)
        $estado = strtolower(trim((string)$estado));
        if ($estado === 'eliminada') return [];

        $cond = ($estado === 'pagada') ? "o.saldo <= 0" : "o.saldo > 0";
        $sql = "SELECT o.*, c.nombre, c.cedula, c.tel1 as telefono, c.email
                FROM obligaciones o
                LEFT JOIN clientes c ON o.cliente_id = c.id_cliente
                WHERE $cond
                ORDER BY o.fecha_creacion DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtener facturas con mora alta
     */
    public function getFacturasMoraAlta($dias_mora = 30) {
        // En el esquema actual no existe `estado_factura`. Se considera "pendiente" cuando saldo > 0.
        $sql = "SELECT o.*, c.nombre, c.cedula, c.tel1 as telefono, c.email
                FROM obligaciones o
                LEFT JOIN clientes c ON o.cliente_id = c.id_cliente
                WHERE o.dias_mora > ? AND o.saldo > 0
                ORDER BY o.dias_mora DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$dias_mora]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtener resumen de facturas para dashboard
     */
    public function getResumenFacturas() {
        // En el esquema actual no existe `estado_factura`. Se infiere:
        // - activas/pendientes: saldo > 0
        // - pagadas: saldo <= 0
        $sql = "SELECT 
                    COUNT(*) as total_facturas,
                    SUM(CASE WHEN saldo > 0 THEN 1 ELSE 0 END) as facturas_activas,
                    SUM(CASE WHEN saldo <= 0 THEN 1 ELSE 0 END) as facturas_pagadas,
                    0 as facturas_canceladas,
                    SUM(CASE WHEN dias_mora > 30 AND saldo > 0 THEN 1 ELSE 0 END) as facturas_mora_alta,
                    SUM(saldo) as saldo_total,
                    AVG(dias_mora) as mora_promedio
                FROM obligaciones";
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
        
        $sql = "SELECT * FROM obligaciones WHERE " . implode(' OR ', $conditions);
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $indexados = [];
        
        foreach ($resultados as $obligacion) {
            $key = ($obligacion['numero_factura'] ?? '') . '-' . ($obligacion['cliente_id'] ?? '');
            $indexados[$key] = $obligacion;
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
        
        // Construir query de INSERT múltiple en obligaciones (schema real)
        $sql = "INSERT INTO obligaciones (base_id, cliente_id, numero_factura, rmt, numero_contrato, saldo, dias_mora, franja, fecha_creacion, fecha_actualizacion) VALUES ";
        $values = [];
        $params = [];
        
        foreach ($facturas as $factura) {
            $values[] = "(?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
            $params[] = (int)($factura['base_id'] ?? $factura['carga_excel_id'] ?? 0);
            $params[] = (int)($factura['cliente_id'] ?? 0);
            $params[] = (string)($factura['numero_factura'] ?? '');

            $rmt = trim((string)($factura['rmt'] ?? ''));
            if ($rmt === '') $rmt = '0';
            $params[] = $rmt;

            $contrato = trim((string)($factura['numero_contrato'] ?? ''));
            if ($contrato === '') $contrato = '0';
            $params[] = $contrato;

            $params[] = (float)($factura['saldo'] ?? 0);
            $params[] = (int)($factura['dias_mora'] ?? 0);

            $franja = trim((string)($factura['franja'] ?? ''));
            if ($franja === '') $franja = 'N/A';
            $params[] = $franja;
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
