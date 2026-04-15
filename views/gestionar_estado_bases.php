<?php
// Vista para gestionar el estado de las bases de datos
$page_title = $page_title ?? 'Gestionar Estado de Bases';
$bases_datos = $bases_datos ?? [];

// Conexión a la base de datos (por si se incluye sin $pdo desde controlador)
if (!isset($pdo)) {
    require_once __DIR__ . '/../config.php';
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("SET CHARACTER SET utf8mb4");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <?php include __DIR__ . '/shared_styles.php'; ?>
</head>
<body>
    <?php
    include __DIR__ . '/shared_navbar.php';
    echo getNavbar('Gestionar Bases', $_SESSION['user_role'] ?? '');
    ?>

<style>
/* Estilos específicos para los botones de acción */
.btn-habilitar {
    background-color: #28a745 !important;
    color: white !important;
    font-weight: bold !important;
    padding: 8px 12px !important;
    border: 2px solid #28a745 !important;
    box-shadow: 0 2px 4px rgba(40, 167, 69, 0.3) !important;
}

.btn-habilitar:hover {
    background-color: #218838 !important;
    border-color: #1e7e34 !important;
    transform: translateY(-1px) !important;
    box-shadow: 0 4px 8px rgba(40, 167, 69, 0.4) !important;
}

.btn-deshabilitar {
    background-color: #dc3545 !important;
    color: white !important;
    font-weight: bold !important;
    padding: 8px 12px !important;
    border: 2px solid #dc3545 !important;
    box-shadow: 0 2px 4px rgba(220, 53, 69, 0.3) !important;
}

.btn-deshabilitar:hover {
    background-color: #c82333 !important;
    border-color: #bd2130 !important;
    transform: translateY(-1px) !important;
    box-shadow: 0 4px 8px rgba(220, 53, 69, 0.4) !important;
}
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-database text-primary"></i>
                        Gestionar Estado de Bases de Datos
                    </h4>
                    <a href="index.php?action=dashboard" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Volver al Dashboard
                    </a>
                </div>
                <div class="card-body">
                    <!-- Barra de búsqueda -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="input-group">
                                <input type="text" id="buscarBases" class="form-control" placeholder="Buscar base de datos por nombre...">
                                <div class="input-group-append">
                                    <button class="btn btn-outline-primary" type="button" id="btnBuscar">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" id="soloHabilitadas" checked>
                                <label class="form-check-label" for="soloHabilitadas">
                                    Solo mostrar bases habilitadas
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Tabla de bases de datos -->
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="thead-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre de la Base</th>
                                    <th>Tipo</th>
                                    <th>Fecha de Carga</th>
                                    <th>Total Clientes</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="tablaBases">
                                <?php foreach ($bases_datos as $base): ?>
                                <tr data-base-id="<?= $base['id'] ?>">
                                    <td><?= $base['id'] ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($base['nombre_cargue']) ?></strong>
                                    </td>
                                    <td>
                                        <span class="badge badge-info">
                                            <?= htmlspecialchars($base['tipo_base_datos'] ?? 'independiente') ?>
                                        </span>
                                    </td>
                                    <td><?= date('d/m/Y H:i', strtotime($base['fecha_cargue'])) ?></td>
                                    <td><?= (int)($base['total_clientes'] ?? 0) ?></td>
                                    <td>
                                        <?php if ($base['estado_habilitado'] === 'habilitado'): ?>
                                            <span class="badge badge-success">
                                                <i class="fas fa-check-circle"></i> Habilitado
                                            </span>
                                        <?php else: ?>
                                            <span class="badge badge-danger">
                                                <i class="fas fa-times-circle"></i> Deshabilitado
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <?php if ($base['estado_habilitado'] === 'habilitado'): ?>
                                                <button class="btn btn-sm btn-deshabilitar" onclick="cambiarEstado(<?= $base['id'] ?>, 'deshabilitado')" 
                                                        title="Deshabilitar Base de Datos">
                                                    <i class="fas fa-ban"></i> Deshabilitar
                                                </button>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-habilitar" onclick="cambiarEstado(<?= $base['id'] ?>, 'habilitado')" 
                                                        title="Habilitar Base de Datos">
                                                    <i class="fas fa-check"></i> Habilitar
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Mensaje cuando no hay resultados -->
                    <div id="sinResultados" class="text-center py-4" style="display: none;">
                        <i class="fas fa-search fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No se encontraron bases de datos</h5>
                        <p class="text-muted">Intenta con otros términos de búsqueda</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal informativo (al frente, para que el usuario sepa qué pasó) -->
<style>
#modalEstado { z-index: 1060; }
#modalBackdropEstado { z-index: 1050; }
</style>
<div class="modal fade" id="modalEstado" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalEstadoTitulo">Estado de la base</h5>
                <button type="button" class="close" onclick="cerrarModalEstado()" aria-label="Cerrar">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body text-center py-4">
                <p id="modalEstadoMensaje" class="mb-0"></p>
            </div>
        </div>
    </div>
</div>

<script>
// Función para buscar bases de datos
function buscarBases() {
    const termino = document.getElementById('buscarBases').value;
    const soloHabilitadas = document.getElementById('soloHabilitadas').checked;
    
    fetch('index.php?action=buscar_bases_datos', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `termino_busqueda=${encodeURIComponent(termino)}&solo_habilitadas=${soloHabilitadas}`
    })
    .then(response => response.json())
    .then(data => {
        mostrarResultados(data);
    })
    .catch(error => {
        console.error('Error:', error);
        mostrarMensaje('Error al buscar bases de datos', 'error');
    });
}

// Formatear fecha de la base (MySQL datetime o ISO)
function formatFechaBase(fecha) {
    if (!fecha) return '-';
    const d = new Date(fecha);
    return isNaN(d.getTime()) ? fecha : d.toLocaleDateString('es-ES') + ' ' + d.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });
}

// Función para mostrar resultados de búsqueda
function mostrarResultados(bases) {
    const tabla = document.getElementById('tablaBases');
    const sinResultados = document.getElementById('sinResultados');
    
    if (bases.length === 0) {
        tabla.innerHTML = '';
        sinResultados.style.display = 'block';
        return;
    }
    
    sinResultados.style.display = 'none';
    
    let html = '';
    bases.forEach(base => {
        const estadoBadge = base.estado_habilitado === 'habilitado' 
            ? '<span class="badge badge-success"><i class="fas fa-check-circle"></i> Habilitado</span>'
            : '<span class="badge badge-danger"><i class="fas fa-times-circle"></i> Deshabilitado</span>';
        
        const botonAccion = base.estado_habilitado === 'habilitado'
            ? `<button class="btn btn-sm btn-deshabilitar" onclick="cambiarEstado(${base.id}, 'deshabilitado')" title="Deshabilitar Base de Datos"><i class="fas fa-ban"></i> Deshabilitar</button>`
            : `<button class="btn btn-sm btn-habilitar" onclick="cambiarEstado(${base.id}, 'habilitado')" title="Habilitar Base de Datos"><i class="fas fa-check"></i> Habilitar</button>`;
        
        html += `
            <tr data-base-id="${base.id}">
                <td>${base.id}</td>
                <td><strong>${base.nombre_cargue}</strong></td>
                <td><span class="badge badge-info">${base.tipo_base_datos || 'independiente'}</span></td>
                <td>${formatFechaBase(base.fecha_cargue || base.fecha_carga)}</td>
                <td>${base.total_clientes || 0}</td>
                <td>${estadoBadge}</td>
                <td>
                    <div class="btn-group" role="group">
                        ${botonAccion}
                    </div>
                </td>
            </tr>
        `;
    });
    
    tabla.innerHTML = html;
}

// Modal informativo al frente (sin confirmación)
function mostrarModalEstado(titulo, mensaje) {
    const modal = document.getElementById('modalEstado');
    const tituloEl = document.getElementById('modalEstadoTitulo');
    const mensajeEl = document.getElementById('modalEstadoMensaje');
    if (modal && tituloEl && mensajeEl) {
        tituloEl.textContent = titulo;
        mensajeEl.textContent = mensaje;
        modal.classList.add('show');
        modal.style.display = 'block';
        document.body.classList.add('modal-open');
        let backdrop = document.getElementById('modalBackdropEstado');
        if (!backdrop) {
            backdrop = document.createElement('div');
            backdrop.className = 'modal-backdrop fade show';
            backdrop.id = 'modalBackdropEstado';
            backdrop.onclick = cerrarModalEstado;
            document.body.appendChild(backdrop);
        }
    }
}
function cerrarModalEstado() {
    const modal = document.getElementById('modalEstado');
    if (modal) {
        modal.classList.remove('show');
        modal.style.display = 'none';
        document.body.classList.remove('modal-open');
        const backdrop = document.getElementById('modalBackdropEstado');
        if (backdrop) backdrop.remove();
    }
}

// Cambiar estado de la base: se ejecuta directo y se muestra modal informativo al frente
function cambiarEstado(baseId, nuevoEstado) {
    const accion = nuevoEstado === 'habilitado' ? 'Habilitar' : 'Deshabilitar';
    mostrarModalEstado('Procesando', accion + ' base de datos...');

    const formData = new FormData();
    formData.append('carga_id', baseId);
    formData.append('nuevo_estado', nuevoEstado);

    fetch('index.php?action=cambiar_estado_base', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(data => {
        const ok = nuevoEstado === 'habilitado'
            ? 'Base habilitada correctamente.'
            : 'Base deshabilitada correctamente. Los asesores ya no tendrán acceso a esta base.';
        mostrarModalEstado('Listo', ok);
        buscarBases();
        setTimeout(cerrarModalEstado, 2500);
    })
    .catch(error => {
        console.error('Error:', error);
        mostrarModalEstado('Error', 'No se pudo actualizar el estado. Intenta de nuevo.');
        setTimeout(cerrarModalEstado, 3000);
    });
}

// Función para mostrar mensajes
function mostrarMensaje(mensaje, tipo) {
    const alertClass = tipo === 'success' ? 'alert-success' : 'alert-danger';
    const alert = `
        <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
            ${mensaje}
            <button type="button" class="close" data-dismiss="alert">
                <span>&times;</span>
            </button>
        </div>
    `;
    
    // Insertar al inicio del card-body
    const cardBody = document.querySelector('.card-body');
    cardBody.insertAdjacentHTML('afterbegin', alert);
    
    // Auto-remover después de 5 segundos
    setTimeout(() => {
        const alertElement = cardBody.querySelector('.alert');
        if (alertElement) {
            alertElement.remove();
        }
    }, 5000);
}

// Event listeners
document.getElementById('btnBuscar').addEventListener('click', buscarBases);
document.getElementById('buscarBases').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        buscarBases();
    }
});
document.getElementById('soloHabilitadas').addEventListener('change', buscarBases);

// Búsqueda inicial
document.addEventListener('DOMContentLoaded', function() {
    buscarBases();
});
</script>

<?php include __DIR__ . '/shared_footer.php'; ?>
</body>
</html>

