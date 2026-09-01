-- Respaldo de `importadorarym_demo` — 2026-07-29 23:57:54
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS `anfitriones`;
CREATE TABLE `anfitriones` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `area` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_anfitriones_activo` (`activo`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `anfitriones` (`id`, `nombre`, `email`, `area`, `activo`, `created_at`) VALUES ('1', 'Laura Gómez', 'laura.gomez@demo.rym', 'Dirección', '1', '2026-07-29 23:53:25');
INSERT INTO `anfitriones` (`id`, `nombre`, `email`, `area`, `activo`, `created_at`) VALUES ('2', 'Carlos Ruiz', 'carlos.ruiz@demo.rym', 'Compras', '1', '2026-07-29 23:53:25');
INSERT INTO `anfitriones` (`id`, `nombre`, `email`, `area`, `activo`, `created_at`) VALUES ('3', 'Ana Torres', 'ana.torres@demo.rym', 'Ventas', '1', '2026-07-29 23:53:25');
INSERT INTO `anfitriones` (`id`, `nombre`, `email`, `area`, `activo`, `created_at`) VALUES ('4', 'Miguel Ángel Díaz', 'miguel.diaz@demo.rym', 'Almacén', '1', '2026-07-29 23:53:25');
INSERT INTO `anfitriones` (`id`, `nombre`, `email`, `area`, `activo`, `created_at`) VALUES ('5', 'Recepción General', 'recepcion@demo.rym', 'Recepción', '1', '2026-07-29 23:53:25');

DROP TABLE IF EXISTS `auditoria`;
CREATE TABLE `auditoria` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `usuario_id` int(10) unsigned DEFAULT NULL,
  `usuario_email` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tipo` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `accion` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `entidad` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `entidad_id` int(10) unsigned DEFAULT NULL,
  `descripcion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_tipo` (`tipo`,`created_at`),
  KEY `idx_usuario` (`usuario_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `categorias`;
CREATE TABLE `categorias` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `categoria_padre_id` int(10) unsigned DEFAULT NULL,
  `nombre` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `imagen` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `orden` int(11) NOT NULL DEFAULT 0,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_categorias_slug` (`slug`),
  KEY `idx_categorias_padre` (`categoria_padre_id`),
  CONSTRAINT `fk_categorias_padre` FOREIGN KEY (`categoria_padre_id`) REFERENCES `categorias` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `categorias` (`id`, `categoria_padre_id`, `nombre`, `slug`, `descripcion`, `imagen`, `orden`, `activo`, `created_at`, `updated_at`) VALUES ('1', NULL, 'Vasos y contenedores', 'vasos-y-contenedores', 'Vasos de papel, plástico y contenedores para alimentos', NULL, '0', '1', '2026-07-29 23:53:21', '2026-07-29 23:53:21');
INSERT INTO `categorias` (`id`, `categoria_padre_id`, `nombre`, `slug`, `descripcion`, `imagen`, `orden`, `activo`, `created_at`, `updated_at`) VALUES ('2', NULL, 'Cubiertos desechables', 'cubiertos-desechables', 'Cubiertos de plástico y biodegradables', NULL, '1', '1', '2026-07-29 23:53:21', '2026-07-29 23:53:21');
INSERT INTO `categorias` (`id`, `categoria_padre_id`, `nombre`, `slug`, `descripcion`, `imagen`, `orden`, `activo`, `created_at`, `updated_at`) VALUES ('3', NULL, 'Servilletas y papel', 'servilletas-y-papel', 'Servilletas, toallas y papel para higiene', NULL, '2', '1', '2026-07-29 23:53:21', '2026-07-29 23:53:21');
INSERT INTO `categorias` (`id`, `categoria_padre_id`, `nombre`, `slug`, `descripcion`, `imagen`, `orden`, `activo`, `created_at`, `updated_at`) VALUES ('4', NULL, 'Bolsas y empaques', 'bolsas-y-empaques', 'Bolsas, film y empaques para tu negocio', NULL, '3', '1', '2026-07-29 23:53:21', '2026-07-29 23:53:21');
INSERT INTO `categorias` (`id`, `categoria_padre_id`, `nombre`, `slug`, `descripcion`, `imagen`, `orden`, `activo`, `created_at`, `updated_at`) VALUES ('5', NULL, 'Biodegradables', 'biodegradables', NULL, NULL, '5', '1', '2026-07-29 23:53:22', '2026-07-29 23:53:22');
INSERT INTO `categorias` (`id`, `categoria_padre_id`, `nombre`, `slug`, `descripcion`, `imagen`, `orden`, `activo`, `created_at`, `updated_at`) VALUES ('6', '5', 'Bolsas', 'biodegradables-bolsas', NULL, NULL, '0', '1', '2026-07-29 23:53:22', '2026-07-29 23:53:22');
INSERT INTO `categorias` (`id`, `categoria_padre_id`, `nombre`, `slug`, `descripcion`, `imagen`, `orden`, `activo`, `created_at`, `updated_at`) VALUES ('7', '5', 'Cubiertos', 'biodegradables-cubiertos', NULL, NULL, '0', '1', '2026-07-29 23:53:22', '2026-07-29 23:53:22');
INSERT INTO `categorias` (`id`, `categoria_padre_id`, `nombre`, `slug`, `descripcion`, `imagen`, `orden`, `activo`, `created_at`, `updated_at`) VALUES ('8', '4', 'Compostables', 'bolsas-y-empaques-compostables', NULL, NULL, '0', '1', '2026-07-29 23:53:22', '2026-07-29 23:53:22');

DROP TABLE IF EXISTS `checador_dispositivos`;
CREATE TABLE `checador_dispositivos` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `activacion_token_hash` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `activacion_expira_en` timestamp NULL DEFAULT NULL,
  `device_token_hash` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `activado_en` timestamp NULL DEFAULT NULL,
  `ultimo_uso_en` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_checador_device` (`device_token_hash`),
  KEY `idx_checador_activacion` (`activacion_token_hash`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `checador_dispositivos` (`id`, `nombre`, `activacion_token_hash`, `activacion_expira_en`, `device_token_hash`, `activo`, `activado_en`, `ultimo_uso_en`, `created_at`) VALUES ('1', 'Recepción Planta', '4426c5eccd891331022f8c47ce11075c6bca8c7c7a2ed1754c00130d55f73f54', '2026-07-30 23:53:25', NULL, '1', NULL, NULL, '2026-07-29 23:53:25');
INSERT INTO `checador_dispositivos` (`id`, `nombre`, `activacion_token_hash`, `activacion_expira_en`, `device_token_hash`, `activo`, `activado_en`, `ultimo_uso_en`, `created_at`) VALUES ('2', 'Recepción Oficinas', 'e93d28a85d7ccbab3362afa299bbda49bc60bd7f68d591817c9e4b9fd3e142fe', '2026-07-30 23:53:25', NULL, '1', NULL, NULL, '2026-07-29 23:53:25');

DROP TABLE IF EXISTS `clientes_logos`;
CREATE TABLE `clientes_logos` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `imagen` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `orden` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_logo_activo` (`activo`,`orden`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `clientes_logos` (`id`, `nombre`, `imagen`, `activo`, `orden`, `created_at`, `updated_at`) VALUES ('1', 'Cliente 01', 'assets/img/clientes/1.png', '1', '1', '2026-07-29 23:52:56', '2026-07-29 23:52:56');
INSERT INTO `clientes_logos` (`id`, `nombre`, `imagen`, `activo`, `orden`, `created_at`, `updated_at`) VALUES ('2', 'Cliente 02', 'assets/img/clientes/2.png', '1', '2', '2026-07-29 23:52:56', '2026-07-29 23:52:56');
INSERT INTO `clientes_logos` (`id`, `nombre`, `imagen`, `activo`, `orden`, `created_at`, `updated_at`) VALUES ('3', 'Cliente 03', 'assets/img/clientes/3.png', '1', '3', '2026-07-29 23:52:56', '2026-07-29 23:52:56');
INSERT INTO `clientes_logos` (`id`, `nombre`, `imagen`, `activo`, `orden`, `created_at`, `updated_at`) VALUES ('4', 'Cliente 04', 'assets/img/clientes/4.png', '1', '4', '2026-07-29 23:52:56', '2026-07-29 23:52:56');
INSERT INTO `clientes_logos` (`id`, `nombre`, `imagen`, `activo`, `orden`, `created_at`, `updated_at`) VALUES ('5', 'Cliente 05', 'assets/img/clientes/5.png', '1', '5', '2026-07-29 23:52:56', '2026-07-29 23:52:56');
INSERT INTO `clientes_logos` (`id`, `nombre`, `imagen`, `activo`, `orden`, `created_at`, `updated_at`) VALUES ('6', 'Cliente 06', 'assets/img/clientes/6.png', '1', '6', '2026-07-29 23:52:56', '2026-07-29 23:52:56');
INSERT INTO `clientes_logos` (`id`, `nombre`, `imagen`, `activo`, `orden`, `created_at`, `updated_at`) VALUES ('7', 'Cliente 07', 'assets/img/clientes/7.png', '1', '7', '2026-07-29 23:52:56', '2026-07-29 23:52:56');
INSERT INTO `clientes_logos` (`id`, `nombre`, `imagen`, `activo`, `orden`, `created_at`, `updated_at`) VALUES ('8', 'Cliente 08', 'assets/img/clientes/8.png', '1', '8', '2026-07-29 23:52:56', '2026-07-29 23:52:56');
INSERT INTO `clientes_logos` (`id`, `nombre`, `imagen`, `activo`, `orden`, `created_at`, `updated_at`) VALUES ('9', 'Cliente 09', 'assets/img/clientes/9.png', '1', '9', '2026-07-29 23:52:56', '2026-07-29 23:52:56');
INSERT INTO `clientes_logos` (`id`, `nombre`, `imagen`, `activo`, `orden`, `created_at`, `updated_at`) VALUES ('10', 'Cliente 10', 'assets/img/clientes/10.png', '1', '10', '2026-07-29 23:52:56', '2026-07-29 23:52:56');
INSERT INTO `clientes_logos` (`id`, `nombre`, `imagen`, `activo`, `orden`, `created_at`, `updated_at`) VALUES ('11', 'Cliente 11', 'assets/img/clientes/11.png', '1', '11', '2026-07-29 23:52:56', '2026-07-29 23:52:56');
INSERT INTO `clientes_logos` (`id`, `nombre`, `imagen`, `activo`, `orden`, `created_at`, `updated_at`) VALUES ('12', 'Cliente 12', 'assets/img/clientes/12.png', '1', '12', '2026-07-29 23:52:56', '2026-07-29 23:52:56');
INSERT INTO `clientes_logos` (`id`, `nombre`, `imagen`, `activo`, `orden`, `created_at`, `updated_at`) VALUES ('13', 'Cliente 13', 'assets/img/clientes/13.png', '1', '13', '2026-07-29 23:52:56', '2026-07-29 23:52:56');
INSERT INTO `clientes_logos` (`id`, `nombre`, `imagen`, `activo`, `orden`, `created_at`, `updated_at`) VALUES ('14', 'Cliente 14', 'assets/img/clientes/14.png', '1', '14', '2026-07-29 23:52:56', '2026-07-29 23:52:56');
INSERT INTO `clientes_logos` (`id`, `nombre`, `imagen`, `activo`, `orden`, `created_at`, `updated_at`) VALUES ('15', 'Cliente 15', 'assets/img/clientes/15.png', '1', '15', '2026-07-29 23:52:56', '2026-07-29 23:52:56');
INSERT INTO `clientes_logos` (`id`, `nombre`, `imagen`, `activo`, `orden`, `created_at`, `updated_at`) VALUES ('16', 'Cliente 16', 'assets/img/clientes/16.png', '1', '16', '2026-07-29 23:52:56', '2026-07-29 23:52:56');
INSERT INTO `clientes_logos` (`id`, `nombre`, `imagen`, `activo`, `orden`, `created_at`, `updated_at`) VALUES ('17', 'Cliente 17', 'assets/img/clientes/17.png', '1', '17', '2026-07-29 23:52:56', '2026-07-29 23:52:56');
INSERT INTO `clientes_logos` (`id`, `nombre`, `imagen`, `activo`, `orden`, `created_at`, `updated_at`) VALUES ('18', 'Cliente 18', 'assets/img/clientes/18.png', '1', '18', '2026-07-29 23:52:56', '2026-07-29 23:52:56');
INSERT INTO `clientes_logos` (`id`, `nombre`, `imagen`, `activo`, `orden`, `created_at`, `updated_at`) VALUES ('19', 'Cliente 19', 'assets/img/clientes/19.png', '1', '19', '2026-07-29 23:52:56', '2026-07-29 23:52:56');
INSERT INTO `clientes_logos` (`id`, `nombre`, `imagen`, `activo`, `orden`, `created_at`, `updated_at`) VALUES ('20', 'Cliente 20', 'assets/img/clientes/20.png', '1', '20', '2026-07-29 23:52:56', '2026-07-29 23:52:56');

DROP TABLE IF EXISTS `cotizacion_items`;
CREATE TABLE `cotizacion_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `cotizacion_id` int(10) unsigned NOT NULL,
  `producto_id` int(10) unsigned DEFAULT NULL,
  `descripcion` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cantidad` int(11) NOT NULL DEFAULT 1,
  `precio_unitario` decimal(12,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `idx_cotitem` (`cotizacion_id`),
  CONSTRAINT `fk_cotitem_cot` FOREIGN KEY (`cotizacion_id`) REFERENCES `cotizaciones` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `cotizacion_items` (`id`, `cotizacion_id`, `producto_id`, `descripcion`, `cantidad`, `precio_unitario`) VALUES ('1', '1', '1', 'Vaso de papel 12 oz', '20', '1.20');
INSERT INTO `cotizacion_items` (`id`, `cotizacion_id`, `producto_id`, `descripcion`, `cantidad`, `precio_unitario`) VALUES ('2', '1', '6', 'Vaso de papel 16 oz', '10', '1.35');
INSERT INTO `cotizacion_items` (`id`, `cotizacion_id`, `producto_id`, `descripcion`, `cantidad`, `precio_unitario`) VALUES ('3', '2', '7', 'Contenedor kraft 750 ml', '15', '2.80');
INSERT INTO `cotizacion_items` (`id`, `cotizacion_id`, `producto_id`, `descripcion`, `cantidad`, `precio_unitario`) VALUES ('4', '2', '9', 'Kit cubiertos + servilleta', '20', '1.10');
INSERT INTO `cotizacion_items` (`id`, `cotizacion_id`, `producto_id`, `descripcion`, `cantidad`, `precio_unitario`) VALUES ('5', '3', '4', 'Bolsa kraft mediana', '30', '0.90');

DROP TABLE IF EXISTS `cotizaciones`;
CREATE TABLE `cotizaciones` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `usuario_id` int(10) unsigned DEFAULT NULL,
  `folio` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `empresa` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `telefono` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `producto_interes` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mensaje` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estado` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'nueva',
  `origen` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'landing',
  `ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `subtotal` decimal(12,2) DEFAULT NULL,
  `iva` decimal(12,2) DEFAULT NULL,
  `total` decimal(12,2) DEFAULT NULL,
  `enviada_en` datetime DEFAULT NULL,
  `pedido_id` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_cotizaciones_estado` (`estado`),
  KEY `idx_cotizaciones_created` (`created_at`),
  KEY `idx_cot_usuario` (`usuario_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `cotizaciones` (`id`, `usuario_id`, `folio`, `nombre`, `empresa`, `email`, `telefono`, `producto_interes`, `mensaje`, `estado`, `origen`, `ip`, `user_agent`, `created_at`, `updated_at`, `subtotal`, `iva`, `total`, `enviada_en`, `pedido_id`) VALUES ('1', '3', 'COT-00001', 'Cafetería La Esquina', 'Cafetería La Esquina', 'compras@laesquina.demo', '5551002030', NULL, 'Solicitud de cotización de demostración.', 'cotizada', 'portal', NULL, NULL, '2026-07-29 23:53:24', '2026-07-29 23:53:24', '37.50', '6.00', '43.50', NULL, NULL);
INSERT INTO `cotizaciones` (`id`, `usuario_id`, `folio`, `nombre`, `empresa`, `email`, `telefono`, `producto_interes`, `mensaje`, `estado`, `origen`, `ip`, `user_agent`, `created_at`, `updated_at`, `subtotal`, `iva`, `total`, `enviada_en`, `pedido_id`) VALUES ('2', '4', 'COT-00002', 'Restaurante El Fogón', 'Restaurante El Fogón', 'pedidos@elfogon.demo', '5551002031', NULL, 'Solicitud de cotización de demostración.', 'aprobada', 'portal', NULL, NULL, '2026-07-29 23:53:24', '2026-07-29 23:53:24', '64.00', '10.24', '74.24', NULL, NULL);
INSERT INTO `cotizaciones` (`id`, `usuario_id`, `folio`, `nombre`, `empresa`, `email`, `telefono`, `producto_interes`, `mensaje`, `estado`, `origen`, `ip`, `user_agent`, `created_at`, `updated_at`, `subtotal`, `iva`, `total`, `enviada_en`, `pedido_id`) VALUES ('3', '5', 'COT-00003', 'Taquería Los Compas', 'Taquería Los Compas', 'contacto@loscompas.demo', '5551002032', NULL, 'Solicitud de cotización de demostración.', 'nueva', 'portal', NULL, NULL, '2026-07-29 23:53:24', '2026-07-29 23:53:24', '27.00', '4.32', '31.32', NULL, NULL);
INSERT INTO `cotizaciones` (`id`, `usuario_id`, `folio`, `nombre`, `empresa`, `email`, `telefono`, `producto_interes`, `mensaje`, `estado`, `origen`, `ip`, `user_agent`, `created_at`, `updated_at`, `subtotal`, `iva`, `total`, `enviada_en`, `pedido_id`) VALUES ('4', NULL, 'COT-00004', 'Laura Méndez', 'Food Truck Sabor', 'laura@sabor.demo', '5559998877', 'Vasos personalizados', 'Quiero cotizar vasos con mi logo.', 'nueva', 'landing', NULL, NULL, '2026-07-29 23:53:24', '2026-07-29 23:53:24', NULL, NULL, NULL, NULL, NULL);

DROP TABLE IF EXISTS `encuestas_pedido`;
CREATE TABLE `encuestas_pedido` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pedido_id` int(10) unsigned NOT NULL,
  `usuario_id` int(10) unsigned DEFAULT NULL,
  `satisfaccion` tinyint(3) unsigned NOT NULL,
  `facilidad` tinyint(3) unsigned NOT NULL,
  `nps` tinyint(3) unsigned DEFAULT NULL,
  `comentario` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_encuesta_pedido` (`pedido_id`),
  KEY `idx_encuesta_created` (`created_at`),
  KEY `fk_encuesta_usuario` (`usuario_id`),
  CONSTRAINT `fk_encuesta_pedido` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_encuesta_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `encuestas_pedido` (`id`, `pedido_id`, `usuario_id`, `satisfaccion`, `facilidad`, `nps`, `comentario`, `created_at`) VALUES ('1', '3', '3', '5', '5', '9', 'Excelente atención y tiempos de entrega, seguiremos comprando.', '2026-07-29 23:53:25');
INSERT INTO `encuestas_pedido` (`id`, `pedido_id`, `usuario_id`, `satisfaccion`, `facilidad`, `nps`, `comentario`, `created_at`) VALUES ('2', '2', '4', '4', '3', '7', 'El pedido en línea es fácil, aunque tardó un poco la confirmación.', '2026-07-29 23:53:25');

DROP TABLE IF EXISTS `logos_proveedores`;
CREATE TABLE `logos_proveedores` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `imagen` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `orden` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_logoprov_activo` (`activo`,`orden`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `logos_proveedores` (`id`, `nombre`, `imagen`, `activo`, `orden`, `created_at`, `updated_at`) VALUES ('1', 'Dart', 'assets/img/logos/proveedores/dart.png', '1', '1', '2026-07-29 23:53:03', '2026-07-29 23:53:03');
INSERT INTO `logos_proveedores` (`id`, `nombre`, `imagen`, `activo`, `orden`, `created_at`, `updated_at`) VALUES ('2', 'Solo', 'assets/img/logos/proveedores/solo.png', '1', '2', '2026-07-29 23:53:03', '2026-07-29 23:53:03');
INSERT INTO `logos_proveedores` (`id`, `nombre`, `imagen`, `activo`, `orden`, `created_at`, `updated_at`) VALUES ('3', 'Convermex', 'assets/img/logos/proveedores/convermex.png', '1', '3', '2026-07-29 23:53:03', '2026-07-29 23:53:03');
INSERT INTO `logos_proveedores` (`id`, `nombre`, `imagen`, `activo`, `orden`, `created_at`, `updated_at`) VALUES ('4', 'Inix', 'assets/img/logos/proveedores/inix.png', '1', '4', '2026-07-29 23:53:03', '2026-07-29 23:53:03');
INSERT INTO `logos_proveedores` (`id`, `nombre`, `imagen`, `activo`, `orden`, `created_at`, `updated_at`) VALUES ('5', 'Jaguar Pactiv', 'assets/img/logos/proveedores/jaguar.png', '1', '5', '2026-07-29 23:53:03', '2026-07-29 23:53:03');
INSERT INTO `logos_proveedores` (`id`, `nombre`, `imagen`, `activo`, `orden`, `created_at`, `updated_at`) VALUES ('6', 'Primo', 'assets/img/logos/proveedores/primo.png', '1', '6', '2026-07-29 23:53:03', '2026-07-29 23:53:03');
INSERT INTO `logos_proveedores` (`id`, `nombre`, `imagen`, `activo`, `orden`, `created_at`, `updated_at`) VALUES ('7', 'Grupo Urpri', 'assets/img/logos/proveedores/grupo-urpri.png', '1', '7', '2026-07-29 23:53:03', '2026-07-29 23:53:03');
INSERT INTO `logos_proveedores` (`id`, `nombre`, `imagen`, `activo`, `orden`, `created_at`, `updated_at`) VALUES ('8', 'International Paper', 'assets/img/logos/proveedores/international-paper.png', '1', '8', '2026-07-29 23:53:03', '2026-07-29 23:53:03');
INSERT INTO `logos_proveedores` (`id`, `nombre`, `imagen`, `activo`, `orden`, `created_at`, `updated_at`) VALUES ('9', 'Classy', 'assets/img/logos/proveedores/classy.png', '1', '9', '2026-07-29 23:53:03', '2026-07-29 23:53:03');
INSERT INTO `logos_proveedores` (`id`, `nombre`, `imagen`, `activo`, `orden`, `created_at`, `updated_at`) VALUES ('10', 'TekniPlex', 'assets/img/logos/proveedores/tekniplex.png', '1', '10', '2026-07-29 23:53:03', '2026-07-29 23:53:03');
INSERT INTO `logos_proveedores` (`id`, `nombre`, `imagen`, `activo`, `orden`, `created_at`, `updated_at`) VALUES ('11', 'Tork', 'assets/img/logos/proveedores/tork.png', '1', '11', '2026-07-29 23:53:03', '2026-07-29 23:53:03');

DROP TABLE IF EXISTS `migrations`;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ejecutada_en` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_migrations` (`migration`)
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `migrations` (`id`, `migration`, `ejecutada_en`) VALUES ('1', '001_create_categorias.sql', '2026-07-29 23:52:44');
INSERT INTO `migrations` (`id`, `migration`, `ejecutada_en`) VALUES ('2', '002_create_productos.sql', '2026-07-29 23:52:45');
INSERT INTO `migrations` (`id`, `migration`, `ejecutada_en`) VALUES ('3', '003_create_cotizaciones.sql', '2026-07-29 23:52:45');
INSERT INTO `migrations` (`id`, `migration`, `ejecutada_en`) VALUES ('4', '004_create_usuarios.sql', '2026-07-29 23:52:46');
INSERT INTO `migrations` (`id`, `migration`, `ejecutada_en`) VALUES ('5', '005_create_pedidos.sql', '2026-07-29 23:52:46');
INSERT INTO `migrations` (`id`, `migration`, `ejecutada_en`) VALUES ('6', '006_create_pedido_items.sql', '2026-07-29 23:52:46');
INSERT INTO `migrations` (`id`, `migration`, `ejecutada_en`) VALUES ('7', '007_add_aprobado_usuarios.sql', '2026-07-29 23:52:47');
INSERT INTO `migrations` (`id`, `migration`, `ejecutada_en`) VALUES ('8', '008_create_roles.sql', '2026-07-29 23:52:47');
INSERT INTO `migrations` (`id`, `migration`, `ejecutada_en`) VALUES ('9', '009_create_permisos.sql', '2026-07-29 23:52:47');
INSERT INTO `migrations` (`id`, `migration`, `ejecutada_en`) VALUES ('10', '010_create_rol_permiso.sql', '2026-07-29 23:52:48');
INSERT INTO `migrations` (`id`, `migration`, `ejecutada_en`) VALUES ('11', '011_create_usuario_permiso.sql', '2026-07-29 23:52:48');
INSERT INTO `migrations` (`id`, `migration`, `ejecutada_en`) VALUES ('12', '012_seed_roles_permisos.sql', '2026-07-29 23:52:49');
INSERT INTO `migrations` (`id`, `migration`, `ejecutada_en`) VALUES ('13', '013_add_rol_id_usuarios.sql', '2026-07-29 23:52:50');
INSERT INTO `migrations` (`id`, `migration`, `ejecutada_en`) VALUES ('14', '014_add_clave_sae.sql', '2026-07-29 23:52:50');
INSERT INTO `migrations` (`id`, `migration`, `ejecutada_en`) VALUES ('15', '015_add_referencia_cliente.sql', '2026-07-29 23:52:51');
INSERT INTO `migrations` (`id`, `migration`, `ejecutada_en`) VALUES ('16', '016_add_vendedor.sql', '2026-07-29 23:52:52');
INSERT INTO `migrations` (`id`, `migration`, `ejecutada_en`) VALUES ('17', '017_create_producto_imagenes.sql', '2026-07-29 23:52:52');
INSERT INTO `migrations` (`id`, `migration`, `ejecutada_en`) VALUES ('18', '018_add_verificacion_email.sql', '2026-07-29 23:52:53');
INSERT INTO `migrations` (`id`, `migration`, `ejecutada_en`) VALUES ('19', '019_create_password_resets.sql', '2026-07-29 23:52:53');
INSERT INTO `migrations` (`id`, `migration`, `ejecutada_en`) VALUES ('20', '020_create_auditoria.sql', '2026-07-29 23:52:53');
INSERT INTO `migrations` (`id`, `migration`, `ejecutada_en`) VALUES ('21', '021_cotizaciones_items.sql', '2026-07-29 23:52:55');
INSERT INTO `migrations` (`id`, `migration`, `ejecutada_en`) VALUES ('22', '022_create_modales.sql', '2026-07-29 23:52:56');
INSERT INTO `migrations` (`id`, `migration`, `ejecutada_en`) VALUES ('23', '023_create_clientes_logos.sql', '2026-07-29 23:52:56');
INSERT INTO `migrations` (`id`, `migration`, `ejecutada_en`) VALUES ('24', '024_create_pedido_exportaciones.sql', '2026-07-29 23:52:57');
INSERT INTO `migrations` (`id`, `migration`, `ejecutada_en`) VALUES ('25', '025_add_permiso_ventas_solo_asignados.sql', '2026-07-29 23:52:57');
INSERT INTO `migrations` (`id`, `migration`, `ejecutada_en`) VALUES ('26', '026_add_permiso_reportes.sql', '2026-07-29 23:52:57');
INSERT INTO `migrations` (`id`, `migration`, `ejecutada_en`) VALUES ('27', '027_create_visitas.sql', '2026-07-29 23:52:58');
INSERT INTO `migrations` (`id`, `migration`, `ejecutada_en`) VALUES ('28', '028_create_bolsa_trabajo.sql', '2026-07-29 23:52:59');
INSERT INTO `migrations` (`id`, `migration`, `ejecutada_en`) VALUES ('29', '029_create_encuestas_pedido.sql', '2026-07-29 23:53:00');
INSERT INTO `migrations` (`id`, `migration`, `ejecutada_en`) VALUES ('30', '030_endurecimiento_auditoria.sql', '2026-07-29 23:53:01');
INSERT INTO `migrations` (`id`, `migration`, `ejecutada_en`) VALUES ('31', '031_add_esquema_impuestos_productos.sql', '2026-07-29 23:53:01');
INSERT INTO `migrations` (`id`, `migration`, `ejecutada_en`) VALUES ('32', '032_create_pedidos_recurrentes.sql', '2026-07-29 23:53:02');
INSERT INTO `migrations` (`id`, `migration`, `ejecutada_en`) VALUES ('33', '033_create_logos_proveedores.sql', '2026-07-29 23:53:03');
INSERT INTO `migrations` (`id`, `migration`, `ejecutada_en`) VALUES ('34', '034_add_subcategorias.sql', '2026-07-29 23:53:04');
INSERT INTO `migrations` (`id`, `migration`, `ejecutada_en`) VALUES ('35', '035_add_personalizable_productos.sql', '2026-07-29 23:53:05');

DROP TABLE IF EXISTS `modales`;
CREATE TABLE `modales` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `titulo` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `imagen` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `enlace` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `orden` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_modal_activo` (`activo`,`orden`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `password_resets`;
CREATE TABLE `password_resets` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token_hash` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expira_en` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_token` (`token_hash`),
  KEY `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `pedido_exportaciones`;
CREATE TABLE `pedido_exportaciones` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pedido_id` int(10) unsigned NOT NULL,
  `usuario_id` int(10) unsigned DEFAULT NULL,
  `usuario_email` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tipo` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'individual',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_pexp` (`pedido_id`,`created_at`),
  CONSTRAINT `fk_pexp_pedido` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `pedido_items`;
CREATE TABLE `pedido_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pedido_id` int(10) unsigned NOT NULL,
  `producto_id` int(10) unsigned DEFAULT NULL,
  `sku` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cantidad` int(10) unsigned NOT NULL DEFAULT 1,
  `precio_unitario` decimal(10,2) NOT NULL DEFAULT 0.00,
  `importe` decimal(12,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `idx_pedido_items_pedido` (`pedido_id`),
  KEY `idx_pedido_items_producto` (`producto_id`),
  CONSTRAINT `fk_pedido_items_pedido` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_pedido_items_producto` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `pedido_items` (`id`, `pedido_id`, `producto_id`, `sku`, `nombre`, `cantidad`, `precio_unitario`, `importe`) VALUES ('1', '1', '1', 'VP-12', 'Vaso de papel 12 oz', '40', '1.20', '48.00');
INSERT INTO `pedido_items` (`id`, `pedido_id`, `producto_id`, `sku`, `nombre`, `cantidad`, `precio_unitario`, `importe`) VALUES ('2', '1', '3', 'SB-01', 'Servilleta blanca doble hoja', '20', '0.30', '6.00');
INSERT INTO `pedido_items` (`id`, `pedido_id`, `producto_id`, `sku`, `nombre`, `cantidad`, `precio_unitario`, `importe`) VALUES ('3', '2', '7', 'CK-750', 'Contenedor kraft 750 ml', '25', '2.80', '70.00');
INSERT INTO `pedido_items` (`id`, `pedido_id`, `producto_id`, `sku`, `nombre`, `cantidad`, `precio_unitario`, `importe`) VALUES ('4', '2', '9', 'KC-01', 'Kit cubiertos + servilleta', '30', '1.10', '33.00');
INSERT INTO `pedido_items` (`id`, `pedido_id`, `producto_id`, `sku`, `nombre`, `cantidad`, `precio_unitario`, `importe`) VALUES ('5', '3', '10', 'TR-01', 'Toalla en rollo', '12', '18.50', '222.00');
INSERT INTO `pedido_items` (`id`, `pedido_id`, `producto_id`, `sku`, `nombre`, `cantidad`, `precio_unitario`, `importe`) VALUES ('6', '4', '11', 'FP-30', 'Film plástico 30 cm', '5', '0.00', '0.00');

DROP TABLE IF EXISTS `pedidos`;
CREATE TABLE `pedidos` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `usuario_id` int(10) unsigned NOT NULL,
  `folio` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `referencia_cliente` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estado` enum('borrador','enviado','en_proceso','sincronizado','cancelado') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'borrador',
  `subtotal` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `notas` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `erp_folio` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `erp_sincronizado` tinyint(1) NOT NULL DEFAULT 0,
  `erp_sincronizado_at` timestamp NULL DEFAULT NULL,
  `erp_error` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_pedidos_folio` (`folio`),
  KEY `idx_pedidos_usuario` (`usuario_id`),
  KEY `idx_pedidos_estado` (`estado`),
  KEY `idx_pedidos_erp` (`erp_sincronizado`),
  CONSTRAINT `fk_pedidos_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `pedidos` (`id`, `usuario_id`, `folio`, `referencia_cliente`, `estado`, `subtotal`, `total`, `notas`, `erp_folio`, `erp_sincronizado`, `erp_sincronizado_at`, `erp_error`, `created_at`, `updated_at`) VALUES ('1', '3', 'RYM-20260729-5CF5F', 'OC-1023', 'enviado', '54.00', '54.00', 'Pedido de demostración.', NULL, '0', NULL, NULL, '2026-07-29 23:53:24', '2026-07-29 23:53:24');
INSERT INTO `pedidos` (`id`, `usuario_id`, `folio`, `referencia_cliente`, `estado`, `subtotal`, `total`, `notas`, `erp_folio`, `erp_sincronizado`, `erp_sincronizado_at`, `erp_error`, `created_at`, `updated_at`) VALUES ('2', '4', 'RYM-20260729-C84A8', 'REQ-88', 'en_proceso', '103.00', '103.00', 'Pedido de demostración.', NULL, '0', NULL, NULL, '2026-07-29 23:53:24', '2026-07-29 23:53:24');
INSERT INTO `pedidos` (`id`, `usuario_id`, `folio`, `referencia_cliente`, `estado`, `subtotal`, `total`, `notas`, `erp_folio`, `erp_sincronizado`, `erp_sincronizado_at`, `erp_error`, `created_at`, `updated_at`) VALUES ('3', '3', 'RYM-20260729-8FE4E', 'OC-1000', 'sincronizado', '222.00', '222.00', 'Pedido de demostración.', 'SAE-4711', '1', '2026-07-29 23:53:25', NULL, '2026-07-29 23:53:24', '2026-07-29 23:53:25');
INSERT INTO `pedidos` (`id`, `usuario_id`, `folio`, `referencia_cliente`, `estado`, `subtotal`, `total`, `notas`, `erp_folio`, `erp_sincronizado`, `erp_sincronizado_at`, `erp_error`, `created_at`, `updated_at`) VALUES ('4', '4', 'RYM-20260729-8DAC0', NULL, 'enviado', '0.00', '0.00', 'Pedido de demostración.', NULL, '0', NULL, NULL, '2026-07-29 23:53:25', '2026-07-29 23:53:25');

DROP TABLE IF EXISTS `pedidos_recurrentes`;
CREATE TABLE `pedidos_recurrentes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `usuario_id` int(10) unsigned NOT NULL,
  `origen_pedido_id` int(10) unsigned DEFAULT NULL,
  `nombre` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `frecuencia_dias` smallint(5) unsigned NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `proximo_recordatorio_en` date NOT NULL,
  `ultimo_recordatorio_en` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_pedrec_usuario` (`usuario_id`),
  KEY `idx_pedrec_pendientes` (`activo`,`proximo_recordatorio_en`),
  KEY `fk_pedrec_origen` (`origen_pedido_id`),
  CONSTRAINT `fk_pedrec_origen` FOREIGN KEY (`origen_pedido_id`) REFERENCES `pedidos` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_pedrec_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `pedidos_recurrentes` (`id`, `usuario_id`, `origen_pedido_id`, `nombre`, `frecuencia_dias`, `activo`, `proximo_recordatorio_en`, `ultimo_recordatorio_en`, `created_at`) VALUES ('1', '3', '3', 'Reposición mensual de toallas', '30', '1', '2026-08-28', NULL, '2026-07-29 23:53:25');

DROP TABLE IF EXISTS `pedidos_recurrentes_items`;
CREATE TABLE `pedidos_recurrentes_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pedido_recurrente_id` int(10) unsigned NOT NULL,
  `producto_id` int(10) unsigned DEFAULT NULL,
  `sku` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cantidad` int(10) unsigned NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_pedrecitem_padre` (`pedido_recurrente_id`),
  KEY `fk_pedrecitem_producto` (`producto_id`),
  CONSTRAINT `fk_pedrecitem_padre` FOREIGN KEY (`pedido_recurrente_id`) REFERENCES `pedidos_recurrentes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_pedrecitem_producto` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `pedidos_recurrentes_items` (`id`, `pedido_recurrente_id`, `producto_id`, `sku`, `nombre`, `cantidad`) VALUES ('1', '1', '10', 'TR-01', 'Toalla en rollo', '12');

DROP TABLE IF EXISTS `permisos`;
CREATE TABLE `permisos` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `clave` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `grupo` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'General',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_permisos_clave` (`clave`)
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `permisos` (`id`, `clave`, `nombre`, `grupo`) VALUES ('1', 'admin.acceder', 'Acceder al panel de administración', 'Administración');
INSERT INTO `permisos` (`id`, `clave`, `nombre`, `grupo`) VALUES ('2', 'usuarios.ver', 'Ver usuarios internos', 'Administración');
INSERT INTO `permisos` (`id`, `clave`, `nombre`, `grupo`) VALUES ('3', 'usuarios.gestionar', 'Crear y editar usuarios internos', 'Administración');
INSERT INTO `permisos` (`id`, `clave`, `nombre`, `grupo`) VALUES ('4', 'roles.gestionar', 'Gestionar roles y permisos', 'Administración');
INSERT INTO `permisos` (`id`, `clave`, `nombre`, `grupo`) VALUES ('5', 'clientes.ver', 'Ver clientes', 'Clientes');
INSERT INTO `permisos` (`id`, `clave`, `nombre`, `grupo`) VALUES ('6', 'clientes.aprobar', 'Aprobar y activar clientes', 'Clientes');
INSERT INTO `permisos` (`id`, `clave`, `nombre`, `grupo`) VALUES ('7', 'cotizaciones.ver', 'Ver cotizaciones', 'Cotizaciones');
INSERT INTO `permisos` (`id`, `clave`, `nombre`, `grupo`) VALUES ('8', 'cotizaciones.gestionar', 'Dar seguimiento a cotizaciones', 'Cotizaciones');
INSERT INTO `permisos` (`id`, `clave`, `nombre`, `grupo`) VALUES ('9', 'pedidos.ver_todos', 'Ver todos los pedidos', 'Pedidos');
INSERT INTO `permisos` (`id`, `clave`, `nombre`, `grupo`) VALUES ('10', 'pedidos.actualizar_estado', 'Actualizar estado de pedidos', 'Pedidos');
INSERT INTO `permisos` (`id`, `clave`, `nombre`, `grupo`) VALUES ('11', 'pedidos.sincronizar_erp', 'Sincronizar pedidos al ERP', 'Pedidos');
INSERT INTO `permisos` (`id`, `clave`, `nombre`, `grupo`) VALUES ('12', 'productos.ver', 'Ver catálogo (admin)', 'Productos');
INSERT INTO `permisos` (`id`, `clave`, `nombre`, `grupo`) VALUES ('13', 'productos.crear', 'Crear productos', 'Productos');
INSERT INTO `permisos` (`id`, `clave`, `nombre`, `grupo`) VALUES ('14', 'productos.editar', 'Editar productos', 'Productos');
INSERT INTO `permisos` (`id`, `clave`, `nombre`, `grupo`) VALUES ('15', 'productos.eliminar', 'Eliminar productos', 'Productos');
INSERT INTO `permisos` (`id`, `clave`, `nombre`, `grupo`) VALUES ('16', 'categorias.gestionar', 'Gestionar categorías', 'Productos');
INSERT INTO `permisos` (`id`, `clave`, `nombre`, `grupo`) VALUES ('17', 'portal.acceder', 'Acceder al portal de clientes', 'Portal');
INSERT INTO `permisos` (`id`, `clave`, `nombre`, `grupo`) VALUES ('18', 'pedidos.ver_propios', 'Ver sus propios pedidos', 'Portal');
INSERT INTO `permisos` (`id`, `clave`, `nombre`, `grupo`) VALUES ('19', 'pedidos.crear', 'Crear pedidos', 'Portal');
INSERT INTO `permisos` (`id`, `clave`, `nombre`, `grupo`) VALUES ('20', 'auditoria.ver', 'Ver auditoría y errores', 'Administración');
INSERT INTO `permisos` (`id`, `clave`, `nombre`, `grupo`) VALUES ('21', 'modales.gestionar', 'Administrar modales del sitio', 'Contenido');
INSERT INTO `permisos` (`id`, `clave`, `nombre`, `grupo`) VALUES ('22', 'clientes_logos.gestionar', 'Administrar logos de clientes', 'Contenido');
INSERT INTO `permisos` (`id`, `clave`, `nombre`, `grupo`) VALUES ('23', 'ventas.solo_asignados', 'Ver solo clientes asignados', 'Clientes');
INSERT INTO `permisos` (`id`, `clave`, `nombre`, `grupo`) VALUES ('24', 'reportes.ver', 'Ver y descargar reportes', 'Administración');
INSERT INTO `permisos` (`id`, `clave`, `nombre`, `grupo`) VALUES ('25', 'visitas.ver', 'Ver la libreta de visitas', 'Visitas');
INSERT INTO `permisos` (`id`, `clave`, `nombre`, `grupo`) VALUES ('26', 'visitas.gestionar', 'Gestionar dispositivos y anfitriones', 'Visitas');
INSERT INTO `permisos` (`id`, `clave`, `nombre`, `grupo`) VALUES ('27', 'vacantes.gestionar', 'Gestionar vacantes', 'Bolsa de trabajo');
INSERT INTO `permisos` (`id`, `clave`, `nombre`, `grupo`) VALUES ('28', 'postulaciones.ver', 'Ver postulaciones', 'Bolsa de trabajo');
INSERT INTO `permisos` (`id`, `clave`, `nombre`, `grupo`) VALUES ('29', 'postulaciones.gestionar', 'Gestionar postulaciones', 'Bolsa de trabajo');
INSERT INTO `permisos` (`id`, `clave`, `nombre`, `grupo`) VALUES ('30', 'encuestas.ver', 'Ver encuestas de experiencia', 'Encuestas');
INSERT INTO `permisos` (`id`, `clave`, `nombre`, `grupo`) VALUES ('31', 'logos_proveedores.gestionar', 'Administrar logos de proveedores', 'Contenido');

DROP TABLE IF EXISTS `postulaciones`;
CREATE TABLE `postulaciones` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `vacante_id` int(10) unsigned DEFAULT NULL,
  `nombre` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `telefono` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mensaje` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cv_archivo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estado` enum('recibida','en_revision','entrevista','rechazada','contratada') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'recibida',
  `cita_at` datetime DEFAULT NULL,
  `ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_postulaciones_vacante` (`vacante_id`),
  KEY `idx_postulaciones_estado` (`estado`),
  KEY `idx_postulaciones_created` (`created_at`),
  CONSTRAINT `fk_postulaciones_vacante` FOREIGN KEY (`vacante_id`) REFERENCES `vacantes` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `postulaciones` (`id`, `vacante_id`, `nombre`, `email`, `telefono`, `mensaje`, `cv_archivo`, `estado`, `cita_at`, `ip`, `created_at`) VALUES ('1', '1', 'Jorge Ramírez', 'jorge.ramirez@demo.rym', '5556781234', 'Cuento con 4 años de experiencia en ventas al sector restaurantero.', NULL, 'entrevista', '2026-08-01 11:00:00', '127.0.0.1', '2026-07-29 23:53:25');
INSERT INTO `postulaciones` (`id`, `vacante_id`, `nombre`, `email`, `telefono`, `mensaje`, `cv_archivo`, `estado`, `cita_at`, `ip`, `created_at`) VALUES ('2', '1', 'Fernanda López', 'fernanda.lopez@demo.rym', '5556781235', 'Me interesa mucho la posición, adjunto mi CV.', NULL, 'en_revision', NULL, '127.0.0.1', '2026-07-29 23:53:25');
INSERT INTO `postulaciones` (`id`, `vacante_id`, `nombre`, `email`, `telefono`, `mensaje`, `cv_archivo`, `estado`, `cita_at`, `ip`, `created_at`) VALUES ('3', '2', 'Iván Castro', 'ivan.castro@demo.rym', '5556781236', 'Disponibilidad inmediata, vivo cerca de la planta.', NULL, 'recibida', NULL, '127.0.0.1', '2026-07-29 23:53:25');
INSERT INTO `postulaciones` (`id`, `vacante_id`, `nombre`, `email`, `telefono`, `mensaje`, `cv_archivo`, `estado`, `cita_at`, `ip`, `created_at`) VALUES ('4', '3', 'Sofía Herrera', 'sofia.herrera@demo.rym', '5556781237', 'Estudiante de 8vo semestre de Mercadotecnia en la UAM.', NULL, 'contratada', NULL, '127.0.0.1', '2026-07-29 23:53:25');

DROP TABLE IF EXISTS `producto_imagenes`;
CREATE TABLE `producto_imagenes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `producto_id` int(10) unsigned NOT NULL,
  `ruta` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `orden` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_producto` (`producto_id`,`orden`),
  CONSTRAINT `fk_prodimg_producto` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `productos`;
CREATE TABLE `productos` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `categoria_id` int(10) unsigned DEFAULT NULL,
  `nombre` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(180) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sku` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `clave_sae` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `esquema_impuestos` smallint(5) unsigned DEFAULT NULL,
  `precio` decimal(10,2) DEFAULT NULL,
  `unidad` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `imagen` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `destacado` tinyint(1) NOT NULL DEFAULT 0,
  `personalizable` tinyint(1) NOT NULL DEFAULT 0,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `orden` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_productos_slug` (`slug`),
  KEY `idx_productos_categoria` (`categoria_id`),
  KEY `idx_productos_sku` (`sku`),
  CONSTRAINT `fk_productos_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `productos` (`id`, `categoria_id`, `nombre`, `slug`, `descripcion`, `sku`, `clave_sae`, `esquema_impuestos`, `precio`, `unidad`, `imagen`, `destacado`, `personalizable`, `activo`, `orden`, `created_at`, `updated_at`) VALUES ('1', '1', 'Vaso de papel 12 oz', 'vaso-papel-12oz', 'Vaso de papel para bebidas calientes.', 'VP-12', 'PA0000001', '1', '1.20', 'Paquete 50 pzas', NULL, '1', '1', '1', '0', '2026-07-29 23:53:21', '2026-07-29 23:53:22');
INSERT INTO `productos` (`id`, `categoria_id`, `nombre`, `slug`, `descripcion`, `sku`, `clave_sae`, `esquema_impuestos`, `precio`, `unidad`, `imagen`, `destacado`, `personalizable`, `activo`, `orden`, `created_at`, `updated_at`) VALUES ('2', '2', 'Tenedor biodegradable', 'tenedor-biodegradable', 'Tenedor a base de almidón de maíz.', 'TB-01', 'PA0000002', '1', '0.55', 'Caja 1000 pzas', NULL, '1', '0', '1', '1', '2026-07-29 23:53:21', '2026-07-29 23:53:22');
INSERT INTO `productos` (`id`, `categoria_id`, `nombre`, `slug`, `descripcion`, `sku`, `clave_sae`, `esquema_impuestos`, `precio`, `unidad`, `imagen`, `destacado`, `personalizable`, `activo`, `orden`, `created_at`, `updated_at`) VALUES ('3', '3', 'Servilleta blanca doble hoja', 'servilleta-blanca', 'Servilleta de papel de alta absorción.', 'SB-01', 'PA0000003', '1', '0.30', 'Paquete 500 pzas', NULL, '0', '0', '1', '2', '2026-07-29 23:53:21', '2026-07-29 23:53:22');
INSERT INTO `productos` (`id`, `categoria_id`, `nombre`, `slug`, `descripcion`, `sku`, `clave_sae`, `esquema_impuestos`, `precio`, `unidad`, `imagen`, `destacado`, `personalizable`, `activo`, `orden`, `created_at`, `updated_at`) VALUES ('4', '4', 'Bolsa kraft mediana', 'bolsa-kraft-mediana', 'Bolsa de papel kraft para llevar.', 'BK-01', 'PA0000004', '1', '0.90', 'Paquete 100 pzas', NULL, '1', '1', '1', '3', '2026-07-29 23:53:21', '2026-07-29 23:53:22');
INSERT INTO `productos` (`id`, `categoria_id`, `nombre`, `slug`, `descripcion`, `sku`, `clave_sae`, `esquema_impuestos`, `precio`, `unidad`, `imagen`, `destacado`, `personalizable`, `activo`, `orden`, `created_at`, `updated_at`) VALUES ('5', '1', 'Vaso de papel 8 oz', 'vaso-papel-8oz', 'Vaso de papel para bebidas calientes 8 oz.', 'VP-08', 'PA0000005', '1', '0.95', 'Paquete 50 pzas', NULL, '1', '1', '1', '10', '2026-07-29 23:53:22', '2026-07-29 23:53:22');
INSERT INTO `productos` (`id`, `categoria_id`, `nombre`, `slug`, `descripcion`, `sku`, `clave_sae`, `esquema_impuestos`, `precio`, `unidad`, `imagen`, `destacado`, `personalizable`, `activo`, `orden`, `created_at`, `updated_at`) VALUES ('6', '1', 'Vaso de papel 16 oz', 'vaso-papel-16oz', 'Vaso de papel para bebidas frías/calientes.', 'VP-16', 'PA0000006', '1', '1.35', 'Paquete 50 pzas', NULL, '0', '1', '1', '11', '2026-07-29 23:53:22', '2026-07-29 23:53:22');
INSERT INTO `productos` (`id`, `categoria_id`, `nombre`, `slug`, `descripcion`, `sku`, `clave_sae`, `esquema_impuestos`, `precio`, `unidad`, `imagen`, `destacado`, `personalizable`, `activo`, `orden`, `created_at`, `updated_at`) VALUES ('7', '1', 'Contenedor kraft 750 ml', 'contenedor-kraft-750', 'Contenedor kraft con tapa para alimentos.', 'CK-750', 'PA0000007', '1', '2.80', 'Caja 300 pzas', NULL, '1', '1', '1', '12', '2026-07-29 23:53:22', '2026-07-29 23:53:22');
INSERT INTO `productos` (`id`, `categoria_id`, `nombre`, `slug`, `descripcion`, `sku`, `clave_sae`, `esquema_impuestos`, `precio`, `unidad`, `imagen`, `destacado`, `personalizable`, `activo`, `orden`, `created_at`, `updated_at`) VALUES ('8', '2', 'Cuchara biodegradable', 'cuchara-biodegradable', 'Cuchara a base de almidón de maíz.', 'CB-01', 'PA0000008', '1', '0.60', 'Caja 1000 pzas', NULL, '0', '0', '1', '13', '2026-07-29 23:53:22', '2026-07-29 23:53:22');
INSERT INTO `productos` (`id`, `categoria_id`, `nombre`, `slug`, `descripcion`, `sku`, `clave_sae`, `esquema_impuestos`, `precio`, `unidad`, `imagen`, `destacado`, `personalizable`, `activo`, `orden`, `created_at`, `updated_at`) VALUES ('9', '2', 'Kit cubiertos + servilleta', 'kit-cubiertos', 'Tenedor, cuchillo y servilleta empacados.', 'KC-01', 'PA0000009', '1', '1.10', 'Caja 500 pzas', NULL, '1', '0', '1', '14', '2026-07-29 23:53:22', '2026-07-29 23:53:22');
INSERT INTO `productos` (`id`, `categoria_id`, `nombre`, `slug`, `descripcion`, `sku`, `clave_sae`, `esquema_impuestos`, `precio`, `unidad`, `imagen`, `destacado`, `personalizable`, `activo`, `orden`, `created_at`, `updated_at`) VALUES ('10', '3', 'Toalla en rollo', 'toalla-rollo', 'Toalla de papel en rollo para cocina.', 'TR-01', 'PA0000010', '1', '18.50', 'Rollo', NULL, '0', '0', '1', '15', '2026-07-29 23:53:22', '2026-07-29 23:53:22');
INSERT INTO `productos` (`id`, `categoria_id`, `nombre`, `slug`, `descripcion`, `sku`, `clave_sae`, `esquema_impuestos`, `precio`, `unidad`, `imagen`, `destacado`, `personalizable`, `activo`, `orden`, `created_at`, `updated_at`) VALUES ('11', '4', 'Film plástico 30 cm', 'film-plastico-30', 'Rollo de film para empaque de alimentos.', 'FP-30', 'PA0000011', '1', '42.00', 'Rollo 300 m', NULL, '0', '0', '1', '16', '2026-07-29 23:53:22', '2026-07-29 23:53:22');
INSERT INTO `productos` (`id`, `categoria_id`, `nombre`, `slug`, `descripcion`, `sku`, `clave_sae`, `esquema_impuestos`, `precio`, `unidad`, `imagen`, `destacado`, `personalizable`, `activo`, `orden`, `created_at`, `updated_at`) VALUES ('12', '6', 'Bolsa biodegradable chica', 'bolsa-bio-chica', 'Bolsa compostable para llevar.', 'BB-CH', 'PA0000012', '1', '0.40', 'Paquete 100 pzas', NULL, '0', '0', '1', '17', '2026-07-29 23:53:22', '2026-07-29 23:53:22');
INSERT INTO `productos` (`id`, `categoria_id`, `nombre`, `slug`, `descripcion`, `sku`, `clave_sae`, `esquema_impuestos`, `precio`, `unidad`, `imagen`, `destacado`, `personalizable`, `activo`, `orden`, `created_at`, `updated_at`) VALUES ('13', '7', 'Tenedor de bambú', 'tenedor-bambu', 'Tenedor desechable de fibra de bambú.', 'TB-BA', 'PA0000013', '1', '0.75', 'Caja 500 pzas', NULL, '1', '0', '1', '18', '2026-07-29 23:53:22', '2026-07-29 23:53:22');
INSERT INTO `productos` (`id`, `categoria_id`, `nombre`, `slug`, `descripcion`, `sku`, `clave_sae`, `esquema_impuestos`, `precio`, `unidad`, `imagen`, `destacado`, `personalizable`, `activo`, `orden`, `created_at`, `updated_at`) VALUES ('14', '8', 'Bolsa compostable grande', 'bolsa-compost-grande', 'Bolsa compostable certificada, uso rudo.', 'BC-GR', 'PA0000014', '1', '0.85', 'Paquete 50 pzas', NULL, '0', '0', '1', '19', '2026-07-29 23:53:22', '2026-07-29 23:53:22');

DROP TABLE IF EXISTS `rol_permiso`;
CREATE TABLE `rol_permiso` (
  `rol_id` int(10) unsigned NOT NULL,
  `permiso_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`rol_id`,`permiso_id`),
  KEY `idx_rp_permiso` (`permiso_id`),
  CONSTRAINT `fk_rp_permiso` FOREIGN KEY (`permiso_id`) REFERENCES `permisos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_rp_rol` FOREIGN KEY (`rol_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `rol_permiso` (`rol_id`, `permiso_id`) VALUES ('1', '1');
INSERT INTO `rol_permiso` (`rol_id`, `permiso_id`) VALUES ('1', '2');
INSERT INTO `rol_permiso` (`rol_id`, `permiso_id`) VALUES ('1', '3');
INSERT INTO `rol_permiso` (`rol_id`, `permiso_id`) VALUES ('1', '4');
INSERT INTO `rol_permiso` (`rol_id`, `permiso_id`) VALUES ('1', '5');
INSERT INTO `rol_permiso` (`rol_id`, `permiso_id`) VALUES ('1', '6');
INSERT INTO `rol_permiso` (`rol_id`, `permiso_id`) VALUES ('1', '7');
INSERT INTO `rol_permiso` (`rol_id`, `permiso_id`) VALUES ('1', '8');
INSERT INTO `rol_permiso` (`rol_id`, `permiso_id`) VALUES ('1', '9');
INSERT INTO `rol_permiso` (`rol_id`, `permiso_id`) VALUES ('1', '10');
INSERT INTO `rol_permiso` (`rol_id`, `permiso_id`) VALUES ('1', '11');
INSERT INTO `rol_permiso` (`rol_id`, `permiso_id`) VALUES ('1', '12');
INSERT INTO `rol_permiso` (`rol_id`, `permiso_id`) VALUES ('1', '13');
INSERT INTO `rol_permiso` (`rol_id`, `permiso_id`) VALUES ('1', '14');
INSERT INTO `rol_permiso` (`rol_id`, `permiso_id`) VALUES ('1', '15');
INSERT INTO `rol_permiso` (`rol_id`, `permiso_id`) VALUES ('1', '16');
INSERT INTO `rol_permiso` (`rol_id`, `permiso_id`) VALUES ('1', '17');
INSERT INTO `rol_permiso` (`rol_id`, `permiso_id`) VALUES ('1', '18');
INSERT INTO `rol_permiso` (`rol_id`, `permiso_id`) VALUES ('1', '19');
INSERT INTO `rol_permiso` (`rol_id`, `permiso_id`) VALUES ('1', '20');
INSERT INTO `rol_permiso` (`rol_id`, `permiso_id`) VALUES ('1', '21');
INSERT INTO `rol_permiso` (`rol_id`, `permiso_id`) VALUES ('1', '22');
INSERT INTO `rol_permiso` (`rol_id`, `permiso_id`) VALUES ('1', '24');
INSERT INTO `rol_permiso` (`rol_id`, `permiso_id`) VALUES ('1', '25');
INSERT INTO `rol_permiso` (`rol_id`, `permiso_id`) VALUES ('1', '26');
INSERT INTO `rol_permiso` (`rol_id`, `permiso_id`) VALUES ('1', '27');
INSERT INTO `rol_permiso` (`rol_id`, `permiso_id`) VALUES ('1', '28');
INSERT INTO `rol_permiso` (`rol_id`, `permiso_id`) VALUES ('1', '29');
INSERT INTO `rol_permiso` (`rol_id`, `permiso_id`) VALUES ('1', '30');
INSERT INTO `rol_permiso` (`rol_id`, `permiso_id`) VALUES ('1', '31');
INSERT INTO `rol_permiso` (`rol_id`, `permiso_id`) VALUES ('2', '1');
INSERT INTO `rol_permiso` (`rol_id`, `permiso_id`) VALUES ('2', '5');
INSERT INTO `rol_permiso` (`rol_id`, `permiso_id`) VALUES ('2', '6');
INSERT INTO `rol_permiso` (`rol_id`, `permiso_id`) VALUES ('2', '7');
INSERT INTO `rol_permiso` (`rol_id`, `permiso_id`) VALUES ('2', '8');
INSERT INTO `rol_permiso` (`rol_id`, `permiso_id`) VALUES ('2', '9');
INSERT INTO `rol_permiso` (`rol_id`, `permiso_id`) VALUES ('2', '10');
INSERT INTO `rol_permiso` (`rol_id`, `permiso_id`) VALUES ('2', '11');
INSERT INTO `rol_permiso` (`rol_id`, `permiso_id`) VALUES ('2', '23');
INSERT INTO `rol_permiso` (`rol_id`, `permiso_id`) VALUES ('2', '24');
INSERT INTO `rol_permiso` (`rol_id`, `permiso_id`) VALUES ('3', '1');
INSERT INTO `rol_permiso` (`rol_id`, `permiso_id`) VALUES ('3', '9');
INSERT INTO `rol_permiso` (`rol_id`, `permiso_id`) VALUES ('3', '10');
INSERT INTO `rol_permiso` (`rol_id`, `permiso_id`) VALUES ('4', '1');
INSERT INTO `rol_permiso` (`rol_id`, `permiso_id`) VALUES ('4', '12');
INSERT INTO `rol_permiso` (`rol_id`, `permiso_id`) VALUES ('4', '13');
INSERT INTO `rol_permiso` (`rol_id`, `permiso_id`) VALUES ('4', '14');
INSERT INTO `rol_permiso` (`rol_id`, `permiso_id`) VALUES ('4', '15');
INSERT INTO `rol_permiso` (`rol_id`, `permiso_id`) VALUES ('4', '16');
INSERT INTO `rol_permiso` (`rol_id`, `permiso_id`) VALUES ('5', '17');
INSERT INTO `rol_permiso` (`rol_id`, `permiso_id`) VALUES ('5', '18');
INSERT INTO `rol_permiso` (`rol_id`, `permiso_id`) VALUES ('5', '19');

DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `es_sistema` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_roles_slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `roles` (`id`, `nombre`, `slug`, `descripcion`, `es_sistema`, `created_at`) VALUES ('1', 'Administrador', 'admin', 'Acceso total al sistema', '1', '2026-07-29 23:52:48');
INSERT INTO `roles` (`id`, `nombre`, `slug`, `descripcion`, `es_sistema`, `created_at`) VALUES ('2', 'Ventas / Cotizaciones', 'ventas', 'Atiende cotizaciones, clientes y pedidos', '1', '2026-07-29 23:52:48');
INSERT INTO `roles` (`id`, `nombre`, `slug`, `descripcion`, `es_sistema`, `created_at`) VALUES ('3', 'Almacén / Logística', 'almacen', 'Prepara y despacha pedidos', '1', '2026-07-29 23:52:48');
INSERT INTO `roles` (`id`, `nombre`, `slug`, `descripcion`, `es_sistema`, `created_at`) VALUES ('4', 'Editor de contenido', 'editor', 'Gestiona catálogo y contenido del sitio', '1', '2026-07-29 23:52:48');
INSERT INTO `roles` (`id`, `nombre`, `slug`, `descripcion`, `es_sistema`, `created_at`) VALUES ('5', 'Cliente', 'cliente', 'Cliente del portal de pedidos', '1', '2026-07-29 23:52:48');

DROP TABLE IF EXISTS `usuario_permiso`;
CREATE TABLE `usuario_permiso` (
  `usuario_id` int(10) unsigned NOT NULL,
  `permiso_id` int(10) unsigned NOT NULL,
  `concedido` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`usuario_id`,`permiso_id`),
  KEY `idx_up_permiso` (`permiso_id`),
  CONSTRAINT `fk_up_permiso` FOREIGN KEY (`permiso_id`) REFERENCES `permisos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_up_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `usuarios`;
CREATE TABLE `usuarios` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `rol_id` int(10) unsigned DEFAULT NULL,
  `empresa` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telefono` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rfc` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `clave_sae` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `clave_vendedor` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `comision` decimal(5,2) DEFAULT NULL,
  `vendedor_id` int(10) unsigned DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `aprobado` tinyint(1) NOT NULL DEFAULT 0,
  `email_verificado_en` datetime DEFAULT NULL,
  `verificacion_token` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `verificacion_expira_en` timestamp NULL DEFAULT NULL,
  `last_login_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_usuarios_email` (`email`),
  KEY `idx_usuarios_rol_id` (`rol_id`),
  KEY `idx_usuarios_vendedor` (`vendedor_id`),
  CONSTRAINT `fk_usuarios_rol` FOREIGN KEY (`rol_id`) REFERENCES `roles` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_usuarios_vendedor` FOREIGN KEY (`vendedor_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `usuarios` (`id`, `nombre`, `email`, `password`, `rol_id`, `empresa`, `telefono`, `rfc`, `clave_sae`, `clave_vendedor`, `comision`, `vendedor_id`, `activo`, `aprobado`, `email_verificado_en`, `verificacion_token`, `verificacion_expira_en`, `last_login_at`, `created_at`, `updated_at`) VALUES ('1', 'Administrador', 'admin@importadorarym.com.mx', '$2y$10$blcEY676EQiM8nOb40y8F.rRk/JmPU5ntFhvZVWhm6z5YTeZjCn6S', '1', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1', '1', NULL, NULL, NULL, NULL, '2026-07-29 23:53:21', '2026-07-29 23:53:21');
INSERT INTO `usuarios` (`id`, `nombre`, `email`, `password`, `rol_id`, `empresa`, `telefono`, `rfc`, `clave_sae`, `clave_vendedor`, `comision`, `vendedor_id`, `activo`, `aprobado`, `email_verificado_en`, `verificacion_token`, `verificacion_expira_en`, `last_login_at`, `created_at`, `updated_at`) VALUES ('2', 'Verónica Sales', 'vendedor@demo.rym', '$2y$12$Q96TOYrRru3vEkh1zX144OSDcVhTGR7AdhyuYomR5ZQK6yRtWdolO', '2', NULL, NULL, NULL, NULL, 'V001', '5.00', NULL, '1', '1', NULL, NULL, NULL, NULL, '2026-07-29 23:53:23', '2026-07-29 23:53:23');
INSERT INTO `usuarios` (`id`, `nombre`, `email`, `password`, `rol_id`, `empresa`, `telefono`, `rfc`, `clave_sae`, `clave_vendedor`, `comision`, `vendedor_id`, `activo`, `aprobado`, `email_verificado_en`, `verificacion_token`, `verificacion_expira_en`, `last_login_at`, `created_at`, `updated_at`) VALUES ('3', 'Cafetería La Esquina', 'compras@laesquina.demo', '$2y$12$f9NklQ8TbOqlWkoWIX.lt.cvOlTZxK6k8f5oYnjOpHNXv048Hew.O', '5', 'Cafetería La Esquina', '5551002030', 'CLE010101AB1', NULL, NULL, NULL, '2', '1', '1', '2026-07-29 23:53:23', NULL, '2026-07-31 23:53:23', NULL, '2026-07-29 23:53:23', '2026-07-29 23:53:23');
INSERT INTO `usuarios` (`id`, `nombre`, `email`, `password`, `rol_id`, `empresa`, `telefono`, `rfc`, `clave_sae`, `clave_vendedor`, `comision`, `vendedor_id`, `activo`, `aprobado`, `email_verificado_en`, `verificacion_token`, `verificacion_expira_en`, `last_login_at`, `created_at`, `updated_at`) VALUES ('4', 'Restaurante El Fogón', 'pedidos@elfogon.demo', '$2y$12$8KqxegTn0BxAkxXQp0.2Zu8ml/CKUKAT3Dk0Ipd4iMm5H.Iv9szse', '5', 'Restaurante El Fogón', '5551002031', 'REF020202CD2', NULL, NULL, NULL, '2', '1', '1', '2026-07-29 23:53:23', NULL, '2026-07-31 23:53:23', NULL, '2026-07-29 23:53:23', '2026-07-29 23:53:23');
INSERT INTO `usuarios` (`id`, `nombre`, `email`, `password`, `rol_id`, `empresa`, `telefono`, `rfc`, `clave_sae`, `clave_vendedor`, `comision`, `vendedor_id`, `activo`, `aprobado`, `email_verificado_en`, `verificacion_token`, `verificacion_expira_en`, `last_login_at`, `created_at`, `updated_at`) VALUES ('5', 'Taquería Los Compas', 'contacto@loscompas.demo', '$2y$12$NOMpedFjH6TvOiLanQHoteH5KyuH8a4JuWLqAY1jp024st3lMpxVm', '5', 'Taquería Los Compas', '5551002032', NULL, NULL, NULL, NULL, '2', '1', '0', '2026-07-29 23:53:23', NULL, '2026-07-31 23:53:23', NULL, '2026-07-29 23:53:23', '2026-07-29 23:53:23');
INSERT INTO `usuarios` (`id`, `nombre`, `email`, `password`, `rol_id`, `empresa`, `telefono`, `rfc`, `clave_sae`, `clave_vendedor`, `comision`, `vendedor_id`, `activo`, `aprobado`, `email_verificado_en`, `verificacion_token`, `verificacion_expira_en`, `last_login_at`, `created_at`, `updated_at`) VALUES ('6', 'Panadería Trigo de Oro', 'ventas@trigodeoro.demo', '$2y$12$9F5gSWRxavNOTZRPaXi3f.GZoqH1zkIU48kLDKMtqGzQhAav9GHU.', '5', 'Panadería Trigo de Oro', '5551002033', NULL, NULL, NULL, NULL, '2', '1', '0', '2026-07-29 23:53:24', NULL, '2026-07-31 23:53:24', NULL, '2026-07-29 23:53:24', '2026-07-29 23:53:24');

DROP TABLE IF EXISTS `vacantes`;
CREATE TABLE `vacantes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `titulo` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(180) COLLATE utf8mb4_unicode_ci NOT NULL,
  `area` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ubicacion` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tipo` enum('tiempo_completo','medio_tiempo','temporal','practicas') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'tiempo_completo',
  `descripcion` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `requisitos` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estado` enum('abierta','cerrada') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'abierta',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_vacantes_slug` (`slug`),
  KEY `idx_vacantes_estado` (`estado`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `vacantes` (`id`, `titulo`, `slug`, `area`, `ubicacion`, `tipo`, `descripcion`, `requisitos`, `estado`, `created_at`, `updated_at`) VALUES ('1', 'Ejecutivo de Ventas B2B', 'ejecutivo-de-ventas-b2b', 'Ventas', 'Iztapalapa, CDMX', 'tiempo_completo', 'Atención y seguimiento a clientes del giro alimentario, prospección de nuevas cuentas.', 'Experiencia en ventas B2B, disponibilidad de viajar en zona metropolitana.', 'abierta', '2026-07-29 23:53:25', '2026-07-29 23:53:25');
INSERT INTO `vacantes` (`id`, `titulo`, `slug`, `area`, `ubicacion`, `tipo`, `descripcion`, `requisitos`, `estado`, `created_at`, `updated_at`) VALUES ('2', 'Auxiliar de Almacén', 'auxiliar-de-almacen', 'Almacén', 'Iztapalapa, CDMX', 'tiempo_completo', 'Recepción, acomodo y surtido de mercancía; apoyo en inventarios.', 'Disponibilidad de horario, gusto por el orden.', 'abierta', '2026-07-29 23:53:25', '2026-07-29 23:53:25');
INSERT INTO `vacantes` (`id`, `titulo`, `slug`, `area`, `ubicacion`, `tipo`, `descripcion`, `requisitos`, `estado`, `created_at`, `updated_at`) VALUES ('3', 'Practicante de Marketing Digital', 'practicante-de-marketing-digital', 'Marketing', 'Remoto', 'practicas', 'Apoyo en redes sociales, contenido y campañas del sitio web.', 'Estudiante de mercadotecnia o afín, últimos semestres.', 'cerrada', '2026-07-29 23:53:25', '2026-07-29 23:53:25');

DROP TABLE IF EXISTS `visitas`;
CREATE TABLE `visitas` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `nombre_visitante` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `empresa` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telefono` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `num_personas` smallint(5) unsigned NOT NULL DEFAULT 1,
  `motivo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `anfitrion_id` int(10) unsigned DEFAULT NULL,
  `anfitrion_email` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dispositivo_id` int(10) unsigned DEFAULT NULL,
  `ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_visitas_created` (`created_at`),
  KEY `idx_visitas_anfitrion` (`anfitrion_id`),
  KEY `fk_visitas_dispositivo` (`dispositivo_id`),
  CONSTRAINT `fk_visitas_anfitrion` FOREIGN KEY (`anfitrion_id`) REFERENCES `anfitriones` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_visitas_dispositivo` FOREIGN KEY (`dispositivo_id`) REFERENCES `checador_dispositivos` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `visitas` (`id`, `nombre_visitante`, `empresa`, `telefono`, `num_personas`, `motivo`, `anfitrion_id`, `anfitrion_email`, `dispositivo_id`, `ip`, `created_at`) VALUES ('1', 'Roberto Sánchez', 'Distribuidora Norte', '5551234567', '1', 'Reunión comercial', '1', 'laura.gomez@demo.rym', NULL, '127.0.0.1', '2026-07-29 23:53:25');
INSERT INTO `visitas` (`id`, `nombre_visitante`, `empresa`, `telefono`, `num_personas`, `motivo`, `anfitrion_id`, `anfitrion_email`, `dispositivo_id`, `ip`, `created_at`) VALUES ('2', 'María Fernández', 'Bimbo', '5559876543', '2', 'Entrega de mercancía', '2', 'carlos.ruiz@demo.rym', NULL, '127.0.0.1', '2026-07-28 23:53:25');
INSERT INTO `visitas` (`id`, `nombre_visitante`, `empresa`, `telefono`, `num_personas`, `motivo`, `anfitrion_id`, `anfitrion_email`, `dispositivo_id`, `ip`, `created_at`) VALUES ('3', 'José Luis Ramírez', 'Coca-Cola FEMSA', '5553334444', '1', 'Revisión de contrato', '3', 'ana.torres@demo.rym', NULL, '127.0.0.1', '2026-07-27 23:53:25');
INSERT INTO `visitas` (`id`, `nombre_visitante`, `empresa`, `telefono`, `num_personas`, `motivo`, `anfitrion_id`, `anfitrion_email`, `dispositivo_id`, `ip`, `created_at`) VALUES ('4', 'Patricia Núñez', 'Independiente', NULL, '1', 'Entrevista de trabajo', '4', 'miguel.diaz@demo.rym', NULL, '127.0.0.1', '2026-07-26 23:53:25');
INSERT INTO `visitas` (`id`, `nombre_visitante`, `empresa`, `telefono`, `num_personas`, `motivo`, `anfitrion_id`, `anfitrion_email`, `dispositivo_id`, `ip`, `created_at`) VALUES ('5', 'Empresa de Mantenimiento', 'ServiTec', '5552221111', '3', 'Mantenimiento de equipos', '5', 'recepcion@demo.rym', NULL, '127.0.0.1', '2026-07-25 23:53:25');
INSERT INTO `visitas` (`id`, `nombre_visitante`, `empresa`, `telefono`, `num_personas`, `motivo`, `anfitrion_id`, `anfitrion_email`, `dispositivo_id`, `ip`, `created_at`) VALUES ('6', 'Andrea Vega', 'Grupo Modelo', '5557778888', '2', 'Presentación de productos', '1', 'laura.gomez@demo.rym', NULL, '127.0.0.1', '2026-07-24 23:53:25');

SET FOREIGN_KEY_CHECKS=1;
