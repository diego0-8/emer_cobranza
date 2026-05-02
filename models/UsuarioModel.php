<?php  
class UsuarioModel {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Normaliza un registro de la tabla `usuarios` del dump para que coincida con
     * las claves que esperan las vistas/controladores actuales (sin tocar vistas).
     */
    private function mapUsuarioRow(?array $row): ?array {
        if (!$row) {
            return null;
        }

        $rolDb = $row['rol'] ?? '';
        $rolUi = $rolDb === 'cordinador' ? 'coordinador' : $rolDb;

        $estadoDb = $row['estado'] ?? '';
        $estadoUi = $estadoDb === 'activo' ? 'Activo' : ($estadoDb === 'inactivo' ? 'Inactivo' : $estadoDb);

        $extension = (string)($row['estension'] ?? '');
        $clave = (string)($row['sip_password'] ?? '');
        $telefonoActivo = ($extension !== '' && $clave !== '') ? 'Si' : 'No';

        return [
            // Alias para compatibilidad con vistas (antes era int autoincrement).
            'id' => $row['cedula'] ?? null,
            'cedula' => $row['cedula'] ?? null,
            'nombre_completo' => $row['nombre'] ?? null,
            'nombre' => $row['nombre'] ?? null,
            'usuario' => $row['usuario'] ?? null,
            'rol' => $rolUi,
            'rol_db' => $rolDb,
            'estado' => $estadoUi,
            'estado_db' => $estadoDb,
            'fecha_creacion' => $row['fecha_creacion'] ?? null,
            'fecha_actualizacion' => $row['fecha_actualizacion'] ?? null,

            // Compatibilidad con pantalla de teléfono
            'extension_telefono' => $extension,
            'clave_webrtc' => $clave,
            'telefono_activo' => $telefonoActivo,

            // Campo original para autenticación
            'contrasena_hash' => $row['contrasena_hash'] ?? null,
        ];
    }

    private function normalizarRolParaDB(string $rolUi): string {
        $rolUi = trim($rolUi);
        return $rolUi === 'coordinador' ? 'cordinador' : $rolUi;
    }

    private function normalizarEstadoParaDB(string $estadoUi): string {
        $estadoUi = trim($estadoUi);
        if ($estadoUi === 'Activo') return 'activo';
        if ($estadoUi === 'Inactivo') return 'inactivo';
        // Si ya viene en minúsculas o en otro formato, conservar.
        return $estadoUi;
    }

    public function getAllUsuarios() {
        $stmt = $this->pdo->query("SELECT * FROM usuarios ORDER BY rol, nombre");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_values(array_filter(array_map([$this, 'mapUsuarioRow'], $rows)));
    }
    
    public function getUsuariosWithFilters($search = '', $rol_filter = '', $estado_filter = '') {
        $sql = "SELECT * FROM usuarios WHERE 1=1";
        $params = [];
        
        if (!empty($search)) {
            $sql .= " AND (nombre LIKE ? OR usuario LIKE ? OR cedula LIKE ?)";
            $searchTerm = "%$search%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        if (!empty($rol_filter)) {
            $sql .= " AND rol = ?";
            $params[] = $this->normalizarRolParaDB($rol_filter);
        }
        
        if (!empty($estado_filter)) {
            $sql .= " AND estado = ?";
            $params[] = $this->normalizarEstadoParaDB($estado_filter);
        }
        
        $sql .= " ORDER BY rol, nombre";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_values(array_filter(array_map([$this, 'mapUsuarioRow'], $rows)));
    }
    
    public function getUsuariosByRol($rol) {
        $rolDb = $this->normalizarRolParaDB((string)$rol);
        $stmt = $this->pdo->prepare("SELECT * FROM usuarios WHERE rol = ? AND estado = 'activo' ORDER BY nombre");
        $stmt->execute([$rolDb]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_values(array_filter(array_map([$this, 'mapUsuarioRow'], $rows)));
    }

    public function getUsuarioById($id) {
        // En el dump, el identificador es `cedula` (varchar).
        $stmt = $this->pdo->prepare("SELECT * FROM usuarios WHERE cedula = ? LIMIT 1");
        $stmt->execute([(string)$id]);
        return $this->mapUsuarioRow($stmt->fetch(PDO::FETCH_ASSOC));
    }
    
    /**
     * Obtiene los datos de teléfono de un usuario
     */
    public function getDatosTelefono($userId) {
        $stmt = $this->pdo->prepare("SELECT estension, sip_password FROM usuarios WHERE cedula = ? LIMIT 1");
        $stmt->execute([(string)$userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $extension = (string)($row['estension'] ?? '');
        $clave = (string)($row['sip_password'] ?? '');
        return [
            'extension_telefono' => $extension,
            'clave_webrtc' => $clave,
            'telefono_activo' => ($extension !== '' && $clave !== '') ? 'Si' : 'No'
        ];
    }
    
    /**
     * Verifica si un usuario tiene teléfono configurado
     */
    public function tieneTelefonoConfigurado($userId) {
        $datos = $this->getDatosTelefono($userId);
        return $datos && $datos['telefono_activo'] === 'Si' && 
               !empty($datos['extension_telefono']) && 
               !empty($datos['clave_webrtc']);
    }

    public function authenticateUser($usuario, $contrasena) {
        try {
            $agentLogPath = __DIR__ . '/../debug-a2fdce.log';
            $stmt = $this->pdo->prepare("SELECT * FROM usuarios WHERE usuario = ? AND estado = 'activo' LIMIT 1");
            $stmt->execute([$usuario]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // #region agent log
            @file_put_contents($agentLogPath, json_encode([
                'sessionId' => 'a2fdce',
                'runId' => 'pre-fix',
                'hypothesisId' => 'L2',
                'location' => 'models/UsuarioModel.php:authenticateUser',
                'message' => 'User lookup result',
                'data' => [
                    'usuarioLen' => strlen((string)$usuario),
                    'found' => $user ? true : false,
                    'estadoDb' => $user ? ($user['estado'] ?? null) : null,
                    'hashLen' => $user ? strlen((string)($user['contrasena_hash'] ?? '')) : 0,
                    'hashPrefix' => $user ? substr((string)($user['contrasena_hash'] ?? ''), 0, 4) : null,
                ],
                'timestamp' => (int) round(microtime(true) * 1000),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n", FILE_APPEND);
            // #endregion

            // #region agent log
            error_log('[AGENTLOG a2fdce L2] lookup found=' . ($user ? '1' : '0') . ' estadoDb=' . ($user['estado'] ?? 'null') . ' hashLen=' . ($user ? strlen((string)($user['contrasena_hash'] ?? '')) : 0));
            // #endregion
            
            if ($user) {
                $hash = (string)($user['contrasena_hash'] ?? '');
                $verified = ($hash !== '' && password_verify((string)$contrasena, $hash));

                // #region agent log
                @file_put_contents($agentLogPath, json_encode([
                    'sessionId' => 'a2fdce',
                    'runId' => 'pre-fix',
                    'hypothesisId' => 'L2',
                    'location' => 'models/UsuarioModel.php:authenticateUser',
                    'message' => 'password_verify result',
                    'data' => [
                        'verified' => $verified,
                        'hashLooksBcrypt' => (strpos($hash, '$2y$') === 0) || (strpos($hash, '$2a$') === 0) || (strpos($hash, '$2b$') === 0),
                    ],
                    'timestamp' => (int) round(microtime(true) * 1000),
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n", FILE_APPEND);
                // #endregion

                // #region agent log
                error_log('[AGENTLOG a2fdce L2] password_verify verified=' . ($verified ? '1' : '0') . ' hashLooksBcrypt=' . (((strpos($hash, '$2y$') === 0) || (strpos($hash, '$2a$') === 0) || (strpos($hash, '$2b$') === 0)) ? '1' : '0'));
                // #endregion

                if ($verified) {
                    return $this->mapUsuarioRow($user);
                }
            }
            
            return false;
        } catch (PDOException $e) {
            error_log("Error en autenticación: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verifica si un usuario existe (sin verificar contraseña)
     */
    public function checkUserExists($usuario) {
        try {
            $stmt = $this->pdo->prepare("SELECT cedula, usuario, estado FROM usuarios WHERE usuario = ? LIMIT 1");
            $stmt->execute([$usuario]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) return false;
            return [
                'id' => $row['cedula'],
                'usuario' => $row['usuario'],
                'estado' => ($row['estado'] ?? '') === 'activo' ? 'Activo' : 'Inactivo'
            ];
        } catch (PDOException $e) {
            error_log("Error verificando usuario: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verifica la contraseña de un usuario por su ID
     */
    public function verificarContrasena($userId, $contrasena) {
        try {
            $stmt = $this->pdo->prepare("SELECT contrasena_hash FROM usuarios WHERE cedula = ? AND estado = 'activo' LIMIT 1");
            $stmt->execute([(string)$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                return password_verify((string)$contrasena, (string)($user['contrasena_hash'] ?? ''));
            }
            
            return false;
        } catch (PDOException $e) {
            error_log("Error en verificarContrasena: " . $e->getMessage());
            return false;
        }
    }

    public function createUsuario($data) {
        try {
            if (empty($data['nombre_completo']) || empty($data['cedula']) || empty($data['usuario']) || empty($data['contrasena']) || empty($data['rol'])) {
                throw new Exception('Faltan campos obligatorios para crear el usuario.');
            }

            $sql = "INSERT INTO usuarios (cedula, nombre, usuario, contrasena_hash, estension, sip_password, estado, rol)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->pdo->prepare($sql);

            $cedula = (string)$data['cedula'];
            $nombre = (string)$data['nombre_completo'];
            $usuario = (string)$data['usuario'];
            $hash = password_hash((string)$data['contrasena'], PASSWORD_DEFAULT);
            $extension = (string)($data['extension_telefono'] ?? '');
            $clave = (string)($data['clave_webrtc'] ?? '');
            $estadoDb = $this->normalizarEstadoParaDB((string)($data['estado'] ?? 'Activo'));
            $rolDb = $this->normalizarRolParaDB((string)$data['rol']);

            $ok = $stmt->execute([$cedula, $nombre, $usuario, $hash, $extension, $clave, $estadoDb, $rolDb]);
            return $ok ? $cedula : false;
        } catch (Exception $e) {
            error_log("Error en createUsuario: " . $e->getMessage());
            return false;
        }
    }

    public function updateUsuario($id, $data) {
        try {
            // En el dump no se debe cambiar la PK `cedula` sin un proceso de migración.
            // Mantendremos la fila identificada por $id (cedula original).
            $sql = "UPDATE usuarios SET nombre = ?, usuario = ?, rol = ?, estado = ?, estension = ?, sip_password = ?";
            $params = [
                (string)($data['nombre_completo'] ?? ''),
                (string)($data['usuario'] ?? ''),
                $this->normalizarRolParaDB((string)($data['rol'] ?? 'asesor')),
                $this->normalizarEstadoParaDB((string)($data['estado'] ?? 'Activo')),
                (string)($data['extension_telefono'] ?? ''),
                (string)($data['clave_webrtc'] ?? '')
            ];
            
            // Si se proporciona una nueva contraseña, incluirla en la actualización
            if (!empty($data['contrasena'])) {
                $sql .= ", contrasena_hash = ?";
                $params[] = password_hash((string)$data['contrasena'], PASSWORD_DEFAULT);
            }

            $sql .= " WHERE cedula = ?";
            $params[] = (string)$id;
            
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($params);
        } catch (Exception $e) {
            error_log("Error en updateUsuario: " . $e->getMessage());
            return false;
        }
    }

    public function toggleEstadoUsuario($id) {
        $usuario = $this->getUsuarioById($id);
        if ($usuario) {
            $nuevoEstadoUi = ($usuario['estado'] === 'Activo') ? 'Inactivo' : 'Activo';
            $nuevoEstadoDb = $this->normalizarEstadoParaDB($nuevoEstadoUi);
            $sql = "UPDATE usuarios SET estado = ? WHERE cedula = ?";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([$nuevoEstadoDb, (string)$id]);
        }
        return false;
    }

    public function getAsesores() {
        $stmt = $this->pdo->prepare("SELECT * FROM usuarios WHERE rol = 'asesor' AND estado = 'activo' ORDER BY nombre");
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_values(array_filter(array_map([$this, 'mapUsuarioRow'], $rows)));
    }
    
    /**
     * Obtiene los asesores asignados a un coordinador específico
     */
    public function getAsesoresByCoordinador($coordinador_id) {
        // Tabla real del dump: asignaciones_cordinador (con cédulas).
        $sql = "SELECT u.*, ac.fecha_asignacion, ac.estado as estado_asignacion
                FROM usuarios u
                JOIN asignaciones_cordinador ac ON u.cedula = ac.asesor_cedula
                WHERE ac.cordinador_cedula = ? AND ac.estado = 'activo' AND u.estado = 'activo'
                ORDER BY ac.fecha_asignacion DESC, u.nombre";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([(string)$coordinador_id]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $asesores = array_values(array_filter(array_map([$this, 'mapUsuarioRow'], $rows)));
        
        // NO devolver todos los asesores si no hay asignaciones
        // Solo devolver los que estén formalmente asignados
        return $asesores;
    }
    
    /**
     * Obtiene solo los asesores que NO están asignados a ningún coordinador
     */
    public function getAsesoresDisponibles() {
        $sql = "SELECT u.* FROM usuarios u
                WHERE u.rol = 'asesor' AND u.estado = 'activo'
                AND u.cedula NOT IN (
                    SELECT DISTINCT ac.asesor_cedula
                    FROM asignaciones_cordinador ac
                    WHERE ac.estado = 'activo'
                )
                ORDER BY u.nombre";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_values(array_filter(array_map([$this, 'mapUsuarioRow'], $rows)));
    }
    
    /**
     * Asigna un asesor a un coordinador
     * IMPORTANTE: Desactiva automáticamente cualquier asignación previa del asesor
     */
    public function asignarAsesorACoordinador($asesorId, $coordinadorId) {
        try {
            // 1. Desactivar cualquier asignación previa activa del asesor.
            $stmt = $this->pdo->prepare("UPDATE asignaciones_cordinador SET estado = 'inactivo' WHERE asesor_cedula = ?");
            $stmt->execute([(string)$asesorId]);

            // 2. Si ya existe relación con este coordinador, reactivar; si no, crear fila.
            $stmt = $this->pdo->prepare("SELECT id_asignacion FROM asignaciones_cordinador WHERE asesor_cedula = ? AND cordinador_cedula = ? LIMIT 1");
            $stmt->execute([(string)$asesorId, (string)$coordinadorId]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                $stmt = $this->pdo->prepare("UPDATE asignaciones_cordinador SET estado = 'activo', fecha_asignacion = NOW() WHERE id_asignacion = ?");
                return $stmt->execute([(int)$existing['id_asignacion']]);
            }

            $stmt = $this->pdo->prepare("INSERT INTO asignaciones_cordinador (cordinador_cedula, asesor_cedula, estado, fecha_asignacion, fecha_creacion)
                                          VALUES (?, ?, 'activo', NOW(), NOW())");
            return $stmt->execute([(string)$coordinadorId, (string)$asesorId]);
        } catch (Exception $e) {
            error_log("Error en asignarAsesorACoordinador: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Libera un asesor de un coordinador
     */
    public function liberarAsesorDeCoordinador($asesorId, $coordinadorId) {
        $sql = "UPDATE asignaciones_cordinador
                SET estado = 'inactivo'
                WHERE asesor_cedula = ? AND cordinador_cedula = ? AND estado = 'activo'";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([(string)$asesorId, (string)$coordinadorId]);
    }
    
    public function getCoordinadores() {
        $stmt = $this->pdo->prepare("SELECT * FROM usuarios WHERE rol = 'cordinador' AND estado = 'activo' ORDER BY nombre");
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_values(array_filter(array_map([$this, 'mapUsuarioRow'], $rows)));
    }

    /**
     * Verifica si un asesor está asignado a un coordinador específico
     */
    public function isAsesorAsignadoACoordinador($asesorId, $coordinadorId) {
        $sql = "SELECT id_asignacion FROM asignaciones_cordinador 
                WHERE asesor_cedula = ? AND cordinador_cedula = ? AND estado = 'activo'";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([(string)$asesorId, (string)$coordinadorId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result !== false;
    }
    
    /**
     * Verifica si un coordinador tiene asesores asignados
     */
    public function tieneCoordinadorAsesoresAsignados($coordinadorId) {
        $sql = "SELECT COUNT(*) as total FROM asignaciones_cordinador 
                WHERE cordinador_cedula = ? AND estado = 'activo'";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([(string)$coordinadorId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] > 0;
    }

    /**
     * Obtiene el total de usuarios
     */
    public function getTotalUsuarios() {
        $stmt = $this->pdo->query("SELECT COUNT(*) as total FROM usuarios");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'];
    }

    /**
     * Obtiene el total de usuarios por rol
     */
    public function getTotalUsuariosByRol($rol) {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) as total FROM usuarios WHERE rol = ?");
        $stmt->execute([$this->normalizarRolParaDB((string)$rol)]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'];
    }

    /**
     * Obtiene el total de usuarios por estado
     */
    public function getTotalUsuariosByEstado($estado) {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) as total FROM usuarios WHERE estado = ?");
        $stmt->execute([$this->normalizarEstadoParaDB((string)$estado)]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'];
    }

    /**
     * Obtiene usuarios recientes
     */
    public function getUsuariosRecientes($limit = 10) {
        $limit = (int)$limit; // Asegurar que sea un entero
        $stmt = $this->pdo->prepare("
            SELECT * FROM usuarios 
            ORDER BY fecha_creacion DESC 
            LIMIT $limit
        ");
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_values(array_filter(array_map([$this, 'mapUsuarioRow'], $rows)));
    }


    /**
     * Obtiene todas las asignaciones asesor-coordinador
     */
    public function getAsignacionesAsesorCoordinador() {
        $stmt = $this->pdo->query("
            SELECT 
                ac.*,
                u_asesor.nombre as asesor_nombre,
                u_coordinador.nombre as coordinador_nombre
            FROM asignaciones_cordinador ac
            LEFT JOIN usuarios u_asesor ON ac.asesor_cedula = u_asesor.cedula
            LEFT JOIN usuarios u_coordinador ON ac.cordinador_cedula = u_coordinador.cedula
            ORDER BY ac.fecha_asignacion DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Toggle del estado de un usuario
     */
    public function toggleUsuario($id) {
        $sql = "UPDATE usuarios SET estado = CASE WHEN estado = 'activo' THEN 'inactivo' ELSE 'activo' END WHERE cedula = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([(string)$id]);
    }
}
?>
