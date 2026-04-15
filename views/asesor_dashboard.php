<?php
// Archivo: views/asesor_dashboard_clean.php
// Dashboard limpio del asesor - Sin código duplicado ni errores
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="js/shared-functions.js"></script>
    <?php require_once 'shared_styles.php'; ?>
    <link rel="stylesheet" href="assets/css/session-time-modal.css">
    <style>
        /* Estilos específicos para el dashboard del asesor */
        .dashboard-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .metric-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s ease;
        }
        
        .metric-card:hover {
            transform: translateY(-5px);
        }
        
        .metric-value {
            font-size: 2.5rem;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 10px;
        }
        
        .metric-label {
            color: #666;
            font-size: 0.9rem;
        }
        
        .charts-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .chart-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .chart-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 15px;
            color: #333;
        }
        
        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
        }
        
        .btn {
            padding: 12px 24px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: #007bff;
            color: white;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
    </style>
</head>
<body>
    <?php 
    include 'views/shared_navbar.php';
    echo getNavbar('Dashboard', $_SESSION['user_role'] ?? ''); 
    ?>
    
    <div class="dashboard-container">
        <div class="page-header">
            <h1><?php echo htmlspecialchars($page_title); ?></h1>
            <p class="page-description">Panel de control del asesor - Gestión de clientes y ventas</p>
            
            <!-- Botón de Tiempo de Sesión -->
            <div style="margin-top: 15px; text-align: center;">
                <button class="session-time-btn" id="btnTiempoSesionVista" title="Tiempo de Sesión"
                    style="background: #3b82f6; color: white; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer; font-size: 1rem; display: inline-flex; align-items: center; gap: 10px; transition: all 0.3s ease;">
                    <i class="fas fa-clock"></i>
                    <span>Ver Tiempo de Sesión</span>
                </button>
            </div>
        </div>

        <!-- Métricas principales -->
        <div class="metrics-grid">
            <div class="metric-card">
                <div class="metric-value"><?php echo $datos_dashboard['total_clientes'] ?? 0; ?></div>
                <div class="metric-label">Total Clientes</div>
            </div>
            <div class="metric-card">
                <div class="metric-value"><?php echo $datos_dashboard['gestiones_hoy'] ?? 0; ?></div>
                <div class="metric-label">Gestiones Hoy</div>
            </div>
            <div class="metric-card">
                <div class="metric-value"><?php echo $datos_dashboard['contactos_efectivos_hoy'] ?? 0; ?></div>
                <div class="metric-label">Contactos Efectivos</div>
            </div>
            <div class="metric-card">
                <div class="metric-value"><?php echo $datos_dashboard['acuerdos_hoy'] ?? 0; ?></div>
                <div class="metric-label">Acuerdos Hoy</div>
            </div>
        </div>

        <!-- Gráficos -->
        <div class="charts-container">
            <div class="chart-card">
                <div class="chart-title">Tipificaciones por Resultado (Período)</div>
                <canvas id="tipificacionesChart"></canvas>
            </div>
            <div class="chart-card">
                <div class="chart-title">Gestiones por Día</div>
                <canvas id="gestionesChart"></canvas>
            </div>
        </div>

        <!-- Botones de Acción -->
        <div class="action-buttons">
            <a href="index.php?action=mis_tareas" class="btn btn-primary">
                <i class="fas fa-tasks"></i> Ver Mis Tareas
            </a>
            <a href="index.php?action=mis_clientes" class="btn btn-secondary">
                <i class="fas fa-list"></i> Ver Mis Clientes
            </a>
            <a href="index.php?action=dashboard&periodo=<?php echo $datos_dashboard['periodo']; ?>" class="btn btn-success">
                <i class="fas fa-sync"></i> Actualizar Dashboard
            </a>
        </div>
    </div>

    <script>
        // Configurar datos para las funciones compartidas
        window.llamadasPendientesData = <?php echo json_encode($datos_dashboard['llamadas_pendientes'] ?? []); ?>;
        
        // Esperar a que el DOM esté completamente cargado
        document.addEventListener('DOMContentLoaded', function() {
            // Verificar que Chart.js esté disponible
            if (typeof Chart === 'undefined') {
                console.error('Chart.js no está cargado');
                return;
            }
            
            // Gráfico de tipificaciones
            const tipificacionesChart = document.getElementById('tipificacionesChart');
            if (!tipificacionesChart) {
                console.error('Elemento tipificacionesChart no encontrado');
                return;
            }
            
            const tipificacionesCtx = tipificacionesChart.getContext('2d');
            new Chart(tipificacionesCtx, {
                type: 'doughnut',
                data: {
                    labels: <?php echo json_encode(array_column($tipificaciones, 'resultado')); ?>,
                    datasets: [{
                        data: <?php echo json_encode(array_column($tipificaciones, 'cantidad')); ?>,
                        backgroundColor: [
                            '#28a745', '#20c997', '#17a2b8', '#007bff',
                            '#6f42c1', '#e83e8c', '#dc3545', '#fd7e14',
                            '#ffc107', '#6c757d', '#343a40'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                font: { size: 12 }
                            }
                        }
                    }
                }
            });
            
            // Gráfico de gestiones por día
            const gestionesChart = document.getElementById('gestionesChart');
            if (!gestionesChart) {
                console.error('Elemento gestionesChart no encontrado');
                return;
            }
            
            const gestionesCtx = gestionesChart.getContext('2d');
            new Chart(gestionesCtx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode(array_column($gestionesPorDia, 'fecha')); ?>,
                    datasets: [{
                        label: 'Gestiones por Día',
                        data: <?php echo json_encode(array_column($gestionesPorDia, 'cantidad')); ?>,
                        borderColor: 'rgb(0, 123, 255)',
                        backgroundColor: 'rgba(0, 123, 255, 0.1)',
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    },
                    plugins: {
                        legend: {
                            labels: {
                                font: { size: 12 }
                            }
                        }
                    }
                }
            });
        });
    </script>

    <!-- Elemento oculto para pasar el tiempo de inicio de sesión al JavaScript -->
    <div id="sessionStartTime" data-start-time="<?php
    // Obtener tiempo de inicio de sesión desde $_SESSION o desde la BD
    $login_time = null;
    
    // Primero intentar desde la sesión PHP
    if (isset($_SESSION['login_time'])) {
        $login_time = $_SESSION['login_time'];
    } else {
        // Si no está en la sesión, intentar recuperarlo desde la BD
        try {
            $asesor_id = $_SESSION['user_id'] ?? null;
            if ($asesor_id) {
                require_once __DIR__ . '/../config.php';
                $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                // Verificar si existe la tabla
                $sqlCheck = "SHOW TABLES LIKE 'sesiones_trabajo'";
                $stmtCheck = $pdo->query($sqlCheck);
                
                if ($stmtCheck->rowCount() > 0) {
                    // Buscar sesión activa
                    $sql = "SELECT fecha_inicio FROM sesiones_trabajo 
                            WHERE usuario_id = ? AND (fecha_fin IS NULL OR estado = 'activa')
                            ORDER BY fecha_inicio DESC LIMIT 1";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$asesor_id]);
                    $sesion = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($sesion) {
                        $fechaInicio = new DateTime($sesion['fecha_inicio']);
                        $login_time = $fechaInicio->getTimestamp();
                        $_SESSION['login_time'] = $login_time; // Guardar en sesión para próximas cargas
                    }
                }
            }
        } catch (Exception $e) {
            error_log("Error al recuperar tiempo de sesión desde BD: " . $e->getMessage());
        }
        
        // Si aún no se tiene, usar tiempo actual (primera vez que inicia sesión)
        if (!$login_time) {
            $login_time = time();
            $_SESSION['login_time'] = $login_time;
        }
    }
    
    echo $login_time;
    ?>" style="display: none;"></div>

    <!-- Modal de Confirmación de Break -->
    <div id="modalConfirmarBreak" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); z-index: 10000; align-items: center; justify-content: center;">
        <div class="modal-content" style="max-width: 450px; width: 90%; background: white; border-radius: 12px; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3); animation: slideIn 0.3s ease-out;">
            <div class="modal-header" style="background: #f8fafc; padding: 20px 25px; border-bottom: 1px solid #e5e7eb; border-radius: 12px 12px 0 0;">
                <h3 style="margin: 0; color: #1f2937; font-size: 1.3rem; display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-question-circle" style="color: #3b82f6;"></i>
                    Confirmar Descanso
                </h3>
            </div>
            <div class="modal-body" style="padding: 25px;">
                <p id="confirmarBreakMensaje" style="margin: 0 0 25px 0; color: #374151; font-size: 1rem; line-height: 1.6;">
                    ¿Deseas iniciar este descanso?
                </p>
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button id="btnCancelarBreak" class="btn btn-secondary" style="padding: 12px 24px; font-size: 1rem; border-radius: 8px; border: none; cursor: pointer; transition: all 0.3s ease; font-weight: 600; background: #6b7280; color: white;">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button id="btnConfirmarBreak" class="btn btn-primary" style="padding: 12px 24px; font-size: 1rem; border-radius: 8px; border: none; cursor: pointer; transition: all 0.3s ease; font-weight: 600; background: #3b82f6; color: white;">
                        <i class="fas fa-check"></i> Confirmar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Tiempo de Sesión y Breaks -->
    <div id="modalTiempoSesion"
        style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); z-index: 9999; align-items: center; justify-content: center;">
        <div class="modal-content session-modal">
            <div class="modal-header">
                <h3><i class="fas fa-clock" style="color: #3b82f6; margin-right: 10px;"></i>Tiempo de Sesión</h3>
                <button type="button" class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <div class="session-time-container">
                    <!-- Hora Actual -->
                    <div class="time-display-card current-time">
                        <div class="time-label">Hora Actual</div>
                        <div class="time-value blue" id="horaActualDisplay">--:-- --</div>
                    </div>

                    <!-- Tiempo de Sesión -->
                    <div class="time-display-card session-time">
                        <div class="time-label">Tiempo de Sesión</div>
                        <div class="time-value green" id="tiempoSesionDisplay">00:00:00</div>
                    </div>

                    <!-- Botones de Breaks -->
                    <div class="break-section">
                        <div class="break-buttons-vertical">
                            <button class="break-btn break-yellow" onclick="registrarBreak('break')" data-tipo="break">
                                <i class="fas fa-coffee"></i>
                                <span>Break</span>
                            </button>
                            <button class="break-btn break-orange" onclick="registrarBreak('almuerzo')"
                                data-tipo="almuerzo">
                                <i class="fas fa-utensils"></i>
                                <span>Almuerzo</span>
                            </button>
                            <button class="break-btn break-teal" onclick="registrarBreak('baño')" data-tipo="baño">
                                <i class="fas fa-toilet"></i>
                                <span>Baño</span>
                            </button>
                            <button class="break-btn break-gray" onclick="registrarBreak('mantenimiento')"
                                data-tipo="mantenimiento">
                                <i class="fas fa-tools"></i>
                                <span>Mantenimiento</span>
                            </button>
                            <button class="break-btn break-green" onclick="registrarBreak('pausa_activa')"
                                data-tipo="pausa_activa">
                                <i class="fas fa-running"></i>
                                <span>Pausa Activa</span>
                            </button>
                            <button class="break-btn break-purple" onclick="registrarBreak('actividad_extra')"
                                data-tipo="actividad_extra">
                                <i class="fas fa-stopwatch"></i>
                                <span>Actividad Extra</span>
                            </button>
                        </div>

                        <div id="breakStatus" class="break-status" style="display: none;">
                            <div class="break-status-content">
                                <i class="fas fa-check-circle"></i>
                                <span id="breakStatusText"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Script para gestión de tiempo de sesión y breaks -->
    <script src="assets/js/asesor-tiempo.js"></script>
    <script>
        // Hacer funciones disponibles globalmente INMEDIATAMENTE
        // Función para abrir el modal directamente
        window.abrirModalTiempoPrueba = function () {
            console.log('=== PRUEBA DIRECTA DEL MODAL ===');
            const modal = document.getElementById('modalTiempoSesion');
            console.log('1. Modal encontrado:', modal);

            if (modal) {
                console.log('2. Clases actuales:', modal.className);
                console.log('3. Display actual:', window.getComputedStyle(modal).display);

                // Agregar clase show
                modal.classList.add('show');
                console.log('4. Clases después de add show:', modal.className);
                console.log('5. Display después:', window.getComputedStyle(modal).display);

                // Forzar display flex
                modal.style.display = 'flex';
                console.log('6. Display forzado:', window.getComputedStyle(modal).display);

                document.body.style.overflow = 'hidden';
                console.log('7. Modal debería estar visible ahora');

                // Iniciar contadores
                if (typeof iniciarContadorTiempo === 'function') {
                    iniciarContadorTiempo();
                } else if (typeof window.iniciarContadorTiempo === 'function') {
                    window.iniciarContadorTiempo();
                }
            } else {
                console.error('Modal NO encontrado');
                alert('Error: Modal de tiempo de sesión no encontrado');
            }
        };

        // Función para cerrar el modal
        window.cerrarModalTiempoPrueba = function () {
            const modal = document.getElementById('modalTiempoSesion');
            if (modal) {
                modal.classList.remove('show');
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';

                // Detener contadores
                if (typeof detenerContadorTiempo === 'function') {
                    detenerContadorTiempo();
                } else if (typeof window.detenerContadorTiempo === 'function') {
                    window.detenerContadorTiempo();
                }
            }
        };

        // También usar la función del script principal si está disponible
        window.mostrarModalTiempoSesion = window.mostrarModalTiempoSesion || window.abrirModalTiempoPrueba;
        window.cerrarModalTiempoSesion = window.cerrarModalTiempoSesion || window.cerrarModalTiempoPrueba;

        // Configurar el botón cuando el DOM esté listo
        document.addEventListener('DOMContentLoaded', function () {
            console.log('=== CONFIGURANDO MODAL DE TIEMPO ===');

            const btnVista = document.getElementById('btnTiempoSesionVista');
            const modal = document.getElementById('modalTiempoSesion');

            console.log('Botón encontrado:', btnVista ? 'SÍ' : 'NO');
            console.log('Modal encontrado:', modal ? 'SÍ' : 'NO');

            if (btnVista) {
                // Limpiar cualquier evento previo y agregar uno nuevo
                btnVista.onclick = function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('=== CLIC EN BOTÓN DETECTADO ===');
                    if (typeof window.abrirModalTiempoPrueba === 'function') {
                        window.abrirModalTiempoPrueba();
                    } else if (typeof window.mostrarModalTiempoSesion === 'function') {
                        window.mostrarModalTiempoSesion();
                    } else {
                        alert('Error: Función para abrir modal no disponible');
                    }
                };
                console.log('Event listener onclick configurado');
            }

            // Configurar botón de cerrar del modal
            if (modal) {
                const closeBtn = modal.querySelector('.modal-close');
                if (closeBtn) {
                    closeBtn.onclick = function (e) {
                        e.preventDefault();
                        if (typeof window.cerrarModalTiempoPrueba === 'function') {
                            window.cerrarModalTiempoPrueba();
                        } else if (typeof window.cerrarModalTiempoSesion === 'function') {
                            window.cerrarModalTiempoSesion();
                        }
                    };
                    console.log('Botón cerrar configurado');
                }

                // Cerrar al hacer clic fuera
                modal.addEventListener('click', function (e) {
                    if (e.target === modal) {
                        if (typeof window.cerrarModalTiempoPrueba === 'function') {
                            window.cerrarModalTiempoPrueba();
                        }
                    }
                });
            }
        });

        // También configurar inmediatamente usando event delegation (por si el DOM ya está listo)
        document.addEventListener('click', function (e) {
            const btnVista = e.target.closest('#btnTiempoSesionVista');
            const btnVistaIcon = e.target.closest('#btnTiempoSesionVista i');

            if (btnVista || btnVistaIcon) {
                e.preventDefault();
                e.stopPropagation();
                console.log('Clic detectado (event delegation)');
                if (typeof window.abrirModalTiempoPrueba === 'function') {
                    window.abrirModalTiempoPrueba();
                } else if (typeof window.mostrarModalTiempoSesion === 'function') {
                    window.mostrarModalTiempoSesion();
                }
            }
        });
    </script>
</body>
</html>
