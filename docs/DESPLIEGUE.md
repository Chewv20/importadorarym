# Checklist de despliegue a producción — Importadora RYM

## 0.0 Contexto del hosting (Rackspace Cloud Sites / Liquid Web)

Producción corre en **Rackspace Cloud Sites**, operado por **Liquid Web**. **No es
cPanel** y el acceso es limitado: solo se puede **subir archivos**, **programar crons**
y **ver/administrar la base de datos** desde el panel. **No hay acceso a consola/SSH.**
MySQL corre en un servidor dedicado del hosting (panel de Cloud Sites → bases de datos).

Esto condiciona varios pasos de este checklist:

- **Sin consola**: `database/migrate.php`, `seed.php`, `import_catalogo.php` y
  cualquier script de `database/` que normalmente se invocaría a mano por CLI debe
  ejecutarse programando un **cron de una sola vez** (fecha/hora próxima) desde el
  panel, en vez de abrir una terminal. Se puede desactivar/borrar el cron después de
  que corra. La guardia CLI de `_bootstrap.php` (§4.3) sigue aplicando: por HTTP estos
  scripts responden 404 pase lo que pase.
- **Ruta física real del sitio en el servidor** (confirmada en el panel de crons,
  2026-09-01): `/mnt/stor12-wc2-dfw1/602249/2023381/www.importadorarym.com/web/content`
  — el árbol del proyecto (`database/`, `app/`, `storage/`, etc.) se sube directamente
  dentro de esa carpeta `web/content/`. Es la ruta ya usada en todos los comandos de
  cron de este documento (§4, §4.1, §4.2, §4.2.1, §4.2.2); si el ID de cuenta/paquete
  cambiara, hay que actualizarla ahí.
- **Restaurar un respaldo** (§4.1) no puede hacerse con el cliente `mysql` por consola:
  se importa el archivo `.sql` generado por `backup.php` desde la herramienta de
  importación del panel de bases de datos.
- **Los triggers de MySQL no funcionan en este hosting.** El proyecto hoy no usa
  ninguno (toda la lógica vive en PHP), así que no hay nada que migrar — pero descarta
  esa opción para cualquier necesidad futura a nivel de esquema.
- **Posible proxy delante de la app** (ver más abajo, "Proxy de confianza"): si el
  hosting reenvía las peticiones vía `X-Forwarded-For`, `REMOTE_ADDR` que ve PHP sería
  la IP del proxy, no la del visitante — ya resuelto en código (`TRUSTED_PROXY_CIDR`
  con la IP del servidor); falta verificar contra tráfico real al desplegar.

## 0. Pendientes de la auditoría que se resuelven AL DESPLEGAR

Estas cuatro medidas salieron de la auditoría del 27/07/2026 (ver [AUDITORIA.md](AUDITORIA.md))
y quedaron **conscientemente aplazadas hasta el paso a producción**, porque dependen del
hosting y no del código. No son deuda olvidada: son requisitos de este checklist.

- [ ] **SSL + `FORCE_HTTPS=true`** — §5. **Bloqueante**: sin esto el login del portal
      viaja en claro y la cookie de sesión sale sin la marca `Secure`.
- [ ] **`AllowOverride All` verificado** — §1 y §2. **Bloqueante**: el `DocumentRoot` no
      apunta a `/public`, así que si Apache ignora los `.htaccess`, `.env` queda
      descargable. Se comprueba en §6.
- [ ] **Credenciales de producción** — §3 y §4: usuario de BD dedicado (hoy es `root`),
      `APP_KEY` nueva y cambio de la contraseña del admin sembrado.
      Cambiar `APP_KEY` invalida las sesiones y los captchas en vuelo: hacerlo dentro de
      la ventana de despliegue, no en caliente.
- [ ] **Cron de mantenimiento y de respaldo agendados** — §4.1, §4.2, §4.2.1 y §4.2.2.
- [ ] **OPcache habilitado** — §1.1. No es bloqueante, pero es la mejora de rendimiento
      de mayor impacto y de ella depende que la caché de datos del sitio aporte algo
      (medido: −58 % con OPcache, −4 % sin él).

Un riesgo más, ya identificado, que **solo aplica a partir de cierta escala**:

- Si el catálogo supera las ~10 000 filas, migrar la búsqueda `LIKE '%q%'` a un índice
  `FULLTEXT` (cambia la semántica: deja de encontrar subcadenas a media palabra).

### Proxy de confianza (servidor de Rackspace) — ✔ resuelto en código

`client_ip()` (`app/Helpers/functions.php`) ya no usa `REMOTE_ADDR` a secas: solo
confía en `X-Forwarded-For` cuando `REMOTE_ADDR` coincide con `TRUSTED_PROXY_CIDR`
(`.env`); vacío = nunca confiar (igual que antes, seguro por defecto). Sin esto, si
las peticiones llegan reenviadas por un proxy del hosting, el rate limiting (login,
registro, cotización, etc.) vería siempre la misma IP y se volvería **global**: un
solo atacante/bot bloquearía a todos los usuarios reales.

- **IP del servidor de producción**: `98.129.229.200` — capturar en el `.env` real:
  ```
  TRUSTED_PROXY_CIDR=98.129.229.200
  ```
- **Verificar al desplegar** (no asumir): confirmar contra tráfico real que
  `REMOTE_ADDR` efectivamente llega como esta IP (log de acceso, o un
  `var_dump($_SERVER['REMOTE_ADDR'])` temporal). Si el hosting reenvía por un
  balanceador que **rota** entre varias IPs del mismo bloque, ampliar a CIDR
  (p. ej. `98.129.229.0/24`) en vez de una IP fija — con IP fija, en cuanto el
  balanceador conecte por otra IP del rango, el rate limit vuelve a ser global.
  Admite IP exacta, CIDR o una lista separada por comas (varios proxies de confianza).
- Si `REMOTE_ADDR` **no** llega como `98.129.229.200` (p. ej. porque Cloud Sites no
  reenvía por proxy interno para este plan), dejar `TRUSTED_PROXY_CIDR` vacío: es el
  estado seguro y es exactamente el comportamiento actual.
- Pruebas: grupos `ip_in_cidr` y `resolve_client_ip` en `tests/run.php`.

## 0.1 Entorno local (XAMPP): URLs sin `/public`

En local el `DocumentRoot` es `htdocs`, así que el proyecto se sirve en
`http://localhost/importadorarym/` — **sin `/public`**, igual que en producción:

- El `.htaccess` de la raíz reescribe internamente todo hacia `/public`, de modo que
  esa carpeta no aparece en ninguna URL.
- `public/.htaccess` redirige con **301** cualquier dirección que sí incluya `/public/`
  hacia su equivalente limpia, para que cada página tenga una sola URL válida.
- `BASE_PATH` (en `public/index.php`) descarta el segmento `/public` al calcular el
  prefijo, de forma que enlaces, assets y cookies salgan ya sin él.

> **Nota sobre Apache:** la redirección 301 vive en `public/.htaccess` y no en el de la
> raíz porque Apache **no hereda** las reglas de `mod_rewrite` del directorio padre
> cuando el subdirectorio define las suyas; puesta arriba, nunca llegaría a ejecutarse.

Tras este cambio, en el navegador conviene **borrar las cookies de `localhost`** y
desregistrar el Service Worker anterior (DevTools → Application → Service Workers):
el registro viejo apuntaba al scope `/importadorarym/public/` y queda huérfano.

`APP_URL` en el `.env` local debe ser `http://localhost/importadorarym` (sin `/public`),
porque de ahí salen los enlaces de los correos y las URLs canónicas.

## 1. Requisitos del servidor
- PHP 8.2+ con extensiones `pdo_mysql`, `mbstring`, `openssl`.
- MariaDB 10.0+.
- Apache con `mod_rewrite`, `mod_headers`, `mod_expires`, `mod_deflate`.
- **`AllowOverride All`** en el directorio del sitio (para que los `.htaccess` se apliquen).
- **OPcache habilitado** (ver §1.1). Es la mejora de rendimiento más grande disponible
  para este proyecto y **de la que depende** la caché de datos del sitio.

## 1.1 OPcache (rendimiento)

Sin OPcache, PHP vuelve a leer y compilar los ~175 archivos del proyecto **en cada
petición**. Con él, se compilan una vez y se sirven desde memoria compartida.

Medido en este proyecto sobre los datos que se leen en cada página del sitio:

| | sin caché de datos | con caché de datos |
|---|---|---|
| OPcache apagado | 0,357 ms | 0,372 ms (**−4 %**, algo peor) |
| OPcache encendido | 0,380 ms | 0,159 ms (**−58 %**) |

La caché de `App\Core\Cache` guarda los valores como archivos PHP (`return [...]`), así
que **rinde solo si OPcache está activo**; sin él es neutra (el ahorro de 2 consultas por
página se mantiene igual, lo que se pierde es la ventaja en CPU).

Configuración recomendada en `php.ini`:

```ini
zend_extension=opcache
opcache.enable=1
opcache.memory_consumption=128
opcache.max_accelerated_files=10000
opcache.validate_timestamps=1     ; en producción estable puede ir a 0
opcache.revalidate_freq=60
```

> Con `opcache.validate_timestamps=0` hay que **reiniciar PHP-FPM/Apache en cada
> despliegue**, o los cambios no se verán. Si no vas a acordarte, déjalo en `1`.

Verificación: `php -r "var_dump(opcache_get_status() !== false);"` en el servidor, o un
`phpinfo()` temporal (y borrarlo después).

## 2. Archivos
- Subir todo el proyecto. Como el DocumentRoot **no** apunta a `/public`, el sitio
  se sirve normalmente; la raíz redirige a `/public` y los `.htaccess` protegen el backend.
- **Verificar** que `https://TU-DOMINIO/.env` responde **403/404** (NO debe descargarse).
  Igual para `/app`, `/config`, `/database`, `/storage`, `/routes`, `/docs`.
- Dar permisos de escritura a `storage/logs` y `storage/cache` (throttle y errores).

## 3. Variables de entorno (`.env`)
```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://www.importadorarym.com
FORCE_HTTPS=true            # solo cuando el SSL ya esté instalado
GA_MEASUREMENT_ID=G-XXXXXXXXXX
DB_USERNAME=rym_app         # usuario dedicado, NO root
DB_PASSWORD=<contraseña fuerte>
TRUSTED_PROXY_CIDR=98.129.229.200   # ver "Proxy de confianza" arriba — verificar contra REMOTE_ADDR real
```

## 4. Base de datos
- Crear usuario dedicado con privilegios mínimos (ver `.env.example`), desde el panel
  de bases de datos de Cloud Sites (no hay `CREATE USER` por consola).
- **Sin acceso a consola**: `migrate.php` y `seed.php` no se pueden invocar a mano.
  Programar cada uno como un **cron de una sola vez** (próximos minutos) desde el
  panel de Cloud Sites, dejarlo correr y luego borrarlo/desactivarlo:
  ```
  php /mnt/stor12-wc2-dfw1/602249/2023381/www.importadorarym.com/web/content/database/migrate.php
  php /mnt/stor12-wc2-dfw1/602249/2023381/www.importadorarym.com/web/content/database/seed.php "ContraseñaAdminFuerte" --solo-admin
  ```
  `--solo-admin` omite las 4 categorías/productos de ejemplo (`Vaso de papel 12 oz`,
  etc.) — el catálogo real se carga aparte con el importador CSV (ver
  `docs/ROADMAP.md` Fase 4). Al pasar la contraseña real como argumento (cumpliendo
  la política: 8–100 caracteres, mayúscula+minúscula+número+símbolo) el admin queda
  creado ya con esa contraseña — **no** con la que por defecto documenta el propio
  script, así que no hace falta un cambio de contraseña posterior por este paso.

## 4.1 Respaldos de base de datos
- Script en PHP puro (no depende de `mysqldump`): `php database/backup.php`
  - Genera `storage/backups/rym-YYYYMMDD-HHMM.sql` (estructura + datos) y conserva los últimos 14.
  - `storage/` ya está protegido por `.htaccess` (no accesible por web).
- **Cron diario (3:00 am)** — el hosting tiene cron disponible:
  ```
  0 3 * * * /usr/bin/php /mnt/stor12-wc2-dfw1/602249/2023381/www.importadorarym.com/web/content/database/backup.php >> /mnt/stor12-wc2-dfw1/602249/2023381/www.importadorarym.com/web/content/storage/logs/backup.log 2>&1
  ```
- **Restaurar un respaldo — sin consola**: no hay `mysql` por línea de comandos. Usar
  la herramienta de **importación** del panel de bases de datos de Cloud Sites,
  subiendo el archivo `storage/backups/rym-YYYYMMDD-HHMM.sql` que generó `backup.php`.
- Recomendado: copiar periódicamente los `.sql` fuera del servidor (descarga manual,
  ya que tampoco hay consola para automatizar la subida a otro destino).
- **Respaldo de documentos** (`storage/facturas`, `storage/cotizaciones_logos`,
  `storage/cvs` — facturas fiscales, logos de cotización y CVs, que viven en disco y
  no en la base de datos): `php database/backup_archivos.php` genera
  `storage/backups/rym-archivos-YYYYMMDD-HHMM.zip` y conserva los últimos 14, igual
  que `backup.php`. Sin este cron, esos documentos solo existen en el propio servidor.
  ```
  15 3 * * * /usr/bin/php /mnt/stor12-wc2-dfw1/602249/2023381/www.importadorarym.com/web/content/database/backup_archivos.php >> /mnt/stor12-wc2-dfw1/602249/2023381/www.importadorarym.com/web/content/storage/logs/backup.log 2>&1
  ```

## 4.2 Mantenimiento periódico
- Script en PHP puro: `php database/mantenimiento.php [--dias-auditoria=180]`
  - Poda la bitácora de auditoría más antigua que N días (180 por defecto).
  - Elimina tokens de restablecimiento de contraseña caducados.
  - Recolecta los archivos de rate limiting (throttle) expirados.
  - Idempotente y seguro de correr en cualquier momento.
- **Cron diario (3:30 am)**:
  ```
  30 3 * * * /usr/bin/php /mnt/stor12-wc2-dfw1/602249/2023381/www.importadorarym.com/web/content/database/mantenimiento.php >> /mnt/stor12-wc2-dfw1/602249/2023381/www.importadorarym.com/web/content/storage/logs/mantenimiento.log 2>&1
  ```
- Sin este cron, la tabla `auditoria` y la carpeta `storage/cache/throttle` crecen sin límite.

## 4.2.1 Recordatorios de pedidos recurrentes
- Script en PHP puro: `php database/recordatorios_recurrentes.php`
  - Envía el correo a los clientes cuya plantilla de pedido recurrente ya toca recordar.
  - Reprograma la siguiente fecha aunque el correo falle o el cliente esté desactivado
    (así nunca se acumulan recordatorios atrasados).
  - Idempotente dentro del mismo día: correrlo dos veces no duplica envíos.
- **Cron diario (7:00 am, hora de negocio — no de madrugada como el resto)**:
  ```
  0 7 * * * /usr/bin/php /mnt/stor12-wc2-dfw1/602249/2023381/www.importadorarym.com/web/content/database/recordatorios_recurrentes.php >> /mnt/stor12-wc2-dfw1/602249/2023381/www.importadorarym.com/web/content/storage/logs/recordatorios.log 2>&1
  ```
- Sin este cron, los clientes pueden seguir gestionando sus pedidos recurrentes desde el
  portal, pero **nunca reciben el aviso** — la funcionalidad queda inerte, no rota.

## 4.2.2 Recordatorio de partidas pendientes de exportar a SAE
- Script en PHP puro: `php database/recordatorio_sae_pendiente.php`
  - Avisa por correo (`MAIL_LEADS`) de los pedidos con recordatorio vencido capturado
    desde el detalle del pedido (Fase 7.5).
  - Idempotente dentro del mismo día, pero **solo si el correo se envía**: si `MAIL_LEADS`
    no está configurado o el envío falla, el recordatorio se deja intacto para
    reintentarse al día siguiente en vez de perderse en silencio.
- **Cron diario (8:00 am, hora de negocio)**:
  ```
  0 8 * * * /usr/bin/php /mnt/stor12-wc2-dfw1/602249/2023381/www.importadorarym.com/web/content/database/recordatorio_sae_pendiente.php >> /mnt/stor12-wc2-dfw1/602249/2023381/www.importadorarym.com/web/content/storage/logs/recordatorios.log 2>&1
  ```
- Verificar que `MAIL_LEADS` esté configurado en `.env` de producción — sin él, el
  script nunca envía el aviso (lo registra en el log de cron, pero no hay quien lo lea
  si nadie revisa ese log).

## 4.3 Scripts de línea de comandos (nota de seguridad)
- Todos los scripts de `database/` (`migrate`, `seed`, `backup`, `backup_archivos`,
  `mantenimiento`, `import_catalogo`, `recordatorios_recurrentes`,
  `recordatorio_sae_pendiente`) incluyen una **guardia CLI** en `_bootstrap.php`: si se
  invocan por HTTP responden `404` y no ejecutan nada. Es defensa en profundidad y
  **no depende** de que `.htaccess`/`AllowOverride` estén bien configurados.

## 4.4 Correo saliente vía Office 365 (Microsoft Graph)
- Alternativa a SMTP para el envío de correo transaccional, configurable **desde el
  panel** (`/admin/configuracion-correo`, permiso `configuracion.correo` — solo rol
  `admin` tras correr la migración `053`) en vez de variables de entorno. Si hay una
  configuración activa, tiene prioridad sobre `MAIL_MAILER=smtp` del `.env`; sin
  configuración activa, el sitio sigue enviando por SMTP exactamente como antes.
- **No requiere variables nuevas en `.env`**: las credenciales se cifran con
  `App\Core\Crypto` usando `APP_KEY` (la misma que ya existe). Si `APP_KEY` cambia,
  hay que volver a capturar el Client Secret desde el panel.
- **Paso previo obligatorio, fuera del sitio** — registrar una app en Azure AD /
  Microsoft Entra ID (lo hace quien administre el Microsoft 365 de la empresa):
  1. portal.azure.com → **Microsoft Entra ID** → **Registros de aplicaciones** →
     **Nuevo registro** (cualquier nombre, ej. "RYM - Correo saliente").
  2. En el registro creado, anotar el **Id. de aplicación (cliente)** y el
     **Id. de directorio (inquilino)** — son el `client_id` y `tenant_id` del panel.
  3. **Certificados y secretos** → **Nuevo secreto de cliente** → copiar el **valor**
     del secreto en cuanto se genera (no se vuelve a mostrar) — es el `client_secret`.
  4. **Permisos de API** → **Agregar un permiso** → **Microsoft Graph** →
     **Permisos de aplicación** (no "delegados") → buscar y marcar **Mail.Send**.
  5. En la misma pantalla, botón **"Conceder consentimiento de administrador"** (lo
     debe hacer un Administrador global o de aplicaciones del tenant) — sin este paso,
     el envío falla con error de autorización aunque las credenciales sean correctas.
  6. El **mailbox** capturado en el panel debe ser un buzón real de ese Microsoft 365
     (ej. `no-responder@importadorarym.com`) — con permisos de aplicación, Graph puede
     enviar como cualquier buzón del tenant sin necesitar licencia dedicada extra.
- Tras capturar los 4 datos y **activar** la configuración, usar el botón "Enviar
  correo de prueba" del panel (se manda al correo del admin que lo prueba, con límite
  de 5 intentos / 10 min) antes de darlo por bueno.
- Si `configuracion_correo` no tiene fila activa o la tabla aún no existe (migración
  no corrida), el sitio no falla: cae de vuelta a SMTP/log automáticamente.

## 4.5 Bitácora de visitas por oficinas
- Migraciones `051`/`052` agregan `oficinas` y `oficina_id` en dispositivos del
  checador, anfitriones, visitas y usuarios — retrocompatible (`oficina_id = NULL`
  se comporta igual que antes de esta función).
- **Paso manual tras migrar en el sitio real**: crear las oficinas reales desde
  `/admin/visitas/oficinas` y asignarlas a los anfitriones y dispositivos existentes.
  **En cuanto un dispositivo del checador (tablet de recepción) tenga una oficina
  asignada, ese kiosco deja de mostrar automáticamente a los anfitriones que NO
  tengan esa misma oficina asignada** — un anfitrión migrado con `oficina_id = NULL`
  desaparece de ese kiosco hasta que se le asigne oficina manualmente. No hacerlo deja
  al personal de recepción sin poder anunciar la visita de ese anfitrión.

## 5. SSL / HTTPS (BLOQUEANTE para el portal)
- Instalar el certificado (Let's Encrypt u otro).
- Poner `FORCE_HTTPS=true` → redirige http→https y activa HSTS.
- Sin SSL, **no exponer el portal de clientes** (login/sesión viajarían en claro).

## 6. Verificación post-deploy
- [ ] `/.env` no es accesible (403/404).
- [ ] El sitio carga por HTTPS y http redirige a https.
- [ ] Cabeceras presentes: `Content-Security-Policy`, `Strict-Transport-Security`,
      `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, `Permissions-Policy`.
- [ ] Formulario de cotización guarda un lead de prueba.
- [ ] Registro → aprobación → login → crear pedido funciona.
- [ ] Rich Results Test (Google) valida el JSON-LD; Sharing Debugger valida la imagen OG.
- [ ] La PWA es instalable (Service Worker activo).
- [ ] Si se activa correo por Office 365: el rol `admin` tiene el permiso
      `configuracion.correo` (lo asigna la migración `053` sola) y el botón "Enviar
      correo de prueba" del panel funciona.
- [ ] Si se activan oficinas en visitas: cada dispositivo del checador y cada
      anfitrión activo tiene su oficina asignada (ver §4.5) antes de dejarlos en uso.

## 7. Al publicar cambios de CSS/JS

1. **Reconstruir los bundles**: `php build/assets.php`
   Concatena y minifica el CSS en `public/assets/css/*.bundle.min.css`.
   Con `APP_DEBUG=false` el sitio sirve **un solo archivo** por sección; en
   desarrollo sigue sirviendo las hojas sueltas, así que no hay que reconstruir
   en cada cambio local.
2. **Comprobar antes de subir**: `php build/assets.php --check`
   Devuelve código 1 si algún bundle quedó desactualizado respecto de sus fuentes.
3. **Subir la versión del Service Worker** (`VERSION` en `public/sw.js`: `rym-v26`,
   `v27`…) para que los clientes con la PWA instalada reciban la actualización.

> Si olvidas el paso 1, el sitio **no** sirve CSS obsoleto: `App\Core\Assets`
> comprueba que el bundle sea más nuevo que sus fuentes y, si no lo es, vuelve a
> las hojas sueltas. Se pierde la optimización, no la corrección.

La composición de cada bundle se declara **una sola vez** en
`app/Core/Assets.php` (`BUNDLES`), que es lo que usan tanto el script de build
como las vistas.

## 8. Tipografía autoalojada
Inter y Poppins se sirven desde `public/assets/fonts/` (SIL Open Font License), no
desde Google Fonts. Ventajas: dos conexiones externas menos en la ruta crítica, la
CSP no autoriza dominios de Google para estilos ni fuentes, y no se envían datos de
los visitantes a un tercero.

- Inter va en versión **variable**: un archivo de 47 KB cubre los pesos 400–700
  (los cuatro estáticos equivalentes pesaban 188 KB).
- Un visitante en español descarga **70 KB** de fuentes (subset `latin`).
- Los `.woff2` se sirven con `Cache-Control: immutable` a un año: son inmutables
  porque al cambiar de versión cambia el nombre del archivo.
- Para actualizarlas hay que volver a descargarlas de Google Fonts y regenerar
  `public/assets/css/fonts.css`; ningún script del proyecto lo hace solo.
