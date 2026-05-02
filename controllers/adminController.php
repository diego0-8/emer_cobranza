<?php 
require_once __DIR__ . '/BaseController.php';

class AdminController extends BaseController {
    public function __construct($pdo) {
        parent::__construct($pdo);
    }
    
    // Nota: La nueva BD usa `tiempos` para sesiones/breaks. La persistencia de sesión
    // en BD se migrará a ese esquema; por ahora la sesión se maneja solo en PHP.

    public function login() {
        $page_title = "Emermedica Cobranza";
        $error = '';
        $success = '';
        
        // Si ya está logueado, redirigir al dashboard
        if (isset($_SESSION['user_id'])) {
            header('Location: index.php?action=dashboard');
            exit;
        }
        
        // Obtener error de la sesión si existe
        if (isset($_SESSION['login_error'])) {
            $error = $_SESSION['login_error'];
            unset($_SESSION['login_error']);
        }
        
        // Solo mostrar el formulario de login (GET)
        require __DIR__ . '/../views/login_form.php';
    }
    
    public function processLogin() {
        // Si ya está logueado, redirigir al dashboard
        if (isset($_SESSION['user_id'])) {
            header('Location: index.php?action=dashboard');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $usuario = trim($_POST['usuario'] ?? '');
            $contrasena = trim($_POST['contrasena'] ?? '');

            $agentLogPath = __DIR__ . '/../debug-a2fdce.log';
            // #region agent log
            @file_put_contents($agentLogPath, json_encode([
                'sessionId' => 'a2fdce',
                'runId' => 'pre-fix',
                'hypothesisId' => 'L1',
                'location' => 'controllers/adminController.php:processLogin',
                'message' => 'Login attempt received',
                'data' => [
                    'usuarioLen' => strlen((string)$usuario),
                    'contrasenaLen' => strlen((string)$contrasena),
                    'hasSessionAlready' => isset($_SESSION['user_id']),
                ],
                'timestamp' => (int) round(microtime(true) * 1000),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n", FILE_APPEND);
            // #endregion

            // #region agent log
            // Respaldo vía error_log (si Apache/PHP no permite escribir archivos en el CWD esperado)
            error_log('[AGENTLOG a2fdce L1] processLogin received usuarioLen=' . strlen((string)$usuario) . ' contrasenaLen=' . strlen((string)$contrasena));
            // #endregion
            
            // Validar que los campos no estén vacíos
            if (empty($usuario) || empty($contrasena)) {
                $error = "Por favor, completa todos los campos.";
                $debug_client_console = [
                    'stage' => 'validate',
                    'reason' => 'empty_fields',
                    'usuarioLen' => strlen((string)$usuario),
                    'contrasenaLen' => strlen((string)$contrasena),
                ];
            } else {
                // Intentar autenticar al usuario
                $user = $this->usuarioModel->authenticateUser($usuario, $contrasena);

                // #region agent log
                @file_put_contents($agentLogPath, json_encode([
                    'sessionId' => 'a2fdce',
                    'runId' => 'pre-fix',
                    'hypothesisId' => 'L1',
                    'location' => 'controllers/adminController.php:processLogin',
                    'message' => 'authenticateUser result',
                    'data' => [
                        'authenticated' => $user ? true : false,
                        'hasCedula' => is_array($user) && !empty($user['cedula']),
                        'role' => is_array($user) ? ($user['rol'] ?? null) : null,
                    ],
                    'timestamp' => (int) round(microtime(true) * 1000),
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n", FILE_APPEND);
                // #endregion

                // #region agent log
                error_log('[AGENTLOG a2fdce L1] authenticateUser authenticated=' . ($user ? '1' : '0') . ' hasCedula=' . ((is_array($user) && !empty($user['cedula'])) ? '1' : '0'));
                // #endregion
                
                if ($user) {
                    // Usuario autenticado correctamente
                    // Regenerar el ID de sesión para evitar fijación de sesión
                    if (session_status() === PHP_SESSION_ACTIVE) {
                        session_regenerate_id(true);
                    }
                    // En el nuevo esquema, el identificador es la cédula (varchar).
                    $_SESSION['user_id'] = $user['cedula'];
                    $_SESSION['user_role'] = $user['rol'];
                    $_SESSION['user_name'] = $user['nombre_completo'];
                    
                    // Establecer tiempo de inicio de sesión (timestamp Unix)
                    $login_time = time();
                    $_SESSION['login_time'] = $login_time;
                    
                    // Log de acceso exitoso
                    error_log("Login exitoso - Usuario: {$usuario}, Rol: {$user['rol']}, Cédula: {$user['cedula']}, Login time: {$login_time}");
                    
                    // Redirigir al dashboard correspondiente
                    header('Location: index.php?action=dashboard');
                    exit;
                } else {
                    // Verificar si el usuario existe pero la contraseña es incorrecta
                    $userExists = $this->usuarioModel->checkUserExists($usuario);
                    
                    if ($userExists) {
                        if ($userExists['estado'] === 'Inactivo') {
                            $error = "Tu cuenta está inactiva. Contacta al administrador.";
                            $debug_client_console = [
                                'stage' => 'auth',
                                'reason' => 'inactive_user',
                                'usuarioLen' => strlen((string)$usuario),
                            ];
                        } else {
                            $error = "Contraseña incorrecta. Verifica tu contraseña.";
                            $debug_client_console = [
                                'stage' => 'auth',
                                'reason' => 'bad_password_or_hash',
                                'usuarioLen' => strlen((string)$usuario),
                            ];
                        }
                    } else {
                        $error = "Usuario no encontrado. Verifica tu nombre de usuario.";
                        $debug_client_console = [
                            'stage' => 'auth',
                            'reason' => 'user_not_found',
                            'usuarioLen' => strlen((string)$usuario),
                        ];
                    }
                    
                    // Log de intento fallido
                    error_log("Login fallido - Usuario: {$usuario}, IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'desconocida'));
                }
            }
            
            // Si hay error, redirigir al login con el error en la sesión
            // #region agent log
            error_log('[AGENTLOG a2fdce L3] setting login_error len=' . strlen((string)$error));
            // #endregion
            // Renderizar el login directamente para que el usuario vea el error
            // incluso si hay problemas con cookies/sesión o redirects.
            $page_title = "Emermedica Cobranza";
            $success = '';
            require __DIR__ . '/../views/login_form.php';
            exit;
        } else {
            // Si no es POST, redirigir al login
            header('Location: index.php?action=login');
            exit;
        }
    }

    public function logout() {
        // Destruir sesión PHP
        session_destroy();
        session_start();
        
        // Limpiar variables de sesión
        $_SESSION = array();
        
        header('Location: index.php?action=login');
        exit;
    }

    public function dashboard() {
        $page_title = "Dashboard Administrador";
        $cargasActivas = $this->cargaExcelModel->getCargasActivas();
        $success_message = $_SESSION['success_message'] ?? null;
        $error_message = $_SESSION['error_message'] ?? null;
        $info_message = $_SESSION['info_message'] ?? null;
        $warning_message = $_SESSION['warning_message'] ?? null;

        unset(
            $_SESSION['success_message'],
            $_SESSION['error_message'],
            $_SESSION['info_message'],
            $_SESSION['warning_message']
        );

        require __DIR__ . '/../views/admin_dashboard.php';
    }

    public function procesarCargaBash() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirigirConError('index.php?action=dashboard', 'Método no permitido para la carga masiva.');
        }

        try {
            $agentLogPath = __DIR__ . '/../debug-d200d9.log';
            // #region agent log
            @file_put_contents($agentLogPath, json_encode([
                'sessionId' => 'd200d9',
                'runId' => 'pre-fix',
                'hypothesisId' => 'B1',
                'location' => 'controllers/adminController.php:procesarCargaBash',
                'message' => 'Inicio carga masiva bash',
                'data' => [
                    'hasFile' => isset($_FILES['archivo_bash']) ? 1 : 0,
                    'fileError' => (int)($_FILES['archivo_bash']['error'] ?? -999),
                    'fileName' => (string)($_FILES['archivo_bash']['name'] ?? ''),
                    'fileSize' => (int)($_FILES['archivo_bash']['size'] ?? -1),
                    'cargaId' => (int)($_POST['carga_id'] ?? 0),
                ],
                'timestamp' => (int) round(microtime(true) * 1000),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n", FILE_APPEND);
            // #endregion

            if (empty($_POST['carga_id']) || !is_numeric($_POST['carga_id'])) {
                throw new Exception('Debes seleccionar una base activa.');
            }

            if (!isset($_FILES['archivo_bash']) || $_FILES['archivo_bash']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('Debes adjuntar un archivo CSV válido.');
            }

            $extension = strtolower(pathinfo($_FILES['archivo_bash']['name'], PATHINFO_EXTENSION));
            if ($extension !== 'csv') {
                throw new Exception('Solo se permiten archivos con extensión CSV.');
            }

            $cargaId = (int) $_POST['carga_id'];
            $carga = $this->cargaExcelModel->getCargaById($cargaId);

            if (
                !$carga ||
                ($carga['estado'] ?? '') !== 'activa' ||
                ($carga['estado_habilitado'] ?? '') !== 'habilitado'
            ) {
                throw new Exception('La base seleccionada no está activa o no existe.');
            }

            $registros = $this->leerArchivoCargaBash($_FILES['archivo_bash']['tmp_name']);
            if (empty($registros)) {
                throw new Exception('El archivo no contiene registros válidos para importar.');
            }

            // #region agent log
            $primero = $registros[0] ?? [];
            @file_put_contents($agentLogPath, json_encode([
                'sessionId' => 'd200d9',
                'runId' => 'pre-fix',
                'hypothesisId' => 'B2',
                'location' => 'controllers/adminController.php:procesarCargaBash',
                'message' => 'CSV leído (muestra 1er registro)',
                'data' => [
                    'totalRegistros' => is_array($registros) ? count($registros) : -1,
                    'firstLinea' => (int)($primero['linea'] ?? 0),
                    'firstFecha' => (string)($primero['fecha_gestion'] ?? ''),
                    'firstAsesor' => (string)($primero['asesor'] ?? ''),
                    'firstCedula' => (string)($primero['cedula_cliente'] ?? ''),
                    'firstCanal' => (string)($primero['canal_contacto'] ?? ''),
                    'firstTipo' => (string)($primero['tipo_contacto'] ?? ''),
                    'firstResultado' => (string)($primero['resultado_contacto'] ?? ''),
                ],
                'timestamp' => (int) round(microtime(true) * 1000),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n", FILE_APPEND);
            // #endregion

            // Detectar CSV con columna "asesor" mal exportada (trae fecha/hora).
            $muestras = array_slice($registros, 0, 10);
            $matchesFecha = 0;
            $evaluadas = 0;
            foreach ($muestras as $m) {
                $aRaw = trim((string)($m['asesor'] ?? ''));
                $fRaw = trim((string)($m['fecha_gestion'] ?? ''));
                if ($aRaw === '' || $fRaw === '') continue;
                $aParsed = $this->parsearFechaFlexibleImportacion($aRaw);
                if ($aParsed === false) continue;
                $tsA = strtotime((string)$aParsed);
                $tsF = strtotime((string)$fRaw);
                if ($tsA === false || $tsF === false) continue;
                $evaluadas++;
                if (abs($tsA - $tsF) <= 120) {
                    $matchesFecha++;
                }
            }
            if ($evaluadas >= 3 && $matchesFecha / max(1, $evaluadas) >= 0.8) {
                // #region agent log
                @file_put_contents($agentLogPath, json_encode([
                    'sessionId' => 'd200d9',
                    'runId' => 'pre-fix',
                    'hypothesisId' => 'B5',
                    'location' => 'controllers/adminController.php:procesarCargaBash',
                    'message' => 'CSV inválido: columna asesor parece fecha',
                    'data' => [
                        'evaluadas' => (int)$evaluadas,
                        'matchesFecha' => (int)$matchesFecha,
                        'firstAsesor' => (string)($primero['asesor'] ?? ''),
                        'firstFecha' => (string)($primero['fecha_gestion'] ?? ''),
                    ],
                    'timestamp' => (int) round(microtime(true) * 1000),
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n", FILE_APPEND);
                // #endregion

                throw new Exception('El archivo CSV parece mal exportado: la columna "asesor" contiene fechas/horas. Debe contener el nombre/usuario o cédula del asesor.');
            }

            usort($registros, function ($a, $b) {
                return strcmp($a['fecha_gestion'], $b['fecha_gestion']);
            });

            $indiceAsesores = $this->construirIndiceAsesoresImportacion();
            $totalProcesados = 0;
            $totalImportados = 0;
            $totalErrores = 0;
            $errores = [];

            $this->pdo->beginTransaction();

            foreach ($registros as $indice => $registro) {
                $totalProcesados++;
                $savepoint = 'sp_carga_bash_' . $indice;
                $this->pdo->exec("SAVEPOINT {$savepoint}");

                try {
                    $asesor = $this->resolverAsesorImportacion($registro['asesor'], $indiceAsesores);
                    $cliente = $this->clienteModel->getClienteByCedulaYCarga($registro['cedula_cliente'], $cargaId);

                    if (!$cliente) {
                        throw new Exception('No se encontró un cliente con esa cédula en la base seleccionada.');
                    }

                    $factura = null;
                    $facturaReferencia = trim((string) ($registro['factura_a_gestionar'] ?? ''));
                    $facturaNormalizada = $this->normalizarTextoImportacion($facturaReferencia);

                    if ($facturaReferencia !== '' && !in_array($facturaNormalizada, ['ninguna', 'na', 'sin_factura'], true)) {
                        $clienteIdRes = (int) ($cliente['id'] ?? $cliente['id_cliente'] ?? 0);
                        $factura = $this->facturacionModel->getFacturaByNumeroAndCliente($facturaReferencia, $clienteIdRes);
                        if (!$factura) {
                            throw new Exception('No se encontró la factura indicada para la cédula y la base seleccionadas.');
                        }
                    }

                    $clienteIdRes = (int) ($cliente['id'] ?? $cliente['id_cliente'] ?? 0);
                    $obligacion = $factura;
                    if (!$obligacion) {
                        $obligacion = $this->facturacionModel->getPrimeraObligacionClienteEnBase($clienteIdRes, $cargaId);
                    }
                    if (!$obligacion) {
                        throw new Exception('El cliente no tiene obligaciones en la base seleccionada; no se puede registrar la gestión.');
                    }
                    $obligacionId = (int) ($obligacion['id_obligacion'] ?? 0);
                    if ($obligacionId <= 0) {
                        throw new Exception('Obligación inválida para el cliente en esta base.');
                    }

                    $observaciones = trim((string) ($registro['observaciones'] ?? ''));
                    if ($observaciones === '') {
                        $observaciones = 'Gestión importada desde carga masiva del administrador.';
                    }

                    $tipoContactoCsv = trim((string) ($registro['tipo_contacto'] ?? ''));
                    $resultadoContactoCsv = trim((string) ($registro['resultado_contacto'] ?? ''));
                    $razonEspecificaCsv = trim((string) ($registro['razon_especifica'] ?? ''));
                    $telefonoContacto = $this->normalizarTelefonoContactoImportacion($registro['telefono_contacto'] ?? '');
                    $formaContacto = $this->normalizarCanalContactoImportacion($registro['canal_contacto'] ?? '');

                    $tipoContacto = $tipoContactoCsv !== '' ? $tipoContactoCsv : 'contacto_exitoso';
                    $resultadoContacto = $resultadoContactoCsv !== '' ? $resultadoContactoCsv : 'localizado_sin_acuerdo';
                    $razonEspecifica = $razonEspecificaCsv !== '' ? $razonEspecificaCsv : 'no_informa';

                    $asesorCedula = (string) ($asesor['cedula'] ?? $asesor['id'] ?? '');

                    $gestionData = [
                        'asesor_cedula' => $asesorCedula,
                        'cliente_id' => $clienteIdRes,
                        'obligacion_id' => $obligacionId,
                        'tipo_contacto' => $tipoContacto,
                        'resultado_contacto' => $resultadoContacto,
                        'razon_especifica' => $razonEspecifica,
                        'forma_contacto' => $formaContacto,
                        'telefono_contacto' => $telefonoContacto !== null && $telefonoContacto !== '' ? $telefonoContacto : '',
                        'observaciones' => $observaciones,
                        // Usar la fecha del CSV como fecha_creacion en historial_gestiones.
                        'fecha_creacion' => $registro['fecha_gestion'] ?? null,
                        'fecha_acuerdo' => $registro['fecha_pago'] ?: null,
                        'monto_acuerdo' => $this->parsearValorMonetarioImportacion($registro['valor_cuota'] ?? ''),
                    ];

                    $gestionId = $this->gestionModel->crearGestion($gestionData);

                    $canalesAutorizados = $this->parsearCanalesAutorizadosImportacion($registro['canales_autorizados'] ?? '');
                    if (!empty($canalesAutorizados)) {
                        $this->gestionModel->guardarCanalesAutorizados($gestionId, $canalesAutorizados);
                    }

                    if ($factura) {
                        $facturaPk = (int) ($factura['id_obligacion'] ?? $factura['id'] ?? 0);
                        $this->actualizarFacturaImportada(
                            $facturaPk,
                            $factura['estado_factura'] ?? 'pendiente',
                            $resultadoContactoCsv,
                            $razonEspecificaCsv,
                            $registro['fecha_pago'] ?: null
                        );
                    }

                    $totalImportados++;
                } catch (Exception $e) {
                    $this->pdo->exec("ROLLBACK TO SAVEPOINT {$savepoint}");
                    $totalErrores++;
                    $errores[] = 'Línea ' . $registro['linea'] . ': ' . $e->getMessage();

                    // #region agent log
                    if ($totalErrores <= 5) {
                        @file_put_contents($agentLogPath, json_encode([
                            'sessionId' => 'd200d9',
                            'runId' => 'pre-fix',
                            'hypothesisId' => 'B3',
                            'location' => 'controllers/adminController.php:procesarCargaBash',
                            'message' => 'Fila con error (muestra primeras 5)',
                            'data' => [
                                'linea' => (int)($registro['linea'] ?? 0),
                                'asesor' => (string)($registro['asesor'] ?? ''),
                                'cedula' => (string)($registro['cedula_cliente'] ?? ''),
                                'tipo_contacto' => (string)($registro['tipo_contacto'] ?? ''),
                                'resultado_contacto' => (string)($registro['resultado_contacto'] ?? ''),
                                'error' => $e->getMessage(),
                            ],
                            'timestamp' => (int) round(microtime(true) * 1000),
                        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n", FILE_APPEND);
                    }
                    // #endregion
                }
            }

            // #region agent log
            @file_put_contents($agentLogPath, json_encode([
                'sessionId' => 'd200d9',
                'runId' => 'pre-fix',
                'hypothesisId' => 'B4',
                'location' => 'controllers/adminController.php:procesarCargaBash',
                'message' => 'Resumen carga masiva bash (antes de commit/rollback final)',
                'data' => [
                    'totalProcesados' => (int)$totalProcesados,
                    'totalImportados' => (int)$totalImportados,
                    'totalErrores' => (int)$totalErrores,
                    'erroresSample' => array_slice($errores, 0, 5),
                ],
                'timestamp' => (int) round(microtime(true) * 1000),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n", FILE_APPEND);
            // #endregion

            if ($totalImportados > 0) {
                $this->pdo->commit();

                $mensaje = "<strong>Carga masiva completada.</strong><br>" .
                    "Base: " . htmlspecialchars($carga['nombre_cargue']) . "<br>" .
                    "Registros leídos: {$totalProcesados}<br>" .
                    "Gestiones importadas: {$totalImportados}<br>" .
                    "Registros con error: {$totalErrores}";

                if (!empty($errores)) {
                    $mensaje .= "<br><br><strong>Primeros errores detectados:</strong><br>" .
                        nl2br(htmlspecialchars(implode("\n", array_slice($errores, 0, 5))));
                    $_SESSION['warning_message'] = $mensaje;
                } else {
                    $_SESSION['success_message'] = $mensaje;
                }
            } else {
                $this->pdo->rollBack();
                throw new Exception('No se importó ninguna gestión. Revisa el archivo y vuelve a intentarlo.');
            }
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            $_SESSION['error_message'] = $e->getMessage();
        }

        header('Location: index.php?action=dashboard');
        exit;
    }

    public function descargarPlantillaBash() {
        if (ob_get_level()) {
            ob_end_clean();
        }

        $filename = 'plantilla_carga_bash.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        $headers = [
            'fecha de gestion',
            'asesor',
            'cedula cliente',
            'telefono de contacto',
            'franja de cliente',
            'canal de contacto',
            'tipo de contacto',
            'resultado del contacto',
            'razón especifica',
            'fecha de pago',
            'valor de la cuota',
            'factura a gestionar',
            'observaciones',
            'canales autorizados'
        ];

        $ejemplo = [
            '2026-03-09 08:30:00',
            'ASESOR EJEMPLO',
            '123456789',
            '3001234567',
            'BLOQUEADO',
            'llamada',
            'contacto_exitoso',
            'acuerdo_pago',
            'no_informa',
            '2026-03-15',
            '250000',
            'FAC-10001',
            'Gestión cargada desde plantilla',
            'llamada,whatsapp'
        ];

        fputcsv($output, $headers, ';');
        fputcsv($output, $ejemplo, ';');
        fclose($output);
        exit;
    }

    private function leerArchivoCargaBash($archivoPath) {
        $registros = [];

        if (($handle = fopen($archivoPath, 'r')) === false) {
            throw new Exception('No se pudo leer el archivo CSV.');
        }

        $primeraLinea = fgets($handle);
        if ($primeraLinea === false) {
            fclose($handle);
            throw new Exception('El archivo CSV está vacío.');
        }

        $primeraLinea = $this->convertirTextoAUtf8Importacion($primeraLinea);
        $delimitador = $this->detectarDelimitadorImportacion($primeraLinea);
        rewind($handle);

        $encabezados = fgetcsv($handle, 0, $delimitador);
        if (!$encabezados) {
            fclose($handle);
            throw new Exception('No fue posible leer los encabezados del archivo CSV.');
        }

        $encabezados = array_map([$this, 'convertirTextoAUtf8Importacion'], $encabezados);

        $indices = $this->mapearEncabezadosCargaBash($encabezados);
        $linea = 2;

        while (($fila = fgetcsv($handle, 0, $delimitador)) !== false) {
            $fila = array_map([$this, 'convertirTextoAUtf8Importacion'], $fila);

            if (count(array_filter($fila, function ($valor) {
                return trim((string) $valor) !== '';
            })) === 0) {
                $linea++;
                continue;
            }

            $fechaGestion = $this->parsearFechaFlexibleImportacion($fila[$indices['fecha_gestion']] ?? '');
            if (!$fechaGestion) {
                fclose($handle);
                throw new Exception("La fecha de gestión de la línea {$linea} no tiene un formato válido.");
            }

            $cedulaCliente = trim((string) ($fila[$indices['cedula_cliente']] ?? ''));
            $asesor = trim((string) ($fila[$indices['asesor']] ?? ''));
            $tipoContacto = trim((string) ($fila[$indices['tipo_contacto']] ?? ''));
            $resultadoContacto = trim((string) ($fila[$indices['resultado_contacto']] ?? ''));

            if ($cedulaCliente === '' || $asesor === '' || $tipoContacto === '' || $resultadoContacto === '') {
                fclose($handle);
                throw new Exception("La línea {$linea} no contiene los campos mínimos obligatorios (fecha, asesor, cédula, tipo de contacto, resultado).");
            }

            $idxNombreBase = $indices['nombre_base'] ?? null;
            $registros[] = [
                'linea' => $linea,
                'fecha_gestion' => $fechaGestion,
                'asesor' => $asesor,
                'cedula_cliente' => $cedulaCliente,
                'nombre_base' => $idxNombreBase !== null ? trim((string) ($fila[$idxNombreBase] ?? '')) : '',
                'telefono_contacto' => trim((string) ($fila[$indices['telefono_contacto']] ?? '')),
                'franja_cliente' => trim((string) ($fila[$indices['franja_cliente']] ?? '')),
                'canal_contacto' => trim((string) ($fila[$indices['canal_contacto']] ?? '')),
                'tipo_contacto' => $tipoContacto,
                'resultado_contacto' => $resultadoContacto,
                'razon_especifica' => trim((string) ($fila[$indices['razon_especifica']] ?? '')),
                'fecha_pago' => $this->parsearFechaFlexibleImportacion($fila[$indices['fecha_pago']] ?? '', true),
                'valor_cuota' => trim((string) ($fila[$indices['valor_cuota']] ?? '')),
                'factura_a_gestionar' => trim((string) ($fila[$indices['factura_a_gestionar']] ?? '')),
                'observaciones' => trim((string) ($fila[$indices['observaciones']] ?? '')),
                'canales_autorizados' => trim((string) ($fila[$indices['canales_autorizados']] ?? ''))
            ];

            $linea++;
        }

        fclose($handle);
        return $registros;
    }

    private function detectarDelimitadorImportacion($linea) {
        $delimitadores = [';' => substr_count($linea, ';'), ',' => substr_count($linea, ','), "\t" => substr_count($linea, "\t")];
        arsort($delimitadores);
        return (string) key($delimitadores);
    }

    private function mapearEncabezadosCargaBash($encabezados) {
        $mapa = [];

        foreach ($encabezados as $indice => $encabezado) {
            $normalizado = $this->normalizarTextoImportacion($encabezado);

            $aliases = [
                'fecha_gestion' => ['fecha_de_gestion', 'fecha_gestion'],
                'asesor' => ['asesor'],
                'cedula_cliente' => ['cedula_cliente', 'cedula_del_cliente'],
                'nombre_base' => ['nombre_de_la_base', 'nombre_base'],
                'telefono_contacto' => ['telefono_de_contacto', 'telefono_contacto'],
                'franja_cliente' => ['franja_de_cliente', 'franja_de_clietne', 'franja_del_cliente', 'franja_cliente'],
                'canal_contacto' => ['canal_de_contacto', 'canal_contacto'],
                'tipo_contacto' => ['tipo_de_contacto', 'tipo_contacto'],
                'resultado_contacto' => ['resultado_del_contacto', 'resultado_contacto'],
                'razon_especifica' => ['razon_especifica', 'razon_especifica_'],
                'fecha_pago' => ['fecha_de_pago', 'fecha_pago'],
                'valor_cuota' => ['valor_de_la_cuota', 'valor_cuota', 'valor_cuota_mensual'],
                'factura_a_gestionar' => ['factura_a_gestionar'],
                'observaciones' => ['observaciones'],
                'canales_autorizados' => ['canales_autorizados']
            ];

            foreach ($aliases as $campo => $posibles) {
                if (in_array($normalizado, $posibles, true)) {
                    $mapa[$campo] = $indice;
                    break;
                }
            }
        }

        $requeridos = [
            'fecha_gestion',
            'asesor',
            'cedula_cliente',
            'telefono_contacto',
            'franja_cliente',
            'canal_contacto',
            'tipo_contacto',
            'resultado_contacto',
            'razon_especifica',
            'fecha_pago',
            'valor_cuota',
            'factura_a_gestionar',
            'observaciones',
            'canales_autorizados'
        ];

        $faltantes = array_diff($requeridos, array_keys($mapa));
        if (!empty($faltantes)) {
            throw new Exception('Faltan columnas requeridas en el CSV: ' . implode(', ', $faltantes));
        }

        return $mapa;
    }

    private function construirIndiceAsesoresImportacion() {
        $stmt = $this->pdo->query("
            SELECT u.cedula AS id, u.cedula, u.nombre AS nombre_completo, u.usuario, u.estado
            FROM usuarios u
            WHERE u.rol = 'asesor' AND u.estado = 'activo'
        ");
        $asesores = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $indice = [];

        foreach ($asesores as $asesor) {
            $claves = [
                $this->normalizarTextoImportacion($asesor['nombre_completo'] ?? ''),
                $this->normalizarTextoImportacion($asesor['usuario'] ?? ''),
                $this->normalizarTextoImportacion((string) ($asesor['cedula'] ?? '')),
            ];

            foreach ($claves as $clave) {
                if ($clave === '') {
                    continue;
                }
                if (!isset($indice[$clave])) {
                    $indice[$clave] = [];
                }
                $indice[$clave][] = $asesor;
            }
        }

        return $indice;
    }

    private function resolverAsesorImportacion($nombreAsesor, $indiceAsesores) {
        $clave = $this->normalizarTextoImportacion($nombreAsesor);

        if ($clave === '' || !isset($indiceAsesores[$clave])) {
            throw new Exception('No se encontró el asesor indicado en el archivo.');
        }

        if (count($indiceAsesores[$clave]) > 1) {
            throw new Exception('El nombre del asesor es ambiguo y coincide con más de un usuario.');
        }

        return $indiceAsesores[$clave][0];
    }

    private function actualizarFacturaImportada($facturaId, $estadoActual, $resultadoContacto, $razonEspecifica, $fechaPago = null) {
        // En la nueva BD (`obligaciones`) no existe `estado_factura`/`fecha_pago`.
        // La importación de gestiones queda registrada en `historial_gestiones` + `acuerdos` (si aplica).
        return;
    }

    private function determinarEstadoFacturaImportada($estadoActual, $resultadoContacto, $razonEspecifica) {
        return 'gestionada';
    }

    private function parsearCanalesAutorizadosImportacion($valor) {
        $valor = trim($this->convertirTextoAUtf8Importacion((string) $valor));
        if ($valor === '') {
            return [];
        }

        $partes = preg_split('/[,\|;]/', $valor);
        $canales = [];

        foreach ($partes as $parte) {
            $canal = $this->normalizarCanalContactoImportacion($parte);
            if ($canal !== '') {
                $canales[$canal] = true;
            }
        }

        return array_keys($canales);
    }

    private function normalizarCanalContactoImportacion($valor) {
        $valor = $this->convertirTextoAUtf8Importacion((string) $valor);
        $normalizado = $this->normalizarTextoImportacion($valor);
        $mapa = [
            'llamada' => 'llamada',
            'llamada_telefonica' => 'llamada',
            'telefonica' => 'llamada',
            'telefono' => 'llamada',
            'whatsapp' => 'whatsapp',
            'wa' => 'whatsapp',
            'correo_electronico' => 'correo_electronico',
            'correo' => 'correo_electronico',
            'email' => 'correo_electronico',
            'sms' => 'sms',
            'mensaje_de_texto' => 'sms',
            'correo_fisico' => 'correo_fisico',
            'mensajeria_por_aplicaciones' => 'mensajeria_aplicaciones',
            'mensajeria_aplicaciones' => 'mensajeria_aplicaciones',
            'chat' => 'chat'
        ];

        return $mapa[$normalizado] ?? str_replace(' ', '_', trim((string) mb_strtolower($valor, 'UTF-8')));
    }

    private function normalizarTelefonoContactoImportacion($telefono) {
        $telefono = preg_replace('/\D+/', '', (string) $telefono);
        if ($telefono === '') {
            return null;
        }

        if (strlen($telefono) > 10) {
            return substr($telefono, -10);
        }

        return $telefono;
    }

    private function parsearFechaFlexibleImportacion($valor, $permitirVacio = false) {
        $valor = trim($this->convertirTextoAUtf8Importacion((string) $valor));
        if ($valor === '') {
            return $permitirVacio ? null : false;
        }

        $formatos = [
            'Y-m-d H:i:s',
            'Y-m-d H:i',
            'd/m/Y H:i:s',
            'd/m/Y H:i',
            'd-m-Y H:i:s',
            'd-m-Y H:i',
            'Y-m-d',
            'd/m/Y',
            'd-m-Y'
        ];

        foreach ($formatos as $formato) {
            $fecha = DateTime::createFromFormat($formato, $valor);
            if ($fecha instanceof DateTime) {
                if (strpos($formato, 'H:i') === false) {
                    return $permitirVacio ? $fecha->format('Y-m-d') : $fecha->format('Y-m-d 00:00:00');
                }
                return $fecha->format('Y-m-d H:i:s');
            }
        }

        $timestamp = strtotime($valor);
        if ($timestamp !== false) {
            return $permitirVacio ? date('Y-m-d', $timestamp) : date('Y-m-d H:i:s', $timestamp);
        }

        return false;
    }

    /**
     * Interpreta montos en CSV (punto/coma decimal, separadores de miles, vacío = null).
     */
    private function parsearValorMonetarioImportacion($valor) {
        $s = trim($this->convertirTextoAUtf8Importacion((string) $valor));
        if ($s === '') {
            return null;
        }

        $s = str_replace(["\xC2\xA0", ' '], '', $s); // NBSP y espacios
        $s = str_replace('$', '', $s);

        if (preg_match('/^(na|n\/a|ninguno|ninguna|sin)$/i', $s)) {
            return null;
        }

        $ultimaComa = strrpos($s, ',');
        $ultimoPunto = strrpos($s, '.');
        if ($ultimaComa !== false && $ultimoPunto !== false) {
            if ($ultimaComa > $ultimoPunto) {
                $s = str_replace('.', '', $s);
                $s = str_replace(',', '.', $s);
            } else {
                $s = str_replace(',', '', $s);
            }
        } elseif ($ultimaComa !== false && $ultimoPunto === false) {
            if (substr_count($s, ',') === 1 && preg_match('/^\d{1,3},\d{1,2}$/', $s)) {
                $s = str_replace(',', '.', $s);
            } else {
                $s = str_replace(',', '', $s);
            }
        } else {
            $s = str_replace(',', '', $s);
        }

        // Miles con punto (ej. 1.234.567) sin parte decimal
        if (strpos($s, ',') === false && strpos($s, '.') !== false && preg_match('/^\d{1,3}(\.\d{3})+$/', $s)) {
            $s = str_replace('.', '', $s);
        }

        if ($s === '' || !is_numeric($s)) {
            return null;
        }

        return (float) $s;
    }

    private function normalizarTextoImportacion($texto) {
        $texto = trim($this->convertirTextoAUtf8Importacion((string) $texto));
        if ($texto === '') {
            return '';
        }

        $texto = preg_replace('/^\xEF\xBB\xBF/', '', $texto);
        if (function_exists('iconv')) {
            $convertido = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto);
            if ($convertido !== false) {
                $texto = $convertido;
            }
        }

        $texto = function_exists('mb_strtolower')
            ? mb_strtolower($texto, 'UTF-8')
            : strtolower($texto);
        $texto = preg_replace('/[^a-z0-9]+/', '_', $texto);
        return trim($texto, '_');
    }

    private function convertirTextoAUtf8Importacion($texto) {
        $texto = (string) $texto;
        if ($texto === '') {
            return '';
        }

        $texto = preg_replace('/^\xEF\xBB\xBF/', '', $texto);

        if (mb_detect_encoding($texto, ['UTF-8'], true) !== false) {
            return $texto;
        }

        $codificaciones = ['Windows-1252', 'ISO-8859-1', 'ISO-8859-15'];
        foreach ($codificaciones as $encoding) {
            $convertido = @mb_convert_encoding($texto, 'UTF-8', $encoding);
            if ($convertido !== false && mb_detect_encoding($convertido, ['UTF-8'], true) !== false) {
                return $convertido;
            }
        }

        if (function_exists('iconv')) {
            foreach ($codificaciones as $encoding) {
                $convertido = @iconv($encoding, 'UTF-8//IGNORE', $texto);
                if ($convertido !== false && $convertido !== '') {
                    return $convertido;
                }
            }
        }

        return $texto;
    }

    public function listUsuarios() {
        $page_title = "Lista de Usuarios";
        
        // Obtener filtros
        $search = $_GET['search'] ?? '';
        $rol_filter = $_GET['rol_filter'] ?? '';
        $estado_filter = $_GET['estado_filter'] ?? '';
        
        // Obtener usuarios con filtros
        $usuarios = $this->usuarioModel->getUsuariosWithFilters($search, $rol_filter, $estado_filter);
        
        require __DIR__ . '/../views/usuario_list.php';
    }

    public function createUsuario() {
        $page_title = "Crear Nuevo Usuario";
        $usuario = null;
        $error = '';
        $success = '';
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                // Validar datos
                if (empty($_POST['nombre']) || empty($_POST['cedula']) || empty($_POST['usuario']) || empty($_POST['contrasena']) || empty($_POST['rol'])) {
                    $error = "Todos los campos obligatorios deben estar completos.";
                } else {
                    // Mapear datos del formulario al formato esperado por el modelo
                    $data = [
                        'nombre_completo' => $_POST['nombre'],
                        'cedula' => $_POST['cedula'],
                        'usuario' => $_POST['usuario'],
                        'contrasena' => $_POST['contrasena'],
                        'rol' => $_POST['rol'],
                        'estado' => $_POST['estado'] ?? 'Activo',
                        'extension_telefono' => $_POST['extension_telefono'] ?? '',
                        'clave_webrtc' => $_POST['clave_webrtc'] ?? ''
                    ];
                    
                    // Activar automáticamente el teléfono si hay extensión y clave
                    if (!empty($data['extension_telefono']) && !empty($data['clave_webrtc'])) {
                        $data['telefono_activo'] = 'Si';
                    } else {
                        $data['telefono_activo'] = 'No';
                    }
                    
                    $result = $this->usuarioModel->createUsuario($data);
                    if ($result) {
                        $success = "Usuario creado exitosamente.";
                        // Limpiar el formulario
                        $_POST = [];
                    } else {
                        $error = "Error al crear el usuario. Verifica que el usuario no exista ya.";
                    }
                }
            } catch (Exception $e) {
                $error = "Error: " . $e->getMessage();
            }
        }
        
        require __DIR__ . '/../views/usuario_form.php';
    }

    public function editUsuario($id) {
        $page_title = "Editar Usuario";
        $usuario = $this->usuarioModel->getUsuarioById($id);
        $error = '';
        $success = '';
        
        if (!$usuario) {
            header('Location: index.php?action=list_usuarios');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                // Validar datos obligatorios
                if (empty($_POST['nombre']) || empty($_POST['cedula']) || empty($_POST['usuario']) || empty($_POST['rol'])) {
                    $error = "Todos los campos obligatorios deben estar completos.";
                } else {
                    // Validar contraseña si se proporciona
                    if (!empty($_POST['contrasena'])) {
                        if (empty($_POST['confirmar_contrasena'])) {
                            $error = "Debe confirmar la nueva contraseña.";
                        } elseif ($_POST['contrasena'] !== $_POST['confirmar_contrasena']) {
                            $error = "Las contraseñas no coinciden.";
                        } elseif (strlen($_POST['contrasena']) < 6) {
                            $error = "La contraseña debe tener al menos 6 caracteres.";
                        }
                    }
                    
                    // Si no hay errores, proceder con la actualización
                    if (empty($error)) {
                        // Mapear datos del formulario al formato esperado por el modelo
                        $data = [
                            'nombre_completo' => $_POST['nombre'],
                            'cedula' => $_POST['cedula'],
                            'usuario' => $_POST['usuario'],
                            'rol' => $_POST['rol'],
                            'estado' => $_POST['estado'] ?? 'Activo',
                            'extension_telefono' => $_POST['extension_telefono'] ?? '',
                            'clave_webrtc' => $_POST['clave_webrtc'] ?? ''
                        ];
                        
                        // Activar automáticamente el teléfono si hay extensión y clave
                        if (!empty($data['extension_telefono']) && !empty($data['clave_webrtc'])) {
                            $data['telefono_activo'] = 'Si';
                        } else {
                            $data['telefono_activo'] = 'No';
                        }
                        
                        // Agregar contraseña solo si se proporciona
                        if (!empty($_POST['contrasena'])) {
                            $data['contrasena'] = $_POST['contrasena'];
                        }
                        
                        $result = $this->usuarioModel->updateUsuario($id, $data);
                        if ($result) {
                            $success = "Usuario actualizado exitosamente.";
                            // Actualizar datos del usuario en la variable
                            $usuario = array_merge($usuario, $data);
                        } else {
                            $error = "Error al actualizar el usuario.";
                        }
                    }
                }
            } catch (Exception $e) {
                $error = "Error: " . $e->getMessage();
            }
        }
        
        require __DIR__ . '/../views/usuario_form.php';
    }

    public function toggleEstadoUsuario($id) {
        $this->usuarioModel->toggleEstadoUsuario($id);
        header('Location: index.php?action=list_usuarios');
        exit;
    }
    
    public function verActividades() {
        $page_title = "Actividades del Sistema";
        
        // Obtener estadísticas de actividades
        $stats = [
            'total_usuarios' => count($this->usuarioModel->getAllUsuarios()),
            'coordinadores' => count($this->usuarioModel->getUsuariosByRol('coordinador')),
            'asesores' => count($this->usuarioModel->getUsuariosByRol('asesor')),
            'usuarios_activos' => count($this->usuarioModel->getUsuariosByRol('administrador')) + count($this->usuarioModel->getUsuariosByRol('coordinador')) + count($this->usuarioModel->getUsuariosByRol('asesor'))
        ];
        
        require __DIR__ . '/../views/admin_actividades.php';
    }
    
    public function asignarPersonal() {
        $page_title = "Asignación de Personal";
        
        // Prevenir cache del navegador para esta página
        header("Cache-Control: no-cache, no-store, must-revalidate");
        header("Pragma: no-cache");
        header("Expires: 0");
        
        // Obtener coordinadores y asesores DISPONIBLES (no asignados)
        $coordinadores = $this->usuarioModel->getUsuariosByRol('coordinador');
        $asesores = $this->usuarioModel->getAsesoresDisponibles(); // Solo asesores NO asignados
        
        // Obtener asesores asignados para mostrar en la sección correspondiente
        $asesoresAsignados = [];
        foreach ($coordinadores as $coordinador) {
            if ($coordinador['estado'] === 'Activo') {
                $asesoresDelCoordinador = $this->usuarioModel->getAsesoresByCoordinador($coordinador['id']);
                foreach ($asesoresDelCoordinador as $asesor) {
                    $asesor['coordinador_nombre'] = $coordinador['nombre_completo'];
                    $asesor['coordinador_id'] = $coordinador['id'];
                    $asesoresAsignados[] = $asesor;
                }
            }
        }
        
        // Obtener mensajes de éxito o error
        $success = $_GET['success'] ?? '';
        $error = $_GET['error'] ?? '';
        
        require __DIR__ . '/../views/admin_asignar_personal.php';
    }

    /**
     * Ver la gestión y métricas de un coordinador específico
     */
    public function verGestionCoordinador($coordinadorId) {
        $page_title = "Gestión del Coordinador";
        
        // Verificar que el usuario sea administrador
        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'administrador') {
            header('Location: index.php?action=login');
            exit;
        }
        
        // Obtener datos del coordinador
        $coordinador = $this->usuarioModel->getUsuarioById($coordinadorId);
        
        if (!$coordinador || $coordinador['rol'] !== 'coordinador') {
            header('Location: index.php?action=asignar_personal&error=coordinador_no_encontrado');
            exit;
        }
        
        // Obtener asesores asignados al coordinador
        $asesoresAsignados = $this->usuarioModel->getAsesoresByCoordinador($coordinadorId);
        
        // Obtener métricas básicas
        $metricas = [
            'total_asesores_asignados' => count($asesoresAsignados),
            'asesores_activos' => count(array_filter($asesoresAsignados, function($asesor) {
                return $asesor['estado'] === 'Activo';
            })),
            'coordinador_estado' => $coordinador['estado']
        ];
        
        require __DIR__ . '/../views/admin_gestion_coordinador.php';
    }

    /**
     * Ver la gestión y métricas de un asesor específico
     */
    public function verGestionAsesor($asesorId) {
        $page_title = "Gestión del Asesor";
        
        // Verificar que el usuario sea administrador
        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'administrador') {
            header('Location: index.php?action=login');
            exit;
        }
        
        // Obtener datos del asesor
        $asesor = $this->usuarioModel->getUsuarioById($asesorId);
        
        if (!$asesor || $asesor['rol'] !== 'asesor') {
            header('Location: index.php?action=asignar_personal&error=asesor_no_encontrado');
            exit;
        }
        
        // Obtener coordinador asignado al asesor
        $coordinadorAsignado = null;
        $coordinadores = $this->usuarioModel->getUsuariosByRol('coordinador');
        
        foreach ($coordinadores as $coordinador) {
            if ($this->usuarioModel->isAsesorAsignadoACoordinador($asesorId, $coordinador['id'])) {
                $coordinadorAsignado = $coordinador;
                break;
            }
        }
        
        // Obtener métricas básicas
        $metricas = [
            'asesor_estado' => $asesor['estado'],
            'coordinador_asignado' => $coordinadorAsignado ? $coordinadorAsignado['nombre_completo'] : 'Sin asignar',
            'fecha_registro' => $asesor['fecha_registro'] ?? 'No disponible'
        ];
        
        require __DIR__ . '/../views/admin_gestion_asesor.php';
    }

    /**
     * Procesar la asignación de un asesor a un coordinador
     */
    public function asignarAsesor() {
        // Verificar que el usuario sea administrador
        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'administrador') {
            header('Location: index.php?action=login');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $coordinadorId = $_POST['coordinador_id'] ?? null;
            $asesorId = $_POST['asesor_id'] ?? null;
            
            // Validar datos
            if (empty($coordinadorId) || empty($asesorId)) {
                header('Location: index.php?action=asignar_personal&error=datos_incompletos');
                exit;
            }
            
            // Verificar que existan los usuarios
            $coordinador = $this->usuarioModel->getUsuarioById($coordinadorId);
            $asesor = $this->usuarioModel->getUsuarioById($asesorId);
            
            if (!$coordinador || $coordinador['rol'] !== 'coordinador') {
                header('Location: index.php?action=asignar_personal&error=coordinador_invalido');
                exit;
            }
            
            if (!$asesor || $asesor['rol'] !== 'asesor') {
                header('Location: index.php?action=asignar_personal&error=asesor_invalido');
                exit;
            }
            
            // Verificar que ambos usuarios estén activos
            if ($coordinador['estado'] !== 'Activo' || $asesor['estado'] !== 'Activo') {
                header('Location: index.php?action=asignar_personal&error=usuarios_inactivos');
                exit;
            }
            
            try {
                // Realizar la asignación
                $result = $this->usuarioModel->asignarAsesorACoordinador($asesorId, $coordinadorId);
                
                if ($result) {
                    // Log de la asignación
                    error_log("Asignación exitosa - Asesor ID: {$asesorId} asignado a Coordinador ID: {$coordinadorId} por Admin ID: {$_SESSION['user_id']}");
                    
                    // Limpiar cualquier cache de sesión
                    if (isset($_SESSION['asesores_cache'])) {
                        unset($_SESSION['asesores_cache']);
                    }
                    
                    header('Location: index.php?action=asignar_personal&success=asignacion_exitosa&t=' . time());
                } else {
                    header('Location: index.php?action=asignar_personal&error=error_asignacion&t=' . time());
                }
            } catch (Exception $e) {
                error_log("Error en asignación de asesor: " . $e->getMessage());
                header('Location: index.php?action=asignar_personal&error=error_sistema');
            }
        } else {
            // Si no es POST, redirigir
            header('Location: index.php?action=asignar_personal');
        }
        exit;
    }

    /**
     * Liberar un asesor de un coordinador
     */
    public function liberarAsesor($asesorId, $coordinadorId) {
        // Verificar que el usuario sea administrador
        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'administrador') {
            header('Location: index.php?action=login');
            exit;
        }
        
        try {
            $result = $this->usuarioModel->liberarAsesorDeCoordinador($asesorId, $coordinadorId);
            
            if ($result) {
                // Log de la liberación
                error_log("Liberación exitosa - Asesor ID: {$asesorId} liberado del Coordinador ID: {$coordinadorId} por Admin ID: {$_SESSION['user_id']}");
                
                // Limpiar cualquier cache de sesión
                if (isset($_SESSION['asesores_cache'])) {
                    unset($_SESSION['asesores_cache']);
                }
                
                header('Location: index.php?action=asignar_personal&success=liberacion_exitosa&t=' . time());
            } else {
                header('Location: index.php?action=asignar_personal&error=error_liberacion&t=' . time());
            }
        } catch (Exception $e) {
            error_log("Error en liberación de asesor: " . $e->getMessage());
            header('Location: index.php?action=asignar_personal&error=error_sistema');
        }
        exit;
    }
}
?>
