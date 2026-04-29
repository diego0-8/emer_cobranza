<?php
class ClienteModel {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Aliases para compatibilidad con el backend/vistas existentes:
     * - id_cliente -> id
     * - base_id -> carga_excel_id
     * - tel1..tel10 -> telefono/celular2/cel3..cel10
     * - estado -> estado_cliente
     */
    private function selectClienteCompatFields(): string {
        return "c.id_cliente AS id,
                c.id_cliente,
                c.base_id,
                c.base_id AS carga_excel_id,
                c.cedula,
                c.nombre,
                c.email,
                c.ciudad,
                c.tel1 AS telefono,
                c.tel2 AS celular2,
                c.tel3 AS cel3,
                c.tel4 AS cel4,
                c.tel5 AS cel5,
                c.tel6 AS cel6,
                c.tel7 AS cel7,
                c.tel8 AS cel8,
                c.tel9 AS cel9,
                c.tel10 AS cel10,
                NULL AS cel11,
                c.estado,
                c.estado AS estado_cliente,
                c.fecha_creacion,
                c.fecha_actualizacion";
    }

    public function getClientsByCargaId($cargaId, $limit = null, $offset = null) {
        $sql = "SELECT " . $this->selectClienteCompatFields() . " FROM clientes c WHERE c.base_id = ?";
        $params = [(int)$cargaId];
        
        if ($limit !== null && $offset !== null) {
            $sql .= " LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
        }
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene el número total de clientes para una carga específica.
     * @param int $cargaId El ID de la carga de Excel.
     * @return int El número total de clientes.
     */
    public function getTotalClientsByCargaId($cargaId) {
        $sql = "SELECT COUNT(*) AS total FROM clientes WHERE base_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([(int)$cargaId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    }

    /**
     * Obtiene clientes por carga ID filtrando solo los del coordinador específico
     */
    public function getClientsByCargaIdAndCoordinador($cargaId, $coordinadorId, $limit = null, $offset = null) {
        $sql = "SELECT " . $this->selectClienteCompatFields() . "
                FROM clientes c
                JOIN base_clientes b ON c.base_id = b.id_base
                WHERE c.base_id = ? AND b.creado_por = ?";
        $params = [(int)$cargaId, (string)$coordinadorId];
        
        if ($limit !== null && $offset !== null) {
            $sql .= " LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
        }
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene el total de clientes por carga filtrando solo los del coordinador específico
     */
    public function getTotalClientsByCargaIdAndCoordinador($cargaId, $coordinadorId) {
        $sql = "SELECT COUNT(*) AS total FROM clientes c
                JOIN base_clientes b ON c.base_id = b.id_base
                WHERE c.base_id = ? AND b.creado_por = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([(int)$cargaId, (string)$coordinadorId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    }
    
    /**
     * Obtiene el total de clientes asignados por carga filtrando solo los del coordinador específico
     */
    public function getTotalClientsAsignadosByCargaIdAndCoordinador($cargaId, $coordinadorId) {
        // En el esquema nuevo no hay `asesor_id` en clientes. Mantener compatibilidad con 0.
        $sql = "SELECT 0 AS total";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    }

    /**
     * Obtiene clientes no asignados por carga filtrando solo los del coordinador específico
     * EXCLUYE clientes que ya fueron gestionados previamente (tienen historial de gestiones)
     */
    public function getUnassignedClientsByCargaAndCoordinador($cargaId, $coordinadorId) {
        // En el esquema nuevo no existe asignación por cliente.
        // Consideramos "no asignados" = todos los clientes de la base.
        $stmt = $this->pdo->prepare("
            SELECT " . $this->selectClienteCompatFields() . "
            FROM clientes c
            JOIN base_clientes b ON c.base_id = b.id_base
            WHERE c.base_id = ? AND b.creado_por = ?
        ");
        $stmt->execute([(int)$cargaId, (string)$coordinadorId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtiene clientes liberados que pueden ser reasignados (sin historial de gestiones)
     */
    public function getLiberatedClientsAvailableForReassignment($cargaId, $coordinadorId) {
        // No aplica en el esquema nuevo (sin asignación por cliente).
        return [];
    }
    
    /**
     * Obtiene el total de clientes asignados por asesor y carga específica
     */
    public function getTotalClientsByAsesorAndCarga($asesorId, $cargaId, $coordinadorId) {
        $sql = "SELECT COUNT(*) AS total FROM clientes c 
                JOIN cargas_excel ce ON c.carga_excel_id = ce.id 
                WHERE c.asesor_id = ? AND c.carga_excel_id = ? AND ce.usuario_coordinador_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$asesorId, $cargaId, $coordinadorId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    }
    
    /**
     * Busca clientes por término de búsqueda en una carga específica
     * Optimizado para millones de registros
     */
    public function buscarClientesPorTermino($cargaId, $coordinadorId, $searchTerm) {
        // Limpiar término de búsqueda
        $searchTerm = trim($searchTerm);
        if (empty($searchTerm)) {
            return [];
        }
        
        // Validar parámetros
        if (!is_numeric($cargaId) || !is_numeric($coordinadorId)) {
            return [];
        }

        // Esquema real: clientes.base_id, base_clientes.creado_por y teléfonos en tel1..tel10
        $termLike = '%' . $searchTerm . '%';

        // Si es numérico, priorizar coincidencias exactas en cédula y teléfonos.
        if (ctype_digit($searchTerm)) {
            $sql = "
                SELECT " . $this->selectClienteCompatFields() . "
                FROM clientes c
                JOIN base_clientes b ON c.base_id = b.id_base
                WHERE c.base_id = ?
                  AND b.creado_por = ?
                  AND (
                    c.cedula = ?
                    OR c.tel1 = ? OR c.tel2 = ? OR c.tel3 = ? OR c.tel4 = ? OR c.tel5 = ?
                    OR c.tel6 = ? OR c.tel7 = ? OR c.tel8 = ? OR c.tel9 = ? OR c.tel10 = ?
                  )
                ORDER BY CASE WHEN c.cedula = ? THEN 0 ELSE 1 END, c.nombre ASC
                LIMIT 200
            ";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                (int)$cargaId,
                (string)$coordinadorId,
                $searchTerm,
                $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm,
                $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm,
                $searchTerm
            ]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($rows)) return $rows;
        }

        // Búsqueda flexible (por cedula o teléfonos o nombre/email)
        $sql = "
            SELECT " . $this->selectClienteCompatFields() . "
            FROM clientes c
            JOIN base_clientes b ON c.base_id = b.id_base
            WHERE c.base_id = ?
              AND b.creado_por = ?
              AND (
                c.nombre LIKE ?
                OR c.cedula LIKE ?
                OR c.email LIKE ?
                OR c.tel1 LIKE ? OR c.tel2 LIKE ? OR c.tel3 LIKE ? OR c.tel4 LIKE ? OR c.tel5 LIKE ?
                OR c.tel6 LIKE ? OR c.tel7 LIKE ? OR c.tel8 LIKE ? OR c.tel9 LIKE ? OR c.tel10 LIKE ?
              )
            ORDER BY c.nombre ASC
            LIMIT 1000
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            (int)$cargaId,
            (string)$coordinadorId,
            $termLike,
            $termLike,
            $termLike,
            $termLike, $termLike, $termLike, $termLike, $termLike,
            $termLike, $termLike, $termLike, $termLike, $termLike
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Guarda los clientes en la base de datos a partir de un archivo Excel.
     * Se asume que los datos del array $clientes tienen la misma estructura que las columnas de la tabla.
     * @param int $cargaId El ID de la carga de Excel.
     * @param array $clientes Un array de arrays con los datos de los clientes.
     * @return bool Retorna true si el guardado fue exitoso, false en caso de error.
     */
    public function saveClientsFromExcel($cargaId, $clientes) {
        $this->pdo->beginTransaction();
        try {
            $sql = "INSERT INTO clientes (base_id, cedula, nombre, email, ciudad, tel1, tel2, tel3, tel4, tel5, tel6, tel7, tel8, tel9, tel10, estado)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'activo')";
            $stmt = $this->pdo->prepare($sql);
            
            foreach ($clientes as $cliente) {
                $stmt->execute([
                    (int)$cargaId,
                    (string)($cliente['cedula'] ?? ''),
                    (string)($cliente['nombre'] ?? ''),
                    (string)($cliente['email'] ?? ''),
                    (string)($cliente['ciudad'] ?? ''),
                    (string)($cliente['telefono'] ?? $cliente['tel1'] ?? ''),
                    (string)($cliente['celular2'] ?? $cliente['tel2'] ?? ''),
                    (string)($cliente['cel3'] ?? $cliente['tel3'] ?? ''),
                    (string)($cliente['cel4'] ?? $cliente['tel4'] ?? ''),
                    (string)($cliente['cel5'] ?? $cliente['tel5'] ?? ''),
                    (string)($cliente['cel6'] ?? $cliente['tel6'] ?? ''),
                    (string)($cliente['cel7'] ?? $cliente['tel7'] ?? ''),
                    (string)($cliente['cel8'] ?? $cliente['tel8'] ?? ''),
                    (string)($cliente['cel9'] ?? $cliente['tel9'] ?? ''),
                    (string)($cliente['cel10'] ?? $cliente['tel10'] ?? '')
                ]);
            }
            $this->pdo->commit();
            return true;
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log("Error al guardar clientes: " . $e->getMessage()); 
            return false;
        }
    }

    public function getUnassignedClientsByCarga($cargaId) {
        // Sin asignación por cliente en el esquema nuevo: devolver todos.
        $stmt = $this->pdo->prepare("SELECT " . $this->selectClienteCompatFields() . " FROM clientes c WHERE c.base_id = ?");
        $stmt->execute([(int)$cargaId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function assignClientsToAsesor($clienteIds, $asesorId) {
        if (empty($clienteIds)) return;
        
        $this->pdo->beginTransaction();
        try {
            // Actualizar la tabla clientes
            $ids = implode(',', array_map('intval', $clienteIds));
            $sql = "UPDATE clientes SET asesor_id = ? WHERE id IN ($ids)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$asesorId]);
            
            // Crear registros en asignaciones_clientes
            $sql = "INSERT INTO asignaciones_clientes (asesor_id, cliente_id, estado, fecha_asignacion) VALUES (?, ?, 'asignado', NOW())";
            $stmt = $this->pdo->prepare($sql);
            
            foreach ($clienteIds as $clienteId) {
                // Verificar si ya existe la asignación
                $existing = $this->getAsignacionId($asesorId, $clienteId);
                if (!$existing) {
                    $stmt->execute([$asesorId, $clienteId]);
                }
            }
            
            $this->pdo->commit();
            return true;
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log("Error al asignar clientes: " . $e->getMessage());
            throw $e;
        }
    }

    public function getClienteById($clienteId) {
        $sql = "SELECT " . $this->selectClienteCompatFields() . " FROM clientes c WHERE c.id_cliente = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([(int)$clienteId]);
        $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Asegurar que todas las columnas de teléfono existan en el array (incluso si son NULL)
        if ($cliente) {
            $columnasTelefono = ['telefono', 'celular2', 'cel3', 'cel4', 'cel5', 'cel6', 'cel7', 'cel8', 'cel9', 'cel10', 'cel11'];
            foreach ($columnasTelefono as $columna) {
                if (!array_key_exists($columna, $cliente)) {
                    $cliente[$columna] = null; // Asegurar que la clave existe
                }
            }
        }
        
        return $cliente;
    }

    public function getAssignedClientsForAsesor($asesorId) {
        // Esquema actual (emermedica_cobranza.sql):
        // - tareas.asesor_cedula, tareas.id_tarea
        // - detalle_tareas.tarea_id, detalle_tareas.cliente_id
        // - clientes.id_cliente
        //
        // Un "cliente asignado" para asesor = cliente presente en detalle_tareas de una tarea del asesor.
        $sql = "
            SELECT DISTINCT
                " . $this->selectClienteCompatFields() . ",
                t.id_tarea AS tarea_id,
                t.estado AS estado_tarea,
                dt.gestionado AS gestionado
            FROM tareas t
            JOIN detalle_tareas dt ON dt.tarea_id = t.id_tarea
            JOIN clientes c ON c.id_cliente = dt.cliente_id
            WHERE t.asesor_cedula = ?
            ORDER BY c.nombre ASC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([(string)$asesorId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene el ID de la asignación de un cliente a un asesor.
     * @param int $asesorId El ID del asesor.
     * @param int $clienteId El ID del cliente.
     * @return int|false El ID de la asignación o false si no se encuentra.
     */
    public function getAsignacionId($asesorId, $clienteId) {
        $sql = "SELECT id FROM asignaciones_clientes WHERE asesor_id = ? AND cliente_id = ? LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$asesorId, $clienteId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['id'] : false;
    }
    
    public function getTotalClientesByAsesor($asesorId) {
        // En el esquema emermedica_cobranza no existe clientes.asesor_id; el asesor se relaciona por cédula
        // en asignacion_base_asesores sobre las bases donde hay clientes.
        $sql = "SELECT COUNT(DISTINCT c.id_cliente) AS total
                FROM clientes c
                INNER JOIN base_clientes b ON b.id_base = c.base_id AND b.estado = 'activo'
                INNER JOIN asignacion_base_asesores aba
                    ON aba.base_id = c.base_id AND aba.estado = 'activa' AND aba.asesor_cedula = ?
                WHERE c.estado = 'activo'";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([(string)$asesorId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($result['total'] ?? 0);
    }
    
    /**
     * Obtiene clientes asignados a un asesor específico
     */
    public function getClientesByAsesor($asesorId) {
        $stmt = $this->pdo->prepare("SELECT c.*, ac.id as asignacion_id, ac.estado as estado_gestion 
                                    FROM clientes c 
                                    LEFT JOIN asignaciones_clientes ac ON c.id = ac.cliente_id AND ac.asesor_id = c.asesor_id
                                    WHERE c.asesor_id = ? 
                                    ORDER BY c.id DESC");
        $stmt->execute([$asesorId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Traspasa un cliente de un asesor a otro
     */
    public function traspasarCliente($clienteId, $nuevoAsesorId, $asesorOrigenId) {
        $this->pdo->beginTransaction();
        try {
            // Verificar que el cliente pertenezca al asesor origen
            $cliente = $this->getClienteById($clienteId);
            if (!$cliente || $cliente['asesor_id'] != $asesorOrigenId) {
                throw new Exception("El cliente no pertenece al asesor origen");
            }
            
            // Actualizar la tabla clientes
            $sql = "UPDATE clientes SET asesor_id = ? WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$nuevoAsesorId, $clienteId]);
            
            // Actualizar o crear registro en asignaciones_clientes
            $sql = "UPDATE asignaciones_clientes SET asesor_id = ?, estado = 'asignado', fecha_asignacion = NOW() 
                    WHERE cliente_id = ? AND asesor_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$nuevoAsesorId, $clienteId, $asesorOrigenId]);
            
            // Si no se actualizó ninguna fila, crear nueva asignación
            if ($stmt->rowCount() == 0) {
                $sql = "INSERT INTO asignaciones_clientes (asesor_id, cliente_id, estado, fecha_asignacion) 
                        VALUES (?, ?, 'asignado', NOW())";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$nuevoAsesorId, $clienteId]);
            }
            
            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Error al traspasar cliente: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Libera un cliente de un asesor (lo deja sin asignar)
     */
    public function liberarCliente($clienteId, $asesorId) {
        $this->pdo->beginTransaction();
        try {
            // Verificar que el cliente pertenezca al asesor
            $cliente = $this->getClienteById($clienteId);
            if (!$cliente || $cliente['asesor_id'] != $asesorId) {
                throw new Exception("El cliente no pertenece al asesor especificado");
            }
            
            // Liberar el cliente
            $sql = "UPDATE clientes SET asesor_id = NULL WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$clienteId]);
            
            // Actualizar estado en asignaciones_clientes
            $sql = "UPDATE asignaciones_clientes SET estado = 'liberado', fecha_asignacion = NOW() 
                    WHERE cliente_id = ? AND asesor_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$clienteId, $asesorId]);
            
            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Error al liberar cliente: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Obtiene un cliente específico verificando que pertenezca a una carga del coordinador
     */
    public function getClienteByIdAndCoordinador($clienteId, $coordinadorId) {
        $sql = "SELECT c.*, ce.usuario_coordinador_id as coordinador_id
                FROM clientes c
                JOIN cargas_excel ce ON c.carga_excel_id = ce.id
                WHERE c.id = ? AND ce.usuario_coordinador_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$clienteId, $coordinadorId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene un cliente por su cédula
     * @deprecated Usar getClienteByCedulaYCarga() para evitar mover clientes entre bases
     */
    public function getClienteByCedula($cedula) {
        $sql = "SELECT * FROM clientes WHERE cedula = ? LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$cedula]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene un cliente por su cédula Y carga_excel_id
     * Esto asegura que cada cliente esté ligado a su base específica
     */
    public function getClienteByCedulaYCarga($cedula, $cargaId) {
        $sql = "SELECT " . $this->selectClienteCompatFields() . "
                FROM clientes c
                WHERE c.cedula = ? AND c.base_id = ?
                LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([(string)$cedula, (int)$cargaId]);
        $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Asegurar que todas las columnas de teléfono existan en el array (incluso si son NULL)
        if ($cliente) {
            $columnasTelefono = ['telefono', 'celular2', 'cel3', 'cel4', 'cel5', 'cel6', 'cel7', 'cel8', 'cel9', 'cel10', 'cel11'];
            foreach ($columnasTelefono as $columna) {
                if (!array_key_exists($columna, $cliente)) {
                    $cliente[$columna] = null; // Asegurar que la clave existe
                }
            }
        }
        
        return $cliente;
    }

    /**
     * Verifica si un cliente ya está en una carga específica
     */
    public function clienteYaEnCarga($clienteId, $cargaId) {
        $sql = "SELECT id_cliente FROM clientes WHERE id_cliente = ? AND base_id = ? LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([(int)$clienteId, (int)$cargaId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
    }

    /**
     * Agrega un cliente existente a una carga
     * @deprecated Este método MUEVE clientes entre bases, causando mezcla de facturas
     * NO USAR: En su lugar, crear un NUEVO cliente con la misma cédula pero diferente carga_excel_id
     */
    public function agregarClienteACarga($cargaId, $clienteId) {
        // DEPRECADO: Este método mueve clientes, causando que las facturas se mezclen
        // En su lugar, crear un NUEVO cliente con la misma cédula pero diferente carga_excel_id
        error_log("ADVERTENCIA: agregarClienteACarga() está deprecado. No mover clientes entre bases.");
        $sql = "UPDATE clientes SET base_id = ? WHERE id_cliente = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([(int)$cargaId, (int)$clienteId]);
    }

    /**
     * Crea un nuevo cliente
     */
    public function crearCliente($datos) {
        // En el dump: email/ciudad/tel1..tel10 son NOT NULL.
        $sql = "INSERT INTO clientes (base_id, cedula, nombre, email, ciudad, tel1, tel2, tel3, tel4, tel5, tel6, tel7, tel8, tel9, tel10, estado)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'activo')";
        $stmt = $this->pdo->prepare($sql);

        $agentLogPath = __DIR__ . '/../debug-a2fdce.log';

        $baseId = (int)($datos['carga_excel_id'] ?? $datos['base_id'] ?? 0);
        $cedula = (string)($datos['cedula'] ?? '');
        $nombre = trim((string)($datos['nombre'] ?? ''));
        if ($nombre === '') $nombre = 'SIN NOMBRE';

        $email = trim((string)($datos['email'] ?? ''));
        if ($email === '') $email = 'sin-email@local';

        $ciudad = trim((string)($datos['ciudad'] ?? ''));
        if ($ciudad === '') $ciudad = 'N/A';

        // Teléfonos NOT NULL: usar '' si no hay.
        $tel1 = (string)($datos['telefono'] ?? $datos['tel1'] ?? '');
        $tel2 = (string)($datos['celular2'] ?? $datos['tel2'] ?? $datos['telefono2'] ?? '');
        $tel3 = (string)($datos['cel3'] ?? $datos['tel3'] ?? $datos['telefonos_3'] ?? '');
        $tel4 = (string)($datos['cel4'] ?? $datos['tel4'] ?? '');
        $tel5 = (string)($datos['cel5'] ?? $datos['tel5'] ?? '');
        $tel6 = (string)($datos['cel6'] ?? $datos['tel6'] ?? '');
        $tel7 = (string)($datos['cel7'] ?? $datos['tel7'] ?? '');
        $tel8 = (string)($datos['cel8'] ?? $datos['tel8'] ?? '');
        $tel9 = (string)($datos['cel9'] ?? $datos['tel9'] ?? '');
        $tel10 = (string)($datos['cel10'] ?? $datos['tel10'] ?? '');

        try {
            $ok = $stmt->execute([
                $baseId,
                $cedula,
                $nombre,
                $email,
                $ciudad,
                $tel1,
                $tel2,
                $tel3,
                $tel4,
                $tel5,
                $tel6,
                $tel7,
                $tel8,
                $tel9,
                $tel10
            ]);
            if ($ok) {
                return $this->pdo->lastInsertId();
            }
            return false;
        } catch (Throwable $e) {
            // #region agent log
            @file_put_contents($agentLogPath, json_encode([
                'sessionId' => 'a2fdce',
                'runId' => 'pre-fix',
                'hypothesisId' => 'IC1',
                'location' => 'models/ClienteModel.php:crearCliente:catch',
                'message' => 'crearCliente exception',
                'data' => [
                    'type' => get_class($e),
                    'code' => (int)$e->getCode(),
                    'message' => substr((string)$e->getMessage(), 0, 300),
                    'baseId' => $baseId,
                    'cedulaLen' => strlen($cedula),
                    'emailWasDefault' => $email === 'sin-email@local',
                    'ciudadWasDefault' => $ciudad === 'N/A',
                    'tel1Len' => strlen($tel1),
                ],
                'timestamp' => (int) round(microtime(true) * 1000),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n", FILE_APPEND);
            // #endregion
            throw $e;
        }
    }

    /**
     * Obtiene el total de clientes únicos en toda la base de datos
     */
    public function getTotalClientesUnicos() {
        $sql = "SELECT COUNT(DISTINCT cedula) as total FROM clientes";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    }
    
    /**
     * Obtiene clientes por asesor con filtros de fechas
     */
    public function getClientesByAsesorWithFilters($asesorId, $fechaInicio = null, $fechaFin = null) {
        $sql = "SELECT c.*, 
                       CASE WHEN hg.id IS NOT NULL THEN true ELSE false END as gestionado,
                       CASE WHEN hg.tipo_gestion IS NOT NULL THEN true ELSE false END as contactado,
                       COALESCE(hg.resultado, 'Sin tipificar') as tipificacion,
                       COALESCE(hg.comentarios, 'Sin observaciones') as observaciones,
                       hg.fecha_gestion,
                       hg.tipo_gestion,
                       hg.monto_venta,
                       hg.duracion_llamada,
                       hg.edad,
                       hg.num_personas,
                       hg.valor_cotizacion,
                       hg.whatsapp_enviado
                FROM clientes c
                JOIN asignaciones_clientes ac ON c.id = ac.cliente_id
                LEFT JOIN historial_gestion hg ON ac.id = hg.asignacion_id
                WHERE ac.asesor_id = ?";
        
        $params = [$asesorId];
        
        // Agregar filtros de fechas si están especificados
        if ($fechaInicio) {
            $sql .= " AND DATE(c.fecha_creacion) >= ?";
            $params[] = $fechaInicio;
        }
        
        if ($fechaFin) {
            $sql .= " AND DATE(c.fecha_creacion) <= ?";
            $params[] = $fechaFin;
        }
        
        $sql .= " ORDER BY c.fecha_creacion DESC, hg.fecha_gestion DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtiene clientes del coordinador con filtros
     */
    public function getClientesByCoordinadorWithFilters($coordinadorId, $fechaInicio = null, $fechaFin = null, $estadoCliente = null) {
        $sql = "SELECT c.* 
                FROM clientes c 
                JOIN cargas_excel ce ON c.carga_excel_id = ce.id
                WHERE ce.usuario_coordinador_id = ?";
        
        $params = [$coordinadorId];
        
        // Agregar filtros de fechas si están especificados
        if ($fechaInicio) {
            $sql .= " AND DATE(c.fecha_creacion) >= ?";
            $params[] = $fechaInicio;
        }
        
        if ($fechaFin) {
            $sql .= " AND DATE(c.fecha_creacion) <= ?";
            $params[] = $fechaFin;
        }
        
        // Agregar filtro por estado del cliente
        if ($estadoCliente) {
            $sql .= " AND c.estado_cliente = ?";
            $params[] = $estadoCliente;
        }
        
        $sql .= " ORDER BY c.fecha_creacion DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtiene el siguiente cliente no gestionado del asesor
     */
    public function getSiguienteClienteAsesor($asesorId) {
        // #region agent log b7eaa7 ClienteModel getSiguienteClienteAsesor entry
        try { @file_put_contents(__DIR__ . '/../debug-b7eaa7.log', json_encode([
            'sessionId'=>'b7eaa7','runId'=>'pre','hypothesisId'=>'NX5',
            'location'=>'models/ClienteModel.php:getSiguienteClienteAsesor:entry',
            'message'=>'enter',
            'data'=>[
                'asesorIdType'=>gettype($asesorId),
                'asesorIdLen'=>strlen((string)$asesorId),
            ],
            'timestamp'=>(int) round(microtime(true)*1000)
        ], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n", FILE_APPEND); } catch (Throwable $e) {}
        // #endregion

        // Esquema actual (nuevo): tareas + detalle_tareas es la fuente de asignación.
        // Tarea "activa": la más reciente en estado pendiente para este asesor.
        $sql = "
            SELECT
                c.id_cliente,
                c.cedula,
                c.nombre,
                c.email,
                c.tel1,
                c.tel2,
                c.base_id,
                dt.tarea_id
            FROM tareas t
            JOIN detalle_tareas dt ON dt.tarea_id = t.id_tarea
            JOIN clientes c ON c.id_cliente = dt.cliente_id
            WHERE t.id_tarea = (
                SELECT tt.id_tarea
                FROM tareas tt
                WHERE tt.asesor_cedula = ?
                  AND tt.estado = 'pendiente'
                ORDER BY tt.fecha_creacion DESC, tt.id_tarea DESC
                LIMIT 1
            )
              AND dt.gestionado = 'no'
            ORDER BY dt.id_detalle ASC
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([(string)$asesorId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

        // #region agent log b7eaa7 ClienteModel getSiguienteClienteAsesor result
        try { @file_put_contents(__DIR__ . '/../debug-b7eaa7.log', json_encode([
            'sessionId'=>'b7eaa7','runId'=>'pre','hypothesisId'=>'NX6',
            'location'=>'models/ClienteModel.php:getSiguienteClienteAsesor:result',
            'message'=>'db_row',
            'data'=>[
                'hasRow'=>$row?1:0,
                'keys'=>is_array($row)?array_slice(array_keys($row),0,20):[],
            ],
            'timestamp'=>(int) round(microtime(true)*1000)
        ], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n", FILE_APPEND); } catch (Throwable $e) {}
        // #endregion

        // Normalizar claves esperadas por el frontend
        if (is_array($row)) {
            $row['id'] = (int)($row['id_cliente'] ?? 0);
            $row['telefono'] = (string)($row['tel1'] ?? '');
        }

        return $row;
    }
    
    /**
     * Actualiza el estado de un cliente
     */
    public function actualizarEstadoCliente($clienteId, $nuevoEstado) {
        $sql = "UPDATE clientes SET estado_cliente = ? WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$nuevoEstado, $clienteId]);
    }
    
    /**
     * Actualiza múltiples campos de un cliente
     */
    public function actualizarCliente($clienteId, $datos) {
        if (empty($datos)) {
            return false;
        }
        
        $campos = [];
        $valores = [];
        
        foreach ($datos as $campo => $valor) {
            if ($campo === 'estado_cliente') {
                $campo = 'estado';
            }
            if ($campo === 'telefono') {
                $campo = 'tel1';
            }
            if ($campo === 'celular2') {
                $campo = 'tel2';
            }
            $campos[] = "$campo = ?";
            $valores[] = $valor;
        }
        
        $valores[] = $clienteId;
        
        $sql = "UPDATE clientes SET " . implode(", ", $campos) . " WHERE id_cliente = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($valores);
    }
    
    /**
     * Obtiene las asignaciones de un asesor
     */
    public function getAsignacionesByAsesor($asesorId) {
        $sql = "SELECT ac.*, c.nombre, c.cedula, c.telefono, c.celular2
                FROM asignaciones_clientes ac
                JOIN clientes c ON ac.cliente_id = c.id
                WHERE ac.asesor_id = ?
                ORDER BY ac.fecha_asignacion DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$asesorId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtiene clientes asignados sin gestionar
     */
    public function getClientesAsignadosSinGestionar($asesorId) {
        $sql = "SELECT c.*, ac.id as asignacion_id, ac.fecha_asignacion, ac.estado,
                       false as gestionado,
                       false as contactado,
                       'Sin tipificar' as tipificacion,
                       'Pendiente de gestionar' as observaciones
                FROM clientes c
                JOIN asignaciones_clientes ac ON c.id = ac.cliente_id
                WHERE ac.asesor_id = ?
                AND NOT EXISTS (
                    SELECT 1 FROM historial_gestion hg 
                    WHERE hg.asignacion_id = ac.id
                )
                ORDER BY ac.fecha_asignacion DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$asesorId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtiene clientes del asesor con filtros avanzados
     */
    public function getClientesByAsesorWithAdvancedFilters($asesorId, $fechaInicio = null, $fechaFin = null, $filtroGestion = null, $filtroContacto = null, $filtroTipificacion = null) {
        $sql = "SELECT c.*, 
                       CASE WHEN hg.id IS NOT NULL THEN true ELSE false END as gestionado,
                       CASE WHEN hg.tipo_gestion IS NOT NULL THEN true ELSE false END as contactado,
                       COALESCE(hg.resultado, 'Sin tipificar') as tipificacion,
                       COALESCE(hg.comentarios, 'Sin observaciones') as observaciones,
                       hg.fecha_gestion,
                       hg.tipo_gestion,
                       hg.monto_venta,
                       hg.duracion_llamada,
                       hg.edad,
                       hg.num_personas,
                       hg.valor_cotizacion,
                       hg.whatsapp_enviado
                FROM clientes c
                JOIN asignaciones_clientes ac ON c.id = ac.cliente_id
                LEFT JOIN historial_gestion hg ON ac.id = hg.asignacion_id
                WHERE ac.asesor_id = ?";
        
        $params = [$asesorId];
        
        // Filtro por gestión
        if ($filtroGestion === 'gestionado') {
            $sql .= " AND hg.id IS NOT NULL";
        } elseif ($filtroGestion === 'no_gestionado') {
            $sql .= " AND hg.id IS NULL";
        }
        
        // Filtro por contacto
        if ($filtroContacto === 'contactado') {
            $sql .= " AND hg.tipo_gestion IS NOT NULL";
        } elseif ($filtroContacto === 'no_contactado') {
            $sql .= " AND (hg.tipo_gestion IS NULL OR hg.tipo_gestion = 'no-contactado')";
        }
        
        // Filtro por tipificación
        if ($filtroTipificacion && $filtroTipificacion !== 'todos') {
            $sql .= " AND hg.resultado = ?";
            $params[] = $filtroTipificacion;
        }
        
        // Filtros de fechas
        if ($fechaInicio) {
            $sql .= " AND DATE(c.fecha_creacion) >= ?";
            $params[] = $fechaInicio;
        }
        
        if ($fechaFin) {
            $sql .= " AND DATE(c.fecha_creacion) <= ?";
            $params[] = $fechaFin;
        }
        
        $sql .= " ORDER BY c.fecha_creacion DESC, hg.fecha_gestion DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene un cliente por cédula en una carga específica
     */
    public function getClienteByCedulaAndCarga($cedula, $cargaId) {
        $sql = "SELECT " . $this->selectClienteCompatFields() . " FROM clientes c WHERE c.cedula = ? AND c.base_id = ? LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([(string)$cedula, (int)$cargaId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene todas las cargas del coordinador con filtro por estado habilitado
     */
    public function getCargasByCoordinador($coordinadorId, $soloHabilitadas = true) {
        // Nuevo esquema: base_clientes (PK id_base) y clientes.base_id
        // Aliases compat para no tocar vistas/controladores:
        // - id_base -> id
        // - nombre -> nombre_cargue
        // - creado_por -> usuario_coordinador_id
        // - fecha_actualizacion -> fecha_cargue
        // - estado -> estado_habilitado
        // Nota: `base_clientes.total_clientes/total_obligaciones` puede quedar desactualizado.
        // Para mostrar información real en UI, contamos desde `clientes` y `obligaciones`.
        $sql = "SELECT
                    b.id_base AS id,
                    b.id_base AS id_base,
                    b.nombre AS nombre_cargue,
                    b.nombre AS nombre,
                    b.creado_por AS usuario_coordinador_id,
                    b.creado_por AS creado_por,
                    b.fecha_actualizacion AS fecha_cargue,
                    b.fecha_actualizacion AS fecha_actualizacion,
                    b.estado AS estado_habilitado,
                    b.estado AS estado,
                    (SELECT COUNT(*) FROM clientes c WHERE c.base_id = b.id_base) AS total_clientes,
                    (SELECT COUNT(*) FROM obligaciones o WHERE o.base_id = b.id_base) AS total_obligaciones
                FROM base_clientes b
                WHERE b.creado_por = ?";
        $params = [$coordinadorId];

        if ($soloHabilitadas) {
            $sql .= " AND b.estado = 'activo'";
        }

        $sql .= " ORDER BY b.fecha_actualizacion DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Cambia el estado de una carga (habilitar/deshabilitar)
     */
    public function cambiarEstadoCarga($cargaId, $nuevoEstado) {
        // Nuevo esquema: base_clientes.estado enum('activo','inactivo')
        $sql = "UPDATE base_clientes SET estado = ? WHERE id_base = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$nuevoEstado, $cargaId]);
    }

    /**
     * Busca cargas por nombre con filtro por estado
     */
    public function buscarCargasPorNombre($coordinadorId, $terminoBusqueda, $soloHabilitadas = true) {
        $sql = "SELECT
                    b.id_base AS id,
                    b.id_base AS id_base,
                    b.nombre AS nombre_cargue,
                    b.nombre AS nombre,
                    b.creado_por AS usuario_coordinador_id,
                    b.creado_por AS creado_por,
                    b.fecha_actualizacion AS fecha_cargue,
                    b.fecha_actualizacion AS fecha_actualizacion,
                    b.estado AS estado_habilitado,
                    b.estado AS estado,
                    (SELECT COUNT(*) FROM clientes c WHERE c.base_id = b.id_base) AS total_clientes,
                    (SELECT COUNT(*) FROM obligaciones o WHERE o.base_id = b.id_base) AS total_obligaciones
                FROM base_clientes b
                WHERE b.creado_por = ?
                  AND b.nombre LIKE ?";
        $params = [$coordinadorId, '%' . $terminoBusqueda . '%'];

        if ($soloHabilitadas) {
            $sql .= " AND b.estado = 'activo'";
        }

        $sql .= " ORDER BY b.fecha_actualizacion DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Busca clientes por número de teléfono
     * @param string $telefono El número de teléfono a buscar
     * @return array Lista de clientes encontrados
     */
    public function buscarPorTelefono($telefono) {
        // En el esquema actual los teléfonos viven en tel1..tel10.
        $termLike = '%' . trim((string)$telefono) . '%';
        $sql = "SELECT " . $this->selectClienteCompatFields() . "
                FROM clientes c
                WHERE c.tel1 LIKE ? OR c.tel2 LIKE ? OR c.tel3 LIKE ? OR c.tel4 LIKE ? OR c.tel5 LIKE ?
                   OR c.tel6 LIKE ? OR c.tel7 LIKE ? OR c.tel8 LIKE ? OR c.tel9 LIKE ? OR c.tel10 LIKE ?
                ORDER BY c.nombre ASC
                LIMIT 20";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$termLike,$termLike,$termLike,$termLike,$termLike,$termLike,$termLike,$termLike,$termLike,$termLike]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Busca clientes por cédula de identidad
     * @param string $cedula La cédula a buscar
     * @return array Lista de clientes encontrados
     */
    public function buscarPorCedula($cedula) {
        $termLike = '%' . trim((string)$cedula) . '%';
        $sql = "SELECT " . $this->selectClienteCompatFields() . "
                FROM clientes c
                WHERE c.cedula LIKE ?
                ORDER BY c.nombre ASC
                LIMIT 20";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$termLike]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Crea una asignación temporal para un cliente cuando se gestiona desde bases asignadas
     * @param int $asesorId ID del asesor
     * @param int $clienteId ID del cliente
     * @return int ID de la asignación creada
     */
    public function createTemporaryAsignacion($asesorId, $clienteId) {
        $this->pdo->beginTransaction();
        try {
            // Crear registro en asignaciones_clientes
            $sql = "INSERT INTO asignaciones_clientes (asesor_id, cliente_id, estado, fecha_asignacion) 
                    VALUES (?, ?, 'asignado', NOW())";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$asesorId, $clienteId]);
            
            $asignacionId = $this->pdo->lastInsertId();
            
            // Actualizar la tabla clientes para asignar el asesor
            $sql = "UPDATE clientes SET asesor_id = ? WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$asesorId, $clienteId]);
            
            $this->pdo->commit();
            return $asignacionId;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Error al crear asignación temporal: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtiene una asignación por su ID
     */
    public function getAsignacionById($asignacionId) {
        try {
            $sql = "SELECT * FROM asignaciones_clientes WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$asignacionId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error obteniendo asignación por ID: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Actualiza información de un cliente
     */
    public function updateCliente($datos) {
        try {
            $id = $datos['id'];
            unset($datos['id']); // Remover id de los datos a actualizar
            
            if (empty($datos)) {
                return true; // No hay nada que actualizar
            }
            
            $campos = [];
            $valores = [];
            
            foreach ($datos as $campo => $valor) {
                $campos[] = "$campo = ?";
                $valores[] = $valor;
            }
            
            $valores[] = $id; // Agregar ID al final para el WHERE
            
            $sql = "UPDATE clientes SET " . implode(', ', $campos) . " WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            
            return $stmt->execute($valores);
        } catch (Exception $e) {
            error_log("Error actualizando cliente: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtener múltiples clientes por cédulas y carga_excel_id en una sola consulta (BULK)
     * Optimizado para verificar existencia de múltiples clientes
     * 
     * @param array $cedulas Array de cédulas
     * @param int $cargaId ID de la carga
     * @return array Array asociativo ['cedula' => cliente_data]
     */
    public function getClientesByCedulasYCarga($cedulas, $cargaId) {
        if (empty($cedulas)) {
            return [];
        }
        
        // Construir condiciones IN para múltiples cédulas
        $placeholders = implode(',', array_fill(0, count($cedulas), '?'));
        $sql = "SELECT * FROM clientes WHERE cedula IN ($placeholders) AND carga_excel_id = ?";
        
        $params = array_merge($cedulas, [$cargaId]);
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $indexados = [];
        
        foreach ($resultados as $cliente) {
            $indexados[$cliente['cedula']] = $cliente;
        }
        
        return $indexados;
    }
    
    /**
     * Crear múltiples clientes en una sola operación (BULK INSERT)
     * Optimizado para cargas masivas de hasta 1 millón de registros
     * 
     * @param array $clientes Array de arrays con datos de clientes
     * @return array Array de IDs de clientes creados
     */
    public function crearClientesBulk($clientes) {
        if (empty($clientes)) {
            return [];
        }
        
        // Construir query de INSERT múltiple
        $sql = "INSERT INTO clientes (cedula, nombre, telefono, celular2, email, direccion, ciudad, carga_excel_id, otros_datos_json, fecha_creacion, fecha_actualizacion) VALUES ";
        $values = [];
        $params = [];
        
        foreach ($clientes as $cliente) {
            $values[] = "(?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
            $params[] = $cliente['cedula'] ?? null;
            $params[] = $cliente['nombre'] ?? null;
            $params[] = $cliente['telefono'] ?? null;
            $params[] = $cliente['celular2'] ?? null;
            $params[] = $cliente['email'] ?? null;
            $params[] = $cliente['direccion'] ?? null;
            $params[] = $cliente['ciudad'] ?? null;
            $params[] = $cliente['carga_excel_id'] ?? null;
            $params[] = $cliente['otros_datos_json'] ?? null;
        }
        
        $sql .= implode(', ', $values);
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute($params);
            
            if (!$result) {
                $errorInfo = $stmt->errorInfo();
                error_log("Error en crearClientesBulk execute: " . print_r($errorInfo, true));
                throw new PDOException("Error ejecutando bulk insert: " . $errorInfo[2]);
            }
            
            // Obtener los IDs creados
            $primerId = $this->pdo->lastInsertId();
            $idsCreados = [];
            
            for ($i = 0; $i < count($clientes); $i++) {
                $idsCreados[] = $primerId + $i;
            }
            
            return $idsCreados;
        } catch (PDOException $e) {
            error_log("Error en crearClientesBulk: " . $e->getMessage());
            error_log("SQL (primeros 1000 chars): " . substr($sql, 0, 1000));
            throw $e;
        }
    }
    
}
?>