<?php
/**
 * Snippets a insertar en views/gestionar_cliente.php (bajo softphone).
 * Fragmentos de referencia — ya están aplicados en emer_cobranza.
 */
?>

<!-- ========== HEAD: CSS + config JS ========== -->
<link rel="stylesheet" href="assets/css/whatsapp-panel.css">
<script>
window.__waConfig = {
    clienteId: <?php echo (int)($cliente['id'] ?? $cliente['id_cliente'] ?? 0); ?>,
    waId: <?php echo (int)($_GET['wa'] ?? 0); ?>,
    pollMs: 5000
};
</script>

<!-- ========== BODY: rail de burbujas (fijo izquierda) ========== -->
<div id="waBubbleRail" aria-label="Chats WhatsApp"></div>

<!-- ========== COLUMNA DERECHA: panel bajo softphone ========== -->
<div id="waPanel" class="cliente-info-card" style="padding: 0; overflow: hidden;">
    <div class="wa-panel-header">
        <h5><i class="fab fa-whatsapp"></i> WhatsApp</h5>
        <span class="wa-panel-mode" id="waPanelMode">…</span>
    </div>
    <div class="wa-panel-body">
        <div class="wa-write-row">
            <label for="waPhoneSelect">Escribir a</label>
            <select id="waPhoneSelect" aria-label="Número del perfil"></select>
            <span class="wa-activo-badge desconocido" id="waActivoBadge" title="Estado WhatsApp">?</span>
        </div>
        <div class="wa-thread" id="waThread">
            <div class="wa-empty">Cargando conversación…</div>
        </div>
        <div class="wa-compose">
            <textarea id="waComposeInput" rows="2" placeholder="Escribe un mensaje…" maxlength="4000"></textarea>
            <button type="button" id="waComposeSend" title="Enviar">
                <i class="fas fa-paper-plane"></i>
            </button>
        </div>
        <div class="wa-error" id="waError" style="display:none;"></div>
    </div>
</div>

<!-- ========== FOOTER: script ========== -->
<script src="assets/js/whatsapp-panel.js?v=1"></script>
