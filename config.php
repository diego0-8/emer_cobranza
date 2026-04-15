<?php
/**
 * Configuración del Sistema de Gestión de Cobranza
 * Configuración completa para desarrollo y producción
 */

// Configuración de sesión (DEBE ir ANTES de session_start())
if (session_status() === PHP_SESSION_NONE) {
    // Verificar que no se hayan enviado headers
    if (!headers_sent()) {
        // Establece un nombre de sesión único para este CRM
        $session_name = "EMERCOBRANZA_SID";  // Nombre único para este CRM
        session_name($session_name);
        
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_secure', 0); // Cambiar a 1 en producción con HTTPS
        ini_set('session.cookie_samesite', 'Lax'); // Mejorar seguridad de cookies
    } else {
        // Si ya se enviaron headers, usar configuración por defecto
        error_log("Warning: Headers already sent, using default session configuration");
    }
}

// Configuración de zona horaria
date_default_timezone_set('America/Bogota');

// Constantes del proyecto
define('SITE_NAME', 'Sistema de Gestión de Cobranza');
define('SITE_VERSION', '2.2');
define('UPLOAD_MAX_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_FILE_TYPES', ['xlsx', 'xls', 'csv']);

// Configuración de base de datos
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'emermedica_cobranza');

// Configuración de errores
error_reporting(E_ALL);
// Evita que warnings/notices rompan headers (redirigir/JSON) en producción.
// Los errores quedan en `logs/error.log` por `log_errors=1`.
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Configurar ruta absoluta para el log de errores
$log_dir = __DIR__ . DIRECTORY_SEPARATOR . 'logs';
if (!is_dir($log_dir)) {
    // Intentar crear el directorio si no existe
    @mkdir($log_dir, 0755, true);
}
$log_file = $log_dir . DIRECTORY_SEPARATOR . 'error.log';
ini_set('error_log', $log_file);
?>
