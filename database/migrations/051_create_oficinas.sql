-- Oficinas / sucursales para separar la bitácora de visitas ("checada").
-- Cada dispositivo de recepción pertenece a una oficina; al registrarse una
-- visita se guarda una copia de la oficina (conserva el rastro si el equipo se
-- reubica). Los usuarios se asignan a una oficina y solo ven la bitácora de esa
-- oficina, salvo que tengan el permiso 'visitas.ver_todas'.
--
-- Cada cambio va en su propio ALTER con IF NOT EXISTS para que la migración se
-- pueda re-ejecutar sin romper si una corrida anterior quedó a medias.

CREATE TABLE IF NOT EXISTS oficinas (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(120) NOT NULL,
    activa TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_oficinas_activa (activa)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE checador_dispositivos
    ADD COLUMN IF NOT EXISTS oficina_id INT UNSIGNED DEFAULT NULL AFTER nombre;
ALTER TABLE checador_dispositivos
    ADD KEY IF NOT EXISTS idx_checador_oficina (oficina_id);
ALTER TABLE checador_dispositivos
    ADD FOREIGN KEY IF NOT EXISTS fk_checador_oficina (oficina_id)
        REFERENCES oficinas (id) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE visitas
    ADD COLUMN IF NOT EXISTS oficina_id INT UNSIGNED DEFAULT NULL AFTER dispositivo_id;
ALTER TABLE visitas
    ADD KEY IF NOT EXISTS idx_visitas_oficina (oficina_id);
ALTER TABLE visitas
    ADD FOREIGN KEY IF NOT EXISTS fk_visitas_oficina (oficina_id)
        REFERENCES oficinas (id) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE usuarios
    ADD COLUMN IF NOT EXISTS oficina_id INT UNSIGNED DEFAULT NULL AFTER rol_id;
ALTER TABLE usuarios
    ADD KEY IF NOT EXISTS idx_usuarios_oficina (oficina_id);
ALTER TABLE usuarios
    ADD FOREIGN KEY IF NOT EXISTS fk_usuarios_oficina (oficina_id)
        REFERENCES oficinas (id) ON DELETE SET NULL ON UPDATE CASCADE;

-- El permiso de gestión ahora también cubre las oficinas.
UPDATE permisos SET nombre = 'Gestionar oficinas, dispositivos y anfitriones'
 WHERE clave = 'visitas.gestionar';

-- Permiso nuevo: sin él, un usuario con 'visitas.ver' solo ve su propia oficina.
INSERT IGNORE INTO permisos (clave, nombre, grupo) VALUES
    ('visitas.ver_todas', 'Ver la bitácora de todas las oficinas', 'Visitas');

INSERT IGNORE INTO rol_permiso (rol_id, permiso_id)
SELECT r.id, p.id FROM roles r JOIN permisos p ON p.clave = 'visitas.ver_todas'
WHERE r.slug = 'admin';
