-- Fase 7.1 (ajuste): "Ciudad" no es el término correcto en México — se usa
-- "Delegación" (CDMX) o "Municipio" (resto del país). Se renombra la columna
-- para que el nombre no siga siendo engañoso (antes: "ciudad" con datos que
-- en realidad debían ser delegación/municipio).
ALTER TABLE usuarios
    CHANGE COLUMN ciudad delegacion_municipio VARCHAR(100) DEFAULT NULL;
