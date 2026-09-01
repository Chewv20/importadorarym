-- Fase 7.1: dirección del cliente, para logística/entrega. Todas nullable (los
-- clientes existentes no tienen dirección capturada; el registro nuevo la exige).
-- "estado_direccion" evita colisionar con la columna `estado` de otras tablas.
ALTER TABLE usuarios
    ADD COLUMN calle VARCHAR(150) DEFAULT NULL AFTER rfc,
    ADD COLUMN numero_ext VARCHAR(20) DEFAULT NULL AFTER calle,
    ADD COLUMN numero_int VARCHAR(20) DEFAULT NULL AFTER numero_ext,
    ADD COLUMN colonia VARCHAR(100) DEFAULT NULL AFTER numero_int,
    ADD COLUMN codigo_postal VARCHAR(5) DEFAULT NULL AFTER colonia,
    ADD COLUMN ciudad VARCHAR(100) DEFAULT NULL AFTER codigo_postal,
    ADD COLUMN estado_direccion VARCHAR(100) DEFAULT NULL AFTER ciudad,
    ADD COLUMN referencias VARCHAR(255) DEFAULT NULL AFTER estado_direccion;
