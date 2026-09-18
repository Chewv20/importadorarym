<?php

/**
 * Definición de rutas web.
 *
 * @var App\Core\Router $router
 */

use App\Controllers\HomeController;
use App\Controllers\CotizacionController;
use App\Controllers\ContactoController;
use App\Controllers\ProductoController;
use App\Controllers\PageController;
use App\Controllers\AuthController;
use App\Controllers\PortalController;
use App\Controllers\PerfilController;
use App\Controllers\PedidoController;
use App\Controllers\PedidoRecurrenteController;
use App\Controllers\PortalCotizacionController;
use App\Controllers\VerificacionController;
use App\Controllers\CodigoPostalController;
use App\Controllers\PasswordResetController;
use App\Controllers\Admin\DashboardController;
use App\Controllers\Admin\CotizacionController as AdminCotizacion;
use App\Controllers\Admin\ClienteController as AdminCliente;
use App\Controllers\Admin\ProductoController as AdminProducto;
use App\Controllers\Admin\CategoriaController as AdminCategoria;
use App\Controllers\Admin\UsuarioController as AdminUsuario;
use App\Controllers\Admin\RolController as AdminRol;
use App\Controllers\Admin\ConfiguracionCorreoController as AdminConfiguracionCorreo;
use App\Controllers\Admin\PedidoController as AdminPedido;
use App\Controllers\Admin\PedidoNuevoController as AdminPedidoNuevo;
use App\Controllers\Admin\AuditoriaController as AdminAuditoria;
use App\Controllers\Admin\ReporteController as AdminReporte;
use App\Controllers\Admin\EncuestaController as AdminEncuesta;
use App\Controllers\Admin\VisitaController as AdminVisita;
use App\Controllers\Admin\VacanteController as AdminVacante;
use App\Controllers\Admin\PostulacionController as AdminPostulacion;
use App\Controllers\Admin\ModalController as AdminModal;
use App\Controllers\Admin\ClienteLogoController as AdminLogo;
use App\Controllers\Admin\LogoProveedorController as AdminLogoProveedor;
use App\Controllers\Admin\ListaProductosController as AdminListaProductos;
use App\Controllers\Admin\ZonaController as AdminZona;
use App\Controllers\Admin\SaeSerieController as AdminSaeSerie;
use App\Controllers\RepartoController;

/* Landing */
$router->get('/', [HomeController::class, 'index']);

/* Formulario de cotización / contacto (leads) */
$router->post('/cotizar', [CotizacionController::class, 'store']);

/* Productos (catálogo dinámico) */
$router->get('/productos', [ProductoController::class, 'index']);
$router->get('/productos/{slug}', [ProductoController::class, 'categoria']);

/* Autocompletar de dirección por código postal (registro y perfil) */
$router->get('/codigo-postal/{cp}/buscar', [CodigoPostalController::class, 'buscar']);

/* Páginas de contenido */
$router->get('/nosotros', [PageController::class, 'nosotros']);
$router->get('/personalizacion', [PageController::class, 'personalizacion']);
$router->get('/reciclaje', [PageController::class, 'reciclaje']);
$router->get('/contacto', [ContactoController::class, 'index']);
$router->get('/aviso-de-privacidad', [PageController::class, 'avisoPrivacidad']);

/* Bolsa de trabajo (público) */
$router->get('/bolsa-de-trabajo', [App\Controllers\CarreraController::class, 'index']);
// Rutas fijas antes de la comodín {slug}: si no, "solicitud" se interpretaría como slug de vacante.
$router->get('/bolsa-de-trabajo/solicitud', [App\Controllers\CarreraController::class, 'solicitud']);
$router->post('/bolsa-de-trabajo/solicitud', [App\Controllers\CarreraController::class, 'enviarSolicitud']);
$router->get('/bolsa-de-trabajo/{slug}', [App\Controllers\CarreraController::class, 'vacante']);
$router->post('/bolsa-de-trabajo/{slug}', [App\Controllers\CarreraController::class, 'postular']);

/* ---- Kiosco de visitas (dispositivos autorizados) ----------------------- */
$router->get('/checador/activar/{token}', [App\Controllers\ChecadorController::class, 'activar']);
$router->get('/checador', [App\Controllers\ChecadorController::class, 'index']);
$router->post('/checador', [App\Controllers\ChecadorController::class, 'store']);

/* ---- Portal de clientes ------------------------------------------------- */

/* Autenticación (públicas) */
$router->get('/portal/login', [AuthController::class, 'showLogin']);
$router->post('/portal/login', [AuthController::class, 'login']);
$router->get('/portal/registro', [AuthController::class, 'showRegister']);
$router->post('/portal/registro', [AuthController::class, 'register']);
$router->post('/portal/logout', [AuthController::class, 'logout']);

/* Verificación de correo (enlace público; reenvío requiere sesión) */
$router->post('/portal/verificar/reenviar', [VerificacionController::class, 'reenviar']);
$router->get('/portal/verificar/{token}', [VerificacionController::class, 'verificar']);

/* Recuperación de contraseña (públicas) */
$router->get('/portal/recuperar', [PasswordResetController::class, 'showRequest']);
$router->post('/portal/recuperar', [PasswordResetController::class, 'request']);
$router->post('/portal/restablecer', [PasswordResetController::class, 'reset']);
$router->get('/portal/restablecer/{token}', [PasswordResetController::class, 'showReset']);

/* Panel y perfil (protegidas) */
$router->get('/portal', [PortalController::class, 'index']);
$router->get('/portal/perfil', [PerfilController::class, 'edit']);
$router->post('/portal/perfil', [PerfilController::class, 'update']);

/* Cotizaciones del portal (todo cliente, aprobado o no) */
$router->get('/portal/cotizar', [PortalCotizacionController::class, 'form']);
$router->post('/portal/cotizar/agregar', [PortalCotizacionController::class, 'agregar']);
$router->post('/portal/cotizar/quitar', [PortalCotizacionController::class, 'quitar']);
$router->post('/portal/cotizar', [PortalCotizacionController::class, 'enviar']);
$router->get('/portal/cotizaciones', [PortalCotizacionController::class, 'mis']);
$router->get('/portal/cotizaciones/{id}/imprimir', [PortalCotizacionController::class, 'imprimir']);
$router->post('/portal/cotizaciones/{id}/responder', [PortalCotizacionController::class, 'responder']);
$router->get('/portal/cotizaciones/{id}', [PortalCotizacionController::class, 'ver']);

/* Pedidos (protegidas) — /nuevo antes de /{id} para no ser capturada por el comodín */
$router->get('/portal/pedidos', [PedidoController::class, 'index']);
$router->get('/portal/pedidos/nuevo', [PedidoController::class, 'nuevo']);
$router->get('/portal/pedidos/confirmar', [PedidoController::class, 'confirmar']);
$router->post('/portal/pedidos', [PedidoController::class, 'store']);
$router->post('/portal/carrito/agregar', [PedidoController::class, 'agregar']);
$router->post('/portal/carrito/quitar', [PedidoController::class, 'quitar']);
$router->post('/portal/carrito/actualizar', [PedidoController::class, 'actualizarCantidad']);
$router->post('/portal/pedidos/{id}/repetir', [PedidoController::class, 'repetir']);
$router->post('/portal/pedidos/{id}/encuesta', [PedidoController::class, 'encuesta']);
$router->post('/portal/pedidos/{id}/envios/{envioId}/encuesta', [PedidoController::class, 'encuestaEntrega']);
$router->get('/portal/pedidos/{id}/facturas/{facturaId}', [PedidoController::class, 'descargarFactura']);
$router->post('/portal/pedidos/{id}/recurrente', [PedidoRecurrenteController::class, 'crear']);
$router->get('/portal/pedidos/{id}', [PedidoController::class, 'show']);

/* Pedidos recurrentes — /recurrentes antes de /{id} por el mismo motivo */
$router->get('/portal/recurrentes', [PedidoRecurrenteController::class, 'index']);
$router->post('/portal/recurrentes/{id}/pedir', [PedidoRecurrenteController::class, 'pedirAhora']);
$router->post('/portal/recurrentes/{id}/pausar', [PedidoRecurrenteController::class, 'pausar']);
$router->post('/portal/recurrentes/{id}/reanudar', [PedidoRecurrenteController::class, 'reanudar']);
$router->post('/portal/recurrentes/{id}/eliminar', [PedidoRecurrenteController::class, 'eliminar']);
$router->post('/portal/recurrentes/{id}/frecuencia', [PedidoRecurrenteController::class, 'actualizarFrecuencia']);
$router->get('/portal/recurrentes/{id}', [PedidoRecurrenteController::class, 'show']);

/* ---- Panel de administración -------------------------------------------- */
$router->get('/admin', [DashboardController::class, 'index']);

/* Cotizaciones */
$router->get('/admin/cotizaciones', [AdminCotizacion::class, 'index']);
$router->get('/admin/cotizaciones/{id}/imprimir', [AdminCotizacion::class, 'imprimir']);
$router->get('/admin/cotizaciones/{id}/logo', [AdminCotizacion::class, 'descargarLogo']);
$router->post('/admin/cotizaciones/{id}/item/{itemId}/quitar', [AdminCotizacion::class, 'quitarItem']);
$router->post('/admin/cotizaciones/{id}/item', [AdminCotizacion::class, 'agregarItem']);
$router->post('/admin/cotizaciones/{id}/convertir', [AdminCotizacion::class, 'convertir']);
$router->post('/admin/cotizaciones/{id}/estado', [AdminCotizacion::class, 'updateEstado']);
$router->get('/admin/cotizaciones/{id}', [AdminCotizacion::class, 'show']);
$router->post('/admin/cotizaciones/{id}', [AdminCotizacion::class, 'guardar']);

/* Clientes */
$router->get('/admin/clientes', [AdminCliente::class, 'index']);
$router->post('/admin/clientes/{id}/aprobar', [AdminCliente::class, 'aprobar']);
$router->post('/admin/clientes/{id}/activo', [AdminCliente::class, 'toggleActivo']);
$router->post('/admin/clientes/{id}/datos-sae', [AdminCliente::class, 'datosSae']);
$router->post('/admin/clientes/{id}/lista-productos', [AdminCliente::class, 'listaProductos']);
$router->post('/admin/clientes/{id}/portal-acceso', [AdminCliente::class, 'togglePortalAcceso']);

/* Productos */
$router->get('/admin/productos', [AdminProducto::class, 'index']);
$router->get('/admin/productos/buscar', [AdminProducto::class, 'buscar']);
$router->get('/admin/productos/importar', [AdminProducto::class, 'importForm']);
$router->get('/admin/productos/importar/plantilla', [AdminProducto::class, 'plantilla']);
$router->post('/admin/productos/importar', [AdminProducto::class, 'importar']);
$router->get('/admin/productos/nuevo', [AdminProducto::class, 'create']);
$router->post('/admin/productos', [AdminProducto::class, 'store']);
$router->get('/admin/productos/{id}/editar', [AdminProducto::class, 'edit']);
$router->post('/admin/productos/{id}/eliminar', [AdminProducto::class, 'delete']);
$router->post('/admin/productos/{id}/imagenes/{imgId}/eliminar', [AdminProducto::class, 'deleteImagen']);
$router->post('/admin/productos/{id}', [AdminProducto::class, 'update']);

/* Categorías */
$router->get('/admin/categorias', [AdminCategoria::class, 'index']);
$router->get('/admin/categorias/nueva', [AdminCategoria::class, 'create']);
$router->post('/admin/categorias', [AdminCategoria::class, 'store']);
$router->get('/admin/categorias/{id}/editar', [AdminCategoria::class, 'edit']);
$router->post('/admin/categorias/{id}/eliminar', [AdminCategoria::class, 'delete']);
$router->post('/admin/categorias/{id}', [AdminCategoria::class, 'update']);

/* Listas de productos por cliente (Fase 7.3) */
$router->get('/admin/listas-productos', [AdminListaProductos::class, 'index']);
$router->get('/admin/listas-productos/nueva', [AdminListaProductos::class, 'create']);
$router->post('/admin/listas-productos', [AdminListaProductos::class, 'store']);
$router->get('/admin/listas-productos/{id}/editar', [AdminListaProductos::class, 'edit']);
$router->post('/admin/listas-productos/{id}/eliminar', [AdminListaProductos::class, 'delete']);
$router->post('/admin/listas-productos/{id}/productos', [AdminListaProductos::class, 'agregarProducto']);
$router->post('/admin/listas-productos/{id}/productos/{productoId}/eliminar', [AdminListaProductos::class, 'quitarProducto']);
$router->post('/admin/listas-productos/{id}', [AdminListaProductos::class, 'update']);

$router->get('/admin/zonas', [AdminZona::class, 'index']);
$router->get('/admin/zonas/nueva', [AdminZona::class, 'create']);
$router->post('/admin/zonas', [AdminZona::class, 'store']);
$router->get('/admin/zonas/{id}/editar', [AdminZona::class, 'edit']);
$router->post('/admin/zonas/{id}/eliminar', [AdminZona::class, 'delete']);
$router->post('/admin/zonas/{id}', [AdminZona::class, 'update']);

/* Usuarios internos */
$router->get('/admin/usuarios', [AdminUsuario::class, 'index']);
$router->get('/admin/usuarios/nuevo', [AdminUsuario::class, 'create']);
$router->post('/admin/usuarios', [AdminUsuario::class, 'store']);
$router->get('/admin/usuarios/{id}/editar', [AdminUsuario::class, 'edit']);
$router->post('/admin/usuarios/{id}', [AdminUsuario::class, 'update']);

/* Pedidos */
$router->get('/admin/pedidos', [AdminPedido::class, 'index']);
$router->get('/admin/pedidos/exportar', [AdminPedido::class, 'exportarLote']);
$router->post('/admin/pedidos/exportar', [AdminPedido::class, 'procesarLote']);

/* Nuevo pedido a nombre de un cliente (Fase 7.9) — /nuevo antes de /{id} para no ser capturada por el comodín */
$router->get('/admin/pedidos/nuevo', [AdminPedidoNuevo::class, 'index']);
$router->get('/admin/pedidos/nuevo/confirmar', [AdminPedidoNuevo::class, 'confirmar']);
$router->get('/admin/pedidos/nuevo/clientes/buscar', [AdminPedidoNuevo::class, 'buscarClientes']);
$router->post('/admin/pedidos/nuevo/cliente', [AdminPedidoNuevo::class, 'seleccionarCliente']);
$router->post('/admin/pedidos/nuevo/cliente/cambiar', [AdminPedidoNuevo::class, 'cambiarCliente']);
$router->post('/admin/pedidos/nuevo/carrito/agregar', [AdminPedidoNuevo::class, 'agregar']);
$router->post('/admin/pedidos/nuevo/carrito/quitar', [AdminPedidoNuevo::class, 'quitar']);
$router->post('/admin/pedidos/nuevo/carrito/actualizar', [AdminPedidoNuevo::class, 'actualizarCantidad']);
$router->post('/admin/pedidos/nuevo', [AdminPedidoNuevo::class, 'store']);

$router->get('/admin/pedidos/{id}', [AdminPedido::class, 'show']);
$router->get('/admin/pedidos/{id}/exportar', [AdminPedido::class, 'exportarPedido']);
$router->post('/admin/pedidos/{id}/exportar', [AdminPedido::class, 'procesarExportacion']);
$router->post('/admin/pedidos/{id}/estado', [AdminPedido::class, 'updateEstado']);
$router->post('/admin/pedidos/{id}/erp', [AdminPedido::class, 'sincronizarErp']);
$router->post('/admin/pedidos/{id}/recordatorio-sae', [AdminPedido::class, 'programarRecordatorio']);
$router->post('/admin/envios/{envioId}/repartidor', [AdminPedido::class, 'asignarRepartidor']);
$router->get('/admin/envios/{envioId}/exportacion', [AdminPedido::class, 'redescargarEnvio']);
$router->post('/admin/envios/{envioId}/factura', [AdminPedido::class, 'subirFactura']);
$router->get('/admin/facturas/{facturaId}', [AdminPedido::class, 'descargarFactura']);
$router->post('/admin/facturas/{facturaId}/eliminar', [AdminPedido::class, 'eliminarFactura']);

/* Series de SAE (Fase 7.4) — reconciliación manual de los consecutivos por serie */
$router->get('/admin/series-sae', [AdminSaeSerie::class, 'index']);
$router->post('/admin/series-sae', [AdminSaeSerie::class, 'actualizar']);

/* Interfaz de reparto (Fase 7.7): pensada para el celular del repartidor, fuera de /admin. */
$router->get('/reparto', [RepartoController::class, 'index']);
$router->post('/reparto/{envioId}/en-ruta', [RepartoController::class, 'enRuta']);
$router->post('/reparto/{envioId}/entregado', [RepartoController::class, 'entregado']);

/* Logos de clientes (sitio) */
$router->get('/admin/logos-clientes', [AdminLogo::class, 'index']);
$router->get('/admin/logos-clientes/nuevo', [AdminLogo::class, 'create']);
$router->post('/admin/logos-clientes', [AdminLogo::class, 'store']);
$router->get('/admin/logos-clientes/{id}/editar', [AdminLogo::class, 'edit']);
$router->post('/admin/logos-clientes/{id}/eliminar', [AdminLogo::class, 'delete']);
$router->post('/admin/logos-clientes/{id}', [AdminLogo::class, 'update']);

/* Logos de proveedores (sitio) */
$router->get('/admin/logos-proveedores', [AdminLogoProveedor::class, 'index']);
$router->get('/admin/logos-proveedores/nuevo', [AdminLogoProveedor::class, 'create']);
$router->post('/admin/logos-proveedores', [AdminLogoProveedor::class, 'store']);
$router->get('/admin/logos-proveedores/{id}/editar', [AdminLogoProveedor::class, 'edit']);
$router->post('/admin/logos-proveedores/{id}/eliminar', [AdminLogoProveedor::class, 'delete']);
$router->post('/admin/logos-proveedores/{id}', [AdminLogoProveedor::class, 'update']);

/* Modales del sitio */
$router->get('/admin/modales', [AdminModal::class, 'index']);
$router->get('/admin/modales/nuevo', [AdminModal::class, 'create']);
$router->post('/admin/modales', [AdminModal::class, 'store']);
$router->get('/admin/modales/{id}/editar', [AdminModal::class, 'edit']);
$router->post('/admin/modales/{id}/eliminar', [AdminModal::class, 'delete']);
$router->post('/admin/modales/{id}', [AdminModal::class, 'update']);

/* Reportes exportables */
$router->get('/admin/reportes', [AdminReporte::class, 'index']);
$router->get('/admin/reportes/descargar', [AdminReporte::class, 'descargar']);

/* Encuestas de experiencia */
$router->get('/admin/encuestas', [AdminEncuesta::class, 'index']);
$router->get('/admin/encuestas/exportar', [AdminEncuesta::class, 'exportar']);
$router->get('/admin/encuestas/entregas', [AdminEncuesta::class, 'entregas']);
$router->get('/admin/encuestas/entregas/exportar', [AdminEncuesta::class, 'entregasExportar']);

/* Libreta de visitas */
$router->get('/admin/visitas', [AdminVisita::class, 'index']);
$router->get('/admin/visitas/exportar', [AdminVisita::class, 'exportar']);
$router->get('/admin/visitas/dispositivos', [AdminVisita::class, 'dispositivos']);
$router->post('/admin/visitas/dispositivos', [AdminVisita::class, 'guardarDispositivo']);
$router->post('/admin/visitas/dispositivos/{id}/revocar', [AdminVisita::class, 'revocarDispositivo']);
$router->post('/admin/visitas/dispositivos/{id}/regenerar', [AdminVisita::class, 'regenerarDispositivo']);
$router->post('/admin/visitas/dispositivos/{id}/oficina', [AdminVisita::class, 'asignarOficinaDispositivo']);
$router->get('/admin/visitas/oficinas', [AdminVisita::class, 'oficinas']);
$router->post('/admin/visitas/oficinas', [AdminVisita::class, 'guardarOficina']);
$router->post('/admin/visitas/oficinas/{id}/eliminar', [AdminVisita::class, 'eliminarOficina']);
$router->post('/admin/visitas/oficinas/{id}', [AdminVisita::class, 'actualizarOficina']);
$router->get('/admin/visitas/anfitriones', [AdminVisita::class, 'anfitriones']);
$router->post('/admin/visitas/anfitriones', [AdminVisita::class, 'guardarAnfitrion']);
$router->post('/admin/visitas/anfitriones/{id}/eliminar', [AdminVisita::class, 'eliminarAnfitrion']);
$router->post('/admin/visitas/anfitriones/{id}', [AdminVisita::class, 'actualizarAnfitrion']);

/* Bolsa de trabajo — vacantes */
$router->get('/admin/vacantes', [AdminVacante::class, 'index']);
$router->get('/admin/vacantes/nueva', [AdminVacante::class, 'create']);
$router->post('/admin/vacantes', [AdminVacante::class, 'store']);
$router->get('/admin/vacantes/{id}/editar', [AdminVacante::class, 'edit']);
$router->post('/admin/vacantes/{id}/eliminar', [AdminVacante::class, 'delete']);
$router->post('/admin/vacantes/{id}', [AdminVacante::class, 'update']);

/* Bolsa de trabajo — postulaciones */
$router->get('/admin/postulaciones', [AdminPostulacion::class, 'index']);
$router->get('/admin/postulaciones/{id}/cv', [AdminPostulacion::class, 'descargarCv']);
$router->post('/admin/postulaciones/{id}/estado', [AdminPostulacion::class, 'updateEstado']);
$router->post('/admin/postulaciones/{id}/cita', [AdminPostulacion::class, 'agendarCita']);
$router->get('/admin/postulaciones/{id}', [AdminPostulacion::class, 'show']);

/* Auditoría y errores */
$router->get('/admin/auditoria', [AdminAuditoria::class, 'index']);
$router->get('/admin/errores', [AdminAuditoria::class, 'errores']);
$router->post('/admin/errores/limpiar', [AdminAuditoria::class, 'limpiarErrores']);

/* Roles y permisos */
$router->get('/admin/roles', [AdminRol::class, 'index']);
$router->get('/admin/roles/{id}/editar', [AdminRol::class, 'edit']);
$router->post('/admin/roles/{id}', [AdminRol::class, 'update']);

/* Configuración de correo (Office 365) */
$router->get('/admin/configuracion-correo', [AdminConfiguracionCorreo::class, 'edit']);
$router->post('/admin/configuracion-correo', [AdminConfiguracionCorreo::class, 'update']);
$router->post('/admin/configuracion-correo/probar', [AdminConfiguracionCorreo::class, 'probar']);
