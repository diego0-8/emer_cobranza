/**
 * Monitoreo en tiempo real del estado de asesores (Reporte TMO - coordinador)
 */
(function () {
    'use strict';

    const POLL_INTERVAL_MS = 15000;
    const API_URL = 'index.php?action=obtener_estado_tiempo_asesores';

    let lastRefreshAt = Date.now();
    let pollTimer = null;

    function esAsesorActivo(estado) {
        return estado === 'en_linea' || estado === 'en_pausa';
    }

    function dividirAsesores(asesores) {
        const activos = [];
        const offline = [];
        (asesores || []).forEach(function (row) {
            if (esAsesorActivo(row.estado)) {
                activos.push(row);
            } else {
                offline.push(row);
            }
        });
        return { activos: activos, offline: offline };
    }

    function formatearHora(datetimeStr) {
        if (!datetimeStr) {
            return '—';
        }
        const d = new Date(datetimeStr.replace(' ', 'T'));
        if (isNaN(d.getTime())) {
            return '—';
        }
        return d.toLocaleTimeString('es-CO', {
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: false,
        });
    }

    function claseBadge(estado) {
        const map = {
            en_linea: 'badge-estado badge-en-linea',
            en_pausa: 'badge-estado badge-en-pausa',
            offline: 'badge-estado badge-offline',
        };
        return map[estado] || 'badge-estado badge-offline';
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text == null ? '' : String(text);
        return div.innerHTML;
    }

    function filaAsesorHtml(row) {
        const estado = row.estado || 'offline';
        const label = row.estado_label || 'Offline';
        return (
            '<tr data-cedula="' +
            escapeHtml(row.cedula) +
            '">' +
            '<td><strong>' +
            escapeHtml(row.nombre) +
            '</strong></td>' +
            '<td>' +
            escapeHtml(formatearHora(row.inicio_sesion)) +
            '</td>' +
            '<td>' +
            escapeHtml(formatearHora(row.fin_sesion)) +
            '</td>' +
            '<td><span class="' +
            claseBadge(estado) +
            '">' +
            escapeHtml(label) +
            '</span></td>' +
            '</tr>'
        );
    }

    function renderCuerpoTabla(tbody, filas, mensajeVacio) {
        if (!tbody) return;
        if (!filas || filas.length === 0) {
            tbody.innerHTML =
                '<tr><td colspan="4" class="text-center text-muted py-4">' +
                escapeHtml(mensajeVacio) +
                '</td></tr>';
            return;
        }
        tbody.innerHTML = filas.map(filaAsesorHtml).join('');
    }

    function actualizarContadores(contadores) {
        if (!contadores) return;
        const elLinea = document.getElementById('statEnLinea');
        const elPausa = document.getElementById('statEnPausa');
        const elOffline = document.getElementById('statOffline');
        if (elLinea) elLinea.textContent = contadores.en_linea ?? 0;
        if (elPausa) elPausa.textContent = contadores.en_pausa ?? 0;
        if (elOffline) elOffline.textContent = contadores.offline ?? 0;

        const elOfflineCount = document.getElementById('offlineAsesoresCount');
        if (elOfflineCount) elOfflineCount.textContent = contadores.offline ?? 0;
    }

    function actualizarIndicadorRefresh() {
        const hint = document.getElementById('liveRefreshHint');
        if (!hint) return;
        const seg = Math.max(0, Math.floor((Date.now() - lastRefreshAt) / 1000));
        hint.innerHTML = '<i class="fas fa-sync-alt"></i> Actualizado hace ' + seg + ' s';
    }

    function renderTablaEstado(asesores) {
        const grupos = dividirAsesores(asesores);
        renderCuerpoTabla(
            document.getElementById('tablaEstadoAsesoresActivos'),
            grupos.activos,
            'Ningún asesor en línea o en pausa en este momento.'
        );
        renderCuerpoTabla(
            document.getElementById('tablaEstadoAsesoresOffline'),
            grupos.offline,
            'No hay asesores offline.'
        );
    }

    function aplicarDatos(payload) {
        if (!payload) return;
        renderTablaEstado(payload.asesores || []);
        actualizarContadores(payload.contadores || {});
        lastRefreshAt = Date.now();
        actualizarIndicadorRefresh();
    }

    function fetchEstado() {
        return fetch(API_URL, {
            method: 'GET',
            credentials: 'same-origin',
            headers: { Accept: 'application/json' },
        })
            .then(function (res) {
                if (!res.ok) {
                    throw new Error('HTTP ' + res.status);
                }
                return res.json();
            })
            .then(function (data) {
                if (data && data.success) {
                    aplicarDatos(data);
                } else {
                    console.warn('Respuesta TMO sin éxito:', data);
                }
            })
            .catch(function (err) {
                console.error('Error al actualizar estado TMO:', err);
            });
    }

    function iniciarPolling() {
        if (pollTimer) {
            clearInterval(pollTimer);
        }
        pollTimer = setInterval(fetchEstado, POLL_INTERVAL_MS);
        setInterval(actualizarIndicadorRefresh, 1000);
    }

    /**
     * Desplegable de asesores offline (sin alterar la tabla superior).
     */
    function initDesplegableOffline() {
        const btn = document.getElementById('btnToggleOfflineAsesores');
        const panel = document.getElementById('offlineAsesoresCollapse');
        if (!btn || !panel) return;

        panel.classList.remove('show');
        btn.setAttribute('aria-expanded', 'false');

        const labelSpan = btn.querySelector('span');

        btn.addEventListener('click', function () {
            const abierto = panel.classList.toggle('show');
            btn.setAttribute('aria-expanded', abierto ? 'true' : 'false');
            if (labelSpan) {
                labelSpan.innerHTML = abierto
                    ? '<i class="fas fa-user-slash"></i> Ocultar asesores offline'
                    : '<i class="fas fa-user-slash"></i> Ver asesores offline';
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        initDesplegableOffline();
        if (window.TMO_ESTADO_INICIAL) {
            aplicarDatos(window.TMO_ESTADO_INICIAL);
        }
        fetchEstado();
        iniciarPolling();
    });
})();
