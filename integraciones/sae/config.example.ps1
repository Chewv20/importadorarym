# Copia este archivo como "config.local.ps1" (mismo folder) y llena los valores
# reales. config.local.ps1 NUNCA se sube al repositorio (ver .gitignore) porque
# contiene credenciales de Firebird y el token del sitio.

# Cadena de conexion a Firebird via ODBC. Usa el mismo driver que ya tienen
# instalado para el reporte de Excel (Firebird ODBC driver / "Firebird/
# InterBase(r) driver"). Si esta cadena no conecta, abre el Administrador de
# origenes de datos ODBC (odbcad32.exe) y copia los mismos parametros que usa
# el origen de datos ya configurado para Excel.
#
# Formato tipico:
#   "Driver={Firebird/InterBase(r) driver};Dbname=<servidor>/<puerto>:<ruta al .fdb o .gdb>;Uid=<usuario>;Pwd=<password>;"
# Ejemplo (ajustar con los datos reales):
$FirebirdConnString = "Driver={Firebird/InterBase(r) driver};Dbname=localhost/3050:C:\Aspel\SAE\Datos\EMPRESA.FDB;Uid=SYSDBA;Pwd=masterkey;"

# URL base del sitio (sin diagonal al final).
$SiteUrl = "https://www.importadorarym.com"

# Token del endpoint de sincronizacion (el mismo valor que SAE_SYNC_TOKEN en el
# .env de produccion del sitio -- pedirselo a quien administra el sitio).
$SyncToken = "PEGA-AQUI-EL-TOKEN-REAL"
