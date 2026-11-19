-- =============================================
-- Job de SQL Server Agent "Nominas - Respaldos" (cada 15 minutos)
--   Paso 1 (T-SQL)      : EXEC dbo.EjecutaRespaldos       -> base completa y log
--   Paso 2 (PowerShell) : exporta checadas a ZIP (CSV)     -> cuando toca o si se pidió desde la página
-- Desactiva el job anterior "respalo" (lunes y jueves 00:00, nunca borraba respaldos viejos).
-- Requiere application/sql/respaldos.sql. Ejecutar con sqlcmd -x (sin sustitución de variables) o desde SSMS.
-- =============================================
USE [msdb];
GO

IF EXISTS (SELECT 1 FROM msdb.dbo.sysjobs WHERE name = N'respalo')
    EXEC msdb.dbo.sp_update_job @job_name = N'respalo', @enabled = 0;
GO

IF EXISTS (SELECT 1 FROM msdb.dbo.sysjobs WHERE name = N'Nominas - Respaldos')
    EXEC msdb.dbo.sp_delete_job @job_name = N'Nominas - Respaldos';
GO

DECLARE @ps NVARCHAR(MAX) = N'
$ErrorActionPreference = "Stop"
$cn = New-Object System.Data.SqlClient.SqlConnection "Server=$(ESCAPE_DQUOTE(SRVR));Database=Nominas;Integrated Security=True"
$cn.Open()

function Consulta([string]$sql) {
    $cmd = $cn.CreateCommand(); $cmd.CommandText = $sql; $cmd.CommandTimeout = 900
    $da = New-Object System.Data.SqlClient.SqlDataAdapter $cmd
    $dt = New-Object System.Data.DataTable
    [void]$da.Fill($dt)
    ,$dt
}
function Fin($id, $estado, $archivo, $tamano, $registros, $mensaje, $archivo2) {
    $cmd = $cn.CreateCommand(); $cmd.CommandText = "dbo.RespaldoChecadasFin"
    $cmd.CommandType = [System.Data.CommandType]::StoredProcedure
    [void]$cmd.Parameters.AddWithValue("@id", $id)
    [void]$cmd.Parameters.AddWithValue("@estado", $estado)
    foreach ($p in @(@("@archivo", $archivo), @("@tamano", $tamano), @("@registros", $registros), @("@mensaje", $mensaje), @("@archivo2", $archivo2))) {
        $v = $p[1]; if ($v -eq $null -or $v -eq "") { $v = [DBNull]::Value }
        [void]$cmd.Parameters.AddWithValue($p[0], $v)
    }
    [void]$cmd.ExecuteNonQuery()
}
function GuardarCsv($dt, $ruta) {
    $dt | Select-Object * -ExcludeProperty RowError, RowState, Table, ItemArray, HasErrors |
        Export-Csv -Path $ruta -NoTypeInformation -Encoding UTF8
}

# Azure Blob Storage (copia en la nube): PUT / GET / DELETE con el token SAS
[Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12
function Blob([string]$metodo, [string]$url, [string]$sas, [byte[]]$datos) {
    $sep = "?"; if ($url.Contains("?")) { $sep = "&" }
    $req = [System.Net.HttpWebRequest]::Create($url + $sep + $sas.TrimStart("?"))
    $req.Method = $metodo
    $req.Headers.Add("x-ms-version", "2019-12-12")
    $req.Timeout = 600000
    if ($datos -ne $null) {
        $req.Headers.Add("x-ms-blob-type", "BlockBlob")
        $req.ContentLength = $datos.Length
        $s = $req.GetRequestStream(); $s.Write($datos, 0, $datos.Length); $s.Close()
    }
    $resp = $req.GetResponse()
    $lector = New-Object System.IO.StreamReader $resp.GetResponseStream()
    $texto = $lector.ReadToEnd(); $resp.Close()
    $texto
}

# Una vez por hora: borra de la nube los respaldos más viejos que los días a guardar
if ((Get-Date).Minute -lt 15) {
    try {
        $nb = Consulta ("SELECT d.ruta, d.sas, (SELECT retencionDias FROM dbo.RespaldoConfig WHERE tipo = ''BASE'') AS diasBase, " +
                        "(SELECT retencionDias FROM dbo.RespaldoConfig WHERE tipo = ''CHECADAS'') AS diasChecadas " +
                        "FROM dbo.RespaldoDestino d WHERE d.orden > 1 AND d.activo = 1 AND d.tipo = ''NUBE''")
        foreach ($r in $nb.Rows) {
          try {
            foreach ($p in @(@("Base/", [int]$r["diasBase"]), @("Checadas/", [int]$r["diasChecadas"]))) {
                $limite = (Get-Date).ToUniversalTime().AddDays(-$p[1])
                [xml]$lista = Blob "GET" ($r["ruta"] + "?restype=container&comp=list&prefix=" + $p[0]) $r["sas"] $null
                foreach ($b in @($lista.EnumerationResults.Blobs.Blob)) {
                    if ($b -eq $null) { continue }
                    if ([DateTime]::Parse($b.Properties."Last-Modified").ToUniversalTime() -lt $limite) {
                        [void](Blob "DELETE" ($r["ruta"] + "/" + $b.Name) $r["sas"] $null)
                    }
                }
            }
          } catch { }
        }
    } catch { }
}

$ini = Consulta "EXEC dbo.RespaldoChecadasInicio"
$id = [int]$ini.Rows[0]["id"]
if ($id -eq 0) { $cn.Close(); return }

try {
    $dias    = [int]$ini.Rows[0]["diasExportar"]
    $sello   = Get-Date -Format "yyyyMMdd_HHmm"
    # La cuenta de SQL Agent solo puede escribir en su carpeta temporal:
    # el ZIP se arma ahí y se guarda en NominasRespaldos.dbo.ArchivoChecadas
    $tmp     = Join-Path $env:TEMP ("respaldo_checadas_" + $sello)
    New-Item -ItemType Directory -Force -Path $tmp | Out-Null

    $chec = Consulta ("SELECT id, usuarioid, dia_id, CONVERT(varchar(16), fechahora, 120) AS fechahora, CONVERT(varchar(10), fecha, 23) AS fecha, " +
                      "CONVERT(varchar(8), hora, 108) AS hora, reloj, idPlanta, CONVERT(varchar(19), fechaSubida, 120) AS fechaSubida " +
                      "FROM dbo.checadas WHERE fecha >= DATEADD(DAY, -" + $dias + ", CONVERT(date, GETDATE())) ORDER BY fechahora")
    GuardarCsv $chec (Join-Path $tmp ("checadas_" + $sello + ".csv"))

    $rel = Consulta ("SELECT e.deviceId, d.descripcion AS reloj, e.eventoId, CONVERT(varchar(19), e.fechahora, 120) AS fechahora, e.usuarioid, " +
                     "e.code, e.resultado, CONVERT(varchar(19), e.fechaDescarga, 120) AS fechaDescarga " +
                     "FROM dbo.RelojEvento e LEFT JOIN dbo.RelojDispositivo d ON d.deviceId = e.deviceId " +
                     "WHERE e.esChecada = 1 AND e.fechahora >= DATEADD(DAY, -" + $dias + ", CONVERT(date, GETDATE())) ORDER BY e.fechahora")
    GuardarCsv $rel (Join-Path $tmp ("checadas_relojes_" + $sello + ".csv"))

    $nombre = "Checadas_" + $sello + ".zip"
    $zip = Join-Path $env:TEMP $nombre
    if (Test-Path $zip) { Remove-Item $zip -Force }
    Add-Type -AssemblyName System.IO.Compression.FileSystem
    [System.IO.Compression.ZipFile]::CreateFromDirectory($tmp, $zip)
    Remove-Item $tmp -Recurse -Force
    $bytes = [System.IO.File]::ReadAllBytes($zip)

    $cmd = $cn.CreateCommand()
    $cmd.CommandText = "INSERT INTO NominasRespaldos.dbo.ArchivoChecadas (idRespaldo, nombre, datos) VALUES (@id, @nombre, @datos)"
    $cmd.CommandTimeout = 900
    [void]$cmd.Parameters.AddWithValue("@id", $id)
    [void]$cmd.Parameters.AddWithValue("@nombre", $nombre)
    $pd = $cmd.Parameters.Add("@datos", [System.Data.SqlDbType]::VarBinary, -1); $pd.Value = $bytes
    [void]$cmd.ExecuteNonQuery()

    # Además, copia del ZIP en las carpetas configuradas en la página (si se puede escribir ahí)
    $mensaje = "Checadas: " + $chec.Rows.Count + ", de relojes: " + $rel.Rows.Count
    $limite = (Get-Date).AddDays(-[int]$ini.Rows[0]["retencionDias"])
    $archivo = "NominasRespaldos: " + $nombre
    $copias = @()
    # carpeta principal (solo si se cambió) y copias en carpeta: otra carpeta, red o MEGA/Drive/OneDrive/Dropbox
    $dirs = @()
    if (-not ($ini.Rows[0]["carpeta1"] -is [DBNull])) { $dirs += @(,@("principal", [string]$ini.Rows[0]["carpeta1"])) }
    if (-not ($ini.Rows[0]["copias"] -is [DBNull])) {
        foreach ($d in ([string]$ini.Rows[0]["copias"]).Split("|")) { if ($d) { $dirs += @(,@("copia", $d)) } }
    }
    foreach ($par in $dirs) {
        $dir = $par[1]
        try {
            New-Item -ItemType Directory -Force -Path $dir | Out-Null
            Copy-Item $zip (Join-Path $dir $nombre) -Force
            Get-ChildItem $dir -Filter "Checadas_*.zip" | Where-Object { $_.LastWriteTime -lt $limite } | Remove-Item -Force
            if ($par[0] -eq "principal") { $archivo = Join-Path $dir $nombre } else { $copias += (Join-Path $dir $nombre) }
        } catch {
            $mensaje += ". No se pudo copiar el ZIP a " + $dir + " (la cuenta de SQL Agent necesita permiso de escritura)"
        }
    }
    # nubes de Azure
    $nubes = Consulta "SELECT ruta, sas FROM dbo.RespaldoDestino WHERE orden > 1 AND activo = 1 AND tipo = ''NUBE'' ORDER BY orden"
    foreach ($nb in $nubes.Rows) {
        try {
            $destino = [string]$nb["ruta"] + "/Checadas/" + $nombre
            [void](Blob "PUT" $destino ([string]$nb["sas"]) $bytes)
            $copias += $destino
        } catch {
            $mensaje += ". No se pudo subir el ZIP a " + [string]$nb["ruta"] + " (revisa internet y que el token SAS siga vigente)"
        }
    }
    Remove-Item $zip -Force

    Fin $id "OK" $archivo $bytes.Length $chec.Rows.Count $mensaje ($copias -join " | ")
}
catch {
    Fin $id "ERROR" $null $null $null $_.Exception.Message
    throw
}
finally { $cn.Close() }
';

EXEC msdb.dbo.sp_add_job
    @job_name = N'Nominas - Respaldos',
    @enabled = 1,
    @description = N'Respaldo completo, de log y de checadas de Nominas. Se configura en la página: Catálogos > Respaldos (tabla Nominas.dbo.RespaldoConfig).',
    @owner_login_name = N'sa';

EXEC msdb.dbo.sp_add_jobstep
    @job_name = N'Nominas - Respaldos',
    @step_name = N'Base y log',
    @subsystem = N'TSQL',
    @database_name = N'Nominas',
    @command = N'EXEC dbo.EjecutaRespaldos;',
    @on_success_action = 3,      -- siguiente paso
    @on_fail_action = 3;

EXEC msdb.dbo.sp_add_jobstep
    @job_name = N'Nominas - Respaldos',
    @step_name = N'Checadas',
    @subsystem = N'PowerShell',
    @command = @ps,
    @on_success_action = 1,
    @on_fail_action = 2;

EXEC msdb.dbo.sp_add_jobschedule
    @job_name = N'Nominas - Respaldos',
    @name = N'Cada 15 minutos',
    @freq_type = 4,              -- diario
    @freq_interval = 1,
    @freq_subday_type = 4,       -- minutos
    @freq_subday_interval = 15,
    @active_start_time = 0;

EXEC msdb.dbo.sp_add_jobserver @job_name = N'Nominas - Respaldos', @server_name = N'(local)';
GO
