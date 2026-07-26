-- WhatsApp + Kommo mirror (emer_cobranza)
-- asesor_id = cedula (varchar), cliente_id = clientes.id_cliente

CREATE TABLE IF NOT EXISTS wa_conversaciones (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NULL,
    telefono_e164 VARCHAR(20) NOT NULL,
    kommo_talk_id VARCHAR(64) NULL,
    kommo_chat_id VARCHAR(64) NULL,
    asesor_id VARCHAR(20) NULL,
    estado ENUM('abierta', 'cerrada', 'sin_cliente') NOT NULL DEFAULT 'abierta',
    wa_activo ENUM('desconocido', 'si', 'no') NOT NULL DEFAULT 'desconocido',
    no_leidos INT UNSIGNED NOT NULL DEFAULT 0,
    ultimo_mensaje_at DATETIME NULL,
    ultimo_preview VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_wa_conv_telefono (telefono_e164),
    KEY idx_wa_conv_cliente (cliente_id),
    KEY idx_wa_conv_asesor (asesor_id),
    KEY idx_wa_conv_estado (estado),
    KEY idx_wa_conv_ultimo (ultimo_mensaje_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS wa_mensajes (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    conversacion_id INT UNSIGNED NOT NULL,
    direccion ENUM('in', 'out') NOT NULL,
    tipo VARCHAR(32) NOT NULL DEFAULT 'text',
    cuerpo TEXT NULL,
    media_url VARCHAR(500) NULL,
    media_name VARCHAR(255) NULL,
    kommo_message_id VARCHAR(128) NULL,
    status VARCHAR(32) NOT NULL DEFAULT 'pendiente_envio',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_wa_msg_kommo (kommo_message_id),
    KEY idx_wa_msg_conv (conversacion_id, id),
    CONSTRAINT fk_wa_msg_conv FOREIGN KEY (conversacion_id)
        REFERENCES wa_conversaciones(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS wa_asignaciones (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    conversacion_id INT UNSIGNED NOT NULL,
    asesor_id VARCHAR(20) NOT NULL,
    asignado_por VARCHAR(20) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_wa_asig_conv (conversacion_id),
    KEY idx_wa_asig_asesor (asesor_id),
    CONSTRAINT fk_wa_asig_conv FOREIGN KEY (conversacion_id)
        REFERENCES wa_conversaciones(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS wa_usuarios_map (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    kommo_user_id VARCHAR(64) NOT NULL,
    usuario_cedula VARCHAR(20) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_wa_map_kommo (kommo_user_id),
    KEY idx_wa_map_usuario (usuario_cedula)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
