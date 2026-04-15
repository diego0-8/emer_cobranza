<?php
// Vista: Dashboard del coordinador (base_clientes, accesos y tareas)
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <?php require_once 'shared_styles.php'; ?>
    <link rel="stylesheet" href="assets/css/admin_dashboard.css">
</head>
<body>
    <?php
    require_once 'shared_navbar.php';
    echo getNavbar('Inicio', $_SESSION['user_role'] ?? '');
    ?>

    <div class="main-container">
        <section class="stats-grid">
            <article class="stat-card">
                <div class="stat-number"><?php echo (int) count($bases); ?></div>
                <div class="stat-label">Bases del coordinador</div>
            </article>
            <article class="stat-card">
                <div class="stat-number"><?php echo (int) count($asesores); ?></div>
                <div class="stat-label">Asesores en equipo</div>
            </article>
            <article class="stat-card">
                <div class="stat-number"><?php echo (int) ($estadisticasTareas['total_tareas'] ?? 0); ?></div>
                <div class="stat-label">Tareas registradas</div>
            </article>
            <article class="stat-card">
                <div class="stat-number"><?php echo (int) ($resumen['clientes_asignados'] ?? 0); ?></div>
                <div class="stat-label">Clientes asignados</div>
            </article>
        </section>

        <section class="card">
            <div class="card-header">Acciones rapidas</div>
            <div class="card-body">
                <div class="card-actions">
                    <a class="btn btn-primary" href="index.php?action=list_cargas">Gestionar bases</a>
                    <a class="btn btn-secondary" href="index.php?action=gestionar_asesores&base_id=0">Accesos</a>
                    <a class="btn btn-warning" href="index.php?action=tareas_coordinador">Tareas</a>
                </div>
            </div>
        </section>
    </div>

    <?php require_once 'shared_footer.php'; ?>
</body>
</html>

