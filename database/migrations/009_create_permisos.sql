CREATE TABLE IF NOT EXISTS permisos (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    clave VARCHAR(80) NOT NULL,
    nombre VARCHAR(120) NOT NULL,
    grupo VARCHAR(60) NOT NULL DEFAULT 'General',
    PRIMARY KEY (id),
    UNIQUE KEY uq_permisos_clave (clave)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
