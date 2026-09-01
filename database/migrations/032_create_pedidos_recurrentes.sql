-- Pedidos recurrentes: el cliente programa un pedido como plantilla y recibe un
-- recordatorio por correo cada N días para volver a levantarlo. Independiente de
-- cualquier pedido concreto (uno la origina, pero puede seguir existiendo aunque
-- ese pedido cambie de estado o se cancele).

CREATE TABLE IF NOT EXISTS pedidos_recurrentes (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    usuario_id INT UNSIGNED NOT NULL,
    origen_pedido_id INT UNSIGNED DEFAULT NULL,   -- pedido del que se copiaron las partidas (informativo)
    nombre VARCHAR(120) DEFAULT NULL,             -- etiqueta opcional ("Pedido mensual de vasos")
    frecuencia_dias SMALLINT UNSIGNED NOT NULL,   -- controlado en la app: 7/15/30/45/60/90
    activo TINYINT(1) NOT NULL DEFAULT 1,
    proximo_recordatorio_en DATE NOT NULL,
    ultimo_recordatorio_en TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_pedrec_usuario (usuario_id),
    KEY idx_pedrec_pendientes (activo, proximo_recordatorio_en),
    CONSTRAINT fk_pedrec_usuario FOREIGN KEY (usuario_id)
        REFERENCES usuarios (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_pedrec_origen FOREIGN KEY (origen_pedido_id)
        REFERENCES pedidos (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pedidos_recurrentes_items (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    pedido_recurrente_id INT UNSIGNED NOT NULL,
    producto_id INT UNSIGNED DEFAULT NULL,
    -- Snapshot al programar la plantilla (por si el producto cambia o se elimina).
    sku VARCHAR(60) DEFAULT NULL,
    nombre VARCHAR(150) NOT NULL,
    cantidad INT UNSIGNED NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    KEY idx_pedrecitem_padre (pedido_recurrente_id),
    CONSTRAINT fk_pedrecitem_padre FOREIGN KEY (pedido_recurrente_id)
        REFERENCES pedidos_recurrentes (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_pedrecitem_producto FOREIGN KEY (producto_id)
        REFERENCES productos (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
