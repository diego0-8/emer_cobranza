<?php
/**
 * Etiquetas legibles para el historial de tipificaciones (3 niveles).
 */

function emer_mapa_tipo_contacto_historial(): array
{
    return [
        'contacto_exitoso' => '✅ CONTACTO EXITOSO',
        'contacto_tercero' => '👥 CONTACTO CON TERCERO',
        'sin_contacto' => '❌ SIN CONTACTO',
    ];
}

function emer_mapa_resultado_contacto_historial(): array
{
    return [
        'acuerdo_pago' => '💰 ACUERDO DE PAGO',
        'ya_pago' => '✅ YA PAGO',
        'localizado_sin_acuerdo' => '📍 LOCALIZADO SIN ACUERDO',
        'reclamo' => '📋 RECLAMO',
        'volver_llamar' => '📞 VOLVER A LLAMAR',
        'recordar_pago' => '⏰ RECORDAR PAGO',
        'venta_novedad' => '🆕 VENTA CON NOVEDAD',
        'aqui_no_vive' => '🏠 AQUÍ NO VIVE NO TRABAJA',
        'mensaje_tercero' => '📝 MENSAJE CON TERCERO',
        'fallecido_otro' => '💀 FALLECIDO/OTRO',
        'no_contesta' => '📞 NO CONTESTA',
        'buzon_mensajes' => '📪 BUZÓN DE MENSAJES',
        'telefono_danado' => '📵 TELÉFONO DAÑADO',
        'localizacion' => '📍 LOCALIZACIÓN',
        'envio_estado_cuenta' => '📧 ENVÍO ESTADO DE CUENTA',
        'venta_novedad_analisis' => '🆕 VENTA CON NOVEDAD ANÁLISIS DATA',
        'ACUERDO DE PAGO' => '💰 ACUERDO DE PAGO',
        'YA PAGO' => '✅ YA PAGO',
        'VOLVER A LLAMAR' => '📞 VOLVER A LLAMAR',
        'Llamada de Venta' => '📞 LLAMADA DE VENTA',
        'Llamada de Gestión' => '📞 LLAMADA DE GESTIÓN',
        'Cliente Interesado' => '💡 CLIENTE INTERESADO',
        'Venta Ingresada' => '💰 VENTA INGRESADA',
    ];
}

function emer_mapa_razon_especifica_historial(): array
{
    return [
        'no_informa' => '❌ NO INFORMA',
        'indefinido' => '❓ INDEFINIDA',
        'desempleo' => '💼 DESEMPLEO',
        'incremento_tarifa' => '📈 INCREMENTO DE TARIFA',
        'otras_prioridades_economicas' => '💰 TIENE OTRAS PRIORIDADES ECONOMICAS',
        'disminucion_ingresos' => '📉 DISMINUCION DE INGRESOS',
        'adquirio_otro_servicio_salud' => '🏥 ADQUIRIO OTRO SERVICIO DE SALUD',
        'no_utiliza_beneficios' => '❌ NO UTILIZA/NO BENEFICIOS DEL SERVICIO',
        'sale_del_pais' => '✈️ SALE DEL PAIS',
        'fallecido' => '💀 FALLECIDO',
        'humanizacion_servicio' => '🤝 HUMANIZACION DEL SERVICIO GENERAL',
        'oportunidad_nunca_llegaron' => '⏰ OPORTUNIDAD/NUNCA LLEGARON',
        'metodo_pago_errado' => '💳 METODO DE PAGO ERRADO/DEBITO AUTOMATICO',
        'no_realizan_debito_automatico' => '🚫 NO REALIZAN DEBITO AUTOMATICO',
        'falsa_promesa_comercial' => '❌ FALSA PROMESA COMERCIAL',
        'fraude' => '🚨 FRAUDE',
        'factura_no_corresponde' => '📄 FACTURA NO CORRESPONDE',
        'no_entrega_aviso_pago' => '📬 NO ENTREGA DE AVISO DE PAGO/FACTURA',
        'facturacion_errada' => '📊 FACTURACION ERRADA',
        'cambio_traslado_sin_cobertura' => '🔄 CAMBIO/TRASLADO SIN COBERTURA',
        'cancelacion_no_aplicada' => '❌ CANCELACION NO APLICADA',
        'incumplimiento_ofercimientos' => '🤝 INCUMPLIMIENTO OFRECIMIENTOS REALIZADOS (LEALTAD)',
        'inconformidad_pqr' => '📋 INCONFORMIDAD PQR',
        'informacion_errada' => 'ℹ️ INFORMACION ERRADA',
        'no_contestaron_sac' => '📞 NO CONTESTARON EN LA LINEA DE SAC',
        'reclamo_pendiente_respuesta' => '⏳ RECLAMO PENDIENTE DE RESPUESTA',
        'pago_afiliacion_no_aplicado' => '💳 PAGO DE AFILIACION NO APLICADO',
        'pago_sin_aplicar' => '💰 PAGO SIN APLICAR',
        'medio_pago_no_sirve' => '💳 MEDIO DE PAGO NO SIRVE',
        'no_conoce_politicas_cancelacion' => '📋 NO CONOCE POLITICAS DE CANCELACION',
        'olvido_pago' => '🔔 OLVIDO DE PAGO',
        'proceso_cancelacion' => '🔄 PROCESO DE CANCELACIÓN',
        'rechazo_teleconsulta' => '📵 RECHAZO TELECONSULTA',
        'viaje' => '✈️ VIAJE',
        'contesta_cuelga' => '📞 CONTESTA-CUELGA',
        'ya_pago' => '✅ YA PAGO',
        'recordar_pago' => '⏰ RECORDAR PAGO',
        'venta_novedad' => '🆕 VENTA CON NOVEDAD',
        'volver_llamar' => '📞 VOLVER A LLAMAR',
        '-' => '—',
    ];
}

function emer_codigo_tipificacion_limpio(?string $codigo): string
{
    $codigo = trim((string)$codigo);
    if ($codigo === '' || $codigo === '-') {
        return '';
    }
    return $codigo;
}

function emer_etiqueta_tipificacion(array $mapa, ?string $codigo): string
{
    $codigo = emer_codigo_tipificacion_limpio($codigo);
    if ($codigo === '') {
        return '';
    }
    if (isset($mapa[$codigo])) {
        return $mapa[$codigo];
    }
    $upper = strtoupper(str_replace('_', ' ', $codigo));
    if (isset($mapa[$upper])) {
        return $mapa[$upper];
    }
    return ucwords(str_replace('_', ' ', $codigo));
}

function emer_normalizar_codigo_tipificacion(?string $codigo): string
{
    $codigo = emer_codigo_tipificacion_limpio($codigo);
    if ($codigo === '') {
        return '';
    }
    $n = mb_strtolower($codigo, 'UTF-8');
    $n = str_replace([' ', '-', '/'], '_', $n);
    $n = preg_replace('/_+/', '_', $n);
    return trim((string)$n, '_');
}

function emer_es_resultado_volver_llamar(?string $codigo): bool
{
    $n = emer_normalizar_codigo_tipificacion($codigo);
    if ($n === '') {
        return false;
    }
    return in_array($n, ['volver_llamar', 'volver_a_llamar', 'agenda_llamada_de_seguimiento'], true)
        || (strpos($n, 'volver') !== false && strpos($n, 'llamar') !== false);
}

/**
 * Extrae fecha/hora de próxima llamada desde observaciones (volver a llamar).
 */
function emer_extraer_proxima_llamada_desde_observaciones(?string $observaciones): ?string
{
    $observaciones = trim((string)$observaciones);
    if ($observaciones === '') {
        return null;
    }
    $patrones = [
        '/\[PROXIMA_LLAMADA:([^\]]+)\]/i',
        '/PR[OÓ]XIMA LLAMADA PROGRAMADA:\s*(.+?)(?:\r?\n|$)/iu',
        '/NUEVA LLAMADA AGENDADA:.*?Fecha:\s*(.+?)(?:\r?\n|$)/ius',
        '/fecha_nueva_llamada[=:]\s*([0-9]{4}-[0-9]{2}-[0-9]{2}[T\s][0-9]{2}:[0-9]{2})/i',
    ];
    foreach ($patrones as $patron) {
        if (preg_match($patron, $observaciones, $m)) {
            $fecha = trim($m[1]);
            if ($fecha !== '') {
                return $fecha;
            }
        }
    }
    return null;
}

function emer_obtener_proxima_llamada_historial(array $gestion): ?string
{
    $proxima = trim((string)($gestion['proxima_fecha'] ?? ''));
    if ($proxima !== '') {
        return $proxima;
    }
    return emer_extraer_proxima_llamada_desde_observaciones(
        $gestion['observaciones'] ?? ($gestion['comentarios'] ?? '')
    );
}

/**
 * Quita metadatos de próxima llamada del texto visible de observaciones.
 */
function emer_limpiar_texto_observaciones_historial(?string $texto): string
{
    $texto = (string)$texto;
    if ($texto === '') {
        return '';
    }

    $lineas = preg_split("/\r\n|\r|\n/", $texto);
    if (!is_array($lineas)) {
        return trim($texto);
    }

    $filtradas = [];
    foreach ($lineas as $linea) {
        $trim = trim((string)$linea);
        if ($trim === '') {
            $filtradas[] = '';
            continue;
        }
        if (preg_match('/^\[PROXIMA_LLAMADA:[^\]]+\]\s*$/i', $trim)) {
            continue;
        }
        if (preg_match('/PR[OÓ]XIMA LLAMADA PROGRAMADA:/iu', $trim)) {
            continue;
        }
        if (preg_match('/^📅\s*PR[OÓ]XIMA LLAMADA PROGRAMADA:/iu', $trim)) {
            continue;
        }
        if (preg_match('/^NUEVA LLAMADA AGENDADA:/iu', $trim)) {
            continue;
        }
        $filtradas[] = $linea;
    }

    $resultado = trim(implode("\n", $filtradas));
    $resultado = preg_replace("/\n{3,}/", "\n\n", $resultado);
    return $resultado;
}

function emer_formatear_proxima_llamada_historial(?string $fecha): string
{
    $fecha = trim((string)$fecha);
    if ($fecha === '') {
        return '';
    }
    if (preg_match('/^\d{4}-\d{2}-\d{2}/', $fecha)) {
        $ts = strtotime(str_replace('T', ' ', $fecha));
        return $ts !== false ? date('d/m/Y H:i', $ts) : $fecha;
    }
    if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})\s+(\d{1,2}):(\d{2})/', $fecha, $m)) {
        return sprintf('%02d/%02d/%04d %02d:%02d', (int)$m[1], (int)$m[2], (int)$m[3], (int)$m[4], (int)$m[5]);
    }
    $ts = strtotime($fecha);
    return $ts !== false ? date('d/m/Y H:i', $ts) : $fecha;
}

/**
 * Enriquece una fila de historial_gestiones para la vista del asesor.
 */
function emer_enriquecer_gestion_historial(array &$row): void
{
    $row['tipo_contacto_arbol_codigo'] = $row['tipo_contacto'] ?? ($row['tipo_contacto_arbol_codigo'] ?? null);
    $row['resultado_contacto_codigo'] = $row['resultado_contacto'] ?? ($row['resultado_contacto_codigo'] ?? null);
    $row['razon_especifica_codigo'] = $row['razon_especifica'] ?? ($row['razon_especifica_codigo'] ?? null);

    $tipoArbol = emer_codigo_tipificacion_limpio($row['tipo_contacto_arbol_codigo'] ?? '');
    $razonCodigo = emer_codigo_tipificacion_limpio($row['razon_especifica_codigo'] ?? '');
    $motivosSinContacto = [
        'no_contesta', 'buzon_mensajes', 'telefono_danado', 'fallecido_otro',
        'localizacion', 'envio_estado_cuenta', 'venta_novedad_analisis',
    ];
    if ($tipoArbol === 'sin_contacto' && in_array($razonCodigo, $motivosSinContacto, true)) {
        $row['razon_especifica_codigo'] = 'indefinido';
    }

    $observacionesRaw = $row['observaciones'] ?? ($row['comentarios'] ?? '');
    $extraida = emer_extraer_proxima_llamada_desde_observaciones($observacionesRaw);
    if ($extraida !== null) {
        $row['proxima_fecha'] = $extraida;
    }
    if (emer_es_resultado_volver_llamar($row['resultado_contacto_codigo'] ?? '')) {
        $row['proxima_accion'] = $row['proxima_accion'] ?? 'Volver a llamar';
    }

    $observacionesLimpias = emer_limpiar_texto_observaciones_historial($observacionesRaw);
    $row['observaciones'] = $observacionesLimpias;
    $row['comentarios'] = $observacionesLimpias;
}
