<?php
/**
 * WhatsApp masivo — coordinador
 * @var array $bases
 * @var array $campanas
 */
$page_title = $page_title ?? 'WhatsApp masivo';
require_once __DIR__ . '/shared_navbar.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php renderPageHead($page_title); ?>
    <?php require_once __DIR__ . '/shared_styles.php'; ?>
    <link rel="stylesheet" href="css/common-styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --wa-green: #25d366;
            --wa-green-dark: #128c7e;
            --wa-green-deep: #075e54;
            --wa-mint: #dcf8c6;
            --wa-surface: #ffffff;
            --wa-bg: #eef2f6;
            --wa-border: #e2e8f0;
            --wa-muted: #64748b;
            --wa-text: #0f172a;
        }

        body {
            background:
                radial-gradient(1200px 420px at 10% -10%, rgba(37, 211, 102, 0.14), transparent 55%),
                radial-gradient(900px 380px at 100% 0%, rgba(18, 140, 126, 0.10), transparent 50%),
                var(--wa-bg);
        }

        .wa-coord-page {
            max-width: 1400px;
            margin: 0 auto;
            padding: 28px 20px 48px;
        }

        .wa-hist-card .wa-card-body { padding: 0; }
        .wa-hist-list { max-height: 720px; overflow-y: auto; }
        .wa-hist-item {
            padding: 12px 14px;
            border-bottom: 1px solid var(--wa-border);
            font-size: 0.82rem;
            color: var(--wa-text);
        }
        .wa-hist-item:last-child { border-bottom: 0; }
        .wa-hist-item .wa-hist-tipo {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.68rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.02em;
            color: var(--wa-green-dark);
            margin-bottom: 4px;
        }
        .wa-hist-item .wa-hist-tipo.is-empareje { color: #0369a1; }
        .wa-hist-item .wa-hist-title {
            font-weight: 600;
            color: var(--wa-text);
            word-break: break-word;
        }
        .wa-hist-item .wa-hist-meta {
            color: var(--wa-muted);
            font-size: 0.72rem;
            margin-top: 6px;
        }
        .wa-hist-pager {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            padding: 10px 12px;
            border-top: 1px solid var(--wa-border);
            background: #f8fafc;
        }
        .accordion-button:not(.collapsed) {
            background: #f0fdf4;
            color: var(--wa-green-deep);
            box-shadow: none;
        }
        .accordion-button:focus {
            box-shadow: 0 0 0 0.2rem rgba(37, 211, 102, 0.25);
        }
        .accordion-item {
            border: 1px solid var(--wa-border);
            border-radius: 14px !important;
            overflow: hidden;
            margin-bottom: 14px;
            background: var(--wa-surface);
            box-shadow: 0 4px 18px rgba(15, 23, 42, 0.05);
        }

        .wa-hero {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 22px;
            padding: 22px 24px;
            border-radius: 16px;
            background: linear-gradient(135deg, var(--wa-green-deep) 0%, var(--wa-green-dark) 48%, var(--wa-green) 100%);
            color: #fff;
            box-shadow: 0 12px 28px rgba(7, 94, 84, 0.28);
            position: relative;
            overflow: hidden;
        }

        .wa-hero::after {
            content: '';
            position: absolute;
            right: -40px;
            top: -40px;
            width: 180px;
            height: 180px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.08);
        }

        .wa-hero-main {
            position: relative;
            z-index: 1;
            max-width: 720px;
        }

        .wa-hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 5px 12px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.16);
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.02em;
            margin-bottom: 10px;
        }

        .wa-hero h1 {
            margin: 0 0 8px;
            font-size: 1.75rem;
            font-weight: 700;
            line-height: 1.2;
        }

        .wa-hero p {
            margin: 0;
            opacity: 0.92;
            font-size: 0.95rem;
            line-height: 1.5;
        }

        .wa-hero-icon {
            position: relative;
            z-index: 1;
            width: 72px;
            height: 72px;
            border-radius: 20px;
            background: rgba(255, 255, 255, 0.16);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            flex-shrink: 0;
        }

        .wa-grid {
            display: grid;
            grid-template-columns: 1.35fr 1fr;
            gap: 18px;
            align-items: start;
        }

        @media (max-width: 960px) {
            .wa-grid { grid-template-columns: 1fr; }
            .wa-hero { flex-direction: column; }
        }

        .wa-card {
            background: var(--wa-surface);
            border: 1px solid var(--wa-border);
            border-radius: 14px;
            box-shadow: 0 4px 18px rgba(15, 23, 42, 0.05);
            overflow: hidden;
        }

        .wa-card-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 14px 18px;
            border-bottom: 1px solid var(--wa-border);
            background: #f8fafc;
        }

        .wa-card-head h2 {
            margin: 0;
            font-size: 1rem;
            color: var(--wa-text);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .wa-card-head h2 i { color: var(--wa-green-dark); }

        .wa-card-body { padding: 18px; }

        .wa-field { margin-bottom: 14px; }

        .wa-field label {
            display: block;
            font-size: 0.78rem;
            font-weight: 700;
            color: #334155;
            margin-bottom: 6px;
            letter-spacing: 0.01em;
        }

        .wa-field select,
        .wa-field textarea,
        .wa-field input {
            width: 100%;
            box-sizing: border-box;
            border: 1.5px solid #dbe3ee;
            border-radius: 10px;
            padding: 10px 12px;
            font-size: 0.9rem;
            color: var(--wa-text);
            background: #fff;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }

        .wa-field select:focus,
        .wa-field textarea:focus {
            outline: none;
            border-color: var(--wa-green);
            box-shadow: 0 0 0 3px rgba(37, 211, 102, 0.18);
        }

        .wa-field textarea {
            min-height: 150px;
            resize: vertical;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
            line-height: 1.45;
            background:
                linear-gradient(#fff, #fff) padding-box,
                linear-gradient(180deg, #f8fafc, #fff) border-box;
        }

        .wa-row-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }

        @media (max-width: 640px) {
            .wa-row-2 { grid-template-columns: 1fr; }
        }

        .wa-hint {
            font-size: 0.78rem;
            color: var(--wa-muted);
            margin-top: 6px;
            line-height: 1.4;
        }

        .wa-var-map {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .wa-var-chip {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 10px;
            border-radius: 10px;
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
        }

        .wa-var-chip span {
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--wa-green-deep);
            font-family: ui-monospace, monospace;
        }

        .wa-var-chip select {
            border: 1px solid #86efac;
            border-radius: 8px;
            padding: 6px 8px;
            font-size: 0.82rem;
            background: #fff;
            min-width: 110px;
        }

        .wa-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 6px;
        }

        .wa-btn {
            border: none;
            border-radius: 10px;
            padding: 11px 16px;
            font-weight: 700;
            font-size: 0.86rem;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: transform 0.12s ease, box-shadow 0.12s ease, opacity 0.12s ease;
        }

        .wa-btn:hover:not(:disabled) { transform: translateY(-1px); }
        .wa-btn:disabled { opacity: 0.55; cursor: not-allowed; transform: none; }

        .wa-btn-primary {
            background: linear-gradient(135deg, var(--wa-green), var(--wa-green-dark));
            color: #fff;
            box-shadow: 0 6px 16px rgba(18, 140, 126, 0.28);
        }

        .wa-btn-secondary {
            background: #eef2f7;
            color: #1e293b;
            border: 1px solid #d8e0ea;
        }

        .wa-btn-ghost {
            background: #fff;
            color: var(--wa-green-dark);
            border: 1px solid #a7f3d0;
            padding: 7px 10px;
            font-size: 0.75rem;
        }

        .wa-msg {
            margin-top: 12px;
            padding: 10px 12px;
            border-radius: 10px;
            font-size: 0.84rem;
            display: none;
        }

        .wa-msg.is-visible { display: block; }
        .wa-msg.hint { background: #f8fafc; color: #475569; border: 1px solid var(--wa-border); }
        .wa-msg.ok { background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; }
        .wa-msg.err { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }

        .wa-stats {
            display: none;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 10px;
            margin-top: 14px;
        }

        .wa-stats.is-visible { display: grid; }

        .wa-stat {
            border-radius: 12px;
            padding: 12px 14px;
            border: 1px solid var(--wa-border);
            background: #f8fafc;
        }

        .wa-stat strong {
            display: block;
            font-size: 1.35rem;
            color: var(--wa-text);
            line-height: 1.1;
            margin-bottom: 2px;
        }

        .wa-stat span {
            font-size: 0.75rem;
            color: var(--wa-muted);
            font-weight: 600;
        }

        .wa-stat.ok { background: #f0fdf4; border-color: #bbf7d0; }
        .wa-stat.ok strong { color: #15803d; }
        .wa-stat.warn { background: #fffbeb; border-color: #fde68a; }
        .wa-stat.warn strong { color: #b45309; }
        .wa-stat.bad { background: #fef2f2; border-color: #fecaca; }
        .wa-stat.bad strong { color: #b91c1c; }

        .wa-side-note {
            margin-top: 14px;
            padding: 12px 14px;
            border-radius: 12px;
            background: linear-gradient(180deg, #f0fdf4, #fff);
            border: 1px dashed #86efac;
            color: #166534;
            font-size: 0.82rem;
            line-height: 1.45;
        }

        .wa-table-wrap {
            overflow: auto;
            border-radius: 0 0 14px 14px;
        }

        .wa-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.84rem;
            min-width: 680px;
        }

        .wa-table th {
            text-align: left;
            padding: 12px 14px;
            color: #64748b;
            font-weight: 700;
            background: #f8fafc;
            border-bottom: 1px solid var(--wa-border);
            white-space: nowrap;
        }

        .wa-table td {
            padding: 12px 14px;
            border-bottom: 1px solid #f1f5f9;
            color: #1e293b;
            vertical-align: middle;
        }

        .wa-table tr:hover td { background: #f8fffb; }

        .wa-estado {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: capitalize;
        }

        .wa-estado.procesando { background: #e0f2fe; color: #0369a1; }
        .wa-estado.completada { background: #dcfce7; color: #166534; }
        .wa-estado.cancelada { background: #fee2e2; color: #991b1b; }
        .wa-estado.borrador { background: #f1f5f9; color: #475569; }

        .wa-empty {
            padding: 28px 18px;
            text-align: center;
            color: var(--wa-muted);
            font-size: 0.9rem;
        }

        .wa-empty i {
            display: block;
            font-size: 1.8rem;
            margin-bottom: 8px;
            color: #94a3b8;
        }

        .wa-inbox-card { margin-top: 18px; }
        .wa-inbox-grid {
            display: grid;
            grid-template-columns: minmax(280px, 0.8fr) minmax(420px, 1.6fr);
            min-height: 500px;
        }
        .wa-unknown-list {
            border-right: 1px solid var(--wa-border);
            max-height: 680px;
            overflow-y: auto;
        }
        .wa-unknown-item {
            width: 100%;
            border: 0;
            border-bottom: 1px solid #eef2f7;
            background: #fff;
            padding: 14px 16px;
            text-align: left;
            cursor: pointer;
        }
        .wa-unknown-item:hover,
        .wa-unknown-item.is-active { background: #f0fdf4; }
        .wa-unknown-item strong { display: block; color: var(--wa-text); }
        .wa-unknown-item span,
        .wa-unknown-item small {
            display: block;
            color: var(--wa-muted);
            margin-top: 4px;
        }
        .wa-unknown-detail { padding: 18px; }
        .wa-unknown-placeholder {
            min-height: 430px;
            display: grid;
            place-content: center;
            text-align: center;
            color: var(--wa-muted);
        }
        .wa-chat-thread {
            height: 250px;
            overflow-y: auto;
            padding: 12px;
            border: 1px solid var(--wa-border);
            border-radius: 12px;
            background: #f8fafc;
            margin: 12px 0;
        }
        .wa-chat-message {
            max-width: 78%;
            margin: 6px 0;
            padding: 8px 10px;
            border-radius: 10px;
            background: #fff;
            border: 1px solid var(--wa-border);
            white-space: pre-wrap;
            word-break: break-word;
        }
        .wa-chat-message.is-out {
            margin-left: auto;
            background: var(--wa-mint);
            border-color: #bbf7d0;
        }
        .wa-chat-compose { display: flex; gap: 8px; }
        .wa-chat-compose input {
            flex: 1;
            border: 1.5px solid #dbe3ee;
            border-radius: 10px;
            padding: 10px 12px;
        }
        .wa-link-form {
            margin-top: 18px;
            padding-top: 16px;
            border-top: 1px solid var(--wa-border);
        }
        .wa-inline-search { display: flex; gap: 8px; align-items: end; }
        .wa-inline-search .wa-field { flex: 1; margin-bottom: 0; }
        .wa-client-meta {
            margin: 8px 0 12px;
            color: var(--wa-muted);
            font-size: 0.8rem;
        }
        @media (max-width: 820px) {
            .wa-inbox-grid { grid-template-columns: 1fr; }
            .wa-unknown-list { border-right: 0; border-bottom: 1px solid var(--wa-border); max-height: 260px; }
        }
    </style>
</head>
<body>
<?php includeNavbar('WhatsApp'); ?>

<div class="wa-coord-page">
    <?php if (!empty($wa_schema_error)): ?>
    <div class="wa-msg err" style="margin-bottom:16px;padding:14px 16px;border-radius:10px;background:#fef2f2;color:#991b1b;border:1px solid #fecaca;">
        <strong>Configuración pendiente.</strong> <?php echo htmlspecialchars((string)$wa_schema_error); ?>
    </div>
    <?php endif; ?>
    <header class="wa-hero">
        <div class="wa-hero-main">
            <div class="wa-hero-badge"><i class="fab fa-whatsapp"></i> Canal corporativo</div>
            <h1>WhatsApp Corporativo</h1>
            <p class="subtitle">
                Realiza envíos masivos por WhatsApp a tus clientes registrados ingresando su <strong>nombre</strong> y <strong>cédula</strong>. 
                E integra nuevos contactos, toda la información se guardará y asociará automáticamente en tu base de datos.
            </p>
        </div>
        <div class="wa-hero-icon" aria-hidden="true"><i class="fab fa-whatsapp"></i></div>
    </header>

    <div class="row g-3 align-items-start">
        <div class="col-lg-8">
            <div class="accordion" id="waCoordAccordion">
                <div class="accordion-item">
                    <h2 class="accordion-header" id="waHeadingMasivos">
                        <button class="accordion-button" type="button" data-bs-toggle="collapse"
                                data-bs-target="#waCollapseMasivos" aria-expanded="true" aria-controls="waCollapseMasivos">
                            <i class="fas fa-paper-plane me-2"></i> Envío masivo
                        </button>
                    </h2>
                    <div id="waCollapseMasivos" class="accordion-collapse collapse show"
                         aria-labelledby="waHeadingMasivos">
                        <div class="accordion-body">
                            <div class="wa-row-2">
                                <div class="wa-field">
                                    <label for="waBase"><i class="fas fa-database"></i> Base de datos</label>
                                    <select id="waBase">
                                        <option value="">— Selecciona base —</option>
                                        <?php foreach (($bases ?? []) as $b): ?>
                                            <option value="<?php echo (int)($b['id_base'] ?? $b['id'] ?? 0); ?>">
                                                <?php
                                                $bn = (string)($b['nombre'] ?? $b['nombre_cargue'] ?? ('Base #' . ($b['id_base'] ?? $b['id'] ?? '')));
                                                echo htmlspecialchars($bn, ENT_QUOTES, 'UTF-8');
                                                ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="wa-field">
                                    <label for="waTemplate"><i class="fas fa-file-alt"></i> Plantilla Meta</label>
                                    <select id="waTemplate">
                                        <option value="">Cargando plantillas…</option>
                                    </select>
                                    <div class="wa-hint" id="waTplHint"></div>
                                </div>
                            </div>

                            <div class="wa-field">
                                <label for="waCedulas"><i class="fas fa-id-card"></i> Cédulas (una por línea o separadas por coma)</label>
                                <textarea id="waCedulas" placeholder="1234567890&#10;9876543210"></textarea>
                            </div>

                            <div class="wa-field">
                                <label><i class="fas fa-sliders-h"></i> Variables de plantilla</label>
                                <div class="wa-var-map" id="waVarMap">
                                    <div class="wa-var-chip">
                                        <span>{{1}}</span>
                                        <select data-var="1">
                                            <option value="nombre" selected>nombre</option>
                                            <option value="cedula">cedula</option>
                                        </select>
                                    </div>
                                    <div class="wa-var-chip">
                                        <span>{{2}}</span>
                                        <select data-var="2">
                                            <option value="nombre">nombre</option>
                                            <option value="cedula" selected>cedula</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="wa-actions">
                                <button type="button" class="wa-btn wa-btn-secondary" id="waBtnPreview">
                                    <i class="fas fa-eye"></i> Previsualizar
                                </button>
                                <button type="button" class="wa-btn wa-btn-primary" id="waBtnSend" disabled>
                                    <i class="fas fa-paper-plane"></i> Crear y enviar lote
                                </button>
                            </div>

                            <div id="waMsg" class="wa-msg hint"></div>

                            <div class="wa-stats" id="waStats">
                                <div class="wa-stat ok">
                                    <strong id="stOk">0</strong>
                                    <span>Con teléfono</span>
                                </div>
                                <div class="wa-stat warn">
                                    <strong id="stNoTel">0</strong>
                                    <span>Sin teléfono</span>
                                </div>
                                <div class="wa-stat bad">
                                    <strong id="stMiss">0</strong>
                                    <span>No en base</span>
                                </div>
                            </div>

                            <div class="wa-side-note">
                                <i class="fas fa-info-circle"></i>
                                Si aún no hay plantillas aprobadas en Meta/Kommo, el selector aparecerá vacío.
                            </div>

                            <hr class="my-3">
                            <h3 class="h6 mb-2"><i class="fas fa-history"></i> Campañas recientes</h3>
                            <div class="wa-table-wrap">
                                <?php if (!empty($campanas)): ?>
                                <table class="wa-table">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Base</th>
                                            <th>Plantilla</th>
                                            <th>Estado</th>
                                            <th>Enviados</th>
                                            <th>Errores</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody id="waCampanasBody">
                                        <?php foreach ($campanas as $cm): ?>
                                            <?php $estado = (string)($cm['estado'] ?? ''); ?>
                                            <tr data-id="<?php echo (int)$cm['id']; ?>">
                                                <td><strong>#<?php echo (int)$cm['id']; ?></strong></td>
                                                <td><?php echo htmlspecialchars((string)($cm['base_nombre'] ?? $cm['base_id']), ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><?php echo htmlspecialchars((string)($cm['template_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td>
                                                    <span class="wa-estado <?php echo htmlspecialchars($estado, ENT_QUOTES, 'UTF-8'); ?>">
                                                        <?php echo htmlspecialchars($estado !== '' ? $estado : '—', ENT_QUOTES, 'UTF-8'); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo (int)($cm['enviados'] ?? 0); ?> / <?php echo (int)($cm['total'] ?? 0); ?></td>
                                                <td><?php echo (int)($cm['errores'] ?? 0); ?></td>
                                                <td>
                                                    <?php if ($estado === 'procesando'): ?>
                                                        <button type="button" class="wa-btn wa-btn-ghost wa-btn-lote" data-id="<?php echo (int)$cm['id']; ?>">
                                                            Continuar
                                                        </button>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                                <?php else: ?>
                                    <div class="wa-empty">
                                        <i class="fas fa-inbox"></i>
                                        Aún no hay campañas masivas.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="accordion-item">
                    <h2 class="accordion-header" id="waHeadingSinCedula">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                data-bs-target="#waCollapseSinCedula" aria-expanded="false" aria-controls="waCollapseSinCedula">
                            <i class="fas fa-user-plus me-2"></i> WhatsApp nuevos sin cédula
                        </button>
                    </h2>
                    <div id="waCollapseSinCedula" class="accordion-collapse collapse"
                         aria-labelledby="waHeadingSinCedula">
                        <div class="accordion-body p-0">
                            <div class="wa-card-head border-0 border-bottom">
                                <h2 class="mb-0" style="font-size:0.95rem"><i class="fas fa-comments"></i> Inbox</h2>
                                <button type="button" class="wa-btn wa-btn-ghost" id="waUnknownRefresh">
                                    <i class="fas fa-sync-alt"></i> Actualizar
                                </button>
                            </div>
                            <div class="wa-inbox-grid">
                                <div class="wa-unknown-list" id="waUnknownList">
                                    <div class="wa-empty"><i class="fas fa-spinner fa-spin"></i>Cargando números nuevos…</div>
                                </div>
                                <div class="wa-unknown-detail">
                                    <div class="wa-unknown-placeholder" id="waUnknownPlaceholder">
                                        <div><i class="fab fa-whatsapp fa-2x"></i><br><br>Selecciona un número para pedir y asociar su cédula.</div>
                                    </div>
                                    <div id="waUnknownWorkspace" hidden>
                                        <strong id="waUnknownPhone"></strong>
                                        <div class="wa-hint" id="waUnknownPreview"></div>
                                        <div class="wa-chat-thread" id="waUnknownMessages"></div>
                                        <div class="wa-chat-compose">
                                            <input type="text" id="waUnknownText" maxlength="4000" placeholder="Escribe al cliente para solicitar su cédula">
                                            <button type="button" class="wa-btn wa-btn-primary" id="waUnknownSend">
                                                <i class="fas fa-paper-plane"></i> Enviar
                                            </button>
                                        </div>
                                        <div class="wa-link-form">
                                            <h3>Agregar número a una base de clientes</h3>
                                            <p class="wa-hint">El teléfono se guardará únicamente en el ID de cliente que selecciones.</p>
                                            <div class="wa-inline-search">
                                                <div class="wa-field">
                                                    <label for="waUnknownCedula">Cédula</label>
                                                    <input type="text" id="waUnknownCedula" autocomplete="off" placeholder="Número de cédula">
                                                </div>
                                                <button type="button" class="wa-btn wa-btn-secondary" id="waUnknownSearch">
                                                    <i class="fas fa-search"></i> Buscar
                                                </button>
                                            </div>
                                            <div class="wa-row-2" style="margin-top:14px">
                                                <div class="wa-field">
                                                    <label for="waUnknownBase">Base de clientes</label>
                                                    <select id="waUnknownBase" disabled>
                                                        <option value="">— Busca una cédula —</option>
                                                    </select>
                                                </div>
                                                <div class="wa-field">
                                                    <label for="waUnknownClient">ID de cliente específico</label>
                                                    <select id="waUnknownClient" disabled>
                                                        <option value="">— Selecciona base —</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="wa-client-meta" id="waUnknownClientMeta"></div>
                                            <button type="button" class="wa-btn wa-btn-primary" id="waUnknownLink" disabled>
                                                <i class="fas fa-link"></i> Emparejar con este ID de cliente
                                            </button>
                                            <div class="wa-msg hint" id="waUnknownMsg"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <section class="wa-card wa-hist-card">
                <div class="wa-card-head">
                    <h2><i class="fas fa-stream"></i> Historial</h2>
                </div>
                <div class="wa-card-body">
                    <div class="wa-hist-list" id="waHistList">
                        <div class="wa-empty"><i class="fas fa-spinner fa-spin"></i>Cargando historial…</div>
                    </div>
                    <div class="wa-hist-pager">
                        <button type="button" class="wa-btn wa-btn-ghost" id="waHistPrev" disabled>Anterior</button>
                        <span class="wa-hint mb-0" id="waHistPageLabel">Página 1 de 1</span>
                        <button type="button" class="wa-btn wa-btn-ghost" id="waHistNext" disabled>Siguiente</button>
                    </div>
                </div>
            </section>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function () {
    const els = {
        base: document.getElementById('waBase'),
        tpl: document.getElementById('waTemplate'),
        hint: document.getElementById('waTplHint'),
        cedulas: document.getElementById('waCedulas'),
        preview: document.getElementById('waBtnPreview'),
        send: document.getElementById('waBtnSend'),
        msg: document.getElementById('waMsg'),
        stats: document.getElementById('waStats'),
        stOk: document.getElementById('stOk'),
        stNoTel: document.getElementById('stNoTel'),
        stMiss: document.getElementById('stMiss'),
    };
    let lastPreviewOk = false;
    let templates = [];
    let selectedUnknown = null;
    let cedulaMatches = [];
    let histPage = 1;
    const hist = {
        list: document.getElementById('waHistList'),
        prev: document.getElementById('waHistPrev'),
        next: document.getElementById('waHistNext'),
        label: document.getElementById('waHistPageLabel'),
    };
    const unknown = {
        list: document.getElementById('waUnknownList'),
        refresh: document.getElementById('waUnknownRefresh'),
        placeholder: document.getElementById('waUnknownPlaceholder'),
        workspace: document.getElementById('waUnknownWorkspace'),
        phone: document.getElementById('waUnknownPhone'),
        preview: document.getElementById('waUnknownPreview'),
        messages: document.getElementById('waUnknownMessages'),
        text: document.getElementById('waUnknownText'),
        send: document.getElementById('waUnknownSend'),
        cedula: document.getElementById('waUnknownCedula'),
        search: document.getElementById('waUnknownSearch'),
        base: document.getElementById('waUnknownBase'),
        client: document.getElementById('waUnknownClient'),
        meta: document.getElementById('waUnknownClientMeta'),
        link: document.getElementById('waUnknownLink'),
        msg: document.getElementById('waUnknownMsg'),
    };

    function setMsg(text, cls) {
        els.msg.className = 'wa-msg ' + (cls || 'hint') + (text ? ' is-visible' : '');
        els.msg.textContent = text || '';
    }

    async function api(action, opts) {
        opts = opts || {};
        const isPost = !!opts.body;
        const url = 'index.php?action=' + encodeURIComponent(action) +
            (opts.query ? '&' + new URLSearchParams(opts.query).toString() : '');
        const res = await fetch(url, {
            method: isPost ? 'POST' : 'GET',
            credentials: 'same-origin',
            headers: isPost
                ? { 'Content-Type': 'application/json', Accept: 'application/json' }
                : { Accept: 'application/json' },
            body: isPost ? JSON.stringify(opts.body) : undefined,
        });
        const data = await res.json().catch(function () { return {}; });
        if (!res.ok || data.success === false) {
            throw new Error(data.error || ('HTTP ' + res.status));
        }
        return data;
    }

    function setUnknownMsg(text, cls) {
        unknown.msg.className = 'wa-msg ' + (cls || 'hint') + (text ? ' is-visible' : '');
        unknown.msg.textContent = text || '';
    }

    function resetUnknownSelection() {
        selectedUnknown = null;
        cedulaMatches = [];
        unknown.placeholder.hidden = false;
        unknown.workspace.hidden = true;
        unknown.base.disabled = true;
        unknown.client.disabled = true;
        unknown.link.disabled = true;
    }

    async function loadUnknownList(opts) {
        opts = opts || {};
        const withSync = !!opts.sync;
        try {
            const data = await api('wa_sin_cliente', {
                query: withSync ? { sync: 1, limit: 20, max_age_hours: 72 } : {},
            });
            const rows = data.conversaciones || [];
            if (typeof data.pendientes !== 'undefined') {
                try {
                    window.dispatchEvent(new CustomEvent('wa-coord-inbox-updated', {
                        detail: { pendientes: Number(data.pendientes || 0) }
                    }));
                } catch (e) { /* ignore */ }
                if (Number(data.pendientes || 0) > 0) {
                    openSinCedulaAccordion();
                }
            }
            if (data.sync && (data.sync.created || data.sync.updated)) {
                setUnknownMsg(
                    'Kommo: +' + (data.sync.created || 0) + ' nuevos, ' +
                    (data.sync.updated || 0) + ' actualizados (' + (data.sync.ms || 0) + ' ms).',
                    'ok'
                );
            }
            unknown.list.innerHTML = '';
            if (!rows.length) {
                unknown.list.innerHTML = '<div class="wa-empty"><i class="fas fa-check-circle"></i>No hay números pendientes.</div>';
                resetUnknownSelection();
                return;
            }
            rows.forEach(function (row) {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'wa-unknown-item' +
                    (selectedUnknown && Number(selectedUnknown.id) === Number(row.id) ? ' is-active' : '');
                const phone = document.createElement('strong');
                phone.textContent = row.telefono_e164 || 'Número desconocido';
                const preview = document.createElement('span');
                preview.textContent = row.ultimo_preview || 'Sin vista previa';
                const date = document.createElement('small');
                date.textContent = row.ultimo_mensaje_at || row.created_at || '';
                button.append(phone, preview, date);
                button.addEventListener('click', function () { selectUnknown(row); });
                unknown.list.appendChild(button);
            });
            if (selectedUnknown && !rows.some(function (row) {
                return Number(row.id) === Number(selectedUnknown.id);
            })) {
                resetUnknownSelection();
            }
        } catch (e) {
            unknown.list.innerHTML = '';
            const errorBox = document.createElement('div');
            errorBox.className = 'wa-empty';
            errorBox.textContent = 'No se pudo cargar: ' + String(e.message || 'Error');
            unknown.list.appendChild(errorBox);
        }
    }

    async function selectUnknown(row) {
        selectedUnknown = row;
        cedulaMatches = [];
        unknown.placeholder.hidden = true;
        unknown.workspace.hidden = false;
        unknown.phone.textContent = row.telefono_e164 || 'Número desconocido';
        unknown.preview.textContent = row.ultimo_preview || '';
        unknown.cedula.value = '';
        unknown.base.innerHTML = '<option value="">— Busca una cédula —</option>';
        unknown.client.innerHTML = '<option value="">— Selecciona base —</option>';
        unknown.base.disabled = true;
        unknown.client.disabled = true;
        unknown.link.disabled = true;
        unknown.meta.textContent = '';
        setUnknownMsg('', 'hint');
        await Promise.all([loadUnknownMessages(), loadUnknownList({ sync: false })]);
    }

    async function loadUnknownMessages() {
        if (!selectedUnknown) return;
        try {
            const data = await api('wa_mensajes', {
                query: { conversacion_id: selectedUnknown.id }
            });
            unknown.messages.innerHTML = '';
            (data.mensajes || []).forEach(function (message) {
                const bubble = document.createElement('div');
                bubble.className = 'wa-chat-message' +
                    (message.direccion === 'out' ? ' is-out' : '');
                bubble.textContent = message.cuerpo || ('[' + (message.tipo || 'mensaje') + ']');
                unknown.messages.appendChild(bubble);
            });
            if (!unknown.messages.children.length) {
                unknown.messages.textContent = 'Aún no hay mensajes sincronizados.';
            }
            unknown.messages.scrollTop = unknown.messages.scrollHeight;
        } catch (e) {
            unknown.messages.textContent = e.message || 'No se pudo cargar el chat';
        }
    }

    async function sendUnknownMessage() {
        const text = unknown.text.value.trim();
        if (!selectedUnknown || !text) return;
        unknown.send.disabled = true;
        try {
            await api('wa_enviar', {
                body: { conversacion_id: Number(selectedUnknown.id), texto: text }
            });
            unknown.text.value = '';
            await loadUnknownMessages();
        } catch (e) {
            setUnknownMsg(e.message || 'No se pudo enviar', 'err');
        } finally {
            unknown.send.disabled = false;
        }
    }

    async function lookupUnknownCedula() {
        const cedula = unknown.cedula.value.trim();
        if (!cedula) {
            setUnknownMsg('Ingresa una cédula.', 'err');
            return;
        }
        unknown.search.disabled = true;
        try {
            const data = await api('wa_lookup_cedula', { query: { cedula: cedula } });
            cedulaMatches = data.clientes || [];
            unknown.base.innerHTML = '<option value="">— Selecciona base —</option>';
            unknown.client.innerHTML = '<option value="">— Selecciona base —</option>';
            unknown.client.disabled = true;
            unknown.link.disabled = true;
            const bases = new Map();
            cedulaMatches.forEach(function (client) {
                if (!bases.has(String(client.base_id))) {
                    bases.set(String(client.base_id), client.base_nombre || ('Base #' + client.base_id));
                }
            });
            bases.forEach(function (name, id) {
                const option = document.createElement('option');
                option.value = id;
                option.textContent = name;
                unknown.base.appendChild(option);
            });
            unknown.base.disabled = bases.size === 0;
            setUnknownMsg(
                bases.size ? 'Selecciona la base y luego el ID de cliente.' : 'La cédula no aparece en tus bases.',
                bases.size ? 'ok' : 'err'
            );
        } catch (e) {
            setUnknownMsg(e.message || 'No se pudo buscar la cédula', 'err');
        } finally {
            unknown.search.disabled = false;
        }
    }

    function fillUnknownClients() {
        const baseId = Number(unknown.base.value || 0);
        const rows = cedulaMatches.filter(function (client) {
            return Number(client.base_id) === baseId;
        });
        unknown.client.innerHTML = '<option value="">— Selecciona ID de cliente —</option>';
        rows.forEach(function (client) {
            const option = document.createElement('option');
            option.value = client.id_cliente;
            option.textContent = '#' + client.id_cliente + ' · ' + (client.nombre || 'Sin nombre');
            option.dataset.campana = client.campana_nombre || 'Sin campaña';
            option.dataset.gestion = client.ultima_gestion || 'Sin gestión previa';
            unknown.client.appendChild(option);
        });
        unknown.client.disabled = rows.length === 0;
        unknown.link.disabled = true;
        unknown.meta.textContent = '';
        if (rows.length === 1) {
            unknown.client.value = String(rows[0].id_cliente);
            updateUnknownClientMeta();
        }
    }

    function updateUnknownClientMeta() {
        const option = unknown.client.options[unknown.client.selectedIndex];
        const valid = !!(option && option.value);
        unknown.link.disabled = !valid;
        unknown.meta.textContent = valid
            ? 'Campaña de la base: ' + option.dataset.campana +
              ' · Última gestión: ' + option.dataset.gestion
            : '';
    }

    async function linkUnknownClient() {
        if (!selectedUnknown || !unknown.client.value) return;
        unknown.link.disabled = true;
        try {
            const data = await api('wa_emparejar', {
                body: {
                    conversacion_id: Number(selectedUnknown.id),
                    cliente_id: Number(unknown.client.value),
                }
            });
            setUnknownMsg('Número amarrado únicamente al ID seleccionado y enviado a asignación.', 'ok');
            resetUnknownSelection();
            await loadUnknownList({ sync: false });
            await loadHistorial(1);
            if (data.open_url) {
                // Mantener al coordinador en el inbox; la URL queda disponible para abrir la ficha después.
                unknown.meta.dataset.openUrl = data.open_url;
            }
        } catch (e) {
            setUnknownMsg(e.message || 'No se pudo emparejar', 'err');
            unknown.link.disabled = false;
        }
    }

    function openSinCedulaAccordion() {
        const el = document.getElementById('waCollapseSinCedula');
        if (!el || typeof bootstrap === 'undefined') return;
        const collapse = bootstrap.Collapse.getOrCreateInstance(el, { toggle: false });
        collapse.show();
    }

    function escapeHtml(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function formatHistDate(value) {
        if (!value) return '';
        try {
            const d = new Date(String(value).replace(' ', 'T'));
            if (isNaN(d.getTime())) return String(value);
            return d.toLocaleString('es-CO', {
                day: '2-digit', month: '2-digit', year: 'numeric',
                hour: '2-digit', minute: '2-digit'
            });
        } catch (e) {
            return String(value);
        }
    }

    async function loadHistorial(page) {
        histPage = Math.max(1, Number(page || 1));
        if (!hist.list) return;
        try {
            const data = await api('wa_historial', {
                query: { page: histPage, per_page: 10 }
            });
            const items = data.items || [];
            const totalPages = Math.max(1, Number(data.total_pages || 1));
            histPage = Math.min(histPage, totalPages);
            hist.list.innerHTML = '';
            if (!items.length) {
                hist.list.innerHTML = '<div class="wa-empty"><i class="fas fa-stream"></i>Aún no hay eventos en el historial.</div>';
            } else {
                items.forEach(function (item) {
                    const row = document.createElement('div');
                    row.className = 'wa-hist-item';
                    const isEmpareje = item.tipo === 'empareje_sin_cliente';
                    const tipo = isEmpareje ? 'Asignado' : 'Masivo';
                    const tipoClass = isEmpareje ? ' is-empareje' : '';
                    const payload = item.payload || {};
                    let titulo = String(item.resumen || '');
                    let detail = '';
                    if (item.tipo === 'campana_masiva') {
                        detail = (payload.base_nombre ? ('Base: ' + payload.base_nombre + ' · ') : '') +
                            (payload.template_name ? ('Plantilla: ' + payload.template_name) : '');
                    } else {
                        const ced = String(payload.cliente_cedula || '').replace(/\D+/g, '');
                        const base = String(payload.base_nombre || '').trim();
                        if (ced || base) {
                            titulo = (ced ? ('CC ' + ced) : 'CC —') + ' → ' + (base || 'Base —');
                        }
                        detail = [
                            payload.cliente_nombre ? ('Cliente: ' + payload.cliente_nombre) : '',
                            payload.telefono_e164 ? ('Tel: ' + payload.telefono_e164) : '',
                            payload.asesor_nombre ? ('Asesor: ' + payload.asesor_nombre) : '',
                            payload.gestionado_por ? ('Gestión: ' + payload.gestionado_por) : ''
                        ].filter(Boolean).join(' · ');
                    }
                    row.innerHTML =
                        '<div class="wa-hist-tipo' + tipoClass + '">' + tipo + '</div>' +
                        '<div class="wa-hist-title">' + escapeHtml(titulo) + '</div>' +
                        (detail ? '<div class="wa-hist-meta">' + escapeHtml(detail) + '</div>' : '') +
                        '<div class="wa-hist-meta">' + formatHistDate(item.created_at) +
                        (item.actor_nombre ? (' · ' + escapeHtml(item.actor_nombre)) : '') + '</div>';
                    hist.list.appendChild(row);
                });
            }
            if (hist.label) {
                hist.label.textContent = 'Página ' + histPage + ' de ' + totalPages +
                    ' · ' + Number(data.total || 0) + ' evento(s)';
            }
            if (hist.prev) hist.prev.disabled = histPage <= 1;
            if (hist.next) hist.next.disabled = histPage >= totalPages;
        } catch (e) {
            hist.list.innerHTML = '<div class="wa-empty">No se pudo cargar el historial: ' +
                String(e.message || 'Error') + '</div>';
        }
    }

    function varMap() {
        const map = {};
        document.querySelectorAll('#waVarMap select[data-var]').forEach(function (s) {
            map[s.getAttribute('data-var')] = s.value;
        });
        return map;
    }

    async function loadTemplates() {
        try {
            const data = await api('wa_templates_list');
            templates = data.templates || [];
            els.tpl.innerHTML = '';
            if (!templates.length) {
                els.tpl.innerHTML = '<option value="">— Sin plantillas aprobadas aún —</option>';
                els.hint.textContent = data.hint || 'Cuando Meta apruebe plantillas en Kommo aparecerán aquí.';
                return;
            }
            els.tpl.innerHTML = '<option value="">— Selecciona plantilla —</option>';
            templates.forEach(function (t) {
                const opt = document.createElement('option');
                opt.value = t.id;
                opt.textContent = t.name + (t.language ? ' (' + t.language + ')' : '');
                opt.dataset.name = t.name;
                opt.dataset.lang = t.language || 'es';
                opt.dataset.body = t.body || '';
                els.tpl.appendChild(opt);
            });
            els.hint.textContent = templates.length + ' plantilla(s) disponibles.';
        } catch (e) {
            els.tpl.innerHTML = '<option value="">— Error al cargar —</option>';
            els.hint.textContent = e.message || 'No se pudieron listar plantillas';
        }
    }

    els.preview.addEventListener('click', async function () {
        setMsg('Previsualizando…', 'hint');
        lastPreviewOk = false;
        els.send.disabled = true;
        try {
            const data = await api('wa_campana_preview_cedulas', {
                body: {
                    base_id: Number(els.base.value || 0),
                    cedulas: els.cedulas.value,
                },
            });
            const c = data.counts || {};
            els.stats.classList.add('is-visible');
            els.stOk.textContent = String(c.encontrados || 0);
            els.stNoTel.textContent = String(c.sin_telefono || 0);
            els.stMiss.textContent = String(c.no_encontrados || 0);
            lastPreviewOk = (c.encontrados || 0) > 0;
            els.send.disabled = !lastPreviewOk || !els.tpl.value;
            setMsg(
                lastPreviewOk
                    ? 'Listo para enviar a ' + c.encontrados + ' destinatario(s) con teléfono.'
                    : 'No hay destinatarios con teléfono válido.',
                lastPreviewOk ? 'ok' : 'err'
            );
        } catch (e) {
            setMsg(e.message || 'Error en preview', 'err');
        }
    });

    els.tpl.addEventListener('change', function () {
        els.send.disabled = !lastPreviewOk || !els.tpl.value;
    });

    els.send.addEventListener('click', async function () {
        const opt = els.tpl.options[els.tpl.selectedIndex];
        if (!opt || !opt.value) {
            setMsg('Selecciona una plantilla', 'err');
            return;
        }
        els.send.disabled = true;
        setMsg('Creando campaña y enviando primer lote…', 'hint');
        try {
            const data = await api('wa_campana_crear', {
                body: {
                    base_id: Number(els.base.value || 0),
                    cedulas: els.cedulas.value,
                    template_external_id: opt.value,
                    template_name: opt.dataset.name || opt.textContent,
                    template_language: opt.dataset.lang || 'es',
                    var_map: varMap(),
                },
            });
            const lote = data.lote || {};
            setMsg(
                'Campaña #' + data.campana_masiva_id +
                ' · lote: ' + (lote.enviados || 0) + ' enviados, ' +
                (lote.errores || 0) + ' errores, ' +
                (lote.pendientes || 0) + ' pendientes. Usa «Continuar» si quedan.',
                'ok'
            );
            loadHistorial(1);
            setTimeout(function () { location.reload(); }, 1200);
        } catch (e) {
            setMsg(e.message || 'Error al crear campaña', 'err');
            els.send.disabled = false;
        }
    });

    document.querySelectorAll('.wa-btn-lote').forEach(function (btn) {
        btn.addEventListener('click', async function () {
            const id = Number(btn.getAttribute('data-id'));
            btn.disabled = true;
            try {
                const data = await api('wa_campana_procesar_lote', {
                    body: { campana_masiva_id: id, limit: 20 },
                });
                const lote = data.lote || {};
                alert('Lote: ' + (lote.enviados || 0) + ' enviados, ' +
                    (lote.errores || 0) + ' errores, ' + (lote.pendientes || 0) + ' pendientes');
                location.reload();
            } catch (e) {
                alert(e.message || 'Error');
                btn.disabled = false;
            }
        });
    });

    unknown.refresh.addEventListener('click', function () {
        loadUnknownList({ sync: true });
    });
    unknown.send.addEventListener('click', sendUnknownMessage);
    unknown.text.addEventListener('keydown', function (event) {
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            sendUnknownMessage();
        }
    });
    unknown.search.addEventListener('click', lookupUnknownCedula);
    unknown.cedula.addEventListener('keydown', function (event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            lookupUnknownCedula();
        }
    });
    unknown.base.addEventListener('change', fillUnknownClients);
    unknown.client.addEventListener('change', updateUnknownClientMeta);
    unknown.link.addEventListener('click', linkUnknownClient);
    if (hist.prev) {
        hist.prev.addEventListener('click', function () {
            if (histPage > 1) loadHistorial(histPage - 1);
        });
    }
    if (hist.next) {
        hist.next.addEventListener('click', function () {
            loadHistorial(histPage + 1);
        });
    }

    loadTemplates();
    loadUnknownList({ sync: true });
    loadHistorial(1);
    let unknownPollTick = 0;
    setInterval(function () {
        unknownPollTick++;
        // Cada ~30s sincroniza talks nuevos de Kommo; el resto solo refresca BD local.
        loadUnknownList({ sync: unknownPollTick % 3 === 0 });
        if (selectedUnknown) loadUnknownMessages();
    }, 10000);
})();
</script>
</body>
</html>
