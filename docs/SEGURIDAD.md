# Medidas de seguridad — Importadora RYM

Resumen de las protecciones implementadas y su ubicación.

## Entrada / inyección
- **SQL**: exclusivamente *prepared statements* (PDO). Sin concatenación de entradas.
- **XSS**: escape con `e()` en todas las vistas. `Content-Security-Policy` con `nonce`
  para los scripts inline propios (`public/index.php`).
- **Límites de longitud**: `str_clean()` en todos los controladores que reciben texto.

## Autenticación y sesión (`app/Core/Auth.php`)
- `password_hash` (bcrypt **coste 12**, `Usuario::HASH_OPTS`) + `password_verify`. Los
  hashes con un coste anterior se migran solos al iniciar sesión (`password_needs_rehash`).
- **Tiempo de intento constante** (`Auth::PISO_MICROS`, 400 ms) + hash dummy: el login
  tarda lo mismo exista o no el correo, así que no se pueden enumerar cuentas midiendo
  la respuesta.
- `session_regenerate_id()` al iniciar sesión (anti-fijación).
- Cookies de sesión `HttpOnly`, `SameSite=Lax`, y `Secure` cuando hay HTTPS.
- **Timeout por inactividad** (`Auth::enforce()`, config `SESSION_TIMEOUT`, 2 h por
  defecto): cierra la sesión y avisa tras el periodo sin actividad.
- **Invalidación por cambio de contraseña**: al iniciar sesión se guarda una huella
  del hash de la contraseña; si esta cambia (reset o cambio en el panel) **todas** las
  sesiones activas dejan de ser válidas en el siguiente request. El hash de contraseña
  ya no se expone en el arreglo de usuario que llega a las vistas.
- **Rate limiting** (`app/Core/RateLimiter.php`): login (8/5min), registro (5/hora),
  cotización (5/10min) por IP. `RateLimiter::gc()` limpia contadores vencidos (cron).
- **IP real tras un proxy**: `client_ip()` (`Helpers/functions.php`) solo confía en
  `X-Forwarded-For` cuando `REMOTE_ADDR` cae dentro de `TRUSTED_PROXY_CIDR` (`.env`,
  vacío por defecto = nunca confiar). En producción es el balanceador de Rackspace
  Cloud Sites/Liquid Web (ver `docs/DESPLIEGUE.md`); sin esto, todo el rate limiting
  quedaría global tras el proxy: un solo visitante bloquearía a todos.
- **Honeypot** (`honeypot_field()`) en login, registro, cotización y contacto.
- **Captcha de un solo uso**: el reto firmado se consume al validarse y caduca a los
  30 min (`CAPTCHA_VIGENCIA`), así no se reenvía la misma tripleta resuelta.

## Autorización (RBAC granular)
- Roles + permisos + *overrides* por usuario.
- `Auth::can()`, `Auth::authorize()` (corta con 403). Las acciones del portal
  verifican su permiso (`pedidos.crear`, `pedidos.ver_propios`).
- Guard del portal exige `portal.acceder`.
- **IDOR**: `PedidoController::show()` valida `usuario_id`.
- **Sin escalada por delegación** (`Admin/UsuarioController`): solo se asignan roles
  cuyos permisos ya tiene quien edita (`roles.gestionar` es la llave de administrador);
  los *overrides* solo conceden permisos que el actor posee; nadie modifica su propio
  rol ni sus permisos; y el módulo no toca cuentas de cliente.

## Caducidad de enlaces y tokens
- Restablecimiento de contraseña: 60 min, un solo uso, hash en BD.
- Verificación de correo: **48 h** (`Usuario::VERIFICACION_HORAS`).
- Activación de un dispositivo de kiosco: **24 h** y un solo uso
  (`ChecadorDispositivo::ACTIVACION_HORAS`).

## Enrutado
- El path **no** se decodifica antes de resolver la ruta: un `%2F` no puede convertirse
  en separador de segmentos y cambiar qué ruta coincide. `Router::dispatch()` decodifica
  solo los parámetros ya delimitados.

## CSRF
- Token por sesión (`csrf_field()` / `csrf_verify()`) en **todos** los POST,
  incluido el logout. **Excepción a propósito**: el endpoint máquina-a-máquina
  de abajo no usa sesión ni CSRF — su autenticación es un token fijo, no una
  sesión de navegador que un CSRF pudiera secuestrar.

## Autenticación máquina-a-máquina (primer caso: sync de disponibilidad SAE)
- `POST /integraciones/sae/disponibilidad` (`App\Controllers\Integraciones\
  SaeDisponibilidadController`) recibe la sincronización de existencias desde
  un script externo en la oficina del cliente (ver `integraciones/sae/`).
- **Auth por token fijo**: header `Authorization: Bearer <token>`, comparado
  con `hash_equals()` contra `SAE_SYNC_TOKEN` (`.env`, vacío por defecto =
  endpoint cerrado, no "abierto sin auth"). Es el primer endpoint del proyecto
  con este patrón — cualquier integración externa futura debería seguir el
  mismo esquema (token de 32 bytes vía `.env`, `hash_equals()`, nunca
  comparación directa con `===`).
- **Rate limit antes de validar el token** (`RateLimiter`, 20/10min por IP):
  así un token equivocado repetido también queda acotado, no solo el abuso
  con un token válido que se llegara a filtrar.

## Cabeceras (`public/index.php` + `public/.htaccess`)
- `Content-Security-Policy` (con nonce; permite GA, Google Fonts y Google Maps).
  `style-src-elem` **no** admite inline: las hojas y el único `<style>` del proyecto van
  por origen propio o con nonce; `unsafe-inline` queda acotado a `style-src-attr`, que
  solo recibe enteros calculados (`--pct` / `--px` de las barras del tablero).
- `Strict-Transport-Security` (con `FORCE_HTTPS`).
- `X-Content-Type-Options: nosniff`, `X-Frame-Options: SAMEORIGIN`,
  `Referrer-Policy`, `Permissions-Policy`, `Cross-Origin-Opener-Policy`. Todas se emiten
  **desde PHP** además del `.htaccess`, para no depender de `mod_headers`.
- **URLs externas** capturadas en el panel (enlace del modal): `url_http_valida()` exige
  esquema `http`/`https`, porque `FILTER_VALIDATE_URL` acepta `javascript:` y `data:`.

## Protección de archivos
- `.htaccess` de denegación en `app`, `config`, `routes`, `database`, `storage`, `docs`.
- `.env` denegado explícitamente. **Requiere `AllowOverride All` en producción.**

## Límites operativos (disponibilidad)
- Logs: rotan a los 5 MB conservando una generación (`App\Core\Log`); el visor del panel
  lee solo la cola del archivo, nunca el log completo.
- Importación de catálogo: 5 000 filas por archivo, dentro de una transacción.
- Exportaciones a Excel: 10 000 filas, con aviso en pantalla al alcanzar el tope.
- Carrito de pedido y de cotización: 200 partidas distintas.
- Historial del cliente (`porUsuario`): 200 registros.

## Pendientes / recomendaciones
- **SSL en producción** (bloqueante para el portal). Ver `docs/DESPLIEGUE.md`.
- Usuario de BD dedicado (no root) en producción.
- Rotar/expirar contraseñas de administradores; cambiar la del admin sembrado.
- Los tokens de restablecimiento y verificación viajan en la ruta URL: quedan en los
  logs del servidor. Ya caducan, pero moverlos al cuerpo de un POST sería más limpio.

## Auditoría
Revisión completa del 27/07/2026 en [AUDITORIA.md](AUDITORIA.md): hallazgos, correcciones
aplicadas y lo que queda pendiente con su motivo.
