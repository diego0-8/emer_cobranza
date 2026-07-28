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
            max-width: 1100px;
            margin: 0 auto;
            padding: 28px 20px 48px;
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
        .wa-field input,
        .wa-field textarea {
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
        .wa-field input:focus,
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
            <h1>WhatsApp masivo</h1>
            <p>
                Envía plantillas aprobadas por Meta a cédulas de una base.
                Personaliza automáticamente <strong>nombre</strong> y <strong>cédula</strong> por destinatario.
            </p>
        </div>
        <div class="wa-hero-icon" aria-hidden="true"><i class="fab fa-whatsapp"></i></div>
    </header>

    <section class="wa-card" style="margin-bottom:18px">
        <div class="wa-card-head">
            <h2><i class="fas fa-file-circle-plus"></i> Plantillas de Meta</h2>
            <button type="button" class="wa-btn wa-btn-ghost" id="waBtnSyncTemplates">
                Actualizar estados
            </button>
        </div>
        <div class="wa-card-body">
            <div class="wa-row-2">
                <div class="wa-field">
                    <label for="waTplNewName">Nombre interno</label>
                    <input id="waTplNewName" type="text" maxlength="512" placeholder="recordatorio_pago">
                    <div class="wa-hint">Solo minúsculas, números y guion bajo.</div>
                </div>
                <div class="wa-field">
                    <label for="waTplNewLang">Idioma</label>
                    <select id="waTplNewLang">
                        <option value="es">Español</option>
                        <option value="es_CO">Español (Colombia)</option>
                    </select>
                </div>
            </div>
            <div class="wa-field">
                <label for="waTplNewBody">Cuerpo Utility</label>
                <textarea id="waTplNewBody" placeholder="Hola {{1}}, te recordamos que..."></textarea>
                <div class="wa-hint">Meta revisa y aprueba la plantilla. El CRM solo la envía a revisión.</div>
            </div>
            <div class="wa-field">
                <label for="waTplExamples">Ejemplos de variables (separados por |)</label>
                <input id="waTplExamples" type="text" placeholder="María | 123456789">
            </div>
            <div class="wa-actions">
                <button type="button" class="wa-btn wa-btn-primary" id="waBtnCreateTemplate">
                    Enviar a revisión de Meta
                </button>
            </div>
            <div id="waTplAdminMsg" class="wa-msg hint"></div>
            <div class="wa-table-wrap" style="margin-top:14px">
                <table class="wa-table" style="min-width:560px">
                    <thead><tr><th>Plantilla</th><th>Idioma</th><th>Categoría</th><th>Estado Meta</th></tr></thead>
                    <tbody id="waTemplatesStatus">
                        <tr><td colspan="4">Cargando…</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <div class="wa-grid">
        <section class="wa-card">
            <div class="wa-card-head">
                <h2><i class="fas fa-paper-plane"></i> Nueva campaña</h2>
            </div>
            <div class="wa-card-body">
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
                    Si aún no hay plantillas aprobadas por Meta, el selector aparecerá vacío.
                    Puedes preparar la base y las cédulas con anticipación.
                </div>
            </div>
        </section>

        <section class="wa-card">
            <div class="wa-card-head">
                <h2><i class="fas fa-history"></i> Campañas recientes</h2>
            </div>
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
        </section>
    </div>
</div>

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
        tplNewName: document.getElementById('waTplNewName'),
        tplNewLang: document.getElementById('waTplNewLang'),
        tplNewBody: document.getElementById('waTplNewBody'),
        tplExamples: document.getElementById('waTplExamples'),
        tplCreate: document.getElementById('waBtnCreateTemplate'),
        tplSync: document.getElementById('waBtnSyncTemplates'),
        tplAdminMsg: document.getElementById('waTplAdminMsg'),
        tplStatus: document.getElementById('waTemplatesStatus'),
    };
    let lastPreviewOk = false;
    let templates = [];

    function setMsg(text, cls) {
        els.msg.className = 'wa-msg ' + (cls || 'hint') + (text ? ' is-visible' : '');
        els.msg.textContent = text || '';
    }

    function setTplAdminMsg(text, cls) {
        els.tplAdminMsg.className = 'wa-msg ' + (cls || 'hint') + (text ? ' is-visible' : '');
        els.tplAdminMsg.textContent = text || '';
    }

    function renderTemplateStatuses(allTemplates) {
        els.tplStatus.innerHTML = '';
        if (!allTemplates.length) {
            els.tplStatus.innerHTML = '<tr><td colspan="4">No hay plantillas visibles.</td></tr>';
            return;
        }
        allTemplates.forEach(function (t) {
            const tr = document.createElement('tr');
            [t.name, t.language || '—', t.category || '—', (t.status || '—').toUpperCase()].forEach(function (value) {
                const td = document.createElement('td');
                td.textContent = value;
                tr.appendChild(td);
            });
            els.tplStatus.appendChild(tr);
        });
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
            const metaActive = data.provider === 'meta';
            els.tplCreate.disabled = !metaActive;
            if (!metaActive) {
                setTplAdminMsg(
                    'Gestión directa de plantillas pendiente: completa config/meta.local.php y activa WA_PROVIDER=meta.',
                    'hint'
                );
            }
            const allTemplates = data.templates || [];
            renderTemplateStatuses(allTemplates);
            templates = allTemplates.filter(function (t) {
                return !t.status || String(t.status).toLowerCase() === 'approved';
            });
            els.tpl.innerHTML = '';
            if (!templates.length) {
                els.tpl.innerHTML = '<option value="">— Sin plantillas aprobadas aún —</option>';
                els.hint.textContent = data.hint || 'Cuando Meta apruebe plantillas aparecerán aquí.';
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
            els.hint.textContent = templates.length + ' plantilla(s) aprobadas disponibles.';
        } catch (e) {
            els.tpl.innerHTML = '<option value="">— Error al cargar —</option>';
            els.hint.textContent = e.message || 'No se pudieron listar plantillas';
        }
    }

    els.tplSync.addEventListener('click', function () {
        els.tplSync.disabled = true;
        loadTemplates().finally(function () { els.tplSync.disabled = false; });
    });

    els.tplCreate.addEventListener('click', async function () {
        const name = (els.tplNewName.value || '').trim();
        const body = (els.tplNewBody.value || '').trim();
        if (!name || !body) {
            setTplAdminMsg('Nombre y cuerpo son requeridos.', 'err');
            return;
        }
        els.tplCreate.disabled = true;
        setTplAdminMsg('Enviando plantilla Utility a revisión…', 'hint');
        try {
            const examples = (els.tplExamples.value || '')
                .split('|')
                .map(function (v) { return v.trim(); })
                .filter(Boolean);
            const data = await api('wa_templates_crear', {
                body: {
                    name: name,
                    language: els.tplNewLang.value || 'es',
                    category: 'UTILITY',
                    body: body,
                    examples: examples,
                },
            });
            setTplAdminMsg(data.message || 'Plantilla enviada a Meta.', 'ok');
            els.tplNewName.value = '';
            els.tplNewBody.value = '';
            els.tplExamples.value = '';
            await loadTemplates();
        } catch (e) {
            setTplAdminMsg(e.message || 'No se pudo crear la plantilla.', 'err');
        } finally {
            els.tplCreate.disabled = false;
        }
    });

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

    loadTemplates();
})();
</script>
</body>
</html>
