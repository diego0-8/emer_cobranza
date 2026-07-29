/**
 * Panel WhatsApp (espejo Kommo) — gestionar_cliente
 * Init no bloqueante: no congela softphone/buscador.
 */
(function () {
    'use strict';

    const cfg = window.__waConfig || {};
    const clienteId = Number(cfg.clienteId || 0);
    const initialWa = Number(cfg.waId || 0) || null;
    const pollMs = Number(cfg.pollMs || 5000);

    let conversacionId = initialWa;
    let lastMsgId = 0;
    let pollTimer = null;
    let sending = false;
    let sendingTpl = false;
    let templatesCache = [];

    const els = {};

    function $(id) {
        return document.getElementById(id);
    }

    function escapeHtml(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function waActivoLabel(v) {
        if (v === 'si') return { text: 'WA', cls: 'si' };
        if (v === 'no') return { text: 'X', cls: 'no' };
        return { text: '?', cls: 'desconocido' };
    }

    function setError(msg) {
        if (!els.error) return;
        els.error.textContent = msg || '';
        els.error.style.display = msg ? 'block' : 'none';
    }

    async function apiGet(action, params) {
        const q = new URLSearchParams(Object.assign({ action: action }, params || {}));
        const res = await fetch('index.php?' + q.toString(), {
            credentials: 'same-origin',
            headers: { Accept: 'application/json' },
        });
        const text = await res.text();
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            throw new Error('Respuesta no JSON');
        }
        if (!res.ok || data.success === false) {
            throw new Error(data.error || ('HTTP ' + res.status));
        }
        return data;
    }

    async function apiPost(action, body) {
        const res = await fetch('index.php?action=' + encodeURIComponent(action), {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(body || {}),
        });
        const text = await res.text();
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            throw new Error('Respuesta no JSON');
        }
        if (!res.ok || data.success === false) {
            throw new Error(data.error || ('HTTP ' + res.status));
        }
        return data;
    }

    function isImageMsg(m) {
        const t = String(m.tipo || '').toLowerCase();
        if (t === 'picture' || t === 'image' || t === 'sticker') return true;
        const url = String(m.media_url || '').toLowerCase().split('?')[0];
        return /\.(png|jpe?g|gif|webp|bmp)$/.test(url);
    }

    function isPdfMsg(m) {
        const t = String(m.tipo || '').toLowerCase();
        const url = String(m.media_url || '').toLowerCase().split('?')[0];
        const name = String(m.media_name || '').toLowerCase();
        return t === 'pdf' || /\.pdf$/.test(url) || /\.pdf$/.test(name);
    }

    function isAudioMsg(m) {
        const t = String(m.tipo || '').toLowerCase();
        if (t === 'voice' || t === 'audio' || t === 'ptt') return true;
        const url = String(m.media_url || '').toLowerCase().split('?')[0];
        const name = String(m.media_name || '').toLowerCase();
        return /\.(ogg|mp3|opus|m4a|wav|aac|amr|oga)$/.test(url)
            || /\.(ogg|mp3|opus|m4a|wav|aac|amr|oga)$/.test(name);
    }

    function mediaIcon(m) {
        const t = String(m.tipo || '').toLowerCase();
        const url = String(m.media_url || '').toLowerCase().split('?')[0];
        if (t === 'video' || /\.(mp4|mov|3gp|webm)$/.test(url)) return 'fa-file-video';
        if (isAudioMsg(m)) return 'fa-file-audio';
        if (isPdfMsg(m) || /\.pdf$/.test(url)) return 'fa-file-pdf';
        if (/\.(docx?|odt)$/.test(url)) return 'fa-file-word';
        if (/\.(xlsx?|csv|ods)$/.test(url)) return 'fa-file-excel';
        if (/\.(zip|rar|7z)$/.test(url)) return 'fa-file-archive';
        return 'fa-file';
    }

    function mediaProxyUrl(m, fallbackUrl) {
        const msgId = Number(m.id || 0);
        return msgId > 0
            ? ('index.php?action=wa_media&mensaje_id=' + msgId)
            : fallbackUrl;
    }

    function renderMediaHtml(m) {
        const url = String(m.media_url || '').trim();
        if (!url) return '';
        const safeUrl = escapeHtml(url);
        const name = String(m.media_name || '').trim();
        if (isImageMsg(m)) {
            const label = name || 'imagen';
            const previewUrl = mediaProxyUrl(m, url);
            return '<button type="button" class="wa-msg-media wa-msg-img" ' +
                'data-wa-preview="' + escapeHtml(previewUrl) + '" data-wa-source="' + safeUrl + '" ' +
                'data-wa-preview-type="image" data-wa-name="' + escapeHtml(label) + '" title="Ver imagen">' +
                '<img src="' + escapeHtml(previewUrl) + '" alt="' + escapeHtml(label) + '" loading="lazy">' +
                '</button>';
        }
        if (isAudioMsg(m)) {
            const label = name || (String(m.tipo || '').toLowerCase() === 'voice' ? 'Nota de voz' : 'Audio');
            const streamUrl = mediaProxyUrl(m, url);
            return '<div class="wa-msg-media wa-msg-audio">' +
                '<div class="wa-msg-audio-head"><i class="fas fa-microphone"></i><span>' +
                escapeHtml(label) + '</span></div>' +
                '<audio controls preload="none" controlsList="nodownload" src="' +
                escapeHtml(streamUrl) + '">Tu navegador no reproduce audio.</audio></div>';
        }
        if (isPdfMsg(m)) {
            const label = name || 'Documento PDF';
            const previewUrl = mediaProxyUrl(m, url);
            return '<button type="button" class="wa-msg-media wa-msg-file wa-msg-pdf" ' +
                'data-wa-preview="' + escapeHtml(previewUrl) + '" data-wa-source="' + safeUrl + '" ' +
                'data-wa-preview-type="pdf" data-wa-name="' + escapeHtml(label) + '" title="Previsualizar PDF">' +
                '<i class="fas fa-file-pdf"></i><span>' + escapeHtml(label) + '</span>' +
                '<em class="wa-msg-preview-hint">Ver</em></button>';
        }
        const label = name || 'Descargar archivo';
        return '<a class="wa-msg-media wa-msg-file" href="' + safeUrl +
            '" target="_blank" rel="noopener" title="' + escapeHtml(label) + '">' +
            '<i class="fas ' + mediaIcon(m) + '"></i><span>' + escapeHtml(label) + '</span></a>';
    }

    function ensureLightbox() {
        let box = document.getElementById('waMediaLightbox');
        if (box) return box;
        box = document.createElement('div');
        box.id = 'waMediaLightbox';
        box.className = 'wa-lightbox';
        box.hidden = true;
        box.innerHTML =
            '<div class="wa-lightbox-backdrop" data-wa-lb-close></div>' +
            '<div class="wa-lightbox-dialog">' +
            '  <button type="button" class="wa-lightbox-close" data-wa-lb-close aria-label="Cerrar"><i class="fas fa-times"></i></button>' +
            '  <img class="wa-lightbox-img" id="waLightboxImg" alt="" hidden>' +
            '  <iframe class="wa-lightbox-pdf" id="waLightboxPdf" title="PDF" hidden></iframe>' +
            '  <div class="wa-lightbox-bar">' +
            '    <span class="wa-lightbox-name" id="waLightboxName"></span>' +
            '    <a class="wa-lightbox-open" id="waLightboxOpen" href="#" target="_blank" rel="noopener"><i class="fas fa-external-link-alt"></i> Abrir</a>' +
            '    <a class="wa-lightbox-download" id="waLightboxDownload" href="#" target="_blank" rel="noopener" download><i class="fas fa-download"></i> Descargar</a>' +
            '  </div>' +
            '</div>';
        document.body.appendChild(box);
        box.addEventListener('click', function (ev) {
            if (ev.target.closest('[data-wa-lb-close]')) closeLightbox();
        });
        document.addEventListener('keydown', function (ev) {
            if (ev.key === 'Escape' && box && !box.hidden) closeLightbox();
        });
        return box;
    }

    function openLightbox(url, name, type, sourceUrl) {
        if (!url) return;
        const box = ensureLightbox();
        const img = document.getElementById('waLightboxImg');
        const pdf = document.getElementById('waLightboxPdf');
        const nameEl = document.getElementById('waLightboxName');
        const openBtn = document.getElementById('waLightboxOpen');
        const dl = document.getElementById('waLightboxDownload');
        const kind = type === 'pdf' ? 'pdf' : 'image';
        const external = sourceUrl || url;
        nameEl.textContent = name || (kind === 'pdf' ? 'Documento PDF' : 'Imagen');
        openBtn.href = external;
        dl.href = external;
        box.classList.toggle('wa-lightbox--pdf', kind === 'pdf');
        if (kind === 'pdf') {
            img.hidden = true;
            img.removeAttribute('src');
            pdf.hidden = false;
            pdf.src = url + (url.indexOf('#') === -1 ? '#view=FitH' : '');
        } else {
            pdf.hidden = true;
            pdf.removeAttribute('src');
            img.hidden = false;
            img.src = url;
        }
        box.hidden = false;
        document.body.classList.add('wa-lightbox-open');
    }

    function closeLightbox() {
        const box = document.getElementById('waMediaLightbox');
        if (!box) return;
        box.hidden = true;
        const img = document.getElementById('waLightboxImg');
        const pdf = document.getElementById('waLightboxPdf');
        if (img) img.removeAttribute('src');
        if (pdf) pdf.removeAttribute('src');
        document.body.classList.remove('wa-lightbox-open');
    }

    function appendMessages(mensajes, replace) {
        if (!els.thread) return;
        if (replace) {
            els.thread.innerHTML = '';
            lastMsgId = 0;
        }
        if (!mensajes || !mensajes.length) {
            if (replace) {
                els.thread.innerHTML = '<div class="wa-empty">Sin mensajes aún. Escribe el primero.</div>';
            }
            return;
        }
        if (replace) els.thread.innerHTML = '';
        else if (els.thread.querySelector('.wa-empty')) els.thread.innerHTML = '';

        mensajes.forEach(function (m) {
            const id = Number(m.id || 0);
            if (id && id <= lastMsgId) return;
            if (id > lastMsgId) lastMsgId = id;
            const div = document.createElement('div');
            div.className = 'wa-msg ' + (m.direccion === 'out' ? 'out' : 'in') +
                (m.status ? ' status-' + m.status : '');
            const meta = [];
            if (m.created_at) meta.push(String(m.created_at).slice(11, 16));
            if (m.direccion === 'out' && m.status) {
                const map = {
                    pendiente_envio: 'pendiente',
                    enviado: 'enviado',
                    sent: 'enviado',
                    delivered: 'entregado',
                    error_envio: 'error',
                };
                meta.push(map[m.status] || m.status);
            }
            let html = renderMediaHtml(m);
            const cuerpo = (m.cuerpo || '').trim();
            if (cuerpo) html += '<span class="wa-msg-text">' + escapeHtml(cuerpo) + '</span>';
            html += '<span class="wa-msg-meta">' + escapeHtml(meta.join(' · ')) + '</span>';
            div.innerHTML = html;
            els.thread.appendChild(div);
        });
        els.thread.scrollTop = els.thread.scrollHeight;
    }

    function updateWaActivo(v) {
        if (!els.activo) return;
        const info = waActivoLabel(v);
        els.activo.textContent = info.text;
        els.activo.className = 'wa-activo-badge ' + info.cls;
    }

    function setPuedeEnviar(puede) {
        const allowed = puede !== false;
        if (els.input) els.input.disabled = !allowed;
        if (els.btn) els.btn.disabled = !allowed || sending;
        if (!allowed && els.input) {
            els.input.placeholder = 'Otro asesor tiene este chat activo';
        } else if (els.input) {
            els.input.placeholder = 'Escribe un mensaje…';
        }
    }

    function fillTelefonos(telefonos, selectedE164) {
        if (!els.select) return;
        els.select.innerHTML = '';
        if (!telefonos || !telefonos.length) {
            const opt = document.createElement('option');
            opt.value = '';
            opt.textContent = 'Sin números en perfil';
            els.select.appendChild(opt);
            els.select.disabled = true;
            return;
        }
        els.select.disabled = false;
        telefonos.forEach(function (t) {
            const opt = document.createElement('option');
            opt.value = t.raw;
            opt.dataset.e164 = t.e164;
            opt.textContent = t.display + ' (' + t.e164 + ')';
            if (selectedE164) {
                const sel = String(selectedE164).replace(/\s/g, '');
                const e164 = String(t.e164 || '').replace(/\s/g, '');
                if (e164 === sel || e164.endsWith(sel.replace(/^\+/, '')) || sel.endsWith(e164.replace(/^\+/, ''))) {
                    opt.selected = true;
                }
            }
            els.select.appendChild(opt);
        });
    }

    async function loadEstado() {
        try {
            const data = await apiGet('wa_estado');
            if (els.mode) {
                els.mode.textContent = data.kommo_enabled ? 'Kommo en vivo' : 'Modo demo';
            }
        } catch (e) { /* ignore */ }
    }

    async function loadBubbles() {
        if (typeof window.__waBubblesRefresh === 'function') {
            return window.__waBubblesRefresh();
        }
    }

    async function ensureConversacion(telefono) {
        if (!clienteId) return null;
        const params = { cliente_id: clienteId };
        if (telefono) params.telefono = telefono;
        if (cfg.claim) params.claim = 1;
        const data = await apiGet('wa_conversacion_cliente', params);
        fillTelefonos(data.telefonos || [], data.conversacion && data.conversacion.telefono_e164);
        if (!data.telefonos || !data.telefonos.length) {
            if (els.thread) {
                els.thread.innerHTML = '<div class="wa-empty">Este cliente no tiene números en el perfil.</div>';
            }
            return null;
        }
        if (data.conversacion) {
            conversacionId = Number(data.conversacion.id);
            updateWaActivo(data.conversacion.wa_activo);
        }
        if (typeof data.puede_enviar !== 'undefined') {
            setPuedeEnviar(data.puede_enviar);
        }
        return data.conversacion || null;
    }

    async function loadMensajes(full) {
        if (!conversacionId || !clienteId) return;
        const params = {
            conversacion_id: conversacionId,
            cliente_id: clienteId,
        };
        if (!full && lastMsgId > 0) params.after_id = lastMsgId;
        if (full) params.skip_sync = 1;
        const data = await apiGet('wa_mensajes', params);
        if (data.conversacion) updateWaActivo(data.conversacion.wa_activo);
        if (typeof data.puede_enviar !== 'undefined') setPuedeEnviar(data.puede_enviar);
        appendMessages(data.mensajes || [], !!full || lastMsgId === 0);
    }

    function syncInBackground() {
        if (!conversacionId || !clienteId) return;
        apiGet('wa_mensajes', {
            conversacion_id: conversacionId,
            cliente_id: clienteId,
            after_id: lastMsgId || 0,
        }).then(function (d) {
            if (d.mensajes && d.mensajes.length) appendMessages(d.mensajes, false);
        }).catch(function () { /* ignore */ });
    }

    async function sendMessage() {
        if (sending) return;
        const texto = (els.input && els.input.value || '').trim();
        if (!texto) return;
        const telefono = els.select ? els.select.value : '';
        if (!telefono) {
            setError('Selecciona un número del perfil');
            return;
        }
        sending = true;
        if (els.btn) els.btn.disabled = true;
        setError('');
        try {
            if (!conversacionId) await ensureConversacion(telefono);
            const data = await apiPost('wa_enviar', {
                conversacion_id: conversacionId,
                cliente_id: clienteId,
                telefono: telefono,
                texto: texto,
            });
            if (data.conversacion) {
                conversacionId = Number(data.conversacion.id);
                updateWaActivo(data.conversacion.wa_activo);
            }
            els.input.value = '';
            await loadMensajes(true);
            syncInBackground();
        } catch (e) {
            setError(e.message || 'Error al enviar');
        } finally {
            sending = false;
            if (els.btn) els.btn.disabled = false;
        }
    }

    function selectedTemplate() {
        if (!els.tplSelect) return null;
        const id = els.tplSelect.value;
        if (!id) return null;
        for (let i = 0; i < templatesCache.length; i++) {
            if (String(templatesCache[i].id) === String(id)) return templatesCache[i];
        }
        return null;
    }

    function updateTemplatePreview() {
        const t = selectedTemplate();
        if (!els.tplPreview) return;
        if (!t) {
            els.tplPreview.hidden = true;
            els.tplPreview.textContent = '';
            if (els.tplBtn) els.tplBtn.disabled = true;
            return;
        }
        const cat = t.category ? (' [' + t.category + ']') : '';
        els.tplPreview.hidden = false;
        els.tplPreview.textContent = (t.name || '') + cat + '\n\n' + (t.body || '(sin vista previa)');
        if (els.tplBtn) els.tplBtn.disabled = sendingTpl;
    }

    async function loadTemplates() {
        if (!els.tplSelect) return;
        try {
            const data = await apiGet('wa_templates_list');
            templatesCache = data.templates || [];
            els.tplSelect.innerHTML = '';
            if (!templatesCache.length) {
                els.tplSelect.innerHTML = '<option value="">— Sin plantillas WABA —</option>';
                if (els.tplBtn) els.tplBtn.disabled = true;
                if (els.tplPreview) {
                    els.tplPreview.hidden = false;
                    els.tplPreview.textContent = data.hint || 'No hay plantillas disponibles en Kommo.';
                }
                return;
            }
            const opt0 = document.createElement('option');
            opt0.value = '';
            opt0.textContent = '— Selecciona plantilla para iniciar —';
            els.tplSelect.appendChild(opt0);
            templatesCache.forEach(function (t) {
                const opt = document.createElement('option');
                opt.value = t.id;
                opt.textContent = t.name + (t.language ? ' (' + t.language + ')' : '') +
                    (t.category ? ' · ' + t.category : '');
                els.tplSelect.appendChild(opt);
            });
            updateTemplatePreview();
        } catch (e) {
            els.tplSelect.innerHTML = '<option value="">— Error al cargar plantillas —</option>';
            if (els.tplBtn) els.tplBtn.disabled = true;
            setError(e.message || 'No se pudieron cargar plantillas');
        }
    }

    async function sendTemplate() {
        if (sendingTpl) return;
        const t = selectedTemplate();
        if (!t) {
            setError('Selecciona una plantilla');
            return;
        }
        const telefono = els.select ? els.select.value : '';
        if (!telefono) {
            setError('Selecciona un número del perfil');
            return;
        }
        sendingTpl = true;
        if (els.tplBtn) els.tplBtn.disabled = true;
        setError('');
        try {
            if (!conversacionId) await ensureConversacion(telefono);
            const nombre = String(cfg.clienteNombre || '').trim();
            const primer = nombre ? nombre.split(/\s+/)[0] : '';
            const cedula = String(cfg.clienteCedula || '').trim();
            const params = [];
            if (primer) params.push(primer);
            if (cedula) params.push(cedula);
            const data = await apiPost('wa_enviar_plantilla', {
                cliente_id: clienteId,
                conversacion_id: conversacionId || 0,
                telefono: telefono,
                template_id: t.id,
                template_name: t.name,
                template_language: t.language || 'es',
                params: params,
            });
            if (data.conversacion) {
                conversacionId = Number(data.conversacion.id);
                updateWaActivo(data.conversacion.wa_activo);
            }
            await loadMensajes(true);
            syncInBackground();
            if (typeof window.__waBubblesRefresh === 'function') {
                window.__waBubblesRefresh();
            }
        } catch (e) {
            setError(e.message || 'Error al enviar plantilla');
        } finally {
            sendingTpl = false;
            updateTemplatePreview();
        }
    }

    async function onTelefonoChange() {
        const telefono = els.select ? els.select.value : '';
        if (!telefono) return;
        setError('');
        try {
            lastMsgId = 0;
            const conv = await ensureConversacion(telefono);
            if (!conv) return;
            await loadMensajes(true);
            syncInBackground();
        } catch (e) {
            setError(e.message || 'No se pudo abrir el chat');
        }
    }

    async function tick() {
        try {
            if (conversacionId) await loadMensajes(false);
        } catch (e) { /* ignore */ }
    }

    async function bootWhatsapp() {
        loadEstado().catch(function () {});
        try {
            if (initialWa) {
                conversacionId = initialWa;
                const data = await apiGet('wa_conversacion_cliente', {
                    cliente_id: clienteId,
                    conversacion_id: initialWa,
                    claim: cfg.claim ? 1 : 0,
                });
                fillTelefonos(
                    data.telefonos || [],
                    (data.conversacion && data.conversacion.telefono_e164)
                        || (data.telefono_preferido && data.telefono_preferido.e164)
                );
                if (typeof data.puede_enviar !== 'undefined') {
                    setPuedeEnviar(data.puede_enviar);
                }
                if (!data.telefonos || !data.telefonos.length) {
                    if (els.thread) {
                        els.thread.innerHTML = '<div class="wa-empty">Este cliente no tiene números en el perfil.</div>';
                    }
                    return;
                }
                await loadMensajes(true);
            } else {
                const conv = await ensureConversacion(null);
                if (!conv) return;
                await loadMensajes(true);
            }
            setTimeout(syncInBackground, 800);
        } catch (e) {
            setError(e.message || 'No se pudo iniciar WhatsApp');
            if (els.thread) {
                els.thread.innerHTML = '<div class="wa-empty">WhatsApp no disponible ahora.</div>';
            }
        }
        pollTimer = setInterval(tick, pollMs);
    }

    function init() {
        els.rail = $('waBubbleRail');
        els.panel = $('waPanel');
        els.select = $('waPhoneSelect');
        els.activo = $('waActivoBadge');
        els.thread = $('waThread');
        els.input = $('waComposeInput');
        els.btn = $('waComposeSend');
        els.error = $('waError');
        els.mode = $('waPanelMode');
        els.tplSelect = null;
        els.tplBtn = null;
        els.tplPreview = null;

        if (!els.panel || !clienteId) return;

        if (els.thread) {
            els.thread.innerHTML = '<div class="wa-empty">Cargando WhatsApp…</div>';
        }
        if (els.btn) els.btn.addEventListener('click', sendMessage);
        if (els.input) {
            els.input.addEventListener('keydown', function (ev) {
                if (ev.key === 'Enter' && !ev.shiftKey) {
                    ev.preventDefault();
                    sendMessage();
                }
            });
        }
        if (els.select) els.select.addEventListener('change', onTelefonoChange);
        if (els.thread) {
            els.thread.addEventListener('click', function (ev) {
                const btn = ev.target.closest('[data-wa-preview]');
                if (!btn) return;
                ev.preventDefault();
                openLightbox(
                    btn.getAttribute('data-wa-preview'),
                    btn.getAttribute('data-wa-name') || '',
                    btn.getAttribute('data-wa-preview-type') || 'image',
                    btn.getAttribute('data-wa-source') || btn.getAttribute('data-wa-preview')
                );
            });
        }
        ensureLightbox();

        // Diferido: deja que softphone/buscador arranquen primero
        setTimeout(function () {
            bootWhatsapp().catch(function (e) {
                setError(e.message || 'No se pudo iniciar WhatsApp');
            });
        }, 50);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
