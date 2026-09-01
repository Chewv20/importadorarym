-- Las estadísticas del dashboard (tarjetas de métricas y gráficas) se veían con
-- solo 'admin.acceder' — cualquier rol interno (editor, almacén) las veía aunque
-- no le correspondiera. Se acota con un permiso propio, administrable desde
-- Roles y permisos como cualquier otro.

INSERT IGNORE INTO permisos (clave, nombre, grupo) VALUES
    ('dashboard.ver', 'Ver estadísticas del dashboard', 'Administración');

INSERT IGNORE INTO rol_permiso (rol_id, permiso_id)
SELECT r.id, p.id FROM roles r JOIN permisos p ON p.clave = 'dashboard.ver'
WHERE r.slug IN ('admin', 'ventas');
