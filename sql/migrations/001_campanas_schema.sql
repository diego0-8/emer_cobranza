-- Migración 001: Esquema de campañas
-- Sistema Emermedica Cobranza

CREATE TABLE IF NOT EXISTS campanas (
    id_campana INT NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT NULL,
    estado ENUM('activa', 'inactiva') NOT NULL DEFAULT 'activa',
    creado_por VARCHAR(10) NOT NULL,
    fecha_creacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id_campana),
    KEY idx_campanas_estado (estado),
    KEY idx_campanas_creado_por (creado_por),
    CONSTRAINT fk_campana_creado_por FOREIGN KEY (creado_por) REFERENCES usuarios (cedula)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS campana_coordinadores (
    id_campana_coordinador INT NOT NULL AUTO_INCREMENT,
    campana_id INT NOT NULL,
    coordinador_cedula VARCHAR(10) NOT NULL,
    estado ENUM('activo', 'inactivo') NOT NULL DEFAULT 'activo',
    asignado_por VARCHAR(10) NULL,
    fecha_asignacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_creacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_campana_coordinador),
    UNIQUE KEY uk_campana_coord (campana_id, coordinador_cedula),
    KEY idx_cc_coord (coordinador_cedula),
    KEY idx_cc_estado (estado),
    CONSTRAINT fk_cc_campana FOREIGN KEY (campana_id) REFERENCES campanas (id_campana),
    CONSTRAINT fk_cc_coordinador FOREIGN KEY (coordinador_cedula) REFERENCES usuarios (cedula),
    CONSTRAINT fk_cc_asignado_por FOREIGN KEY (asignado_por) REFERENCES usuarios (cedula)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS campana_asesores (
    id_campana_asesor INT NOT NULL AUTO_INCREMENT,
    campana_id INT NOT NULL,
    asesor_cedula VARCHAR(10) NOT NULL,
    estado ENUM('activo', 'inactivo') NOT NULL DEFAULT 'activo',
    asignado_por VARCHAR(10) NULL,
    fecha_asignacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_creacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_campana_asesor),
    UNIQUE KEY uk_campana_asesor (campana_id, asesor_cedula),
    KEY idx_ca_asesor (asesor_cedula),
    KEY idx_ca_estado (estado),
    CONSTRAINT fk_ca_campana FOREIGN KEY (campana_id) REFERENCES campanas (id_campana),
    CONSTRAINT fk_ca_asesor FOREIGN KEY (asesor_cedula) REFERENCES usuarios (cedula),
    CONSTRAINT fk_ca_asignado_por FOREIGN KEY (asignado_por) REFERENCES usuarios (cedula)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS auditoria_coordinadores (
    id_auditoria INT NOT NULL AUTO_INCREMENT,
    coordinador_cedula VARCHAR(10) NOT NULL,
    campana_id INT NULL,
    accion VARCHAR(80) NOT NULL,
    entidad VARCHAR(80) NOT NULL,
    entidad_id INT NULL,
    detalle JSON NULL,
    fecha TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_auditoria),
    KEY idx_aud_coord (coordinador_cedula),
    KEY idx_aud_campana (campana_id),
    KEY idx_aud_fecha (fecha),
    CONSTRAINT fk_aud_coord FOREIGN KEY (coordinador_cedula) REFERENCES usuarios (cedula),
    CONSTRAINT fk_aud_campana FOREIGN KEY (campana_id) REFERENCES campanas (id_campana)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Alteraciones idempotentes (solo si la columna no existe)
SET @col_exists = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'base_clientes' AND COLUMN_NAME = 'campana_id'
);
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE base_clientes ADD COLUMN campana_id INT NULL AFTER creado_por, ADD KEY idx_base_campana (campana_id)',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @fk_exists = (
    SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'base_clientes' AND CONSTRAINT_NAME = 'fk_base_campana'
);
SET @sql = IF(@fk_exists = 0,
    'ALTER TABLE base_clientes ADD CONSTRAINT fk_base_campana FOREIGN KEY (campana_id) REFERENCES campanas (id_campana)',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'asignacion_base_asesores' AND COLUMN_NAME = 'asignado_por'
);
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE asignacion_base_asesores ADD COLUMN asignado_por VARCHAR(10) NULL AFTER asesor_cedula',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @fk_exists = (
    SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'asignacion_base_asesores' AND CONSTRAINT_NAME = 'fk_aba_asignado_por'
);
SET @sql = IF(@fk_exists = 0,
    'ALTER TABLE asignacion_base_asesores ADD CONSTRAINT fk_aba_asignado_por FOREIGN KEY (asignado_por) REFERENCES usuarios (cedula)',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
