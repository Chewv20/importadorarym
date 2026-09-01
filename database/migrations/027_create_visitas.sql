-- Módulo "Libreta de visitas": kiosco de registro de visitantes en dispositivos
-- autorizados (recepción). Al registrarse una visita se notifica por correo al
-- anfitrión (persona visitada).

-- Dispositivos autorizados (tablets de recepción). Se arman una vez con un enlace
-- de activación de un solo uso y quedan autorizados por cookie (device token).
CREATE TABLE IF NOT EXISTS checador_dispositivos (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(80) NOT NULL,
    activacion_token_hash VARCHAR(64) DEFAULT NULL,   -- token del enlace (un solo uso); NULL tras activar
    device_token_hash VARCHAR(64) DEFAULT NULL,       -- token de la cookie del dispositivo
    activo TINYINT(1) NOT NULL DEFAULT 1,
    activado_en TIMESTAMP NULL DEFAULT NULL,
    ultimo_uso_en TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_checador_device (device_token_hash),
    KEY idx_checador_activacion (activacion_token_hash)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Anfitriones: empleados que pueden recibir visitas (no todos son usuarios del sistema).
CREATE TABLE IF NOT EXISTS anfitriones (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(120) NOT NULL,
    email VARCHAR(191) NOT NULL,
    area VARCHAR(100) DEFAULT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_anfitriones_activo (activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Registros de visita (solo entrada en v1).
CREATE TABLE IF NOT EXISTS visitas (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre_visitante VARCHAR(120) NOT NULL,
    empresa VARCHAR(150) DEFAULT NULL,
    telefono VARCHAR(30) DEFAULT NULL,
    num_personas SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    motivo VARCHAR(255) DEFAULT NULL,
    anfitrion_id INT UNSIGNED DEFAULT NULL,
    anfitrion_email VARCHAR(191) DEFAULT NULL,        -- copia para conservar el rastro
    dispositivo_id INT UNSIGNED DEFAULT NULL,
    ip VARCHAR(45) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_visitas_created (created_at),
    KEY idx_visitas_anfitrion (anfitrion_id),
    CONSTRAINT fk_visitas_anfitrion FOREIGN KEY (anfitrion_id)
        REFERENCES anfitriones (id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_visitas_dispositivo FOREIGN KEY (dispositivo_id)
        REFERENCES checador_dispositivos (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Permisos del módulo, asignados al rol Administrador.
INSERT IGNORE INTO permisos (clave, nombre, grupo) VALUES
    ('visitas.ver',       'Ver la libreta de visitas',            'Visitas'),
    ('visitas.gestionar', 'Gestionar dispositivos y anfitriones', 'Visitas');

INSERT IGNORE INTO rol_permiso (rol_id, permiso_id)
SELECT r.id, p.id FROM roles r JOIN permisos p ON p.clave IN ('visitas.ver', 'visitas.gestionar')
WHERE r.slug = 'admin';
