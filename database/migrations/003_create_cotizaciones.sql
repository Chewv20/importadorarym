CREATE TABLE IF NOT EXISTS cotizaciones (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(120) NOT NULL,
    empresa VARCHAR(150) DEFAULT NULL,
    email VARCHAR(191) NOT NULL,
    telefono VARCHAR(30) DEFAULT NULL,
    producto_interes VARCHAR(150) DEFAULT NULL,
    mensaje TEXT DEFAULT NULL,
    estado ENUM('nuevo','contactado','cotizado','cerrado','descartado') NOT NULL DEFAULT 'nuevo',
    origen VARCHAR(50) NOT NULL DEFAULT 'landing',
    ip VARCHAR(45) DEFAULT NULL,
    user_agent VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_cotizaciones_estado (estado),
    KEY idx_cotizaciones_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
