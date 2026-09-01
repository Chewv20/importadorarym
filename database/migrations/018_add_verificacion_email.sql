-- Verificación de correo del cliente. `email_verificado_en` NULL = sin verificar.
-- `verificacion_token` guarda el hash SHA-256 del token enviado por correo.
ALTER TABLE usuarios
    ADD COLUMN email_verificado_en DATETIME     DEFAULT NULL AFTER aprobado,
    ADD COLUMN verificacion_token  VARCHAR(64)  DEFAULT NULL AFTER email_verificado_en;
