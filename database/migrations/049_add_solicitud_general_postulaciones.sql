-- Solicitud de empleo general (sin vacante específica), versión reducida de la
-- plantilla SNE que dejó el cliente en docs/brand/. Estas columnas solo las
-- llena el nuevo formulario de /bolsa-de-trabajo/solicitud; en postulaciones
-- ligadas a una vacante concreta quedan NULL.
ALTER TABLE postulaciones
    ADD COLUMN area_interes  VARCHAR(150) DEFAULT NULL AFTER telefono,
    ADD COLUMN sueldo_deseado VARCHAR(60)  DEFAULT NULL AFTER area_interes,
    ADD COLUMN disponibilidad VARCHAR(100) DEFAULT NULL AFTER sueldo_deseado,
    ADD COLUMN escolaridad    VARCHAR(150) DEFAULT NULL AFTER disponibilidad;
