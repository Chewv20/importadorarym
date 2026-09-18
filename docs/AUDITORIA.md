# Auditoría de seguridad y rendimiento — 27/07/2026

> **Auditoría de seguimiento — 12/08/2026.** Cubrió todo lo añadido entre el 27/07 y el
> 08/08 (reparto y encuesta de entrega, pedidos/facturas SAE, simulador de logo,
> cotización con logo de impresión, solicitud de empleo general) en cuatro frentes:
> seguridad, calidad de código, visual/accesibilidad y robustez funcional/operativa.
> Detalle completo en «Auditoría de seguimiento — 12/08/2026» al final de este
> documento.

Revisión completa del código (173 archivos PHP, vistas, JS, `.htaccess`, migraciones).
Estado de partida: 30/30 pruebas de `tests/run.php` en verde, sin errores de sintaxis.

Este documento recoge **lo que falta**; las medidas ya implementadas están en
[SEGURIDAD.md](SEGURIDAD.md) y se verificaron como correctas (ver "Lo que está bien").

> **Estado: CORREGIDO (27–28/07/2026).** Todos los hallazgos salvo R7 (diferido con disparador) y R12 (configuración de servidor)
> están aplicados y verificados; la suite pasó de 30 a 48 pruebas. Cada apartado
> corregido lleva su nota **✔ Aplicado**. El resumen de la implementación, con los
> dos problemas que la verificación destapó sobre la marcha, está al final en
> «Implementación».

---

## Resumen

| Severidad | Seguridad | Rendimiento |
|---|---|---|
| Alta      | 0 | 1 |
| Media     | 5 | 4 |
| Baja      | 6 | 5 |

No se encontró ninguna vulnerabilidad explotable **sin autenticación**: inyección SQL,
CSRF, IDOR y XSS reflejado están cubiertos de forma consistente. Los hallazgos de
severidad media son de **escalada de privilegios entre roles internos** y de
**endurecimiento**.

---

## Seguridad

### S1 · Escalada de privilegios en la gestión de usuarios — MEDIA

> **✔ Aplicado.** Roles y overrides acotados a lo que ya posee quien edita; `roles.gestionar` actúa de llave de administrador. Nadie ajusta su propio rol ni sus permisos.

`app/Controllers/Admin/UsuarioController.php:79-132`

Un usuario con el permiso `usuarios.gestionar` que **no** sea administrador puede:

- asignar el rol `admin` a cualquier cuenta, **incluida la suya**;
- conceder mediante *overrides* permisos que él mismo no posee (`$_POST['override']`
  se acepta sin comprobar el permiso del actor).

No hay comprobación de que el actor posea los permisos que otorga ni de que no se
esté editando a sí mismo.

**Corrección**: filtrar los roles y permisos asignables a los que el actor ya tiene
(`Auth::permissions()`), y rechazar `id === Auth::id()` para los campos de rol,
`activo` y overrides.

### S2 · Cuentas de cliente editables desde el módulo de usuarios internos — MEDIA

> **✔ Aplicado.** `objetivoInterno()` rechaza cuentas de cliente en `edit()` y `update()`.

`app/Controllers/Admin/UsuarioController.php:68-115`

`edit()` carga con `Usuario::autenticado($id)`, que devuelve **cualquier** usuario, y
`update()` llama a `actualizarInterno()`, un `UPDATE` sin filtro de rol. El listado
`internos()` sí excluye clientes, pero la URL directa
`/admin/usuarios/{id_de_cliente}/editar` funciona: permite cambiar la contraseña de un
cliente y suplantarlo en el portal.

**Corrección**: verificar `rol_slug <> 'cliente'` en `edit()` y en `update()` antes de
tocar la fila.

### S3 · Enlace `javascript:` en el modal promocional — MEDIA

> **✔ Aplicado.** Helper `url_http_valida()`, con pruebas para `javascript:` y `data:`.

`app/Controllers/Admin/ModalController.php:128-132` → `app/Views/partials/modal_promo.php:12`

`filter_var($url, FILTER_VALIDATE_URL)` **acepta** `javascript://%0aalert(1)` y
`data:`. El valor se emite tal cual en el `href` del modal que ve todo visitante del
sitio público → XSS almacenado con alcance a usuarios anónimos.

La CSP actual (`script-src` sin `'unsafe-inline'`) bloquea los URI `javascript:` en
navegadores modernos, por lo que hoy es defensa en profundidad, no explotación directa.

**Corrección**:

```php
private function enlace(): ?string
{
    $url = str_clean($_POST['enlace'] ?? '', 255);
    if (!filter_var($url, FILTER_VALIDATE_URL)) return null;
    $esquema = strtolower((string) parse_url($url, PHP_URL_SCHEME));
    return in_array($esquema, ['http', 'https'], true) ? $url : null;
}
```

### S4 · Token de activación del kiosco sin caducidad — MEDIA

> **✔ Aplicado.** Caduca a las 24 h (migración `030`); verificado contra la base real.

`app/Models/ChecadorDispositivo.php:15-33`

El token de activación de una tablet vive **indefinidamente** hasta que alguien lo usa.
Un enlace enviado por chat o correo hace meses sigue armando un dispositivo autorizado
(cookie válida un año).

**Corrección**: columna `activacion_expira_en` (p. ej. 24 h) y filtrarla en
`porActivacionToken()`.

### S5 · Reutilización del reto anti-bot durante una hora — MEDIA

> **✔ Aplicado.** El reto se consume al validarse; vigencia de 30 min.

`app/Helpers/functions.php:299-318`

El captcha es sin estado: la tripleta `(_cap_ts, _cap_sig, captcha)` valida durante
3600 s y **no se marca como consumida**. Un bot resuelve la suma una vez y reenvía la
misma tripleta en envíos masivos durante toda la hora. Además solo hay 17 respuestas
posibles (2–18), así que el acierto ciego ronda el 6 %.

El rate limit por IP (5/10 min en cotización, 5/h en postulación) lo contiene
parcialmente, pero se elude rotando IPs.

**Corrección**: bajar la ventana a ~10 min y registrar los `sig` consumidos
(`RateLimiter` o una tabla pequeña con TTL).

### S6 · Enumeración de usuarios por temporización en el login — BAJA

> **✔ Aplicado.** Hash dummy + piso de 400 ms + coste bcrypt unificado con migración transparente. Medido: 6,2 ms de diferencia entre casos.

`app/Core/Auth.php:16-23`

Si el correo no existe se retorna sin ejecutar `password_verify()` (~100 ms de bcrypt).
La diferencia de tiempo distingue correos registrados de no registrados, pese al mensaje
neutro.

**Corrección**: ejecutar `password_verify($password, $hashDummy)` en la rama negativa.

### S7 · `urldecode()` de la URI completa antes de enrutar — BAJA

> **✔ Aplicado.** El path ya no se decodifica antes de enrutar; `/admin%2Fusuarios` pasó de resolver a `/admin/usuarios` a devolver 404.

`public/index.php` (cálculo de `$uri`) y `app/Core/Router.php:30-46`

La URI se decodifica **antes** del match de rutas, de modo que `%2F` se convierte en
separador de segmentos y altera qué ruta coincide. No produce escalada (toda ruta pasa
por `Auth::authorize()`), pero permite eludir reglas de servidor o WAF basadas en la
ruta literal.

**Corrección**: enrutar con el path sin decodificar y aplicar `urldecode()` solo a los
parámetros capturados en `Router::dispatch()`.

### S8 · Otros puntos de endurecimiento — BAJA

> **✔ Aplicado.** Cabeceras desde PHP, token de verificación con caducidad de 48 h, `style-src-elem` sin inline y rotación de logs con `RateLimiter::gc()` por muestreo.

- **Tokens en la ruta URL**: los de restablecimiento y verificación viajan en el path,
  quedando en los logs del servidor y en la cabecera `Referer` hacia terceros. El de
  verificación además **no expira nunca** (`Usuario::porTokenVerificacion` no filtra por
  fecha).
- **`style-src 'unsafe-inline'`** en la CSP, necesario por los `style="width:…%"` de
  `admin/dashboard.php:41,55,71` y `admin/encuestas.php:51` y por estilos sueltos en
  `admin/modales.php`, `modal_form.php` y `logo_cliente_form.php`. Contradice además la
  convención de [ESTILOS.md](ESTILOS.md). Sustituibles por variables CSS
  (`style="--pct:37"`) o clases de escalón para poder retirar `'unsafe-inline'`.
- **Cabeceras solo en `.htaccess`**: `X-Frame-Options` y `Referrer-Policy` dependen de
  `mod_headers`; `nosniff` sí se emite también desde PHP. Conviene emitirlas desde
  `public/index.php` igual que las demás.
- **Sin rotación del log de errores** (`storage/logs/php-error.log`) — ver R1.
- **`RateLimiter::gc()` no está agendado**: sin cron, `storage/cache/throttle/` acumula
  un archivo por IP indefinidamente.
- **`activar()` del kiosco es un GET con efecto secundario**: si un administrador abre
  el enlace por error, consume la activación y se lleva la cookie de kiosco.

---

## Rendimiento

### R1 · El visor de errores carga el log completo en memoria — ALTA

> **✔ Aplicado.** El visor lee solo la cola (256 KB) y los logs rotan a los 5 MB.

`app/Controllers/Admin/AuditoriaController.php:56-59`

```php
$contenido = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
$lineas = array_reverse(array_slice($contenido, -300));
```

Se carga el archivo **entero** para mostrar 300 líneas. Sin rotación, un log que crezca
a cientos de MB (un error recurrente en producción lo consigue en días) provoca un fatal
por `memory_limit` — y precisamente en la pantalla que se usa para diagnosticarlo.

**Corrección**: leer la cola con `SplFileObject::seek()` o `fseek()` desde el final;
añadir rotación al superar ~5 MB.

### R2 · 3.4 MB de imágenes muertas en el repositorio — MEDIA

> **✔ Aplicado (28/07/2026), con corrección del hallazgo.** `assets/img/clientes/` SÍ estaba en uso (la migración 023 apunta ahí): solo eran huérfanos los 3,1 MB de originales en `assets/img/logos/clientes/`, movidos a `storage/backups/`. Ver «Fase 2 aplicada».

`public/assets/img/logos/clientes/` (20 archivos, hasta **403 KB** cada uno) y
`public/assets/img/clientes/` (20 archivos) **no se referencian desde ningún archivo**
del proyecto: la marquesina de clientes se sirve desde la tabla `clientes_logos` y
`public/uploads/`. Son peso muerto en cada despliegue y en cada copia del proyecto.

**Corrección**: eliminarlas tras confirmar que no se usan en material externo.

### R3 · Sin cache-busting en CSS y JS — MEDIA

> **✔ Aplicado.** `asset()` versiona con `?v=filemtime`.

`public/.htaccess` cachea CSS/JS **7 días** (`ExpiresByType … access plus 7 days`) y las
etiquetas se emiten sin versión (`asset('assets/css/site.css')`). Tras un despliegue,
los navegadores que ya visitaron el sitio siguen sirviendo el CSS viejo hasta una
semana. El `VERSION` del service worker solo gobierna su propia caché.

**Corrección**: versionar en `asset()`:

```php
function asset(string $path): string
{
    $abs = PUBLIC_PATH . '/' . ltrim($path, '/');
    $v   = is_file($abs) ? '?v=' . filemtime($abs) : '';
    return base_url($path) . $v;
}
```

### R4 · Imágenes sin `loading="lazy"` ni dimensiones — MEDIA

> **✔ Aplicado.** `loading="lazy"` donde corresponde; `width`/`height` en los logos de marca.

18 de las 20 etiquetas `<img>` de las vistas carecen de `loading="lazy"` y de
`width`/`height`. Solo `partials/clientes.php` y `partials/proveedores.php` los tienen.
Impacto: descargas innecesarias y *layout shift* (CLS) en Core Web Vitals.

### R5 · Importación CSV sin límite de filas ni transacción — MEDIA

> **✔ Aplicado.** Tope de 5 000 filas y transacción con rollback.

`app/Core/CatalogoImport.php:69-143`

Se aceptan hasta 2 MB de CSV sin cota de filas; cada fila ejecuta entre 2 y 4 consultas
(`porSku`, `porClaveSae`, `crear`/`actualizar`, `setPrecio`), todas fuera de
transacción. Un archivo de ~30 000 filas supera el `max_execution_time` a mitad de la
importación y deja el catálogo a medias, sin posibilidad de revertir.

**Corrección**: cota de filas (p. ej. 5 000), transacción por lotes y `set_time_limit()`
explícito.

### R6 · Consultas sin paginación — BAJA

> **✔ Aplicado.** `porUsuario()` acotado a 200; carrito y solicitud a 200 partidas.

Devuelven la tabla completa: `Pedido::porUsuario`, `Cotizacion::porUsuario`,
`Producto::activos`, `Anfitrion::todos`, `Vacante::todas`, `ClienteLogo::activos`,
`Categoria::todas`. Hoy son conjuntos pequeños; el pedido de un cliente antiguo con
cientos de registros ya carga toda su historia en "Mis pedidos".

### R7 · Búsqueda con `LIKE '%q%'` — BAJA

> **⏸ Pendiente:** solo compensa con decenas de miles de SKU.

`app/Models/Producto.php:104` — el comodín inicial impide usar `idx_productos_sku`, así
que cada búsqueda del catálogo y del autocompletar hace un *full scan* de `productos`.
Con catálogos de decenas de miles de SKU conviene un índice `FULLTEXT` sobre
`nombre, sku` con `MATCH … AGAINST`.

### R8 · Exportaciones a Excel enteramente en memoria — BAJA

> **✔ Aplicado.** Tope de 10 000 filas por exportación, con aviso al alcanzarlo.

`Xlsx::crear()` construye el binario completo como string y los controladores hacen
`fetchAll()` sin límite. Visitas y encuestas exportan **sin rango de fechas
obligatorio**: con el histórico completo la petición puede agotar la memoria.

**Corrección**: exigir rango de fechas y/o cota de filas en la exportación.

### R9 · Fuentes de Google en la ruta crítica — BAJA

> **✔ Aplicado (28/07/2026).** Inter (variable, 47 KB) y Poppins autoalojadas; 0 conexiones externas y 70 KB por visitante frente a ~219 KB. Ver «Fase 3 aplicada».

`layouts/main.php` y `partials/head_basic.php` cargan Inter + Poppins desde
`fonts.googleapis.com`: 2 resoluciones DNS + 2 handshakes TLS bloqueando el render,
más una dependencia externa que la CSP tiene que autorizar. Autoalojar los `.woff2` en
`public/assets/fonts/` (la carpeta ya existe) elimina ambos y permite endurecer la CSP.

### R10 · 4 hojas CSS por página — BAJA

> **✔ Aplicado (28/07/2026).** `build/assets.php` genera 4 bundles (site 6,9 KB con gzip). Desarrollo sin cambios; si falta el build, se vuelve a las hojas sueltas. Ver «Fase 3 aplicada».

`brand.css` + `app.css` + `components.css` + `site.css` = ~43 KB sin minificar en 4
peticiones. Con HTTP/2 el coste es moderado, pero un paso de concatenado + minificado
en el despliegue reduciría a un archivo de ~12 KB con gzip.

### R11 · Consultas en parciales globales — BAJA

> **✔ Aplicado (28/07/2026).** `App\Core\Cache` con invalidación dentro de los métodos de escritura del modelo. Ojo: el ahorro real depende de OPcache (−58 % con él, −4 % sin él). Ver «Fase 2 aplicada» y R12.

`partials/modal_promo.php` y `partials/clientes.php` consultan la base de datos en
**cada página** del sitio público (2 consultas extra por request, incluidas las de
usuarios anónimos). Son tablas diminutas y de cambio muy infrecuente: un caché en
archivo con invalidación al guardar desde el panel las eliminaría por completo.

---

## Lo que está bien (verificado)

- **SQL**: 100 % *prepared statements*. Las tres interpolaciones en cadenas SQL
  (`Pedido.php:237`, `Producto.php:48`, `Producto.php:262`) construyen únicamente
  placeholders o enteros pasados por `intval` — no hay superficie de inyección.
  `ATTR_EMULATE_PREPARES => false`.
- **XSS**: escape con `e()` sistemático; ninguna salida cruda de datos en las vistas.
  El autocompletar de `admin.js` usa `textContent`, no `innerHTML`.
- **CSRF**: los 47 endpoints POST verifican token; los controladores con varias
  acciones lo centralizan en un `guard()` privado.
- **IDOR**: las rutas del portal comprueban `usuario_id` contra el dueño; el panel
  aplica `vendedorScope()` con `verificarAcceso()` en cotizaciones, pedidos y clientes.
- **Subidas**: `Upload` valida MIME real con `finfo`, renombra con `random_bytes`,
  reprocesa las imágenes con GD y guarda los CV en `storage/` (fuera de la web).
  `public/uploads/.htaccess` desactiva el motor PHP.
- **Sesión**: `session_regenerate_id` al entrar y salir, huella HMAC del hash de
  contraseña para invalidar sesiones tras un cambio, timeout por inactividad,
  cookies `HttpOnly`/`SameSite=Lax`/`Secure` condicional.
- **Contraseñas**: `password_hash` con el algoritmo por defecto, política de 8+ con
  mayúscula, minúscula, dígito y símbolo, tokens de reset hasheados con caducidad de
  60 min y respuesta neutra.
- **N+1**: resuelto explícitamente donde importaba (`porIds`, `imagenesPorProductos`).
- **Paginación**: presente en todos los listados del panel y del catálogo.

---

## Implementación (27/07/2026)

Todos los hallazgos de seguridad y los cuatro de rendimiento con mayor impacto quedaron
aplicados y verificados. La suite pasó de **30 a 48 pruebas**, todas en verde.

### Qué se tocó

| Hallazgo | Corrección | Archivos |
|---|---|---|
| S1 | Un rol solo se asigna si quien edita ya tiene sus permisos; `roles.gestionar` es la llave de administrador. Los overrides solo conceden permisos que el actor posee. Nadie ajusta su propio rol/permisos. | `Admin/UsuarioController.php`, `admin/usuario_form.php`, `Models/Permiso.php` |
| S2 | `edit()`/`update()` exigen que el objetivo sea un usuario **interno** (`objetivoInterno()`). | `Admin/UsuarioController.php` |
| S3 | `url_http_valida()`: solo `http`/`https` en el href del modal. | `Helpers/functions.php`, `Admin/ModalController.php` |
| S4 | El enlace de activación del kiosco caduca a las 24 h (`activacion_expira_en`). | migración `030`, `Models/ChecadorDispositivo.php`, `admin/visitas_dispositivos.php` |
| S5 | El reto anti-bot se consume al validarse y su vigencia baja a 30 min. | `Helpers/functions.php` |
| S6 | Hash dummy + **piso de 400 ms** en cada intento; coste bcrypt unificado a 12 con migración transparente al iniciar sesión. | `Core/Auth.php`, `Models/Usuario.php` |
| S7 | El path ya no se decodifica antes de enrutar; el Router decodifica solo los parámetros capturados. | `public/index.php`, `Core/Router.php` |
| S8 | `X-Frame-Options`, `Referrer-Policy` y `Cross-Origin-Opener-Policy` emitidas desde PHP; token de verificación con caducidad de 48 h; `style-src-elem` sin `unsafe-inline`. | `public/index.php`, `Models/Usuario.php`, migración `030` |
| R1 | El visor lee solo la cola del log (256 KB) y los logs rotan a los 5 MB. | `Core/Log.php` (nuevo), `Admin/AuditoriaController.php` |
| R3 | `asset()` versiona con `?v=filemtime`. | `Helpers/functions.php` |
| R4 | `loading="lazy"` donde corresponde y `width`/`height` en los logos de marca. | vistas de portal, admin, kiosco y parciales |
| R5 | Tope de 5 000 filas por importación y transacción: o entra todo, o no entra nada. | `Core/CatalogoImport.php` |
| R6/R8 | Cotas en `porUsuario()` (200) y en las exportaciones (10 000, con aviso al llegar al tope). Carrito y solicitud limitados a 200 partidas. | modelos `Pedido`, `Cotizacion`, `Visita`, `EncuestaPedido`; controladores de portal, visitas y encuestas |

Además, los estilos inline pasaron a CSS (clases `.thumb-modal`, `.preview-img` y las
barras con `--pct`/`--px`), lo que permitió endurecer la CSP: `style-src-elem` ya no
admite inline y el único `<style>` del proyecto va con nonce.

### Dos problemas que la verificación destapó

Ninguno era visible leyendo el código; salieron al probar contra la base real:

1. **La regla de S1 dejaba al administrador sin poder dar de alta vendedores.** El rol
   `ventas` tiene `ventas.solo_asignados`, un permiso que **restringe** (limita al
   vendedor a sus clientes) y que el administrador no posee. La comparación de
   subconjuntos lo trataba como si fuera poder y bloqueaba la asignación. Se resolvió
   excluyendo los permisos restrictivos (`PERMISOS_RESTRICTIVOS`) y reconociendo
   `roles.gestionar` como llave de administrador.

2. **El hash dummy de S6 no bastaba**: las contraseñas de la base tenían costes bcrypt
   mezclados (10 y 12 → 54 ms frente a 222 ms), así que el tiempo seguía delatando la
   cuenta, y el rehash solo actúa tras un login correcto. Se añadió un piso de 400 ms
   por intento. Medido: **6,2 ms de diferencia** entre correo inexistente, cuenta con
   hash antiguo y cuenta al día.

### Verificación

- `php tests/run.php` → **48/48**. Grupos nuevos: antirreplay del captcha, URL externa
  segura, assets versionados, rotación de logs y enrutado sin `urldecode` global.
- `php -l` sobre los 175 archivos PHP, sin errores.
- Migración `030` aplicada sin incidencias.
- Servidor real: landing, catálogo, contacto, bolsa y login **200**; `/admin` y
  `/portal` **302**; `/checador` **403**; inexistente **404**. `/admin%2Fusuarios` pasó
  de resolver a `/admin/usuarios` a devolver **404**.
- Cabeceras confirmadas en la respuesta HTTP; assets servidos con `?v=`.
- Contra la base real: caducidad del token de kiosco, login correcto e incorrecto,
  migración de hash 10 → 12 y matriz de roles asignables por rol.

### Pendiente (no aplicado)

- **R2** — borrar los 3,4 MB de imágenes sin referenciar. Es irreversible y el proyecto
  no está bajo control de versiones: **requiere tu confirmación**.
- **R7** — índice `FULLTEXT` para la búsqueda del catálogo. Solo compensa con decenas de
  miles de SKU; hoy sería complejidad sin ganancia.
- **R9/R10** — autoalojar las fuentes y minificar/concatenar el CSS. Mejoras de
  despliegue, mejor abordadas junto con el paso a producción.
- **R11** — cachear los parciales que consultan la base en cada página.

---

## Fase 2 aplicada (28/07/2026)

### R2 — corrección del hallazgo original

El informe decía «3,4 MB en dos carpetas sin referenciar». **Era incorrecto en la mitad**:
`public/assets/img/clientes/` (492 KB) **sí está en uso** — la migración
`023_create_clientes_logos.sql` siembra `clientes_logos` apuntando ahí y las 20 filas de
la base lo confirman. La verificación inicial solo buscó referencias en `app/` y
`public/assets`, no en `database/`. Borrar esa carpeta habría dejado la marquesina de
clientes sin imágenes.

Lo realmente huérfano son los **originales sin recortar** de
`public/assets/img/logos/clientes/`: **3,1 MB** (11 PNG + 9 JPG), cero referencias en todo
el proyecto. Se movieron a `storage/backups/img-originales-clientes-2026-07/` (fuera de la
raíz web, con un `LEEME.txt` que explica procedencia y cómo revertir) en vez de borrarse,
por ser el material fuente de los recortes y no haber control de versiones.

`public/assets/img` pasó de **3,9 MB a 828 KB**. Verificado: la marquesina sigue
renderizando sus 40 `<img>` y los archivos se sirven con HTTP 200.

### R11 — caché de datos del sitio

`App\Core\Cache` (nueva): caché en archivo, con escritura atómica, invalidación de
OPcache y autocurativa ante archivos corruptos. La invalidación vive **dentro de los
métodos de escritura del modelo** (`Modal` y `ClienteLogo`), no en el controlador, para
que no pueda olvidarse si mañana escribe otro código. TTL de 1 h como red de seguridad
por si se edita la base a mano. `mantenimiento.php` la vacía en cada corrida.

**El resultado honesto: depende por completo de OPcache.**

| | sin caché | con caché | efecto |
|---|---|---|---|
| OPcache apagado (estado actual del entorno) | 0,357 ms | 0,372 ms | **−4 %**, algo peor |
| OPcache encendido | 0,380 ms | 0,159 ms | **−58 %** |

La caché guarda los valores como archivos PHP, así que sin OPcache el `include` cuesta más
que la consulta a una MariaDB local con tablas diminutas. Lo que sí se ahorra en ambos
casos son **2 consultas por página** en todo el tráfico público (~20 000/mes con 10 000
visitas), que es carga sobre la base, no latencia perceptible.

### R12 (nuevo) · OPcache no está habilitado — ALTA

Descubierto al medir R11: ni en CLI ni en el servidor web. PHP recompila los ~175 archivos
del proyecto **en cada petición**. Es la mejora de rendimiento de mayor impacto disponible
y no requiere tocar código: configuración documentada en
[DESPLIEGUE.md §1.1](DESPLIEGUE.md).

### Verificación

- `php tests/run.php` → **55/55** (7 pruebas nuevas de caché, incluido el caso de un
  `null` cacheado —el sitio sin modal activo— que no debe confundirse con ausencia de
  caché, y el de archivo corrupto).
- Ciclo completo contra la base real: consultar → cachear → `crear()`/`actualizar()`/
  `eliminar()` invalidan → la siguiente lectura refleja el cambio → estado final idéntico
  al inicial.
- Sitio en marcha: landing, catálogo, contacto, bolsa y login **200**; `/admin` **302**;
  `/checador` **403**; inexistente **404**. HTML idéntico byte a byte antes y después de
  cachear (40 245 bytes), con los 40 logos de la marquesina.

---

## Fase 3 aplicada (28/07/2026)

### R9 · Tipografía autoalojada

Inter y Poppins pasan a servirse desde `public/assets/fonts/`. Se eliminan las dos
conexiones externas de la ruta crítica (`fonts.googleapis.com` + `fonts.gstatic.com`),
la CSP deja de autorizar dominios de Google para estilos y fuentes
(`style-src 'self'`, `font-src 'self'`), y los visitantes dejan de generar tráfico
hacia un tercero.

Dos hallazgos al hacerlo:

- **Poppins 800 se descargaba y no se usa en ningún sitio.** Los pesos reales son
  Inter 400/500/600/700 y Poppins 500/600/700 (vía `--rym-fw-*`). Se eliminó.
- **Inter tiene versión variable**: un archivo de 47 KB cubre los pesos 400–700, frente
  a 188 KB de los cuatro estáticos.

| | antes (desde Google) | ahora (autoalojado) |
|---|---|---|
| Conexiones externas | 2 (DNS + TLS) | 0 |
| Descarga en español | ~219 KB | **70 KB** |
| Archivos en disco | — | 169 KB (8 `.woff2`, latin + latin-ext) |

Los `.woff2` se sirven con `Cache-Control: immutable` a un año (el nombre cambia si
cambia la versión) y las tres fuentes visibles al inicio entran en el precache del
Service Worker, para que la PWA sin red no caiga a la fuente del sistema.

### R10 · Empaquetado y minificado de CSS

`build/assets.php` (CLI) concatena y minifica cuatro bundles. La composición se declara
una sola vez en `App\Core\Assets::BUNDLES`, que usan tanto el build como las vistas, de
modo que lo que se empaqueta y lo que se sirve no pueden desincronizarse.

| bundle | antes | minificado | con gzip |
|---|---|---|---|
| site | 46,8 KB en 5 archivos | 34,5 KB en 1 | **6,9 KB** |
| portal | 26,1 KB en 5 | 17,8 KB en 1 | 3,8 KB |
| admin | 40,7 KB en 5 | 30,2 KB en 1 | 5,9 KB |
| kiosco | 22,5 KB en 5 | 14,7 KB en 1 | 3,4 KB |

**El desarrollo no cambia**: con `APP_DEBUG=true` se siguen sirviendo las hojas sueltas,
así que no hay que reconstruir tras cada edición. Y si alguien olvida ejecutar el build
antes de publicar, `Assets::css()` comprueba que el bundle sea más nuevo que sus fuentes
y, si no lo es, vuelve a las hojas sueltas: se pierde la optimización, nunca la
corrección. `php build/assets.php --check` avisa (código 1) antes de publicar.

### Dos correcciones sobre la marcha

1. **`kiosco.bundle` omitía `components.css`.** En mi primera definición copié las hojas
   que cargaba cada layout sin advertir que `head_basic.php` añadía `components.css` a
   todos. Habría publicado el kiosco sin los estilos de sus componentes. Detectado al
   centralizar la definición en `Assets::BUNDLES`.
2. **El minificador rompía los data URI.** La limpieza estructural (`;` `,` `:`) se
   aplicaba sobre el texto completo, incluidos los tramos ya copiados de `url()` y de las
   cadenas, donde esos caracteres son datos y no estructura. Hoy el CSS no tiene ningún
   data URI, así que no se manifestaba, pero el primero que se añadiera habría salido
   corrupto. `App\Core\CssMin` aparta esos literales antes de limpiar y los repone al
   final; hay pruebas para el caso.

### Verificación

- **Equivalencia funcional**: comparador que extrae de cada hoja los pares
  (contexto, selector, propiedad: valor) normalizados y los contrasta con los del bundle.
  **3 382 declaraciones idénticas** en los cuatro (site 1 229, portal 587, admin 1 075,
  kiosco 491), cero diferencias.
  > Las tres primeras pasadas dieron «DIFIEREN», y en las tres el fallo era del
  > comparador, no del minificador: no normalizaba el espacio tras coma dentro de los
  > valores (`rgba(1, 2, 3)`), el ` !important` ni el `:` de las media queries. Son
  > formas distintas del mismo CSS.
- `php tests/run.php` → **70/70** (12 pruebas nuevas del minificador: `calc()`,
  combinador `+`, media queries, data URI, cadenas con `;` y `,`, `url()` entrecomillado,
  comentario como separador y llaves balanceadas en los bundles reales).
- `php -l` sobre 177 archivos, sin errores.
- Sitio en marcha en ambos modos: con `APP_DEBUG=true`, 5 hojas por página; con
  `false`, 1 sola. Las 15 rutas comprobadas responden igual que antes (públicas 200,
  `/admin` y `/portal` 302, `/checador` 403, inexistente 404).
- HTML sin recursos de terceros: los únicos enlaces externos son `wa.me` y Google Maps,
  que son enlaces de navegación, no recursos que se carguen.

---

## Auditoría de seguimiento — 12/08/2026

Cobertura: todo lo añadido entre el 27/07 y el 08/08 que nunca había pasado por una
auditoría — módulo de reparto y encuesta de entrega, pedidos/facturas SAE, simulador de
logo, cotización con logo de impresión, solicitud de empleo general y postulaciones.
Cuatro frentes en paralelo: seguridad, calidad de código, visual/accesibilidad y
robustez funcional/operativa. Estado de partida: `tests/run.php` 135/135.

### Seguridad — sin hallazgos explotables

Revisión completa (CSRF, `Auth::authorize()`, IDOR/`verificarAcceso()`, subida de
archivos fuera de webroot, escape de salida, SQL con *prepared statements*). El código
nuevo sigue de forma consistente los patrones ya validados en la auditoría de julio; no
se encontró ninguna desviación explotable. Confirmado en particular: el simulador de
logo es 100 % cliente-side (no hay endpoint de servidor que reciba el archivo), la
solicitud de empleo general no pide ni guarda datos sensibles, y las descargas de
factura/logo/CV verifican propiedad antes de servir el archivo.

### F1 · Condición de carrera en la exportación a SAE — ALTA

> **✔ Aplicado.**

`Pedido::crearEnvio()` marcaba las partidas como exportadas con un `UPDATE` sin
`AND envio_id IS NULL` ni bloqueo. Dos exportaciones casi simultáneas del mismo pedido
(dos personas, o un doble clic) veían las mismas partidas como pendientes, cada una
consumía un consecutivo SAE válido y distinto, y la segunda sobrescribía el `envio_id`
de la primera sin error — dos folios SAE para las mismas partidas, duplicando la
partida si ambos Excel se importan en Aspel SAE.

**Corrección**: el `UPDATE` ahora exige `envio_id IS NULL` y compara `rowCount()`
contra la cantidad de partidas esperada; si no coincide, lanza `RuntimeException` y la
transacción hace *rollback* (`app/Models/Pedido.php`). `procesarExportacion()` y
`procesarLote()` capturan esa excepción: la exportación individual avisa y regresa al
pedido; el lote salta solo el pedido afectado y sigue con el resto, y las filas de un
pedido no entran al Excel final si su `crearEnvio()` no se confirmó (antes se
generaban antes de saber si la escritura tendría éxito).

### F2 · El recordatorio de SAE se perdía si el correo fallaba — ALTA

> **✔ Aplicado.**

`database/recordatorio_sae_pendiente.php` limpiaba `recordatorio_en` de forma
incondicional, sin comprobar si el correo realmente se envió. Sin `MAIL_LEADS`
configurado, o con el SMTP caído, el aviso desaparecía en silencio y no volvía a
dispararse hasta reprogramarlo a mano.

**Corrección**: el recordatorio solo se limpia si `Mailer::enviar()` devolvió `true`;
si falla o no hay destinatario configurado, se deja intacto para que el cron lo
reintente al día siguiente, con un aviso explícito en el log.

### F3 · El cron de recordatorio de SAE no estaba documentado — MEDIA

> **✔ Aplicado.**

`recordatorio_sae_pendiente.php` no aparecía en `docs/DESPLIEGUE.md` (a diferencia de
`backup.php` y `recordatorios_recurrentes.php`), con riesgo real de que nunca se
programara en el despliegue a Rackspace. Se agregó §4.2.2 con el cron sugerido (8:00
am) y una nota para verificar `MAIL_LEADS`.

### F4 · Sin forma de recuperar el Excel de una exportación a SAE — MEDIA

> **✔ Aplicado.**

`crearEnvio()` marca las partidas como exportadas y consume el consecutivo SAE
**antes** de que el navegador reciba el archivo; si la descarga se interrumpe, no había
manera de recuperarlo — el Excel real nunca le llegó a nadie pero el folio ya se había
usado.

**Corrección**: nuevo método `Pedido::itemsDeEnvio()` reconstruye las partidas de un
envío ya creado, y `Admin\PedidoController::redescargarEnvio()`
(`GET /admin/envios/{envioId}/exportacion`) regenera el mismo Excel a partir de los
datos ya persistidos. Enlace «Volver a descargar Excel» junto a la clave SAE en
`admin/pedido_detalle.php`.

### F5 · `RepartoController` daba un 403 crudo si la sesión expiraba — MEDIA

> **✔ Aplicado.**

A diferencia del resto de áreas autenticadas (que redirigen a `/portal/login`),
`RepartoController` llamaba `Auth::authorize()` directamente en cada acción; un
repartidor cuya sesión expirara a media ruta (escenario plausible: es la pantalla
pensada para el celular) caía en un 403 en texto plano sin salida.

**Corrección**: constructor con guardia `Auth::check()` que redirige a
`/portal/login` con mensaje, igual que `Admin\BaseController` y
`PortalBaseController`. Verificado contra el servidor real: `/reparto` sin sesión
ahora responde 302 a `/portal/login` (antes 403 sin redirección).

### F6 · `CarreraController`: flujo duplicado y cupo de anti-spam compartido — MEDIA

> **✔ Aplicado.**

`enviarSolicitud()` (solicitud general) y `postular()` (postulación a vacante)
repetían casi entero el mismo flujo (CSRF, honeypot, límite de envíos, validación,
subida de CV, correos), y ambas usaban la misma clave de `RateLimiter`
(`'postular:' . client_ip()`): una persona que llenara la solicitud general agotaba
sin querer el cupo para postularse a una vacante concreta, y viceversa.

**Corrección**: se extrajeron `guardPostulacion()`, `erroresComunes()`, `subirCv()` y
`avisarPostulacion()` como métodos privados compartidos; cada acción pública quedó con
solo lo que le es propio. Los cupos de anti-spam ahora son independientes
(`postular-general:` y `postular-vacante:` por IP).

### F7 · N+1 en la exportación por lote a SAE — MEDIA

> **✔ Aplicado.**

`exportarLote()` y `procesarLote()` llamaban `itemsPendientesSae()` una vez por cada
pedido pendiente dentro de un bucle — N consultas extra para un lote de N pedidos.

**Corrección**: `Pedido::itemsPendientesSaePorPedidos()`, mismo patrón que
`Producto::porIds()`/`imagenesPorProductos()` — una sola consulta con
`pedido_id IN (...)`, agrupada en PHP.

### F8 · Documentos en `storage/` sin respaldo — BAJA-MEDIA

> **✔ Aplicado.**

El respaldo de base de datos (`backup.php`) cubre todas las tablas dinámicamente, pero
las facturas (PDF+XML), los logos de cotización y los CVs viven como archivos en
`storage/` y no tenían ningún mecanismo de respaldo — con Fase 7.6, `storage/` pasó a
guardar documentos fiscales.

**Corrección**: `database/backup_archivos.php` empaqueta `storage/facturas`,
`storage/cotizaciones_logos` y `storage/cvs` en un `.zip` con `ZipArchive`, misma
retención (14) y mismo estilo que `backup.php`. Documentado en
`docs/DESPLIEGUE.md` §4.1, con cron sugerido a las 3:15 am.

### F9 · Boilerplate de descarga de archivos duplicado 4 veces — BAJA

> **✔ Aplicado.**

El bloque de cabeceras + `readfile()` + `exit` para servir un archivo desde disco
estaba copiado en cuatro controladores (factura del portal, factura del panel, logo de
cotización, CV de postulación).

**Corrección**: `Core\Controller::streamFile()`, disponible en todos los
controladores (portal y admin comparten la misma clase base). Los cuatro puntos ahora
llaman a un único método.

### F10 · Hueco de CSS en el input de imágenes de `producto_form.php` — BAJA

> **✔ Aplicado.**

El botón de marca para `input[type="file"]` (`::file-selector-button`) solo existía en
`site.css`, que el bundle `admin` no carga (`App\Core\Assets::BUNDLES`) — el selector
de imágenes del formulario de producto (y de otros cuatro formularios de admin) mostraba
el botón nativo del sistema en vez del botón de marca que sí tienen los demás inputs de
archivo del sitio.

**Corrección**: la regla `.field input[type="file"]::file-selector-button` se movió de
`site.css` a `components.css`, que sí está en los cinco bundles — corrige el hueco en
`producto_form.php` y, de paso, en `logo_cliente_form.php`, `logo_proveedor_form.php`,
`modal_form.php` y `producto_import.php`. Además, `producto_form.php` (el formulario
más largo del panel sin agrupar) se organizó en `.form-section` — mismo patrón ya usado
en `portal/registro.php` — con los grupos «Identificación», «Presentación y SAE»,
«Contenido» e «Imágenes».

### Verificación

- `php tests/run.php` → **135/135**, sin cambios respecto del estado de partida (las
  correcciones son de robustez y estructura, no de lógica pura nueva).
- `php -l` sobre los 13 archivos tocados, sin errores.
- `php build/assets.php` reconstruido tras el cambio de CSS; confirmado que
  `admin.bundle.min.css` incluye la regla de `.field input[type="file"]`.
- Servidor real: `/reparto` sin sesión → 302 a `/portal/login` (antes 403 sin
  redirección); `/admin/pedidos`, `/admin/pedidos/1` → 302 (sin cambio);
  `/admin/envios/1/exportacion` → 302 (ruta nueva registrada correctamente);
  `/bolsa-de-trabajo`, `/bolsa-de-trabajo/solicitud`, `/productos`,
  `/personalizacion`, `/nosotros`, `/` → 200.
- `database/backup_archivos.php` probado con y sin documentos presentes (el caso sin
  documentos no dejaba un `.zip` vacío corrupto por una particularidad de `ZipArchive`
  con cero archivos añadidos — corregido para reportarlo en vez de fallar).

## Auditoría final pre-producción — 01/09/2026

Última pasada antes de exponer el sitio a Internet (Fase 0 pendiente aparte, ver
`ROADMAP.md`). No había código nuevo desde la auditoría del 12/08 — los únicos archivos
tocados desde entonces son exactamente las correcciones F1–F10 de esa ronda. Cobertura
holística de **todo** el sitio (204 archivos PHP), no solo lo reciente: tres frentes en
paralelo — seguridad de aplicación, checklist operativo de despliegue (Rackspace Cloud
Sites, sin consola) y calidad/robustez de código. Estado de partida: `tests/run.php`
135/135.

### Seguridad — sin hallazgos explotables

Revisión completa de IDOR, CSRF, SQL injection, XSS, autorización por rol/alcance de
vendedor, subida de archivos, inyección de fórmulas en exportaciones a Excel y XXE, en
absolutamente todos los módulos (incluidos los menos revisados hasta ahora: kiosco de
visitas, encuestas, series SAE, zonas, reparto, postulaciones). Los patrones validados
en julio y agosto se confirmaron aplicados de forma consistente en todo el árbol. Dos
detalles de endurecimiento, ambos corregidos:

- **`is_https()` no validaba el proxy de origen** — a diferencia de `client_ip()`
  (que solo confía en `X-Forwarded-For` si `REMOTE_ADDR` cae en `TRUSTED_PROXY_CIDR`),
  `is_https()` aceptaba `X-Forwarded-Proto: https` de cualquier origen. Si el servidor
  real fuera alcanzable sin pasar por el balanceador de Rackspace, una petición HTTP
  directa con esa cabecera falsificada evitaba la redirección a HTTPS y el flag
  `Secure` de las cookies de sesión. **Corregido**: `is_https()` ahora reutiliza
  `trusted_proxy_cidrs()`/`ip_in_cidr()`, mismo criterio que `client_ip()`
  (`app/Helpers/functions.php`).
- **`tests/run.php` sin guardia CLI ni `.htaccess` propio** — a diferencia de
  `database/_bootstrap.php` y `build/assets.php`, que rechazan ejecución fuera de CLI,
  `tests/run.php` (que escribe y borra archivos bajo `storage/logs` y `storage/cache`
  al probar `Log`/`Cache`) solo dependía de la regla de la raíz. **Corregido**: mismo
  guard `PHP_SAPI !== 'cli'` que los otros scripts, más `tests/.htaccess` con
  `Require all denied`. Verificado sirviendo el proyecto con `php -S` (sin `.htaccess`
  activo): `GET /tests/run.php` → 404.

### Checklist operativo de despliegue

`docs/DESPLIEGUE.md`, `docs/SEGURIDAD.md`, `.htaccess` (raíz y por carpeta) y el
mecanismo raíz→`/public` están completos y accionables para un hosting sin consola.
Los 5 scripts de cron periódicos están documentados con su horario, incluido
`recordatorio_sae_pendiente.php` (el hallazgo F3 de agosto). Tres correcciones:

- **`.env.example` no documentaba `IVA_TASA` ni `WHATSAPP_MENSAJE`**, ambas ya leídas
  por `config/app.php` con default de código. Agregadas con su valor por defecto.
- **`APP_DEBUG` fallaba "abierto"**: si el `.env` de producción no se sube (o
  `App\Core\Env::load()` no lo encuentra), `config('app.debug')` caía en `true` por
  defecto, exponiendo trazas de error a cualquier visitante. Cambiado el default de
  `config/app.php` a `false` — ausencia de `.env` ahora falla "cerrado".
- Los 4 bloqueantes de Fase 0 (SSL, `APP_KEY`, `AllowOverride`, credenciales de
  prueba) siguen pendientes como se esperaba, son tareas de servidor real; se
  confirmó que el código ya los soporta correctamente en los tres casos verificables
  desde código.

### Código, robustez y consistencia — 6 hallazgos, todos corregidos

- **Service Worker con precache desalineado de lo que sirve producción (MEDIA-ALTA)**
  — `public/sw.js` precacheaba en el evento `install` archivos de nombre fijo
  (`site.css`, `site.js`, …), pero producción sirve bundles minificados
  (`site.bundle.min.css`) y todo lo que pasa por `asset()` lleva `?v=<mtime>` — la URL
  real nunca coincidía con la cacheada, y si algún día los archivos sueltos dejan de
  existir en el servidor tras el build, `cache.addAll()` fallaría completo y el SW
  jamás se activaría. **Corregido**: el precache install-time solo incluye ahora
  recursos de URL verdaderamente fija (fuentes `.woff2` referenciadas por `url()`
  relativo, iconos referenciados dentro del manifest JSON); CSS/JS/manifest/logo se
  cachean solos, con su URL real, en la primera visita (el `stale-while-revalidate`
  ya existente). `VERSION` subida a `rym-v52`.
- **Reportes del panel sin tope de filas (MEDIA)** — `Admin\ReporteController` /
  `Pedido::reportePedidos|reporteProductos|reporteClientes` / `Cotizacion::reporteLeads`
  no tenían el mismo `MAX_EXPORT` que ya se aplicó en julio a
  `Visita`/`EncuestaPedido`/`EnvioEncuesta`. **Corregido**: `Pedido::MAX_EXPORT` y
  `Cotizacion::MAX_EXPORT` (10 000, mismo valor) con `LIMIT` en las cuatro consultas.
- **Precio de cotización sin piso en 0 (MEDIA)** — `Cotizacion::actualizarLineas()`
  no acotaba el precio como sí hace `Admin\PedidoController` al capturar precios para
  SAE (`max(0, …)`); un precio negativo capturado por error se arrastraba al total y,
  si se aprobaba, al pedido convertido. **Corregido**: mismo `max(0, …)`.
- **Duplicación real entre `PedidoController` (portal) y `Admin\PedidoNuevoController`
  (vendedor crea a nombre de cliente) (MEDIA-BAJA)** — catálogo filtrado/paginado,
  ajuste de cantidad a presentación/mínimo, `carritoDetallado()`/
  `imagenPrincipalPorIds()` y el armado de partidas para `Pedido::crear()` estaban
  copiados carácter por carácter en los dos controladores. **Corregido**: extraído
  `App\Core\CarritoPedido` (parametrizado por la clave de sesión del carrito — el
  portal usa `carrito`, el panel `admin_pedido_carrito`), con los mismos mensajes de
  flash preservados exactamente en cada controlador. Verificado sin regresión: los
  135 tests siguen en verde, prueba directa de `CarritoPedido` contra productos reales
  (ajuste de lote, límite de partidas, quitar/vaciar) y `/portal/pedidos/nuevo` +
  `/admin/pedidos/nuevo` siguen respondiendo 302 sin sesión (no 500).
- **`database/backup_archivos.php` sin blindaje (BAJA)** — a diferencia de
  `backup.php`, no envolvía la lógica en `try/catch`; un error de `DirectoryIterator`
  a mitad de la iteración terminaba en fatal sin el mensaje limpio a `STDERR` ni
  `exit(1)`. **Corregido**: mismo patrón que `backup.php`.
- **`cotizaciones.usuario_id`/`pedido_id` sin FK (BAJA)** — única relación del
  esquema sin `CONSTRAINT ... FOREIGN KEY` (arrastrado desde la migración 021, que
  solo agregó el índice de `usuario_id`). **Corregido**: migración `050`, verificado
  antes sin huérfanos (`0` en ambas comprobaciones) y aplicada contra la base real.

### Verificación

- `php tests/run.php` → **135/135** antes y después de todos los cambios.
- `php -l` sobre los 9 archivos tocados/creados, sin errores.
- Migración `050` aplicada contra la base real (`php database/migrate.php`),
  confirmadas ambas `CONSTRAINT` con `SHOW CREATE TABLE cotizaciones`.
- Guard CLI de `tests/run.php` verificado sirviendo el proyecto con `php -S` (sin
  `.htaccess`): `GET /tests/run.php` → 404.
- Servidor real (XAMPP): `/`, `/sw.js` → 200; `/portal/pedidos/nuevo`,
  `/admin/pedidos/nuevo` sin sesión → 302 a login (no 500, confirma que el refactor
  del carrito no rompió el enrutado de ninguno de los dos controladores).
- `CarritoPedido` probado directo contra productos reales de la base (sin HTTP):
  ajuste de cantidad a presentación/mínimo, límite `MAX_PARTIDAS` (acepta hasta el
  tope y rechaza el excedente), `quitar()`/`vaciar()` limpian la sesión correctamente,
  `catalogo()` estático devuelve paginación consistente.

## Verificación en producción real (01/09/2026, primer despliegue)

Tras subir el código a Rackspace, se detectaron y corrigieron 2 problemas propios del
paso a un servidor real (no visibles en desarrollo local ni en las auditorías previas):

- **`.footer__grid` sin versión responsive (MEDIA)** — único grid de 3+ columnas del
  sitio (a diferencia de `.hero__inner`, `.split`, `.stats__grid`, `.sectors`,
  `.cta__grid`, `.contact-grid`, `.steps`, todos con su colapso a 1 columna en el
  `@media (max-width: 960px)`) que se quedó fijo en `1.4fr 1fr 1fr`. En móvil, el
  contenido de cada columna (dirección completa, teléfono, links) no se comprime por
  debajo de su ancho mínimo intrínseco, así que el grid entero desborda el viewport:
  overflow horizontal en **todo el sitio** (el footer está en todas las páginas) y la
  columna "Mapa de sitio" queda parcialmente fuera de la pantalla visible sin que se
  note que hay que hacer scroll lateral. Reportado por el usuario probando en un
  celular real. Corregido agregando `.footer__grid` al mismo selector de colapso que
  ya usan los demás grids (`site.css`), bundle reconstruido con `build/assets.php`.
- **`str_contains()` (PHP 8.0+) usado en el camino de arranque antes de la
  redirección a HTTPS (MEDIA)** — el vhost de `http://` en el hosting real resultó
  tener una versión de PHP anterior a 8.0 (probablemente configurado por separado del
  vhost HTTPS al instalar el SSL), donde `str_contains()` no existe como función
  nativa: `Fatal error: Call to undefined function App\Core\str_contains()` en
  `App\Core\Env::load()` — ocurre tan temprano en `public/index.php` que ni siquiera
  llega al bloque que redirigiría `http://` a `https://`, dando un 500 en vez de un
  301. La causa raíz es de configuración del hosting (las dos versiones de PHP deben
  igualarse a 8.2+ en el panel — el resto del proyecto usa sintaxis de PHP 8 en
  varios puntos, así que un parche completo de compatibilidad no es el camino
  correcto), pero se blindaron los dos únicos usos de `str_contains()` que están en
  el camino crítico *antes* de esa redirección (`Env::load()` y `ip_in_cidr()`, esta
  última invocada por `is_https()`) reemplazándolos por `strpos() === false` /
  `strpos() !== false` — equivalentes, compatibles con cualquier versión de PHP, sin
  tocar el resto del código (que sigue asumiendo PHP 8.2+ como está documentado en
  `docs/DESPLIEGUE.md` §1). Así, aunque el vhost de HTTP quede mal configurado, como
  mínimo la redirección a HTTPS no se rompe.

Verificado: `tests/run.php` 135/135, `php -l` limpio, `curl` directo contra el HTML y
CSS reales de producción para confirmar cada hallazgo antes de tocar código (no se
adivinó ninguno de los dos).

## Segunda vuelta tras subir los fixes (mismo día) — 3 hallazgos más

- **`storage/logs/php-error.log` no capturaba errores de arranque muy tempranos** —
  `ini_set('error_log', ...)` en `public/index.php` se configuraba DESPUÉS de
  `Env::load()`/`Config::load()`; un fatal en esos dos puntos (como el de
  `str_contains()` de más abajo) usaba el log nativo del servidor/hosting, invisible
  para el visor de errores del panel. Corregido moviendo `ini_set('log_errors', '1')`
  + `ini_set('error_log', ...)` a las primeras líneas del archivo, justo después de
  definir `ROOT_PATH` — ya no depende de `app.debug` ni de haber cargado nada más.
- **El vhost de `http://` en producción resultó tener una versión de PHP muy
  anterior a la documentada (8.2+)** — confirmado en dos pasos con logs reales del
  servidor: primero un `Call to undefined function str_contains()` (indica PHP
  &lt;8.0), corregido reemplazando los 2 usos de esa función en el camino crítico antes
  de la redirección a HTTPS (`Env::load()`, `ip_in_cidr()` vía `is_https()`) por
  `strpos()`. Tras ese fix, un SEGUNDO error reveló que la versión real era aún más
  vieja: `syntax error, unexpected 'array', expecting function or const` en
  `Config.php:11` (`protected static array $items = [];`, una *typed property*,
  sintaxis de PHP **7.4+**) — el parser ni siquiera llega a compilar esa línea, así
  que el vhost estaba en PHP &lt;7.4. Conclusión: no es un problema de código
  parcheable (el proyecto usa `match`, constructor property promotion, `str_contains`
  y otras características de PHP 8 en decenas de archivos) sino de configuración del
  hosting — el usuario confirmó que Rackspace Cloud Sites tenía la versión de PHP
  del vhost HTTP configurada por separado de la del vhost HTTPS, y la actualizó a
  8.4 en ambos.
- **`Assets::minVigente()` — mecanismo frágil ante el orden real de subida por
  FTP (MEDIA)**: comparaba la fecha de modificación del bundle contra la de sus 5
  hojas fuente, sirviendo las sueltas si CUALQUIERA quedaba más reciente que el
  bundle. En el primer despliegue real, subir `site.css` después de
  `site.bundle.min.css` (aunque fuera solo por el orden del cliente FTP) bastó para
  que producción sirviera 5 peticiones sueltas en vez de 1 bundle, indefinidamente,
  sin que nada estuviera realmente roto — solo el chequeo de "vigencia". Como esta
  comparación SOLO se evalúa cuando `app.debug=false` (en desarrollo siempre se
  sirven las sueltas sin pasar por aquí), el mecanismo únicamente protegía contra un
  escenario raro (bundle desactualizado en producción) a costa de ser frágil ante
  algo común (orden de subida por FTP). **Simplificado**: en producción,
  `Assets::css()` ahora sirve el bundle si simplemente existe, sin comparar fechas
  contra sus fuentes — el proceso correcto sigue siendo generar el bundle con
  `build/assets.php` antes de subir, pero ya no depende de qué mtime relativo quede
  tras el FTP. `minVigente()` se eliminó (quedaba sin uso).

Verificado: `tests/run.php` 135/135, `php -l` limpio, `curl` directo contra
`AllowOverride` (`.env`, `/app/`, `/database/`, `/tests/run.php`, `/storage/logs/` →
403, confirmando ese bloqueante de Fase 0 resuelto), contra el bundle real ya sirviendo
(confirmado con el fix del footer presente byte a byte), y en local confirmando que
`app.debug=true` sigue sirviendo las hojas sueltas sin cambios de comportamiento.

## Auditoría — 17/09/2026

Cobertura: los 4 commits agregados desde la auditoría final del 01/09/2026 —
correo saliente vía Microsoft Graph/Office 365, separación de la bitácora de
visitas por oficinas, y el fix de logo/layout del header. Tres frentes en
paralelo: seguridad, calidad de código y preparación para producción. Estado de
partida: `tests/run.php` 135/135.

### Seguridad — sin hallazgos explotables

`App\Core\Crypto` cifra el `client_secret` con AES-256-GCM (IV aleatorio, tag
autenticado), clave derivada de `APP_KEY` — mismo patrón ya aceptado para
`captcha_secret()`. Las 3 rutas de `ConfiguracionCorreoController` exigen el
permiso `configuracion.correo` (solo rol `admin`); el secreto nunca llega al
HTML; el botón de prueba solo envía al correo del propio admin autenticado (no
es vector de SSRF/spam a terceros). El scope por oficina
(`BaseController::oficinaScope()`) se aplica siempre server-side sin importar
el filtro de query string, y `ChecadorController` valida que el anfitrión
pertenezca a la oficina del dispositivo autenticado — sin IDOR entre oficinas.
Barrido general del resto del sitio sin hallazgos nuevos.

### F1 · `Mailer::enviar()` podía romper TODO el envío de correo del sitio — ALTA

> **✔ Aplicado.**

`GraphMailer::configActiva()` se llamaba en `app/Core/Mailer.php` **fuera de
cualquier try/catch**, antes de decidir si usar Graph o SMTP. Si esa consulta
fallaba (tabla `configuracion_correo` sin migrar todavía en algún entorno, o un
hipo momentáneo de la base de datos), la excepción se propagaba sin capturar y
tumbaba el envío de contraseñas, cotizaciones, pedidos y notificaciones de
visitas — contradiciendo el propio contrato documentado de la clase ("nunca
lanza excepción hacia el flujo del usuario"). Especialmente delicado en
`ChecadorController`, donde la visita ya queda guardada en BD antes de llamar a
`Mailer::enviar()`: el visitante vería un error 500 aunque su registro sí se
guardó.

**Corrección**: la llamada a `GraphMailer::configActiva()` ahora está dentro de
su propio try/catch; si falla, se registra en `mail.log` y se trata como "sin
Graph activo", cayendo al camino de SMTP existente en vez de propagar la
excepción.

### F2 · Endpoint de "correo de prueba" sin rate-limit — BAJA

> **✔ Aplicado.**

`ConfiguracionCorreoController::probar()` no limitaba intentos, a diferencia
del resto de endpoints sensibles del proyecto (`checador`, `cotizar`,
`pwreset`, etc.). No era una desviación explotable por sí sola (ya requiere
sesión de admin con el permiso), pero permitía golpear repetidamente el
endpoint de token de Microsoft. Se agregó `RateLimiter::attempt()` (5 intentos
/ 10 min, por usuario).

### F3 · Token de Graph cacheado en texto plano — BAJA (endurecimiento)

> **✔ Aplicado.**

`configuracion_correo.token_cache` guardaba el access_token de Graph (vida
~60-90 min) sin cifrar, a diferencia del `client_secret` de la misma tabla. No
era explotable por sí solo, pero por defensa en profundidad ahora se cifra y
descifra con `App\Core\Crypto`, igual que el secreto.

### F4 · Documentación desactualizada — MEDIA

> **✔ Aplicado.**

`docs/DESPLIEGUE.md`, `docs/ROADMAP.md` y este documento no se habían tocado
en los 3 commits de funcionalidad nueva. Se agregó `docs/DESPLIEGUE.md §4.4`
(correo O365, incluida la guía paso a paso para registrar la app en Azure
AD/Entra ID con permiso `Mail.Send` app-only — paso obligatorio fuera del
sitio que antes no estaba documentado en ningún lado) y `§4.5` (oficinas, con
la advertencia operativa de que un anfitrión sin oficina asignada desaparece
del kiosco en cuanto su dispositivo sí tiene una oficina asignada); dos ítems
nuevos en el checklist de verificación post-deploy (§6); y `docs/ROADMAP.md`
registra ambas features en el backlog completado.

Verificado: `tests/run.php` 135/135 antes y después de las correcciones,
`php -l` limpio en los archivos tocados (`Mailer.php`, `GraphMailer.php`,
`ConfiguracionCorreo.php`, `ConfiguracionCorreoController.php`).

### F5 · Sin cobertura de pruebas para el código de esta ronda — BAJA

> **✔ Aplicado.**

Ni `Crypto` ni el resto del código nuevo tenían pruebas puras en
`tests/run.php` (el archivo seguía en 135/135, idéntico a la auditoría del
01/09). De los módulos nuevos, `Crypto` es el único con lógica pura sin BD/red
que valga la pena cubrir — `Oficina` es CRUD puro sobre BD (fuera del alcance
de este archivo, que a propósito evita depender de base de datos) y
`GraphMailer` no expone funciones puras públicas (todo pasa por BD o HTTP a
Microsoft). Se agregó el grupo "Cifrado de secretos (Crypto, correo
O365/Graph)": round-trip cifra/descifra, el texto cifrado nunca contiene el
secreto en claro, dos cifrados del mismo texto dan salidas distintas (IV
aleatorio), un payload manipulado un solo byte no se descifra (confirma que el
tag de autenticación de GCM sí protege contra manipulación, no solo confidencia),
y payloads inválidos (no base64, demasiado corto, vacío) no truenan.

Verificado: `tests/run.php` 135 → **142/142**, `php -l` limpio en `tests/run.php`.
