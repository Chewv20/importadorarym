-- Correcciones derivadas de la auditoría de seguridad (docs/AUDITORIA.md).

-- S4 · El token de activación del kiosco no caducaba: un enlace enviado por
-- chat o correo hace meses seguía armando una tablet autorizada. Ahora vence.
ALTER TABLE checador_dispositivos
    ADD COLUMN activacion_expira_en TIMESTAMP NULL DEFAULT NULL AFTER activacion_token_hash;

-- Los enlaces ya emitidos y sin usar quedan vencidos: hay que regenerarlos
-- desde el panel (Visitas > Dispositivos > Regenerar enlace).
UPDATE checador_dispositivos
   SET activacion_expira_en = created_at
 WHERE activacion_token_hash IS NOT NULL;

-- S8 · El token de verificación de correo tampoco expiraba.
ALTER TABLE usuarios
    ADD COLUMN verificacion_expira_en TIMESTAMP NULL DEFAULT NULL AFTER verificacion_token;

UPDATE usuarios
   SET verificacion_expira_en = DATE_ADD(NOW(), INTERVAL 48 HOUR)
 WHERE verificacion_token IS NOT NULL;
