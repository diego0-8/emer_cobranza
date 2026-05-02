<?php
// Vista: listado de usuarios (administrador)
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once __DIR__ . '/shared_navbar.php'; renderPageHead($page_title ?? 'Usuarios'); ?>
    <?php require_once 'shared_styles.php'; ?>
    <link rel="stylesheet" href="assets/css/admin_dashboard.css">
</head>
<body>
    <?php
    require_once 'shared_navbar.php';
    echo getNavbar('Inicio', $_SESSION['user_role'] ?? '');
    ?>

    <div class="main-container">
        <?php if (!empty($mensajes['success'])): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($mensajes['success']); ?></div>
        <?php endif; ?>
        <?php if (!empty($mensajes['error'])): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($mensajes['error']); ?></div>
        <?php endif; ?>

        <section class="card">
            <div class="card-header">Filtros</div>
            <div class="card-body">
                <form method="GET" action="index.php">
                    <input type="hidden" name="action" value="list_usuarios">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="buscar">Buscar</label>
                            <input class="form-input" id="buscar" name="buscar" value="<?php echo htmlspecialchars($_GET['buscar'] ?? ''); ?>" placeholder="Nombre, usuario o cedula">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="rol">Rol</label>
                            <select class="form-select" id="rol" name="rol">
                                <option value="">Todos</option>
                                <option value="administrador" <?php echo (($_GET['rol'] ?? '') === 'administrador') ? 'selected' : ''; ?>>Administrador</option>
                                <option value="coordinador" <?php echo (($_GET['rol'] ?? '') === 'coordinador') ? 'selected' : ''; ?>>Coordinador</option>
                                <option value="asesor" <?php echo (($_GET['rol'] ?? '') === 'asesor') ? 'selected' : ''; ?>>Asesor</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="estado">Estado</label>
                            <select class="form-select" id="estado" name="estado">
                                <option value="">Todos</option>
                                <option value="Activo" <?php echo (($_GET['estado'] ?? '') === 'Activo') ? 'selected' : ''; ?>>Activo</option>
                                <option value="Inactivo" <?php echo (($_GET['estado'] ?? '') === 'Inactivo') ? 'selected' : ''; ?>>Inactivo</option>
                            </select>
                        </div>
                    </div>

                    <div class="card-actions">
                        <button class="btn btn-primary" type="submit">Filtrar</button>
                        <a class="btn btn-success" href="index.php?action=crear_usuario">Nuevo usuario</a>
                    </div>
                </form>
            </div>
        </section>

        <section class="card">
            <div class="card-header">Listado</div>
            <div class="card-body">
                <?php if (empty($usuarios)): ?>
                    <div class="empty-state">No hay usuarios.</div>
                <?php else: ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Cedula</th>
                                    <th>Nombre</th>
                                    <th>Usuario</th>
                                    <th>Rol</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($usuarios as $u): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($u['cedula'] ?? $u['id'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($u['nombre'] ?? $u['nombre_completo'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($u['usuario'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($u['rol'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($u['estado'] ?? ''); ?></td>
                                        <td>
                                            <a class="btn btn-sm btn-primary" href="index.php?action=editar_usuario&id=<?php echo urlencode($u['cedula'] ?? $u['id']); ?>">Editar</a>
                                            <a class="btn btn-sm btn-secondary" href="index.php?action=toggle_estado&id=<?php echo urlencode($u['cedula'] ?? $u['id']); ?>">Cambiar estado</a>
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

