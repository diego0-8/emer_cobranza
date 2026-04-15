<?php
// Vista: listado de bases del coordinador
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
    echo getNavbar('Gestion', $_SESSION['user_role'] ?? '');
    ?>

    <div class="main-container">
        <section class="card">
            <div class="card-header">Crear nueva base</div>
            <div class="card-body">
                <form class="form-row" method="POST" action="index.php">
                    <input type="hidden" name="action" value="crear_nueva_base">
                    <div class="form-group">
                        <label class="form-label" for="nombre_base">Nombre de la base</label>
                        <input class="form-input" id="nombre_base" name="nombre_base" required>
                    </div>
                    <div class="form-group">
                        <button class="btn btn-primary mt-20" type="submit">Crear base</button>
                    </div>
                </form>
            </div>
        </section>

        <section class="card">
            <div class="card-header">Bases del coordinador</div>
            <div class="card-body">
                <?php if (empty($bases)): ?>
                    <div class="empty-state">No has creado bases todavia.</div>
                <?php else: ?>
                    <div class="grid-2">
                        <?php foreach ($bases as $base): ?>
                            <article class="card">
                                <div class="card-body">
                                    <h3><?php echo htmlspecialchars($base['nombre_cargue'] ?? $base['nombre'] ?? ''); ?></h3>
                                    <p>Estado: <strong><?php echo htmlspecialchars($base['estado'] ?? ''); ?></strong></p>
                                    <p>Total clientes: <strong><?php echo (int) ($base['estadisticas']['total_clientes'] ?? 0); ?></strong></p>
                                    <p>Clientes en tareas: <strong><?php echo (int) ($base['estadisticas']['clientes_asignados'] ?? 0); ?></strong></p>

                                    <div class="card-actions">
                                        <a class="btn btn-primary" href="index.php?action=ver_clientes&base_id=<?php echo (int) $base['id']; ?>">Clientes</a>
                                        <a class="btn btn-secondary" href="index.php?action=gestionar_asesores&base_id=<?php echo (int) $base['id']; ?>">Accesos</a>
                                        <a class="btn btn-warning" href="index.php?action=tareas_coordinador&base_id=<?php echo (int) $base['id']; ?>">Tareas</a>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <?php require_once 'shared_footer.php'; ?>
</body>
</html>

