/**
 * Badge de notificaciones WhatsApp en el navbar del coordinador/admin.
 * Cuenta chats sin_cliente pendientes en el inbox (sin amarrar a cédula/base).
 */
(function () {
    const cfg = window.__waCoordNav || {};
    const pollMs = Math.max(8000, Number(cfg.pollMs || 15000));
    const syncEvery = Math.max(1, Number(cfg.syncEvery || 2));
    let tick = 0;
    let lastCount = null;

    function badgeEl() {
        return document.getElementById('waCoordNavBadge');
    }

    function render(count) {
        const el = badgeEl();
        if (!el) return;
        const n = Math.max(0, Number(count || 0));
        if (n <= 0) {
            el.hidden = true;
            el.setAttribute('aria-hidden', 'true');
            el.textContent = '0';
        } else {
            el.hidden = false;
            el.setAttribute('aria-hidden', 'false');
            el.textContent = n > 99 ? '99+' : String(n);
            el.title = n === 1
                ? '1 chat WhatsApp nuevo sin cédula'
                : n + ' chats WhatsApp nuevos sin cédula';
        }
        if (lastCount !== null && n > lastCount) {
            try {
                if (typeof Notification !== 'undefined' && Notification.permission === 'granted') {
                    new Notification('WhatsApp pendiente', {
                        body: n + ' chat(s) nuevos sin cédula en el inbox del coordinador',
                        tag: 'wa-coord-notif',
                    });
                }
            } catch (e) { /* ignore */ }
        }
        lastCount = n;
    }

    async function poll() {
        tick++;
        const sync = tick === 1 || (tick % syncEvery === 0) ? 1 : 0;
        try {
            const url = 'index.php?action=wa_coord_notif&sync=' + sync;
            const res = await fetch(url, {
                credentials: 'same-origin',
                headers: { Accept: 'application/json' },
            });
            const data = await res.json().catch(function () { return {}; });
            if (!res.ok || data.success === false) {
                return;
            }
            render(data.pendientes || 0);
        } catch (e) {
            // Silencioso: no romper navegación si falla el poll
        }
    }

    function boot() {
        if (!badgeEl()) return;
        poll();
        setInterval(poll, pollMs);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }

    // Si la vista de WhatsApp actualiza el inbox, refrescar badge enseguida.
    window.addEventListener('wa-coord-inbox-updated', function (ev) {
        const n = ev && ev.detail ? ev.detail.pendientes : null;
        if (n != null) {
            render(n);
        } else {
            poll();
        }
    });
})();
