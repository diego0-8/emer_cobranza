<?php
require_once __DIR__ . '/shared_navbar.php';
$currentPage = 'Llamadas';
$userRole = $_SESSION['user_role'] ?? '';

// Defaults para evitar notices/lints si la vista se reutiliza.
$fechaInicio = isset($fechaInicio) ? (string)$fechaInicio : '';
$fechaFin = isset($fechaFin) ? (string)$fechaFin : '';
$asesorCedula = isset($asesorCedula) ? (string)$asesorCedula : '';
$hangupBy = isset($hangupBy) ? (string)$hangupBy : '';
$telefono = isset($telefono) ? (string)$telefono : '';
$periodo = isset($periodo) ? (string)$periodo : ((isset($_GET['periodo']) ? (string)$_GET['periodo'] : 'semana'));
$periodo = strtolower(trim($periodo));
if (!in_array($periodo, ['dia','semana','mes'], true)) $periodo = 'semana';
$page = isset($page) ? (int)$page : 1;
$totalPages = isset($totalPages) ? (int)$totalPages : 1;
$total = isset($total) ? (int)$total : 0;
$callLogMissing = isset($callLogMissing) ? (bool)$callLogMissing : false;
$callLogMissingMessage = isset($callLogMissingMessage) ? (string)$callLogMissingMessage : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php renderPageHead($page_title ?? 'Llamadas'); ?>
    <?php require_once 'shared_styles.php'; ?>
    <link rel="stylesheet" href="assets/css/coord_call.css">
</head>
<body>
<?php echo getNavbar($currentPage, $userRole); ?>

<div class="main-container">
<div class="coord-call-container">
    <div class="coord-call-topbar">
        <div class="coord-call-title">
            <h1><?php echo htmlspecialchars($page_title ?? 'Llamadas'); ?></h1>
            <div class="coord-call-subtitle">Mostrando máximo 10 llamadas por página. Total: <?php echo (int)($total ?? 0); ?></div>
        </div>

        <form method="GET" action="index.php" class="coord-call-filters coord-call-filters-inline">
            <input type="hidden" name="action" value="coord_call">
            <div class="coord-call-inline-fields">
                <div class="form-group">
                    <label class="form-label">Período</label>
                    <select class="form-select" name="periodo">
                        <option value="dia" <?php echo $periodo === 'dia' ? 'selected' : ''; ?>>Día</option>
                        <option value="semana" <?php echo $periodo === 'semana' ? 'selected' : ''; ?>>Semana</option>
                        <option value="mes" <?php echo $periodo === 'mes' ? 'selected' : ''; ?>>Mes</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Teléfono</label>
                    <input class="form-input" type="text" name="telefono" placeholder="Buscar teléfono..." value="<?php echo htmlspecialchars($telefono ?? ''); ?>">
                </div>
                <div class="coord-call-actions">
                    <button class="btn btn-primary" type="submit">Filtrar</button>
                    <a class="btn btn-secondary" href="index.php?action=coord_call">Limpiar</a>
                </div>
            </div>
        </form>
    </div>

    <?php if ($callLogMissing): ?>
        <div class="alert alert-warning coord-call-alert">
            <strong>Acción requerida:</strong>
            <?php echo htmlspecialchars($callLogMissingMessage ?: 'Falta la tabla call_log.'); ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">Resultados</div>
        <div class="card-body">
            <div class="table-container coord-call-table">
        <table>
            <thead>
                <tr>
                    <th>Inicio</th>
                    <th>Fin</th>
                    <th>Duración</th>
                    <th>Teléfono</th>
                    <th>Cliente</th>
                    <th>Asesor</th>
                    <th>Quién colgó</th>
                    <th>Gestión</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="8" class="coord-call-muted">Sin resultados para los filtros seleccionados.</td></tr>
                <?php else: ?>
                    <?php foreach ($rows as $r): ?>
                        <?php
                            $hb = (string)($r['hangup_by'] ?? 'sistema');
                            $dur = (int)($r['duracion_segundos'] ?? 0);
                            $mm = floor($dur / 60);
                            $ss = $dur % 60;
                            $durTxt = sprintf('%02d:%02d', $mm, $ss);
                            $gestionId = (int)($r['gestion_id'] ?? 0);
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars((string)($r['inicio'] ?? '')); ?></td>
                            <td><?php echo htmlspecialchars((string)($r['fin'] ?? '')); ?></td>
                            <td><?php echo htmlspecialchars($durTxt); ?></td>
                            <td><?php echo htmlspecialchars((string)($r['telefono_contacto'] ?? '')); ?></td>
                            <td>
                                <?php echo htmlspecialchars((string)($r['cliente_nombre'] ?? '')); ?>
                                <div class="coord-call-muted"><?php echo htmlspecialchars((string)($r['cliente_cedula'] ?? '')); ?></div>
                            </td>
                            <td>
                                <?php echo htmlspecialchars((string)($r['asesor_nombre'] ?? $r['asesor_cedula'] ?? '')); ?>
                                <div class="coord-call-muted"><?php echo htmlspecialchars((string)($r['asesor_cedula'] ?? '')); ?></div>
                            </td>
                            <td><span class="call-badge <?php echo htmlspecialchars($hb); ?>"><?php echo htmlspecialchars($hb); ?></span></td>
                            <td>
                                <?php if ($gestionId > 0): ?>
                                    <a
                                        href="#"
                                        class="cc-open-gestion"
                                        data-gestion-id="<?php echo (int)$gestionId; ?>"
                                        data-call-id="<?php echo htmlspecialchars((string)($r['call_id'] ?? '')); ?>"
                                    >Ver gestión</a>
                                    <div class="coord-call-muted">ID: <?php echo $gestionId; ?></div>
                                <?php else: ?>
                                    <span class="coord-call-muted">Pendiente</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
        </div>
    </div>

    <?php
        $page = (int)($page ?? 1);
        $totalPages = (int)($totalPages ?? 1);
        $buildUrl = function(int $p) use ($fechaInicio, $fechaFin, $asesorCedula, $hangupBy, $telefono, $periodo) {
            $qs = [
                'action' => 'coord_call',
                'periodo' => (string)$periodo,
                'telefono' => $telefono,
                'page' => $p,
            ];
            return 'index.php?' . http_build_query($qs);
        };
    ?>

    <div class="coord-call-pagination">
        <?php if ($page > 1): ?>
            <a href="<?php echo htmlspecialchars($buildUrl($page - 1)); ?>">Anterior</a>
        <?php else: ?>
            <span class="disabled">Anterior</span>
        <?php endif; ?>

        <?php
            $start = max(1, $page - 3);
            $end = min($totalPages, $page + 3);
            for ($p = $start; $p <= $end; $p++):
        ?>
            <?php if ($p === $page): ?>
                <span class="active"><?php echo $p; ?></span>
            <?php else: ?>
                <a href="<?php echo htmlspecialchars($buildUrl($p)); ?>"><?php echo $p; ?></a>
            <?php endif; ?>
        <?php endfor; ?>

        <?php if ($page < $totalPages): ?>
            <a href="<?php echo htmlspecialchars($buildUrl($page + 1)); ?>">Siguiente</a>
        <?php else: ?>
            <span class="disabled">Siguiente</span>
        <?php endif; ?>
    </div>
</div>
</div>

<!-- Modal: detalle de gestión -->
<div class="cc-modal-backdrop" id="ccGestionBackdrop" aria-hidden="true">
    <div class="cc-modal" role="dialog" aria-modal="true" aria-labelledby="ccGestionTitle">
        <div class="cc-modal-top">
            <div class="cc-modal-top-left">
                <div id="ccGestionTitle" class="cc-modal-top-title">Gestión</div>
                <div class="cc-modal-top-subtitle" id="ccGestionSubtitle"></div>
            </div>
            <button type="button" class="cc-modal-close" id="ccGestionClose" aria-label="Cerrar">×</button>
        </div>
        <div class="cc-modal-body" id="ccGestionBody">
            <div class="coord-call-muted">Cargando…</div>
        </div>
    </div>
</div>

<script>
(() => {
    const backdrop = document.getElementById('ccGestionBackdrop');
    const body = document.getElementById('ccGestionBody');
    const subtitle = document.getElementById('ccGestionSubtitle');
    const closeBtn = document.getElementById('ccGestionClose');

    function closeModal() {
        backdrop.setAttribute('aria-hidden', 'true');
        backdrop.classList.remove('open');
        body.innerHTML = '<div class="coord-call-muted">Cargando…</div>';
        subtitle.textContent = '';
    }

    function openModal() {
        backdrop.setAttribute('aria-hidden', 'false');
        backdrop.classList.add('open');
    }

    document.addEventListener('click', async (e) => {
        const a = e.target && e.target.closest ? e.target.closest('.cc-open-gestion') : null;
        if (!a) return;
        e.preventDefault();

        const gestionId = a.getAttribute('data-gestion-id');
        const callId = a.getAttribute('data-call-id') || '';
        if (!gestionId) return;

        subtitle.textContent = callId ? ('Call ID: ' + callId) : '';
        openModal();

        try {
            const url = 'index.php?action=coord_call_gestion_modal&gestion_id=' + encodeURIComponent(gestionId);
            const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const html = await res.text();
            if (!res.ok) {
                body.innerHTML = '<div class="alert alert-warning">No fue posible cargar la gestión.</div><div class="coord-call-muted">' +
                    (html ? String(html) : '') + '</div>';
                return;
            }
            body.innerHTML = html;
        } catch (err) {
            body.innerHTML = '<div class="alert alert-warning">Error de red cargando la gestión.</div>';
        }
    });

    closeBtn.addEventListener('click', closeModal);
    backdrop.addEventListener('click', (e) => {
        if (e.target === backdrop) closeModal();
    });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && backdrop.classList.contains('open')) closeModal();
    });
})();
</script>

</body>
</html>

