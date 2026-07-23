<?php
$page_title = $page_title ?? 'Auditoría de Campaña';
$campana = $campana ?? [];
$registros = $registros ?? [];
$campanaId = (int)($campana['id'] ?? 0);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once __DIR__ . '/shared_navbar.php'; renderPageHead($page_title); ?>
    <?php require_once __DIR__ . '/shared_styles.php'; ?>
    <link rel="stylesheet" href="assets/css/admin_dashboard.css">
    <link rel="stylesheet" href="assets/css/admin_campanas.css">
</head>
<body>
    <?php
    require_once __DIR__ . '/shared_navbar.php';
    echo getNavbar('Campañas', $_SESSION['user_role'] ?? '');
    ?>

    <div class="main-container">
        <div class="page-intro">
            <h1>Auditoría: <?php echo htmlspecialchars($campana['nombre'] ?? ''); ?></h1>
            <p>Historial de acciones realizadas por coordinadores en esta campaña.</p>
        </div>

        <section class="card">
            <div class="card-header">Registro de actividades</div>
            <div class="card-body">
                <?php if (empty($registros)): ?>
                    <div class="empty-state">
                        <strong>Sin registros</strong>
                        Aún no hay acciones de coordinadores registradas en esta campaña.
                    </div>
                <?php else: ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Coordinador</th>
                                    <th>Acción</th>
                                    <th>Entidad</th>
                                    <th>Detalle</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($registros as $r): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($r['fecha'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($r['coordinador_nombre'] ?? $r['coordinador_cedula'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($r['accion'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars(($r['entidad'] ?? '') . ($r['entidad_id'] ? ' #' . $r['entidad_id'] : '')); ?></td>
                                    <td><span class="audit-detail"><?php echo htmlspecialchars($r['detalle'] ?? ''); ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <div class="breadcrumb-links">
            <a href="index.php?action=gestionar_campana&id=<?php echo $campanaId; ?>">← Volver a la campaña</a>
            <span>·</span>
            <a href="index.php?action=list_campanas">Listado de campañas</a>
        </div>
    </div>

    <?php require_once __DIR__ . '/shared_footer.php'; ?>
</body>
</html>
