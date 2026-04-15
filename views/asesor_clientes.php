<?php
$page_title = $page_title ?? 'Mis clientes';
$termino = $termino ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <?php require_once 'shared_styles.php'; ?>
</head>
<body>
    <?php
    require_once 'shared_navbar.php';
    echo getNavbar('Mis Clientes', $_SESSION['user_role'] ?? 'asesor');
    ?>

    <div class="main-container">
        <section class="card">
            <div class="card-header">Busqueda</div>
            <div class="card-body">
                <form method="GET" action="index.php">
                    <input type="hidden" name="action" value="mis_clientes">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="buscar">Buscar</label>
                            <input class="form-input" id="buscar" name="buscar" value="<?php echo htmlspecialchars($termino); ?>" placeholder="Nombre, cedula o telefono">
                        </div>
                        <div class="form-group">
                            <button class="btn btn-secondary" type="submit">Filtrar</button>
                        </div>
                    </div>
                </form>
            </div>
        </section>

        <section class="card">
            <div class="card-header">Clientes</div>
            <div class="card-body">
                <?php if (empty($clientes)): ?>
                    <div class="empty-state">No tienes clientes asignados.</div>
                <?php else: ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Cedula</th>
                                    <th>Telefono</th>
                                    <th>Tarea</th>
                                    <th>Estado</th>
                                    <th>Accion</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($clientes as $c): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($c['nombre'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($c['cedula'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($c['telefono'] ?? ($c['tel1'] ?? '')); ?></td>
                                        <td><?php echo htmlspecialchars($c['nombre_tarea'] ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars($c['estado_tarea'] ?? ''); ?></td>
                                        <td>
                                            <a class="btn btn-sm btn-primary" href="index.php?action=gestionar_cliente&id=<?php echo (int) ($c['id_cliente'] ?? $c['id'] ?? 0); ?>">Gestionar</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <?php require_once 'shared_footer.php'; ?>
</body>
</html>

