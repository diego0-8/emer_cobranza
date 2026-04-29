<?php
// Archivo: views/admin_dashboard.php
// Vista del dashboard principal del administrador con diseño moderno
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <?php require_once 'shared_styles.php'; ?>
    <style>
        .dashboard-alert {
            padding: 16px 18px;
            border-radius: 10px;
            margin-bottom: 20px;
            line-height: 1.5;
        }

        .dashboard-alert-success {
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            color: #065f46;
        }

        .dashboard-alert-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
        }

        .dashboard-alert-info {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            color: #1d4ed8;
        }

        .dashboard-alert-warning {
            background: #fffbeb;
            border: 1px solid #fde68a;
            color: #92400e;
        }

        .custom-modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.65);
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
            z-index: 2000;
        }

        .custom-modal-backdrop.active {
            display: flex;
        }

        .custom-modal {
            background: white;
            width: 100%;
            max-width: 760px;
            border-radius: 14px;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.25);
            overflow: hidden;
        }

        .custom-modal-header {
            padding: 20px 24px;
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }

        .custom-modal-header h3 {
            margin: 0;
            font-size: 1.2rem;
        }

        .custom-modal-close {
            background: transparent;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
        }

        .custom-modal-body {
            padding: 24px;
        }

        .required-columns {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 16px 18px;
            margin-bottom: 20px;
        }

        .required-columns h4 {
            margin-bottom: 12px;
            color: #1f2937;
            font-size: 1rem;
        }

        .required-columns ul {
            margin: 0;
            padding-left: 20px;
            columns: 2;
        }

        .required-columns li {
            margin-bottom: 6px;
        }

        .help-text {
            color: #6b7280;
            font-size: 0.9rem;
            margin-top: 8px;
        }

        @media (max-width: 768px) {
            .required-columns ul {
                columns: 1;
            }
        }
    </style>
</head>
<body>
    <?php 
    require_once 'shared_navbar.php';
    echo getNavbar('Inicio', $_SESSION['user_role'] ?? '');
    ?>
    
    <div class="main-container">
        <?php if (!empty($success_message)): ?>
            <div class="dashboard-alert dashboard-alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <?php if (!empty($error_message)): ?>
            <div class="dashboard-alert dashboard-alert-error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <?php if (!empty($info_message)): ?>
            <div class="dashboard-alert dashboard-alert-info"><?php echo $info_message; ?></div>
        <?php endif; ?>

        <?php if (!empty($warning_message)): ?>
            <div class="dashboard-alert dashboard-alert-warning"><?php echo $warning_message; ?></div>
        <?php endif; ?>

        <!-- Tarjeta de bienvenida -->
        <div class="card">
            <div class="card-header">
                🏠 Panel de Control del Administrador
            </div>
            <div class="card-body">
                <h2>Bienvenido al Sistema de Gestión de Ventas</h2>
                <p>Desde aquí puedes gestionar usuarios, coordinar actividades y supervisar el rendimiento del equipo.</p>
            </div>
        </div>
        
        <!-- Grid de estadísticas -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number">👥</div>
                <div class="stat-label">Gestión de Usuarios</div>
                <p class="mt-20">Crear, editar y gestionar usuarios del sistema</p>
                <a href="index.php?action=list_usuarios" class="btn btn-primary mt-20">Gestionar Usuarios</a>
            </div>
            
            <div class="stat-card">
                <div class="stat-number">📊</div>
                <div class="stat-label">Reportes y Estadísticas</div>
                <p class="mt-20">Ver métricas y reportes del equipo</p>
                <a href="index.php?action=ver_actividades" class="btn btn-primary mt-20">Ver Reportes</a>
            </div>
            
            <div class="stat-card">
                <div class="stat-number">⚙️</div>
                <div class="stat-label">Configuración del Sistema</div>
                <p class="mt-20">Asignar personal y configurar roles</p>
                <a href="index.php?action=asignar_personal" class="btn btn-primary mt-20">Configurar</a>
            </div>
            
            <div class="stat-card">
                <div class="stat-number">➕</div>
                <div class="stat-label">Nuevo Usuario</div>
                <p class="mt-20">Crear un nuevo usuario en el sistema</p>
                <a href="index.php?action=crear_usuario" class="btn btn-success mt-20">Crear Usuario</a>
            </div>
        </div>
        
        <!-- Tarjeta de acciones rápidas -->
        <div class="card">
            <div class="card-header">
                🚀 Acciones Rápidas
            </div>
            <div class="card-body">
                <div class="form-row">
                    <div class="form-group">
                        <a href="index.php?action=list_usuarios" class="btn btn-primary" style="width: 100%; padding: 15px;">
                            👥 Ver Todos los Usuarios
                        </a>
                    </div>
                    <div class="form-group">
                        <a href="index.php?action=crear_usuario" class="btn btn-success" style="width: 100%; padding: 15px;">
                            ➕ Crear Nuevo Usuario
                        </a>
                    </div>
                    <div class="form-group">
                        <a href="index.php?action=ver_actividades" class="btn btn-secondary" style="width: 100%; padding: 15px;">
                            📊 Ver Actividades
                        </a>
                    </div>
                    <div class="form-group">
                        <button type="button" class="btn btn-warning" style="width: 100%; padding: 15px;" id="openCargaBashModal">
                            📥 Subir Bash
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Nueva tarjeta de gestión de personal -->
        <div class="card">
            <div class="card-header">
                👥 Gestión de Personal
            </div>
            <div class="card-body">
                <p>Gestiona la asignación de asesores a coordinadores y supervisa el rendimiento del equipo.</p>
                <div class="form-row">
                    <div class="form-group">
                        <a href="index.php?action=asignar_personal" class="btn btn-primary" style="width: 100%; padding: 15px;">
                            🔗 Asignar Personal
                        </a>
                    </div>
                    <div class="form-group">
                        <a href="index.php?action=ver_actividades" class="btn btn-info" style="width: 100%; padding: 15px;">
                            📈 Ver Métricas del Equipo
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Información del sistema -->
        <div class="card">
            <div class="card-header">
                ℹ️ Información del Sistema
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <strong>Estado:</strong> Sistema funcionando correctamente
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <strong>Usuario actual:</strong> <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'No identificado'); ?>
                    </div>
                    <div class="form-group">
                        <strong>Rol:</strong> <?php echo htmlspecialchars($_SESSION['user_role'] ?? 'No definido'); ?>
                    </div>
                    <div class="form-group">
                        <strong>ID de sesión:</strong> <?php echo htmlspecialchars($_SESSION['user_id'] ?? 'No disponible'); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="custom-modal-backdrop" id="cargaBashModal" aria-hidden="true">
        <div class="custom-modal" role="dialog" aria-modal="true" aria-labelledby="cargaBashTitulo">
            <div class="custom-modal-header">
                <h3 id="cargaBashTitulo">Subir Bash de Gestiones</h3>
                <button type="button" class="custom-modal-close" id="closeCargaBashModal" aria-label="Cerrar">&times;</button>
            </div>
            <div class="custom-modal-body">
                <div class="required-columns">
                    <h4>Columnas requeridas del CSV</h4>
                    <ul>
                        <li>fecha de gestion</li>
                        <li>asesor</li>
                        <li>cedula cliente</li>
                        <li>telefono de contacto</li>
                        <li>franja de cliente</li>
                        <li>canal de contacto</li>
                        <li>tipo de contacto</li>
                        <li>resultado del contacto</li>
                        <li>razón especifica</li>
                        <li>fecha de pago</li>
                        <li>valor de la cuota</li>
                        <li>factura a gestionar</li>
                        <li>observaciones</li>
                        <li>canales autorizados</li>
                    </ul>
                </div>

                <form action="index.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="process_upload_bash">

                    <div class="form-group">
                        <label class="form-label" for="carga_id">Base activa</label>
                        <select class="form-select" id="carga_id" name="carga_id" required>
                            <option value="">Selecciona una base activa</option>
                            <?php foreach (($cargasActivas ?? []) as $carga): ?>
                                <option value="<?php echo (int) $carga['id']; ?>">
                                    <?php echo htmlspecialchars($carga['nombre_cargue']); ?>
                                    <?php if (!empty($carga['coordinador_nombre'])): ?>
                                        - <?php echo htmlspecialchars($carga['coordinador_nombre']); ?>
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="help-text">Solo se listan bases activas y habilitadas.</div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="archivo_bash">Archivo CSV</label>
                        <input class="form-input" type="file" id="archivo_bash" name="archivo_bash" accept=".csv" required>
                        <div class="help-text">
                            La base destino es la que eliges arriba. Solo se importan filas cuya <strong>cédula</strong> exista en esa base activa; el resto se registrará como error en el resumen.
                            Si la columna de factura viene vacía o como “ninguna”, se usa la <strong>primera obligación</strong> del cliente en esa base.
                        </div>
                        <div class="help-text" style="margin-top: 12px;">
                            <a href="index.php?action=descargar_plantilla_bash" class="btn btn-primary" style="display: inline-block;">
                                Descargar plantilla CSV
                            </a>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <button type="submit" class="btn btn-warning" style="width: 100%;">Cargar Gestiones</button>
                        </div>
                        <div class="form-group">
                            <button type="button" class="btn btn-secondary" style="width: 100%;" id="cancelCargaBashModal">Cancelar</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        (function() {
            const modal = document.getElementById('cargaBashModal');
            const openBtn = document.getElementById('openCargaBashModal');
            const closeBtn = document.getElementById('closeCargaBashModal');
            const cancelBtn = document.getElementById('cancelCargaBashModal');

            function openModal() {
                modal.classList.add('active');
                modal.setAttribute('aria-hidden', 'false');
            }

            function closeModal() {
                modal.classList.remove('active');
                modal.setAttribute('aria-hidden', 'true');
            }

            if (openBtn) {
                openBtn.addEventListener('click', openModal);
            }

            if (closeBtn) {
                closeBtn.addEventListener('click', closeModal);
            }

            if (cancelBtn) {
                cancelBtn.addEventListener('click', closeModal);
            }

            if (modal) {
                modal.addEventListener('click', function(event) {
                    if (event.target === modal) {
                        closeModal();
                    }
                });
            }

            document.addEventListener('keydown', function(event) {
                if (event.key === 'Escape' && modal.classList.contains('active')) {
                    closeModal();
                }
            });
        })();
    </script>
</body>
</html>
