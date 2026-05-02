<?php
$page_title = $page_title ?? '';
$asesores = isset($asesores) && is_array($asesores) ? $asesores : [];
// #region agent log
@file_put_contents(__DIR__ . '/../debug-5e8407.log', json_encode([
    'sessionId' => '5e8407',
    'hypothesisId' => 'H2',
    'runId' => 'post-fix',
    'location' => 'coordinador_reportes_exportacion_simplificado.php:after_init',
    'message' => 'estado $asesores tras normalizar vista',
    'data' => [
        'count' => count($asesores),
    ],
    'timestamp' => (int) round(microtime(true) * 1000),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n", FILE_APPEND);
// #endregion
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once __DIR__ . '/shared_navbar.php'; renderPageHead($page_title ?? ''); ?>
    <?php require_once 'shared_styles.php'; ?>
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .main-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            margin: 20px auto;
            padding: 30px;
            max-width: 700px;
        }
        
        .page-header {
            text-align: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e9ecef;
        }
        
        .page-header h1 {
            color: #2c3e50;
            font-weight: 700;
            margin-bottom: 8px;
            font-size: 1.8rem;
        }
        
        .page-header p {
            color: #6c757d;
            font-size: 1rem;
            margin-bottom: 0;
        }
        
        .export-section {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid #e9ecef;
        }
        
        .export-icon {
            text-align: center;
            margin-bottom: 15px;
        }
        
        .export-icon i {
            font-size: 2.5rem;
            color: #667eea;
            background: white;
            width: 60px;
            height: 60px;
            line-height: 60px;
            border-radius: 50%;
            box-shadow: 0 3px 10px rgba(102, 126, 234, 0.3);
        }
        
        .export-title {
            text-align: center;
            color: #2c3e50;
            font-weight: 600;
            margin-bottom: 15px;
            font-size: 1.3rem;
        }
        
        .export-description {
            text-align: center;
            color: #6c757d;
            margin-bottom: 20px;
            line-height: 1.5;
            font-size: 0.9rem;
        }
        
        .form-section {
            background: white;
            border-radius: 10px;
            padding: 20px;
            border: 1px solid #dee2e6;
        }
        
        .form-section h5 {
            color: #2c3e50;
            margin-bottom: 15px;
            font-weight: 600;
            text-align: center;
            font-size: 1rem;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 6px;
            font-size: 0.9rem;
        }
        
        .form-control {
            border-radius: 8px;
            border: 2px solid #e9ecef;
            padding: 10px 12px;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .btn-export {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 8px;
            padding: 12px 30px;
            font-size: 1rem;
            font-weight: 600;
            color: white;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
            width: 100%;
        }
        
        .btn-export:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.5);
            color: white;
        }
        
        .quick-actions {
            text-align: center;
            margin-top: 20px;
        }
        
        .quick-actions h6 {
            color: #6c757d;
            margin-bottom: 12px;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .btn-quick {
            background: #6c757d;
            border: none;
            border-radius: 6px;
            padding: 6px 12px;
            font-size: 0.8rem;
            color: white;
            margin: 0 3px;
            transition: all 0.3s ease;
        }
        
        .btn-quick:hover {
            background: #5a6268;
            color: white;
            transform: translateY(-1px);
        }
        
        .btn-quick.active {
            background: #667eea;
        }
        
        .info-box {
            background: #e3f2fd;
            border: 1px solid #bbdefb;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .info-box i {
            color: #1976d2;
            font-size: 1.2rem;
            margin-bottom: 8px;
        }
        
        .info-box p {
            color: #1565c0;
            margin-bottom: 0;
            font-weight: 500;
            font-size: 0.9rem;
        }
        
        .stats-row {
            display: flex;
            justify-content: space-around;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .stat-item {
            flex: 1;
            padding: 12px;
            margin: 0 8px;
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #e9ecef;
        }
        
        .stat-number {
            font-size: 1.5rem;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 4px;
        }
        
        .stat-label {
            font-size: 0.8rem;
            color: #6c757d;
            font-weight: 500;
        }
        
        @media (max-width: 768px) {
            .main-container {
                margin: 15px;
                padding: 20px;
            }
            
            .stats-row {
                flex-direction: column;
            }
            
            .stat-item {
                margin: 3px 0;
            }
            
            .btn-quick {
                margin: 2px;
                padding: 5px 10px;
                font-size: 0.75rem;
            }
        }
    </style>
</head>
<body>
    <?php 
    require_once 'shared_navbar.php';
    echo getNavbar('Reportes CSV', $_SESSION['user_role'] ?? '');
    ?>

    <!-- Main Content -->
    <div class="container">
        <div class="main-container">
            <!-- Page Header -->
            <div class="page-header">
                <h1><i class="fas fa-file-csv"></i> Exportación CSV</h1>
                <p>Genera reportes completos de la gestión de todo tu equipo</p>
            </div>
            
            <!-- Stats Row -->
            <div class="stats-row">
                <div class="stat-item">
                    <div class="stat-number"><?php echo count($asesores); ?></div>
                    <div class="stat-label">Asesores</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo date('M Y'); ?></div>
                    <div class="stat-label">Período</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">CSV</div>
                    <div class="stat-label">Formato</div>
                </div>
            </div>
            
            <!-- Info Box -->
            <div class="info-box">
                <i class="fas fa-info-circle"></i>
                <p><strong>Información:</strong> Exporta toda la gestión de tu equipo en un solo archivo CSV.</p>
                <p><strong>Nota:</strong> Asegúrate de seleccionar un período que contenga gestiones registradas.</p>
            </div>
            
            <!-- Export Section -->
            <div class="export-section">
                <div class="export-icon">
                    <i class="fas fa-users"></i>
                </div>
                
                <h3 class="export-title">Exportar Gestión de Todos los Asesores</h3>
                
                <p class="export-description">
                    Genera un reporte completo en CSV con toda la gestión de tu equipo.
                </p>
                
                <!-- Export Form -->
                <div class="form-section">
                    <h5><i class="fas fa-cog"></i> Configuración de Exportación</h5>
                    
                    <form action="index.php" method="GET" id="exportForm">
                        <input type="hidden" name="action" value="exportar_gestion_todos_asesores">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="fecha_inicio" class="form-label">
                                        <i class="fas fa-calendar"></i> Fecha de Inicio
                                    </label>
                                    <input type="date" 
                                           class="form-control" 
                                           id="fecha_inicio" 
                                           name="fecha_inicio" 
                                           value="<?php echo date('Y-m-01'); ?>"
                                           required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="fecha_fin" class="form-label">
                                        <i class="fas fa-calendar"></i> Fecha de Fin
                                    </label>
                                    <input type="date" 
                                           class="form-control" 
                                           id="fecha_fin" 
                                           name="fecha_fin" 
                                           value="<?php echo date('Y-m-t'); ?>"
                                           required>
                                </div>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-export">
                            <i class="fas fa-download"></i> Exportar CSV del Equipo
                        </button>
                    </form>
                </div>
                
                <!-- Quick Actions: type="button" evita que envíen el formulario sin las fechas -->
                <div class="quick-actions">
                    <h6><i class="fas fa-bolt"></i> Períodos Rápidos</h6>
                    <button type="button" class="btn btn-quick" onclick="setPeriod('hoy', this)">
                        <i class="fas fa-calendar-check"></i> Hoy
                    </button>
                    <button type="button" class="btn btn-quick" onclick="setPeriod('semana', this)">
                        <i class="fas fa-calendar-day"></i> Semana
                    </button>
                    <button type="button" class="btn btn-quick" onclick="setPeriod('mes', this)">
                        <i class="fas fa-calendar-week"></i> Mes
                    </button>
                    <button type="button" class="btn btn-quick" onclick="setPeriod('ayer', this)">
                        <i class="fas fa-calendar-minus"></i> Ayer
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Formatea fecha a YYYY-MM-DD en hora LOCAL (evita que "Hoy" sea el día anterior por UTC)
        function formatLocalDate(d) {
            const y = d.getFullYear();
            const m = String(d.getMonth() + 1).padStart(2, '0');
            const day = String(d.getDate()).padStart(2, '0');
            return y + '-' + m + '-' + day;
        }

        // Función para establecer períodos rápidos (usa fecha local para que "Hoy" sea realmente hoy)
        function setPeriod(period, buttonElement) {
            const today = new Date();
            let startDate, endDate;
            
            document.querySelectorAll('.btn-quick').forEach(btn => btn.classList.remove('active'));
            
            switch(period) {
                case 'hoy':
                    startDate = formatLocalDate(today);
                    endDate = formatLocalDate(today);
                    if (buttonElement) buttonElement.classList.add('active');
                    break;
                case 'ayer':
                    const ayer = new Date(today);
                    ayer.setDate(today.getDate() - 1);
                    startDate = formatLocalDate(ayer);
                    endDate = formatLocalDate(ayer);
                    if (buttonElement) buttonElement.classList.add('active');
                    break;
                case 'semana':
                    const startOfWeek = new Date(today);
                    startOfWeek.setDate(today.getDate() - today.getDay());
                    startDate = formatLocalDate(startOfWeek);
                    endDate = formatLocalDate(today);
                    if (buttonElement) buttonElement.classList.add('active');
                    break;
                case 'mes':
                    startDate = formatLocalDate(new Date(today.getFullYear(), today.getMonth(), 1));
                    endDate = formatLocalDate(new Date(today.getFullYear(), today.getMonth() + 1, 0));
                    if (buttonElement) buttonElement.classList.add('active');
                    break;
            }
            
            document.getElementById('fecha_inicio').value = startDate;
            document.getElementById('fecha_fin').value = endDate;
        }
        
        // Validación del formulario
        document.getElementById('exportForm').addEventListener('submit', function(e) {
            const startDate = new Date(document.getElementById('fecha_inicio').value);
            const endDate = new Date(document.getElementById('fecha_fin').value);
            
            if (startDate > endDate) {
                e.preventDefault();
                alert('La fecha de inicio no puede ser mayor que la fecha de fin.');
                return false;
            }
        });
        
        // Animación de carga al exportar
        document.getElementById('exportForm').addEventListener('submit', function() {
            const button = this.querySelector('.btn-export');
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generando CSV...';
            button.disabled = true;
        });
        
        // Establecer período por defecto (ayer) usando fecha local
        document.addEventListener('DOMContentLoaded', function() {
            const ayerButton = document.querySelector('button[onclick*="ayer"]');
            if (ayerButton) ayerButton.classList.add('active');
            
            const today = new Date();
            const ayer = new Date(today);
            ayer.setDate(today.getDate() - 1);
            const startDate = formatLocalDate(ayer);
            const endDate = formatLocalDate(ayer);
            
            document.getElementById('fecha_inicio').value = startDate;
            document.getElementById('fecha_fin').value = endDate;
        });
    </script>
</body>
</html>
