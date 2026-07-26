-- Migración 003: Seed campaña Emermedica Cobranza (producción / dump)
-- Idempotente: crea campaña, asigna TODOS los coordinadores activos y TODOS los asesores activos,
-- vincula TODAS las bases activas, desactiva asignaciones_cordinador legacy.
--
-- Uso manual (phpMyAdmin / mysql CLI):
--   SOURCE sql/migrations/001_campanas_schema.sql;
--   SOURCE sql/migrations/003_campanas_seed_todos_prod.sql;
-- O: php scripts/ejecutar_seed_campana_prod.php

SET @campana_nombre = 'Emermedica Cobranza';

SET @admin_cedula = (
    SELECT cedula FROM usuarios
    WHERE rol = 'administrador' AND estado = 'activo'
    ORDER BY fecha_creacion ASC
    LIMIT 1
);

SET @fallback_cedula = (
    SELECT cedula FROM usuarios
    WHERE rol = 'cordinador' AND estado = 'activo'
    ORDER BY fecha_creacion ASC
    LIMIT 1
);

SET @creado_por = COALESCE(@admin_cedula, @fallback_cedula);

-- 1) Crear campaña si no existe
INSERT INTO campanas (nombre, descripcion, estado, creado_por)
SELECT @campana_nombre,
       'Campaña principal Emermedica Cobranza (seed producción)',
       'activa',
       @creado_por
WHERE @creado_por IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM campanas WHERE nombre = @campana_nombre);

SET @campana_id = (SELECT id_campana FROM campanas WHERE nombre = @campana_nombre LIMIT 1);

-- Reactivar si estaba inactiva
UPDATE campanas SET estado = 'activa' WHERE id_campana = @campana_id AND estado <> 'activa';

-- 2) Asignar TODOS los coordinadores ACTIVOS a la campaña
INSERT INTO campana_coordinadores (campana_id, coordinador_cedula, estado, asignado_por)
SELECT @campana_id, u.cedula, 'activo', @creado_por
FROM usuarios u
WHERE u.rol = 'cordinador'
  AND u.estado = 'activo'
  AND @campana_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM campana_coordinadores cc
    WHERE cc.campana_id = @campana_id AND cc.coordinador_cedula = u.cedula
  );

UPDATE campana_coordinadores cc
INNER JOIN usuarios u ON u.cedula = cc.coordinador_cedula
SET cc.estado = 'activo',
    cc.asignado_por = COALESCE(cc.asignado_por, @creado_por),
    cc.fecha_asignacion = CURRENT_TIMESTAMP
WHERE cc.campana_id = @campana_id
  AND u.rol = 'cordinador'
  AND u.estado = 'activo';

-- 3) Asignar TODOS los asesores ACTIVOS a la campaña
--    (desactiva otras campañas activas del asesor para respetar 1 campaña activa)
UPDATE campana_asesores ca
INNER JOIN usuarios u ON u.cedula = ca.asesor_cedula
SET ca.estado = 'inactivo'
WHERE ca.estado = 'activo'
  AND u.rol = 'asesor'
  AND u.estado = 'activo'
  AND ca.campana_id <> @campana_id
  AND @campana_id IS NOT NULL;

INSERT INTO campana_asesores (campana_id, asesor_cedula, estado, asignado_por)
SELECT @campana_id, u.cedula, 'activo', @creado_por
FROM usuarios u
WHERE u.rol = 'asesor'
  AND u.estado = 'activo'
  AND @campana_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM campana_asesores ca
    WHERE ca.campana_id = @campana_id AND ca.asesor_cedula = u.cedula
  );

UPDATE campana_asesores ca
INNER JOIN usuarios u ON u.cedula = ca.asesor_cedula
SET ca.estado = 'activo',
    ca.asignado_por = COALESCE(ca.asignado_por, @creado_por),
    ca.fecha_asignacion = CURRENT_TIMESTAMP
WHERE ca.campana_id = @campana_id
  AND u.rol = 'asesor'
  AND u.estado = 'activo';

-- 4) Vincular TODAS las bases activas a la campaña
UPDATE base_clientes
SET campana_id = @campana_id
WHERE estado = 'activo'
  AND @campana_id IS NOT NULL
  AND (campana_id IS NULL OR campana_id <> @campana_id);

-- 5) Desactivar legacy asesor↔coordinador
UPDATE asignaciones_cordinador
SET estado = 'inactivo'
WHERE estado = 'activo'
  AND @campana_id IS NOT NULL;

-- 6) Auditoría
INSERT INTO auditoria_coordinadores (coordinador_cedula, campana_id, accion, entidad, entidad_id, detalle)
SELECT COALESCE(@fallback_cedula, @creado_por), @campana_id, 'seed_produccion', 'campana', @campana_id,
       JSON_OBJECT(
           'mensaje', 'Seed producción: campaña + todos los coords/asesores activos + bases',
           'campana', @campana_nombre,
           'coordinadores', (SELECT COUNT(*) FROM campana_coordinadores WHERE campana_id = @campana_id AND estado = 'activo'),
           'asesores', (SELECT COUNT(*) FROM campana_asesores WHERE campana_id = @campana_id AND estado = 'activo'),
           'bases', (SELECT COUNT(*) FROM base_clientes WHERE campana_id = @campana_id AND estado = 'activo')
       )
WHERE @campana_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM auditoria_coordinadores
    WHERE campana_id = @campana_id AND accion = 'seed_produccion'
  );
