CREATE TABLE IF NOT EXISTS pedidos (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    usuario_id INT UNSIGNED NOT NULL,
    folio VARCHAR(30) NOT NULL,
    estado ENUM('borrador','enviado','en_proceso','sincronizado','cancelado') NOT NULL DEFAULT 'borrador',
    subtotal DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    total DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    notas TEXT DEFAULT NULL,

    -- Integración con el ERP: se llenan al pasar el pedido al sistema externo.
    erp_folio VARCHAR(60) DEFAULT NULL,
    erp_sincronizado TINYINT(1) NOT NULL DEFAULT 0,
    erp_sincronizado_at TIMESTAMP NULL DEFAULT NULL,
    erp_error VARCHAR(255) DEFAULT NULL,

    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_pedidos_folio (folio),
    KEY idx_pedidos_usuario (usuario_id),
    KEY idx_pedidos_estado (estado),
    KEY idx_pedidos_erp (erp_sincronizado),
    CONSTRAINT fk_pedidos_usuario FOREIGN KEY (usuario_id)
        REFERENCES usuarios (id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
