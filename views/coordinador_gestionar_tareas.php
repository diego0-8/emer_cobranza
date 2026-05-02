<?php
// Archivo: views/coordinador_gestionar_tareas.php
// Vista para que el coordinador gestione tareas específicas para asesores

// Defaults para evitar notices/lints si falta data del controller.
$page_title = isset($page_title) ? (string)$page_title : 'Gestión de Tareas';
$cargas = isset($cargas) && is_array($cargas) ? $cargas : [];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once __DIR__ . '/shared_navbar.php'; renderPageHead($page_title ?? ''); ?>
    <?php require_once __DIR__ . '/shared_styles.php'; ?>
    <link rel="stylesheet" href="css/common-styles.css">
</head>
<body>
    <?php 
    require_once __DIR__ . '/shared_navbar.php';
    echo getNavbar('Gestión de Tareas', $_SESSION['user_role'] ?? ''); 
    ?>
    
    <div class="main-container">
        <div class="page-header text-center">
            <h1><?php echo htmlspecialchars($page_title); ?></h1>
            <p class="page-description">Asigna clientes específicos a asesores mediante tareas personalizadas</p>
            <div class="header-actions">
                <button type="button" class="btn btn-primary" onclick="abrirModalCrearTarea()">
                    <i class="fas fa-plus"></i> Crear Nueva Tarea
                </button>
                <a href="index.php?action=list_cargas" class="btn btn-outline-primary">
                    <i class="fas fa-database"></i> Gestionar Bases
                </a>
            </div>
        </div>

        <?php if (!empty($_SESSION['success_message'])): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($_SESSION['success_message'] ?? ''); ?>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <?php if (!empty($_SESSION['error_message'])): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($_SESSION['error_message'] ?? ''); ?>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <?php if (!empty($_SESSION['csv_import_report']) && is_array($_SESSION['csv_import_report'])): ?>
            <?php $r = $_SESSION['csv_import_report']; ?>
            <details class="alert" style="border:1px solid #d1d5db; background:#f9fafb; padding:12px; border-radius:8px; margin-bottom:16px;">
                <summary style="cursor:pointer; font-weight:600;">
                    Reporte de importación CSV (<?php echo !empty($r['ok']) ? 'OK' : 'ERROR'; ?>) — clic para ver detalles
                </summary>
                <div style="margin-top:10px; font-size:14px; line-height:1.4;">
                    <div><strong>Archivo:</strong> <?php echo htmlspecialchars((string)($r['archivo'] ?? '')); ?></div>
                    <div><strong>Base:</strong> <?php echo (int)($r['base_id'] ?? 0); ?> <strong>Asesor:</strong> <?php echo htmlspecialchars((string)($r['asesor'] ?? '')); ?></div>
                    <div><strong>Nombre tarea:</strong> <?php echo htmlspecialchars((string)($r['nombre_tarea'] ?? '')); ?></div>
                    <div><strong>Separador:</strong> <?php echo htmlspecialchars((string)($r['delimiter'] ?? '')); ?> <strong>Header detectado:</strong> <?php echo isset($r['header_detected']) ? ( ($r['header_detected'] ? 'sí' : 'no') ) : ''; ?></div>
                    <div><strong>Columna cédula (índice):</strong> <?php echo isset($r['cedula_column_index']) ? (int)$r['cedula_column_index'] : ''; ?></div>
                    <hr style="margin:10px 0;">
                    <div><strong>Cédulas (raw):</strong> <?php echo (int)($r['cedulas_raw_count'] ?? 0); ?></div>
                    <div><strong>Válidas (únicas):</strong> <?php echo (int)($r['cedulas_valid_unique'] ?? 0); ?></div>
                    <div><strong>Encontradas en base:</strong> <?php echo (int)($r['cedulas_found_count'] ?? 0); ?></div>
                    <div><strong>No encontradas:</strong> <?php echo (int)($r['cedulas_not_found_count'] ?? 0); ?></div>

                    <?php if (!empty($r['error'])): ?>
                        <hr style="margin:10px 0;">
                        <div><strong>Error:</strong> <?php echo htmlspecialchars((string)$r['error']); ?></div>
                        <?php if (!empty($r['error_detail'])): ?>
                            <div><strong>Detalle:</strong> <?php echo htmlspecialchars((string)$r['error_detail']); ?></div>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php if (!empty($r['samples']['invalid']) && is_array($r['samples']['invalid'])): ?>
                        <hr style="margin:10px 0;">
                        <div><strong>Ejemplos inválidos (como venían en el CSV):</strong></div>
                        <div style="font-family:monospace; white-space:pre-wrap;"><?php echo htmlspecialchars(implode("\n", array_slice($r['samples']['invalid'], 0, 10))); ?></div>
                    <?php endif; ?>

                    <?php if (!empty($r['samples']['not_found']) && is_array($r['samples']['not_found'])): ?>
                        <hr style="margin:10px 0;">
                        <div><strong>Ejemplos no encontrados en la base:</strong></div>
                        <div style="font-family:monospace; white-space:pre-wrap;"><?php echo htmlspecialchars(implode("\n", array_slice($r['samples']['not_found'], 0, 10))); ?></div>
                    <?php endif; ?>
                </div>
            </details>
            <?php unset($_SESSION['csv_import_report']); ?>
        <?php endif; ?>

        <!-- Estadísticas de tareas -->
        <div class="stats-grid" style="margin-bottom: 30px;">
            <div class="stat-card">
                <div class="stat-icon">📋</div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo $estadisticas['total_tareas'] ?? 0; ?></div>
                    <div class="stat-label">Total Tareas</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">⏳</div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo $estadisticas['pendientes'] ?? 0; ?></div>
                    <div class="stat-label">Pendientes</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">🔄</div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo $estadisticas['en_proceso'] ?? 0; ?></div>
                    <div class="stat-label">En Proceso</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">✅</div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo $estadisticas['completadas'] ?? 0; ?></div>
                    <div class="stat-label">Completadas</div>
                </div>
            </div>
        </div>

        <!-- Lista de tareas existentes -->
        <div class="tareas-section">
            <h3><i class="fas fa-tasks"></i> Tareas Existentes</h3>
            
            <?php if (empty($tareas)): ?>
                <div class="empty-state">
                    <i class="fas fa-clipboard-list"></i>
                    <h3>No hay tareas creadas</h3>
                    <p>Crea tu primera tarea para asignar clientes específicos a un asesor</p>
                    <button type="button" class="btn btn-primary" onclick="abrirModalCrearTarea()">
                        <i class="fas fa-plus"></i> Crear Primera Tarea
                    </button>
                </div>
            <?php else: ?>
                <div class="tareas-list">
                    <?php foreach ($tareas as $tarea): ?>
                        <div class="tarea-item">
                            <div class="tarea-header">
                                <div class="tarea-info">
                                    <h4>Tarea de <?php echo htmlspecialchars($tarea['asesor_nombre'] ?? ''); ?></h4>
                                    <div class="tarea-meta">
                                        <span class="badge badge-<?php echo $tarea['estado'] === 'completada' ? 'success' : ($tarea['estado'] === 'en_proceso' ? 'warning' : 'secondary'); ?>">
                                            <?php echo ucfirst($tarea['estado']); ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="tarea-actions">
                                    <button class="btn btn-sm btn-outline-primary" onclick="verDetallesTarea(<?php echo $tarea['id']; ?>)">
                                        <i class="fas fa-eye"></i> Ver Detalles
                                    </button>
                                    <?php if ($tarea['estado'] !== 'completada'): ?>
                                        <button class="btn btn-sm btn-success" onclick="marcarCompletada(<?php echo $tarea['id']; ?>)">
                                            <i class="fas fa-check"></i> Completar
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="tarea-body">
                                <div class="tarea-details">
                                    <div class="detail-item">
                                        <strong>Base:</strong> <?php echo htmlspecialchars($tarea['nombre_cargue'] ?? ''); ?>
                                    </div>
                                    <div class="detail-item">
                                        <strong>Clientes:</strong> <?php echo $tarea['total_clientes'] ?? 0; ?> asignados
                                    </div>
                                    <div class="detail-item">
                                        <strong>Creada:</strong> <?php echo date('d/m/Y H:i', strtotime($tarea['fecha_creacion'])); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal para crear nueva tarea -->
    <div class="modal" id="modalCrearTarea" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-plus"></i> Crear Nueva Tarea</h3>
                <button type="button" class="close" onclick="cerrarModalCrearTarea()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="formCrearTarea" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="nombre_tarea">Nombre de la tarea <span class="required">*</span></label>
                        <input type="text" id="nombre_tarea" name="nombre_tarea" class="form-control" required
                               placeholder="Ej: RETANQUEO - Semana 18">
                    </div>

                    <div class="form-group">
                        <label for="metodo_asignacion">Método de asignación <span class="required">*</span></label>
                        <select id="metodo_asignacion" name="metodo_asignacion" class="form-control" onchange="onMetodoAsignacionChange()">
                            <option value="auto" selected>Automática (por filtros)</option>
                            <option value="csv">Por CSV (cédulas)</option>
                        </select>
                        <small class="form-text text-muted">
                            En CSV debes subir un archivo con una columna llamada <strong>cedula</strong>.
                        </small>
                    </div>
                    <div class="form-group">
                        <label for="carga_id">Base de Datos <span class="required">*</span></label>
                        <select id="carga_id" name="carga_id" required class="form-control" onchange="cargarAsesoresYClientes()">
                            <option value="">Selecciona una base...</option>
                            <?php foreach ($cargas as $carga): ?>
                                <option value="<?php echo $carga['id']; ?>">
                                    <?php echo htmlspecialchars($carga['nombre_cargue']); ?> 
                                    (<?php echo $carga['total_clientes'] ?? 0; ?> clientes)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="asesor_id">Asesor <span class="required">*</span></label>
                        <select id="asesor_id" name="asesor_id" required class="form-control">
                            <option value="">Primero selecciona una base de datos</option>
                        </select>
                    </div>

                    <div class="form-group" id="grupo-filtros">
                        <label>Filtros por obligaciones</label>
                        <div style="display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:10px;">
                            <div>
                                <label for="saldo_min">Saldo mínimo</label>
                                <input type="number" step="0.01" id="saldo_min" name="saldo_min" class="form-control" placeholder="0">
                            </div>
                            <div>
                                <label for="saldo_max">Saldo máximo</label>
                                <input type="number" step="0.01" id="saldo_max" name="saldo_max" class="form-control" placeholder="0">
                            </div>
                            <div>
                                <label for="mora_min">Días de mora mínimo</label>
                                <input type="number" id="mora_min" name="mora_min" class="form-control" placeholder="0">
                            </div>
                            <div>
                                <label for="mora_max">Días de mora máximo</label>
                                <input type="number" id="mora_max" name="mora_max" class="form-control" placeholder="0">
                            </div>
                            <div style="grid-column:1 / -1;">
                                <label for="franja">Franja</label>
                                <select id="franja" name="franja" class="form-control">
                                    <option value="">Todas</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group" id="grupo-gestion">
                        <label>Filtros por gestión</label>
                        <div class="alert alert-info" style="margin-bottom:10px;">
                            <label style="display:flex; align-items:center; gap:8px; margin:0;">
                                <input type="checkbox" id="solo_no_gestionados" name="solo_no_gestionados" value="1" checked>
                                <span>Solo clientes sin gestiones (no gestionados)</span>
                            </label>
                            <small class="form-text text-muted" style="margin-top:6px;">
                                Si desmarcas esta opción, podrás filtrar por forma/tipo/resultado/razón y se incluirán clientes con historial.
                            </small>
                        </div>
                        <div style="display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:10px;">
                            <div>
                                <label for="forma_contacto">Forma de contacto</label>
                                <select id="forma_contacto" name="forma_contacto" class="form-control">
                                    <option value="">Todas</option>
                                </select>
                            </div>
                            <div>
                                <label for="tipo_contacto">Tipo de contacto</label>
                                <select id="tipo_contacto" name="tipo_contacto" class="form-control">
                                    <option value="">Todos</option>
                                </select>
                            </div>
                            <div>
                                <label for="resultado_contacto">Resultado de contacto</label>
                                <select id="resultado_contacto" name="resultado_contacto" class="form-control">
                                    <option value="">Todos</option>
                                </select>
                            </div>
                            <div>
                                <label for="razon_especifica">Razón específica</label>
                                <select id="razon_especifica" name="razon_especifica" class="form-control">
                                    <option value="">Todas</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group" id="grupo-asignacion-auto">
                        <label>Asignar Clientes <span class="required">*</span></label>
                        <div id="clientes-info" class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <span id="clientes-disponibles">Selecciona una base de datos para ver los clientes disponibles</span>
                        </div>
                        <div class="form-group">
                            <label for="cantidad_clientes">Cantidad de Clientes a Asignar</label>
                            <input type="number" id="cantidad_clientes" name="cantidad_clientes" 
                                   class="form-control" min="1" max="1000" 
                                   placeholder="Ingresa la cantidad de clientes">
                            <small class="form-text text-muted">
                                Se asignarán clientes no gestionados de forma aleatoria
                            </small>
                        </div>
                    </div>

                    <div class="form-group" id="grupo-asignacion-csv" style="display:none;">
                        <label for="csv_cedulas">Archivo CSV (cédulas) <span class="required">*</span></label>
                        <input type="file" id="csv_cedulas" name="csv_cedulas" accept=".csv" class="form-control">
                        <small class="form-text text-muted">
                            Solo se asignarán cédulas que existan en la base seleccionada.
                        </small>
                    </div>
                    
                    <div class="modal-actions">
                        <button type="button" class="btn btn-secondary" onclick="cerrarModalCrearTarea()">
                            <i class="fas fa-times"></i> Cancelar
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Crear Tarea
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal para ver detalles de tarea -->
    <div class="modal" id="modalDetallesTarea" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-eye"></i> Detalles de la Tarea</h3>
                <button type="button" class="close" onclick="cerrarModalDetalles()">&times;</button>
            </div>
            <div class="modal-body">
                <div id="detalles-tarea-content">
                    <!-- Se cargará dinámicamente -->
                </div>
            </div>
        </div>
    </div>

    <script>
        function abrirModalCrearTarea() {
            document.getElementById('modalCrearTarea').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function cerrarModalCrearTarea() {
            document.getElementById('modalCrearTarea').style.display = 'none';
            document.body.style.overflow = 'auto';
            document.getElementById('formCrearTarea').reset();
            // Limpiar selectores
            document.getElementById('asesor_id').innerHTML = '<option value="">Primero selecciona una base de datos</option>';
            document.getElementById('clientes-disponibles').textContent = 'Selecciona una base de datos para ver los clientes disponibles';
            try { onMetodoAsignacionChange(); } catch (e) {}
        }

        function onMetodoAsignacionChange() {
            const metodo = document.getElementById('metodo_asignacion')?.value || 'auto';
            const showAuto = metodo !== 'csv';
            const grupoFiltros = document.getElementById('grupo-filtros');
            const grupoGestion = document.getElementById('grupo-gestion');
            const grupoAuto = document.getElementById('grupo-asignacion-auto');
            const grupoCsv = document.getElementById('grupo-asignacion-csv');

            if (grupoFiltros) grupoFiltros.style.display = showAuto ? '' : 'none';
            if (grupoGestion) grupoGestion.style.display = showAuto ? '' : 'none';
            if (grupoAuto) grupoAuto.style.display = showAuto ? '' : 'none';
            if (grupoCsv) grupoCsv.style.display = showAuto ? 'none' : '';

            // Requeridos según método
            const cant = document.getElementById('cantidad_clientes');
            const csv = document.getElementById('csv_cedulas');
            if (cant) cant.required = showAuto;
            if (csv) csv.required = !showAuto;
        }

        function cargarAsesoresYClientes() {
            const cargaId = document.getElementById('carga_id').value;
            const asesorSelect = document.getElementById('asesor_id');
            const clientesInfo = document.getElementById('clientes-disponibles');
            
            if (!cargaId) {
                asesorSelect.innerHTML = '<option value="">Primero selecciona una base de datos</option>';
                clientesInfo.textContent = 'Selecciona una base de datos para ver los clientes disponibles';
                return;
            }
            
            // Mostrar loading
            asesorSelect.innerHTML = '<option value="">Cargando asesores...</option>';
            clientesInfo.textContent = 'Cargando información de clientes...';

            cargarOpcionesFiltrosTarea(cargaId);
            
            // Cargar asesores asignados a esta base
            fetch(`index.php?action=get_asesores_base&carga_id=${cargaId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        asesorSelect.innerHTML = '<option value="">Selecciona un asesor...</option>';
                        data.asesores.forEach(asesor => {
                            const option = document.createElement('option');
                            option.value = asesor.id;
                            option.textContent = asesor.nombre_completo;
                            asesorSelect.appendChild(option);
                        });
                    } else {
                        asesorSelect.innerHTML = '<option value="">Error al cargar asesores</option>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    asesorSelect.innerHTML = '<option value="">Error al cargar asesores</option>';
                });
            
            actualizarConteoFiltrado();
        }

        function cargarOpcionesFiltrosTarea(cargaId) {
            const franjaSel = document.getElementById('franja');
            const formaSel = document.getElementById('forma_contacto');
            const tipoSel = document.getElementById('tipo_contacto');
            const resSel = document.getElementById('resultado_contacto');
            const razonSel = document.getElementById('razon_especifica');

            const reset = (sel, placeholder) => {
                if (!sel) return;
                sel.innerHTML = `<option value="">${placeholder}</option>`;
            };

            reset(franjaSel, 'Todas');
            reset(formaSel, 'Todas');
            reset(tipoSel, 'Todos');
            reset(resSel, 'Todos');
            reset(razonSel, 'Todas');

            fetch(`index.php?action=get_opciones_filtros_tarea&carga_id=${encodeURIComponent(cargaId)}`, { credentials: 'same-origin' })
                .then(r => r.json())
                .then(data => {
                    if (!data || !data.success) return;
                    const addOptions = (sel, arr) => {
                        if (!sel || !Array.isArray(arr)) return;
                        arr.forEach(v => {
                            const opt = document.createElement('option');
                            opt.value = String(v);
                            opt.textContent = String(v);
                            sel.appendChild(opt);
                        });
                    };
                    addOptions(franjaSel, data.franjas);
                    addOptions(formaSel, data.forma_contacto);
                    addOptions(tipoSel, data.tipo_contacto);
                    addOptions(resSel, data.resultado_contacto);
                    addOptions(razonSel, data.razon_especifica);
                })
                .catch(err => console.error(err));
        }

        function getFiltrosFormQuery() {
            const getVal = (id) => (document.getElementById(id)?.value ?? '').toString().trim();
            const cargaId = getVal('carga_id');
            const soloNoGestionados = document.getElementById('solo_no_gestionados')?.checked ? '1' : '0';

            const params = new URLSearchParams();
            if (cargaId) params.set('carga_id', cargaId);

            // Obligaciones
            ['saldo_min','saldo_max','mora_min','mora_max','franja'].forEach(k => {
                const v = getVal(k);
                if (v !== '') params.set(k, v);
            });

            // Gestión
            params.set('solo_no_gestionados', soloNoGestionados);
            ['forma_contacto','tipo_contacto','resultado_contacto','razon_especifica'].forEach(k => {
                const v = getVal(k);
                if (v !== '') params.set(k, v);
            });
            return params.toString();
        }

        let _conteoTimer = null;
        function actualizarConteoFiltrado() {
            const cargaId = document.getElementById('carga_id').value;
            const clientesInfo = document.getElementById('clientes-disponibles');
            if (!cargaId) return;

            if (_conteoTimer) clearTimeout(_conteoTimer);
            _conteoTimer = setTimeout(() => {
                const qs = getFiltrosFormQuery();
                fetch(`index.php?action=get_clientes_no_gestionados&${qs}`, { credentials: 'same-origin' })
                    .then(r => r.json())
                    .then(data => {
                        if (!data || !data.success) {
                            clientesInfo.textContent = 'Error al cargar información de clientes';
                            return;
                        }
                        const totalFiltrados = Number(data.total_filtrados ?? 0);
                        const totalNoGestionados = Number(data.total_no_gestionados ?? 0);
                        const totalClientes = Number(data.total_clientes ?? 0);

                        const soloNoGest = document.getElementById('solo_no_gestionados')?.checked;
                        const baseMsg = soloNoGest
                            ? `No gestionados: ${totalNoGestionados} de ${totalClientes} clientes`
                            : `Clientes en base: ${totalClientes}`;

                        clientesInfo.textContent = `${baseMsg} | Total con filtro: ${totalFiltrados}`;
                        const inputCant = document.getElementById('cantidad_clientes');
                        if (inputCant) inputCant.max = Math.max(0, totalFiltrados);
                    })
                    .catch(err => {
                        console.error(err);
                        clientesInfo.textContent = 'Error al cargar información de clientes';
                    });
            }, 250);
        }

        // Manejar envío del formulario
        document.getElementById('formCrearTarea').addEventListener('submit', function(e) {
            e.preventDefault();

            const metodo = document.getElementById('metodo_asignacion')?.value || 'auto';
            const nombreTarea = (document.getElementById('nombre_tarea')?.value || '').trim();
            const asesorId = document.getElementById('asesor_id').value;
            const cargaId = document.getElementById('carga_id').value;
            
            if (!nombreTarea || !asesorId || !cargaId) {
                alert('Por favor completa todos los campos requeridos');
                return;
            }
            
            const formData = new FormData(this);
            
            let action = 'crear_tarea';
            if (metodo === 'csv') action = 'crear_tarea_csv';

            if (metodo !== 'csv') {
                const cantidadClientes = document.getElementById('cantidad_clientes').value;
                if (!cantidadClientes || parseInt(cantidadClientes, 10) < 1) {
                    alert('La cantidad de clientes debe ser mayor a 0');
                    return;
                }
                const maxPermitido = parseInt(document.getElementById('cantidad_clientes').max || '0', 10);
                if (maxPermitido > 0 && parseInt(cantidadClientes, 10) > maxPermitido) {
                    alert(`La cantidad a asignar no puede ser mayor al total con filtro (${maxPermitido}).`);
                    return;
                }
            } else {
                const f = document.getElementById('csv_cedulas');
                if (!f || !f.files || !f.files[0]) {
                    alert('Debes seleccionar un archivo CSV');
                    return;
                }
            }

            fetch(`index.php?action=${action}`, {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(text => {
                // Recargar la página para mostrar la nueva tarea
                window.location.reload();
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al crear la tarea');
            });
        });

        // Inicializar estado del modal al cargar
        try { onMetodoAsignacionChange(); } catch (e) {}

        // Recalcular conteo cuando cambian filtros
        ['saldo_min','saldo_max','mora_min','mora_max','franja','forma_contacto','tipo_contacto','resultado_contacto','razon_especifica'].forEach(id => {
            const el = document.getElementById(id);
            if (!el) return;
            el.addEventListener('change', actualizarConteoFiltrado);
            el.addEventListener('input', actualizarConteoFiltrado);
        });
        const chk = document.getElementById('solo_no_gestionados');
        if (chk) chk.addEventListener('change', actualizarConteoFiltrado);

        function verDetallesTarea(tareaId) {
            const container = document.getElementById('detalles-tarea-content');
            if (container) {
                container.innerHTML = '<div class="alert alert-info">Cargando detalles...</div>';
            }
            document.getElementById('modalDetallesTarea').style.display = 'flex';
            document.body.style.overflow = 'hidden';

            fetch(`index.php?action=get_detalles_tarea&tarea_id=${tareaId}`, { credentials: 'same-origin' })
                .then(r => r.json())
                .then(data => {
                    if (!data.success) {
                        if (container) container.innerHTML = `<div class="alert alert-error">${(data.error || 'Error cargando detalles')}</div>`;
                        return;
                    }
                    const t = data.tarea || {};
                    const clientes = Array.isArray(data.clientes) ? data.clientes : [];
                    const rows = clientes.map(c => {
                        const g = (c.gestionado === 'si') ? '✅ Si' : '❌ No';
                        return `<tr>
                            <td>${String(c.nombre || '')}</td>
                            <td>${String(c.cedula || '')}</td>
                            <td>${String(c.telefono || '')}</td>
                            <td>${g}</td>
                        </tr>`;
                    }).join('');
                    if (container) {
                        container.innerHTML = `
                            <div class="alert alert-info" style="margin-bottom:15px;">
                                <strong>${String(t.asesor_nombre || '')}</strong> — Base: <strong>${String(t.nombre_cargue || '')}</strong><br>
                                Estado: <strong>${String(t.estado || '')}</strong> | Clientes: <strong>${Number(t.total_clientes || 0)}</strong> | Gestionados: <strong>${Number(t.clientes_gestionados || 0)}</strong>
                            </div>
                            <div style="overflow:auto; max-height:50vh; border:1px solid #e9ecef; border-radius:8px;">
                                <table class="table" style="width:100%; border-collapse:collapse;">
                                    <thead>
                                        <tr>
                                            <th>Cliente</th>
                                            <th>Cédula</th>
                                            <th>Teléfono</th>
                                            <th>Gestionado</th>
                                        </tr>
                                    </thead>
                                    <tbody>${rows || '<tr><td colspan="4">Sin clientes</td></tr>'}</tbody>
                                </table>
                            </div>
                        `;
                    }
                })
                .catch(err => {
                    if (container) container.innerHTML = `<div class="alert alert-error">Error: ${String(err && err.message || err)}</div>`;
                });
        }

        function cerrarModalDetalles() {
            document.getElementById('modalDetallesTarea').style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        function marcarCompletada(tareaId) {
            if (confirm('¿Estás seguro de que quieres marcar esta tarea como completada?')) {
                fetch('index.php?action=actualizar_estado_tarea', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `tarea_id=${tareaId}&estado=completada`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.reload();
                    } else {
                        alert('Error: ' + data.error);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al actualizar la tarea');
                });
            }
        }

        // Cerrar modales al hacer clic fuera de ellos
        document.getElementById('modalCrearTarea').addEventListener('click', function(e) {
            if (e.target === this) {
                cerrarModalCrearTarea();
            }
        });

        document.getElementById('modalDetallesTarea').addEventListener('click', function(e) {
            if (e.target === this) {
                cerrarModalDetalles();
            }
        });
    </script>

    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border: 1px solid #e9ecef;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .stat-icon {
            font-size: 2rem;
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }

        .stat-content {
            flex: 1;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #495057;
            margin: 0;
        }

        .stat-label {
            color: #6c757d;
            font-size: 0.9rem;
            margin: 0;
        }

        .tareas-section {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border: 1px solid #e9ecef;
        }

        .tareas-section h3 {
            margin-bottom: 20px;
            color: #495057;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .tarea-item {
            border: 1px solid #e9ecef;
            border-radius: 8px;
            margin-bottom: 15px;
            background: #f8f9fa;
            transition: all 0.2s;
        }

        .tarea-item:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transform: translateY(-1px);
        }

        .tarea-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            background: white;
            border-bottom: 1px solid #e9ecef;
            border-radius: 8px 8px 0 0;
        }

        .tarea-info h4 {
            margin: 0 0 8px 0;
            color: #495057;
            font-size: 1.1rem;
        }

        .tarea-meta {
            display: flex;
            gap: 8px;
        }

        .tarea-actions {
            display: flex;
            gap: 8px;
        }

        .tarea-body {
            padding: 15px 20px;
        }

        .tarea-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px;
        }

        .detail-item {
            font-size: 0.9rem;
            color: #6c757d;
        }

        .detail-item strong {
            color: #495057;
        }



        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .modal-content {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 25px;
            border-bottom: 1px solid #e9ecef;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 12px 12px 0 0;
        }

        .modal-header h3 {
            margin: 0;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .close {
            background: none;
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: background-color 0.2s;
        }

        .close:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }

        .modal-body {
            padding: 25px;
        }

        .modal-actions {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
        }

        .form-row {
            display: flex;
            gap: 15px;
        }

        .form-row .form-group {
            flex: 1;
        }

        .required {
            color: #dc3545;
        }

        @media (max-width: 768px) {
            .tarea-header {
                flex-direction: column;
                gap: 15px;
                align-items: stretch;
            }

            .tarea-actions {
                justify-content: center;
            }

            .tarea-details {
                grid-template-columns: 1fr;
            }

            .form-row {
                flex-direction: column;
            }

            .modal-content {
                width: 95%;
                margin: 10px;
            }
        }
    </style>
</body>
</html>
