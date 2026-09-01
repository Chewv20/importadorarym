-- Permite al cliente marcar "requiere impresión" en el formulario público de
-- cotización y adjuntar opcionalmente su logo (se guarda en storage/, fuera
-- del webroot — solo visible desde el detalle de la cotización en el panel).
ALTER TABLE cotizaciones
    ADD COLUMN requiere_impresion TINYINT(1) NOT NULL DEFAULT 0 AFTER mensaje,
    ADD COLUMN logo_archivo VARCHAR(140) DEFAULT NULL AFTER requiere_impresion;
