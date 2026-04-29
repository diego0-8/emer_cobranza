-- Normalización para acelerar filtros en historial_gestiones.
-- Ejecutar en la BD `emermedica_db`.
--
-- Objetivo:
-- - Evitar WHERE con LOWER/TRIM/REPLACE que no usan índices.
-- - Guardar columnas *_norm y indexarlas.

ALTER TABLE historial_gestiones
  ADD COLUMN forma_contacto_norm VARCHAR(120) NULL AFTER forma_contacto,
  ADD COLUMN tipo_contacto_norm VARCHAR(120) NULL AFTER tipo_contacto,
  ADD COLUMN resultado_contacto_norm VARCHAR(120) NULL AFTER resultado_contacto,
  ADD COLUMN razon_especifica_norm VARCHAR(120) NULL AFTER razon_especifica;

-- Backfill (normalización simple: trim -> lower -> espacios a underscore)
UPDATE historial_gestiones
SET
  forma_contacto_norm = LOWER(REPLACE(TRIM(forma_contacto), ' ', '_')),
  tipo_contacto_norm = LOWER(REPLACE(TRIM(tipo_contacto), ' ', '_')),
  resultado_contacto_norm = LOWER(REPLACE(TRIM(resultado_contacto), ' ', '_')),
  razon_especifica_norm = LOWER(REPLACE(TRIM(razon_especifica), ' ', '_'))
WHERE
  forma_contacto_norm IS NULL
  OR tipo_contacto_norm IS NULL
  OR resultado_contacto_norm IS NULL
  OR razon_especifica_norm IS NULL;

-- Índices para consultas nuevas (mis clientes / acuerdos / volver a llamar)
CREATE INDEX idx_hg_asesor_cliente_fecha ON historial_gestiones (asesor_cedula, cliente_id, fecha_creacion);
CREATE INDEX idx_hg_asesor_cliente_resultado_norm ON historial_gestiones (asesor_cedula, cliente_id, resultado_contacto_norm);
CREATE INDEX idx_hg_cliente_resultado_norm ON historial_gestiones (cliente_id, resultado_contacto_norm);

