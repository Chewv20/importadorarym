-- Fase 7.8: encuesta de satisfacción de la ENTREGA física, ligada al envío
-- (pedido_envios) que se marcó "entregado" en 7.7 — distinta de encuestas_pedido
-- (satisfacción del proceso de COMPRA, una por pedido completo). Un pedido con
-- dos envíos puede tener dos encuestas de entrega, una por cada uno.
-- Reutiliza el permiso 'encuestas.ver' ya existente (misma sección del panel).

CREATE TABLE IF NOT EXISTS envio_encuestas (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    envio_id INT UNSIGNED NOT NULL,
    usuario_id INT UNSIGNED DEFAULT NULL,
    llego_completo TINYINT(1) NOT NULL,
    que_falto VARCHAR(500) DEFAULT NULL,
    satisfaccion TINYINT UNSIGNED NOT NULL,
    comentario TEXT DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_envio_encuesta (envio_id),
    KEY idx_envio_encuesta_created (created_at),
    CONSTRAINT fk_envio_encuesta_envio FOREIGN KEY (envio_id)
        REFERENCES pedido_envios (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_envio_encuesta_usuario FOREIGN KEY (usuario_id)
        REFERENCES usuarios (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
