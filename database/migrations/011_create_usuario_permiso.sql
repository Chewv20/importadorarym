-- Overrides de permisos por usuario (concedido=1 concede, concedido=0 revoca).
CREATE TABLE IF NOT EXISTS usuario_permiso (
    usuario_id INT UNSIGNED NOT NULL,
    permiso_id INT UNSIGNED NOT NULL,
    concedido TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (usuario_id, permiso_id),
    KEY idx_up_permiso (permiso_id),
    CONSTRAINT fk_up_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_up_permiso FOREIGN KEY (permiso_id) REFERENCES permisos (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
