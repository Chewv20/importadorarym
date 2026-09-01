-- Fase 7.7: tracking de envío (en ruta / entregado) + interfaz de repartidor.
-- El esquema (tabla `zonas` y las columnas de `pedido_envios`) ya existe desde
-- la migración 042 (Fase 7.5) — aquí solo se agregan los permisos nuevos.

INSERT IGNORE INTO permisos (clave, nombre, grupo) VALUES
    ('pedidos.tracking', 'Interfaz de reparto (marcar en ruta / entregado)', 'Pedidos'),
    ('zonas.gestionar', 'Gestionar catálogo de zonas de reparto', 'Pedidos');

-- Almacén / Logística: son quienes reparten (rol ya existente, "Prepara y despacha pedidos").
INSERT IGNORE INTO rol_permiso (rol_id, permiso_id)
SELECT r.id, p.id FROM roles r JOIN permisos p ON p.clave = 'pedidos.tracking'
WHERE r.slug = 'almacen';

-- Zonas es catálogo de configuración: solo administración lo edita, igual que listas.gestionar.
INSERT IGNORE INTO rol_permiso (rol_id, permiso_id)
SELECT r.id, p.id FROM roles r JOIN permisos p ON p.clave = 'zonas.gestionar'
WHERE r.slug = 'admin';
