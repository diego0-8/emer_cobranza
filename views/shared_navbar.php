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
                'Resultados' => 'index.php?action=asignar_personal',
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
                'Tareas' => 'index.php?action=tareas_coordinador',
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
        $navbar .= '<li><a href="' . $url . '" class="' . $activeClass . '">' . $label . '</a></li>';
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

// Función alternativa para incluir la barra de navegación directamente
if (!function_exists('includeNavbar')) {
function includeNavbar($currentPage = '', $userRole = '') {
    echo getNavbar($currentPage, $userRole);
}
}
?>
