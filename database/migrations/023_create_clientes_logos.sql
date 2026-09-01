-- Logos de clientes que se muestran en el sitio público (marquesina del home).
CREATE TABLE IF NOT EXISTS clientes_logos (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre     VARCHAR(150) NOT NULL,
    imagen     VARCHAR(255) NOT NULL,
    activo     TINYINT(1)   NOT NULL DEFAULT 1,
    orden      INT          NOT NULL DEFAULT 0,
    created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_logo_activo (activo, orden)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Permiso granular (grupo Contenido, asignado a admin).
INSERT IGNORE INTO permisos (clave, nombre, grupo) VALUES
    ('clientes_logos.gestionar', 'Administrar logos de clientes', 'Contenido');
INSERT IGNORE INTO rol_permiso (rol_id, permiso_id)
SELECT r.id, p.id FROM roles r JOIN permisos p ON p.clave = 'clientes_logos.gestionar'
WHERE r.slug = 'admin';

-- Siembra los logos ya existentes (versiones recortadas en assets/img/clientes/).
INSERT INTO clientes_logos (nombre, imagen, activo, orden) VALUES
    ('Cliente 01', 'assets/img/clientes/1.png', 1, 1),
    ('Cliente 02', 'assets/img/clientes/2.png', 1, 2),
    ('Cliente 03', 'assets/img/clientes/3.png', 1, 3),
    ('Cliente 04', 'assets/img/clientes/4.png', 1, 4),
    ('Cliente 05', 'assets/img/clientes/5.png', 1, 5),
    ('Cliente 06', 'assets/img/clientes/6.png', 1, 6),
    ('Cliente 07', 'assets/img/clientes/7.png', 1, 7),
    ('Cliente 08', 'assets/img/clientes/8.png', 1, 8),
    ('Cliente 09', 'assets/img/clientes/9.png', 1, 9),
    ('Cliente 10', 'assets/img/clientes/10.png', 1, 10),
    ('Cliente 11', 'assets/img/clientes/11.png', 1, 11),
    ('Cliente 12', 'assets/img/clientes/12.png', 1, 12),
    ('Cliente 13', 'assets/img/clientes/13.png', 1, 13),
    ('Cliente 14', 'assets/img/clientes/14.png', 1, 14),
    ('Cliente 15', 'assets/img/clientes/15.png', 1, 15),
    ('Cliente 16', 'assets/img/clientes/16.png', 1, 16),
    ('Cliente 17', 'assets/img/clientes/17.png', 1, 17),
    ('Cliente 18', 'assets/img/clientes/18.png', 1, 18),
    ('Cliente 19', 'assets/img/clientes/19.png', 1, 19),
    ('Cliente 20', 'assets/img/clientes/20.png', 1, 20);
