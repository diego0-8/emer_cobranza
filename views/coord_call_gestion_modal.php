<?php
// Vista parcial (HTML) para modal de gestión desde coord_call.
// Variables esperadas: $gestion (array), $call (array), $cliente (array|null)

function cc_pretty_label($v): string {
    $s = trim((string)$v);
    if ($s === '') return '';
    $s = str_replace('_', ' ', $s);
    $s = preg_replace('/\s+/', ' ', $s);
    return trim((string)$s);
}
?>

<div class="cc-modal-head">
    <div>
        <div class="cc-modal-title">Gestión de la llamada</div>
        <div class="cc-modal-subtitle">
            <?php
                $inicio = (string)($call['inicio'] ?? '');
                $telefono = (string)($call['telefono_contacto'] ?? '');
                $dur = (int)($call['duracion_segundos'] ?? 0);
                $mm = floor($dur / 60);
                $ss = $dur % 60;
                $durTxt = sprintf('%02d:%02d', $mm, $ss);
            ?>
            <?php echo htmlspecialchars($inicio); ?> · <?php echo htmlspecialchars($telefono); ?> · <?php echo htmlspecialchars($durTxt); ?>
        </div>
    </div>
</div>

<div class="cc-modal-grid">
    <div class="cc-modal-card">
        <div class="cc-modal-card-title">Cliente</div>
        <div class="cc-modal-kv">
            <div class="cc-k">Nombre</div>
            <div class="cc-v"><?php echo htmlspecialchars((string)($cliente['nombre'] ?? $gestion['cliente_nombre'] ?? '')); ?></div>
            <div class="cc-k">Cédula</div>
            <div class="cc-v"><?php echo htmlspecialchars((string)($cliente['cedula'] ?? $gestion['cliente_cedula'] ?? '')); ?></div>
        </div>
    </div>

    <div class="cc-modal-card">
        <div class="cc-modal-card-title">Asesor</div>
        <div class="cc-modal-kv">
            <div class="cc-k">Nombre</div>
            <div class="cc-v"><?php echo htmlspecialchars((string)($gestion['asesor_nombre'] ?? '')); ?></div>
            <div class="cc-k">Cédula</div>
            <div class="cc-v"><?php echo htmlspecialchars((string)($gestion['asesor_cedula'] ?? '')); ?></div>
        </div>
    </div>

    <div class="cc-modal-card cc-modal-card-full">
        <div class="cc-modal-card-title">Detalle de gestión</div>
        <div class="cc-modal-kv">
            <div class="cc-k">Fecha</div>
            <div class="cc-v"><?php echo htmlspecialchars((string)($gestion['fecha_creacion'] ?? '')); ?></div>

            <div class="cc-k">Forma de contacto</div>
            <div class="cc-v"><?php echo htmlspecialchars(cc_pretty_label($gestion['forma_contacto'] ?? '')); ?></div>

            <div class="cc-k">Tipo de contacto</div>
            <div class="cc-v"><?php echo htmlspecialchars(cc_pretty_label($gestion['tipo_contacto'] ?? '')); ?></div>

            <div class="cc-k">Resultado</div>
            <div class="cc-v"><?php echo htmlspecialchars(cc_pretty_label($gestion['resultado_contacto'] ?? '')); ?></div>

            <div class="cc-k">Razón específica</div>
            <div class="cc-v"><?php echo htmlspecialchars(cc_pretty_label($gestion['razon_especifica'] ?? '')); ?></div>

            <div class="cc-k">Observaciones</div>
            <div class="cc-v cc-pre"><?php echo nl2br(htmlspecialchars((string)($gestion['observaciones'] ?? ''))); ?></div>
        </div>
    </div>
</div>

