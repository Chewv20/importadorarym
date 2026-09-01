-- Reemplaza la columna ENUM `rol` por una relación a la tabla `roles`.
ALTER TABLE usuarios
    ADD COLUMN rol_id INT UNSIGNED NULL AFTER rol,
    ADD KEY idx_usuarios_rol_id (rol_id),
    ADD CONSTRAINT fk_usuarios_rol FOREIGN KEY (rol_id) REFERENCES roles (id) ON DELETE SET NULL ON UPDATE CASCADE;

-- Migra los roles existentes (admin/editor/cliente) a rol_id.
UPDATE usuarios u JOIN roles r ON r.slug = u.rol SET u.rol_id = r.id WHERE u.rol_id IS NULL;

ALTER TABLE usuarios DROP COLUMN rol;
