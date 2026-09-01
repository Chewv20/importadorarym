CREATE TABLE IF NOT EXISTS pedido_items (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    pedido_id INT UNSIGNED NOT NULL,
    producto_id INT UNSIGNED DEFAULT NULL,
    -- Snapshot del producto al momento del pedido (por si cambia o se elimina).
    sku VARCHAR(60) DEFAULT NULL,
    nombre VARCHAR(150) NOT NULL,
    cantidad INT UNSIGNED NOT NULL DEFAULT 1,
    precio_unitario DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    importe DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    PRIMARY KEY (id),
    KEY idx_pedido_items_pedido (pedido_id),
    KEY idx_pedido_items_producto (producto_id),
    CONSTRAINT fk_pedido_items_pedido FOREIGN KEY (pedido_id)
        REFERENCES pedidos (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_pedido_items_producto FOREIGN KEY (producto_id)
        REFERENCES productos (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
