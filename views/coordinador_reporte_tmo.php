<?php
$page_title = $page_title ?? '';
$asesores = isset($asesores) && is_array($asesores) ? $asesores : [];
$fecha_inicio = isset($fecha_inicio) ? (string)$fecha_inicio : (string)($_GET['fecha_inicio'] ?? date('Y-m-d', strtotime('-30 days')));
$fecha_fin = isset($fecha_fin) ? (string)$fecha_fin : (string)($_GET['fecha_fin'] ?? date('Y-m-d'));
$asesor_id = isset($asesor_id) ? (string)$asesor_id : (string)($_GET['asesor_id'] ?? '');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once __DIR__ . '/shared_navbar.php'; renderPageHead($page_title ?? ''); ?>
    <?php require_once __DIR__ . '/shared_styles.php'; ?>
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
    require_once __DIR__ . '/shared_navbar.php';
    echo getNavbar('Reporte TMO', $_SESSION['user_role'] ?? ''); 
    ?>

    <!-- Main Content -->
    <div class="container">
        <div class="main-container">
            <!-- Page Header -->
            <div class="page-header">
                <h1><i class="fas fa-clock"></i> Reporte TMO</h1>
                <p>Genera reportes detallados del tiempo de sesión y pausas de los asesores</p>
            </div>
            
            <!-- Stats Row -->
            <div class="stats-row">
                <div class="stat-item">
                    <div class="stat-number"><?php echo count($asesores); ?></div>
                    <div class="stat-label">Asesores</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo htmlspecialchars($fecha_inicio . ' a ' . $fecha_fin, ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="stat-label">Período</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">TMO</div>
                    <div class="stat-label">Reporte</div>
                </div>
            </div>
            
            <!-- Info Box -->
            <div class="info-box">
                <i class="fas fa-info-circle"></i>
                <p><strong>Información:</strong> Exporta el tiempo de sesión y todas las pausas de los asesores en formato CSV.</p>
                <p><strong>Incluye:</strong> Fecha y hora de inicio, asesor, hora fin, tiempo en sesión, motivo de pausa y duración.</p>
            </div>
            
            <!-- Export Section -->
            <div class="export-section">
                <div class="export-icon">
                    <i class="fas fa-clock"></i>
                </div>
                
                <h3 class="export-title">Exportar Reporte de Tiempo (TMO)</h3>
                
                <p class="export-description">
                    Genera un reporte completo en CSV con el tiempo de sesión y todas las pausas realizadas por los asesores.
                </p>
                
                <!-- Export Form -->
                <div class="form-section">
                    <h5><i class="fas fa-cog"></i> Configuración de Exportación</h5>
                    
                    <form action="index.php" method="GET" id="exportForm">
                        <input type="hidden" name="action" value="exportar_reporte_tmo">
                        
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
                                           value="<?php echo htmlspecialchars($fecha_inicio, ENT_QUOTES, 'UTF-8'); ?>"
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
                                           value="<?php echo htmlspecialchars($fecha_fin, ENT_QUOTES, 'UTF-8'); ?>"
                                           required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="asesor_id" class="form-label">
                                <i class="fas fa-user"></i> Asesor (Opcional)
                            </label>
                            <select class="form-control" id="asesor_id" name="asesor_id">
                                <option value="">Todos los asesores</option>
                                <?php foreach ($asesores as $asesor): ?>
                                    <option value="<?php echo htmlspecialchars((string)($asesor['id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" 
                                            <?php echo ($asesor_id !== '' && $asesor_id == ($asesor['id'] ?? null)) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars((string)($asesor['nombre_completo'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn btn-export">
                            <i class="fas fa-download"></i> Exportar Reporte TMO CSV
                        </button>
                    </form>
                </div>
                
                <!-- Quick Actions -->
                <div class="quick-actions">
                    <h6><i class="fas fa-bolt"></i> Períodos Rápidos</h6>
                    <button class="btn btn-quick" onclick="setPeriod('hoy', this)">
                        <i class="fas fa-calendar-check"></i> Hoy
                    </button>
                    <button class="btn btn-quick" onclick="setPeriod('semana', this)">
                        <i class="fas fa-calendar-day"></i> Semana
                    </button>
                    <button class="btn btn-quick" onclick="setPeriod('mes', this)">
                        <i class="fas fa-calendar-week"></i> Mes
                    </button>
                    <button class="btn btn-quick" onclick="setPeriod('ayer', this)">
                        <i class="fas fa-calendar-minus"></i> Ayer
                    </button>
                    <button class="btn btn-quick" onclick="setPeriod('30dias', this)">
                        <i class="fas fa-calendar-alt"></i> 30 Días
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Función para establecer períodos rápidos
        function setPeriod(period, buttonElement) {
            const today = new Date();
            let startDate, endDate;
            
            // Remover clase active de todos los botones
            document.querySelectorAll('.btn-quick').forEach(btn => {
                btn.classList.remove('active');
            });
            
            switch(period) {
                case 'hoy':
                    startDate = today.toISOString().split('T')[0];
                    endDate = today.toISOString().split('T')[0];
                    if (buttonElement) buttonElement.classList.add('active');
                    break;
                case 'ayer':
                    const ayer = new Date(today);
                    ayer.setDate(today.getDate() - 1);
                    startDate = ayer.toISOString().split('T')[0];
                    endDate = ayer.toISOString().split('T')[0];
                    if (buttonElement) buttonElement.classList.add('active');
                    break;
                case 'semana':
                    const startOfWeek = new Date(today);
                    startOfWeek.setDate(today.getDate() - today.getDay());
                    startDate = startOfWeek.toISOString().split('T')[0];
                    endDate = today.toISOString().split('T')[0];
                    if (buttonElement) buttonElement.classList.add('active');
                    break;
                case 'mes':
                    startDate = new Date(today.getFullYear(), today.getMonth(), 1).toISOString().split('T')[0];
                    endDate = new Date(today.getFullYear(), today.getMonth() + 1, 0).toISOString().split('T')[0];
                    if (buttonElement) buttonElement.classList.add('active');
                    break;
                case '30dias':
                    const hace30Dias = new Date(today);
                    hace30Dias.setDate(today.getDate() - 30);
                    startDate = hace30Dias.toISOString().split('T')[0];
                    endDate = today.toISOString().split('T')[0];
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
        
        // Establecer período por defecto (30 días)
        document.addEventListener('DOMContentLoaded', function() {
            // Establecer las fechas por defecto (últimos 30 días)
            const today = new Date();
            const hace30Dias = new Date(today);
            hace30Dias.setDate(today.getDate() - 30);
            const startDate = hace30Dias.toISOString().split('T')[0];
            const endDate = today.toISOString().split('T')[0];
            
            // Solo establecer si los campos están vacíos
            const fechaInicioInput = document.getElementById('fecha_inicio');
            const fechaFinInput = document.getElementById('fecha_fin');
            
            if (!fechaInicioInput.value) {
                fechaInicioInput.value = startDate;
            }
            if (!fechaFinInput.value) {
                fechaFinInput.value = endDate;
            }
            
            // Activar el botón de 30 días si coincide
            const btn30Dias = document.querySelector('button[onclick*="30dias"]');
            if (btn30Dias && fechaInicioInput.value === startDate && fechaFinInput.value === endDate) {
                btn30Dias.classList.add('active');
            }
        });
    </script>
</body>
</html>
