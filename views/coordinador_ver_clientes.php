<?php
// Archivo: views/coordinador_ver_clientes.php
// Vista para mostrar los clientes de una carga específica
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
    echo getNavbar('Clientes de la Carga', $_SESSION['user_role'] ?? '');
    ?>
    
    <div class="main-container">
        <!-- Encabezado -->
        <div class="card">
            <div class="card-header">
                👥 Clientes de la Carga: <?php echo htmlspecialchars($carga['nombre_cargue'] ?? 'N/A'); ?>
            </div>
            <div class="card-body">
                <h2>Lista de Clientes</h2>
                <p>Clientes cargados desde el archivo Excel el <?php echo date('d/m/Y', strtotime($carga['fecha_cargue'] ?? 'now')); ?></p>
                
                <div style="margin-bottom: 20px;">
                    <a href="index.php?action=dashboard" class="btn btn-secondary">
                        ← Volver al Inicio
                    </a>
                    <a href="index.php?action=asignar_clientes&carga_id=<?php echo $carga['id']; ?>" class="btn btn-success">
                        📋 Asignar Clientes
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Estadísticas -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $total_clientes; ?></div>
                <div class="stat-label">Total Clientes</div>
                <p class="mt-20">Clientes en esta carga</p>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?php echo count($asesores); ?></div>
                <div class="stat-label">Asesores Disponibles</div>
                <p class="mt-20">Para asignar clientes</p>
            </div>
        </div>
        
        <!-- Lista de Clientes -->
        <div class="card">
            <div class="card-header">
                📋 Lista de Clientes
            </div>
            <div class="card-body">
                <!-- Barra de búsqueda -->
                <div class="search-container" style="margin-bottom: 20px;">
                    <form id="searchForm" class="search-form" onsubmit="return false;">
                        <div class="search-box">
                            <input type="text" id="searchInput" name="search" placeholder="Buscar por nombre, cédula, teléfono, celular o email... (mín. 2 caracteres)" 
                                   class="form-control" style="padding: 10px 15px; border-radius: 25px 0 0 25px; border: 2px solid #e9ecef; border-right: none;">
                            <button type="button" id="searchButton" class="btn btn-primary search-btn" style="border-radius: 0 25px 25px 0; padding: 10px 20px;">
                                <i class="fas fa-search"></i> Buscar
                            </button>
                        </div>
                        <div class="search-actions" style="margin-top: 10px;">
                            <button type="button" id="clearSearch" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-times"></i> Limpiar
                            </button>
                            <span class="search-results-info" id="searchResultsInfo" style="margin-left: 15px; color: #6c757d; font-size: 14px;">
                                Mostrando <?php echo count($clientes_vista); ?> de <?php echo $total_clientes; ?> clientes
                                <?php if ($total_clientes > 200): ?>
                                    <span class="badge badge-warning ml-2">Usa la búsqueda para ver todos</span>
                                <?php endif; ?>
                            </span>
                        </div>
                    </form>
                </div>
                
                <?php if (!empty($clientes_vista)): ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Cédula</th>
                                    <th>Teléfono</th>
                                    <th>Celular</th>
                                    <th>Email</th>
                                    <th>Estado</th>
                                    <th style="width: 72px; text-align: center;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="clientesTableBody">
                                <?php foreach ($clientes_vista as $cliente): ?>
                                    <tr class="cliente-row" 
                                        data-nombre="<?php echo strtolower(htmlspecialchars($cliente['nombre'] ?? '')); ?>"
                                        data-cedula="<?php echo htmlspecialchars($cliente['cedula'] ?? ''); ?>"
                                        data-telefono="<?php echo htmlspecialchars($cliente['telefono'] ?? ''); ?>"
                                        data-celular="<?php echo htmlspecialchars($cliente['celular2'] ?? ''); ?>"
                                        data-email="<?php echo strtolower(htmlspecialchars($cliente['email'] ?? '')); ?>">
                                        <td><?php echo htmlspecialchars($cliente['nombre'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($cliente['cedula'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($cliente['telefono'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($cliente['celular2'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($cliente['email'] ?? 'N/A'); ?></td>
                                        <td>
                                            <?php
                                            $estado = (string)($cliente['estado'] ?? '');
                                            if ($estado === '') $estado = 'activo';
                                            ?>
                                            <span class="badge <?php echo $estado === 'activo' ? 'badge-success' : 'badge-secondary'; ?>">
                                                <?php echo htmlspecialchars($estado); ?>
                                            </span>
                                        </td>
                                        <td style="text-align: center;">
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-primary js-ver-obligaciones"
                                                    data-cliente-id="<?php echo (int)($cliente['id'] ?? 0); ?>"
                                                    data-cliente-nombre="<?php echo htmlspecialchars((string)($cliente['nombre'] ?? '')); ?>"
                                                    title="Ver obligaciones del cliente"
                                                    aria-label="Ver obligaciones">
                                                <span aria-hidden="true" style="font-size: 16px; line-height: 1;">👁</span>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <strong>No hay clientes en esta carga.</strong>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        // #region debug d200d9 modal + icon
        function dbglog(location, message, data, hypothesisId, runId) {
            try {
                fetch('http://127.0.0.1:7559/ingest/0bcc0192-fe61-4fb0-b109-b4792228bcf7', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-Debug-Session-Id': 'b7eaa7' },
                    body: JSON.stringify({
                        sessionId: 'b7eaa7',
                        runId: runId || 'pre',
                        hypothesisId: hypothesisId || 'M0',
                        location: location,
                        message: message,
                        data: data || {},
                        timestamp: Date.now()
                    })
                }).catch(function () { });
            } catch (e) { }
        }
        // #endregion

        // Funcionalidad de búsqueda AJAX
        document.addEventListener('DOMContentLoaded', function() {
            const searchForm = document.getElementById('searchForm');
            const searchInput = document.getElementById('searchInput');
            const searchButton = document.getElementById('searchButton');
            const clearSearchBtn = document.getElementById('clearSearch');
            const clientesTableBody = document.getElementById('clientesTableBody');
            const searchResultsInfo = document.getElementById('searchResultsInfo');

            // Modal bindings
            const modal = document.getElementById('obligacionesModal');
            const modalClose = document.getElementById('closeObligacionesModal');
            const modalBody = document.getElementById('obligacionesModalBody');
            const modalSubtitle = document.getElementById('obligacionesModalSubtitle');
            const modalStatus = document.getElementById('obligacionesModalStatus');
            
            let searchTimeout;
            const cargaId = '<?php echo $carga_id; ?>';

            dbglog('views/coordinador_ver_clientes.php:DOMContentLoaded', 'init', {
                cargaId: String(cargaId),
                hasModal: !!modal,
                actionButtons: document.querySelectorAll('.js-ver-obligaciones').length
            }, 'M1');

            function openModal() {
                if (!modal) return;
                modal.style.display = 'block';
            }
            function closeModal() {
                if (!modal) return;
                modal.style.display = 'none';
            }
            if (modalClose) modalClose.addEventListener('click', closeModal);
            if (modal) {
                modal.addEventListener('click', function(e) {
                    if (e.target === modal) closeModal();
                });
            }

            function formatCOP(value) {
                const n = Number(String(value ?? '').replace(/[^\d.-]/g, ''));
                const safe = Number.isFinite(n) ? n : 0;
                try {
                    return new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 }).format(safe);
                } catch (e) {
                    return '$ ' + String(Math.round(safe)).replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                }
            }

            async function cargarObligaciones(clienteId, clienteNombre) {
                modalSubtitle.textContent = `${clienteNombre || ''}`.trim();
                modalStatus.style.display = 'block';
                modalStatus.className = 'alert alert-info';
                modalStatus.textContent = 'Cargando obligaciones...';
                modalBody.innerHTML = '<tr><td colspan="6" class="text-center">Cargando...</td></tr>';

                const url = `index.php?action=obtener_obligaciones_cliente&carga_id=${encodeURIComponent(String(cargaId))}&cliente_id=${encodeURIComponent(String(clienteId))}`;
                dbglog('views/coordinador_ver_clientes.php:cargarObligaciones', 'fetch_start', { url }, 'M2');

                try {
                    const res = await fetch(url, { method: 'GET', credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                    const text = await res.text();
                    dbglog('views/coordinador_ver_clientes.php:cargarObligaciones', 'fetch_response', { status: res.status, textLen: text.length }, 'M2');
                    let data;
                    try { data = JSON.parse(text); } catch (e) { data = null; }
                    if (!data || !data.success) {
                        modalStatus.className = 'alert alert-error';
                        modalStatus.textContent = (data && data.error) ? String(data.error) : 'Respuesta no válida del servidor';
                        modalBody.innerHTML = '<tr><td colspan="6" class="text-center">Sin datos</td></tr>';
                        return;
                    }
                    const obligs = Array.isArray(data.obligaciones) ? data.obligaciones : [];
                    modalStatus.style.display = 'none';
                    if (!obligs.length) {
                        modalBody.innerHTML = '<tr><td colspan="6" class="text-center">Sin obligaciones</td></tr>';
                        return;
                    }
                    modalBody.innerHTML = obligs.map(o => `
                        <tr>
                            <td>${(o.numero_factura ?? 'N/A')}</td>
                            <td>${(o.rmt ?? 'N/A')}</td>
                            <td>${(o.numero_contrato ?? 'N/A')}</td>
                            <td>${formatCOP(o.saldo)}</td>
                            <td>${(o.dias_mora ?? '0')}</td>
                            <td>${(o.franja ?? 'N/A')}</td>
                        </tr>
                    `).join('');
                } catch (e) {
                    modalStatus.className = 'alert alert-error';
                    modalStatus.textContent = 'Error cargando obligaciones: ' + String(e && e.message || e);
                    modalBody.innerHTML = '<tr><td colspan="6" class="text-center">Sin datos</td></tr>';
                }
            }

            // Delegación de eventos para los botones de acciones (incluye resultados AJAX)
            document.addEventListener('click', function(e) {
                const btn = e.target && e.target.closest ? e.target.closest('.js-ver-obligaciones') : null;
                if (!btn) return;
                e.preventDefault();
                const clienteId = btn.getAttribute('data-cliente-id');
                const clienteNombre = btn.getAttribute('data-cliente-nombre') || '';
                dbglog('views/coordinador_ver_clientes.php:actions', 'open_modal', { clienteId: String(clienteId || ''), clienteNombreLen: clienteNombre.length }, 'M3');
                openModal();
                cargarObligaciones(clienteId, clienteNombre);
            });
            
            // Función para realizar búsqueda AJAX
            function performSearch(searchTerm, isManualSearch = false) {
                if (searchTerm === '') {
                    // Si no hay término de búsqueda, recargar página para mostrar todos
                    window.location.reload();
                    return;
                }
                
                // Mostrar indicador de carga
                searchResultsInfo.textContent = 'Buscando...';
                searchResultsInfo.style.color = '#007bff';
                
                // Realizar petición AJAX
                fetch(`index.php?action=buscar_clientes&carga_id=${cargaId}&search=${encodeURIComponent(searchTerm)}`, {
                    method: 'GET',
                    credentials: 'same-origin', // Incluir cookies de sesión
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                    .then(response => {
                        console.log('Response status:', response.status);
                        console.log('Response headers:', response.headers);
                        
                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }
                        
                        return response.text(); // Primero obtener como texto
                    })
                    .then(text => {
                        console.log('Response text:', text);
                        
                        try {
                            const data = JSON.parse(text);
                            console.log('Parsed JSON:', data);
                            
                            if (data.success) {
                                updateTable(data.clientes);
                                if (data.total === 0) {
                                    searchResultsInfo.textContent = `No se encontraron clientes con "${searchTerm}"`;
                                    searchResultsInfo.style.color = '#dc3545';
                                    
                                    // Mostrar alerta solo si es una búsqueda manual
                                    if (isManualSearch) {
                                        setTimeout(function() {
                                            alert(`No se encontró ningún cliente con la información: "${searchTerm}"\n\nIntenta buscar por:\n- Nombre completo o parcial\n- Cédula\n- Teléfono\n- Celular\n- Email`);
                                        }, 100);
                                    }
                                } else {
                                    searchResultsInfo.innerHTML = `Mostrando ${data.total} clientes encontrados <span class="badge badge-success ml-1">Búsqueda</span>`;
                                    searchResultsInfo.style.color = '#28a745';
                                }
                            } else {
                                searchResultsInfo.textContent = 'Error en la búsqueda: ' + (data.error || 'Error desconocido');
                                searchResultsInfo.style.color = '#dc3545';
                                console.error('Error:', data.error);
                            }
                        } catch (jsonError) {
                            console.error('JSON Parse Error:', jsonError);
                            console.error('Response text:', text);
                            searchResultsInfo.textContent = 'Error: Respuesta no válida del servidor';
                            searchResultsInfo.style.color = '#dc3545';
                        }
                    })
                    .catch(error => {
                        searchResultsInfo.textContent = 'Error en la búsqueda: ' + error.message;
                        searchResultsInfo.style.color = '#dc3545';
                        console.error('Fetch Error:', error);
                    });
            }
            
            // Función para actualizar la tabla con los resultados
            function updateTable(clientes) {
                let html = '';
                if (clientes.length === 0) {
                    html = '<tr><td colspan="7" class="text-center">No se encontraron clientes</td></tr>';
                } else {
                    clientes.forEach(function(cliente) {
                        const estadoVal = (cliente.estado || 'activo');
                        const estadoBadge = (estadoVal === 'activo')
                            ? '<span class="badge badge-success">activo</span>'
                            : `<span class="badge badge-secondary">${estadoVal}</span>`;
                        
                        html += `
                            <tr class="cliente-row">
                                <td>${cliente.nombre}</td>
                                <td>${cliente.cedula}</td>
                                <td>${cliente.telefono}</td>
                                <td>${cliente.celular}</td>
                                <td>${cliente.email}</td>
                                <td>${estadoBadge}</td>
                                <td style="text-align: center;">
                                    <button type="button"
                                            class="btn btn-sm btn-outline-primary js-ver-obligaciones"
                                            data-cliente-id="${cliente.id}"
                                            data-cliente-nombre="${String(cliente.nombre || '').replaceAll('"','&quot;')}"
                                            title="Ver obligaciones del cliente"
                                            aria-label="Ver obligaciones">
                                        <span aria-hidden="true" style="font-size: 16px; line-height: 1;">👁</span>
                                    </button>
                                </td>
                            </tr>
                        `;
                    });
                }
                clientesTableBody.innerHTML = html;
            }
            
            // Búsqueda con debounce (esperar 500ms después de que el usuario deje de escribir)
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                const searchTerm = this.value.trim();
                
                if (searchTerm.length >= 2) {
                    searchTimeout = setTimeout(function() {
                        performSearch(searchTerm);
                    }, 500);
                } else if (searchTerm === '') {
                    // Si se borra todo, recargar página
                    window.location.reload();
                }
            });
            
            // Búsqueda al hacer clic en el botón buscar
            searchButton.addEventListener('click', function() {
                const searchTerm = searchInput.value.trim();
                if (searchTerm.length >= 2) {
                    performSearch(searchTerm, true);
                } else {
                    alert('Por favor ingresa al menos 2 caracteres para buscar');
                }
            });
            
            // Búsqueda al enviar el formulario (Enter)
            searchForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const searchTerm = searchInput.value.trim();
                if (searchTerm.length >= 2) {
                    performSearch(searchTerm, true);
                } else {
                    alert('Por favor ingresa al menos 2 caracteres para buscar');
                }
                return false;
            });
            
            // Botón limpiar búsqueda
            clearSearchBtn.addEventListener('click', function() {
                searchInput.value = '';
                window.location.reload();
            });
            
            // Limpiar búsqueda con Escape
            searchInput.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    this.value = '';
                    window.location.reload();
                }
            });
        });
    </script>

    <!-- Modal Obligaciones -->
    <div id="obligacionesModal" class="modal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:9999;">
        <div class="modal-dialog" style="background:#fff; max-width:900px; margin:5vh auto; border-radius:12px; overflow:hidden;">
            <div class="modal-header" style="display:flex; justify-content:space-between; align-items:center; padding:14px 16px; border-bottom:1px solid #e9ecef;">
                <div>
                    <strong>Obligaciones del cliente</strong>
                    <div id="obligacionesModalSubtitle" style="color:#6c757d; font-size: 13px; margin-top:2px;"></div>
                </div>
                <button type="button" id="closeObligacionesModal" class="btn btn-sm btn-outline-secondary" aria-label="Cerrar">Cerrar</button>
            </div>
            <div class="modal-body" style="padding:16px;">
                <div id="obligacionesModalStatus" class="alert alert-info" style="margin-bottom: 12px; display:none;"></div>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Número factura</th>
                                <th>RMT</th>
                                <th>Contrato</th>
                                <th>Saldo</th>
                                <th>Días mora</th>
                                <th>Franja</th>
                            </tr>
                        </thead>
                        <tbody id="obligacionesModalBody">
                            <tr><td colspan="6" class="text-center">Sin datos</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <style>
        .modal[aria-hidden="true"] { display: none; }
        .search-container {
            position: relative;
        }
        
        .search-form {
            max-width: 600px;
        }
        
        .search-box {
            display: flex;
            position: relative;
            max-width: 500px;
        }
        
        .search-box input {
            flex: 1;
            transition: all 0.3s ease;
        }
        
        .search-box input:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
            z-index: 2;
        }
        
        .search-btn {
            transition: all 0.3s ease;
            border: 2px solid #007bff;
            border-left: none;
        }
        
        .search-btn:hover {
            background-color: #0056b3;
            border-color: #0056b3;
        }
        
        .search-actions {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .search-results-info {
            font-weight: 500;
        }
        
        .search-box:focus-within .search-btn {
            background-color: #0056b3;
            border-color: #0056b3;
        }
        
        .search-box input:focus + .search-btn {
            background-color: #0056b3;
            border-color: #0056b3;
        }
        
        .table-responsive {
            max-height: 600px;
            overflow-y: auto;
        }
        
        .cliente-row.highlight {
            background-color: #fff3cd !important;
            border-left: 4px solid #ffc107;
        }
        
        .cliente-row {
            transition: all 0.3s ease;
        }
        
        .cliente-row:hover {
            background-color: #f8f9fa;
        }
        
        .search-results-info {
            font-style: italic;
        }
        
        @media (max-width: 768px) {
            .search-box {
                max-width: 100%;
            }
        }
    </style>
    
    <?php require_once 'shared_footer.php'; ?>
</body>
</html>

