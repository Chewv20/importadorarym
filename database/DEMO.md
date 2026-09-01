# Presentación / Demo — Importadora RYM

Este paquete permite montar una **muestra funcional** del sistema en un servidor
externo, con datos de ejemplo en todos los módulos.

## 1. Importar la base de datos

El archivo **`database/demo.sql`** contiene la estructura completa (27 tablas) y
los datos de demostración. Impórtalo en una base vacía:

**Opción A — phpMyAdmin (hosting típico):**
1. Crea una base de datos nueva (ej. `importadorarym`) con cotejamiento `utf8mb4_general_ci`.
2. Entra a esa base → pestaña **Importar** → selecciona `demo.sql` → **Continuar**.

**Opción B — línea de comandos:**
```bash
mysql -u USUARIO -p NOMBRE_BD < database/demo.sql
```

> El archivo ya trae `SET NAMES utf8mb4`, así que los acentos se importan correctos.

## 2. Configurar el `.env` en el servidor

Copia `.env.example` a `.env` y ajusta al menos:

```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tu-dominio-o-subdominio/...     # URL real donde quede montado
APP_KEY=<genera uno nuevo: php -r "echo bin2hex(random_bytes(32));">

DB_HOST=...        DB_DATABASE=...
DB_USERNAME=...    DB_PASSWORD=...

# Para la demo puedes dejar el correo en modo log (no envía, escribe en storage/logs/mail.log):
MAIL_MAILER=log
# …o configúralo con SMTP real si quieres mostrar los correos.

FORCE_HTTPS=true    # solo si el servidor ya tiene certificado SSL
```

`APP_URL` **debe ser la URL real** o los enlaces canónicos/imágenes saldrán mal.

## 3. Credenciales de acceso

| Rol | Usuario | Contraseña |
|-----|---------|------------|
| **Administrador** | `admin@importadorarym.com.mx` | `Rym#Admin2026` |
| **Vendedor** (panel filtrado) | `vendedor@demo.rym` | `Demo#2026` |
| **Cliente** (portal) | `compras@laesquina.demo` | `Demo#2026` |
| **Cliente** (portal) | `pedidos@elfogon.demo` | `Demo#2026` |

Otros clientes demo: `contacto@loscompas.demo`, `ventas@trigodeoro.demo` (todos `Demo#2026`).

- **Panel admin:** `…/portal/login` → entra con el admin.
- **Portal de clientes:** `…/portal/login` → entra con un cliente.

## 4. Qué incluye la demo

- **Catálogo:** 5 categorías principales + 3 subcategorías (Biodegradables → Bolsas,
  Cubiertos; Bolsas y empaques → Compostables), 14 productos con precio, SKU, clave SAE
  y esquema de impuestos ya capturados (listos para exportar a SAE sin bloqueos).
  5 marcados como **personalizables** (admiten impresión con el logo del cliente:
  vasos y contenedor kraft), con su distintivo propio en el catálogo público junto
  al de "Destacado".
- **Clientes:** 4 (2 aprobados para levantar pedidos, asignados al vendedor).
- **Cotizaciones:** 4 en distintos estados (nueva, cotizada, aprobada) + 1 lead público.
- **Pedidos:** 4 en distintos estados (enviado, en proceso, sincronizado) con precios.
- **Pedido recurrente:** 1 programado (recordatorio cada 30 días) — el cron que lo avisa
  no corre solo en la demo; agéndalo si quieres mostrarlo en vivo (`docs/DESPLIEGUE.md §4.2.1`).
- **Encuestas de experiencia:** 2 pedidos calificados (una con comentario positivo, otra con áreas de mejora).
- **Bolsa de trabajo:** 3 vacantes (2 abiertas, 1 cerrada) y 4 postulaciones en distintos
  estados, una con entrevista agendada.
- **Visitas:** 5 anfitriones, 2 dispositivos de recepción y 6 visitas registradas.
- **Logos de proveedores y de clientes:** 11 y 20 respectivamente, ya sembrados y activos.
- Roles y permisos completos (RBAC).

## 5. Kiosco de visitas (para mostrarlo)

Los 2 dispositivos vienen **creados pero sin activar** (por seguridad, la cookie
no se puede sembrar). Para demostrar el kiosco:
1. Entra al panel → **Visitas → Dispositivos** → botón **"Regenerar enlace"** de un dispositivo.
2. Copia el enlace de activación y **ábrelo una vez** en el equipo/pestaña que hará de kiosco.
3. Queda autorizado; a partir de ahí `…/checador` muestra el formulario de registro.

## 6. Antes de considerarlo "producción"

Esta demo es para mostrar; si se volviera el sitio real:
- Cambia **todas** las contraseñas y regenera `APP_KEY`.
- Usa un usuario de base de datos dedicado (no `root`).
- Instala SSL y pon `FORCE_HTTPS=true`.
- Garantiza `AllowOverride All` para que los `.htaccess` protejan el backend.
- Ver `docs/DESPLIEGUE.md` para el checklist completo.

## 7. Regenerar `demo.sql` (cuando el esquema o los seeds cambien)

Se genera sobre una **base de datos temporal**, nunca sobre la de desarrollo, para no
mezclar datos ficticios con datos reales de trabajo:

1. En `.env`, cambia `DB_DATABASE=importadorarym` por `DB_DATABASE=importadorarym_demo`
   (anota el valor original para restaurarlo después).
2. `php database/migrate.php` — crea la base temporal y aplica todas las migraciones.
3. `php database/seed.php "Rym#Admin2026"` — admin + categorías/productos base.
4. `php database/demo_seed.php` — todo el contenido rico (idempotente: se puede
   volver a correr sin duplicar nada).
5. `php database/backup.php` — genera `storage/backups/rym-<fecha>.sql`.
6. Copia ese archivo a `database/demo.sql` (sobrescribe el anterior).
7. **Restaura `DB_DATABASE` a su valor original en `.env`.**
8. Elimina la base temporal: `DROP DATABASE importadorarym_demo;`
9. Borra el respaldo de `storage/backups/` que generó el paso 5 (ya está copiado en
   `database/demo.sql`; dejarlo ahí solo duplica el archivo).

Verifica al final que `.env` quedó exactamente como antes y que los conteos de la base
de desarrollo (`SELECT COUNT(*) FROM usuarios`, etc.) no cambiaron.
