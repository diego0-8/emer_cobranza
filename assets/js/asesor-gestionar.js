/**
 * `assets/js/asesor-gestionar.js`
 *
 * Versión alineada con `views/gestionar_cliente.php`.
 *
 * Objetivo:
 * - Proporcionar funciones auxiliares para la gestión de clientes.
 * - Al cambiar de cliente, siempre se recarga la página.
 *
 * NOTA:
 * La función `cambiarClienteSinRecargar` ahora recarga la página para mantener consistencia.
 */

(function () {
  'use strict';

  const logPrefix = '[asesor-gestionar]';

  function getUrlParams() {
    return new URLSearchParams(window.location.search);
  }

  function getCurrentClienteId() {
    const p = getUrlParams();
    return p.get('id') || p.get('cliente_id');
  }

  function escapeHtml(s) {
    return String(s ?? '')
      .replaceAll('&', '&amp;')
      .replaceAll('<', '&lt;')
      .replaceAll('>', '&gt;')
      .replaceAll('"', '&quot;')
      .replaceAll("'", '&#039;');
  }

  async function fetchJson(url, opts = {}) {
    const res = await fetch(url, {
      credentials: 'same-origin',
      headers: { Accept: 'application/json', ...(opts.headers || {}) },
      ...opts,
    });

    const ct = (res.headers.get('content-type') || '').toLowerCase();
    const text = await res.text();

    if (!res.ok) {
      throw new Error(`HTTP ${res.status} ${res.statusText} en ${url}`);
    }

    // Intentar parsear JSON aunque el Content-Type no sea application/json
    // (algunos servidores envían text/html pero con contenido JSON válido)
    let jsonData = null;
    try {
      jsonData = JSON.parse(text);
      // Si el parseo fue exitoso, retornar los datos aunque el Content-Type no sea correcto
      return jsonData;
    } catch (parseError) {
      // Si falla el parseo, verificar si el Content-Type es correcto
      if (!ct.includes('application/json')) {
        // Verificar si parece ser HTML (página de error/login)
        if (text.trim().startsWith('<!DOCTYPE') || text.trim().startsWith('<html')) {
          const preview = text.slice(0, 200).replace(/\s+/g, ' ').trim();
          throw new Error(`Respuesta HTML en ${url}. Content-Type=${ct || 'N/A'}. Preview="${preview}"`);
        }
        // Si no es HTML pero tampoco es JSON válido, lanzar error
        const preview = text.slice(0, 200).replace(/\s+/g, ' ').trim();
        throw new Error(`Respuesta no-JSON en ${url}. Content-Type=${ct || 'N/A'}. Preview="${preview}"`);
      }
      // Si el Content-Type es JSON pero el parseo falló, lanzar error de JSON inválido
      const preview = text.slice(0, 200).replace(/\s+/g, ' ').trim();
      throw new Error(`JSON inválido en ${url}. Preview="${preview}"`);
    }
  }

  function setText(id, value) {
    const el = document.getElementById(id);
    if (!el) return;
    el.textContent = value ?? '';
  }

  function setValue(id, value) {
    const el = document.getElementById(id);
    if (!el) return;
    el.value = value ?? '';
  }

  function renderTelefonos(cliente) {
    if (!cliente) {
      console.warn(logPrefix, 'cliente es nulo o indefinido en renderTelefonos');
      return;
    }
    // IDs correctos según la vista gestionar_cliente.php
    const select = document.getElementById('telefonoDropdown');
    const display = document.getElementById('telefonoSeleccionado');

    if (!select) {
      console.warn(logPrefix, '⚠️ telefonoDropdown no encontrado');
      return;
    }

    if (!display) {
      console.warn(logPrefix, '⚠️ telefonoSeleccionado no encontrado');
    }

    // Incluir todos los campos de teléfono disponibles
    const telefonos = [];

    // Campo principal (telefono)
    if (cliente.telefono && cliente.telefono.trim() !== '') {
      telefonos.push({ num: cliente.telefono.trim(), tipo: 'Teléfono' });
    }

    // Campos adicionales (celular2, cel3, cel4, etc. hasta cel11)
    const camposCelular = ['celular2', 'cel3', 'cel4', 'cel5', 'cel6', 'cel7', 'cel8', 'cel9', 'cel10', 'cel11'];
    camposCelular.forEach((campo, index) => {
      if (cliente[campo] && cliente[campo].trim() !== '') {
        const numeroCelular = index + 2; // celular2 = Celular 2, cel3 = Celular 3, etc.
        telefonos.push({ num: cliente[campo].trim(), tipo: `Celular ${numeroCelular}` });
      }
    });

    // Limpiar y poblar el select
    select.innerHTML = '';

    if (telefonos.length === 0) {
      const opt = document.createElement('option');
      opt.value = '';
      opt.textContent = 'N/A';
      select.appendChild(opt);
      if (display) {
        display.textContent = 'N/A';
      }
      return;
    }

    telefonos.forEach((t, idx) => {
      const opt = document.createElement('option');
      opt.value = t.num;
      opt.dataset.tipo = t.tipo;
      opt.textContent = t.num; // Solo el número, sin el tipo entre paréntesis (como en la vista PHP)
      if (idx === 0) opt.selected = true;
      select.appendChild(opt);
    });

    // Actualizar el display (es un span, no un input)
    if (display) {
      display.textContent = telefonos[0]?.num || '';
    }

    // Log para debugging
    if (window.console && window.console.log) {
      console.log('📞 [renderTelefonos] Teléfonos renderizados:', telefonos.length, telefonos);
    }
  }

  function renderObligaciones(obligaciones) {
    // Actualizar el panel de facturas (facturasListaPanel)
    const panelFacturas = document.getElementById('facturasListaPanel');

    // Actualizar el dropdown de facturas a gestionar (#factura_gestionar)
    const selectFactura = document.getElementById('factura_gestionar');

    if (!Array.isArray(obligaciones)) obligaciones = [];

    // Actualizar dropdown de facturas (#factura_gestionar)
    if (selectFactura) {
      const prevValue = selectFactura.value;
      selectFactura.innerHTML = '<option value="">Selecciona una factura</option><option value="ninguna">❌ Ninguna (Cliente no quiere pagar)</option>';

      if (obligaciones.length > 0) {
        // Calcular total de facturas pendientes
        const facturasPendientes = obligaciones.filter(f => (f.estado_factura || f.estado || 'pendiente') === 'pendiente');
        const totalFacturasPendientes = facturasPendientes.length;
        let totalSaldoPendiente = 0;
        facturasPendientes.forEach(f => {
          totalSaldoPendiente += Number(f.saldo || f.saldo_k_obligacion || 0);
        });

        // Agregar opción "Todas las facturas" si hay 2 o más pendientes
        if (totalFacturasPendientes >= 2) {
          const optTodas = document.createElement('option');
          optTodas.value = 'todas_las_facturas';
          optTodas.dataset.numero = 'TODAS LAS FACTURAS';
          optTodas.dataset.saldo = totalSaldoPendiente;
          optTodas.dataset.estado = 'todas';
          optTodas.dataset.facturasIds = facturasPendientes.map(f => f.id || f.obligacion_id).join(',');
          optTodas.textContent = `💰 Todas las facturas (${totalFacturasPendientes} facturas) - Total: $${Number(totalSaldoPendiente).toLocaleString('es-CO')}`;
          selectFactura.appendChild(optTodas);
        }

        // Agregar cada factura individual
        obligaciones.forEach((factura, index) => {
          const opt = document.createElement('option');
          opt.value = factura.id || factura.obligacion_id || '';
          opt.dataset.numero = factura.numero_factura || factura.obligacion || 'N/A';
          opt.dataset.saldo = factura.saldo || factura.saldo_k_obligacion || 0;
          opt.dataset.estado = factura.estado_factura || factura.estado || 'pendiente';
          const saldo = Number(factura.saldo || factura.saldo_k_obligacion || 0);
          opt.textContent = `📄 Factura #${index + 1} - ${escapeHtml(factura.numero_factura || factura.obligacion || 'N/A')} (Saldo: $${saldo.toLocaleString('es-CO')})`;
          selectFactura.appendChild(opt);
        });
      }

      // Restaurar selección anterior si existe
      if (prevValue && [...selectFactura.options].some(op => op.value === prevValue)) {
        selectFactura.value = prevValue;
      }
    }

    // Actualizar panel de facturas (facturasListaPanel)
    if (panelFacturas) {
      if (obligaciones.length === 0) {
        panelFacturas.innerHTML = `
          <div style="text-align: center; padding: 20px; color: #7f8c8d;">
            <i class="fas fa-file-invoice" style="font-size: 24px; margin-bottom: 10px;"></i>
            <p style="margin: 0; font-size: 12px;">No se encontraron facturas para este cliente.</p>
          </div>
        `;
        return;
      }

      panelFacturas.innerHTML = obligaciones
        .map((factura, index) => {
          const numeroFactura = factura.numero_factura || factura.obligacion || 'N/A';
          const saldo = Number(factura.saldo || factura.saldo_k_obligacion || 0);
          const estadoFactura = factura.estado_factura || factura.estado || 'pendiente';
          const diasMora = factura.dias_mora || null;
          const numeroContrato = factura.numero_contrato || null;
          const rmt = factura.rmt || null;
          const franja = factura.franja || null;
          const fechaCreacion = factura.fecha_creacion || null;

          // Determinar clase de mora
          let claseMora = '';
          if (diasMora) {
            if (diasMora > 30) claseMora = 'mora-alta';
            else if (diasMora > 15) claseMora = 'mora-media';
            else claseMora = 'mora-baja';
          }

          // Determinar estilo de estado
          let estiloEstado = '';
          let colorEstado = '';
          if (estadoFactura === 'pendiente') {
            estiloEstado = 'background: #d4edda; color: #155724;';
          } else if (estadoFactura === 'pagada') {
            estiloEstado = 'background: #d1ecf1; color: #0c5460;';
          } else {
            estiloEstado = 'background: #fff3cd; color: #856404;';
          }

          // Determinar estilo de franja
          let estiloFranja = '';
          if (franja) {
            const franjaUpper = franja.toUpperCase();
            if (franjaUpper === 'BLOQUEADO') estiloFranja = '#dc3545';
            else if (franjaUpper === 'ESPERA') estiloFranja = '#ffc107';
            else if (franjaUpper === 'ACTIVO') estiloFranja = '#28a745';
            else if (franjaUpper === 'SUSPENDIDO') estiloFranja = '#6c757d';
            else estiloFranja = '#17a2b8';
          }

          return `
            <div class="factura-item" style="border: 1px solid #e0e0e0; border-radius: 8px; padding: 12px; margin-bottom: 10px; background: #f8f9fa;">
              <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                <h5 style="margin: 0; color: #2c3e50; font-size: 13px;">
                  <i class="fas fa-file-invoice"></i> Factura #${index + 1}
                </h5>
                <span class="badge" style="font-size: 10px; padding: 4px 8px; border-radius: 12px; ${estiloEstado}">
                  ${escapeHtml(estadoFactura.charAt(0).toUpperCase() + estadoFactura.slice(1))}
                </span>
              </div>
              
              <div class="datos-factura-lista" style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; font-size: 11px;">
                <div class="dato-item">
                  <strong>Número:</strong><br>
                  ${escapeHtml(numeroFactura)}
                </div>
                
                <div class="dato-item">
                  <strong>Saldo:</strong><br>
                  $${saldo.toLocaleString('es-CO')}
                </div>
                
                ${diasMora ? `
                <div class="dato-item ${claseMora}">
                  <strong>Días en Mora:</strong><br>
                  ${diasMora} días
                </div>
                ` : ''}
                
                ${numeroContrato ? `
                <div class="dato-item">
                  <strong>Número Contrato:</strong><br>
                  ${escapeHtml(numeroContrato)}
                </div>
                ` : ''}
                
                ${rmt ? `
                <div class="dato-item">
                  <strong>RMT:</strong><br>
                  ${escapeHtml(rmt)}
                </div>
                ` : ''}
                
                ${franja ? `
                <div class="dato-item franja-${franja.toLowerCase().replace(/\s+/g, '-')}">
                  <strong>Franja:</strong><br>
                  <span class="franja-badge" style="display: inline-block; padding: 2px 6px; border-radius: 4px; font-size: 10px; font-weight: 600; text-transform: uppercase; background: ${estiloFranja}; color: white;">
                    ${escapeHtml(franja)}
                  </span>
                </div>
                ` : ''}
                
                ${fechaCreacion ? `
                <div class="dato-item">
                  <strong>Fecha Creación:</strong><br>
                  ${new Date(fechaCreacion).toLocaleDateString('es-CO', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' })}
                </div>
                ` : ''}
              </div>
            </div>
          `;
        })
        .join('');
    }
  }

  function renderHistorialMini(historial) {
    const panel = document.getElementById('historialMiniPanel');
    if (!panel) return;
    if (!Array.isArray(historial)) historial = [];

    if (historial.length === 0) {
      panel.innerHTML = `
        <div style="text-align:center; padding: 12px; color:#7f8c8d; background:#f8f9fa; border-radius: 6px;">
          <i class="fas fa-info-circle" style="font-size:14px; margin-bottom:5px;"></i>
          <div style="font-size:11px;">No hay gestiones registradas para este cliente.</div>
        </div>
      `;
      return;
    }

    panel.innerHTML = historial
      .slice(0, 5)
      .map((g) => {
        const fecha = g.fecha_gestion || '';
        const res = g.resultado || 'Sin resultado';
        const tipo = g.tipo_gestion || 'N/A';
        return `
          <div style="background:#f8f9fa; border: 1px solid #e9ecef; border-radius: 6px; padding: 10px; margin-bottom: 8px;">
            <div style="display:flex; justify-content:space-between; gap:10px; align-items:flex-start; margin-bottom:5px;">
              <div style="font-size: 11px; color:#6c757d;">
                <i class="fas fa-calendar-alt"></i> ${escapeHtml(fecha)}
              </div>
              <div style="font-size: 10px; color:#28a745; font-weight: 600;">
                ${escapeHtml(res)}
              </div>
            </div>
            <div style="font-size: 10px; color:#495057;">
              <strong>Tipo:</strong> ${escapeHtml(tipo)}
            </div>
          </div>
        `;
      })
      .join('');
  }

  function renderHistorialLlamadas(historial) {
    const container = document.getElementById('historialLlamadasLista');
    if (!container) {
      console.warn(logPrefix, '⚠️ Contenedor historialLlamadasLista no encontrado');
      return;
    }
    if (!Array.isArray(historial)) historial = [];

    // Actualizar el título del historial con el conteo
    const historialTitle = document.querySelector('.historial-title');
    if (historialTitle) {
      historialTitle.innerHTML = `
        <i class="fas fa-history"></i> 
        Historial de Interacciones (${historial.length} registros)
      `;
    }

    if (historial.length === 0) {
      container.innerHTML = `
        <div class="alert alert-info">
          <i class="fas fa-info-circle"></i>
          <strong>Sin historial:</strong> Este cliente no tiene gestiones registradas aún.
        </div>
      `;
      return;
    }

    // Mapeo de canales de contacto
    const canalesMap = {
      'llamada': '📞 Llamada Telefónica',
      'whatsapp': '📱 WhatsApp',
      'email': '📧 Correo Electrónico',
      'correo_electronico': '📧 Correo Electrónico',
      'sms': '💬 SMS',
      'correo_fisico': '📮 Correo Físico',
      'mensajeria_aplicaciones': '📱 Mensajería por Aplicaciones',
      'chat': '💬 Chat en Línea'
    };

    // Mapeo de tipificaciones generales
    const tipificacionGeneralMap = {
      'contacto_exitoso': '✅ CONTACTO EXITOSO',
      'contacto_tercero': '👥 CONTACTO CON TERCERO',
      'sin_contacto': '❌ SIN CONTACTO',
      'acuerdo_pago': '💰 ACUERDO DE PAGO',
      'ya_pago': '✅ YA PAGO',
      'localizado_sin_acuerdo': '📍 LOCALIZADO SIN ACUERDO',
      'reclamo': '📋 RECLAMO',
      'volver_llamar': '📞 VOLVER A LLAMAR',
      'recordar_pago': '⏰ RECORDAR PAGO',
      'venta_novedad': '🆕 VENTA CON NOVEDAD'
    };

    // Mapeo de razones específicas
    const razonEspecificaMap = {
      'acuerdo_pago': '💰 ACUERDO DE PAGO',
      'ya_pago': '✅ YA PAGO',
      'localizado_sin_acuerdo': '📍 LOCALIZADO SIN ACUERDO',
      'reclamo': '📋 RECLAMO',
      'volver_llamar': '📞 VOLVER A LLAMAR',
      'recordar_pago': '⏰ RECORDAR PAGO',
      'venta_novedad': '🆕 VENTA CON NOVEDAD',
      'desempleo': '💼 DESEMPLEO',
      'incremento_tarifa': '📈 INCREMENTO DE TARIFA',
      'otras_prioridades_economicas': '💰 TIENE OTRAS PRIORIDADES ECONOMICAS',
      'disminucion_ingresos': '📉 DISMINUCION DE INGRESOS',
      'no_contesta': '📞 NO CONTESTA',
      'mensaje_tercero': '📝 MENSAJE CON TERCERO',
      'no_informa': '❌ NO INFORMA',
      'contesta_cuelga': '📞 CONTESTA-CUELGA',
      'aqui_no_vive': '🏠 AQUÍ NO VIVE',
      'fallecido_otro': '💀 FALLECIDO/OTRO',
      'localizacion': '📍 LOCALIZACIÓN',
      'envio_estado_cuenta': '📧 ENVÍO DE ESTADO DE CUENTA',
      'venta_novedad_analisis': '🆕 VENTA CON NOVEDAD ANÁLISIS DATA'
    };

    // Función para formatear valores monetarios
    const formatearPesos = (valor) => {
      if (!valor || valor === 0) return 'N/A';
      return '$' + Number(valor).toLocaleString('es-CO');
    };

    // Función para formatear fecha
    const formatearFecha = (fecha) => {
      if (!fecha) return 'N/A';
      try {
        const fechaObj = new Date(fecha);
        if (!isNaN(fechaObj.getTime())) {
          return fechaObj.toLocaleDateString('es-CO', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
          });
        }
      } catch (e) {
        return fecha;
      }
      return fecha;
    };

    // Generar tarjetas de historial (igual que la vista PHP)
    container.innerHTML = historial
      .map((g) => {
        const fechaGestion = formatearFecha(g.fecha_gestion);
        const canalContacto = canalesMap[g.forma_contacto] || canalesMap[g.forma_contacto?.toLowerCase()] || '📞 Llamada Telefónica';
        const tipificacionGeneral = tipificacionGeneralMap[g.tipo_gestion] || g.tipo_gestion || 'No especificada';
        const razonEspecifica = razonEspecificaMap[g.resultado] || g.resultado || 'No especificada';
        const asesor = g.asesor_nombre || 'N/A';
        const facturaGestionar = g.factura_gestionar || '';
        const numeroObligacion = g.numero_obligacion || '';
        const montoObligacion = g.monto_obligacion || null;
        const canalesAutorizados = g.canales_autorizados || [];
        const fechaAcuerdo = g.fecha_acuerdo || null;
        const montoAcuerdo = g.monto_acuerdo || null;
        const proximaAccion = g.proxima_accion || null;
        const proximaFecha = g.proxima_fecha || null;
        const comentarios = g.comentarios || 'Sin observaciones';
        const gestionId = g.id || 0;

        // Normalizar canales autorizados
        let canalesArray = [];
        if (Array.isArray(canalesAutorizados)) {
          canalesArray = canalesAutorizados;
        } else if (typeof canalesAutorizados === 'string') {
          canalesArray = canalesAutorizados.split(',').map(c => c.trim()).filter(c => c);
        }

        const canalesTexto = canalesArray.map(c => canalesMap[c.toLowerCase()] || c).join(', ') || 'No especificados';

        return `
          <div class="historial-item">
            <div class="historial-header">
              <div class="historial-fecha">
                <i class="fas fa-calendar-alt"></i>
                ${escapeHtml(fechaGestion)}
              </div>
            </div>
            
            <div class="historial-content">
              <div class="historial-grid">
                <!-- Canal de Contacto -->
                <div class="historial-field">
                  <div class="historial-field-label">
                    <i class="fas fa-phone"></i> Canal de Contacto
                  </div>
                  <div class="historial-field-value canal-contacto">
                    ${escapeHtml(canalContacto)}
                  </div>
                </div>
                
                <!-- Tipificación General -->
                <div class="historial-field">
                  <div class="historial-field-label">
                    <i class="fas fa-tags"></i> Resultado del Contacto
                  </div>
                  <div class="historial-field-value tipificacion">
                    ${escapeHtml(tipificacionGeneral)}
                  </div>
                </div>
                
                <!-- Razón Específica -->
                <div class="historial-field">
                  <div class="historial-field-label">
                    <i class="fas fa-list-alt"></i> Razón Específica
                  </div>
                  <div class="historial-field-value razon-especifica">
                    ${escapeHtml(razonEspecifica)}
                  </div>
                </div>
                
                <!-- Asesor -->
                <div class="historial-field">
                  <div class="historial-field-label">
                    <i class="fas fa-user"></i> Asesor Responsable
                  </div>
                  <div class="historial-field-value asesor">
                    ${escapeHtml(asesor)}
                  </div>
                </div>
                
                ${facturaGestionar ? `
                <!-- Factura a Gestionar -->
                <div class="historial-field">
                  <div class="historial-field-label">
                    <i class="fas fa-file-invoice"></i> Factura a Gestionar
                  </div>
                  <div class="historial-field-value factura">
                    ${facturaGestionar === 'ninguna' ? `
                      <span style="color: #dc3545; font-weight: bold;">❌ Ninguna (Cliente no quiere pagar)</span>
                    ` : numeroObligacion === 'TODAS LAS FACTURAS' ? `
                      <span style="color: #17a2b8; font-weight: bold;">💰 Todas las facturas</span>
                      ${montoObligacion ? `<span style="color: #6c757d; font-size: 0.9em; font-family: 'Courier New', monospace;">- ${formatearPesos(montoObligacion)} COP</span>` : ''}
                    ` : `
                      <span style="color: #28a745; font-weight: bold;">✅ Factura #${escapeHtml(numeroObligacion)}</span>
                      ${montoObligacion ? `<span style="color: #6c757d; font-size: 0.9em; font-family: 'Courier New', monospace;">- ${formatearPesos(montoObligacion)} COP</span>` : ''}
                    `}
                  </div>
                </div>
                ` : ''}
                
                <!-- Canales Autorizados -->
                <div class="historial-field">
                  <div class="historial-field-label">
                    <i class="fas fa-broadcast-tower"></i> Canales Autorizados
                  </div>
                  <div class="historial-field-value canales-autorizados">
                    ${canalesTexto !== 'No especificados' ? escapeHtml(canalesTexto) : '<span style="color: #6c757d; font-style: italic;">No especificados</span>'}
                  </div>
                </div>
                
                ${fechaAcuerdo && montoAcuerdo ? `
                <!-- Fecha de Pago y Cuota -->
                <div class="historial-field">
                  <div class="historial-field-label">
                    <i class="fas fa-calendar-check"></i> Fecha de la Cuota
                  </div>
                  <div class="historial-field-value">
                    ${new Date(fechaAcuerdo).toLocaleDateString('es-CO', { day: '2-digit', month: '2-digit', year: 'numeric' })}
                  </div>
                </div>
                
                <div class="historial-field">
                  <div class="historial-field-label">
                    <i class="fas fa-dollar-sign"></i> Cuota a Pagar
                  </div>
                  <div class="historial-field-value" style="font-family: 'Courier New', monospace; font-weight: bold; color: #28a745;">
                    ${formatearPesos(montoAcuerdo)} COP
                  </div>
                </div>
                ` : ''}
                
                <!-- Observaciones -->
                <div class="historial-field historial-observaciones">
                  <div class="historial-field-label">
                    <i class="fas fa-comments"></i> Observaciones
                  </div>
                  <div class="historial-field-value observaciones">
                    ${escapeHtml(comentarios)}
                    <button class="btn btn-sm btn-info" 
                            onclick="window.mostrarObservacionesGestion(${gestionId})"
                            title="Ver observaciones completas"
                            style="margin-top: 10px; padding: 4px 8px; font-size: 0.85rem;">
                      <i class="fas fa-eye"></i> Ver más
                    </button>
                  </div>
                </div>
              </div>
            </div>
            
            ${proximaAccion || proximaFecha ? `
            <div class="historial-proxima">
              <h5>📅 Próxima Acción:</h5>
              ${proximaAccion ? `
              <div class="proxima-accion">
                <strong>Acción:</strong> ${escapeHtml(proximaAccion)}
              </div>
              ` : ''}
              ${proximaFecha ? `
              <div class="proxima-fecha">
                <strong>Fecha:</strong> ${formatearFecha(proximaFecha)}
              </div>
              ` : ''}
            </div>
            ` : ''}
          </div>
        `;
      })
      .join('');
  }

  function updateUrl(clienteId) {
    const url = new URL(window.location.href);
    url.searchParams.set('action', 'gestionar_cliente');
    url.searchParams.set('id', String(clienteId));
    url.searchParams.delete('cliente_id');
    url.searchParams.delete('gestion_guardada');
    history.pushState({ clienteId }, '', url.toString());
  }

  /**
   * Verificar si hay una llamada activa en el softphone
   * Verifica tanto la existencia de currentCall como su estado
   */
  function hayLlamadaActiva() {
    if (typeof window.webrtcSoftphone === 'undefined' || !window.webrtcSoftphone) {
      return false;
    }

    const call = window.webrtcSoftphone.currentCall;
    if (!call) {
      return false;
    }

    // Verificar el estado de la llamada (Established = 4 = llamada activa)
    const state = call.state;
    const stateStr = String(state);

    // La llamada está activa si el estado es 'Established' o '4'
    return stateStr === 'Established' || stateStr === '4' || state === 'Established';
  }

  // Función eliminada: cambiarClienteSinRecargar
  // Ahora siempre se recarga la página al cambiar de cliente
  function cambiarCliente(nuevoClienteId) {
    console.log(logPrefix, 'cambiarCliente llamado con:', nuevoClienteId);

    if (!nuevoClienteId) {
      console.error(logPrefix, 'ERROR: nuevoClienteId es null/undefined');
      return;
    }

    // Convertir a número si es string
    const idNumerico = Number(nuevoClienteId);
    if (isNaN(idNumerico) || idNumerico <= 0) {
      console.error(logPrefix, 'ERROR: nuevoClienteId no es un número válido:', nuevoClienteId);
      return;
    }

    const current = getCurrentClienteId();
    if (String(current) === String(idNumerico)) {
      console.log(logPrefix, 'Ya se está mostrando este cliente');
      return;
    }

    // Recargar la página con el nuevo cliente
    console.log(logPrefix, 'Recargando página con cliente:', idNumerico);
    window.location.href = `index.php?action=gestionar_cliente&id=${encodeURIComponent(idNumerico)}`;
  }

  /**
   * FIX: Definir mostrarObservacionesGestion globalmente como fallback
   * Esta función es llamada desde el HTML (onclick) pero debe estar disponible globalmente
   * Si ya existe en la vista (asesor_gestionar_cliente.php), se respeta esa implementación
   * Si no existe, se proporciona una implementación básica de fallback
   */
  // Verificar si mostrarObservacionesGestion ya está definida (por ejemplo en el HTML)
  if (typeof window.mostrarObservacionesGestion === 'undefined') {
    window.mostrarObservacionesGestion = function (gestionId) {
      console.log(logPrefix, 'Solicitando observaciones para gestión (Fallback):', gestionId);

      if (!gestionId || gestionId <= 0) {
        console.error(logPrefix, 'ERROR: gestionId inválido:', gestionId);
        alert('Error: ID de gestión inválido');
        return;
      }

      // Intentar obtener las observaciones desde el servidor
      fetchJson(`index.php?action=obtener_observaciones&id=${encodeURIComponent(gestionId)}`)
        .then(data => {
          if (data && data.success && data.observacion) {
            const observacion = data.observacion || 'Sin observaciones';
            alert('Observaciones de la gestión:\n\n' + observacion);
          } else {
            alert('No se encontraron observaciones para esta gestión.');
          }
        })
        .catch(error => {
          console.error(logPrefix, 'Error al obtener observaciones:', error);
          alert('Error al cargar las observaciones: ' + (error.message || 'Error desconocido'));
        });
    };
    console.log(logPrefix, '✅ mostrarObservacionesGestion inicializada (Fallback Global)');
  } else {
    // Ya existe, probablemente definida en la vista principal
    if (config.debug) {
      console.log(logPrefix, 'ℹ️ mostrarObservacionesGestion ya estaba definida externamente');
    }
  }

  // Exponer funciones globalmente para uso desde otras partes del código
  // Función renombrada: ahora recarga la página
  window.cambiarCliente = cambiarCliente;
  // Mantener alias para compatibilidad (pero ahora recarga la página)
  window.cambiarClienteSinRecargar = cambiarCliente;
  window.renderHistorialLlamadas = renderHistorialLlamadas;
  window.renderObligaciones = renderObligaciones;
  window.renderTelefonos = renderTelefonos;

  window.addEventListener('popstate', (ev) => {
    const id = ev.state?.clienteId || getCurrentClienteId();
    if (id) cambiarCliente(id);
  });

  document.addEventListener('DOMContentLoaded', () => {
    const id = getCurrentClienteId();
    if (id) {
      try {
        history.replaceState({ clienteId: id }, '', window.location.href);
      } catch (_) { }
    }
  });
})();


