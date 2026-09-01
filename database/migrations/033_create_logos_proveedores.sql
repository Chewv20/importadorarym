-- Logos de proveedores/marcas que distribuye RYM, mostrados en el sitio público
-- (home y nosotros). Antes era un arreglo estático en partials/proveedores.php;
-- pasa a administrarse desde el panel, igual que clientes_logos.

CREATE TABLE IF NOT EXISTS logos_proveedores (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre     VARCHAR(150) NOT NULL,
    imagen     VARCHAR(255) NOT NULL,
    activo     TINYINT(1)   NOT NULL DEFAULT 1,
    orden      INT          NOT NULL DEFAULT 0,
    created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_logoprov_activo (activo, orden)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Permiso granular (grupo Contenido, asignado a admin).
INSERT IGNORE INTO permisos (clave, nombre, grupo) VALUES
    ('logos_proveedores.gestionar', 'Administrar logos de proveedores', 'Contenido');
INSERT IGNORE INTO rol_permiso (rol_id, permiso_id)
SELECT r.id, p.id FROM roles r JOIN permisos p ON p.clave = 'logos_proveedores.gestionar'
WHERE r.slug = 'admin';

-- Siembra con los proveedores ya existentes (archivos en assets/img/logos/proveedores/;
-- las subidas nuevas desde el panel van a uploads/proveedores/, igual que clientes_logos).
INSERT INTO logos_proveedores (nombre, imagen, activo, orden) VALUES
    ('Dart',                 'assets/img/logos/proveedores/dart.png', 1, 1),
    ('Solo',                 'assets/img/logos/proveedores/solo.png', 1, 2),
    ('Convermex',            'assets/img/logos/proveedores/convermex.png', 1, 3),
    ('Inix',                 'assets/img/logos/proveedores/inix.png', 1, 4),
    ('Jaguar Pactiv',        'assets/img/logos/proveedores/jaguar.png', 1, 5),
    ('Primo',                'assets/img/logos/proveedores/primo.png', 1, 6),
    ('Grupo Urpri',          'assets/img/logos/proveedores/grupo-urpri.png', 1, 7),
    ('International Paper',  'assets/img/logos/proveedores/international-paper.png', 1, 8),
    ('Classy',               'assets/img/logos/proveedores/classy.png', 1, 9),
    ('TekniPlex',            'assets/img/logos/proveedores/tekniplex.png', 1, 10),
    ('Tork',                 'assets/img/logos/proveedores/tork.png', 1, 11);
