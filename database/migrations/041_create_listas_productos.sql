-- Fase 7.3: lista de productos específica por cliente. Reemplaza, por ahora, la
-- idea pausada de "precios por cliente" con algo más simple: qué productos puede
-- ver/pedir un cliente, no a qué precio. NULL en usuarios.lista_productos_id =
-- el cliente ve el catálogo completo (comportamiento de siempre, sin cambios).

CREATE TABLE IF NOT EXISTS listas_productos (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(120) NOT NULL,
    descripcion VARCHAR(255) DEFAULT NULL,
    activa TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS listas_productos_items (
    lista_id INT UNSIGNED NOT NULL,
    producto_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (lista_id, producto_id),
    KEY idx_lpi_producto (producto_id),
    CONSTRAINT fk_lpi_lista FOREIGN KEY (lista_id)
        REFERENCES listas_productos (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_lpi_producto FOREIGN KEY (producto_id)
        REFERENCES productos (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Si se borra la lista, el cliente vuelve a ver el catálogo completo (no se queda
-- huérfano ni bloqueado): ON DELETE SET NULL.
ALTER TABLE usuarios
    ADD COLUMN lista_productos_id INT UNSIGNED DEFAULT NULL AFTER vendedor_id,
    ADD KEY idx_usuarios_lista_productos (lista_productos_id),
    ADD CONSTRAINT fk_usuarios_lista_productos FOREIGN KEY (lista_productos_id)
        REFERENCES listas_productos (id) ON DELETE SET NULL ON UPDATE CASCADE;

INSERT IGNORE INTO permisos (clave, nombre, grupo) VALUES
    ('listas.gestionar', 'Gestionar listas de productos por cliente', 'Productos');

INSERT IGNORE INTO rol_permiso (rol_id, permiso_id)
SELECT r.id, p.id FROM roles r JOIN permisos p ON p.clave = 'listas.gestionar'
WHERE r.slug = 'admin';
