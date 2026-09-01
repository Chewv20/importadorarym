-- Panel de vendedor: permiso que restringe a un usuario a ver ÚNICAMENTE sus
-- clientes asignados (usuarios.vendedor_id) y los pedidos/cotizaciones de esos
-- clientes. Sin este permiso, el usuario ve todo (comportamiento de admin).
-- Un "gerente de ventas" que deba ver el total se resuelve revocándole este
-- permiso con un override por usuario (usuario_permiso.concedido = 0).
INSERT IGNORE INTO permisos (clave, nombre, grupo) VALUES
    ('ventas.solo_asignados', 'Ver solo clientes asignados', 'Clientes');

-- Se asigna al rol Ventas por defecto: los vendedores existentes pasan a ver
-- solo lo suyo automáticamente.
INSERT IGNORE INTO rol_permiso (rol_id, permiso_id)
SELECT r.id, p.id FROM roles r JOIN permisos p ON p.clave = 'ventas.solo_asignados'
WHERE r.slug = 'ventas';
