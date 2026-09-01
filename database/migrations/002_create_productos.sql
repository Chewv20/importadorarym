CREATE TABLE IF NOT EXISTS productos (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    categoria_id INT UNSIGNED DEFAULT NULL,
    nombre VARCHAR(150) NOT NULL,
    slug VARCHAR(180) NOT NULL,
    descripcion TEXT DEFAULT NULL,
    sku VARCHAR(60) DEFAULT NULL,
    precio DECIMAL(10,2) DEFAULT NULL,
    unidad VARCHAR(40) DEFAULT NULL,
    imagen VARCHAR(255) DEFAULT NULL,
    destacado TINYINT(1) NOT NULL DEFAULT 0,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    orden INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_productos_slug (slug),
    KEY idx_productos_categoria (categoria_id),
    KEY idx_productos_sku (sku),
    CONSTRAINT fk_productos_categoria FOREIGN KEY (categoria_id)
        REFERENCES categorias (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
