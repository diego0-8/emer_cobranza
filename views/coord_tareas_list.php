<?php
// Vista: tareas del coordinador (tareas + detalle via tablas)
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title ?? 'Tareas'); ?></title>
    <?php require_once 'shared_styles.php'; ?>
</head>
<body>
    <?php
    require_once 'shared_navbar.php';
    echo getNavbar('Gestion', $_SESSION['user_role'] ?? '');
    ?>

    <div class="main-container">
        <section class="card">
            <div class="card-header">Filtrar por base</div>
            <div class="card-body">
                <form class="form-row" method="GET" action="index.php">
                    <input type="hidden" name="action" value="tareas_coordinador">
                    <div class="form-group">
                        <label class="form-label" for="base_id">Base</label>
                        <select class="form-select" id="base_id" name="base_id">
                            <?php foreach ($bases as $base): ?>
                                <option value="<?php echo (int) $base['id']; ?>" <?php echo ((int) ($baseId ?? 0)) === (int) $base['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($base['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <button class="btn btn-secondary" type="submit">Cargar</button>
                    </div>
                </form>
            </div>
        </section>

        <?php if (!empty($baseSeleccionada)): ?>
            <section class="card">
                <div class="card-header">Crear tarea</div>
                <div class="card-body">
                    <?php if (empty($asesoresBase)): ?>
                        <div class="empty-state">Primero debes otorgar acceso a un asesor sobre esta base.</div>
                    <?php elseif (empty($clientesDisponibles)): ?>
                        <div class="empty-state">No hay clientes disponibles para nuevas tareas en esta base.</div>
                    <?php else: ?>
                        <form method="POST" action="index.php">
                            <input type="hidden" name="action" value="crear_tarea">
                            <input type="hidden" name="base_id" value="<?php echo (int) $baseSeleccionada['id']; ?>">

                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label" for="nombre_tarea">Nombre tarea</label>
                                    <input class="form-input" id="nombre_tarea" name="nombre_tarea" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="asesor_cedula">Asesor</label>
                                    <select class="form-select" id="asesor_cedula" name="asesor_cedula" required>
                                        <?php foreach ($asesoresBase as $asesor): ?>
                                            <option value="<?php echo htmlspecialchars($asesor['cedula']); ?>">
                                                <?php echo htmlspecialchars($asesor['nombre_completo']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Clientes para la tarea</label>
                                <div class="checkbox-list">
                                    <?php foreach ($clientesDisponibles as $cliente): ?>
                                        <label class="checkbox-item">
                                            <input type="checkbox" name="cliente_ids[]" value="<?php echo (int) $cliente['id_cliente']; ?>">
                                            <span>
                                                <strong><?php echo htmlspecialchars($cliente['nombre']); ?></strong><br>
                                                <?php echo htmlspecialchars($cliente['cedula']); ?> - <?php echo htmlspecialchars($cliente['tel1'] ?? ''); ?>
                                            </span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <button class="btn btn-primary mt-20" type="submit">Crear tarea</button>
                        </form>
                    <?php endif; ?>
                </div>
            </section>
        <?php endif; ?>

        <section class="card">
            <div class="card-header">Tareas existentes</div>
            <div class="card-body">
                <?php if (empty($tareas)): ?>
                    <div class="empty-state">No hay tareas registradas.</div>
                <?php else: ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Tarea</th>
                                    <th>Asesor</th>
                                    <th>Estado</th>
                                    <th>Clientes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tareas as $tarea): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($tarea['nombre_tarea'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($tarea['asesor_nombre'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($tarea['estado'] ?? ''); ?></td>
                                        <td><?php echo count($tarea['cliente_ids'] ?? []); ?></td>
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

