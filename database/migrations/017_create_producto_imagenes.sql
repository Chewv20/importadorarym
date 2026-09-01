-- Galería de imágenes por producto (hasta 5; el registro con menor `orden` es la principal).
CREATE TABLE IF NOT EXISTS producto_imagenes (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    producto_id  INT UNSIGNED NOT NULL,
    ruta         VARCHAR(255) NOT NULL,
    orden        INT NOT NULL DEFAULT 0,
    created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_producto (producto_id, orden),
    CONSTRAINT fk_prodimg_producto FOREIGN KEY (producto_id)
        REFERENCES productos (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
