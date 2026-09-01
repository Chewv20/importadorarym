-- Fase 7.9: el vendedor (o el gerente de ventas, sin la restricción de alcance)
-- puede crear un pedido a nombre de un cliente desde el panel. El alcance de
-- QUÉ cliente puede elegir ya lo resuelve 'ventas.solo_asignados' existente
-- (BaseController::vendedorScope()); este permiso solo habilita la funcionalidad.
INSERT IGNORE INTO permisos (clave, nombre, grupo) VALUES
    ('pedidos.crear_para_cliente', 'Crear pedidos a nombre de un cliente', 'Pedidos');

INSERT IGNORE INTO rol_permiso (rol_id, permiso_id)
SELECT r.id, p.id FROM roles r JOIN permisos p ON p.clave = 'pedidos.crear_para_cliente'
WHERE r.slug = 'ventas';
