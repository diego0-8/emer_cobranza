<?php
/**
 * Controlador Base
 * Contiene funcionalidades comunes para todos los controladores
 */

// Incluir todos los modelos necesarios (rutas absolutas al archivo: no dependen del CWD)
require_once __DIR__ . '/../models/UsuarioModel.php';
require_once __DIR__ . '/../models/ClienteModel.php';
require_once __DIR__ . '/../models/GestionModel.php';
require_once __DIR__ . '/../models/TareaModel.php';
require_once __DIR__ . '/../models/CargaExcelModel.php';
require_once __DIR__ . '/../models/FacturacionModel.php';

class BaseController {
    protected $pdo;
    /** @var UsuarioModel */
    protected $usuarioModel;
    /** @var ClienteModel */
    protected $clienteModel;
    /** @var GestionModel */
    protected $gestionModel;
    /** @var TareaModel */
    protected $tareaModel;
    /** @var CargaExcelModel */
    protected $cargaExcelModel;
    /** @var FacturacionModel */
    protected $facturacionModel;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->usuarioModel = new UsuarioModel($pdo);
        $this->clienteModel = new ClienteModel($pdo);
        $this->gestionModel = new GestionModel($pdo);
        $this->tareaModel = new TareaModel($pdo);
        $this->cargaExcelModel = new CargaExcelModel($pdo);
        $this->facturacionModel = new FacturacionModel($pdo);
    }

    /**
     * Verifica la conexión y reconecta si MySQL cerró la sesión (p.ej. 2006 "server has gone away").
     */
    protected function asegurarConexionDB() {
        try {
            $this->pdo->query('SELECT 1');
            return;
        } catch (Throwable $e) {
            $this->reconectarPDO();
        }
    }

    /**
     * Reconecta PDO y reinyecta la conexión en los modelos.
     */
    protected function reconectarPDO() {
        // Requiere que las constantes DB_* estén definidas (config.php cargado por index.php).
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS
        );
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("SET CHARACTER SET utf8mb4");

        $this->pdo = $pdo;
        $this->usuarioModel = new UsuarioModel($pdo);
        $this->clienteModel = new ClienteModel($pdo);
        $this->gestionModel = new GestionModel($pdo);
        $this->tareaModel = new TareaModel($pdo);
        $this->cargaExcelModel = new CargaExcelModel($pdo);
        $this->facturacionModel = new FacturacionModel($pdo);
    }

    /**
     * Verifica si el usuario está autenticado
     */
    protected function verificarAutenticacion() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: index.php?action=login');
            exit;
        }
    }

    /**
     * Verifica si el usuario tiene el rol requerido
     */
    protected function verificarRol($rolRequerido) {
        $this->verificarAutenticacion();
        
        // Si es un array, verificar si el rol del usuario está en el array
        if (is_array($rolRequerido)) {
            if (!in_array($_SESSION['user_role'], $rolRequerido)) {
                header('Location: index.php?action=dashboard');
                exit;
            }
        } else {
            // Si es un string, verificar igualdad
            if ($_SESSION['user_role'] !== $rolRequerido) {
                header('Location: index.php?action=dashboard');
                exit;
            }
        }
    }

    /**
     * Renderiza una vista con datos
     */
    protected function renderizarVista($vista, $datos = []) {
        extract($datos);
        include __DIR__ . "/../views/{$vista}.php";
    }

    /**
     * Redirige con mensaje de éxito
     */
    protected function redirigirConExito($url, $mensaje) {
        $_SESSION['success_message'] = $mensaje;
        header("Location: $url");
        exit;
    }

    /**
     * Redirige con mensaje de error
     */
    protected function redirigirConError($url, $mensaje) {
        $_SESSION['error_message'] = $mensaje;
        header("Location: $url");
        exit;
    }

    /**
     * Obtiene mensajes de sesión y los limpia
     */
    protected function obtenerMensajes() {
        $mensajes = [
            'success' => $_SESSION['success_message'] ?? null,
            'error' => $_SESSION['error_message'] ?? null
        ];
        
        unset($_SESSION['success_message'], $_SESSION['error_message']);
        return $mensajes;
    }

    /**
     * Valida datos requeridos
     */
    protected function validarDatosRequeridos($datos, $camposRequeridos) {
        $errores = [];
        foreach ($camposRequeridos as $campo) {
            if (empty($datos[$campo])) {
                $errores[] = "El campo {$campo} es requerido";
            }
        }
        return $errores;
    }

    /**
     * Sanitiza datos de entrada
     */
    protected function sanitizarDatos($datos) {
        $sanitizados = [];
        foreach ($datos as $clave => $valor) {
            if (is_string($valor)) {
                $sanitizados[$clave] = htmlspecialchars(trim($valor), ENT_QUOTES, 'UTF-8');
            } else {
                $sanitizados[$clave] = $valor;
            }
        }
        return $sanitizados;
    }

    /**
     * Obtiene y sanitiza datos de $_GET
     */
    protected function getGet($key, $default = null) {
        if (!isset($_GET[$key])) {
            return $default;
        }
        return htmlspecialchars(trim($_GET[$key]), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Obtiene y sanitiza datos de $_POST
     */
    protected function getPost($key, $default = null) {
        if (!isset($_POST[$key])) {
            return $default;
        }
        return htmlspecialchars(trim($_POST[$key]), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Valida que un ID sea numérico y positivo
     */
    protected function validarId($id, $nombreCampo = 'ID') {
        if (!is_numeric($id) || $id <= 0 || !is_int($id + 0)) {
            throw new Exception("El {$nombreCampo} debe ser un número entero positivo válido");
        }
        return (int)$id;
    }

    /**
     * Valida que una fecha tenga el formato correcto
     */
    protected function validarFecha($fecha, $nombreCampo = 'fecha') {
        if (empty($fecha)) {
            return null;
        }
        
        $fechaObj = DateTime::createFromFormat('Y-m-d', $fecha);
        if (!$fechaObj || $fechaObj->format('Y-m-d') !== $fecha) {
            throw new Exception("El formato de {$nombreCampo} debe ser YYYY-MM-DD");
        }
        
        return $fecha;
    }

    /**
     * Redirige a una URL con un mensaje opcional
     */
    protected function redirect($url, $message = null) {
        if ($message) {
            // Determinar si es un mensaje de éxito o error basado en el contenido
            if (stripos($message, 'exitoso') !== false || stripos($message, 'creado') !== false || stripos($message, 'actualizado') !== false) {
                $_SESSION['success_message'] = $message;
            } else {
                $_SESSION['error_message'] = $message;
            }
        }
        
        // Si ya se enviaron headers, usar JavaScript para redireccionar
        if (headers_sent()) {
            echo "<script>window.location.href = '$url';</script>";
            echo "<noscript><meta http-equiv='refresh' content='0;url=$url'></noscript>";
            exit;
        } else {
            header("Location: $url");
            exit;
        }
    }

    /**
     * Limpia todos los output buffers
     */
    protected function limpiarOutputBuffers() {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
    }

    /**
     * Configura headers para JSON
     */
    protected function configurarHeadersJSON() {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
            header('Cache-Control: no-cache, must-revalidate');
        }
    }

    /**
     * Envía una respuesta JSON de error
     */
    protected function enviarJSONError($mensaje, $codigo = 'ERROR', $httpCode = 400) {
        $this->limpiarOutputBuffers();
        $this->configurarHeadersJSON();
        
        if (!headers_sent() && $httpCode !== 200) {
            http_response_code($httpCode);
        }
        
        echo json_encode([
            'success' => false,
            'message' => $mensaje,
            'code' => $codigo
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * Envía una respuesta JSON de éxito
     */
    protected function enviarJSONExito($datos = []) {
        $this->limpiarOutputBuffers();
        $this->configurarHeadersJSON();
        
        $respuesta = array_merge(['success' => true], $datos);
        echo json_encode($respuesta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

}
?>
