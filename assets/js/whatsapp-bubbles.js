/**
 * Burbujas WhatsApp globales (cualquier vista del asesor).
 * Máx 10 visibles + esfera +N para cola (dismissed / overflow).
 * Poll rápido (BD) cada 5s; sync Kommo ligero cada ~15s para alertar
 * aunque el asesor esté en OTRA ficha de cliente.
 */
(function () {
    'use strict';

    const ROLE = (window.__waGlobal && window.__waGlobal.role) || '';
    if (ROLE !== 'asesor') {
        return;
    }

    const POLL_MS = Number((window.__waGlobal && window.__waGlobal.pollMs) || 5000);
    const SYNC_EVERY = 3; // 3 * 5s = ~15s entre syncs Kommo
    let timer = null;
    let overflowOpen = false;
    let pollTick = 0;
    let lastUnreadMap = {};
    let alertArmed = false; // evita beep en el primer paint

    function escapeHtml(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function initials(name) {
        const p = String(name || '?').trim().split(/\s+/);
        if (!p.length) return '?';
        if (p.length === 1) return p[0].slice(0, 2).toUpperCase();
        return (p[0][0] + p[1][0]).toUpperCase();
    }

    async function apiGet(action, params) {
        const q = new URLSearchParams(Object.assign({ action: action }, params || {}));
        const res = await fetch('index.php?' + q.toString(), {
            credentials: 'same-origin',
            headers: { Accept: 'application/json' },
        });
        const data = await res.json().catch(function () { return {}; });
        if (!res.ok || data.success === false) {
            throw new Error(data.error || ('HTTP ' + res.status));
        }
        return data;
    }

    async function apiPost(action, body) {
        const res = await fetch('index.php?action=' + encodeURIComponent(action), {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
            body: JSON.stringify(body || {}),
        });
        const data = await res.json().catch(function () { return {}; });
        if (!res.ok || data.success === false) {
            throw new Error(data.error || ('HTTP ' + res.status));
        }
        return data;
    }

    function ensureRail() {
        let rail = document.getElementById('waBubbleRail');
        if (rail) return rail;
        rail = document.createElement('div');
        rail.id = 'waBubbleRail';
        rail.setAttribute('aria-label', 'Chats WhatsApp');
        document.body.appendChild(rail);
        return rail;
    }

    function ensureOverflowPanel() {
        let panel = document.getElementById('waOverflowPanel');
        if (panel) return panel;
        panel = document.createElement('div');
        panel.id = 'waOverflowPanel';
        panel.className = 'wa-overflow-panel';
        panel.hidden = true;
        panel.innerHTML = '<div class="wa-overflow-head">Cola WhatsApp</div><div class="wa-overflow-list" id="waOverflowList"></div>';
        document.body.appendChild(panel);
        return panel;
    }

    function playAlertBeep() {
        try {
            const Ctx = window.AudioContext || window.webkitAudioContext;
            if (!Ctx) return;
            const ctx = new Ctx();
            const o = ctx.createOscillator();
            const g = ctx.createGain();
            o.type = 'sine';
            o.frequency.value = 880;
            g.gain.value = 0.0001;
            o.connect(g);
            g.connect(ctx.destination);
            const now = ctx.currentTime;
            g.gain.exponentialRampToValueAtTime(0.12, now + 0.02);
            g.gain.exponentialRampToValueAtTime(0.0001, now + 0.28);
            o.start(now);
            o.stop(now + 0.3);
            setTimeout(function () { try { ctx.close(); } catch (e) {} }, 400);
        } catch (e) { /* ignore */ }
    }

    function detectNewUnread(chats) {
        let raised = false;
        const next = {};
        (chats || []).forEach(function (c) {
            const id = String(c.id || '');
            const n = Number(c.no_leidos || 0);
            next[id] = n;
            const prev = Number(lastUnreadMap[id] || 0);
            if (alertArmed && n > prev) {
                raised = true;
            }
        });
        lastUnreadMap = next;
        if (!alertArmed) {
            alertArmed = true;
            return false;
        }
        return raised;
    }

    function renderBubbles(data) {
        const rail = ensureRail();
        const chats = data.chats || [];
        const overflow = data.overflow || [];
        const overflowCount = Number(data.overflow_count || data.extra || 0);
        const hasNew = detectNewUnread(chats);

        rail.innerHTML = '';
        chats.forEach(function (c) {
            const a = document.createElement('a');
            a.className = 'wa-bubble';
            a.href = 'index.php?action=gestionar_cliente&id=' + encodeURIComponent(c.cliente_id || '') +
                '&wa=' + encodeURIComponent(c.id || '') + '&claim=1';
            a.title = (c.cliente_nombre || c.telefono_e164 || 'Chat') +
                (c.ultimo_preview ? '\n' + c.ultimo_preview : '');
            a.innerHTML = '<span class="wa-bubble-ini">' + escapeHtml(initials(c.cliente_nombre || c.telefono_e164)) + '</span>';
            const n = Number(c.no_leidos || 0);
            if (n > 0) {
                const badge = document.createElement('span');
                badge.className = 'wa-bubble-badge';
                badge.textContent = n > 99 ? '99+' : String(n);
                a.appendChild(badge);
                a.classList.add('wa-bubble--unread');
            }
            const close = document.createElement('button');
            close.type = 'button';
            close.className = 'wa-bubble-close';
            close.title = 'Ocultar';
            close.innerHTML = '&times;';
            close.addEventListener('click', function (ev) {
                ev.preventDefault();
                ev.stopPropagation();
                apiPost('wa_burbuja_dismiss', { conversacion_id: Number(c.id) })
                    .then(refresh)
                    .catch(function () { /* ignore */ });
            });
            a.appendChild(close);
            rail.appendChild(a);
        });

        if (overflowCount > 0) {
            const more = document.createElement('button');
            more.type = 'button';
            more.className = 'wa-bubble wa-bubble-more';
            more.textContent = '+' + overflowCount;
            more.title = 'Cola de chats WhatsApp';
            more.addEventListener('click', function () {
                overflowOpen = !overflowOpen;
                renderOverflow(overflow, overflowOpen);
            });
            rail.appendChild(more);
        } else {
            overflowOpen = false;
            const panel = document.getElementById('waOverflowPanel');
            if (panel) panel.hidden = true;
        }

        if (hasNew) {
            rail.classList.add('wa-bubble-rail--alert');
            playAlertBeep();
            setTimeout(function () {
                rail.classList.remove('wa-bubble-rail--alert');
            }, 1800);
            try {
                if (window.Notification && Notification.permission === 'granted') {
                    const unread = (chats || []).filter(function (c) { return Number(c.no_leidos || 0) > 0; })[0];
                    if (unread) {
                        new Notification('WhatsApp — mensaje nuevo', {
                            body: (unread.cliente_nombre || unread.telefono_e164 || 'Cliente') +
                                (unread.ultimo_preview ? ': ' + unread.ultimo_preview : ''),
                            tag: 'wa-bubble-' + unread.id,
                        });
                    }
                }
            } catch (e) { /* ignore */ }
        }
    }

    function renderOverflow(items, open) {
        const panel = ensureOverflowPanel();
        const list = document.getElementById('waOverflowList');
        if (!open) {
            panel.hidden = true;
            return;
        }
        panel.hidden = false;
        list.innerHTML = '';
        if (!items.length) {
            list.innerHTML = '<div class="wa-overflow-empty">Sin chats en cola</div>';
            return;
        }
        items.forEach(function (c) {
            const row = document.createElement('div');
            row.className = 'wa-overflow-item';
            const name = c.cliente_nombre || c.telefono_e164 || ('Chat #' + c.id);
            row.innerHTML =
                '<a href="index.php?action=gestionar_cliente&id=' + encodeURIComponent(c.cliente_id || '') +
                '&wa=' + encodeURIComponent(c.id || '') + '&claim=1">' +
                escapeHtml(name) +
                '</a>' +
                '<button type="button" data-id="' + Number(c.id) + '">Mostrar</button>';
            row.querySelector('button').addEventListener('click', function () {
                apiPost('wa_burbuja_restore', { conversacion_id: Number(c.id) })
                    .then(function () {
                        overflowOpen = false;
                        return refresh();
                    })
                    .catch(function (e) { alert(e.message || 'No se pudo restaurar'); });
            });
            list.appendChild(row);
        });
    }

    async function refresh(forceSync) {
        try {
            pollTick += 1;
            const doSync = forceSync === true || (pollTick % SYNC_EVERY === 0);
            const params = { limit: 10 };
            if (doSync) params.sync_bubbles = 1;
            const data = await apiGet('wa_mis_chats', params);
            renderBubbles(data);
            if (overflowOpen) {
                renderOverflow(data.overflow || [], true);
            }
            if (data.sync && window.__waDebugBubbles) {
                console.debug('[wa-bubbles] sync', data.sync);
            }
        } catch (e) {
            /* silencioso en poll */
        }
    }

    function init() {
        // Retrasar burbujas para no competir con softphone / primera pintura
        setTimeout(function () {
            ensureRail();
            ensureOverflowPanel();
            // Primer tick con sync para no perder mensajes mientras se cargaba la ficha
            refresh(true);
            timer = setInterval(function () { refresh(false); }, POLL_MS);
            try {
                if (window.Notification && Notification.permission === 'default') {
                    Notification.requestPermission().catch(function () {});
                }
            } catch (e) { /* ignore */ }
        }, 400);
        document.addEventListener('click', function (ev) {
            const panel = document.getElementById('waOverflowPanel');
            if (!panel || panel.hidden) return;
            if (ev.target.closest('#waOverflowPanel') || ev.target.closest('.wa-bubble-more')) return;
            overflowOpen = false;
            panel.hidden = true;
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Exponer refresh para el panel de ficha
    window.__waBubblesRefresh = function () { return refresh(true); };
})();
