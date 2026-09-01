-- Encuesta de experiencia de pedido: opinión del cliente sobre el proceso de
-- realizar un pedido. Una respuesta por pedido.

CREATE TABLE IF NOT EXISTS encuestas_pedido (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    pedido_id INT UNSIGNED NOT NULL,
    usuario_id INT UNSIGNED DEFAULT NULL,
    satisfaccion TINYINT UNSIGNED NOT NULL,     -- 1-5
    facilidad TINYINT UNSIGNED NOT NULL,        -- 1-5
    nps TINYINT UNSIGNED DEFAULT NULL,          -- 0-10 (recomendación)
    comentario TEXT DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_encuesta_pedido (pedido_id),   -- una respuesta por pedido
    KEY idx_encuesta_created (created_at),
    CONSTRAINT fk_encuesta_pedido FOREIGN KEY (pedido_id)
        REFERENCES pedidos (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_encuesta_usuario FOREIGN KEY (usuario_id)
        REFERENCES usuarios (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Permiso para ver los resultados en el panel.
INSERT IGNORE INTO permisos (clave, nombre, grupo) VALUES
    ('encuestas.ver', 'Ver encuestas de experiencia', 'Encuestas');

INSERT IGNORE INTO rol_permiso (rol_id, permiso_id)
SELECT r.id, p.id FROM roles r JOIN permisos p ON p.clave = 'encuestas.ver'
WHERE r.slug = 'admin';
