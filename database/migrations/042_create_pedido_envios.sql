-- Fase 7.5 (+ base para 7.7/7.8): "envío/remesa" de un pedido — un pedido no
-- siempre se exporta/reparte/entrega todo junto, puede ir por partes. Una sola
-- tabla pedido_envios sirve a los tres puntos: qué partidas incluyó (7.5), su
-- tracking de ruta (7.7, columnas ya aquí pero sin uso hasta esa fase) y su
-- encuesta de entrega (7.8, tabla propia que referenciará envio_id).

CREATE TABLE IF NOT EXISTS zonas (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(120) NOT NULL,
    activa TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pedido_envios (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    pedido_id INT UNSIGNED NOT NULL,
    exportado_por INT UNSIGNED DEFAULT NULL,
    -- Sin uso hasta 7.7 (asignación manual de reparto); se agregan desde ahora
    -- para no requerir otra migración cuando se construya esa fase.
    zona_id INT UNSIGNED DEFAULT NULL,
    repartidor_id INT UNSIGNED DEFAULT NULL,
    evento ENUM('en_ruta', 'entregado') DEFAULT NULL,
    eta DATETIME DEFAULT NULL,
    notas VARCHAR(500) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_envios_pedido (pedido_id),
    KEY idx_envios_zona (zona_id),
    KEY idx_envios_repartidor (repartidor_id),
    CONSTRAINT fk_envios_pedido FOREIGN KEY (pedido_id)
        REFERENCES pedidos (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_envios_exportador FOREIGN KEY (exportado_por)
        REFERENCES usuarios (id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_envios_zona FOREIGN KEY (zona_id)
        REFERENCES zonas (id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_envios_repartidor FOREIGN KEY (repartidor_id)
        REFERENCES usuarios (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Qué partidas van en cada envío: una partida pertenece a lo más a un envío
-- (envio_id NULL = todavía no se exportó). Si se borra el envío, la partida
-- vuelve a quedar pendiente en vez de romperse (ON DELETE SET NULL).
ALTER TABLE pedido_items
    ADD COLUMN envio_id INT UNSIGNED DEFAULT NULL AFTER importe,
    ADD COLUMN exportado_en TIMESTAMP NULL DEFAULT NULL AFTER envio_id,
    ADD COLUMN recordatorio_en DATE DEFAULT NULL AFTER exportado_en,
    ADD KEY idx_items_envio (envio_id),
    ADD CONSTRAINT fk_items_envio FOREIGN KEY (envio_id)
        REFERENCES pedido_envios (id) ON DELETE SET NULL ON UPDATE CASCADE;

-- Estado 'parcial': el pedido tiene al menos una partida exportada y al menos
-- una pendiente. No rompe filas existentes, solo agrega un valor posible.
ALTER TABLE pedidos
    MODIFY COLUMN estado ENUM('borrador', 'enviado', 'en_proceso', 'parcial', 'sincronizado', 'cancelado')
        NOT NULL DEFAULT 'borrador';
