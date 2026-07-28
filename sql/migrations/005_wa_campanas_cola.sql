-- WhatsApp campañas masivas + cola de asignación + dismiss de burbujas
-- Requiere 004_wa_kommo_schema.sql

ALTER TABLE wa_conversaciones
    ADD COLUMN campana_id INT NULL AFTER cliente_id,
    ADD COLUMN origen ENUM('organico','campana_masiva') NOT NULL DEFAULT 'organico' AFTER campana_id,
    ADD COLUMN campana_masiva_id INT UNSIGNED NULL AFTER origen,
    ADD COLUMN asesor_notificacion_id VARCHAR(20) NULL AFTER asesor_id,
    ADD COLUMN ultimo_interactuante_id VARCHAR(20) NULL AFTER asesor_notificacion_id;

-- Backfill: notificaciones = dueño legacy
UPDATE wa_conversaciones
SET asesor_notificacion_id = asesor_id
WHERE asesor_notificacion_id IS NULL AND asesor_id IS NOT NULL;

UPDATE wa_conversaciones
SET ultimo_interactuante_id = asesor_id
WHERE ultimo_interactuante_id IS NULL AND asesor_id IS NOT NULL;

ALTER TABLE wa_conversaciones
    ADD KEY idx_wa_conv_campana (campana_id),
    ADD KEY idx_wa_conv_notif (asesor_notificacion_id),
    ADD KEY idx_wa_conv_masiva (campana_masiva_id),
    ADD KEY idx_wa_conv_origen (origen);

CREATE TABLE IF NOT EXISTS wa_campanas_masivas (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    base_id INT NOT NULL,
    campana_id INT NULL,
    coordinador_cedula VARCHAR(20) NOT NULL,
    template_external_id VARCHAR(128) NOT NULL DEFAULT '',
    template_name VARCHAR(191) NOT NULL DEFAULT '',
    template_language VARCHAR(16) NOT NULL DEFAULT 'es',
    body_preview TEXT NULL,
    var_map JSON NULL,
    estado ENUM('borrador','procesando','completada','cancelada') NOT NULL DEFAULT 'borrador',
    total INT UNSIGNED NOT NULL DEFAULT 0,
    enviados INT UNSIGNED NOT NULL DEFAULT 0,
    errores INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_wa_cm_base (base_id),
    KEY idx_wa_cm_campana (campana_id),
    KEY idx_wa_cm_coord (coordinador_cedula),
    KEY idx_wa_cm_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS wa_campana_destinatarios (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    campana_masiva_id INT UNSIGNED NOT NULL,
    cliente_id INT NULL,
    cedula VARCHAR(32) NOT NULL,
    nombre VARCHAR(191) NULL,
    telefono_e164 VARCHAR(20) NULL,
    conversacion_id INT UNSIGNED NULL,
    estado ENUM('pendiente','enviando','enviado','error','sin_telefono','sin_cliente') NOT NULL DEFAULT 'pendiente',
    kommo_message_id VARCHAR(128) NULL,
    error_msg VARCHAR(500) NULL,
    enviado_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_wa_cd_campana (campana_masiva_id),
    KEY idx_wa_cd_estado (estado),
    KEY idx_wa_cd_cedula (cedula),
    CONSTRAINT fk_wa_cd_campana FOREIGN KEY (campana_masiva_id)
        REFERENCES wa_campanas_masivas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS wa_cola_asignacion (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    conversacion_id INT UNSIGNED NOT NULL,
    campana_id INT NULL,
    estado ENUM('esperando_asesor','asignado','cancelado') NOT NULL DEFAULT 'esperando_asesor',
    asignado_a VARCHAR(20) NULL,
    asignado_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_wa_cola_conv (conversacion_id),
    KEY idx_wa_cola_estado (estado),
    KEY idx_wa_cola_campana (campana_id),
    CONSTRAINT fk_wa_cola_conv FOREIGN KEY (conversacion_id)
        REFERENCES wa_conversaciones(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS wa_burbuja_dismiss (
    conversacion_id INT UNSIGNED NOT NULL,
    asesor_id VARCHAR(20) NOT NULL,
    dismissed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (conversacion_id, asesor_id),
    KEY idx_wa_dismiss_asesor (asesor_id),
    CONSTRAINT fk_wa_dismiss_conv FOREIGN KEY (conversacion_id)
        REFERENCES wa_conversaciones(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
