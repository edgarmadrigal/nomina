<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Respaldos automáticos (application/sql/respaldos.sql y respaldos_job.sql).
 * Los ejecuta el job "Nominas - Respaldos" de SQL Server Agent cada 15 minutos;
 * aquí solo se configuran, se consultan y se piden manualmente.
 */
class RespaldoModel extends CI_Model {

    const JOB = 'Nominas - Respaldos';

    function __construct(){
        parent::__construct();
        // BACKUP manda mensajes informativos ("Processed N pages..."); sin esto sqlsrv los toma como error
        if (function_exists('sqlsrv_configure')) sqlsrv_configure('WarningsReturnAsErrors', 0);
    }

    /** Configuración por tipo con el último respaldo correcto de cada uno. */
    public function config(){
        $sql = "SELECT c.tipo, c.descripcion, c.activo, c.hora, c.cadaMinutos, c.retencionDias, c.diasExportar, c.solicitado,
                       CONVERT(VARCHAR(19), u.fin, 120) AS ultimoOk,
                       u.tamanoBytes AS ultimoTamano,
                       (SELECT COUNT(*) FROM dbo.RespaldoLog l WHERE l.tipo = c.tipo AND l.estado = 'OK') AS archivos,
                       (SELECT SUM(l.tamanoBytes) FROM dbo.RespaldoLog l WHERE l.tipo = c.tipo AND l.estado = 'OK') AS espacioBytes,
                       (SELECT TOP 1 l.estado FROM dbo.RespaldoLog l WHERE l.tipo = c.tipo AND l.estado <> 'SOLICITADO' ORDER BY l.id DESC) AS ultimoEstado
                FROM dbo.RespaldoConfig c
                OUTER APPLY (SELECT TOP 1 l.fin, l.tamanoBytes FROM dbo.RespaldoLog l
                             WHERE l.tipo = c.tipo AND l.estado = 'OK' ORDER BY l.fin DESC) u
                ORDER BY CASE c.tipo WHEN 'BASE' THEN 1 WHEN 'LOG' THEN 2 WHEN 'CHECADAS' THEN 3 ELSE 4 END";
        return $this->db->query($sql)->result_array();
    }

    public function guardarConfig($tipo, $d){
        $sql = "UPDATE dbo.RespaldoConfig
                SET activo = ?, hora = ?, cadaMinutos = ?, retencionDias = ?, diasExportar = ?
                WHERE tipo = ?";
        return $this->db->query($sql, array($d['activo'], $d['hora'], $d['cadaMinutos'], $d['retencionDias'], $d['diasExportar'], $tipo));
    }

    public function historial($tipo, $dias){
        $sql = "SELECT TOP 500 id, tipo, estado, origen, archivo, archivo2, tamanoBytes, registros, mensaje,
                       CONVERT(VARCHAR(19), inicio, 120) AS inicio,
                       CONVERT(VARCHAR(19), fin, 120) AS fin,
                       DATEDIFF(SECOND, inicio, ISNULL(fin, GETDATE())) AS segundos
                FROM dbo.RespaldoLog
                WHERE (? = '' OR tipo = ?) AND inicio >= DATEADD(DAY, -?, GETDATE()) AND estado <> 'SOLICITADO'
                ORDER BY id DESC";
        return $this->db->query($sql, array($tipo, $tipo, (int) $dias))->result_array();
    }

    /** Estado del job, tamaño del log y espacio libre en el servidor. */
    public function estado(){
        $job = $this->db->query(
            "SELECT TOP 1 j.enabled,
                    CASE WHEN s.next_run_date > 0 THEN CONVERT(VARCHAR(19),
                        msdb.dbo.agent_datetime(s.next_run_date, s.next_run_time), 120) END AS siguiente,
                    (SELECT TOP 1 CONVERT(VARCHAR(19), msdb.dbo.agent_datetime(h.run_date, h.run_time), 120)
                     FROM msdb.dbo.sysjobhistory h WHERE h.job_id = j.job_id AND h.step_id = 0
                     ORDER BY h.instance_id DESC) AS ultimaCorrida,
                    (SELECT TOP 1 h.run_status FROM msdb.dbo.sysjobhistory h
                     WHERE h.job_id = j.job_id AND h.step_id = 0 ORDER BY h.instance_id DESC) AS ultimoResultado
             FROM msdb.dbo.sysjobs j
             LEFT JOIN msdb.dbo.sysjobschedules s ON s.job_id = j.job_id
             WHERE j.name = ?", array(self::JOB))->row_array();

        $log = $this->db->query(
            "SELECT CAST(total_log_size_in_bytes / 1048576.0 AS INT) AS logMB,
                    CAST(used_log_space_in_bytes / 1048576.0 AS INT) AS logUsadoMB
             FROM sys.dm_db_log_space_usage")->row_array();

        $disco = $this->db->query(
            "SELECT TOP 1 CAST(available_bytes / 1073741824.0 AS DECIMAL(10,1)) AS libreGB,
                          CAST(total_bytes / 1073741824.0 AS DECIMAL(10,1)) AS totalGB,
                          volume_mount_point AS unidad
             FROM sys.master_files f
             CROSS APPLY sys.dm_os_volume_stats(f.database_id, f.file_id)
             WHERE f.database_id = DB_ID('Nominas')")->row_array();

        $carpeta = $this->db->query("SELECT dbo.RespaldoCarpeta('') AS carpeta")->row_array();

        return array('job' => $job, 'log' => $log, 'disco' => $disco,
                     'carpeta' => $carpeta ? rtrim($carpeta['carpeta'], '\\') : '',
                     'destinos' => $this->destinos());
    }

    /** Carpeta principal (ruta vacía = la predeterminada) y copias adicionales (hasta 3). */
    public function destinos(){
        $filas = $this->db->query(
            "SELECT orden, ISNULL(ruta, '') AS ruta, activo, tipo, servicio, CASE WHEN NULLIF(sas, N'') IS NULL THEN 0 ELSE 1 END AS tieneSas,
                    CONVERT(NVARCHAR(300), SERVERPROPERTY('InstanceDefaultBackupPath')) + N'\\Nominas' AS predeterminada
             FROM dbo.RespaldoDestino ORDER BY orden")->result_array();
        // el token SAS nunca se manda a la página, solo si ya hay uno guardado
        $d = array('ruta1' => '', 'copias' => array(),
                   'predeterminada' => $this->db->query("SELECT CONVERT(NVARCHAR(300), SERVERPROPERTY('InstanceDefaultBackupPath')) + N'\\Nominas' AS p")->row()->p);
        foreach ($filas as $f) {
            if ($f['orden'] == 1) { $d['ruta1'] = $f['ruta']; continue; }
            $d['copias'][] = array('servicio' => $f['servicio'], 'ruta' => $f['ruta'], 'tieneSas' => (int) $f['tieneSas']);
        }
        return $d;
    }

    /**
     * Prueba que SQL Server pueda escribir en la carpeta (dbo.RespaldoProbarCarpeta).
     * Devuelve null si está bien o el mensaje de error.
     */
    public function probarCarpeta($ruta){
        $debug = $this->db->db_debug;
        $this->db->db_debug = FALSE;
        $q = $this->db->query("EXEC dbo.RespaldoProbarCarpeta ?", array($ruta));
        $error = $q ? null : $this->db->error();
        $this->db->db_debug = $debug;
        if (!$error) return null;
        // quita el prefijo del driver: [Microsoft][ODBC Driver 17 for SQL Server][SQL Server]
        $msg = preg_replace('/^(\[[^\]]*\])+/', '', $error['message']);
        if (strpos($msg, 'Invalid Parameter') !== false) {
            $msg = 'No se pudo entrar a ' . $ruta . ' desde el servidor de SQL. Si es carpeta de red, revisa el nombre del equipo y que esté compartida.';
        } elseif (strpos($msg, 'xp_create_subdir') !== false) {
            $msg = 'No se pudo crear la carpeta ' . $ruta . ' en el servidor de SQL: ' . preg_replace("/^.*returned error \d+, '(.*)'.*$/s", '$1', $msg);
        }
        return $msg;
    }

    /** Prueba la copia en la nube (dbo.RespaldoProbarNube). Devuelve null si está bien o el mensaje de error. */
    public function probarNube($url, $sas){
        $debug = $this->db->db_debug;
        $this->db->db_debug = FALSE;
        $q = $this->db->query("EXEC dbo.RespaldoProbarNube ?, ?", array($url, $sas));
        $error = $q ? null : $this->db->error();
        $this->db->db_debug = $debug;
        return $error ? preg_replace('/^(\[[^\]]*\])+/', '', $error['message']) : null;
    }

    /** Token SAS guardado (para no pedirlo otra vez al cambiar otra cosa). */
    public function sasGuardado($url){
        $f = $this->db->query("SELECT TOP 1 sas FROM dbo.RespaldoDestino WHERE orden > 1 AND tipo = 'NUBE' AND ruta = ?", array($url))->row_array();
        return $f ? (string) $f['sas'] : '';
    }

    /** $copias: [['servicio' => ..., 'ruta' => ..., 'sas' => ...], ...] (máximo 3). */
    public function guardarDestinos($ruta1, $copias){
        $this->db->trans_start();
        $this->db->query("UPDATE dbo.RespaldoDestino SET ruta = ?, activo = 1 WHERE orden = 1", array($ruta1 === '' ? null : $ruta1));
        $this->db->query("DELETE FROM dbo.RespaldoDestino WHERE orden > 1");
        foreach (array_values($copias) as $i => $c) {
            $this->db->query("INSERT INTO dbo.RespaldoDestino (orden, ruta, activo, tipo, servicio, sas) VALUES (?, ?, 1, ?, ?, ?)",
                array($i + 2, $c['ruta'], $c['servicio'] === 'AZURE' ? 'NUBE' : 'CARPETA', $c['servicio'],
                      $c['servicio'] === 'AZURE' ? $c['sas'] : null));
        }
        $this->db->trans_complete();
    }

    /** BASE y LOG se hacen en el momento; CHECADAS se pide al job (paso PowerShell); CODIGO lo arma PHP; ROSTROS en segundo plano. */
    public function respaldarAhora($tipo, $usuario){
        $origen = substr('Manual (' . $usuario . ')', 0, 60);
        if ($tipo === 'CODIGO') return $this->respaldoCodigo($origen);
        if ($tipo === 'ROSTROS') return $this->lanzarRostros($origen);
        if ($tipo === 'CHECADAS') {
            $this->db->query("INSERT INTO dbo.RespaldoLog (tipo, estado, origen) VALUES ('CHECADAS', 'SOLICITADO', ?)", array($origen));
            $this->db->query("UPDATE dbo.RespaldoConfig SET solicitado = 1 WHERE tipo = 'CHECADAS'");
            $this->db->query("EXEC msdb.dbo.sp_start_job @job_name = ?, @step_name = N'Checadas'", array(self::JOB));
            return array('exito' => true, 'mensaje' => 'Se pidió el respaldo de checadas; en unos segundos aparece en el historial.');
        }
        $this->db->query("EXEC dbo.EjecutaRespaldos @forzar = ?, @origen = ?", array($tipo, $origen));
        $ult = $this->db->query("SELECT TOP 1 estado, mensaje FROM dbo.RespaldoLog WHERE tipo = ? ORDER BY id DESC", array($tipo))->row_array();
        $ok = $ult && $ult['estado'] === 'OK';
        return array('exito' => $ok, 'mensaje' => $ok ? 'Respaldo terminado.' : ('El respaldo falló: ' . ($ult ? $ult['mensaje'] : '')));
    }

    /**
     * ZIP de checadas: se guarda en NominasRespaldos.dbo.ArchivoChecadas (la cuenta de
     * SQL Agent no puede escribir en la carpeta de respaldos). Llega en base64 porque el
     * driver sqlsrv no maneja bien binarios grandes.
     */
    // ---------------- Código del sistema (application/sql/respaldo_codigo.sql) ----------------

    /** Carpetas del proyecto que no van en el ZIP (rutas relativas, en minúsculas). */
    private $fueraDelZip = array('.git', 'uploads', '~', 'user_guide', 'tools/relojsync/bin', 'tools/relojsync/obj', 'tools/relojsync/publish');

    /**
     * Arma un ZIP con el código del sistema (FCPATH) y lo guarda en NominasRespaldos.dbo.ArchivoCodigo,
     * registrado en RespaldoLog (tipo CODIGO). Borra los de más días que los configurados.
     */
    public function respaldoCodigo($origen){
        ini_set('max_execution_time', 0);
        ignore_user_abort(true);
        $id = (int) $this->db->query(
            "DECLARE @t TABLE (id INT); SET NOCOUNT ON;
             INSERT INTO dbo.RespaldoLog (tipo, estado, origen) OUTPUT INSERTED.id INTO @t VALUES ('CODIGO', 'EN PROCESO', ?);
             SELECT id FROM @t", array($origen))->row()->id;
        $sello  = (new DateTime('now', new DateTimeZone('America/Mexico_City')))->format('Ymd_Hi');   // php.ini trae Europe/Berlin
        $nombre = 'Codigo_nomina_' . $sello . '.zip';
        $zipRuta = rtrim(sys_get_temp_dir(), '\\/') . DIRECTORY_SEPARATOR . $nombre;
        try {
            $base = rtrim(str_replace('\\', '/', realpath(FCPATH)), '/');
            $zip = new ZipArchive();
            if ($zip->open($zipRuta, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) throw new Exception('No se pudo crear el ZIP en ' . $zipRuta);
            $archivos = 0; $omitidos = 0;
            $it = new RecursiveIteratorIterator(
                new RecursiveCallbackFilterIterator(
                    new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS | FilesystemIterator::UNIX_PATHS),
                    function ($f) use ($base) {
                        $rel = strtolower(substr(str_replace('\\', '/', $f->getPathname()), strlen($base) + 1));
                        return !in_array($rel, $this->fueraDelZip, true);
                    }),
                RecursiveIteratorIterator::LEAVES_ONLY);
            foreach ($it as $f) {
                if (!$f->isFile()) continue;
                $rel = substr(str_replace('\\', '/', $f->getPathname()), strlen($base) + 1);
                if ($f->isReadable() && $zip->addFile($f->getPathname(), 'nomina/' . $rel)) $archivos++; else $omitidos++;
            }
            if (!$zip->close()) throw new Exception('No se pudo cerrar el ZIP.');
            $tamano = filesize($zipRuta);

            // El binario va como flujo (stream) para no cargar todo el ZIP en memoria
            $fh = fopen($zipRuta, 'rb');
            $ok = sqlsrv_query($this->db->conn_id,
                "INSERT INTO NominasRespaldos.dbo.ArchivoCodigo (idRespaldo, nombre, datos) VALUES (?, ?, ?)",
                array($id, $nombre, array($fh, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STREAM(SQLSRV_ENC_BINARY), SQLSRV_SQLTYPE_VARBINARY('max'))));
            fclose($fh);
            if ($ok === false) {
                $e = sqlsrv_errors();
                throw new Exception('No se pudo guardar el ZIP en NominasRespaldos: ' . ($e ? $e[0]['message'] : ''));
            }
            $borrados = $this->limpiarCodigo();
            $mensaje = 'Archivos: ' . $archivos . ($omitidos ? ', no se pudieron leer: ' . $omitidos : '')
                     . '. Sin .git, uploads ni lo compilado de RelojSync.' . ($borrados ? ' Se borraron ' . $borrados . ' respaldo(s) viejo(s).' : '');
            $this->db->query("UPDATE dbo.RespaldoLog SET estado = 'OK', fin = GETDATE(), archivo = ?, tamanoBytes = ?, registros = ?, mensaje = ? WHERE id = ?",
                array('NominasRespaldos: ' . $nombre, $tamano, $archivos, $mensaje, $id));
            return array('exito' => true, 'mensaje' => 'Respaldo del código terminado: ' . $archivos . ' archivos, ' . round($tamano / 1048576, 1) . ' MB.');
        } catch (Exception $e) {
            $this->db->query("UPDATE dbo.RespaldoLog SET estado = 'ERROR', fin = GETDATE(), mensaje = ? WHERE id = ?", array(substr($e->getMessage(), 0, 4000), $id));
            return array('exito' => false, 'mensaje' => 'El respaldo del código falló: ' . $e->getMessage());
        } finally {
            if (is_file($zipRuta)) @unlink($zipRuta);
        }
    }

    /** Borra los ZIP de código más viejos que los días a guardar. */
    private function limpiarCodigo(){
        $r = $this->db->query(
            "DECLARE @dias INT = (SELECT retencionDias FROM dbo.RespaldoConfig WHERE tipo = 'CODIGO');
             DECLARE @b TABLE (id INT); SET NOCOUNT ON;
             DELETE a OUTPUT DELETED.idRespaldo INTO @b
               FROM NominasRespaldos.dbo.ArchivoCodigo a
              WHERE a.fecha < DATEADD(DAY, -ISNULL(@dias, 15), GETDATE());
             UPDATE l SET estado = 'BORRADO' FROM dbo.RespaldoLog l JOIN @b b ON b.id = l.id;
             SELECT COUNT(*) AS n FROM @b")->row();
        return $r ? (int) $r->n : 0;
    }

    /** Lo usa la tarea programada: true si está activo, ya pasó la hora de hoy y hoy no se ha hecho. */
    public function tocaCodigo(){
        $r = $this->db->query(
            "SELECT CASE WHEN c.activo = 1 AND CONVERT(CHAR(5), GETDATE(), 108) >= ISNULL(c.hora, '22:00')
                          AND NOT EXISTS (SELECT 1 FROM dbo.RespaldoLog l WHERE l.tipo = 'CODIGO' AND l.estado IN ('OK', 'EN PROCESO')
                                          AND l.inicio >= CONVERT(DATE, GETDATE()))
                         THEN 1 ELSE 0 END AS toca
             FROM dbo.RespaldoConfig c WHERE c.tipo = 'CODIGO'")->row();
        return $r && (int) $r->toca === 1;
    }

    /**
     * Manda al navegador el ZIP de código guardado. Es grande (decenas de MB): se lee como flujo
     * sin búfer, por pedazos, porque el driver sqlsrv no deja consultas con búfer de más de 50 MB.
     * Regresa false si no existe.
     */
    public function enviarArchivoCodigo($id){
        return $this->enviarArchivo('ArchivoCodigo', $id);
    }

    public function enviarArchivoRostros($id){
        return $this->enviarArchivo('ArchivoRostros', $id);
    }

    /** $tabla: ArchivoCodigo o ArchivoRostros (fijo, nunca viene del usuario). */
    private function enviarArchivo($tabla, $id){
        if (!in_array($tabla, array('ArchivoCodigo', 'ArchivoRostros'), true)) return false;
        $st = sqlsrv_query($this->db->conn_id,
            "SELECT a.nombre, DATALENGTH(a.datos) AS tamano, a.datos
               FROM NominasRespaldos.dbo." . $tabla . " a
               JOIN dbo.RespaldoLog l ON l.id = a.idRespaldo AND l.estado = 'OK'
              WHERE a.idRespaldo = ?", array((int) $id), array('Scrollable' => SQLSRV_CURSOR_FORWARD));
        if ($st === false || !sqlsrv_fetch($st)) return false;
        $nombre = sqlsrv_get_field($st, 0);
        $tamano = sqlsrv_get_field($st, 1);
        $flujo  = sqlsrv_get_field($st, 2, SQLSRV_PHPTYPE_STREAM(SQLSRV_ENC_BINARY));
        while (ob_get_level()) ob_end_clean();
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $nombre . '"');
        header('Content-Length: ' . $tamano);
        while (!feof($flujo)) { echo fread($flujo, 1048576); flush(); }
        fclose($flujo);
        sqlsrv_free_stmt($st);
        return true;
    }

    // ---------------- Rostros de los relojes (application/sql/respaldo_rostros.sql) ----------------

    /** Relojes que se respaldan (deviceId => descripción), de ConfigSistema 'relojes_respaldo_rostros'. */
    private function relojesRostros(){
        $r = $this->db->query("SELECT valor FROM dbo.ConfigSistema WHERE clave = 'relojes_respaldo_rostros'")->row_array();
        $ids = array_filter(array_map(function ($x) { return preg_replace('/\D/', '', $x); },
                                      explode(',', $r && trim((string) $r['valor']) !== '' ? $r['valor'] : '542189366,542389460')));
        $out = array();
        foreach ($this->db->query("SELECT CONVERT(VARCHAR(20), deviceId) AS deviceId, descripcion FROM dbo.RelojDispositivo
                                   WHERE sdk = 'BS2' AND activo = 1")->result_array() as $d) {
            if (in_array($d['deviceId'], $ids, true)) $out[$d['deviceId']] = $d['descripcion'];
        }
        return $out;
    }

    private function rostrosEnProceso(){
        $r = $this->db->query("SELECT COUNT(*) AS n FROM dbo.RespaldoLog WHERE tipo = 'ROSTROS' AND estado = 'EN PROCESO'
                               AND inicio >= DATEADD(HOUR, -3, GETDATE())")->row();
        return $r && (int) $r->n > 0;
    }

    /** "Respaldar ahora" desde la web: tarda varios minutos por reloj, así que corre en segundo plano. */
    private function lanzarRostros($origen){
        if ($this->rostrosEnProceso()) return array('exito' => false, 'mensaje' => 'Ya se está haciendo un respaldo de rostros; espera a que termine.');
        $id = $this->nuevoLogRostros($origen);
        $php = PHP_BINDIR . DIRECTORY_SEPARATOR . 'php.exe';
        if (!is_file($php)) $php = 'C:\\xampp\\php\\php.exe';
        $cmd = 'start "" /B "' . $php . '" "' . str_replace('/', '\\', FCPATH) . 'index.php" controllerRespaldos rostrosProgramado ' . $id;
        $h = popen($cmd, 'r');
        if ($h !== false) pclose($h);
        return array('exito' => true, 'mensaje' => 'Se empezó el respaldo de rostros. Tarda unos minutos por reloj; aparece en el historial cuando termine.');
    }

    private function nuevoLogRostros($origen){
        return (int) $this->db->query(
            "DECLARE @t TABLE (id INT); SET NOCOUNT ON;
             INSERT INTO dbo.RespaldoLog (tipo, estado, origen) OUTPUT INSERTED.id INTO @t VALUES ('ROSTROS', 'EN PROCESO', ?);
             SELECT id FROM @t", array(substr($origen, 0, 60)))->row()->id;
    }

    /**
     * Toca si está activo y no hay respaldo (correcto o en proceso) desde el último domingo a la hora
     * configurada. El domingo se hace a esa hora; si se perdió (equipo apagado, reloj sin red) se repone
     * de noche (19:00 a 05:00) para no ocupar el reloj mientras la gente checa.
     */
    public function tocaRostros(){
        $c = $this->db->query("SELECT activo, hora FROM dbo.RespaldoConfig WHERE tipo = 'ROSTROS'")->row_array();
        if (!$c || !(int) $c['activo']) return false;
        $tz = new DateTimeZone('America/Mexico_City');   // php.ini trae Europe/Berlin
        $ahora = new DateTime('now', $tz);
        $hora = preg_match('/^(\d{2}):(\d{2})/', (string) $c['hora'], $m) ? array((int) $m[1], (int) $m[2]) : array(20, 0);
        $turno = clone $ahora;
        $turno->modify('-' . (int) $ahora->format('w') . ' days')->setTime($hora[0], $hora[1]);   // domingo de esta semana
        if ($turno > $ahora) $turno->modify('-7 days');
        $r = $this->db->query("SELECT COUNT(*) AS n FROM dbo.RespaldoLog WHERE tipo = 'ROSTROS' AND estado IN ('OK', 'EN PROCESO') AND inicio >= ?",
                              array($turno->format('Y-m-d H:i:s')))->row();
        if ($r && (int) $r->n > 0) return false;
        $h = (int) $ahora->format('G');
        return $ahora->format('Y-m-d') === $turno->format('Y-m-d') || $h >= 19 || $h < 5;
    }

    /** Corre RelojSync (en su carpeta, por las DLL del SDK) y regresa el JSON= o el ERROR=. */
    private function relojSync(array $args){
        $exe = FCPATH . 'tools/RelojSync/publish/RelojSync.exe';
        if (!is_file($exe)) return array('error' => 'No está instalado RelojSync.exe.');
        $cmd = '"' . str_replace('/', '\\', $exe) . '"';
        foreach ($args as $a) $cmd .= ' "' . str_replace('"', '', (string) $a) . '"';
        $p = proc_open($cmd, array(0 => array('pipe', 'r'), 1 => array('pipe', 'w'), 2 => array('pipe', 'w')), $pipes,
                       str_replace('/', '\\', dirname($exe)), null, array('bypass_shell' => true));
        if (!is_resource($p)) return array('error' => 'No se pudo lanzar RelojSync.');
        fclose($pipes[0]);
        $salida = stream_get_contents($pipes[1]);
        $err = stream_get_contents($pipes[2]);
        fclose($pipes[1]); fclose($pipes[2]);
        proc_close($p);
        if (preg_match('/^JSON=(.*)$/m', $salida, $m)) return array('json' => json_decode(trim($m[1]), true));
        if (preg_match('/^ERROR=(.*)$/m', $salida, $m)) return array('error' => trim($m[1]));
        return array('error' => trim(substr($salida . ' ' . $err, -500)) ?: 'RelojSync no respondió.');
    }

    /**
     * Lee los rostros de cada reloj configurado, revisa una muestra contra el reloj y guarda un ZIP en
     * NominasRespaldos.dbo.ArchivoRostros (RespaldoLog tipo ROSTROS). Si un reloj falla se sigue con los
     * demás; el respaldo queda OK si al menos uno se pudo leer.
     */
    public function respaldoRostros($origen, $id = 0){
        ini_set('max_execution_time', 0);
        ignore_user_abort(true);
        if (!$id) $id = $this->nuevoLogRostros($origen);
        $sello  = (new DateTime('now', new DateTimeZone('America/Mexico_City')))->format('Ymd_Hi');
        $nombre = 'Rostros_relojes_' . $sello . '.zip';
        $dir = rtrim(sys_get_temp_dir(), '\\/') . DIRECTORY_SEPARATOR . 'rostros_' . $sello . '_' . $id;
        @mkdir($dir, 0777, true);
        $zipRuta = $dir . '.zip';
        $partes = array(); $fallas = array(); $archivos = array(); $usuarios = 0;
        try {
            $relojes = $this->relojesRostros();
            if (!$relojes) throw new Exception('No hay relojes BioStar 2 activos configurados para respaldar (ConfigSistema relojes_respaldo_rostros).');
            foreach ($relojes as $dev => $desc) {
                $base = preg_replace('/[^A-Za-z0-9_-]+/', '_', $desc) . '_' . $dev;
                $bin = $dir . DIRECTORY_SEPARATOR . $base . '.bin';
                $r = $this->relojSync(array('respaldarrostros', $dev, $bin));
                if (isset($r['error'])) { $fallas[] = $desc . ': ' . $r['error']; continue; }
                $x = $r['json'];
                $v = $this->relojSync(array('verificarrostros', $dev, $bin, 20));
                $verif = isset($v['json']) ? ' (revisados contra el reloj: ' . (int) $v['json']['iguales'] . ' de ' . (int) $v['json']['revisados'] . ' iguales)'
                                           : ' (no se pudo revisar contra el reloj: ' . $v['error'] . ')';
                $partes[] = $desc . ': ' . (int) $x['usuarios'] . ' usuarios, ' . (int) $x['conRostro'] . ' con rostro'
                          . (empty($x['conFoto']) ? ' (sin la foto de pantalla: este reloj no la entrega)' : '') . $verif;
                $usuarios += (int) $x['usuarios'];
                $archivos[$base . '.bin'] = $bin;
                $archivos[$base . '.bin.csv'] = $bin . '.csv';
            }
            if (!$archivos) throw new Exception('No se pudo leer ningún reloj. ' . implode(' | ', $fallas));

            $zip = new ZipArchive();
            if ($zip->open($zipRuta, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) throw new Exception('No se pudo crear el ZIP.');
            foreach ($archivos as $n => $ruta) $zip->addFile($ruta, $n);
            $zip->addFromString('LEEME.txt',
                "Respaldo de rostros de los relojes checadores BioStar 2 (" . $sello . ").\r\n\r\n"
              . implode("\r\n", $partes) . "\r\n\r\n"
              . "Cada .bin trae los usuarios del reloj con su rostro, foto, nombre y vigencia; el .csv es la lista para consultarla.\r\n"
              . "Para volver a cargarlos a un reloj (en el equipo que tiene tools\\RelojSync\\publish):\r\n"
              . "  RelojSync.exe restaurarrostros <deviceId del reloj> <archivo.bin>                (solo a quien no tenga rostro)\r\n"
              . "  RelojSync.exe restaurarrostros <deviceId del reloj> <archivo.bin> reemplazar     (a todos)\r\n"
              . "  RelojSync.exe restaurarrostros <deviceId del reloj> <archivo.bin> numeros.json   (solo esos números, [\"1234\",...])\r\n"
              . "Para revisar un respaldo sin escribir nada: RelojSync.exe verificarrostros <deviceId> <archivo.bin> [cuantos]\r\n");
            if (!$zip->close()) throw new Exception('No se pudo cerrar el ZIP.');
            $tamano = filesize($zipRuta);

            $fh = fopen($zipRuta, 'rb');
            $ok = sqlsrv_query($this->db->conn_id,
                "INSERT INTO NominasRespaldos.dbo.ArchivoRostros (idRespaldo, nombre, datos) VALUES (?, ?, ?)",
                array($id, $nombre, array($fh, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STREAM(SQLSRV_ENC_BINARY), SQLSRV_SQLTYPE_VARBINARY('max'))));
            fclose($fh);
            if ($ok === false) {
                $e = sqlsrv_errors();
                throw new Exception('No se pudo guardar el ZIP en NominasRespaldos: ' . ($e ? $e[0]['message'] : ''));
            }
            $borrados = $this->limpiarRostros();
            $mensaje = implode('. ', $partes) . '.' . ($fallas ? ' No se pudo respaldar: ' . implode(' | ', $fallas) : '')
                     . ($borrados ? ' Se borraron ' . $borrados . ' respaldo(s) viejo(s).' : '');
            $this->db->query("UPDATE dbo.RespaldoLog SET estado = 'OK', fin = GETDATE(), archivo = ?, tamanoBytes = ?, registros = ?, mensaje = ? WHERE id = ?",
                array('NominasRespaldos: ' . $nombre, $tamano, $usuarios, substr($mensaje, 0, 4000), $id));
            return array('exito' => true, 'mensaje' => 'Respaldo de rostros terminado. ' . $mensaje);
        } catch (Exception $e) {
            $this->db->query("UPDATE dbo.RespaldoLog SET estado = 'ERROR', fin = GETDATE(), mensaje = ? WHERE id = ?", array(substr($e->getMessage(), 0, 4000), $id));
            return array('exito' => false, 'mensaje' => 'El respaldo de rostros falló: ' . $e->getMessage());
        } finally {
            foreach ((array) glob($dir . DIRECTORY_SEPARATOR . '*') as $f) @unlink($f);
            @rmdir($dir);
            if (is_file($zipRuta)) @unlink($zipRuta);
        }
    }

    /** Borra los ZIP de rostros más viejos que los días a guardar. */
    private function limpiarRostros(){
        $r = $this->db->query(
            "DECLARE @dias INT = (SELECT retencionDias FROM dbo.RespaldoConfig WHERE tipo = 'ROSTROS');
             DECLARE @b TABLE (id INT); SET NOCOUNT ON;
             DELETE a OUTPUT DELETED.idRespaldo INTO @b
               FROM NominasRespaldos.dbo.ArchivoRostros a
              WHERE a.fecha < DATEADD(DAY, -ISNULL(@dias, 56), GETDATE());
             UPDATE l SET estado = 'BORRADO' FROM dbo.RespaldoLog l JOIN @b b ON b.id = l.id;
             SELECT COUNT(*) AS n FROM @b")->row();
        return $r ? (int) $r->n : 0;
    }

    public function archivoChecadas($id){
        $fila = $this->db->query(
            "SELECT a.nombre,
                    CAST('' AS XML).value('xs:base64Binary(sql:column(\"a.datos\"))', 'VARCHAR(MAX)') AS b64
             FROM NominasRespaldos.dbo.ArchivoChecadas a
             JOIN dbo.RespaldoLog l ON l.id = a.idRespaldo AND l.estado = 'OK'
             WHERE a.idRespaldo = ?", array($id))->row_array();
        if (!$fila) return null;
        return array('nombre' => $fila['nombre'], 'datos' => base64_decode($fila['b64']));
    }
}
