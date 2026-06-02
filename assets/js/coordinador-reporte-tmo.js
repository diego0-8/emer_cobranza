/**
 * Monitoreo en tiempo real del estado de asesores (Reporte TMO - coordinador)
 */
(function () {
    'use strict';

    const POLL_INTERVAL_MS = 15000;
    const API_URL = 'index.php?action=obtener_estado_tiempo_asesores';
    const API_DETALLE_URL = 'index.php?action=obtener_detalle_pausas_asesor_tmo';

    let lastRefreshAt = Date.now();
    let pollTimer = null;
    let pauseTimerInterval = null;
    let modalDetalleInstance = null;

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

    function formatearDuracion(seg) {
        const s = Math.max(0, Math.floor(Number(seg) || 0));
        const h = Math.floor(s / 3600);
        const m = Math.floor((s % 3600) / 60);
        const r = s % 60;
        return (
            String(h).padStart(2, '0') +
            ':' +
            String(m).padStart(2, '0') +
            ':' +
            String(r).padStart(2, '0')
        );
    }

    function segundosDesde(datetimeStr) {
        if (!datetimeStr) return 0;
        const d = new Date(datetimeStr.replace(' ', 'T'));
        if (isNaN(d.getTime())) return 0;
        return Math.max(0, Math.floor((Date.now() - d.getTime()) / 1000));
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

    function celdaTiempoPausaHtml(row) {
        if (row.estado !== 'en_pausa' || !row.pausa_desde) {
            return '—';
        }
        const segIni =
            row.pausa_duracion_segundos != null
                ? Number(row.pausa_duracion_segundos)
                : segundosDesde(row.pausa_desde);
        return (
            '<span class="tmo-pausa-timer" data-pausa-desde="' +
            escapeHtml(row.pausa_desde) +
            '">' +
            escapeHtml(formatearDuracion(segIni)) +
            '</span>'
        );
    }

    function filaAsesorActivoHtml(row) {
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
            '<td class="col-tiempo-pausa">' +
            celdaTiempoPausaHtml(row) +
            '</td>' +
            '<td class="col-detalles">' +
            '<button type="button" class="btn btn-sm btn-outline-primary btn-ver-pausas" data-cedula="' +
            escapeHtml(row.cedula) +
            '" data-nombre="' +
            escapeHtml(row.nombre) +
            '">Ver</button>' +
            '</td>' +
            '</tr>'
        );
    }

    function filaAsesorOfflineHtml(row) {
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

    function renderCuerpoTabla(tbody, filas, mensajeVacio, colspan, renderFila) {
        if (!tbody) return;
        if (!filas || filas.length === 0) {
            tbody.innerHTML =
                '<tr><td colspan="' +
                colspan +
                '" class="text-center text-muted py-4">' +
                escapeHtml(mensajeVacio) +
                '</td></tr>';
            return;
        }
        tbody.innerHTML = filas.map(renderFila).join('');
    }

    function actualizarTimersPausa() {
        const timers = document.querySelectorAll('.tmo-pausa-timer[data-pausa-desde]');
        if (!timers.length) {
            if (pauseTimerInterval) {
                clearInterval(pauseTimerInterval);
                pauseTimerInterval = null;
            }
            return;
        }
        timers.forEach(function (el) {
            const desde = el.getAttribute('data-pausa-desde');
            el.textContent = formatearDuracion(segundosDesde(desde));
        });
        if (!pauseTimerInterval) {
            pauseTimerInterval = setInterval(actualizarTimersPausa, 1000);
        }
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
            'Ningún asesor en línea o en pausa en este momento.',
            6,
            filaAsesorActivoHtml
        );
        renderCuerpoTabla(
            document.getElementById('tablaEstadoAsesoresOffline'),
            grupos.offline,
            'No hay asesores offline.',
            4,
            filaAsesorOfflineHtml
        );
        actualizarTimersPausa();
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

    function getModalDetalle() {
        const el = document.getElementById('modalDetallePausasAsesor');
        if (!el || typeof bootstrap === 'undefined') return null;
        if (!modalDetalleInstance) {
            modalDetalleInstance = new bootstrap.Modal(el);
        }
        return modalDetalleInstance;
    }

    function renderModalDetalle(data) {
        const body = document.getElementById('modalDetallePausasBody');
        const titulo = document.getElementById('modalDetallePausasTitulo');
        if (!body) return;

        const asesor = data.asesor || {};
        const jornada = data.jornada;
        const pausas = data.pausas || [];

        if (titulo) {
            titulo.textContent = 'Pausas del día — ' + (asesor.nombre || asesor.cedula || 'Asesor');
        }

        let html = '';

        if (jornada && jornada.hora_inicio) {
            const finJornada = jornada.hora_fin
                ? formatearHora(jornada.hora_fin)
                : '<span class="text-success">En curso</span>';
            html +=
                '<dl class="row modal-jornada mb-0">' +
                '<dt class="col-sm-4">Inicio de sesión (hoy)</dt>' +
                '<dd class="col-sm-8">' +
                escapeHtml(formatearHora(jornada.hora_inicio)) +
                '</dd>' +
                '<dt class="col-sm-4">Fin de sesión</dt>' +
                '<dd class="col-sm-8">' +
                finJornada +
                '</dd>' +
                '</dl>';
        } else {
            html +=
                '<div class="alert alert-warning mb-3">No hay registro de jornada (login) para hoy.</div>';
        }

        if (!pausas.length) {
            html += '<p class="text-muted mb-0">No hay pausas registradas hoy.</p>';
        } else {
            html +=
                '<div class="table-responsive"><table class="table table-sm table-striped tabla-pausas-dia mb-0">' +
                '<thead><tr><th>Motivo</th><th>Inicio</th><th>Fin</th><th>Duración</th></tr></thead><tbody>';
            pausas.forEach(function (p) {
                const enCurso = !p.hora_fin || p.estado === 'activa';
                const finTxt = enCurso
                    ? '<span class="text-warning">En curso</span>'
                    : escapeHtml(formatearHora(p.hora_fin));
                const durClass = enCurso ? ' tmo-modal-pausa-activa' : '';
                const durAttr = enCurso && p.hora_inicio
                    ? ' data-pausa-desde="' + escapeHtml(p.hora_inicio) + '"'
                    : '';
                html +=
                    '<tr>' +
                    '<td>' +
                    escapeHtml(p.tipo_label || p.tipo_registro) +
                    '</td>' +
                    '<td>' +
                    escapeHtml(formatearHora(p.hora_inicio)) +
                    '</td>' +
                    '<td>' +
                    finTxt +
                    '</td>' +
                    '<td class="' +
                    durClass.trim() +
                    '"><span class="tmo-pausa-timer' +
                    durClass +
                    '"' +
                    durAttr +
                    '>' +
                    escapeHtml(p.duracion_fmt || formatearDuracion(p.duracion_segundos)) +
                    '</span></td>' +
                    '</tr>';
            });
            html += '</tbody></table></div>';
        }

        body.innerHTML = html;
        actualizarTimersPausa();
    }

    function abrirDetallePausas(cedula, nombre) {
        const modal = getModalDetalle();
        const body = document.getElementById('modalDetallePausasBody');
        const titulo = document.getElementById('modalDetallePausasTitulo');

        if (titulo) {
            titulo.textContent = 'Detalle — ' + (nombre || cedula);
        }
        if (body) {
            body.innerHTML =
                '<div class="text-center text-muted py-4"><i class="fas fa-spinner fa-spin"></i> Cargando...</div>';
        }
        if (modal) modal.show();

        const url =
            API_DETALLE_URL +
            '&cedula=' +
            encodeURIComponent(cedula);

        fetch(url, {
            method: 'GET',
            credentials: 'same-origin',
            headers: { Accept: 'application/json' },
        })
            .then(function (res) {
                if (!res.ok) throw new Error('HTTP ' + res.status);
                return res.json();
            })
            .then(function (data) {
                if (data && data.success) {
                    renderModalDetalle(data);
                } else {
                    if (body) {
                        body.innerHTML =
                            '<div class="alert alert-danger mb-0">' +
                            escapeHtml(data.message || 'No se pudo cargar el detalle.') +
                            '</div>';
                    }
                }
            })
            .catch(function (err) {
                console.error('Error detalle pausas TMO:', err);
                if (body) {
                    body.innerHTML =
                        '<div class="alert alert-danger mb-0">Error al cargar el detalle. Intenta de nuevo.</div>';
                }
            });
    }

    function initModalDetalle() {
        const tbody = document.getElementById('tablaEstadoAsesoresActivos');
        if (!tbody) return;

        tbody.addEventListener('click', function (e) {
            const btn = e.target.closest('.btn-ver-pausas');
            if (!btn) return;
            e.preventDefault();
            const cedula = btn.getAttribute('data-cedula');
            const nombre = btn.getAttribute('data-nombre');
            if (!cedula) return;
            abrirDetallePausas(cedula, nombre);
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        initDesplegableOffline();
        initModalDetalle();
        if (window.TMO_ESTADO_INICIAL) {
            aplicarDatos(window.TMO_ESTADO_INICIAL);
        }
        fetchEstado();
        iniciarPolling();
    });
})();
