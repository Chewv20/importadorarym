-- Roles del sistema
INSERT IGNORE INTO roles (nombre, slug, descripcion, es_sistema) VALUES
    ('Administrador',          'admin',    'Acceso total al sistema',                       1),
    ('Ventas / Cotizaciones',  'ventas',   'Atiende cotizaciones, clientes y pedidos',      1),
    ('Almacén / Logística',    'almacen',  'Prepara y despacha pedidos',                    1),
    ('Editor de contenido',    'editor',   'Gestiona catálogo y contenido del sitio',       1),
    ('Cliente',                'cliente',  'Cliente del portal de pedidos',                 1);

-- Catálogo de permisos
INSERT IGNORE INTO permisos (clave, nombre, grupo) VALUES
    ('admin.acceder',             'Acceder al panel de administración', 'Administración'),
    ('usuarios.ver',              'Ver usuarios internos',              'Administración'),
    ('usuarios.gestionar',        'Crear y editar usuarios internos',   'Administración'),
    ('roles.gestionar',           'Gestionar roles y permisos',         'Administración'),
    ('clientes.ver',              'Ver clientes',                       'Clientes'),
    ('clientes.aprobar',          'Aprobar y activar clientes',         'Clientes'),
    ('cotizaciones.ver',          'Ver cotizaciones',                   'Cotizaciones'),
    ('cotizaciones.gestionar',    'Dar seguimiento a cotizaciones',     'Cotizaciones'),
    ('pedidos.ver_todos',         'Ver todos los pedidos',              'Pedidos'),
    ('pedidos.actualizar_estado', 'Actualizar estado de pedidos',       'Pedidos'),
    ('pedidos.sincronizar_erp',   'Sincronizar pedidos al ERP',         'Pedidos'),
    ('productos.ver',             'Ver catálogo (admin)',               'Productos'),
    ('productos.crear',           'Crear productos',                    'Productos'),
    ('productos.editar',          'Editar productos',                   'Productos'),
    ('productos.eliminar',        'Eliminar productos',                 'Productos'),
    ('categorias.gestionar',      'Gestionar categorías',               'Productos'),
    ('portal.acceder',            'Acceder al portal de clientes',      'Portal'),
    ('pedidos.ver_propios',       'Ver sus propios pedidos',            'Portal'),
    ('pedidos.crear',             'Crear pedidos',                      'Portal');

-- Administrador: todos los permisos
INSERT IGNORE INTO rol_permiso (rol_id, permiso_id)
SELECT r.id, p.id FROM roles r CROSS JOIN permisos p WHERE r.slug = 'admin';

-- Cliente
INSERT IGNORE INTO rol_permiso (rol_id, permiso_id)
SELECT r.id, p.id FROM roles r JOIN permisos p
    ON p.clave IN ('portal.acceder', 'pedidos.ver_propios', 'pedidos.crear')
WHERE r.slug = 'cliente';

-- Ventas / Cotizaciones
INSERT IGNORE INTO rol_permiso (rol_id, permiso_id)
SELECT r.id, p.id FROM roles r JOIN permisos p
    ON p.clave IN ('admin.acceder', 'clientes.ver', 'clientes.aprobar', 'cotizaciones.ver',
                   'cotizaciones.gestionar', 'pedidos.ver_todos', 'pedidos.actualizar_estado',
                   'pedidos.sincronizar_erp')
WHERE r.slug = 'ventas';

-- Almacén / Logística
INSERT IGNORE INTO rol_permiso (rol_id, permiso_id)
SELECT r.id, p.id FROM roles r JOIN permisos p
    ON p.clave IN ('admin.acceder', 'pedidos.ver_todos', 'pedidos.actualizar_estado')
WHERE r.slug = 'almacen';

-- Editor de contenido
INSERT IGNORE INTO rol_permiso (rol_id, permiso_id)
SELECT r.id, p.id FROM roles r JOIN permisos p
    ON p.clave IN ('admin.acceder', 'productos.ver', 'productos.crear', 'productos.editar',
                   'productos.eliminar', 'categorias.gestionar')
WHERE r.slug = 'editor';
