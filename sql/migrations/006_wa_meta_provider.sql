-- Proveedor Meta Cloud API manteniendo compatibilidad Kommo.
ALTER TABLE wa_conversaciones
    ADD COLUMN provider ENUM('kommo','meta') NOT NULL DEFAULT 'kommo' AFTER telefono_e164,
    ADD COLUMN meta_phone_number_id VARCHAR(64) NULL AFTER provider,
    ADD KEY idx_wa_conv_provider (provider);

ALTER TABLE wa_mensajes
    ADD COLUMN external_message_id VARCHAR(191) NULL AFTER kommo_message_id,
    ADD COLUMN media_id VARCHAR(191) NULL AFTER media_url,
    ADD UNIQUE KEY uq_wa_msg_external (external_message_id);

UPDATE wa_mensajes
SET external_message_id = kommo_message_id
WHERE external_message_id IS NULL AND kommo_message_id IS NOT NULL;

ALTER TABLE wa_campana_destinatarios
    ADD COLUMN external_message_id VARCHAR(191) NULL AFTER kommo_message_id,
    ADD KEY idx_wa_cd_external (external_message_id);

UPDATE wa_campana_destinatarios
SET external_message_id = kommo_message_id
WHERE external_message_id IS NULL AND kommo_message_id IS NOT NULL;
