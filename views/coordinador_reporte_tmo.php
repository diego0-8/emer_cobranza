<?php
$page_title = $page_title ?? '';
$asesores = isset($asesores) && is_array($asesores) ? $asesores : [];
$estado_asesores = isset($estado_asesores) && is_array($estado_asesores) ? $estado_asesores : [];
$contadores_estado = isset($contadores_estado) && is_array($contadores_estado) ? $contadores_estado : ['en_linea' => 0, 'en_pausa' => 0, 'offline' => 0];
$total_registros_tmo = isset($total_registros_tmo) ? (int)$total_registros_tmo : 0;

function formatoHoraTmo(?string $datetime): string {
    if (!$datetime) {
        return '—';
    }
    $ts = strtotime($datetime);
    return $ts ? date('H:i:s', $ts) : '—';
}

function badgeEstadoTmo(string $estado): string {
    $map = [
        'en_linea' => 'badge-estado badge-en-linea',
        'en_llamada' => 'badge-estado badge-en-llamada',
        'en_pausa' => 'badge-estado badge-en-pausa',
        'offline' => 'badge-estado badge-offline',
    ];
    return $map[$estado] ?? 'badge-estado badge-offline';
}

function esAsesorActivoTmo(array $row): bool {
    $estado = $row['estado'] ?? 'offline';
    return $estado === 'en_linea' || $estado === 'en_pausa';
}

function duracionDesdeTmo(?string $desde): string {
    if (!$desde) {
        return '00:00:00';
    }
    $ts = strtotime($desde);
    if (!$ts) {
        return '00:00:00';
    }
    $seg = max(0, time() - $ts);
    $h = (int) floor($seg / 3600);
    $m = (int) floor(($seg % 3600) / 60);
    $s = $seg % 60;
    return sprintf('%02d:%02d:%02d', $h, $m, $s);
}

function celdaTiempoPausaTmo(array $row): string {
    if (($row['estado'] ?? '') !== 'en_pausa' || empty($row['pausa_desde'])) {
        return '—';
    }
    $desde = htmlspecialchars($row['pausa_desde'], ENT_QUOTES, 'UTF-8');
    $texto = duracionDesdeTmo($row['pausa_desde']);
    return '<span class="tmo-pausa-timer" data-pausa-desde="' . $desde . '">' . htmlspecialchars($texto) . '</span>';
}

function dividirAsesoresTmo(array $estadoAsesores): array {
    $activos = [];
    $offline = [];
    foreach ($estadoAsesores as $row) {
        if (esAsesorActivoTmo($row)) {
            $activos[] = $row;
        } else {
            $offline[] = $row;
        }
    }
    return ['activos' => $activos, 'offline' => $offline];
}

$grupos_tmo = dividirAsesoresTmo($estado_asesores);
$asesores_activos_tmo = $grupos_tmo['activos'];
$asesores_offline_tmo = $grupos_tmo['offline'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once __DIR__ . '/shared_navbar.php'; renderPageHead($page_title ?? ''); ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
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
            max-width: 1150px;
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

        .stats-row {
            display: flex;
            justify-content: space-around;
            margin-bottom: 20px;
            text-align: center;
            flex-wrap: wrap;
            gap: 8px;
        }

        .stat-item {
            flex: 1;
            min-width: 100px;
            padding: 12px;
            margin: 0 4px;
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

        .stat-item.stat-en-linea .stat-number { color: #198754; }
        .stat-item.stat-en-pausa .stat-number { color: #fd7e14; }
        .stat-item.stat-offline .stat-number { color: #6c757d; }

        .live-section {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid #e9ecef;
        }

        .live-section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 15px;
        }

        .live-section-header h3 {
            color: #2c3e50;
            font-weight: 600;
            font-size: 1.2rem;
            margin: 0;
        }

        .live-refresh-hint {
            font-size: 0.85rem;
            color: #6c757d;
        }

        .live-refresh-hint i {
            color: #667eea;
        }

        .live-table-wrap {
            background: white;
            border-radius: 10px;
            border: 1px solid #dee2e6;
            overflow: hidden;
        }

        .live-table {
            margin-bottom: 0;
            font-size: 0.9rem;
        }

        .live-table thead th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-weight: 600;
            border: none;
            padding: 12px 14px;
            white-space: nowrap;
        }

        .live-table tbody td {
            padding: 11px 14px;
            vertical-align: middle;
            border-color: #e9ecef;
        }

        .live-table tbody tr:hover {
            background-color: #f8f9ff;
        }

        .badge-estado {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.78rem;
            font-weight: 600;
            white-space: nowrap;
        }

        .badge-en-linea {
            background: #d1e7dd;
            color: #0f5132;
        }

        .badge-en-llamada {
            background: #cfe2ff;
            color: #084298;
        }

        .badge-en-pausa {
            background: #fff3cd;
            color: #664d03;
        }

        .badge-offline {
            background: #e9ecef;
            color: #495057;
        }

        .live-table-block {
            margin-bottom: 0;
        }

        .offline-toggle-wrap {
            margin-top: 12px;
            border-top: 1px solid #dee2e6;
            padding-top: 12px;
        }

        .btn-offline-toggle {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 10px 14px;
            color: #495057;
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            transition: background 0.2s ease, border-color 0.2s ease;
        }

        .btn-offline-toggle:hover {
            background: #f8f9fa;
            border-color: #ced4da;
            color: #2c3e50;
        }

        .btn-offline-toggle .offline-count {
            background: #e9ecef;
            color: #495057;
            border-radius: 12px;
            padding: 2px 10px;
            font-size: 0.8rem;
            font-weight: 700;
        }

        .btn-offline-toggle .chevron {
            color: #667eea;
            transition: transform 0.25s ease;
        }

        .btn-offline-toggle[aria-expanded="true"] .chevron {
            transform: rotate(180deg);
        }

        .offline-collapse-panel {
            margin-top: 10px;
            display: none;
            overflow: hidden;
        }

        .offline-collapse-panel.show {
            display: block;
        }

        .offline-collapse-panel.collapsing {
            display: block;
            height: 0;
            transition: height 0.3s ease;
        }

        .offline-collapse-panel .live-table-wrap {
            border-top-left-radius: 0;
            border-top-right-radius: 0;
        }

        .offline-collapse-panel .live-table thead th {
            background: #6c757d;
        }

        .live-table thead th.col-tiempo-pausa {
            color: #fff;
        }

        .live-table tbody td.col-tiempo-pausa {
            min-width: 100px;
            font-variant-numeric: tabular-nums;
            font-weight: 600;
            color: #212529;
        }

        .live-table .col-detalles {
            min-width: 72px;
            text-align: center;
        }

        .tmo-pausa-timer {
            font-family: 'Consolas', 'Courier New', monospace;
        }

        .btn-ver-pausas {
            font-size: 0.8rem;
            padding: 4px 12px;
            border-radius: 6px;
        }

        #modalDetallePausasAsesor .modal-jornada {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 12px 16px;
            margin-bottom: 16px;
        }

        #modalDetallePausasAsesor .modal-jornada dt {
            font-weight: 600;
            color: #495057;
        }

        #modalDetallePausasAsesor .tabla-pausas-dia th {
            background: #667eea;
            color: #fff;
            font-size: 0.85rem;
        }

        #modalDetallePausasAsesor .tabla-pausas-dia td {
            font-size: 0.9rem;
            vertical-align: middle;
        }

        .export-compact {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 18px 20px;
            border: 1px solid #e9ecef;
        }

        .export-compact h5 {
            color: #2c3e50;
            font-weight: 600;
            font-size: 1rem;
            margin-bottom: 14px;
        }

        .form-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 6px;
            font-size: 0.85rem;
        }

        .form-control {
            border-radius: 8px;
            border: 2px solid #e9ecef;
            padding: 8px 12px;
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
            padding: 10px 20px;
            font-size: 0.95rem;
            font-weight: 600;
            color: white;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
            width: 100%;
            height: 100%;
            min-height: 42px;
        }

        .btn-export:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.5);
            color: white;
        }

        .quick-actions {
            text-align: center;
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid #dee2e6;
        }

        .quick-actions h6 {
            color: #6c757d;
            margin-bottom: 8px;
            font-weight: 600;
            font-size: 0.85rem;
        }

        .btn-quick {
            background: #6c757d;
            border: none;
            border-radius: 6px;
            padding: 5px 10px;
            font-size: 0.75rem;
            color: white;
            margin: 2px;
            transition: all 0.3s ease;
        }

        .btn-quick:hover {
            background: #5a6268;
            color: white;
        }

        .btn-quick.active {
            background: #667eea;
        }

        .info-box {
            background: #e3f2fd;
            border: 1px solid #bbdefb;
            border-radius: 8px;
            padding: 12px 15px;
            margin-bottom: 18px;
            text-align: center;
        }

        .info-box p {
            color: #1565c0;
            margin-bottom: 0;
            font-weight: 500;
            font-size: 0.88rem;
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
        }
    </style>
</head>
<body>
    <?php
    require_once 'shared_navbar.php';
    echo getNavbar('Reporte TMO', $_SESSION['user_role'] ?? '');
    ?>

    <div class="container">
        <div class="main-container">
            <div class="page-header">
                <h1><i class="fas fa-clock"></i> Reporte TMO</h1>
                <p>Monitoreo en tiempo real del equipo y exportación histórica de pausas</p>
            </div>

            <?php if (!empty($_SESSION['error_message'])): ?>
                <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
                    <?php echo htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                </div>
            <?php endif; ?>

            <div class="stats-row" id="statsEstadoRow">
                <div class="stat-item stat-en-linea">
                    <div class="stat-number" id="statEnLinea"><?php echo (int)($contadores_estado['en_linea'] ?? 0); ?></div>
                    <div class="stat-label">En línea</div>
                </div>
                <div class="stat-item stat-en-pausa">
                    <div class="stat-number" id="statEnPausa"><?php echo (int)($contadores_estado['en_pausa'] ?? 0); ?></div>
                    <div class="stat-label">En pausa</div>
                </div>
                <div class="stat-item stat-offline">
                    <div class="stat-number" id="statOffline"><?php echo (int)($contadores_estado['offline'] ?? 0); ?></div>
                    <div class="stat-label">Offline</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo (int)$total_registros_tmo; ?></div>
                    <div class="stat-label">Pausas (período export.)</div>
                </div>
            </div>

            <!-- Tabla en tiempo real -->
            <section class="live-section">
                <div class="live-section-header">
                    <h3><i class="fas fa-users"></i> En línea y en pausa</h3>
                    <span class="live-refresh-hint" id="liveRefreshHint">
                        <i class="fas fa-sync-alt"></i> Actualizado al cargar
                    </span>
                </div>
                <div class="live-table-block">
                    <div class="live-table-wrap table-responsive">
                        <table class="table live-table" id="tablaEstadoPrincipal">
                        <thead>
                            <tr>
                                <th>Asesor</th>
                                <th>Inicio sesión</th>
                                <th>Fin sesión</th>
                                <th>Estado</th>
                                <th class="col-tiempo-pausa">Tiempo de pausa</th>
                                <th class="col-detalles">Detalles</th>
                            </tr>
                        </thead>
                            <tbody id="tablaEstadoAsesoresActivos">
                                <?php if (empty($asesores_activos_tmo)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">Ningún asesor en línea o en pausa en este momento.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($asesores_activos_tmo as $row): ?>
                                    <tr data-cedula="<?php echo htmlspecialchars($row['cedula'] ?? ''); ?>">
                                        <td><strong><?php echo htmlspecialchars($row['nombre'] ?? ''); ?></strong></td>
                                        <td><?php echo htmlspecialchars(formatoHoraTmo($row['inicio_sesion'] ?? null)); ?></td>
                                        <td><?php echo htmlspecialchars(formatoHoraTmo($row['fin_sesion'] ?? null)); ?></td>
                                        <td>
                                            <span class="<?php echo badgeEstadoTmo($row['estado'] ?? 'offline'); ?>">
                                                <?php echo htmlspecialchars($row['estado_label'] ?? 'Offline'); ?>
                                            </span>
                                        </td>
                                        <td class="col-tiempo-pausa"><?php echo celdaTiempoPausaTmo($row); ?></td>
                                        <td class="col-detalles">
                                            <button type="button" class="btn btn-sm btn-outline-primary btn-ver-pausas"
                                                data-cedula="<?php echo htmlspecialchars($row['cedula'] ?? ''); ?>"
                                                data-nombre="<?php echo htmlspecialchars($row['nombre'] ?? ''); ?>">
                                                Ver
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="offline-toggle-wrap">
                        <button
                            type="button"
                            class="btn-offline-toggle"
                            id="btnToggleOfflineAsesores"
                            aria-expanded="false"
                            aria-controls="offlineAsesoresCollapse">
                            <span><i class="fas fa-user-slash"></i> Ver asesores offline</span>
                            <span class="d-flex align-items-center gap-2">
                                <span class="offline-count" id="offlineAsesoresCount"><?php echo count($asesores_offline_tmo); ?></span>
                                <i class="fas fa-chevron-down chevron"></i>
                            </span>
                        </button>
                        <div class="collapse offline-collapse-panel" id="offlineAsesoresCollapse">
                            <div class="live-table-wrap table-responsive">
                                <table class="table live-table mb-0" aria-label="Asesores offline">
                                    <thead>
                                        <tr>
                                            <th>Asesor</th>
                                            <th>Inicio sesión</th>
                                            <th>Fin sesión</th>
                                            <th>Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tablaEstadoAsesoresOffline">
                                        <?php if (empty($asesores_offline_tmo)): ?>
                                            <tr>
                                                <td colspan="4" class="text-center text-muted py-3">No hay asesores offline.</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($asesores_offline_tmo as $row): ?>
                                                <tr data-cedula="<?php echo htmlspecialchars($row['cedula'] ?? ''); ?>">
                                                    <td><strong><?php echo htmlspecialchars($row['nombre'] ?? ''); ?></strong></td>
                                                    <td><?php echo htmlspecialchars(formatoHoraTmo($row['inicio_sesion'] ?? null)); ?></td>
                                                    <td><?php echo htmlspecialchars(formatoHoraTmo($row['fin_sesion'] ?? null)); ?></td>
                                                    <td>
                                                        <span class="<?php echo badgeEstadoTmo($row['estado'] ?? 'offline'); ?>">
                                                            <?php echo htmlspecialchars($row['estado_label'] ?? 'Offline'); ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Modal detalle pausas del día -->
            <div class="modal fade" id="modalDetallePausasAsesor" tabindex="-1" aria-labelledby="modalDetallePausasTitulo" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-scrollable">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="modalDetallePausasTitulo">Detalle de pausas</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                        </div>
                        <div class="modal-body" id="modalDetallePausasBody">
                            <div class="text-center text-muted py-4">
                                <i class="fas fa-spinner fa-spin"></i> Cargando...
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Exportación compacta -->
            <section class="export-compact">
                <h5><i class="fas fa-download"></i> Exportar reporte histórico (CSV)</h5>
                
                <form action="index.php" method="GET" id="exportForm">
                    <input type="hidden" name="action" value="exportar_reporte_tmo">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-3 col-6">
                            <label for="fecha_inicio" class="form-label">Fecha inicio</label>
                            <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio"
                                   value="<?php echo htmlspecialchars($fecha_inicio ?? date('Y-m-d', strtotime('-30 days'))); ?>" required>
                        </div>
                        <div class="col-md-3 col-6">
                            <label for="fecha_fin" class="form-label">Fecha fin</label>
                            <input type="date" class="form-control" id="fecha_fin" name="fecha_fin"
                                   value="<?php echo htmlspecialchars($fecha_fin ?? date('Y-m-d')); ?>" required>
                        </div>
                        <div class="col-md-4 col-12">
                            <label for="asesor_id" class="form-label">Asesor (opcional)</label>
                            <select class="form-control" id="asesor_id" name="asesor_id">
                                <option value="">Todos</option>
                                <?php foreach ($asesores as $asesor): ?>
                                    <option value="<?php echo htmlspecialchars($asesor['id']); ?>"
                                        <?php echo (isset($asesor_id) && $asesor_id == $asesor['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($asesor['nombre_completo']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2 col-12">
                            <button type="submit" class="btn btn-export">
                                <i class="fas fa-download"></i> Exportar
                            </button>
                        </div>
                    </div>
                </form>
                <div class="quick-actions">
                    <h6><i class="fas fa-bolt"></i> Períodos rápidos</h6>
                    <button type="button" class="btn btn-quick" onclick="setPeriod('hoy', this)">Hoy</button>
                    <button type="button" class="btn btn-quick" onclick="setPeriod('ayer', this)">Ayer</button>
                    <button type="button" class="btn btn-quick" onclick="setPeriod('semana', this)">Semana</button>
                    <button type="button" class="btn btn-quick" onclick="setPeriod('mes', this)">Mes</button>
                    <button type="button" class="btn btn-quick" onclick="setPeriod('30dias', this)">30 días</button>
                </div>
            </section>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        window.TMO_ESTADO_INICIAL = <?php echo json_encode([
            'asesores' => $estado_asesores,
            'contadores' => $contadores_estado,
        ], JSON_UNESCAPED_UNICODE); ?>;
    </script>
    <script src="assets/js/coordinador-reporte-tmo.js"></script>
    <script>
        function formatLocalDate(d) {
            const y = d.getFullYear();
            const m = String(d.getMonth() + 1).padStart(2, '0');
            const day = String(d.getDate()).padStart(2, '0');
            return y + '-' + m + '-' + day;
        }

        function setPeriod(period, buttonElement) {
            const today = new Date();
            let startDate, endDate;

            document.querySelectorAll('.btn-quick').forEach(btn => btn.classList.remove('active'));

            switch (period) {
                case 'hoy':
                    startDate = endDate = formatLocalDate(today);
                    break;
                case 'ayer':
                    const ayer = new Date(today);
                    ayer.setDate(today.getDate() - 1);
                    startDate = endDate = formatLocalDate(ayer);
                    break;
                case 'semana':
                    const startOfWeek = new Date(today);
                    startOfWeek.setDate(today.getDate() - today.getDay());
                    startDate = formatLocalDate(startOfWeek);
                    endDate = formatLocalDate(today);
                    break;
                case 'mes':
                    startDate = formatLocalDate(new Date(today.getFullYear(), today.getMonth(), 1));
                    endDate = formatLocalDate(new Date(today.getFullYear(), today.getMonth() + 1, 0));
                    break;
                case '30dias':
                    const hace30 = new Date(today);
                    hace30.setDate(today.getDate() - 30);
                    startDate = formatLocalDate(hace30);
                    endDate = formatLocalDate(today);
                    break;
            }

            document.getElementById('fecha_inicio').value = startDate;
            document.getElementById('fecha_fin').value = endDate;
            if (buttonElement) buttonElement.classList.add('active');
        }

        document.getElementById('exportForm').addEventListener('submit', function (e) {
            const startDate = new Date(document.getElementById('fecha_inicio').value);
            const endDate = new Date(document.getElementById('fecha_fin').value);
            if (startDate > endDate) {
                e.preventDefault();
                alert('La fecha de inicio no puede ser mayor que la fecha de fin.');
                return false;
            }
            const button = this.querySelector('.btn-export');
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generando...';
            button.disabled = true;
        });

        document.addEventListener('DOMContentLoaded', function () {
            const today = new Date();
            const hace30 = new Date(today);
            hace30.setDate(today.getDate() - 30);
            const fi = document.getElementById('fecha_inicio');
            const ff = document.getElementById('fecha_fin');
            if (!fi.value) fi.value = formatLocalDate(hace30);
            if (!ff.value) ff.value = formatLocalDate(today);
        });
    </script>
</body>
</html>
