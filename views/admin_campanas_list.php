<?php
$page_title = $page_title ?? 'Gestionar Campañas';
$campanas = $campanas ?? [];
$success = $success ?? '';
$error = $error ?? '';

$totalCampanas = count($campanas);
$totalActivas = count(array_filter($campanas, fn($c) => ($c['estado'] ?? '') === 'activa'));
$totalCoords = array_sum(array_column($campanas, 'total_coordinadores'));
$totalAsesores = array_sum(array_column($campanas, 'total_asesores'));
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
            <div class="page-intro-actions">
                <div>
                    <h1>Gestionar Campañas</h1>
                    <p>Organiza coordinadores, asesores y bases de datos por campaña de cobranza.</p>
                </div>
                <a href="index.php?action=crear_campana" class="btn btn-success">+ Nueva campaña</a>
            </div>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success">Operación realizada correctamente.</div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error">Ocurrió un error: <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="campanas-stats">
            <div class="campanas-stat-card">
                <div class="value"><?php echo $totalCampanas; ?></div>
                <div class="label">Campañas</div>
            </div>
            <div class="campanas-stat-card">
                <div class="value"><?php echo $totalActivas; ?></div>
                <div class="label">Activas</div>
            </div>
            <div class="campanas-stat-card">
                <div class="value"><?php echo $totalCoords; ?></div>
                <div class="label">Coordinadores</div>
            </div>
            <div class="campanas-stat-card">
                <div class="value"><?php echo $totalAsesores; ?></div>
                <div class="label">Asesores</div>
            </div>
        </div>

        <section class="card">
            <div class="card-header">Listado de campañas</div>
            <div class="card-body">
                <?php if (empty($campanas)): ?>
                    <div class="empty-state">
                        <strong>No hay campañas registradas</strong>
                        Crea la primera campaña para asignar coordinadores y asesores.
                    </div>
                    <div class="card-actions">
                        <a href="index.php?action=crear_campana" class="btn btn-success">Crear campaña</a>
                    </div>
                <?php else: ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre</th>
                                    <th>Estado</th>
                                    <th>Coordinadores</th>
                                    <th>Asesores</th>
                                    <th>Bases</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($campanas as $c): ?>
                                <tr>
                                    <td><?php echo (int)$c['id']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($c['nombre']); ?></strong></td>
                                    <td>
                                        <span class="status-badge status-badge-<?php echo $c['estado'] === 'activa' ? 'activa' : 'inactiva'; ?>">
                                            <?php echo htmlspecialchars($c['estado']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo (int)$c['total_coordinadores']; ?></td>
                                    <td><?php echo (int)$c['total_asesores']; ?></td>
                                    <td><?php echo (int)$c['total_bases']; ?></td>
                                    <td>
                                        <div class="table-actions">
                                            <a class="btn btn-sm btn-primary" href="index.php?action=gestionar_campana&id=<?php echo (int)$c['id']; ?>">Gestionar</a>
                                            <a class="btn btn-sm btn-secondary" href="index.php?action=editar_campana&id=<?php echo (int)$c['id']; ?>">Editar</a>
                                            <a class="btn btn-sm btn-outline-secondary" href="index.php?action=ver_auditoria_campana&id=<?php echo (int)$c['id']; ?>">Auditoría</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <div class="breadcrumb-links">
            <a href="index.php?action=dashboard">← Volver al panel</a>
        </div>
    </div>

    <?php require_once __DIR__ . '/shared_footer.php'; ?>
</body>
</html>
