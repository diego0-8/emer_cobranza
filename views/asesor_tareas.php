<?php
$page_title = "Mis Tareas";
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <?php require_once 'shared_styles.php'; ?>
    <style>
        /* Contenedor principal centralizado - Consistente con otras vistas */
        .tareas-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
            background: #f8fafc;
            min-height: 100vh;
        }
        
        /* Header principal consistente con otras vistas */
        .dashboard-header {
            text-align: center;
            margin-bottom: 30px;
            background: white;
            padding: 25px;
            border-radius: 16px;
            box-shadow: 0 2px 20px rgba(0,0,0,0.08);
            border: 1px solid #e2e8f0;
            position: relative;
        }
        
        .dashboard-header h1 {
            color: #1f2937;
            font-weight: 700;
            margin-bottom: 8px;
            font-size: 2rem;
        }
        
        .dashboard-header p {
            color: #6b7280;
            font-size: 1rem;
            margin-bottom: 0;
        }
        
        /* Tarjetas de estadísticas mejoradas */
        .stats-card {
            background: white;
            border-radius: 16px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 2px 20px rgba(0,0,0,0.08);
            border: 1px solid #e2e8f0;
            position: relative;
            transition: transform 0.3s ease;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
        }
        
        /* Contenido principal */
        .main-content {
            background: white;
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 2px 20px rgba(0,0,0,0.08);
            border: 1px solid #e2e8f0;
        }
        
        /* Pestañas mejoradas - Lado a lado */
        .nav-tabs {
            border-bottom: none;
            margin-bottom: 25px;
            display: flex;
            gap: 10px;
        }
        
        .nav-tabs .nav-item {
            flex: 1;
        }
        
        .nav-tabs .nav-link {
            border: none;
            border-radius: 8px;
            padding: 12px 20px;
            font-weight: 600;
            color: #6b7280;
            transition: all 0.3s ease;
            text-align: center;
            background: #f8fafc;
            border: 2px solid #e2e8f0;
        }
        
        .nav-tabs .nav-link.active {
            background: #3b82f6;
            color: white;
            border-color: #1d4ed8;
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
        }
        
        .nav-tabs .nav-link:hover {
            background: #f1f5f9;
            color: #1f2937;
            border-color: #d1d5db;
        }
        
        /* Botones de navegación - Dashboard */
        .btn-dashboard {
            background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 12px 24px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(107, 114, 128, 0.3);
        }
        
        .btn-dashboard:hover {
            background: linear-gradient(135deg, #4b5563 0%, #374151 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(107, 114, 128, 0.4);
        }
        
        /* Botones de navegación - Mis Clientes */
        .btn-mis-clientes {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 12px 24px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
        }
        
        .btn-mis-clientes:hover {
            background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(59, 130, 246, 0.4);
        }
        
        /* Botón de pestaña - Pendientes (Activo) */
        .btn-pendientes {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            color: white;
            border: 2px solid #1d4ed8;
            border-radius: 8px;
            padding: 12px 20px;
            font-weight: 600;
            transition: all 0.3s ease;
            text-align: center;
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
        }
        
        .btn-pendientes:hover {
            background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(59, 130, 246, 0.4);
        }
        
        /* Botón de pestaña - Completadas (Inactivo) */
        .btn-completadas {
            background: #f8fafc;
            color: #6b7280;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            padding: 12px 20px;
            font-weight: 600;
            transition: all 0.3s ease;
            text-align: center;
        }
        
        .btn-completadas:hover {
            background: #f1f5f9;
            color: #1f2937;
            border-color: #d1d5db;
            transform: translateY(-1px);
        }
        
        /* Botones mejorados - Clases genéricas para compatibilidad */
        .btn-primary {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 12px 24px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(59, 130, 246, 0.4);
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 12px 24px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(107, 114, 128, 0.3);
        }
        
        .btn-secondary:hover {
            background: linear-gradient(135deg, #4b5563 0%, #374151 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(107, 114, 128, 0.4);
        }
        
        /* Tabla mejorada */
        .table {
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .table thead th {
            background: #1f2937;
            color: white;
            border: none;
            font-weight: 600;
            padding: 15px;
        }
        
        .table tbody td {
            padding: 15px;
            vertical-align: middle;
            border-color: #f1f5f9;
        }
        
        .table tbody tr:hover {
            background-color: #f8fafc;
        }
        
        /* Badges mejorados */
        .badge {
            font-size: 0.8rem;
            padding: 8px 12px;
            border-radius: 6px;
            font-weight: 600;
        }
        
        /* Estado vacío mejorado */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6b7280;
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        
        .empty-state h4 {
            color: #374151;
            margin-bottom: 10px;
        }
        
        /* Estilos para paginación */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 30px;
            flex-wrap: wrap;
        }
        
        .pagination a, .pagination span {
            padding: 10px 15px;
            border: 1px solid #e2e8f0;
            background: white;
            color: #374151;
            text-decoration: none;
            border-radius: 6px;
            transition: all 0.3s ease;
            min-width: 40px;
            text-align: center;
            font-weight: 500;
        }
        
        .pagination a:hover {
            background: #f1f5f9;
            border-color: #3b82f6;
            color: #3b82f6;
            transform: translateY(-1px);
        }
        
        .pagination .btn-primary {
            background: #3b82f6;
            color: white;
            border-color: #3b82f6;
        }
        
        .pagination .btn-secondary {
            background: #6b7280;
            color: white;
            border-color: #6b7280;
        }
        
        .pagination .btn-outline-primary {
            background: white;
            color: #3b82f6;
            border-color: #3b82f6;
        }
        
        .pagination .btn-outline-primary:hover {
            background: #3b82f6;
            color: white;
        }
    </style>
</head>
<body>
    <?php 
    require_once 'shared_navbar.php';
    echo getNavbar($page_title, $_SESSION['user_role'] ?? '');
    ?>

<div class="tareas-container">
    <!-- Header principal consistente con otras vistas -->
    <div class="dashboard-header">
        <h1><i class="fas fa-tasks"></i> Mis Tareas</h1>
        <p>Gestiona y completa las tareas asignadas por tu coordinador</p>
    </div>
            <!-- Estadísticas -->
            <div class="row mb-4">
                <div class="col-md-6">
            <div class="stats-card bg-warning text-white">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4><?php echo $totalTareasPendientes; ?></h4>
                                    <p class="mb-0">Tareas Pendientes</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-clock fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                <div class="col-md-6">
            <div class="stats-card bg-info text-white">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4><?php echo count($clientesTareasPendientes); ?></h4>
                                    <p class="mb-0">Clientes Asignados</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-users fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
    </div>

    <!-- Contenido principal -->
    <div class="main-content">
        <!-- Botones de navegación -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div></div>
            <div class="d-flex gap-3">
                <a href="index.php?action=dashboard" class="btn btn-dashboard">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <a href="index.php?action=mis_clientes" class="btn btn-mis-clientes">
                    <i class="fas fa-users"></i> Mis Clientes
                </a>
                </div>
            </div>

            <!-- Tareas Pendientes -->
            <div class="mb-4">
                <h3><i class="fas fa-clock"></i> Mis Tareas (<?php echo $totalTareasPendientes; ?>)</h3>
            </div>
            
            <div class="tareas-content">
                <?php if (empty($tareasPendientes)): ?>
                    <div class="empty-state">
                        <i class="fas fa-check-circle text-success"></i>
                                    <h4>¡Excelente!</h4>
                        <p>No tienes tareas pendientes en este momento.</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                            <thead>
                                            <tr>
                                                <th>Tarea</th>
                                    <th>Clientes Asignados</th>
                                                <th>Fecha Asignación</th>
                                    <th>Estado</th>
                                                <th>Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                <?php foreach ($tareasPendientes as $tarea): ?>
                                                <tr>
                                                    <td>
                                            <span class="badge bg-warning"><?php echo htmlspecialchars($tarea['descripcion']); ?></span>
                                        </td>
                                        <td>
                                            <span class="badge bg-info"><?php echo count($tarea['cliente_ids']); ?> cliente(s)</span>
                                        </td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($tarea['fecha_creacion'])); ?></td>
                                        <td>
                                            <span class="badge bg-warning">Pendiente</span>
                                                    </td>
                                                    <td>
                                            <a href="index.php?action=gestionar_tarea&id=<?php echo $tarea['id']; ?>" 
                                                           class="btn btn-primary btn-sm">
                                                <i class="fas fa-edit"></i> Gestionar Tarea
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                    
                    <!-- Paginación -->
                    <?php if ($total_paginas > 1): ?>
                        <div class="pagination mt-4">
                            <?php if ($pagina_actual > 1): ?>
                                <a href="?action=mis_tareas&pagina=<?php echo $pagina_actual - 1; ?>" class="btn btn-secondary">
                                    ← Anterior
                                </a>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                                <?php if ($i == $pagina_actual): ?>
                                    <span class="btn btn-primary"><?php echo $i; ?></span>
                            <?php else: ?>
                                    <a href="?action=mis_tareas&pagina=<?php echo $i; ?>" class="btn btn-outline-primary">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endif; ?>
                            <?php endfor; ?>
                            
                            <?php if ($pagina_actual < $total_paginas): ?>
                                <a href="?action=mis_tareas&pagina=<?php echo $pagina_actual + 1; ?>" class="btn btn-secondary">
                                    Siguiente →
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'shared_footer.php'; ?>
</body>
</html>
