<?php
// Vista: clientes de una base del coordinador
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
            <div class="card-header">Clientes de la base</div>
            <div class="card-body">
                <div class="card-actions">
                    <a class="btn btn-secondary" href="index.php?action=list_cargas">Volver a bases</a>
                    <a class="btn btn-warning" href="index.php?action=tareas_coordinador&base_id=<?php echo (int) $base['id']; ?>">Crear tareas</a>
                </div>
            </div>
        </section>

        <section class="card">
            <div class="card-header">Listado</div>
            <div class="card-body">
                <form class="form-row" method="GET" action="index.php">
                    <input type="hidden" name="action" value="ver_clientes">
                    <input type="hidden" name="base_id" value="<?php echo (int) $base['id']; ?>">
                    <div class="form-group">
                        <label class="form-label" for="buscar">Buscar</label>
                        <input class="form-input" id="buscar" name="buscar" value="<?php echo htmlspecialchars($termino ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <button class="btn btn-secondary mt-20" type="submit">Filtrar</button>
                    </div>
                </form>

                <?php if (empty($clientes)): ?>
                    <div class="empty-state">No hay clientes para mostrar.</div>
                <?php else: ?>
                    <div class="table-container mt-20">
                        <table>
                            <thead>
                                <tr>
                                    <th>Cedula</th>
                                    <th>Nombre</th>
                                    <th>Telefono</th>
                                    <th>Email</th>
                                    <th>Ciudad</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($clientes as $cliente): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($cliente['cedula'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($cliente['nombre'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($cliente['tel1'] ?? $cliente['telefono'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($cliente['email'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($cliente['ciudad'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($cliente['estado'] ?? ''); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <section class="card">
            <div class="card-header">Agregar cliente a la base</div>
            <div class="card-body">
                <form class="form-row" method="POST" action="index.php">
                    <input type="hidden" name="action" value="agregar_a_base_existente">
                    <input type="hidden" name="base_id" value="<?php echo (int) $base['id']; ?>">
                    <div class="form-group">
                        <label class="form-label" for="cedula">Cedula</label>
                        <input class="form-input" id="cedula" name="cedula" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="nombre">Nombre</label>
                        <input class="form-input" id="nombre" name="nombre" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="tel1">Telefono</label>
                        <input class="form-input" id="tel1" name="tel1">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="email">Email</label>
                        <input class="form-input" id="email" name="email">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="ciudad">Ciudad</label>
                        <input class="form-input" id="ciudad" name="ciudad">
                    </div>
                    <div class="form-group">
                        <button class="btn btn-primary mt-20" type="submit">Guardar</button>
                    </div>
                </form>
            </div>
        </section>
    </div>

    <?php require_once 'shared_footer.php'; ?>
</body>
</html>

