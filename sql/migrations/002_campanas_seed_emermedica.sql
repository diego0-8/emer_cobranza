-- Migración 002: Seed campaña Emermedica Cobranza + Juanfe
-- Idempotente: puede ejecutarse varias veces sin duplicar

SET @juanfe_cedula = '1000121307';
SET @campana_nombre = 'Emermedica Cobranza';

-- Admin activo para creado_por / asignado_por
SET @admin_cedula = (
    SELECT cedula FROM usuarios
    WHERE rol = 'administrador' AND estado = 'activo'
    ORDER BY fecha_creacion ASC
    LIMIT 1
);

-- Crear campaña si no existe
INSERT INTO campanas (nombre, descripcion, estado, creado_por)
SELECT @campana_nombre,
       'Campaña principal de cobranza Emermedica',
       'activa',
       COALESCE(@admin_cedula, @juanfe_cedula)
WHERE NOT EXISTS (
    SELECT 1 FROM campanas WHERE nombre = @campana_nombre
);

SET @campana_id = (
    SELECT id_campana FROM campanas WHERE nombre = @campana_nombre LIMIT 1
);

-- Asignar Juanfe como coordinador de la campaña
INSERT INTO campana_coordinadores (campana_id, coordinador_cedula, estado, asignado_por)
SELECT @campana_id, @juanfe_cedula, 'activo', COALESCE(@admin_cedula, @juanfe_cedula)
WHERE @campana_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM campana_coordinadores
    WHERE campana_id = @campana_id AND coordinador_cedula = @juanfe_cedula
);

UPDATE campana_coordinadores
SET estado = 'activo',
    asignado_por = COALESCE(asignado_por, COALESCE(@admin_cedula, @juanfe_cedula))
WHERE campana_id = @campana_id AND coordinador_cedula = @juanfe_cedula;

-- Migrar asesores activos de asignaciones_cordinador (legacy) a campana_asesores
INSERT INTO campana_asesores (campana_id, asesor_cedula, estado, asignado_por)
SELECT @campana_id, ac.asesor_cedula, 'activo', COALESCE(@admin_cedula, @juanfe_cedula)
FROM asignaciones_cordinador ac
INNER JOIN usuarios u ON u.cedula = ac.asesor_cedula
WHERE ac.cordinador_cedula = @juanfe_cedula
  AND ac.estado = 'activo'
  AND u.rol = 'asesor'
  AND u.estado = 'activo'
  AND @campana_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM campana_asesores ca
    WHERE ca.campana_id = @campana_id AND ca.asesor_cedula = ac.asesor_cedula
);

-- Reactivar asesores ya migrados previamente
UPDATE campana_asesores ca
INNER JOIN asignaciones_cordinador ac
    ON ac.asesor_cedula = ca.asesor_cedula
   AND ac.cordinador_cedula = @juanfe_cedula
   AND ac.estado = 'activo'
SET ca.estado = 'activo'
WHERE ca.campana_id = @campana_id;

-- Vincular bases activas de Juanfe a la campaña
UPDATE base_clientes
SET campana_id = @campana_id
WHERE creado_por = @juanfe_cedula
  AND estado = 'activo'
  AND @campana_id IS NOT NULL
  AND (campana_id IS NULL OR campana_id = @campana_id);

-- Desactivar relaciones legacy coord-asesor (conservar historial)
UPDATE asignaciones_cordinador
SET estado = 'inactivo'
WHERE cordinador_cedula = @juanfe_cedula
  AND estado = 'activo'
  AND @campana_id IS NOT NULL;

-- Registro de auditoría de migración (solo si no existe uno igual reciente)
INSERT INTO auditoria_coordinadores (coordinador_cedula, campana_id, accion, entidad, entidad_id, detalle)
SELECT @juanfe_cedula, @campana_id, 'migracion_inicial', 'campana', @campana_id,
       JSON_OBJECT(
           'mensaje', 'Migración inicial a modelo de campañas',
           'campana', @campana_nombre,
           'asesores_migrados', (SELECT COUNT(*) FROM campana_asesores WHERE campana_id = @campana_id AND estado = 'activo'),
           'bases_vinculadas', (SELECT COUNT(*) FROM base_clientes WHERE campana_id = @campana_id AND estado = 'activo')
       )
WHERE @campana_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM auditoria_coordinadores
    WHERE campana_id = @campana_id AND accion = 'migracion_inicial'
  );
