<?php
// Archivo: views/shared_navbar.php
// Barra de navegación compartida para todas las vistas

// Verificar que la sesión esté iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Función para obtener la barra de navegación
if (!function_exists('getNavbar')) {
function getNavbar($currentPage = '', $userRole = '') {
    // Obtener el rol del usuario de la sesión si no se proporciona
    if (empty($userRole)) {
        $userRole = $_SESSION['user_role'] ?? '';
    }

    // Normalizar rol: en BD el enum puede ser `cordinador` (typo histórico) pero el resto del sistema usa `coordinador`.
    $userRole = strtolower(trim((string) $userRole));
    if ($userRole === 'cordinador') {
        $userRole = 'coordinador';
    }

    // Ruta canónica a la vista de resultados del equipo (views/coordinador_resultados_equipo.php vía CoordinadorController::resultadosEquipo).
    $urlResultadosCoordinador = 'index.php?action=resultados_equipo';
    
    $menuItems = [];
    
    // Menú según el rol del usuario
    switch ($userRole) {
        case 'administrador':
            $menuItems = [
                'Inicio' => 'index.php?action=dashboard',
                'Gestión' => 'index.php?action=ver_actividades',
                'Campañas' => 'index.php?action=list_campanas',
                'WhatsApp' => 'index.php?action=coord_whatsapp',
                'Tareas' => 'index.php?action=ver_actividades',
                'Localización' => 'index.php?action=ver_actividades',
                'Registrar usuario' => 'index.php?action=crear_usuario',
                'Sitio Web' => '#'
            ];
            break;
            
        case 'coordinador':
            $menuItems = [
                'Inicio' => 'index.php?action=dashboard',
                'Gestión' => 'index.php?action=list_cargas',
                'Resultados' => $urlResultadosCoordinador,
                'Tareas' => 'index.php?action=gestionar_tareas',
                'WhatsApp' => 'index.php?action=coord_whatsapp',
                'Llamadas' => 'index.php?action=coord_call',
                'Reportes CSV' => 'index.php?action=reportes_exportacion',
                'Reporte TMO' => 'index.php?action=reporte_tmo'
            ];
            break;
            
        case 'asesor':
            $menuItems = [
                'Inicio' => 'index.php?action=dashboard',
                'Mis Clientes' => 'index.php?action=mis_clientes',
                
            ];
            break;
            
        default:
            $menuItems = [
                'Inicio' => 'index.php?action=dashboard'
            ];
    }
    
    $navbar = '
    <nav class="top-navbar">
        <div class="nav-container">
            <ul class="nav-menu">';
    
    foreach ($menuItems as $label => $url) {
        $activeClass = ($currentPage === $label) ? 'active' : '';
        $extra = '';
        if ($label === 'WhatsApp' && in_array($userRole, ['coordinador', 'administrador'], true)) {
            $extra = '<span class="wa-coord-nav-badge" id="waCoordNavBadge" hidden aria-hidden="true">0</span>';
        }
        $navbar .= '<li><a href="' . $url . '" class="' . $activeClass . '"'
            . ($label === 'WhatsApp' ? ' id="waCoordNavLink"' : '')
            . '>' . $label . $extra . '</a></li>';
    }
    
    $navbar .= '
            </ul>
            <div class="user-section">
                <div class="user-greeting">
                    Bienvenido/a: <span class="user-name">' . htmlspecialchars($_SESSION['user_name'] ?? 'Usuario') . '</span>
                </div>
                <a href="index.php?action=logout" class="logout-btn">Cerrar</a>
            </div>
        </div>
    </nav>';


    return $navbar;
}
}

/**
 * Imprime elementos compartidos del <head> (título + favicon).
 * Uso en vistas (dentro de <head>):
 *   require_once __DIR__ . '/shared_navbar.php';
 *   renderPageHead($page_title ?? '');
 */
if (!function_exists('renderPageHead')) {
function renderPageHead($pageTitle = '') {
    $brand = 'Emermedica Cobranza';
    $pageTitle = trim((string)$pageTitle);
    if ($pageTitle === '') {
        $finalTitle = $brand;
    } else {
        // Evitar duplicar el brand si ya viene incluido.
        $finalTitle = (stripos($pageTitle, $brand) !== false) ? $pageTitle : ($pageTitle . ' - ' . $brand);
    }

    echo '<title>' . htmlspecialchars($finalTitle, ENT_QUOTES, 'UTF-8') . "</title>\n";
    // Logo de pestaña (favicon). Ruta relativa al root del proyecto.
    echo '<link rel="icon" type="image/png" href="img/emer_logo.png">' . "\n";
}
}

// Función alternativa para incluir la barra de navegación directamente
if (!function_exists('includeNavbar')) {
function includeNavbar($currentPage = '', $userRole = '') {
    echo getNavbar($currentPage, $userRole);

    // Burbujas WhatsApp globales (solo asesor — claim y gestionar_cliente)
    $role = $userRole !== '' ? $userRole : ($_SESSION['user_role'] ?? '');
    $role = strtolower(trim((string)$role));
    if ($role === 'cordinador') {
        $role = 'coordinador';
    }
    if ($role === 'asesor') {
        static $waGlobalInjected = false;
        if (!$waGlobalInjected) {
            $waGlobalInjected = true;
            echo '<link rel="stylesheet" href="assets/css/whatsapp-panel.css?v=10">' . "\n";
            echo '<script>window.__waGlobal=' . json_encode([
                'role' => $role,
                'pollMs' => 5000,
            ], JSON_UNESCAPED_UNICODE) . ';</script>' . "\n";
            echo '<script src="assets/js/whatsapp-bubbles.js?v=11"></script>' . "\n";
        }
    }
    if (in_array($role, ['coordinador', 'administrador'], true)) {
        static $waCoordNavInjected = false;
        if (!$waCoordNavInjected) {
            $waCoordNavInjected = true;
            echo '<script>window.__waCoordNav=' . json_encode([
                'pollMs' => 15000,
                'syncEvery' => 2,
            ], JSON_UNESCAPED_UNICODE) . ';</script>' . "\n";
            echo '<script src="assets/js/whatsapp-coord-nav.js?v=1"></script>' . "\n";
        }
    }
}
}
?>
