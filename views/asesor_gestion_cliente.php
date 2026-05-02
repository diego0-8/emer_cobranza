<?php
$page_title = $page_title ?? 'Gestion de cliente';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once __DIR__ . '/shared_navbar.php'; renderPageHead($page_title ?? ''); ?>
    <?php require_once 'shared_styles.php'; ?>
</head>
<body>
    <?php
    require_once 'shared_navbar.php';
    echo getNavbar('Mis Clientes', $_SESSION['user_role'] ?? 'asesor');
    ?>

    <div class="main-container">
        <section class="card">
            <div class="card-header">Cliente</div>
            <div class="card-body">
                <p><strong>Nombre:</strong> <?php echo htmlspecialchars($cliente['nombre'] ?? ''); ?></p>
                <p><strong>Cedula:</strong> <?php echo htmlspecialchars($cliente['cedula'] ?? ''); ?></p>
                <p><strong>Telefono:</strong> <?php echo htmlspecialchars($cliente['tel1'] ?? ($cliente['telefono'] ?? '')); ?></p>
                <p><strong>Base:</strong> <?php echo htmlspecialchars($cliente['nombre_base'] ?? ''); ?></p>
                <p><strong>Tarea:</strong> <?php echo htmlspecialchars($contextoTarea['nombre_tarea'] ?? ''); ?></p>
            </div>
        </section>

        <section class="card">
            <div class="card-header">Facturas</div>
            <div class="card-body">
                <?php if (empty($facturas)): ?>
                    <div class="empty-state">No hay facturas disponibles.</div>
                <?php else: ?>
                    <form method="POST" action="index.php">
                        <input type="hidden" name="action" value="guardar_tipificacion">
                        <input type="hidden" name="cliente_id" value="<?php echo (int) ($cliente['id_cliente'] ?? $cliente['id'] ?? 0); ?>">
                        <input type="hidden" name="tarea_id" value="<?php echo (int) ($contextoTarea['id_tarea'] ?? 0); ?>">

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="factura_id">Factura</label>
                                <select class="form-select" id="factura_id" name="factura_id" required>
                                    <option value="">Selecciona</option>
                                    <?php foreach ($facturas as $f): ?>
                                        <option value="<?php echo (int) $f['id_factura']; ?>">
                                            <?php echo htmlspecialchars($f['numero_factura'] ?? ''); ?> (Saldo <?php echo htmlspecialchars($f['saldo'] ?? ''); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="canal_contacto">Canal</label>
                                <select class="form-select" id="canal_contacto" name="canal_contacto">
                                    <option value="telefono">Telefono</option>
                                    <option value="whatsapp">WhatsApp</option>
                                    <option value="email">Email</option>
                                    <option value="visita">Visita</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="nivel1_tipo">Nivel 1</label>
                                <input class="form-input" id="nivel1_tipo" name="nivel1_tipo">
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="nivel2_tipo">Nivel 2</label>
                                <input class="form-input" id="nivel2_tipo" name="nivel2_tipo">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="nivel3_tipo">Nivel 3</label>
                                <input class="form-input" id="nivel3_tipo" name="nivel3_tipo">
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="nivel4_tipo">Nivel 4</label>
                                <input class="form-input" id="nivel4_tipo" name="nivel4_tipo">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="telefono_contacto">Telefono contacto</label>
                                <input class="form-input" id="telefono_contacto" name="telefono_contacto" value="<?php echo htmlspecialchars($cliente['tel1'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="duracion_segundos">Duracion (seg)</label>
                                <input class="form-input" id="duracion_segundos" name="duracion_segundos" type="number" min="0" value="0">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="llamada_telefonica">Llamada telefonica</label>
                                <select class="form-select" id="llamada_telefonica" name="llamada_telefonica">
                                    <option value="No">No</option>
                                    <option value="Si">Si</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="fecha_pago">Fecha pago</label>
                                <input class="form-input" id="fecha_pago" name="fecha_pago" type="date">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="valor_pago">Valor pago</label>
                                <input class="form-input" id="valor_pago" name="valor_pago" type="number" step="0.01" min="0">
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="observaciones">Observaciones</label>
                                <input class="form-input" id="observaciones" name="observaciones">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="email_envio">Email</label>
                                <select class="form-select" id="email_envio" name="email_envio">
                                    <option value="No">No</option>
                                    <option value="Si">Si</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="sms">SMS</label>
                                <select class="form-select" id="sms" name="sms">
                                    <option value="No">No</option>
                                    <option value="Si">Si</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="correo_fisico">Correo fisico</label>
                                <select class="form-select" id="correo_fisico" name="correo_fisico">
                                    <option value="No">No</option>
                                    <option value="Si">Si</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="whatsapp">WhatsApp</label>
                                <select class="form-select" id="whatsapp" name="whatsapp">
                                    <option value="No">No</option>
                                    <option value="Si">Si</option>
                                </select>
                            </div>
                        </div>

                        <div class="card-actions">
                            <button class="btn btn-primary" type="submit">Guardar gestion</button>
                            <a class="btn btn-secondary" href="index.php?action=mis_clientes">Volver</a>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </section>

        <section class="card">
            <div class="card-header">Historial</div>
            <div class="card-body">
                <?php if (empty($historial)): ?>
                    <div class="empty-state">Aun no hay gestiones registradas.</div>
                <?php else: ?>
                    <ul class="list-clean">
                        <?php foreach ($historial as $h): ?>
                            <li>
                                <div><strong><?php echo htmlspecialchars($h['nivel1_tipo'] ?? ''); ?></strong> / <?php echo htmlspecialchars($h['nivel2_tipo'] ?? ''); ?></div>
                                <div>Factura: <?php echo htmlspecialchars($h['numero_factura'] ?? ''); ?></div>
                                <div><?php echo htmlspecialchars($h['fecha_creacion'] ?? ''); ?></div>
                                <div><?php echo htmlspecialchars($h['observaciones'] ?? ''); ?></div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <?php require_once 'shared_footer.php'; ?>
</body>
</html>

