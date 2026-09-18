# Sincronización de existencias desde Aspel SAE

Envía la existencia (`EXIST`) de cada artículo de Aspel SAE (tabla `INVE03` en
Firebird) al sitio de Importadora RYM, para que el catálogo y el flujo de
pedido muestren "Agotado" / "Bajo pedido" según lo que ya controla SAE.

Este script **solo lee** de Firebird (una consulta `SELECT`) — nunca escribe
nada en la base de datos de SAE.

## Puesta en marcha (una sola vez)

1. **Copiar la configuración**: duplica `config.example.ps1` como
   `config.local.ps1` en esta misma carpeta, y llena los 3 valores:
   - `$FirebirdConnString`: la cadena de conexión ODBC a Firebird. Si ya
     tienen un origen de datos ODBC funcionando para el reporte de Excel,
     ábranlo en el **Administrador de orígenes de datos ODBC**
     (`odbcad32.exe`, pestaña "DSN de sistema" o "DSN de usuario") y copien
     los mismos datos (servidor, puerto, ruta del archivo `.FDB`/`.GDB`,
     usuario, contraseña) al formato de la plantilla.
   - `$SiteUrl`: `https://www.importadorarym.com` (sin diagonal al final).
   - `$SyncToken`: pedirlo a quien administra el sitio — es el mismo valor
     que se capturó como `SAE_SYNC_TOKEN` en el `.env` de producción.

   **`config.local.ps1` nunca se debe compartir ni subir a ningún control de
   versiones** — trae la contraseña de la base de datos y el token del sitio.

2. **Probar una corrida manual**, desde PowerShell:
   ```powershell
   cd "ruta\a\esta\carpeta"
   .\sync_disponibilidad.ps1
   ```
   Si todo sale bien, verán una línea nueva en `sync_disponibilidad.log` (en
   esta misma carpeta) con la cantidad de artículos leídos y la respuesta del
   sitio, algo como:
   ```
   [2026-09-18 09:00:00] Leídas 842 filas de INVE03.
   [2026-09-18 09:00:01] Respuesta del sitio: {"ok":true,"actualizados":810,"no_encontrados":32,"omitidos":0,"errores":[]}
   ```
   - `actualizados`: productos del sitio cuya existencia se actualizó.
   - `no_encontrados`: artículos de SAE que no están dados de alta en el
     catálogo del sitio (normal — SAE puede tener más artículos de los que se
     venden en línea).
   - Si `ok` sale en `false`, o el script termina con un error, revisen el
     mensaje — típicamente es la cadena de conexión o el token mal capturados.

3. **Programar la tarea** en el Programador de tareas de Windows:
   - Acción: iniciar un programa → `powershell.exe`
   - Argumentos: `-ExecutionPolicy Bypass -File "ruta\completa\sync_disponibilidad.ps1"`
   - Frecuencia sugerida: **cada 1-2 horas, en horario laboral** (no hace
     falta más seguido; el sitio no bloquea pedidos por falta de stock, solo
     muestra un aviso, así que no es crítico que el dato sea al segundo).
   - Marcar "Ejecutar tanto si el usuario inició sesión como si no", para que
     corra aunque nadie tenga la sesión de Windows abierta.

## Si algo falla

- **`sync_disponibilidad.log`** (en esta carpeta) tiene el detalle de cada
  corrida, incluidos los errores.
- El sitio también registra cada llamada recibida en
  `storage/logs/sync-disponibilidad.log` (visible para quien administra el
  sitio) — sirve para confirmar si el problema es de este lado (no llega la
  petición) o del lado del sitio (llega pero algo se rechaza).
- Un `401` en la respuesta significa token incorrecto; un `429`, que se
  mandaron demasiadas peticiones seguidas (el sitio limita a 20 cada 10 min
  por equipo — de sobra para correr cada 1-2 horas).
