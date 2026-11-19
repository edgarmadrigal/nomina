<#
  Réplica de la base de Nóminas por log shipping (lo corre una tarea programada en la VM).

  1. Copia de CARPETA_RESPALDOS (Base\*.bak y Log\*.trn) a CARPETA_REPLICA lo que sea nuevo.
  2. Si la base no existe en SQL_DESTINO, restaura el FULL más reciente (WITH STANDBY/NORECOVERY).
  3. Aplica en orden los .trn que siguen al último restaurado (por LSN, así no importa el nombre).
  4. Escribe bitácora (replica.log) y estado.txt.

  Todas las conexiones salen de conexiones.txt (ver conexiones.ejemplo.txt).
  Uso:  powershell -NoProfile -ExecutionPolicy Bypass -File replicar.ps1 [-Reiniciar]
        -Reiniciar  vuelve a restaurar desde el FULL más reciente (si se rompió la cadena).
#>
param([switch]$Reiniciar)

$ErrorActionPreference = 'Stop'
$aqui = Split-Path -Parent $MyInvocation.MyCommand.Path

# ------------------------------------------------------------------ configuración
$archivoCfg = Join-Path $aqui 'conexiones.txt'
if (-not (Test-Path $archivoCfg)) {
    Copy-Item (Join-Path $aqui 'conexiones.ejemplo.txt') $archivoCfg
}
$cfg = @{}
foreach ($l in Get-Content $archivoCfg -Encoding UTF8) {
    if ($l -match '^\s*([A-Z_]+)\s*=\s*(.*?)\s*$') { $cfg[$matches[1]] = $matches[2] }
}
function Cfg($n, $def = '') { if ($cfg.ContainsKey($n) -and $cfg[$n] -ne '') { $cfg[$n] } else { $def } }

$origen   = (Cfg 'CARPETA_RESPALDOS').TrimEnd('\')
$local    = (Cfg 'CARPETA_REPLICA' 'C:\ReplicaNominas').TrimEnd('\')
$base     = Cfg 'BASE_REPLICA' 'Nominas'
$modo     = (Cfg 'MODO_REPLICA' 'STANDBY').ToUpper()
$dias     = [int](Cfg 'DIAS_CONSERVAR' '7')
$undo     = Join-Path $local "$($base)_undo.dat"

New-Item -ItemType Directory -Force "$local\Base", "$local\Log" | Out-Null
$bitacora = Join-Path $local 'replica.log'

function Log($msg) {
    $linea = "$(Get-Date -Format 'yyyy-MM-dd HH:mm:ss') $msg"
    Add-Content -Path $bitacora -Value $linea -Encoding UTF8
    Write-Output $linea
}

# ------------------------------------------------------------------ SQL
$cs = "Server=$(Cfg 'SQL_REPLICA' 'localhost');Database=master;Application Name=ReplicaNominas;"
if ((Cfg 'SQL_REPLICA_USUARIO') -ne '') { $cs += "User ID=$(Cfg 'SQL_REPLICA_USUARIO');Password=$(Cfg 'SQL_REPLICA_CLAVE');" }
else { $cs += 'Integrated Security=SSPI;' }
$cn = New-Object System.Data.SqlClient.SqlConnection $cs

function Consulta($sql) {
    $cmd = $cn.CreateCommand(); $cmd.CommandText = $sql; $cmd.CommandTimeout = 0
    $t = New-Object System.Data.DataTable
    $t.Load($cmd.ExecuteReader())
    return ,$t
}
function Ejecuta($sql) {
    $cmd = $cn.CreateCommand(); $cmd.CommandText = $sql; $cmd.CommandTimeout = 0
    [void]$cmd.ExecuteNonQuery()
}
function Q($s) { $s.Replace("'", "''") }

function SacarUsuarios {
    # En STANDBY alguien puede estar consultando la copia: se le corta para poder restaurar
    $t = Consulta "SELECT session_id FROM sys.dm_exec_sessions WHERE database_id = DB_ID(N'$(Q $base)') AND session_id <> @@SPID"
    foreach ($r in $t.Rows) { try { Ejecuta "KILL $($r.session_id)" } catch {} }
}

function Recuperacion {
    if ($modo -eq 'NORECOVERY') { 'NORECOVERY' } else { "STANDBY = N'$(Q $undo)'" }
}

function UltimoLsn {
    # LSN final del último respaldo restaurado en esta base (FULL o LOG)
    $t = Consulta @"
SELECT TOP 1 bs.last_lsn, bs.backup_finish_date
FROM msdb.dbo.restorehistory rh
JOIN msdb.dbo.backupset bs ON bs.backup_set_id = rh.backup_set_id
WHERE rh.destination_database_name = N'$(Q $base)'
ORDER BY rh.restore_history_id DESC
"@
    if ($t.Rows.Count -eq 0) { return $null }
    return $t.Rows[0]
}

function Encabezado($archivo) {
    $t = Consulta "RESTORE HEADERONLY FROM DISK = N'$(Q $archivo)'"
    return $t.Rows[0]   # FirstLSN, LastLSN, BackupType, BackupFinishDate
}

function RestaurarFull {
    $bak = Get-ChildItem "$local\Base" -Filter *.bak | Sort-Object LastWriteTime -Descending | Select-Object -First 1
    if (-not $bak) { throw "No hay respaldo FULL (.bak) en $local\Base para iniciar la copia." }
    Log "Restaurando FULL $($bak.Name) ..."

    $rutas = Consulta "SELECT CONVERT(nvarchar(260), SERVERPROPERTY('InstanceDefaultDataPath')) AS datos, CONVERT(nvarchar(260), SERVERPROPERTY('InstanceDefaultLogPath')) AS logs"
    $archivos = Consulta "RESTORE FILELISTONLY FROM DISK = N'$(Q $bak.FullName)'"
    $moves = foreach ($f in $archivos.Rows) {
        $dir = if ($f.Type -eq 'L') { $rutas.Rows[0].logs } else { $rutas.Rows[0].datos }
        "MOVE N'$(Q $f.LogicalName)' TO N'$(Q (Join-Path $dir ("$($base)_$($f.LogicalName)$([IO.Path]::GetExtension($f.PhysicalName))")))'"
    }

    $existe = (Consulta "SELECT DB_ID(N'$(Q $base)') AS id").Rows[0].id -isnot [DBNull]
    if ($existe) { SacarUsuarios }
    Ejecuta "RESTORE DATABASE [$base] FROM DISK = N'$(Q $bak.FullName)' WITH REPLACE, $($moves -join ', '), $(Recuperacion)"
    Log "FULL restaurado."
}

# ------------------------------------------------------------------ principal
try {
    # 1. Traer respaldos nuevos
    $usr = Cfg 'RESPALDOS_USUARIO'
    $puedeEntrar = { try { Test-Path "$origen\Log" -ErrorAction Stop } catch { $false } }
    if ($usr -ne '' -and -not (& $puedeEntrar)) {
        if ((Cfg 'RESPALDOS_CLAVE') -eq '') { throw "Falta RESPALDOS_CLAVE en conexiones.txt (contraseña de $usr)." }
        # 2>&1 dentro de cmd: el error de net use queda como texto y no corta el script
        $salida = cmd /c "net use `"$origen`" `"$(Cfg 'RESPALDOS_CLAVE')`" /user:`"$usr`" /persistent:no 2>&1"
        if ($LASTEXITCODE -ne 0) {
            throw "Paso 1 (conectar a $origen como $usr): $((@($salida) | Where-Object { $_ -match '\S' }) -join ' ')"
        }
    }
    if (-not (& $puedeEntrar)) { throw "Paso 1: no se puede leer $origen\Log (revisa CARPETA_RESPALDOS / RESPALDOS_USUARIO en conexiones.txt)." }

    foreach ($sub in @(@('Base', '*.bak'), @('Log', '*.trn'))) {
        $salida = robocopy "$origen\$($sub[0])" "$local\$($sub[0])" $sub[1] /XO /R:2 /W:10 /NP /NJH /NJS /NDL /NFL
        if ($LASTEXITCODE -ge 8) {
            throw "Paso 2 (copiar $origen\$($sub[0])), robocopy código ${LASTEXITCODE}: $((@($salida) | Where-Object { $_ -match 'ERROR' } | Select-Object -First 2) -join ' ')"
        }
    }

    try { $cn.Open() }
    catch { throw "Paso 3 (conectar al SQL de la réplica $(Cfg 'SQL_REPLICA')): $($_.Exception.Message)" }

    # 2. Copia inicial (o reinicio a mano)
    $existe = (Consulta "SELECT DB_ID(N'$(Q $base)') AS id").Rows[0].id -isnot [DBNull]
    if (-not $existe -or $Reiniciar) { RestaurarFull }

    # 3. Logs pendientes, en orden de LSN
    $ultimo = UltimoLsn
    if ($null -eq $ultimo) { throw "La base $base existe pero no tiene historial de restauración; corre replicar.ps1 -Reiniciar." }
    $lsn = [decimal]$ultimo.last_lsn
    # Solo se revisan los .trn más nuevos que el último restaurado (con margen), no todos
    $desde = ([datetime]$ultimo.backup_finish_date).AddHours(-3)

    $logs = foreach ($f in Get-ChildItem "$local\Log" -Filter *.trn | Where-Object { $_.LastWriteTime -ge $desde } | Sort-Object Name) {
        $h = Encabezado $f.FullName
        if ([decimal]$h.LastLSN -gt $lsn) {
            [pscustomobject]@{ Archivo = $f; First = [decimal]$h.FirstLSN; Last = [decimal]$h.LastLSN; Fecha = $h.BackupFinishDate }
        }
    }
    $logs = @($logs | Sort-Object First)

    $aplicados = 0
    foreach ($l in $logs) {
        if ($l.First -gt $lsn) {
            throw "Falta un respaldo de log antes de $($l.Archivo.Name) (se rompió la cadena). Corre replicar.ps1 -Reiniciar cuando haya un FULL nuevo."
        }
        SacarUsuarios
        Ejecuta "RESTORE LOG [$base] FROM DISK = N'$(Q $l.Archivo.FullName)' WITH $(Recuperacion)"
        $lsn = $l.Last
        $aplicados++
    }

    $u = UltimoLsn
    $al = if ($u) { ([datetime]$u.backup_finish_date).ToString('yyyy-MM-dd HH:mm') } else { '?' }
    if ($aplicados -gt 0) { Log "Aplicados $aplicados log(s). Copia al día hasta $al." }

    # 4. Estado y limpieza
    @(
        "Principal: $(Cfg 'SQL_PRINCIPAL') / $(Cfg 'BASE_PRINCIPAL') ($origen)"
        "Réplica  : $(Cfg 'SQL_REPLICA') / $base ($modo)"
        "Al día   : $al"
        "Revisado : $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')"
        "Estado   : OK"
    ) | Set-Content (Join-Path $local 'estado.txt') -Encoding UTF8

    $limite = (Get-Date).AddDays(-$dias)
    $ultimoBak = Get-ChildItem "$local\Base" -Filter *.bak | Sort-Object LastWriteTime -Descending | Select-Object -First 1
    Get-ChildItem "$local\Base", "$local\Log" -File |
        Where-Object { $_.LastWriteTime -lt $limite -and $_.FullName -ne $ultimoBak.FullName } |
        Remove-Item -Force -ErrorAction SilentlyContinue
    exit 0
}
catch {
    Log "ERROR: $($_.Exception.Message)"
    @(
        "Revisado : $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')"
        "Estado   : ERROR - $($_.Exception.Message)"
    ) | Set-Content (Join-Path $local 'estado.txt') -Encoding UTF8
    exit 1
}
finally {
    if ($cn.State -eq 'Open') { $cn.Close() }
}
