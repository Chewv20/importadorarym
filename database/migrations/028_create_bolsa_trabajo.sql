-- Módulo "Bolsa de trabajo": publicación de vacantes y postulaciones con CV.
-- Los archivos de CV se guardan en storage/cvs/ (fuera de la web); aquí solo
-- se guarda el nombre de archivo.

CREATE TABLE IF NOT EXISTS vacantes (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    titulo VARCHAR(150) NOT NULL,
    slug VARCHAR(180) NOT NULL,
    area VARCHAR(100) DEFAULT NULL,
    ubicacion VARCHAR(150) DEFAULT NULL,
    tipo ENUM('tiempo_completo','medio_tiempo','temporal','practicas') NOT NULL DEFAULT 'tiempo_completo',
    descripcion TEXT DEFAULT NULL,
    requisitos TEXT DEFAULT NULL,
    estado ENUM('abierta','cerrada') NOT NULL DEFAULT 'abierta',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_vacantes_slug (slug),
    KEY idx_vacantes_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS postulaciones (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    vacante_id INT UNSIGNED DEFAULT NULL,
    nombre VARCHAR(120) NOT NULL,
    email VARCHAR(191) NOT NULL,
    telefono VARCHAR(30) DEFAULT NULL,
    mensaje TEXT DEFAULT NULL,
    cv_archivo VARCHAR(255) DEFAULT NULL,      -- nombre del archivo en storage/cvs/
    estado ENUM('recibida','en_revision','entrevista','rechazada','contratada') NOT NULL DEFAULT 'recibida',
    cita_at DATETIME NULL DEFAULT NULL,        -- fecha/hora de la entrevista agendada
    ip VARCHAR(45) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_postulaciones_vacante (vacante_id),
    KEY idx_postulaciones_estado (estado),
    KEY idx_postulaciones_created (created_at),
    CONSTRAINT fk_postulaciones_vacante FOREIGN KEY (vacante_id)
        REFERENCES vacantes (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Permisos del módulo, asignados al rol Administrador.
INSERT IGNORE INTO permisos (clave, nombre, grupo) VALUES
    ('vacantes.gestionar',       'Gestionar vacantes',          'Bolsa de trabajo'),
    ('postulaciones.ver',        'Ver postulaciones',           'Bolsa de trabajo'),
    ('postulaciones.gestionar',  'Gestionar postulaciones',     'Bolsa de trabajo');

INSERT IGNORE INTO rol_permiso (rol_id, permiso_id)
SELECT r.id, p.id FROM roles r JOIN permisos p
    ON p.clave IN ('vacantes.gestionar', 'postulaciones.ver', 'postulaciones.gestionar')
WHERE r.slug = 'admin';
