<?php
$page_title = $page_title ?? 'Campaña';
$campana = $campana ?? null;
$isEdit = !empty($campana);
$error = $error ?? '';
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
            <h1><?php echo $isEdit ? 'Editar campaña' : 'Crear campaña'; ?></h1>
            <p><?php echo $isEdit ? 'Actualiza la información de la campaña.' : 'Define una nueva campaña de cobranza.'; ?></p>
        </div>

        <section class="card">
            <div class="card-header"><?php echo $isEdit ? 'Datos de la campaña' : 'Nueva campaña'; ?></div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <form method="post">
                    <div class="form-group">
                        <label class="form-label" for="nombre">Nombre</label>
                        <input class="form-input" type="text" id="nombre" name="nombre" required
                               value="<?php echo htmlspecialchars($campana['nombre'] ?? ''); ?>"
                               placeholder="Ej: Emermedica Cobranza">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="descripcion">Descripción</label>
                        <textarea class="form-input" id="descripcion" name="descripcion" rows="4"
                                  placeholder="Descripción opcional de la campaña"><?php echo htmlspecialchars($campana['descripcion'] ?? ''); ?></textarea>
                    </div>

                    <?php if ($isEdit): ?>
                        <div class="form-group">
                            <label class="form-label" for="estado">Estado</label>
                            <select class="form-select" id="estado" name="estado">
                                <option value="activa" <?php echo ($campana['estado'] ?? '') === 'activa' ? 'selected' : ''; ?>>Activa</option>
                                <option value="inactiva" <?php echo ($campana['estado'] ?? '') === 'inactiva' ? 'selected' : ''; ?>>Inactiva</option>
                            </select>
                        </div>
                    <?php endif; ?>

                    <div class="card-actions">
                        <button type="submit" class="btn btn-primary">Guardar</button>
                        <a href="index.php?action=list_campanas" class="btn btn-secondary">Cancelar</a>
                        <?php if ($isEdit): ?>
                            <a href="index.php?action=gestionar_campana&id=<?php echo (int)($campana['id'] ?? 0); ?>" class="btn btn-outline-primary">Gestionar campaña</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </section>

        <div class="breadcrumb-links">
            <a href="index.php?action=list_campanas">← Volver al listado</a>
        </div>
    </div>

    <?php require_once __DIR__ . '/shared_footer.php'; ?>
</body>
</html>
