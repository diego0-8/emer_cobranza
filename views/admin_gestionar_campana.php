<?php
$page_title = $page_title ?? 'Gestionar Campaña';
$campana = $campana ?? [];
$coordinadores = $coordinadores ?? [];
$coordinadoresDisponibles = $coordinadoresDisponibles ?? [];
$asesores = $asesores ?? [];
$asesoresDisponibles = $asesoresDisponibles ?? [];
$success = $success ?? '';
$error = $error ?? '';
$campanaId = (int)($campana['id'] ?? 0);

$messages = [
    'campana_creada' => 'Campaña creada correctamente.',
    'campana_actualizada' => 'Campaña actualizada.',
    'coord_asignado' => 'Coordinador asignado.',
    'coord_liberado' => 'Coordinador liberado.',
    'asesor_asignado' => 'Asesor asignado a la campaña.',
    'asesor_liberado' => 'Asesor liberado de la campaña.',
];
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
            <h1><?php echo htmlspecialchars($campana['nombre'] ?? ''); ?></h1>
            <p><?php echo htmlspecialchars($campana['descripcion'] ?? 'Sin descripción'); ?></p>
            <div class="campana-meta">
                <span class="campana-meta-item">
                    Estado:
                    <span class="status-badge status-badge-<?php echo ($campana['estado'] ?? '') === 'activa' ? 'activa' : 'inactiva'; ?>">
                        <?php echo htmlspecialchars($campana['estado'] ?? ''); ?>
                    </span>
                </span>
                <span class="campana-meta-item"><?php echo count($coordinadores); ?> coordinador(es)</span>
                <span class="campana-meta-item"><?php echo count($asesores); ?> asesor(es)</span>
                <span class="campana-meta-item"><?php echo (int)($campana['total_bases'] ?? 0); ?> base(s)</span>
            </div>
        </div>

        <?php if ($success && isset($messages[$success])): ?>
            <div class="alert alert-success"><?php echo $messages[$success]; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error">Error: <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="campanas-grid">
            <section class="card">
                <div class="card-header">Coordinadores</div>
                <div class="card-body">
                    <?php if (empty($coordinadores)): ?>
                        <div class="empty-state">
                            <strong>Sin coordinadores</strong>
                            Asigna al menos un coordinador para operar esta campaña.
                        </div>
                    <?php else: ?>
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Nombre</th>
                                        <th>Usuario</th>
                                        <th>Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($coordinadores as $c): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($c['nombre_completo']); ?></td>
                                        <td><?php echo htmlspecialchars($c['usuario']); ?></td>
                                        <td>
                                            <a class="btn btn-sm btn-danger"
                                               href="index.php?action=liberar_coordinador_campana&campana_id=<?php echo $campanaId; ?>&coordinador_id=<?php echo urlencode($c['cedula']); ?>"
                                               onclick="return confirm('¿Liberar coordinador de esta campaña?');">Liberar</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($coordinadoresDisponibles)): ?>
                        <form method="post" action="index.php?action=asignar_coordinador_campana" class="assign-toolbar">
                            <input type="hidden" name="campana_id" value="<?php echo $campanaId; ?>">
                            <select class="form-select" name="coordinador_id" required>
                                <option value="">Seleccionar coordinador...</option>
                                <?php foreach ($coordinadoresDisponibles as $c): ?>
                                    <option value="<?php echo htmlspecialchars($c['cedula']); ?>">
                                        <?php echo htmlspecialchars($c['nombre_completo'] . ' (' . ($c['usuario'] ?? '') . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn btn-primary">Asignar coordinador</button>
                        </form>
                    <?php else: ?>
                        <p class="alert alert-info" style="margin-top:16px;margin-bottom:0;">No hay coordinadores disponibles sin campaña (o ya están todos en esta). Crea/activa un coordinador o libéralo de otra campaña para poder añadirlo.</p>
                    <?php endif; ?>
                </div>
            </section>

            <section class="card">
                <div class="card-header">Asesores</div>
                <div class="card-body">
                    <?php if (empty($asesores)): ?>
                        <div class="empty-state">
                            <strong>Sin asesores</strong>
                            Los asesores de la campaña podrán recibir acceso a bases desde el coordinador.
                        </div>
                    <?php else: ?>
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Nombre</th>
                                        <th>Usuario</th>
                                        <th>Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($asesores as $a): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($a['nombre_completo']); ?></td>
                                        <td><?php echo htmlspecialchars($a['usuario']); ?></td>
                                        <td>
                                            <a class="btn btn-sm btn-danger"
                                               href="index.php?action=liberar_asesor_campana&campana_id=<?php echo $campanaId; ?>&asesor_id=<?php echo urlencode($a['cedula']); ?>"
                                               onclick="return confirm('¿Liberar asesor de esta campaña?');">Liberar</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($asesoresDisponibles)): ?>
                        <form method="post" action="index.php?action=asignar_asesor_campana" class="assign-toolbar">
                            <input type="hidden" name="campana_id" value="<?php echo $campanaId; ?>">
                            <select class="form-select" name="asesor_id" required>
                                <option value="">Seleccionar asesor...</option>
                                <?php foreach ($asesoresDisponibles as $a): ?>
                                    <option value="<?php echo htmlspecialchars($a['cedula']); ?>">
                                        <?php echo htmlspecialchars($a['nombre_completo'] . ' (' . ($a['usuario'] ?? '') . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn btn-primary">Asignar asesor</button>
                        </form>
                    <?php else: ?>
                        <p class="alert alert-info" style="margin-top:16px;margin-bottom:0;">No hay asesores disponibles sin campaña activa. Crea/activa un asesor o libéralo de otra campaña para poder añadirlo.</p>
                    <?php endif; ?>
                </div>
            </section>
        </div>

        <div class="breadcrumb-links">
            <a href="index.php?action=editar_campana&id=<?php echo $campanaId; ?>">Editar campaña</a>
            <span>·</span>
            <a href="index.php?action=ver_auditoria_campana&id=<?php echo $campanaId; ?>">Ver auditoría</a>
            <span>·</span>
            <a href="index.php?action=list_campanas">← Volver al listado</a>
        </div>
    </div>

    <?php require_once __DIR__ . '/shared_footer.php'; ?>
</body>
</html>
