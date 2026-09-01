-- Reportes exportables (ventas): permiso para acceder a /admin/reportes y
-- descargar los reportes en Excel. Se asigna a Administrador y a Ventas.
INSERT IGNORE INTO permisos (clave, nombre, grupo) VALUES
    ('reportes.ver', 'Ver y descargar reportes', 'Administración');

INSERT IGNORE INTO rol_permiso (rol_id, permiso_id)
SELECT r.id, p.id FROM roles r JOIN permisos p ON p.clave = 'reportes.ver'
WHERE r.slug IN ('admin', 'ventas');
