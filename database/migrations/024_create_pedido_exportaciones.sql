-- Historial de exportaciones de pedidos a SAE (individual o por lote).
CREATE TABLE IF NOT EXISTS pedido_exportaciones (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    pedido_id     INT UNSIGNED NOT NULL,
    usuario_id    INT UNSIGNED DEFAULT NULL,
    usuario_email VARCHAR(191) DEFAULT NULL,
    tipo          VARCHAR(20)  NOT NULL DEFAULT 'individual', -- individual | lote
    created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_pexp (pedido_id, created_at),
    CONSTRAINT fk_pexp_pedido FOREIGN KEY (pedido_id)
        REFERENCES pedidos (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
