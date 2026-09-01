-- Modales promocionales que se muestran en el sitio público (no en panel ni portal).
CREATE TABLE IF NOT EXISTS modales (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    titulo     VARCHAR(150) NOT NULL,
    imagen     VARCHAR(255) NOT NULL,
    enlace     VARCHAR(255) DEFAULT NULL,
    activo     TINYINT(1)   NOT NULL DEFAULT 1,
    orden      INT          NOT NULL DEFAULT 0,
    created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_modal_activo (activo, orden)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Permiso granular para administrar los modales.
INSERT IGNORE INTO permisos (clave, nombre, grupo) VALUES
    ('modales.gestionar', 'Administrar modales del sitio', 'Contenido');

INSERT IGNORE INTO rol_permiso (rol_id, permiso_id)
SELECT r.id, p.id FROM roles r JOIN permisos p ON p.clave = 'modales.gestionar'
WHERE r.slug = 'admin';
