-- Bitácora de auditoría: ingresos (login/logout) y cambios en el panel.
CREATE TABLE IF NOT EXISTS auditoria (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    usuario_id    INT UNSIGNED DEFAULT NULL,      -- NULL en logins fallidos o usuario borrado
    usuario_email VARCHAR(191) DEFAULT NULL,      -- copia para conservar el rastro
    tipo          VARCHAR(20)  NOT NULL,          -- 'ingreso' | 'cambio'
    accion        VARCHAR(40)  NOT NULL,          -- login, login_fallido, logout, crear, actualizar, eliminar, aprobar...
    entidad       VARCHAR(40)  DEFAULT NULL,      -- producto, categoria, cliente, usuario, pedido, rol...
    entidad_id    INT UNSIGNED DEFAULT NULL,
    descripcion   VARCHAR(255) DEFAULT NULL,
    ip            VARCHAR(45)  DEFAULT NULL,
    user_agent    VARCHAR(255) DEFAULT NULL,
    created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_tipo (tipo, created_at),
    KEY idx_usuario (usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Permiso granular para ver la auditoría y los errores (asignable por rol/usuario).
INSERT IGNORE INTO permisos (clave, nombre, grupo) VALUES
    ('auditoria.ver', 'Ver auditoría y errores', 'Administración');

-- Se lo damos al rol Administrador por defecto.
INSERT IGNORE INTO rol_permiso (rol_id, permiso_id)
SELECT r.id, p.id FROM roles r JOIN permisos p ON p.clave = 'auditoria.ver'
WHERE r.slug = 'admin';
