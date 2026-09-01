-- Convierte las cotizaciones en documentos con partidas y precios.
-- Nuevo ciclo de vida: nueva -> cotizada -> aprobada/rechazada -> convertida.
ALTER TABLE cotizaciones
    MODIFY estado VARCHAR(20) NOT NULL DEFAULT 'nueva',
    ADD COLUMN usuario_id INT UNSIGNED DEFAULT NULL AFTER id,
    ADD COLUMN folio      VARCHAR(30)  DEFAULT NULL AFTER usuario_id,
    ADD COLUMN subtotal   DECIMAL(12,2) DEFAULT NULL,
    ADD COLUMN iva        DECIMAL(12,2) DEFAULT NULL,
    ADD COLUMN total      DECIMAL(12,2) DEFAULT NULL,
    ADD COLUMN enviada_en DATETIME     DEFAULT NULL,
    ADD COLUMN pedido_id  INT UNSIGNED DEFAULT NULL,
    ADD KEY idx_cot_usuario (usuario_id);

-- Remapea los estados anteriores de los leads existentes.
UPDATE cotizaciones SET estado = CASE estado
    WHEN 'nuevo'      THEN 'nueva'
    WHEN 'contactado' THEN 'nueva'
    WHEN 'cotizado'   THEN 'cotizada'
    WHEN 'cerrado'    THEN 'aprobada'
    WHEN 'descartado' THEN 'rechazada'
    ELSE estado END;

-- Partidas (líneas) de cada cotización.
CREATE TABLE IF NOT EXISTS cotizacion_items (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    cotizacion_id   INT UNSIGNED NOT NULL,
    producto_id     INT UNSIGNED DEFAULT NULL,
    descripcion     VARCHAR(200) NOT NULL,
    cantidad        INT NOT NULL DEFAULT 1,
    precio_unitario DECIMAL(12,2) NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    KEY idx_cotitem (cotizacion_id),
    CONSTRAINT fk_cotitem_cot FOREIGN KEY (cotizacion_id)
        REFERENCES cotizaciones (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
