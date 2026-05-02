<?php
// Vista: formulario de usuario (administrador)
$modo = $modo ?? 'crear';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once __DIR__ . '/shared_navbar.php'; renderPageHead($page_title ?? 'Usuario'); ?>
    <?php require_once 'shared_styles.php'; ?>
</head>
<body>
    <?php
    require_once 'shared_navbar.php';
    echo getNavbar('Inicio', $_SESSION['user_role'] ?? '');
    ?>

    <div class="main-container">
        <section class="card">
            <div class="card-header"><?php echo $modo === 'editar' ? 'Editar usuario' : 'Crear usuario'; ?></div>
            <div class="card-body">
                <?php if (!empty($mensajes['error'])): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($mensajes['error']); ?></div>
                <?php endif; ?>
                <?php if (!empty($mensajes['success'])): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($mensajes['success']); ?></div>
                <?php endif; ?>

                <form method="POST" action="index.php">
                    <input type="hidden" name="action" value="<?php echo $modo === 'editar' ? 'process_update_usuario' : 'process_create_usuario'; ?>">
                    <?php if ($modo === 'editar'): ?>
                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($usuario['cedula'] ?? $usuario['id'] ?? ''); ?>">
                    <?php endif; ?>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="cedula">Cedula</label>
                            <input
                                class="form-input"
                                id="cedula"
                                name="cedula"
                                value="<?php echo htmlspecialchars($usuario['cedula'] ?? $usuario['id'] ?? ''); ?>"
                                <?php echo $modo === 'editar' ? 'readonly' : ''; ?>
                                required
                            >
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="nombre">Nombre</label>
                            <input class="form-input" id="nombre" name="nombre" value="<?php echo htmlspecialchars($usuario['nombre'] ?? $usuario['nombre_completo'] ?? ''); ?>" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="usuario">Usuario</label>
                            <input class="form-input" id="usuario" name="usuario" value="<?php echo htmlspecialchars($usuario['usuario'] ?? ''); ?>" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="contrasena">Contrasena</label>
                            <input class="form-input" type="password" id="contrasena" name="contrasena" <?php echo $modo === 'crear' ? 'required' : ''; ?>>
                            <div class="form-help">Solo se actualiza si escribes una nueva.</div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="rol">Rol</label>
                            <select class="form-select" id="rol" name="rol" required>
                                <?php foreach (['administrador', 'coordinador', 'asesor'] as $r): ?>
                                    <option value="<?php echo $r; ?>" <?php echo (($usuario['rol'] ?? '') === $r) ? 'selected' : ''; ?>>
                                        <?php echo ucfirst($r); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="estado">Estado</label>
                            <select class="form-select" id="estado" name="estado" required>
                                <?php foreach (['Activo', 'Inactivo'] as $e): ?>
                                    <option value="<?php echo $e; ?>" <?php echo (($usuario['estado'] ?? '') === $e) ? 'selected' : ''; ?>>
                                        <?php echo $e; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="extension">Extension</label>
                            <input class="form-input" id="extension" name="extension" value="<?php echo htmlspecialchars($usuario['extension'] ?? $usuario['extension_telefono'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="sip_password">Clave SIP</label>
                            <input class="form-input" id="sip_password" name="sip_password" value="<?php echo htmlspecialchars($usuario['sip_password'] ?? $usuario['clave_webrtc'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="card-actions">
                        <button class="btn btn-primary" type="submit"><?php echo $modo === 'editar' ? 'Guardar cambios' : 'Crear usuario'; ?></button>
                        <a class="btn btn-secondary" href="index.php?action=list_usuarios">Volver</a>
                    </div>
                </form>
            </div>
        </section>
    </div>

    <?php require_once 'shared_footer.php'; ?>
</body>
</html>

