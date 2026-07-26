/**
 * Panel WhatsApp (espejo Kommo) — gestionar_cliente
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
        const q = new URLSearchParams(Object.assign({ action }, params || {}));
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

    function renderBubbles(chats, total, extra) {
        if (!els.rail) return;
        els.rail.innerHTML = '';
        (chats || []).forEach(function (c) {
            const a = document.createElement('a');
            a.className = 'wa-bubble' + (Number(c.id) === Number(conversacionId) ? ' is-active' : '');
            a.href = 'index.php?action=gestionar_cliente&id=' + encodeURIComponent(c.cliente_id) +
                '&wa=' + encodeURIComponent(c.id);
            a.title = (c.cliente_nombre || 'Cliente') + ' · ' + (c.telefono_e164 || '');
            const initials = String(c.cliente_nombre || 'WA').trim().slice(0, 2).toUpperCase();
            a.textContent = initials;
            const n = Number(c.no_leidos || 0);
            if (n > 0) {
                const b = document.createElement('span');
                b.className = 'wa-bubble-badge';
                b.textContent = n > 99 ? '99+' : String(n);
                a.appendChild(b);
            }
            els.rail.appendChild(a);
        });
        if (extra > 0) {
            const more = document.createElement('div');
            more.className = 'wa-bubble wa-bubble-more';
            more.title = 'Otros chats: ' + extra;
            more.textContent = '+' + extra;
            els.rail.appendChild(more);
        }
    }

    function isImageMsg(m) {
        const t = String(m.tipo || '').toLowerCase();
        if (t === 'picture' || t === 'image' || t === 'sticker') return true;
        const url = String(m.media_url || '').toLowerCase().split('?')[0];
        return /\.(png|jpe?g|gif|webp|bmp)$/.test(url);
    }

    function mediaIcon(m) {
        const t = String(m.tipo || '').toLowerCase();
        const url = String(m.media_url || '').toLowerCase().split('?')[0];
        if (t === 'video' || /\.(mp4|mov|3gp|webm)$/.test(url)) return 'fa-file-video';
        if (t === 'voice' || t === 'audio' || /\.(ogg|mp3|opus|m4a|wav)$/.test(url)) return 'fa-file-audio';
        if (/\.pdf$/.test(url)) return 'fa-file-pdf';
        if (/\.(docx?|odt)$/.test(url)) return 'fa-file-word';
        if (/\.(xlsx?|csv|ods)$/.test(url)) return 'fa-file-excel';
        if (/\.(zip|rar|7z)$/.test(url)) return 'fa-file-archive';
        return 'fa-file';
    }

    /**
     * Devuelve el HTML del adjunto (imagen embebida o enlace descargable).
     * Las imágenes abren lightbox de previsualización (no descargan al clic).
     */
    function renderMediaHtml(m) {
        const url = String(m.media_url || '').trim();
        if (!url) return '';
        const safeUrl = escapeHtml(url);
        const name = String(m.media_name || '').trim();
        if (isImageMsg(m)) {
            return '<button type="button" class="wa-msg-media wa-msg-img" ' +
                'data-wa-preview="' + safeUrl + '" ' +
                'data-wa-name="' + escapeHtml(name || 'imagen') + '" ' +
                'title="Ver imagen">' +
                '<img src="' + safeUrl + '" alt="' + escapeHtml(name || 'imagen') + '" loading="lazy">' +
                '</button>';
        }
        const label = name || 'Descargar archivo';
        return '<a class="wa-msg-media wa-msg-file" href="' + safeUrl +
            '" target="_blank" rel="noopener" title="' + escapeHtml(label) + '">' +
            '<i class="fas ' + mediaIcon(m) + '"></i>' +
            '<span>' + escapeHtml(label) + '</span></a>';
    }

    function ensureLightbox() {
        let box = document.getElementById('waMediaLightbox');
        if (box) return box;
        box = document.createElement('div');
        box.id = 'waMediaLightbox';
        box.className = 'wa-lightbox';
        box.setAttribute('role', 'dialog');
        box.setAttribute('aria-modal', 'true');
        box.setAttribute('aria-label', 'Previsualización de imagen');
        box.hidden = true;
        box.innerHTML =
            '<div class="wa-lightbox-backdrop" data-wa-lb-close></div>' +
            '<div class="wa-lightbox-dialog">' +
            '  <button type="button" class="wa-lightbox-close" data-wa-lb-close title="Cerrar" aria-label="Cerrar">' +
            '    <i class="fas fa-times"></i>' +
            '  </button>' +
            '  <img class="wa-lightbox-img" id="waLightboxImg" alt="Previsualización">' +
            '  <div class="wa-lightbox-bar">' +
            '    <span class="wa-lightbox-name" id="waLightboxName"></span>' +
            '    <a class="wa-lightbox-download" id="waLightboxDownload" href="#" target="_blank" rel="noopener" download>' +
            '      <i class="fas fa-download"></i> Descargar' +
            '    </a>' +
            '  </div>' +
            '</div>';
        document.body.appendChild(box);
        box.addEventListener('click', function (ev) {
            if (ev.target.closest('[data-wa-lb-close]')) {
                closeLightbox();
            }
        });
        document.addEventListener('keydown', function (ev) {
            if (ev.key === 'Escape' && box && !box.hidden) {
                closeLightbox();
            }
        });
        return box;
    }

    function openLightbox(url, name) {
        if (!url) return;
        const box = ensureLightbox();
        const img = document.getElementById('waLightboxImg');
        const nameEl = document.getElementById('waLightboxName');
        const dl = document.getElementById('waLightboxDownload');
        img.src = url;
        img.alt = name || 'imagen';
        nameEl.textContent = name || 'Imagen';
        dl.href = url;
        dl.setAttribute('download', name || 'imagen');
        box.hidden = false;
        document.body.classList.add('wa-lightbox-open');
    }

    function closeLightbox() {
        const box = document.getElementById('waMediaLightbox');
        if (!box) return;
        box.hidden = true;
        const img = document.getElementById('waLightboxImg');
        if (img) img.removeAttribute('src');
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
        if (replace) {
            els.thread.innerHTML = '';
        } else if (els.thread.querySelector('.wa-empty')) {
            els.thread.innerHTML = '';
        }
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
            if (cuerpo) {
                html += '<span class="wa-msg-text">' + escapeHtml(cuerpo) + '</span>';
            }
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
        els.activo.title = v === 'si' ? 'WhatsApp activo'
            : (v === 'no' ? 'Sin WhatsApp / rechazado' : 'Estado desconocido');
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
            if (selectedE164 && t.e164 === selectedE164) opt.selected = true;
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
        try {
            const data = await apiGet('wa_mis_chats', { limit: 10 });
            renderBubbles(data.chats || [], data.total || 0, data.extra || 0);
        } catch (e) { /* ignore */ }
    }

    async function ensureConversacion(telefono) {
        if (!clienteId) return null;
        const params = { cliente_id: clienteId };
        if (telefono) params.telefono = telefono;
        const data = await apiGet('wa_conversacion_cliente', params);
        fillTelefonos(data.telefonos || [], data.conversacion && data.conversacion.telefono_e164);
        if (data.conversacion) {
            conversacionId = Number(data.conversacion.id);
            updateWaActivo(data.conversacion.wa_activo);
            // Deep-link limpio en URL
            const url = new URL(window.location.href);
            url.searchParams.set('id', String(clienteId));
            url.searchParams.set('wa', String(conversacionId));
            window.history.replaceState({}, '', url.toString());
        }
        return data.conversacion;
    }

    async function loadMensajes(full) {
        if (!conversacionId || !clienteId) return;
        const params = {
            conversacion_id: conversacionId,
            cliente_id: clienteId,
        };
        if (!full && lastMsgId > 0) params.after_id = lastMsgId;
        const data = await apiGet('wa_mensajes', params);
        if (data.conversacion) updateWaActivo(data.conversacion.wa_activo);
        appendMessages(data.mensajes || [], !!full || lastMsgId === 0);
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
            if (!conversacionId) {
                await ensureConversacion(telefono);
            }
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
            await loadBubbles();
        } catch (e) {
            setError(e.message || 'Error al enviar');
        } finally {
            sending = false;
            if (els.btn) els.btn.disabled = false;
        }
    }

    async function onTelefonoChange() {
        const telefono = els.select ? els.select.value : '';
        if (!telefono) return;
        setError('');
        try {
            lastMsgId = 0;
            await ensureConversacion(telefono);
            await loadMensajes(true);
            await loadBubbles();
        } catch (e) {
            setError(e.message || 'No se pudo abrir el chat');
        }
    }

    async function tick() {
        try {
            await loadBubbles();
            if (conversacionId) await loadMensajes(false);
        } catch (e) { /* ignore poll errors */ }
    }

    async function init() {
        els.rail = $('waBubbleRail');
        els.panel = $('waPanel');
        els.select = $('waPhoneSelect');
        els.activo = $('waActivoBadge');
        els.thread = $('waThread');
        els.input = $('waComposeInput');
        els.btn = $('waComposeSend');
        els.error = $('waError');
        els.mode = $('waPanelMode');

        if (!els.panel || !clienteId) return;

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

        // Clic en miniatura → previsualización (lightbox), no descarga directa
        if (els.thread) {
            els.thread.addEventListener('click', function (ev) {
                const btn = ev.target.closest('.wa-msg-img[data-wa-preview]');
                if (!btn) return;
                ev.preventDefault();
                openLightbox(btn.getAttribute('data-wa-preview'), btn.getAttribute('data-wa-name') || 'imagen');
            });
        }

        ensureLightbox();

        await loadEstado();
        try {
            if (initialWa) {
                conversacionId = initialWa;
                const data = await apiGet('wa_conversacion_cliente', { cliente_id: clienteId });
                fillTelefonos(
                    data.telefonos || [],
                    data.conversacion && data.conversacion.telefono_e164
                );
                // Si deep-link wa=, preferir esa conversación
                const msgs = await apiGet('wa_mensajes', {
                    conversacion_id: conversacionId,
                    cliente_id: clienteId,
                });
                if (msgs.conversacion) {
                    updateWaActivo(msgs.conversacion.wa_activo);
                    // Seleccionar el teléfono de esa conversación
                    if (els.select && msgs.conversacion.telefono_e164) {
                        Array.from(els.select.options).forEach(function (o) {
                            if (o.dataset.e164 === msgs.conversacion.telefono_e164) o.selected = true;
                        });
                    }
                }
                appendMessages(msgs.mensajes || [], true);
            } else {
                await ensureConversacion(null);
                await loadMensajes(true);
            }
        } catch (e) {
            setError(e.message || 'No se pudo iniciar WhatsApp');
        }
        await loadBubbles();
        pollTimer = setInterval(tick, pollMs);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
