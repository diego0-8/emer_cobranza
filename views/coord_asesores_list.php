<?php
// Vista: accesos de asesores para una base
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title ?? 'Accesos'); ?></title>
    <?php require_once 'shared_styles.php'; ?>
</head>
<body>
    <?php
    require_once 'shared_navbar.php';
    echo getNavbar('Gestion', $_SESSION['user_role'] ?? '');
    ?>

    <div class="main-container">
        <section class="card">
            <div class="card-header">Base: <?php echo htmlspecialchars($base['nombre_cargue'] ?? $base['nombre'] ?? ''); ?></div>
            <div class="card-body">
                <div class="card-actions">
                    <a class="btn btn-secondary" href="index.php?action=list_cargas">Volver a bases</a>
                    <a class="btn btn-warning" href="index.php?action=ver_clientes&base_id=<?php echo (int) $base['id']; ?>">Ver clientes</a>
                </div>
            </div>
        </section>

        <section class="grid-2">
            <article class="card">
                <div class="card-header">Conceder acceso</div>
                <div class="card-body">
                    <?php if (empty($disponibles)): ?>
                        <div class="empty-state">No hay asesores disponibles.</div>
                    <?php else: ?>
                        <form method="POST" action="index.php">
                            <input type="hidden" name="action" value="asignar_asesor_base">
                            <input type="hidden" name="base_id" value="<?php echo (int) $base['id']; ?>">

                            <div class="form-group">
                                <label class="form-label" for="asesor_cedula">Asesor</label>
                                <select class="form-select" id="asesor_cedula" name="asesor_cedula" required>
                                    <option value="">Selecciona un asesor</option>
                                    <?php foreach ($disponibles as $asesor): ?>
                                        <option value="<?php echo htmlspecialchars($asesor['cedula']); ?>">
                                            <?php echo htmlspecialchars($asesor['nombre_completo'] ?? $asesor['nombre'] ?? ''); ?> (<?php echo htmlspecialchars($asesor['cedula']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <button class="btn btn-primary mt-20" type="submit">Asignar acceso</button>
                        </form>
                    <?php endif; ?>
                </div>
            </article>

            <article class="card">
                <div class="card-header">Asesores con acceso</div>
                <div class="card-body">
                    <?php if (empty($asignados)): ?>
                        <div class="empty-state">Todavia no hay asesores con acceso.</div>
                    <?php else: ?>
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Asesor</th>
                                        <th>Cedula</th>
                                        <th>Accion</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($asignados as $asesor): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($asesor['nombre_completo'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($asesor['cedula'] ?? ''); ?></td>
                                            <td>
                                                <form method="POST" action="index.php">
                                                    <input type="hidden" name="action" value="liberar_asesor_base">
                                                    <input type="hidden" name="base_id" value="<?php echo (int) $base['id']; ?>">
                                                    <input type="hidden" name="asesor_cedula" value="<?php echo htmlspecialchars($asesor['cedula'] ?? ''); ?>">
                                                    <button class="btn btn-warning" type="submit" onclick="return confirm('¿Retirar acceso?')">Retirar</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </article>
        </section>
    </div>

    <?php require_once 'shared_footer.php'; ?>
</body>
</html>

