-- Fase 7.6: facturas (PDF + XML) del cliente, ligadas al envío (una factura
-- suele corresponder a una remesa exportada a SAE, no al pedido completo —
-- igual que 7.5/7.7/7.8). Sin UNIQUE en envio_id: una factura cancelada y
-- reemitida puede convivir con la anterior, no se fuerza 1:1.

CREATE TABLE IF NOT EXISTS pedido_facturas (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    envio_id INT UNSIGNED NOT NULL,
    archivo_pdf VARCHAR(60) NOT NULL,
    archivo_xml VARCHAR(60) NOT NULL,
    subido_por INT UNSIGNED DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_pedido_facturas_envio (envio_id),
    CONSTRAINT fk_pedido_facturas_envio FOREIGN KEY (envio_id)
        REFERENCES pedido_envios (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_pedido_facturas_usuario FOREIGN KEY (subido_por)
        REFERENCES usuarios (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
