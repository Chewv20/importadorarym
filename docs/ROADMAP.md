# Roadmap — Importadora RYM

> Estado base: **2026-07-14**. Núcleo MVC, portal, panel admin, RBAC, pedidos→SAE, PWA y
> seguridad ya operativos. Este documento especifica lo que **falta**, en orden de prioridad.
> Convención de esfuerzo: **S** ≈ ½–1 día · **M** ≈ 1–3 días · **L** ≈ 3–6 días.

Leyenda de estado: ☐ pendiente · ◐ en progreso · ☑ hecho.

---

## Fase 0 — Bloqueantes de producción  ·  esfuerzo total ~M

Sin esto, no se debe exponer el portal a Internet.

### 0.1 ◐ SSL / HTTPS forzado  · S
- **Objetivo:** todo el tráfico (login, portal, panel) cifrado.
- **Certificado instalado en el servidor (confirmado 2026-09-01).** Pendiente solo:
  en el `.env` de producción, `FORCE_HTTPS=true`.
- **Ya soportado:** `config('app.force_https')`, `is_https()` (detecta proxy vía
  `X-Forwarded-Proto`, validado contra `TRUSTED_PROXY_CIDR` desde la auditoría del
  01/09), HSTS en `.htaccess`.
- **Aceptación:** `http://` redirige a `https://`; cookies de sesión con flag `Secure`; sin *mixed content*.

### 0.2 ◐ `APP_KEY` — generada, falta pegarla en el servidor  · S
- `.env.example` ya trae `APP_KEY=` con el comando para generarla — hecho.
- **Generada 2026-09-01** (ver `.env` de producción preparado, no versionado):
  `php -r "echo bin2hex(random_bytes(32));"`. Pendiente solo: pegarla en el `.env`
  real del servidor (distinta a la de desarrollo).
- **Aceptación:** `config('app.key')` no vacío en producción y distinto al de desarrollo.

### 0.3 ☐ `AllowOverride All` + protección de backend  · S
- **Contexto:** el DocumentRoot **no** apunta a `/public`, así que los `.htaccess` deben aplicar.
- **Pasos:** en el vhost, `AllowOverride All`; verificar que `.env`, `app/`, `storage/`, `database/` devuelven 403 por web.
- **Aceptación:** `GET /.env` y `GET /app/` → 403. Solo verificable una vez subido al servidor real.

### 0.4 ☐ Rotar credenciales de prueba  · S
- **Decisión 2026-09-01:** producción arranca con base de datos limpia
  (`migrate.php` + `seed.php "ContraseñaFuerte" --solo-admin`, ver `DESPLIEGUE.md`
  §3) — los datos de prueba de la base local (2 pedidos y `clave_sae='1'` de
  `jesusarturov20@gmail.com`) **no viajan a producción**, así que no hace falta
  limpiarlos. `--solo-admin` (agregado 2026-09-01) omite además las 4
  categorías/productos de ejemplo del seed.
- El admin de producción queda creado directamente con una contraseña fuerte (pasada
  como argumento del seed), nunca con la contraseña por defecto del script.
- Crear el/los usuarios internos reales con sus roles desde el panel, una vez arriba.

---

## Fase 1 — Correo transaccional  ·  ✅ COMPLETADA (2026-07-14)

Implementada con **PHPMailer vendorizado** (`app/Vendor/PHPMailer`, sin Composer) y
**SMTP del hosting**. Plantillas HTML en `app/Views/emails` con layout de marca.
`MAIL_MAILER=log` en local (vuelca a `storage/logs/mail.log` sin enviar); en producción
`smtp` con las credenciales del `.env`. Los envíos **no bloquean** el flujo si fallan.

**Pendiente operativo:** capturar en el `.env` de producción las credenciales SMTP reales
del hosting (`MAIL_HOST/PORT/USERNAME/PASSWORD/ENCRYPTION`) y cambiar `MAIL_MAILER=smtp`.

### 1.0 ☑ Servicio de correo `App\Core\Mailer`  · hecho
- **Implementado con:** PHPMailer 6.9.3 vendorizado en `app/Vendor/PHPMailer/src` (sin Composer;
  cargado con `require_once` puntual porque el autoloader solo cubre `App\`).
- **`app/Core/Mailer.php`** — `Mailer::enviar($to, $asunto, $vista, $datos): bool`:
  - Modos según `MAIL_MAILER`: `smtp` (SMTP+TLS/SSL autenticado), `mail` (sendmail), `log` (no envía, vuelca a `storage/logs/mail.log`).
  - Renderiza `Views/emails/{vista}` dentro de `Views/emails/layout` con `View::capture`; genera `AltBody` de texto plano.
  - **Nunca lanza** hacia el flujo del usuario: captura `Throwable`, registra en `storage/logs/mail.log` y devuelve `false`.
- **Config** en `config/app.php` → clave `mail`, alimentada por `MAIL_*` del `.env`.
- **Plantillas** en `app/Views/emails/` con `layout.php` de marca (tabla-based, estilos inline por compatibilidad de clientes de correo).
- **Consideraciones producción:** configurar SPF/DKIM del dominio para no caer en spam; `MAIL_MAILER=smtp` con credenciales del hosting.
- **Verificado:** las 4 plantillas renderizan sin variables faltantes y el envío end-to-end en modo `log` registra correctamente.

### 1.1 ☑ Aviso de cotización/lead al asesor  · hecho
- **Disparador:** `CotizacionController::store()` y `PortalCotizacionController::enviar()` tras `Cotizacion::crear()`.
- **Contenido:** datos del lead (nombre, empresa, correo, teléfono, producto de interés, mensaje, origen).
- **Destino:** buzón configurable `MAIL_LEADS` (p. ej. `cotizaciones@importadorarym.com`).
- **Aceptación:** al enviar una cotización llega correo al asesor en < 1 min con todos los campos.

### 1.2 ☑ Confirmación al cliente  · hecho
- **Disparador:** mismo punto que 1.1, al correo capturado.
- **Contenido:** acuse "recibimos tu solicitud", resumen y tiempos de respuesta.
- **Aceptación:** el remitente recibe confirmación; no se envía si el email es inválido.

### 1.3 ☑ Aviso de aprobación de cuenta  · hecho
- **Disparador:** `Admin\ClienteController::aprobar()` (y opcional en `toggleActivo`).
- **Contenido:** "tu cuenta ya puede levantar pedidos" + enlace a `/portal/login`.
- **Aceptación:** al aprobar un cliente, éste recibe el aviso.

### 1.4 ☑ Aviso de pedido nuevo al equipo  · hecho
- **Disparador:** `PedidoController::store()` (portal) tras crear el pedido.
- **Contenido:** folio interno, cliente, líneas y notas; enlace al detalle en el panel.
- **Aceptación:** cada pedido enviado genera aviso interno.

---

## Fase 2 — Imágenes de producto (galería)  ·  ✅ COMPLETADA (2026-07-15)

Ampliada a **galería de hasta 5 imágenes por producto** con **pasarela al hacer hover** en el catálogo.
Tabla `producto_imagenes` (migración 017, FK `ON DELETE CASCADE`); la de menor `orden` es la principal.

### 2.1 ☑ Carga múltiple en el panel  · hecho
- Servicio `App\Core\Upload`: valida MIME real (`finfo`: jpg/png/webp), tamaño (≤3 MB), renombra aleatorio (`random_bytes`), guarda en `public/uploads/productos/`, borra archivos.
- `admin/producto_form.php`: `multipart/form-data` + `<input name="imagenes[]" multiple>`; sección de imágenes actuales con borrado individual (formularios fuera del form principal) y etiqueta "Principal".
- `Admin\ProductoController`: `guardarImagenes()` respeta el tope (5 − existentes); ruta `POST /admin/productos/{id}/imagenes/{imgId}/eliminar`; al eliminar producto borra sus archivos.
- **Seguridad:** `public/uploads/.htaccess` (`php_flag engine off` + `Require all denied` a scripts) — verificado: un `.php` en uploads devuelve 403.

### 2.2 ☑ Pasarela en el catálogo  · hecho
- `Producto::imagenesPorProductos()` (una consulta, sin N+1) → vista `pages/productos.php`.
- Markup `.prod-gallery` (imágenes apiladas con fade) + `.prod-gallery__dots`; fallback SVG de marca si el producto no tiene imágenes.
- JS en `site.js`: al `mouseenter` avanza cada 1 s con crossfade; puntos indicadores; **respeta `prefers-reduced-motion`**. CSS en `site.css` (catálogo) y `admin.css` (panel). SW → `rym-v14`.
- **Verificado E2E:** subida real (2 imgs), rechazo de no-imagen, tope de 5, borrado (fila+archivo), render de la pasarela en `/productos` y protección de `uploads`.

**Nota:** la columna antigua `productos.imagen` queda sin uso (la galería es la fuente de verdad); el portal de pedidos aún no muestra miniatura — opcional a futuro.

---

## Fase 3 — Recuperación de contraseña  ·  ✅ COMPLETADA (2026-07-15)

### 3.1 ☑ Flujo "olvidé mi contraseña"  · hecho
- **Migración 019** `password_resets` (`email`, `token_hash` UNIQUE, `expira_en`, `created_at`); se guarda el hash SHA-256 del token. Modelo `App\Models\PasswordReset` (crear/emailPorToken/eliminarDe/purgar); `crear()` reemplaza tokens previos del mismo correo.
- **`PasswordResetController`** + rutas: `GET/POST /portal/recuperar` (solicitud, respuesta **neutra**) y `GET /portal/restablecer/{token}` + `POST /portal/restablecer` (nueva contraseña con `password_errores()`).
- **Seguridad:** token 32 bytes, caducidad **60 min**, **un solo uso**; rate limit por IP (5/h) y por correo (3/h); honeypot + CSRF; el POST revalida el token (no confía en el correo del cliente).
- **UI:** vistas `portal/recuperar` y `portal/restablecer` (layout auth), correo `emails/restablecer_password`, enlace "¿Olvidaste tu contraseña?" en el login.
- **Verificado E2E:** correo existente vs inexistente dan la **misma** respuesta neutra (sin fila para inexistente); token válido abre el form, bogus/expirado redirige; contraseña débil rechazada (token intacto); contraseña fuerte cambia y **consume** el token; login con la nueva funciona y con la vieja falla.

---

## Fase 4 — Carga del catálogo real  ·  ✅ IMPORTADOR LISTO (2026-07-15)

Herramienta construida; falta que el negocio cargue su inventario real con ella.

### 4.1 ☑ Importador de catálogo (CSV)  · hecho
- **Servicio** `App\Core\CatalogoImport::procesar($rutaCsv, $dryRun)`: upsert por SKU (o clave SAE), crea categorías por nombre, valida filas, autodetecta delimitador `,`/`;`, quita BOM, parsea precio/booleanos. Reutilizado por CLI y panel.
- **CLI:** `php database/import_catalogo.php <archivo.csv> [--dry-run]`.
- **Panel:** `/admin/productos/importar` (permiso `productos.crear`) — descarga la plantilla, sube el CSV, opción **Solo simular**, muestra resumen (creados/actualizados/omitidos/categorías) y registra en auditoría. Botón "Importar CSV" en la lista de productos.
- **Plantilla:** `database/plantilla_catalogo.csv` (descargable desde el panel). Columnas: nombre*, categoria, sku, clave_sae, unidad, descripcion, precio, destacado, activo, orden.
- **Imágenes:** se cargan después desde la ficha del producto (galería, Fase 2).
- **Verificado E2E:** dry-run no escribe; import crea productos+categorías con precio/activo correctos; re-import actualiza sin duplicar; descarga de plantilla y subida por web con conteos correctos.

**Pendiente del negocio:** llenar la plantilla con el inventario real y correr la importación; luego subir imágenes por el panel.

---

## Fase 5 — Robustez y operación  ·  ✅ COMPLETADA (2026-07-15)

### 5.1 ☑ Manejo global de errores + página 500  · hecho
- `public/index.php`: `dispatch` envuelto en `try/catch(\Throwable)` + `register_shutdown_function` para fatales. En `app.debug=false` registra en `storage/logs/php-error.log` y renderiza `errors/500`; en desarrollo muestra el detalle en texto. Vista `app/Views/errors/500.php`.
- **Verificado E2E:** `/__boom` (ruta temporal) → 500 amigable sin filtrar el stack trace en prod, con detalle en dev, y registro en log.

**Ampliación 2026-09-18: aviso por correo ante un error 500.** El log de errores requería que alguien lo revisara manualmente; ahora, con `MAIL_ERRORES` configurado (opcional, vacío por defecto), `App\Core\ErrorAlert` manda un correo técnico al ocurrir un 500 en producción — con throttle de 1 correo/30min por firma de error (clase+archivo+línea+mensaje) para no inundar el buzón si un mismo error se repite en cada visita. Reutiliza `Mailer`/`RateLimiter` ya existentes, sin infraestructura nueva. Ver `docs/DESPLIEGUE.md §4.6`. `tests/run.php` 142 → **152/152** (10 pruebas nuevas sobre la parte pura: `datosDe()`/`firma()`).

### 5.2 ☑ Verificación de correo — requerida para pedidos  · hecho
- Migración 018 (`email_verificado_en`, `verificacion_token` = hash). Registro genera token y envía correo `verificar_correo`.
- `VerificacionController`: `GET /portal/verificar/{token}` (público, un solo uso) y `POST /portal/verificar/reenviar` (con rate limit). Aviso + botón de reenvío en el dashboard del portal si no está verificado.
- **Compuerta:** `Admin\ClienteController::aprobar()` bloquea si el correo no está verificado; la lista de clientes muestra pill "Correo sin verificar" y deshabilita el botón Aprobar.
- **Verificado E2E:** aprobar sin verificar se bloquea → enlace marca verificado → aprobar funciona → token usado deja de servir.
- **Nota:** clientes creados antes de la migración quedan con `email_verificado_en=NULL`; los ya aprobados siguen operando (la compuerta solo aplica a nuevas aprobaciones).

### 5.3 ☑ Respaldos de base de datos  · hecho
- `database/backup.php` (PHP puro, sin `mysqldump`): vuelca estructura+datos a `storage/backups/rym-YYYYMMDD-HHMM.sql`, conserva 14. Cron documentado en `docs/DESPLIEGUE.md` (§4.1).
- **Verificado:** respaldo generado y **restaurado** en BD temporal (12 tablas, datos íntegros).

### 5.4 ☑ Pruebas automatizadas mínimas  · hecho
- `tests/run.php` (PHP puro, sin Composer; sale con código 1 si algo falla): validadores, `password_errores`, `captcha_valido`, `SaeExport::claveDocumento`, `Upload::borrar` (anti path-traversal), `slugify`.
- **Verificado:** `php tests/run.php` → 30/30 en verde.

---

## Fase 6 — Ampliaciones de negocio (propuesta 2026-07-28)

Identificadas al revisar el estado actual del proyecto (catálogo aún en datos de prueba,
core ya sólido). Tres quedan **pausadas por decisión del negocio** — no se descartan, se
retoman cuando el catálogo real esté cargado y haya definición de negocio para cada una:

- ⏸ **Listas de precios por cliente** — sigue pausado.
- ⏸ **Importador general de precios/existencias desde SAE** — sigue pausado (distinto del
  sync automático de solo-existencias de 6.2, ver abajo).

### 6.2 ☑ Inventario / disponibilidad · hecho (2026-09-18)

Retomada a petición del usuario tras una revisión de estado general del proyecto — de
las 3 pausadas de Fase 6, es la única que se reactivó; las otras dos siguen en pausa.
Decisiones de negocio confirmadas antes de construir: la fuente de verdad es **Aspel
SAE** (Firebird 2.5, tabla `INVE03`, columnas `CVE_ART`/`EXIST`) — el sitio refleja la
existencia, no lleva su propio conteo; "Agotado" **no bloquea** el pedido, solo muestra
un aviso (mismo espíritu que "Bajo pedido": el negocio sigue tomando pedidos sin stock
inmediato); y la sincronización es **automática** (un script en la oficina, no un
importador de archivo manual), aprovechando que ya tienen ODBC funcionando contra esa
Firebird para reportes en Excel.

**Esquema** (migración `054`): `productos.existencia_sae` (última existencia
sincronizada, `NULL` = nunca sincronizado → sin aviso, cero cambio de comportamiento),
`existencia_actualizada_en` (informativo, para notar si la sincronización dejó de
correr) y `disponibilidad_manual` (override que **siempre gana** sobre lo automático —
para forzar "Bajo pedido" en un artículo sin stock que igual se puede producir, sin que
la siguiente sincronización lo revierta). `Producto::estadoDisponibilidad()` es la
función pura que resuelve el estado efectivo (manual > automático > sin aviso).

**Endpoint nuevo** `POST /integraciones/sae/disponibilidad`
(`App\Controllers\Integraciones\SaeDisponibilidadController`) — primer caso de
autenticación por token del proyecto (header `Bearer`, `.env` `SAE_SYNC_TOKEN`, vacío =
desactivado), con rate-limit ANTES de validar el token (así un token equivocado
repetido también queda acotado). `App\Core\DisponibilidadSync` es clase hermana de
`CatalogoImport`, no una extensión: solo toca `existencia_sae`, nunca crea productos ni
toca el resto de sus campos — un artículo de SAE sin equivalente en el catálogo del
sitio simplemente cuenta como "no encontrado". Documentado como precedente de auth
máquina-a-máquina en `docs/SEGURIDAD.md`.

**Entregable para la oficina**: `integraciones/sae/` (script de PowerShell +
`config.example.ps1` + `README.md`) — consulta Firebird por el mismo ODBC que ya usan
para Excel, arma el JSON a mano por fila (evita un problema conocido de PowerShell 5.1
que "aplana" un arreglo de un solo elemento) y lo manda al endpoint. Las credenciales
reales viven en `config.local.ps1`, gitignored, nunca en el repo. Pensado para
Programador de tareas de Windows, cada 1-2 horas.

**UI**: badge "Agotado"/"Bajo pedido" en el catálogo público (`pages/productos.php`,
solo si el estado no es "disponible" — sin badge = normal) y el mismo aviso en el
`order-item__meta` del flujo de pedido (portal y panel), sin bloquear el `qty-form`.
Select "Disponibilidad" en la ficha del producto para el override manual, con la
existencia sincronizada mostrada de solo lectura.

Verificado con `curl` contra un servidor local real (no solo revisión estática): sin
token → 401; token incorrecto repetido → 429 tras 20 intentos (confirma el orden
rate-limit-antes-que-auth); token correcto con filas válidas/inexistentes/inválidas →
`actualizados`/`no_encontrados`/`omitidos` correctos, `existencia_sae` actualizada en la
BD real, log escrito. El script de PowerShell no pudo probarse de extremo a extremo (sin
acceso a la Firebird real de la oficina) — revisado por sintaxis, a validar en su primera
corrida real. `tests/run.php` 152 → **169/169** (17 pruebas nuevas, todas puras: 4 sobre
`Producto::disponibilidadManual()`, 6 sobre `Producto::estadoDisponibilidad()`, 6 sobre
`DisponibilidadSync::filaValida()`).

### 6.1 ☑ Pedidos recurrentes  · hecho (2026-07-28)
- **Objetivo:** un cliente con compra regular (p. ej. vasos y servilletas cada mes) programa
  un pedido como plantilla recurrente; el sistema le recuerda por correo cuando toca repetirlo,
  sin tener que acordarse ni volver a armar el carrito desde cero.
- **Implementado:** tablas `pedidos_recurrentes` + `pedidos_recurrentes_items` (migración 032,
  snapshot de producto/sku/cantidad como ya hace `pedido_items`), modelo `PedidoRecurrente`,
  controlador `PedidoRecurrenteController` (portal). Se programa desde el detalle de un pedido
  ya realizado, con frecuencia controlada (7/15/30/45/60/90 días, `<select>`, no texto libre).
- **Cliente controla:** ver detalle (`/portal/recurrentes/{id}`), pedir ahora (agrega al carrito
  y va a `/portal/pedidos/nuevo`), pausar/reanudar, cambiar frecuencia, eliminar — todo desde
  `/portal/recurrentes`, con nav propio.
- **Cron** `database/recordatorios_recurrentes.php` (diario, 7 am): envía `emails/pedido_recordatorio`
  a las plantillas activas que ya tocan, reprograma la siguiente fecha (aunque el correo falle o
  la cuenta esté desactivada, para no acumular atrasos), idempotente dentro del mismo día.
- **Seguridad:** el "ver" es GET sin efectos secundarios; "agregar al carrito" es POST+CSRF
  (así un enlace de correo no puede mutar el carrito de otra pestaña abierta); ownership
  verificado en cada acción (IDOR) — verificado con un segundo cliente intentando ver/eliminar
  la plantilla ajena, bloqueado en ambos casos.
- **Sin cambios en:** modelo de pedidos existente, exportación a SAE, cotizaciones.
- **Verificado E2E:** 16 comprobaciones contra la base real (crear desde pedido, listar, las
  3 vistas nuevas renderizan con datos reales, pedir ahora carga el carrito, pausar/reanudar
  reprograma desde hoy, cambiar frecuencia, el cron encuentra lo pendiente y reprograma,
  segunda corrida el mismo día no reenvía, eliminar limpia en cascada) + flujo real por HTTP
  (login, crear, listar) + IDOR bloqueado con un segundo cliente. `tests/run.php` 81/81.
- **Pendiente operativo:** agendar el cron en producción (documentado en `docs/DESPLIEGUE.md §4.2.1`).

---

## Fase 7 — Logística, ventas asistidas y catálogo por cliente (propuesta 2026-07-31)

Nueve cambios solicitados el 2026-07-31, más **7.10 agregado el mismo día** (piezas por
presentación). Se agrupan por dependencia real (no por el orden en que se pidieron).
**Decisiones de negocio ya resueltas (2026-07-31)**, aplicadas en cada punto: mapa de
solo confirmación (sin costo); imagen del pedido = la actual del producto, con pantalla
nueva de "revisar y confirmar"; consecutivo de SAE por serie; el pedido tiene un estado
`parcial` cuando se exporta por partes; asignación de reparto manual por ahora, pero con
`zonas`/rutas modeladas desde ya para no rehacer el esquema después.

**Orden de construcción recomendado:**

1. **Grupo A — independientes, bajo riesgo, alto valor**: 7.9 (vendedor crea pedido) ✅,
   7.2 (imagen + pantalla de revisión) ✅, 7.10 (piezas por presentación) ✅, 7.1
   (dirección + CP + mapa) ✅, 7.3 (lista de productos por cliente) ✅. **Grupo A completo.**
2. **Grupo B — la entidad de datos compartida** (`pedido_envios`, ver nota de diseño):
   7.5, 7.7 y 7.8 modelan la misma realidad (un pedido puede entregarse/exportarse
   **por partes**) desde tres ángulos distintos, y comparten una sola tabla en vez de
   inventar cada uno la suya — exactamente lo que pidió el punto 8 ("no tener
   diferencias entre sistemas"). 7.5 ✅ (crea `pedido_envios`, 2026-08-01), 7.7 ✅ (le
   agrega los eventos de ruta, 2026-08-02), 7.8 ✅ (cuelga la encuesta del evento
   "entregado", 2026-08-03). **Grupo B completo.**
3. **Grupo C — depende del Grupo B ya construido**: 7.6 ✅ (una factura corresponde a un
   envío/exportación, hecho 2026-08-03) y 7.4 (la serie por día de entrega solo tiene
   sentido una vez que el pedido registra su fecha de entrega, dato que también usa 7.7).

---

### 7.9 ☑ El vendedor o el gerente de ventas crea pedidos a nombre del cliente · M · hecho (2026-07-31)

**Contexto:** hoy `PedidoController` (portal) es exclusivamente "el cliente arma su
propio carrito" — usa `$this->usuario['id']` como dueño del pedido, sin forma de
capturar "en nombre de". El control de alcance vendedor/gerente **ya existe** y se
reutiliza tal cual: `BaseController::vendedorScope()` (si el actor tiene el permiso
`ventas.solo_asignados` ve solo sus clientes vía `Usuario::esClienteDeVendedor()`; si
no lo tiene —caso "gerente"— ve todos, igual que ya pasa en clientes/pedidos/cotizaciones).

**Pasos:**
- Permiso nuevo `pedidos.crear_para_cliente`, asignado al rol `ventas`.
- Acción nueva en `Admin\PedidoController` (o un controlador dedicado
  `Admin\PedidoNuevoController`): selector de cliente (autocompletar, mismo patrón
  `[data-prodpicker]`/`admin.js` ya usado para productos en cotizaciones) + el mismo
  catálogo/carrito de `portal/pedido_nuevo.php`, reutilizando `Producto::activosFiltrado`
  y `Pedido::crear()` sin tocarlos — el único cambio es que `usuario_id` es el del
  cliente elegido, no el del actor autenticado.
- Verificación de acceso: si el actor tiene `ventas.solo_asignados`, el cliente elegido
  debe pasar `esClienteDeVendedor()` (rechazar con 403/flash si no).
- Auditoría: `Audit::cambio('crear', 'pedido', $id, "Creó el pedido a nombre de {cliente} (vendedor: {actor})")`
  — trazabilidad de quién pidió por quién.
- Aviso interno igual que hoy (`pedido_nuevo` a `MAIL_LEADS`), y aviso normal al cliente
  cuando cambie de estado (sin cambios en `notificarCliente()`).

**Aceptación:** un vendedor con clientes asignados solo puede elegir entre los suyos;
un gerente sin la restricción puede elegir cualquiera; el pedido creado aparece en "Mis
pedidos" del cliente elegido, no del vendedor; queda registrado en auditoría quién lo creó.

**Implementado:** migración `036_add_permiso_pedidos_crear_para_cliente.sql`;
`Usuario::buscarClientes()` (autocompletar) + `Usuario::clientesElegibles()` (lista
completa, tope 300); `Admin\PedidoNuevoController` (selector de cliente en sesión
propia `admin_pedido_cliente_id`/`admin_pedido_carrito`, independiente del carrito del
portal); vista `admin/pedido_nuevo.php` con **dos formas de elegir cliente**: búsqueda
dinámica (nombre/empresa) o un `<select>` con todos sus clientes asignados (o todos si
no tiene la restricción `ventas.solo_asignados`) — ambas envían al mismo
`seleccionarCliente()`; botón en `admin/pedidos.php`; autocompletar `[data-clientpicker]`
en `admin.js`. Los estilos de catálogo/carrito
(`.order-grid`, `.order-item`, `.cart`, `.portal-filters`) se movieron de `portal.css`
a `components.css` al graduarse de "solo portal" a compartidos entre portal y panel.
**Verificado E2E** contra la base real con un usuario de prueba: búsqueda de cliente
respeta `ventas.solo_asignados` (cliente no asignado → resultado vacío, selección
rechazada; sin la restricción → aparece y se puede elegir), alta de producto al
carrito, creación del pedido con `usuario_id` del cliente elegido (no el actor),
auditoría con la leyenda "Creó el pedido a nombre de…", y la sesión de captura se
limpia tras crear. `tests/run.php` 96/96 sin regresiones. Dos bugs reales atrapados y
corregidos durante la verificación (no visibles en revisión estática): placeholder
`:q` duplicado en `Usuario::buscarClientes()` (el proyecto corre con
`ATTR_EMULATE_PREPARES=false`, que no admite parámetros nombrados repetidos — se
corrigió a `:qn`/`:qe`, mismo patrón que `Producto::whereActivos()`) y variable
`$accion` faltante al renderizar la vista (la necesita `partials/portal_filtros.php`).

**Ampliación (2026-07-31): clientes sin acceso al portal.** Habrá clientes que nunca
usan el portal por su cuenta — el vendedor lleva su cuenta directamente. Se agregó un
botón "Quitar acceso al portal" / "Dar acceso al portal" en `admin/clientes.php`
(`Admin\ClienteController::togglePortalAcceso()`, permiso `clientes.aprobar`, mismo
`verificarAcceso()` de alcance de vendedor que el resto del módulo). Reutiliza el
mecanismo de *override* por usuario que ya existía para `ventas.solo_asignados`
(`usuario_permiso.concedido = 0` revoca `portal.acceder` sin tocar el resto de sus
permisos) — `Usuario::setAccesoPortal()`/`sinAccesoPortal()`. **Las dos cosas son
independientes a propósito**: `PortalBaseController` ya bloqueaba el login sin
`portal.acceder` (código preexistente, sin cambios); la elegibilidad para que un
vendedor cree un pedido a su nombre (`clienteEsElegible()`,
`Usuario::buscarClientes()`/`clientesElegibles()`) solo mira `aprobado`/`activo`, no
permisos — un cliente sin acceso al portal sigue apareciendo y siendo seleccionable ahí.
Pill "Sin acceso al portal" en el listado (columna `sin_acceso_portal`, calculada en la
misma consulta de `clientesPaginado()`, sin N+1). **Verificado E2E** contra la base
real: el override se crea/borra correctamente al alternar, el pill aparece/desaparece,
y el cliente sigue siendo elegible en 7.9 con el acceso revocado. `tests/run.php` 96/96.

**Ampliación (2026-07-31): un vendedor puede crear pedidos aunque el cliente no esté
aprobado.** `clienteEsElegible()`, `Usuario::buscarClientes()` y
`Usuario::clientesElegibles()` dejaron de exigir `aprobado = 1` — solo `activo = 1` (y
el alcance de vendedor si aplica). Es a propósito: **la aprobación solo controla que el
cliente levante pedidos por su cuenta en el portal** (`PortalBaseController::requiereAprobacion()`,
sin tocar); un vendedor que ya conoce y respalda al cliente puede levantarle un pedido
antes de que su cuenta esté aprobada — no son la misma compuerta. Para que el vendedor
no se confunda, la búsqueda, el `<select>` y el encabezado "Pedido para…" muestran
"(pendiente de aprobación)" cuando aplica. **Verificado E2E** contra la base real con un
cliente `aprobado=0`: aparece en la búsqueda y el select con el aviso, se puede elegir,
y el pedido se crea correctamente con su `usuario_id`. `tests/run.php` 96/96.

---

### 7.2 ☑ Imagen principal en pedidos + pantalla de revisar y confirmar · M · hecho (2026-07-31)

**Parte A — imagen principal:** se muestra **la imagen actual del producto** (no un
snapshot congelado como nombre/sku). `Producto::imagenesPorProductos()` ya existe (sin
N+1, la usa el catálogo público) — solo falta pasarlo a `pedido_nuevo.php` y a la nueva
pantalla de confirmación, y pintar una miniatura (la de menor `orden`; no hace falta la
pasarela completa del catálogo). Sin migración: es un JOIN a `producto_imagenes`, no una
columna nueva en `pedido_items`.

**Parte B — pantalla de revisar y confirmar:** se agrega un paso nuevo entre el carrito
y el envío final (hoy no existe; el carrito lateral de `pedido_nuevo.php` iba directo a
`store()`).
- Rutas nuevas: `GET /portal/pedidos/confirmar` (muestra todas las partidas del carrito
  con imagen, nombre y un `<input type="number">` de cantidad editable por línea) y
  `POST /portal/pedidos/confirmar` (aplica los cambios de cantidad a `$_SESSION['carrito']`
  — mismo mecanismo que ya usa `agregar()`/`quitar()` — y de ahí sí a `POST /portal/pedidos`).
- El botón "Enviar pedido" del carrito lateral pasa a apuntar a `/portal/pedidos/confirmar`
  en vez de a `POST /portal/pedidos` directo.
- Este paso es también el lugar natural para capturar, más adelante, la dirección de
  entrega (7.1) y la fecha deseada (7.4/7.7) en un solo momento — dejar la vista
  preparada para agregar esos campos sin rehacer la pantalla.

**Aceptación:** el cliente ve la imagen actual de cada producto en el catálogo del
pedido, el carrito y la pantalla de confirmación; en la confirmación puede cambiar
cantidades (o quitar partidas) antes de enviar, sin volver al catálogo.

**Implementado:** `PedidoController::imagenPrincipalPorIds()` (helper compartido,
una sola consulta vía `Producto::imagenesPorProductos()`) aplicado en el catálogo, el
carrito lateral y la confirmación; partial nuevo `partials/order_item_img.php`
(miniatura o placeholder, reutilizado en las tres vistas). Pantalla nueva
`portal/pedido_confirmar.php` en `GET /portal/pedidos/confirmar`; cantidad editable vía
`POST /portal/carrito/actualizar` (`actualizarCantidad()`, **fija** la cantidad en vez
de sumarla como `agregar()`); `quitar()` ahora acepta un campo `origen` para regresar a
`/portal/pedidos/confirmar` en vez de `/portal/pedidos/nuevo` cuando se invoca desde
ahí. El botón "Enviar pedido" del carrito lateral pasó a ser un enlace a
"Revisar y confirmar"; el formulario final (notas/referencia + envío) se movió a la
nueva pantalla, `store()` sin cambios de lógica. CSS nuevo en `components.css`
(`.order-item__main`, `.order-item__img`, `.order-item__img--empty`), reutilizado tal
cual por el catálogo, el carrito y la confirmación. **Extendido también al panel (2026-07-31):** mismo tratamiento en `Admin\PedidoNuevoController`
("pedido a nombre de un cliente", 7.9) — `GET /admin/pedidos/nuevo/confirmar`
(`admin/pedido_confirmar.php`), `POST /admin/pedidos/nuevo/carrito/actualizar`, y
`quitar()` con el mismo parámetro `origen`. Reutiliza el partial `order_item_img.php`
y las clases de `components.css` sin cambios — cero CSS/JS nuevo para esta extensión.
El encabezado de la pantalla de confirmación del panel también recuerda para quién es
el pedido, con el mismo aviso "Cuenta pendiente de aprobación" cuando aplica.

**Verificado E2E** contra la base real con un cliente de prueba: `/portal/pedidos/confirmar`
con carrito vacío redirige a `/nuevo`; el catálogo muestra la imagen real del producto
con imágenes cargadas y el placeholder en los que no tienen; se agregaron dos productos,
se actualizó la cantidad de uno a 5 desde la confirmación, se quitó el otro desde ahí
mismo, y el pedido final se creó en la base con exactamente esa partida y esa cantidad.
`tests/run.php` 96/96 sin regresiones. **Verificado E2E también en el panel** con un
vendedor de prueba: redirecciones correctas sin cliente/sin productos, imagen real en
el catálogo, actualizar cantidad y quitar desde la confirmación, y el pedido final
creado con el `usuario_id` del cliente (no del vendedor) y exactamente esa partida.

---

### 7.10 ☑ Piezas por presentación (personalizables y otros) · M · hecho (2026-07-31)

**Contexto:** ciertos productos (sobre todo personalizables — impresión con el logo del
cliente) se producen por lote mínimo, ej. "1 millar" = 1000 piezas. Hoy
`productos.unidad` (campo **"Presentación"** en `producto_form.php`, ya existe) es solo
texto libre sin ningún efecto en las cantidades capturadas — un cliente o un vendedor
puede pedir 750 piezas de un producto que solo se produce en millares completos, y el
sistema lo acepta tal cual.

**Por qué implementarlo aquí y no después de 7.1/7.3:** toca exactamente los cuatro
mismos puntos de captura de cantidad que 7.2 acaba de construir y dejar en paridad entre
portal y panel (`agregar()`/`actualizarCantidad()` en `PedidoController` y en
`Admin\PedidoNuevoController`). Ese código está fresco, ya probado E2E y sincronizado
entre los dos flujos — hacerlo ahora evita volver a abrir los mismos cuatro métodos más
adelante y arriesgar que portal y panel se desincronicen otra vez. 7.1 (dirección) y 7.3
(lista de productos por cliente) no comparten ese código, así que no hay la misma
urgencia de secuencia con ellos.

**Pasos:**
- Migración `037_add_piezas_por_presentacion_productos.sql`: columna
  `productos.piezas_por_presentacion` (INT UNSIGNED, nullable). `NULL` = comportamiento
  actual, sin restricción (la mayoría de los productos no la necesitan). Con valor, las
  cantidades de ese producto deben ser múltiplos exactos de ese número.
- `Admin\ProductoController` / `producto_form.php`: campo nuevo junto al de
  "Presentación" (`unidad`) que ya existe — ej. Presentación = "Millar", Piezas por
  presentación = `1000`. Validación: entero positivo o vacío (mismo patrón que
  `esquema_impuestos`, que ya es "número opcional, vacío = sin restricción").
- `Producto::cantidadValida(int $solicitada, ?int $piezasPorPresentacion): int` —
  función pura, fácil de cubrir en `tests/run.php` sin BD: sin restricción devuelve la
  solicitada (mínimo 1); con restricción, **redondea hacia arriba** al múltiplo más
  cercano, con un mínimo de una presentación completa (nunca 0 — pedir 1 pieza de un
  producto que se vende por millar debe convertirse en 1000, no rechazarse).
- Aplicar en los **cuatro** puntos de captura/edición de cantidad (los mismos que 7.2):
  `PedidoController::agregar()`/`actualizarCantidad()` (portal) y
  `Admin\PedidoNuevoController::agregar()`/`actualizarCantidad()` (panel).
- Cuando el sistema ajusta la cantidad, avisar con un flash explícito: *"Se ajustó a
  2000 piezas (2 × millar de 1000) para completar la presentación de {producto}."* — el
  ajuste no debe ser silencioso, ni parecer un error de captura.
- Catálogo: mostrar el requisito **antes** de que el cliente escriba la cantidad (ej.
  "Se vende por millar · mínimo 1000 pzas" junto al producto), y opcionalmente
  `step="1000"` en el `<input type="number">` como ayuda de UX — el redondeo real de
  todas formas ocurre en servidor, que es la autoridad, así que el `step` es solo
  cosmético/orientativo.

**Fuera de este alcance, a revisar cuando se llegue a esos módulos:**
- **Cotizaciones** (`PortalCotizacionController`, carrito propio `cot_cart`): si una
  cotización con este producto se convierte en pedido, ¿debe la cotización ya venir en
  múltiplos, o se ajusta al convertir? Recomiendo la misma regla ahí, pero es una
  decisión aparte para cuando se retome ese módulo.
- **Pedidos recurrentes** (`PedidoRecurrenteController`): "pedir ahora" recarga
  cantidades ya guardadas de una plantilla — si el producto cambió su
  `piezas_por_presentacion` después de crear la plantilla, la cantidad guardada podría
  haber dejado de ser válida. Revalidar al recargar es barato (misma función
  `cantidadValida()`), pero no es parte de este alcance inicial.
- **`PedidoController::repetir()`**: mismo caso — recarga cantidades de un pedido
  pasado, en teoría ya válidas cuando se hicieron, pero podrían no serlo si la
  presentación cambió después. Bajo riesgo, no crítico.

**Aceptación:** un producto sin `piezas_por_presentacion` se comporta exactamente igual
que hoy (cero regresión); un producto con, por ejemplo, 1000: pedir 750 se ajusta a
1000, pedir 1500 se ajusta a 2000, pedir 1000 exacto no cambia — en los cuatro puntos de
captura (portal y panel), siempre con aviso visible del ajuste.

**Implementado:** migración `037_add_piezas_por_presentacion_productos.sql`
(`productos.piezas_por_presentacion`, nullable); `Producto::piezasPorPresentacion()`
(normaliza captura, mismo patrón que `esquemaImpuestos()`) y
`Producto::cantidadValida()` (redondeo puro, cubierta en `tests/run.php` sin BD);
campo nuevo "Piezas por presentación" junto a "Presentación" en `producto_form.php` /
`Admin\ProductoController::input()`. Aplicado en los cuatro puntos de captura:
`PedidoController::agregar()`/`actualizarCantidad()` (portal) y
`Admin\PedidoNuevoController::agregar()`/`actualizarCantidad()` (panel), con flash
explicando el ajuste cuando cambia la cantidad. Nota "mín. N pzas" en el catálogo de
ambos flujos (`.order-item__meta`), sin CSS nuevo. `tests/run.php` 96 → **108/108**
(12 pruebas nuevas: redondeo exacto/hacia arriba/mínimo de una presentación/sin
restricción, y normalización de la captura).

**Verificado E2E** contra la base real (producto marcado temporalmente con
`piezas_por_presentacion=1000`, revertido a `NULL` al terminar): nota "mín. 1000 pzas"
visible en ambos catálogos; agregar 750 se ajustó a 1000 con el flash correcto;
actualizar a 1500 en la confirmación se ajustó a 2000; el pedido final quedó en la base
con exactamente 2000 piezas; mismo comportamiento probado en el panel (300 → 1000).

**Ajuste (2026-07-31, mismo día): mínimo de piezas independiente de la presentación.**
Caso real señalado: un producto puede venderse en paquetes de 50 (`piezas_por_presentacion`)
pero exigir un mínimo de 2 paquetes (100 piezas) por pedido — dos números distintos, no
uno solo. Se agregó columna nueva `productos.piezas_minimas` (migración `038`) y
`Producto::piezasMinimas()`. `Producto::cantidadValida()` ahora recibe un tercer
parámetro opcional: redondea primero al múltiplo de la presentación y **luego** sube el
resultado hasta el mínimo (el propio mínimo también se redondea a un múltiplo válido,
por si se capturó uno que no lo fuera). Nuevo helper `Producto::loteDesdeFila()` para no
repetir la extracción de ambos campos en los 4 puntos de captura. Campo nuevo "Mínimo de
piezas por pedido" en `producto_form.php`. La nota del catálogo ahora muestra el
**mínimo efectivo** (`minimo_efectivo`, calculado con `cantidadValida(1, …)` en el
controlador, no en la vista) en vez de solo la presentación.

**Verificado E2E** el caso exacto del negocio (presentación 50, mínimo 100, revertido al
terminar): catálogo mostró "mín. 100 pzas" (no 50); pedir 1 pieza se ajustó a 100 (2
paquetes), con el flash correspondiente; el pedido final quedó en la base con
exactamente 100 piezas. `tests/run.php` 108 → **122/122** (14 pruebas nuevas: mínimo +
presentación combinados, solo mínimo sin presentación, mínimo mal capturado que se
autocorrige, `loteDesdeFila()`).

---

### 7.1 ☑ Dirección del cliente en el registro (con CP y mapa) · M · hecho (2026-07-31)

**Pasos:**
- Migración nueva: columnas en `usuarios` — `calle`, `numero_ext`, `numero_int`
  (opcional), `colonia`, `codigo_postal`, `ciudad`, `estado_direccion` (evitar
  colisión de nombre con el `estado` de otras tablas), `referencias` (opcional).
- Validación nueva en `Helpers/functions.php`: `codigo_postal_valido()` (5 dígitos),
  siguiendo el mismo patrón que `rfc_valido()`/`telefono_valido()`; resto de campos con
  `str_clean()` + límites de longitud, igual que el resto del formulario de registro.
- Aplica en tres puntos: `AuthController::register()` + `portal/registro.php`
  (captura inicial), `PerfilController` (editar después), y se muestra en
  `Admin\ClienteController`/`admin/clientes.php` (solo lectura, para logística).
- **Mapa: de solo confirmación, sin costo (decidido 2026-07-31, "por mientras")**.
  Reutiliza el mismo patrón que ya existe en `pages/contacto.php` (iframe
  `google.com/maps?q=...&output=embed`, sin API key ni facturación, ya permitido por la
  CSP actual `frame-src https://www.google.com`), armando la URL con la dirección
  capturada para que el cliente **confirme visualmente** el punto tras guardar. El
  "por mientras" queda anotado: si más adelante hace falta que el cliente **arrastre un
  pin** para corregir la ubicación exacta, eso es un cambio aparte que requiere la API
  de JavaScript de Google Maps con API key propia y cuenta de facturación de Google —
  no romper el diseño de la Parte A por eso (los campos de dirección quedan iguales,
  solo cambiaría el widget del mapa).

**Aceptación:** el registro exige CP válido; tras guardar, el cliente ve un mapa con su
dirección; el panel muestra la dirección del cliente en su ficha.

**Implementado:** migración `039_add_direccion_usuarios.sql` (8 columnas nuevas en
`usuarios`, todas nullable — clientes existentes quedan sin dirección hasta que la
capturen). Validadores nuevos `direccion_valida()` y `codigo_postal_valido()` en
`Helpers/functions.php`, mismo patrón que `rfc_valido()`. Helpers `direccion_texto()`
(arma la dirección en una línea; `null` si no hay calle) y `direccion_maps_url()` (URL
del iframe, `null` si no hay dirección). Capturado como **requerido** en
`AuthController::register()`/`portal/registro.php` (calle, número ext., colonia, CP,
ciudad, estado — número int. y referencias opcionales) — decisión de diseño: sin
dirección útil para logística, mejor exigirla desde el registro que perseguirla después.
Editable en `PerfilController`/`portal/perfil.php`, con el mapa de confirmación debajo
del formulario. Panel: columna "Dirección" (solo lectura) en `admin/clientes.php`,
usando `direccion_texto()` — sin cambios al modelo, ya venía en `u.*`. El componente
`.map-embed` (antes solo en `site.css`, usado por `pages/contacto.php`) se movió a
`components.css` al graduarse a compartido entre el sitio público y el portal.

**Verificado E2E** contra la base real (usuario de prueba, borrado al terminar):
registro sin CP se rechaza (nada se crea en BD); registro con dirección completa guarda
los 7 campos exactos; el perfil precarga la dirección y renderiza el iframe con la URL
armada y url-encoded correctamente; editar el perfil sin colonia se rechaza (no cambia
nada) y con datos válidos sí actualiza; el panel de admin muestra la dirección en texto.
`tests/run.php` 122 → **134/134** (12 pruebas nuevas: validadores + `direccion_texto()`/
`direccion_maps_url()`).

**Ampliación (2026-07-31, mismo día): autocompletar por código postal + vista previa
del mapa antes de guardar.** Dos pedidos del usuario:

1. **Autocompletar colonia/ciudad/estado desde el CP.** Se investigaron varias APIs
   gratuitas de CP mexicano; la mayoría "gratis" ya piden token (Copomex/hckdrk.mx
   migró a `api.copomex.com` con token obligatorio; `sepomex.icalialabs.com` está
   caído). Se encontró y **verificó en vivo** `https://cp.terio.dev/v1/codigos-postales/{cp}`
   — sin llave, sin registro, responde `{datos: [{asentamiento, municipio, ciudad,
   estado, ...}]}`. Se llama **desde el servidor** (`App\Core\CodigoPostal::buscar()`,
   vía cURL con timeout de 4s), nunca desde el navegador — evita tocar la CSP
   (`connect-src` sigue en `'self'`) y evita exponer el proveedor externo al cliente.
   Nuevo endpoint público `GET /codigo-postal/{cp}/buscar` (`CodigoPostalController`,
   sin CSRF por ser GET de solo lectura sobre datos geográficos públicos, con
   rate-limit 30/10min por IP para no exponer el externo a abuso vía proxy). Si el
   servicio externo falla, tarda o no encuentra el CP, el endpoint responde
   `{"found":false}` y el formulario sigue funcionando 100% manual — nunca es un
   requisito duro.
2. **Botón "Mostrar mapa" en el registro**, antes de enviar. JS nuevo y compartido
   `assets/js/direccion.js` (progresivo: si el marcado no está en la página, no hace
   nada) con dos comportamientos independientes activados por atributos `data-*`:
   `[data-cp-form]` dispara el autocompletar (colonia vía `<datalist>`, ciudad/estado
   se sobrescriben) al capturar un CP de 5 dígitos; `[data-mapa-preview]` habilita un
   botón solo cuando los 6 campos requeridos de dirección están llenos, y al hacer
   clic arma la URL de Google Maps **en el cliente** (mismo formato que
   `direccion_texto()`/`direccion_maps_url()` en PHP) e inyecta el iframe — sin volver
   a pedir la página. Aplicado en `portal/registro.php` (lo pedido) y también en
   `portal/perfil.php` (extensión por consistencia: mismo patrón, mismo costo,
   permite verificar el mapa antes de guardar cambios, no solo después).

**Bug real atrapado por la prueba en navegador (no visible en revisión estática):**
asignar `ciudad.value`/`estado.value` por JS al autocompletar **no dispara el evento
`input`**, así que el botón "Mostrar mapa" (que solo se re-evalúa en `input`) se
quedaba deshabilitado para siempre aunque los campos ya estuvieran llenos. Corregido
disparando un `Event('input', {bubbles:true})` sintético tras la asignación
programática (`fijarValor()`).

**Verificado E2E con Playwright (Chromium real, no simulado) contra el servidor
local:** botón deshabilitado al inicio y sin CP; al capturar el CP, el `<datalist>`
recibe las colonias reales devueltas por la API y ciudad/estado se autocompletan
correctamente; con los 6 campos llenos el botón se habilita; al hacer clic inyecta un
iframe con la URL correctamente armada y url-encoded; cero errores de consola.
`tests/run.php` sin cambios (134/134) — la lógica de red/DOM de esta pieza no se
prueba con pruebas puras, sino con el navegador real, a propósito (igual que el resto
del proyecto evita pruebas dependientes de red en `tests/run.php`).

**Ajuste (2026-07-31, mismo día): 4 correcciones sobre lo anterior.**

1. **"Mostrar mapa" no avisaba nada si faltaban datos.** El botón antes se dejaba
   `disabled` en silencio, sin explicación — mala UX, sobre todo en móvil. Ahora
   **siempre está habilitado**; al hacer clic con datos incompletos muestra un mensaje
   (`.alert.alert--error`) listando exactamente qué falta (ej. "Completa tu dirección
   (calle, colonia) para poder ver el mapa."), y solo arma el mapa cuando todo está
   completo.
2. **Estilos para el selector de colonia.** El `<datalist>` nativo no se puede
   estilizar (la lista de sugerencias es UI del navegador, sin ganchos de CSS). Se
   reemplazó por un dropdown propio (`data-colonia-picker`), mismo patrón visual que
   `[data-prodpicker]` del panel (`.colonia-picker__resultados`/`__item` en
   `components.css`, compartido): al escribir filtra las colonias ya recibidas del CP
   y las muestra como botones con hover; clic selecciona y cierra el dropdown.
3. **"Ciudad" renombrado a "Delegación o Municipio"** — término correcto en México
   (delegación en CDMX, municipio en el resto del país). Migración `040`
   (`CHANGE COLUMN ciudad delegacion_municipio`) + todas las referencias (`Usuario`,
   `AuthController`, `PerfilController`, `direccion_texto()`, las dos vistas,
   `direccion.js`, `tests/run.php`). **Corrección de fondo, no solo de etiqueta**: el
   autocompletar leía `data.ciudad` de la API (p. ej. "Ciudad de México" — una
   agrupación amplia), que es **distinto** de `data.municipio` (p. ej. "Iztapalapa" —
   el dato correcto para delegación/municipio). Cambiar solo el label sin cambiar la
   fuente habría mostrado el dato equivocado bajo la etiqueta correcta; ahora lee
   `municipio`.
4. **Contenedor de registro más ancho** (`.auth-card--wide`, 680px en vez de 440px,
   modificador nuevo que no afecta login/recuperar/restablecer) — con la dirección
   agregada el formulario se veía angosto y muy alargado.

**Verificado E2E con Playwright (Chromium real):** ancho de la tarjeta = 680px; clic en
"Mostrar mapa" sin datos muestra el mensaje de error exacto y no dibuja el mapa;
capturar el CP autocompleta "Iztapalapa" en delegación/municipio (**no** "Ciudad de
México", confirmando la corrección del punto 3) y el estado; el selector de colonia
filtra en vivo con la clase estilizada nueva, clic selecciona y cierra el dropdown; con
todo completo el botón arma el mapa correctamente y el mensaje de error desaparece.
Cero errores de consola. `tests/run.php` 134/134 sin regresiones.

**Ajuste (2026-08-01): dos fallas lógicas del autocompletar por CP, señaladas por el
usuario tras probarlo.**

1. **Al cambiar el código postal, colonia/delegación/estado se quedaban con los
   datos del CP anterior** — nada los limpiaba, así que un cambio de CP podía dejar
   una dirección mezclada (CP nuevo con colonia/delegación del CP viejo) sin que nada
   lo evidenciara. Ahora, en cuanto el CP capturado deja de coincidir con el último
   resuelto, `limpiarDerivados()` vacía colonia/delegación/estado de inmediato — antes
   incluso de que la nueva consulta responda — así nunca queda una combinación
   mezclada, ni de forma transitoria.
2. **El cliente podía escribir directamente sobre delegación/municipio y estado**,
   proporcionando un dato que no correspondiera al código postal capturado. Ahora,
   en cuanto el CP resuelve correctamente, esos dos campos quedan `readonly`
   (estilo visual nuevo `.field input[readonly]` en `components.css`, fondo gris +
   cursor "no permitido") — la única forma de corregirlos es cambiando el CP, que es
   la fuente real del dato. Se desbloquean solos en cuanto el CP vuelve a editarse.
   La **colonia** no se bloquea (debe poder escribirse para filtrar la lista), pero
   se valida al perder el foco: si el CP ya devolvió una lista de colonias válidas y
   lo escrito no coincide exactamente con ninguna, se borra — evita guardar una
   colonia inventada. Si el CP no resolvió nada (API caída o CP no encontrado), no
   hay lista que validar y la colonia sigue siendo 100% texto libre — la captura
   manual nunca queda bloqueada por completo.

**Verificado E2E con Playwright (Chromium real):** CP 09850 resuelve
Iztapalapa/CDMX/San Juan Xalpa, ambos campos quedan `readonly` y escribir sobre
ellos directamente **no cambia el valor**; escribir una colonia inventada y perder
el foco la borra; cambiar el CP a 06600 limpia los tres campos **de inmediato**
(antes de que resuelva la nueva consulta) y los desbloquea; al resolver el nuevo CP
aparecen los datos correctos y distintos del CP anterior (Cuauhtémoc, no
Iztapalapa). Cero errores de consola. `tests/run.php` 134/134 sin regresiones.

---

### 7.3 ☑ Lista de productos específica por cliente · M · hecho (2026-08-01)

Reemplaza, por ahora, la idea pausada de "precios por cliente" (que sigue pausada) por
algo más simple: **qué productos puede ver/pedir** un cliente, no a qué precio.

**Implementado:**
- Migración `041_create_listas_productos.sql`: tabla `listas_productos` (id, nombre,
  descripcion, activa) + `listas_productos_items` (lista_id, producto_id, FKs con
  `ON DELETE CASCADE`) + columna `usuarios.lista_productos_id` (nullable, FK
  `ON DELETE SET NULL` — si se borra la lista, el cliente vuelve solo al catálogo
  completo sin quedar huérfano) + permiso `listas.gestionar` (solo rol `admin`).
- `App\Models\ListaProductos`: CRUD de listas y de sus productos, más
  `idsParaCliente(?int $listaProductosId): ?array` — `null` = sin restricción (cliente
  sin lista o lista inactiva); array vacío = lista activa sin productos, el cliente no
  ve nada, a propósito.
- `Producto::activosFiltrado()`/`contarActivosFiltrado()` reciben un 5º/3º parámetro
  opcional `?array $productoIds`, con default `null` que preserva el comportamiento de
  todos los llamadores existentes (catálogo público, cotizaciones no llevan este filtro).
- `Admin\ListaProductosController` (rutas `/admin/listas-productos*`) + vistas
  `listas_productos.php`/`lista_productos_form.php`: crear/editar/activar listas y
  agregar/quitar productos reutilizando el mismo `[data-prodpicker]` que ya arma
  cotizaciones (sin JS nuevo). Link en el nav del panel, tras Categorías.
- `Admin\ClienteController::listaProductos()` (ruta
  `POST /admin/clientes/{id}/lista-productos`) + `<select>` en `admin/clientes.php`, junto
  al de datos SAE. Requiere solo `clientes.aprobar` (no `listas.gestionar`): un vendedor
  puede asignar una lista ya creada por administración, no crear/editar su contenido.
- Filtro aplicado en los dos flujos de "crear pedido": `PedidoController::nuevo()`
  (portal, según `$this->usuario['lista_productos_id']`) y
  `Admin\PedidoNuevoController::index()` (panel, según la lista del cliente
  seleccionado). Deliberadamente **no** se tocó el catálogo público (`ProductoController`)
  ni las cotizaciones (`PortalCotizacionController`) — fuera del alcance de 7.3.
- Ambos `agregar()` (portal y panel) también validan `producto_id` contra la lista
  permitida antes de sumarlo al carrito — evita que un POST directo con un
  `producto_id` fuera de la lista la esquive.

**Verificado E2E (curl, datos de prueba creados y eliminados después):** cliente con
lista asignada (2 de 4 productos) ve solo esos 2 en `/portal/pedidos/nuevo`; un POST
directo a `/portal/carrito/agregar` con un `producto_id` fuera de la lista se rechaza
("Ese producto no está disponible"); mismo comportamiento en el flujo del vendedor
(`/admin/pedidos/nuevo` y `/admin/pedidos/nuevo/carrito/agregar`); un cliente sin lista
sigue viendo el catálogo completo (4/4, sin regresión); desactivar la lista revierte al
cliente al catálogo completo; eliminar la lista pone `lista_productos_id = NULL` en el
cliente (FK) y también revierte al catálogo completo; CRUD completo de listas
(crear/agregar producto/asignar a cliente) probado vía HTTP real contra el panel.
`tests/run.php` 134/134 sin regresiones (no se agregaron funciones puras nuevas).

---

### Nota de diseño — 7.5 + 7.7 + 7.8 comparten una misma entidad

Los tres puntos siguientes describen la misma realidad desde ángulos distintos: **un
pedido no siempre se exporta, reparte y entrega todo junto** — puede ir por partes.
Fijamos **una sola tabla** que representa "un envío/remesa de un pedido" (nombre
propuesto: `pedido_envios`), con: `pedido_id`, qué partidas incluye (tabla puente a
`pedido_items`), su estado de exportación a SAE (7.5), su tracking de ruta con
`zona_id` nullable (7.7) y su encuesta de entrega (7.8) todos enganchados a ese mismo
`envio_id` — así el panel de encuestas (7.8) puede mostrar exactamente qué partidas
iban en ese envío según SAE (7.5), sin mantener dos historias distintas.

`zona_id` se agrega desde esta primera migración aunque 7.7 empiece con asignación
manual (ver esa sección): así, cuando más adelante se quiera automatizar por zona/ruta,
no hace falta otra migración ni tocar las filas ya creadas — solo empezar a llenar la
columna que ya existe.

### 7.5 ☑ Selección de partidas al exportar a SAE + recordatorio de lo pendiente · L · hecho (2026-08-01)

**Implementado:**
- Migración `042_create_pedido_envios.sql`: tabla `zonas` (id, nombre, activa — sin uso
  hasta 7.7) + tabla `pedido_envios` (id, pedido_id, exportado_por, y ya con `zona_id`/
  `repartidor_id`/`evento`/`eta`/`notas` nullable para 7.7, sin UI todavía) + columnas
  nuevas en `pedido_items` (`envio_id` FK `ON DELETE SET NULL`, `exportado_en`,
  `recordatorio_en`) + `'parcial'` agregado al `ENUM` de `pedidos.estado`.
- `Pedido::itemsPendientesSae()` (partidas con `envio_id IS NULL`) junto al ya existente
  `itemsParaSae()` (todas, sin filtrar — se sigue usando donde no aplica lo pendiente).
- `Pedido::crearEnvio($pedidoId, $itemIds, $usuarioId)`: crea la fila de `pedido_envios`,
  marca esas partidas como exportadas (limpia su recordatorio) y ajusta el estado del
  pedido con una regla deliberadamente asimétrica: pasa a `parcial` en cuanto queda
  alguna partida pendiente junto con alguna ya exportada; pasa de `parcial` a
  `sincronizado` cuando la última partida pendiente se exporta — **pero un pedido
  exportado completo de una sola vez (como siempre) NO cambia de estado solo por
  exportarse**, sigue requiriendo el folio de SAE vía "Marcar como sincronizado" como
  antes. Decisión propia para no alterar el comportamiento ya establecido del caso
  simple, verificada explícitamente en la prueba E2E.
- `Admin\PedidoController::exportarPedido()`: separa el error de **cliente** sin clave SAE
  (bloquea todo el pedido, como antes) de los errores **por artículo** (clave/esquema
  faltante) — estos ya no bloquean la pantalla completa, solo esa partida: se muestra con
  fila roja, casilla deshabilitada y el motivo inline; el resto se puede exportar igual.
  `procesarExportacion()` solo exporta las partidas marcadas (`item_id[]`), crea el envío
  y descarga el Excel de esas partidas.
- `exportarLote()`/`procesarLote()`: ahora trabajan sobre partidas pendientes
  (`itemsPendientesSae`) de cada pedido en vez de "todas sus partidas" — un pedido ya
  parcialmente exportado puede seguir apareciendo en el lote solo con lo que le falta.
  `pendientesSae()` amplía su filtro a `estado IN ('enviado','parcial')` con al menos una
  partida sin envío.
- Recordatorio: `Pedido::establecerRecordatorio()` (mismo `<select>` de
  `PedidoRecurrente::FRECUENCIAS`, formulario en `pedido_detalle.php`, solo visible con
  partidas pendientes) + `pedidosConRecordatorioVencido()`/`limpiarRecordatorio()`. Cron
  `database/recordatorio_sae_pendiente.php` (mismo patrón que
  `recordatorios_recurrentes.php`): un solo correo interno **digest** (no uno por pedido)
  al buzón `MAIL_LEADS` existente (no se creó `MAIL_ALMACEN` nuevo, para no pedir una
  variable de entorno de más sin que se haya pedido) listando folio/cliente/partidas
  pendientes; limpia el recordatorio al avisar — idempotente, correrlo dos veces el mismo
  día no reenvía nada.
- `pedido_detalle.php`: pill de estado con `'parcial' => 'Parcial'` y su propio color
  (`.status--parcial`, ámbar); sección "Envíos a SAE" (nueva, por partidas) que reemplaza
  visualmente al antiguo "Historial de exportaciones" cuando ya hay envíos, con
  compatibilidad hacia atrás para pedidos exportados antes de esta migración (sin filas en
  `pedido_envios`, siguen mostrando su historial viejo). El pipeline visual
  (`Pedido::FLUJO`) **deliberadamente no incluye** `'parcial'` — es un estado opcional que
  puede saltarse por completo si el pedido se exporta todo de una vez, así que forzarlo
  como paso fijo habría marcado como "completados" pasos por los que un pedido nunca
  pasó; mientras el pedido está `parcial`, el pipeline se ve igual que `en_proceso` y el
  pill de arriba es lo que comunica el matiz.
- CSS nuevo compartido: `.text-danger`, `.fs-xs` (app.css), `.row-blocked` (fila
  bloqueada en rojo suave, components.css).

**Verificado E2E con curl** (datos de prueba creados y eliminados después, incluida la
corrección temporal y reversión de un producto real para simular el caso "ya se puede
exportar"): partida sin esquema de impuestos aparece bloqueada con su motivo y casilla
deshabilitada, el resto se exporta igual; tras exportar 1 de 2 partidas el pedido pasa a
`parcial` con 1 pendiente registrada en un nuevo envío; al exportar la partida restante
pasa automáticamente a `sincronizado` (dos envíos separados en el historial); un pedido
exportado completo de una sola vez (probado llamando `crearEnvio()` directo, sin pasar
por la cola compartida de lote para no tocar pedidos reales pendientes) se queda en
`enviado`, sin regresión; recordatorio se programa solo en las partidas pendientes, el
cron lo detecta al vencer, no reenvía si se corre dos veces seguidas; el bloqueo por
cliente sin clave SAE (todo el pedido) se sigue comportando igual que antes. `tests/run.php`
134/134 (sin funciones puras nuevas que agregar — la lógica nueva es toda de base de
datos/controlador).

**Ajuste 2026-08-01 (mismo día): la selección por partida no se notaba y en el lote no existía.**
El usuario probó y reportó que no veía forma de excluir partidas manualmente en ninguna de
las dos pantallas. Dos causas reales, no una:
1. **Bug funcional real** en `admin/pedido_exportar.php`: al desmarcar una partida, su campo
   de precio seguía con el atributo `required` — si se dejaba vacío (lo normal, si no se va a
   exportar), el navegador bloqueaba el envío del formulario con su validación nativa, sin
   ningún mensaje visible. Daba la impresión de que la casilla "no hacía nada". Arreglado con
   un listener delegado en `admin.js` (`data-sae-item`): al desmarcar, el precio de esa fila
   pasa a `disabled` (los campos disabled no se envían con el formulario, así que no hace
   falta filtrarlos aparte) y la fila se atenúa (`.row-unselected`, opacity .45) para que se
   note a simple vista qué queda fuera. Se agregó también un checkbox maestro
   `data-sae-check-all` para marcar/desmarcar todo de un golpe.
2. **La pantalla de lote (`admin/pedidos_exportar.php`) nunca tuvo casillas** — por diseño
   original solo excluía automáticamente pedidos completos con algún error, todo o nada. Se
   le agregó el mismo mecanismo de selección por partida que a la individual, y
   `Admin\PedidoController::exportarLote()`/`procesarLote()` se reescribieron para ya no
   bloquear el PEDIDO completo por un error de un solo artículo (antes lo hacían) — ahora,
   igual que en la individual, solo el error de cliente (sin clave SAE) excluye el pedido
   entero del lote; los errores por artículo solo bloquean esa fila y el resto del pedido
   sigue siendo seleccionable. `procesarLote()` ahora respeta `item_id[]` en vez de exportar
   automáticamente todo lo pendiente de cada pedido elegible.

**Verificado con Playwright real** (no solo curl, porque el bug original solo se manifestaba
con interacción real de navegador): desmarcar una partida y dejar su precio vacío ya NO
bloquea el envío (antes sí); la partida desmarcada queda con `envio_id` en `NULL` tras
exportar, la marcada sí se exporta; la fila se ve visiblemente atenuada. Nota para la próxima
vez: al probar formularios con Playwright, escoger el botón de envío con un selector acotado
al formulario (`form.form--admin button[type="submit"]`), no un selector genérico como
`button[type="submit"]` — el layout del panel tiene el botón "Cerrar sesión" con el mismo
`type="submit"` en el sidebar y aparece antes en el DOM, así que un selector genérico le hace
clic a ese en vez de al formulario real (me pasó a mí mismo verificando este arreglo: el primer
intento "no funcionó" porque en realidad estaba cerrando la sesión, no probando el export).
`tests/run.php` 134/134.

### 7.7 ☑ Tracking de envío (en ruta / entregado) + interfaz de repartidor · L · hecho (2026-08-02)

**Implementado:**
- Sin migración de esquema nueva para el tracking en sí: `pedido_envios` (7.5) ya traía
  `zona_id`/`repartidor_id`/`evento`/`eta`/`notas` y la tabla `zonas` desde la migración
  042, tal como se planeó. La migración `043` solo agrega los permisos nuevos:
  `pedidos.tracking` (concedido al rol `almacen` — se extendió el rol existente "Almacén
  / Logística" en vez de crear uno nuevo, como permitía el roadmap) y `zonas.gestionar`
  (concedido solo a `admin`, mismo patrón que `listas.gestionar`).
- `App\Models\Zona` + `Admin\ZonaController` (CRUD simple, mismo patrón que
  `CategoriaController`) + vistas `admin/zonas.php`/`zona_form.php` + link de nav.
  Explícitamente sin ruteo automático ni mapas — solo el catálogo, como se acotó.
- `Pedido::asignarRepartidor()`/`envioConDetalle()`/`enviosDeRepartidor()`/
  `enviosEntregadosDeRepartidor()`/`marcarEnRuta()`/`marcarEntregado()` +
  `Usuario::repartidores()` (usuarios del rol `almacen` activos).
- En `admin/pedido_detalle.php`, nueva sección "Envíos y reparto" (fuera del panel de
  SAE, que solo ven quienes tienen `pedidos.sincronizar_erp` — el rol `almacen` no lo
  tiene pero sí necesita asignar repartidor, por eso quedó en un panel aparte gateado
  solo por `pedidos.actualizar_estado`): por cada envío ya exportado, `<select>` de
  repartidor + zona + notas, y el estado de reparto (sin repartir / en ruta / entregado,
  con ETA si se capturó).
- **Interfaz de reparto nueva y separada del panel** (`App\Controllers\RepartoController`,
  `layouts/reparto.php`, rutas `/reparto`, `/reparto/{envioId}/en-ruta`,
  `/reparto/{envioId}/entregado`, gateadas por `pedidos.tracking`): tarjetas grandes por
  envío pendiente con folio, cliente, teléfono (`tel:`), dirección con enlace directo a
  Google Maps y un botón grande ("Salió a ruta" con ETA opcional, o "Entregado" con
  confirmación) — mismo espíritu minimalista que `layouts/kiosco.php`. Verificación de
  propiedad: un repartidor no puede tocar el envío de otro (`envioDelRepartidor()`
  compara `repartidor_id` contra el usuario autenticado). JS propio mínimo
  (`reparto.js`, solo el listener de `data-confirm`) y bundle CSS propio (`reparto.css`,
  agregado a `App\Core\Assets::BUNDLES`).
- Correos `emails/pedido_en_ruta.php`/`pedido_entregado.php` al cliente en cada evento,
  mismo patrón que `pedido_estado.php`; no bloquean el flujo si fallan.
- `public/sw.js`: `/reparto` se agregó a `isPrivate()` (no debe cachearse, es contenido
  autenticado sensible a la sesión — el mismo criterio que ya aplicaba a portal/admin).

**Decisión propia no cubierta explícitamente por el roadmap:** el login (`AuthController`)
no cambió su redirección — sigue mandando a `/admin` si el usuario tiene `admin.acceder`
(que el rol `almacen` conserva). El repartidor entra a `/admin` y usa el link "Reparto
(móvil)" del sidebar (o guarda `/reparto` en su celular) en vez de que el login lo mande
directo ahí — se evitó tocar la redirección global para no afectar a otros usuarios del
rol `almacen` que sí necesiten el panel completo (ej. quien prepara pedidos en bodega,
no solo reparte).

**Verificado E2E con curl + Playwright** (datos de prueba creados y eliminados después):
admin crea una zona y asigna repartidor+zona a un envío ya exportado desde el detalle del
pedido; el repartidor ve el envío en `/reparto` con dirección/teléfono/link a mapa; marca
"salió a ruta" con ETA (el `datetime-local` del navegador manda `T` como separador, se
convierte a espacio antes de guardar en la columna `DATETIME`); la tarjeta cambia a
"Entregado"; al marcarlo, el envío pasa al historial de entregados y desaparece de
pendientes; un segundo repartidor con un envío ajeno recibe "No tienes ese envío
asignado" al intentar tocarlo; un usuario sin `pedidos.tracking` (probado con `admin`,
que a propósito no lo tiene) recibe 403 en `/reparto`; los correos de ambos eventos
renderizan sin errores (revisado el log de errores, sin nuevas entradas). Verificación
visual con Playwright a tamaño de celular (390×844): tarjetas grandes, botones táctiles,
todo legible sin necesitar zoom. `tests/run.php` 135/135 (un caso más que la sesión
anterior: la prueba de "llaves balanceadas" del minificador de CSS recorre con `glob()`
todos los `*.bundle.min.css` que existan en disco, y ahora encuentra también
`reparto.bundle.min.css` recién generado).

**Ajuste 2026-08-02 (mismo día, pedido de revisión de UX): navegación de vuelta al panel
+ notas visibles.** El usuario pidió revisar `/reparto` y agregar botones de regreso al
panel. Se agregaron dos: un link "← Panel" en el header (fijo arriba, junto al logo,
visible solo si el usuario tiene `admin.acceder`) y un botón "← Volver al panel de
administración" al final de la lista. De paso, dos mejoras chicas detectadas en la
revisión: (1) las `notas` que el admin captura al asignar un repartidor (ej. "Dejar con
el guardia") se guardaban desde 7.7 pero **nunca se mostraban** en la tarjeta del
repartidor — vacío real, corregido; (2) contador de envíos pendientes y link
"↻ Actualizar" en el encabezado. Verificado con curl + Playwright (celular 390×844).
`tests/run.php` 135/135.

**Ajuste 2026-08-02 (mismo día, dos mejoras propuestas y aceptadas): confirmación
sensorial + agrupar por zona.** (1) Al marcar "Entregado", ahora vibra el celular (si lo
soporta, patrón corto-largo-corto) y suena un tono de confirmación generado con Web Audio
API — mismo espíritu que el sonido del kiosco de visitas, sin depender de un archivo de
audio. Se activa solo justo después de marcar entregado (un flash de un solo uso,
`reparto_entregado_ok`, que agrega el atributo `data-entregado-ok` únicamente en esa
carga de página), nunca en una recarga normal de la lista. (2) Cuando un repartidor tiene
envíos pendientes en **más de una zona distinta**, la lista ahora los agrupa bajo un
encabezado por zona (orden alfabético, "Sin zona" al final) en vez de una lista plana; el
badge de zona por tarjeta se oculta en ese caso (ya no hace falta, lo dice el encabezado).
Con una sola zona (o ninguna) entre los pendientes, se mantiene exactamente el
comportamiento anterior: lista plana con el badge en cada tarjeta. Verificado con curl +
Playwright: 3 envíos en 2 zonas muestran 2 encabezados con las tarjetas correctas debajo;
reduciendo a 1 zona, los encabezados desaparecen y vuelve el badge; vibración capturada
(`navigator.vibrate` instrumentado) tras confirmar "Entregado"; el marcador desaparece en
la siguiente carga (no se repite el sonido). `tests/run.php` 135/135.

### 7.8 ☑ Encuesta de satisfacción de entrega · L (depende de 7.7) · hecho (2026-08-03)

Ya existía `EncuestaPedido` (satisfacción del **proceso de compra**, una por pedido). Esto
es distinto: satisfacción de **la entrega física**, ligada al envío (`pedido_envios`) que
marcó "entregado" en 7.7, no al pedido completo — así un pedido con dos envíos puede
tener dos encuestas de entrega, una por cada uno.

**Implementado:**
- Migración `045`: tabla `envio_encuestas` (mismo patrón que `encuestas_pedido`):
  `envio_id` (FK `ON DELETE CASCADE`, `UNIQUE` — una respuesta por envío), `usuario_id`,
  `llego_completo`, `que_falto`, `satisfaccion` (1–5), `comentario`. Reutiliza el permiso
  `encuestas.ver` ya existente (misma sección del panel) — no se creó un permiso nuevo.
- `App\Models\EnvioEncuesta`: mismo patrón que `EncuestaPedido` (`crear`/`porEnvio`/
  `paginado`/`contar`/`metricas`/`exportar`), pero sin NPS/facilidad (no aplican a una
  entrega) y con `pct_completo` (% que llegó completo) en vez de eso. La consulta de
  panel hace `JOIN` con `pedido_items` para traer `num_partidas` de cada envío — es el
  "cruce" que pedía el roadmap contra lo exportado a SAE.
- `Pedido::envioConDetalle()` ahora también trae `p.usuario_id` (necesario para validar
  que el envío pertenece al cliente autenticado). `Pedido::enviosParaPortal()` (nuevo):
  envíos de un pedido con partidas y si ya tienen encuesta, para el detalle del portal.
- `PedidoController::show()` (portal) pasa los envíos del pedido; nuevo
  `encuestaEntrega($id, $envioId)` (POST `/portal/pedidos/{id}/envios/{envioId}/encuesta`)
  valida pertenencia (pedido del usuario + envío del pedido), que el envío esté
  `evento = 'entregado'` y que no se haya calificado ya — mismo patrón defensivo que
  `PedidoController::encuesta()` (el del proceso).
- `portal/pedido_detalle.php`: nueva sección "Entregas de este pedido" — una tarjeta por
  envío con ancla `id="envio-{id}"` (para el link del correo); según su estado muestra "te
  avisaremos cuando salga a ruta/se entregue" (aún no entregado), el formulario de
  calificación (entregado, sin calificar) o el resumen de lo ya respondido (entregado,
  calificado) — reutiliza el CSS de `.survey-card`/`.star-rating`/`.nps-scale` ya
  existente, sin una sola línea de CSS nueva.
- `emails/pedido_entregado.php`: el botón principal ahora es "Calificar mi entrega"
  (antes "Ver mi pedido", que baja de rango a link secundario) apuntando a
  `verUrl#envio-{id}` — al abrirlo, el navegador cae directo en la tarjeta de esa
  entrega específica. `RepartoController::avisarCliente()` arma esa URL.
- `Admin\EncuestaController::entregas()`/`entregasExportar()` + vista nueva
  `admin/encuestas_entrega.php`: mismo layout que `admin/encuestas.php` (métricas,
  distribución, tabla, exportar) con pestañas cruzadas ("Proceso de compra" /
  "Satisfacción de entrega") en ambas vistas para moverse entre las dos — se optó por
  **no** crear un nuevo permiso ni un nuevo link de nav; ambas cuelgan de
  `encuestas.ver` y del mismo ítem "Encuestas" del sidebar.

**Verificado E2E con curl + Playwright** (datos de prueba creados y eliminados después):
un pedido con dos envíos (uno entregado, otro en ruta) muestra el formulario solo en el
entregado y el mensaje de espera en el otro; se envía la calificación y de inmediato se
ve el resumen en vez del formulario; un segundo intento de calificar el mismo envío se
rechaza ("ya lo calificaste"); un cliente distinto intentando calificar el envío de otro
recibe "No encontramos ese envío" (falla exactamente en la comparación de `usuario_id`,
sin filtrar si el envío existe); el panel `/admin/encuestas/entregas` muestra las
métricas correctas (5.0/5, 100% completo, 1 respuesta) y la columna "Partidas
exportadas" coincide con lo real; exportar a Excel descarga un archivo válido; marcar
"entregado" desde `/reparto` (flujo real, no atajo directo al modelo) no generó ningún
error nuevo en el log al renderizar la plantilla de correo con el link nuevo.
Verificación visual con Playwright: las dos tarjetas de envío y la de proceso conviven
bien en la misma página, sin traslape ni CSS roto. `tests/run.php` 135/135 (sin
funciones puras nuevas que agregar).

Con esto, el **Grupo B queda completo** (7.5 ✅, 7.7 ✅, 7.8 ✅) — sigue el Grupo C
(7.6 facturas, 7.4 serie SAE por día de entrega).

### 7.6 ☑ Facturas y XML en el portal + notificación por correo · M (depende de 7.5) · hecho (2026-08-03)

**Implementado:**
- Migración `046`: tabla `pedido_facturas` (`envio_id` FK `ON DELETE CASCADE` — una
  factura corresponde a un envío, no al pedido completo, como se decidió; **sin**
  `UNIQUE` en `envio_id`, a propósito: una factura cancelada y reemitida puede convivir
  con la anterior en vez de forzar 1:1) con `archivo_pdf`, `archivo_xml`, `subido_por`,
  `created_at`. Los archivos viven en `storage/facturas/` — protegidos automáticamente
  por el `.htaccess` de `storage/` que ya existe (deniega todo acceso web directo,
  recursivo a cualquier subcarpeta nueva, sin tocar nada).
- `App\Core\Upload` se refactorizó (sin romper `documento()`, que usan los CVs): la
  lógica de validación de un documento se movió a un privado `guardarDocumento()`
  parametrizado por mapa MIME/tamaño/mensajes de error, y se agregaron
  `Upload::facturaPdf()` (reusa el mismo mapa `application/pdf` de los CVs) y
  `Upload::facturaXml()` (mapa nuevo `text/xml`/`application/xml` — confirmado en vivo
  que `finfo` detecta un XML real como `text/xml`, no como `text/plain` como se temía).
  Incluye tanto PDF como XML: **ambos obligatorios juntos** al subir (si falta uno, se
  borra el que sí se subió para no dejarlo huérfano) — coincide con la realidad de un
  CFDI, que siempre trae los dos documentos.
- `App\Models\PedidoFactura`: `crear`/`find`/`porEnvio`/`conDetalle` (con `pedido_id` y
  `usuario_id` del dueño, para validar pertenencia)/`paraPedido`/`eliminar`.
- Panel: en `admin/pedido_detalle.php`, cada tarjeta de envío (sección "Envíos y
  reparto") ahora tiene su propia sub-sección de facturas — listado con botones
  PDF/XML/Eliminar y un formulario de subida (dos `<input type="file">`), visible solo
  con `pedidos.sincronizar_erp` (mismo permiso que ya gatea todo lo de SAE/ERP en ese
  controlador, coherente con que una factura corresponde a un envío ya exportado).
  `Admin\PedidoController::subirFactura()`/`descargarFactura()`/`eliminarFactura()`
  reutilizan `verificarAcceso()` (alcance de vendedor) ya existente en el controlador.
- Portal: en `portal/pedido_detalle.php`, la tarjeta de cada envío (sección "Entregas de
  este pedido") muestra los links de descarga si hay factura — **primer precedente en
  el proyecto de descarga de archivo privado desde el portal** (antes solo existía
  desde el panel, ej. CVs); se construyó verificando pertenencia con el mismo patrón
  usado en el resto del portal (`usuario_id` del pedido dueño del envío == usuario
  autenticado), sin revelar si la factura existe cuando no es del cliente.
- Correo `emails/factura_nueva.php` al cliente cuando se sube, con link directo a
  `verUrl#envio-{id}` (mismo patrón de ancla que 7.7/7.8) — no adjunta los archivos,
  solo notifica con el link, mismo patrón que el resto de correos transaccionales del
  proyecto.

**Verificado E2E con curl + Playwright** (con un PDF y un XML mínimos reales — no solo
simulados —, confirmando primero con `finfo` en este entorno que se detectan como
`application/pdf`/`text/xml`): subir factura completa, el PDF y el XML descargados son
byte-idénticos a los originales tanto desde el panel como desde el portal; falta un
archivo → bloqueado con mensaje claro; un XML disfrazado de PDF (mismo contenido, campo
cambiado) → rechazado por la validación de **contenido real**, no por nombre/extensión
declarada; un cliente distinto no puede descargar la factura de otro (mismo patrón
"No encontramos esa factura" sin filtrar existencia); eliminar borra la fila y **ambos**
archivos de disco, sin huérfanos tras los intentos fallidos tampoco. `tests/run.php`
135/135 (sin funciones puras nuevas).

Con esto, el **Grupo C** avanza — queda solo 7.4 (serie de SAE por día de entrega).

### 7.4 ☑ Serie de SAE según el día de entrega (L, M, X, J, V) · hecho (2026-08-03)

**Decisiones del usuario (2026-08-03):** la fecha de entrega se captura **al exportar a
SAE** (no antes), viviendo en `pedido_envios.fecha_entrega` — no en `pedidos`, porque
desde 7.5 un pedido puede exportarse en varias remesas con fechas distintas. Sábado y
domingo **no se permiten**: la captura los rechaza (aviso JS + validación de servidor).

**Implementado:**
- Migración 047: `pedido_envios` gana `fecha_entrega DATE`, `sae_serie CHAR(1)`,
  `sae_consecutivo INT UNSIGNED` (quedan como registro permanente de la clave SAE
  realmente usada — antes no se guardaba en ningún lado, solo aparecía en el Excel
  descargado). Nueva tabla `sae_series_consecutivos` (`serie` PK, `ultimo_consecutivo`),
  pre-sembrada para L/M/X/J/V.
- `App\Models\SaeSerieConsecutivo`: `siguienteConsecutivo()` (avanza atómico, dentro de
  transacción), `todas()` (para el panel) y `establecer()` (corrección manual).
- `SaeExport::serieDelDia()`: día ISO (`date('N')`) → L/M/X/J/V, `null` para sábado/
  domingo. `claveDocumento()` gana un `?string $serie` opcional (si no se pasa, cae a la
  serie fija de `config('app.erp')` — compatible con el comportamiento previo). De paso
  se llenó la columna "Fecha de entrega" del Excel de SAE, que siempre iba vacía.
- `Pedido::crearEnvio()` gana parámetros opcionales `fechaEntrega`/`saeSerie`/
  `saeConsecutivo` para persistirlos en el envío creado.
- Exportación individual (`pedido_exportar.php`): el antiguo campo "Último folio" se
  reemplazó por un `<input type="date">` obligatorio (con aviso en vivo de qué serie
  corresponde); el servidor rechaza fin de semana y calcula serie + consecutivo solo.
- Exportación por lote (`pedidos_exportar.php`): cada pedido del lote captura **su
  propia** fecha (pueden caer en días distintos → series/consecutivos independientes).
  El campo no es `required` a nivel HTML (para no bloquear el envío de todo el
  formulario por pedidos que el admin no piensa tocar en esa tanda); el servidor excluye
  del lote, con un aviso puntual, solo los pedidos con fecha faltante o de fin de semana
  — el resto del lote se exporta igual (mismo patrón de tolerancia a errores de 7.5).
- Nueva pantalla `/admin/series-sae` (permiso `pedidos.sincronizar_erp`, sin permiso
  nuevo): lista las 5 series con su último consecutivo y permite corregirlo a mano
  (reconciliación con SAE si algo se exportó fuera del sistema). Enlazada desde
  `/admin/pedidos`.
- `pedido_detalle.php`: cada tarjeta de envío ahora muestra su clave SAE (serie +
  consecutivo) y fecha de entrega.

**Verificado E2E (Playwright, datos desechables):** exportación individual con fecha en
sábado rechazada (aviso de servidor, sin generar envío); con fecha entre semana (lunes)
genera el envío con `sae_serie`/`sae_consecutivo` correctos, el Excel trae la clave con
el padding esperado (`"L        1"`) y la columna "Fecha de entrega" ya no va vacía; el
detalle del pedido muestra "Clave SAE: L1 · Entrega: 03/08/2026". Lote con dos pedidos
(uno en martes, otro con fecha en sábado a propósito) exportó solo el válido, con su
propia serie "M" y consecutivo independiente del de "L", y avisó del excluido sin
bloquear el resto. Pantalla de reconciliación: corregir manualmente el consecutivo de
una serie y revertirlo funciona correctamente. `php tests/run.php` sigue en 135/135.

---

## Backlog / mejoras futuras — ✅ COMPLETADO (2026-07-16)

- ☑ Historial / reexportación de pedidos a SAE — hecho (2026-07-16): tabla `pedido_exportaciones` (migración 024), se registra cada export (individual/lote), historial en el detalle del pedido y botón "Reexportar" (el export individual ya funcionaba para cualquier pedido).
- ☑ Notificación al cliente cuando su pedido cambia de estado — hecho (2026-07-16): correo `emails/pedido_estado` en `updateEstado` y `sincronizarErp` (etiquetas amigables; no notifica en `borrador`).
- ☑ Buscador de productos en el catálogo público — hecho (2026-07-16): campo `q` (nombre/SKU) combinable con el filtro de categoría, paginado, conserva búsqueda al cambiar de categoría/página.
- ☑ Dashboard admin con métricas — hecho (2026-07-16): pedidos por estado y cotizaciones por estado (barras horizontales) + cotizaciones por semana (8 semanas, barras verticales), en CSS puro (sin librerías, respeta CSP). Métodos `contarPorEstado`/`porSemana`.
- ☑ Enrutar o eliminar `pages/proximamente.php` — hecho (2026-07-16): eliminado (era una vista huérfana sin referencias).
- ☑ Panel de auditoría (ingresos + cambios) y visor de errores — hecho (2026-07-15), permiso granular `auditoria.ver`.

---

## Mejoras post-auditoría (2026-07)

Tras la auditoría de seguridad/performance y su endurecimiento (guardia CLI, mantenimiento, sesión):

- ☑ **Repetir pedido** — hecho: botón "Volver a pedir" en el detalle del pedido del portal recarga las partidas (productos activos) en el carrito.
- ☑ **Panel de vendedor** — hecho: el admin se filtra por vendedor asignado con el permiso `ventas.solo_asignados` (clientes/pedidos/cotizaciones + dashboard), con blindaje IDOR en detalle/acciones y bloqueo de la exportación por lote.
- ☐ **Listas de precios por cliente** — precios diferenciados por cliente (B2B). Pausado.
- ☑ **Inventario / disponibilidad** — hecho (2026-09-18), ver Fase 6.2 arriba.
- ☑ **Reportes exportables** — hecho: sección `/admin/reportes` (permiso `reportes.ver`, admin+ventas) con 4 reportes en Excel por rango de fechas — pedidos, cotizaciones/leads, productos más pedidos y clientes por ventas; respeta el alcance de vendedor.
- ☑ **WhatsApp click-to-chat** — hecho: número centralizado en `.env` (`WHATSAPP_NUMERO`) + helper `whatsapp_url()`, botón flotante en el sitio público, y enlaces con mensaje prellenado en el catálogo (por producto) y en el detalle de pedido del portal (con folio).
- ☑ **Encuesta de experiencia de pedido** — hecho: el cliente califica el proceso desde el detalle de su pedido (satisfacción 1-5, facilidad 1-5, NPS 0-10, comentario; una respuesta por pedido, estados enviado/en_proceso/sincronizado); panel `/admin/encuestas` con promedios, NPS, distribución y export a Excel (permiso `encuestas.ver`).
- ☑ **Bolsa de trabajo** — hecho: vacantes públicas en `/bolsa-de-trabajo`, postulación con CV (PDF en `storage/`, inaccesible por URL), avisos por correo a RRHH y al candidato (confirmación + cita de entrevista); panel de vacantes y postulaciones con descarga de CV y agendado de cita; purga a 365 días.
- ☑ **Libreta de visitas (kiosco)** — hecho: registro de visitantes en tablets autorizadas (cookie de dispositivo, sin IP), notifica por correo al anfitrión; panel con historial, dispositivos y anfitriones (permisos `visitas.*`); purga a 365 días.
- ☑ **Bitácora de visitas por oficinas** (2026-09-17) — hecho: la libreta de visitas se separó por oficina (`Oficina`, `oficina_id` en dispositivos/anfitriones/visitas/usuarios); un dispositivo del checador solo ve/registra anfitriones de su misma oficina, y un usuario sin `visitas.ver_todas` solo ve la bitácora de la suya. Retrocompatible (`oficina_id = NULL` se comporta como antes). Ver `docs/DESPLIEGUE.md §4.5` para el paso manual de asignar oficina a lo ya existente al desplegar.
- ☑ **Correo saliente vía Office 365** (2026-09-17) — hecho: alternativa a SMTP configurable desde el panel (`/admin/configuracion-correo`, permiso `configuracion.correo`), usando Microsoft Graph en modo app-only (Client Credentials, sin usuario interactivo). Client secret cifrado con `App\Core\Crypto` (AES-256-GCM, clave derivada de `APP_KEY`); tiene prioridad sobre SMTP solo si hay una configuración activa, sin fallback silencioso entre ambos. Ver `docs/DESPLIEGUE.md §4.4` para el paso de registrar la app en Azure AD/Entra ID (obligatorio, fuera del sitio).
- ☐ **WhatsApp notificaciones automáticas** (fase futura) — mensajes salientes de cambio de estado requieren WhatsApp Business API (Meta Cloud API): cuenta Business, token, plantillas aprobadas y costo por conversación. Pendiente de que el negocio active esa cuenta.

---

## Estado y orden recomendado

Hechas: **Fase 1** (correo), **Fase 2** (galería), **Fase 3** (recuperar contraseña), **Fase 4** (importador de catálogo), **Fase 5** (robustez). Extras: módulo de auditoría + visor de errores; **módulo de cotizaciones** con partidas/precios (cliente arma → ventas cotiza → cliente aprueba → convierte a pedido; correo + imprimible).
Pendiente:

1. **Fase 0 (bloqueantes de producción)** — cerrar junto con la fecha de despliegue (SSL, APP_KEY, AllowOverride, rotar credenciales de prueba).
2. Operativo: cargar el inventario real con el importador y subir imágenes por el panel.
3. **Fase 7 (propuesta 2026-07-31) — ✅ COMPLETA (2026-08-03)** — logística, ventas
   asistidas y catálogo por cliente. **Grupo A completo: 7.9 ✅, 7.2 ✅, 7.10 ✅, 7.1 ✅
   (2026-07-31) y 7.3 ✅ (2026-08-01)**. **Grupo B completo: 7.5 ✅ (2026-08-01), 7.7 ✅
   (2026-08-02) y 7.8 ✅ (2026-08-03)** — crean y usan la entidad `pedido_envios`.
   **Grupo C completo: 7.6 ✅ y 7.4 ✅ (ambos 2026-08-03)** — facturas y XML en el portal,
   y serie de SAE por día de entrega.
</content>
