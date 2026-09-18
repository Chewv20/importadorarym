# Sincroniza existencias desde Aspel SAE (Firebird, tabla INVE03) hacia el
# sitio de Importadora RYM, vía POST /integraciones/sae/disponibilidad.
#
# Uso:
#   1. Copia config.example.ps1 como config.local.ps1 (mismo folder) y llena
#      tus datos reales (ver ese archivo). config.local.ps1 NUNCA se sube a
#      ningún repositorio.
#   2. Prueba una corrida manual:  powershell -File sync_disponibilidad.ps1
#   3. Prográmalo en el Programador de tareas de Windows (ver README.md).
#
# Este script solo LEE de Firebird (SELECT) — nunca escribe en la base de SAE.

$ErrorActionPreference = 'Stop'
$scriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$logFile   = Join-Path $scriptDir 'sync_disponibilidad.log'

function Write-Log {
    param([string]$Mensaje)
    $linea = "[{0}] {1}" -f (Get-Date -Format 'yyyy-MM-dd HH:mm:ss'), $Mensaje
    Add-Content -Path $logFile -Value $linea -Encoding UTF8
}

try {
    $configPath = Join-Path $scriptDir 'config.local.ps1'
    if (-not (Test-Path $configPath)) {
        throw "Falta config.local.ps1 -- copia config.example.ps1 y llena tus datos reales antes de correr este script."
    }
    . $configPath   # define $FirebirdConnString, $SiteUrl, $SyncToken

    # 1) Leer existencias de Firebird (solo SELECT).
    $conn = New-Object System.Data.Odbc.OdbcConnection
    $conn.ConnectionString = $FirebirdConnString
    $conn.Open()
    try {
        $cmd = $conn.CreateCommand()
        $cmd.CommandText = 'SELECT CVE_ART, EXIST FROM INVE03'
        $reader = $cmd.ExecuteReader()

        $filas = New-Object System.Collections.Generic.List[hashtable]
        while ($reader.Read()) {
            $clave = $reader['CVE_ART']
            $exist = $reader['EXIST']
            if ($null -eq $clave -or $null -eq $exist) { continue }
            $filas.Add(@{ clave_sae = ("$clave").Trim(); existencia = [int]$exist })
        }
        $reader.Close()
    } finally {
        $conn.Close()
    }

    Write-Log ("Leídas {0} filas de INVE03." -f $filas.Count)

    # 2) Armar el JSON a mano para el arreglo "filas": ConvertTo-Json en
    # PowerShell 5.1 puede "aplanar" un arreglo de un solo elemento (deja de
    # verse como arreglo), así que se construye el envoltorio [ ... ] a mano
    # y solo se usa ConvertTo-Json por fila (eso sí es seguro).
    $piezasJson = $filas | ForEach-Object { $_ | ConvertTo-Json -Compress -Depth 3 }
    $bodyJson = '{"filas":[' + ($piezasJson -join ',') + ']}'

    # 3) Enviar al sitio.
    $url = "$SiteUrl/integraciones/sae/disponibilidad"
    $headers = @{ Authorization = "Bearer $SyncToken" }
    $respuesta = Invoke-RestMethod -Uri $url -Method Post -Headers $headers `
        -Body $bodyJson -ContentType 'application/json; charset=utf-8'

    Write-Log ("Respuesta del sitio: {0}" -f ($respuesta | ConvertTo-Json -Compress))

    if (-not $respuesta.ok) {
        Write-Log 'ADVERTENCIA: el sitio respondió ok=false, revisar el mensaje de arriba.'
        exit 1
    }
}
catch {
    Write-Log ("ERROR: {0}" -f $_.Exception.Message)
    exit 1
}
