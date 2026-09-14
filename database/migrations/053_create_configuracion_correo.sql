-- Configuración de correo saliente vía Microsoft Graph (Office 365, app-only).
-- Tabla de una sola fila lógica, editable desde el panel (App\Controllers\
-- Admin\ConfiguracionCorreoController). El client_secret se guarda cifrado
-- (App\Core\Crypto) — nunca en claro. token_cache/token_expira_en cachean el
-- access_token de Graph (vigencia ~60-90 min) para no pedir uno nuevo en cada
-- envío, sin necesitar infraestructura de caché adicional (Composer/Redis).

CREATE TABLE IF NOT EXISTS configuracion_correo (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    tenant_id VARCHAR(100) NOT NULL,
    client_id VARCHAR(100) NOT NULL,
    client_secret_cifrado TEXT NOT NULL,
    mailbox VARCHAR(190) NOT NULL,
    remitente_nombre VARCHAR(120) NOT NULL DEFAULT 'Importadora RYM',
    activo TINYINT(1) NOT NULL DEFAULT 0,
    token_cache TEXT DEFAULT NULL,
    token_expira_en DATETIME DEFAULT NULL,
    actualizado_por INT UNSIGNED DEFAULT NULL,
    actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_configuracion_correo_activo (activo),
    CONSTRAINT fk_configuracion_correo_usuario FOREIGN KEY (actualizado_por)
        REFERENCES usuarios (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO permisos (clave, nombre, grupo) VALUES
    ('configuracion.correo', 'Configurar el correo saliente (Office 365)', 'Administración');

INSERT IGNORE INTO rol_permiso (rol_id, permiso_id)
SELECT r.id, p.id FROM roles r JOIN permisos p ON p.clave = 'configuracion.correo'
WHERE r.slug = 'admin';
