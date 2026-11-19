<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Licencia del sistema (dbo.Licencia, un renglón).
 *
 * - Serie: NOM-XXXX-XXXX a partir de instalacionId + servidor + base de datos.
 * - Cada solicitud genera un solicitudId nuevo y un código por cada opción de tiempo
 *   (y uno para desactivar). Código = HMAC(secreto, serie|solicitud|opción).
 *   Al usar un código la solicitud se borra, así que cada código sirve una sola vez.
 */
class LicenciaModel extends CI_Model {

    function __construct(){
        parent::__construct();
        $this->config->load('licencia', TRUE);
    }

    public function cfg($k){
        return $this->config->item('licencia_' . $k, 'licencia');
    }

    public function fila(){
        $t = $this->db->query("SELECT OBJECT_ID('dbo.Licencia') AS t")->row_array();
        if (empty($t['t'])) return null;
        return $this->db->query(
            "SELECT CONVERT(VARCHAR(36), l.instalacionId) AS instalacionId, l.serieActivada, l.activa, l.indefinida,
                    CONVERT(VARCHAR(19), l.venceEl, 120) AS venceEl, CONVERT(VARCHAR(19), l.activadaEl, 120) AS activadaEl,
                    l.solicitudId, CONVERT(VARCHAR(19), l.solicitudFecha, 120) AS solicitudFecha, l.solicitudPor,
                    CONVERT(VARCHAR(128), SERVERPROPERTY('MachineName')) AS servidor, DB_NAME() AS base,
                    CONVERT(VARCHAR(19), GETDATE(), 120) AS ahora
             FROM dbo.Licencia l WHERE l.id = 1")->row_array();
    }

    public function serie($f){
        $h = strtoupper(hash('sha256', $f['instalacionId'] . '|' . strtoupper($f['servidor']) . '|' . strtoupper($f['base'])));
        return 'NOM-' . substr($h, 0, 4) . '-' . substr($h, 4, 4);
    }

    /** Estado para pantalla y hook: vigente, dias restantes, etc. */
    public function estado(){
        $f = $this->fila();
        if (!$f) return array('vigente' => false, 'requerida' => (bool) $this->cfg('requerida'), 'serie' => '',
                              'mensaje' => 'Falta crear la tabla dbo.Licencia (application/sql/licencia.sql)');
        $serie = $this->serie($f);
        $vigente = (int) $f['activa'] === 1 && $f['serieActivada'] === $serie
                && ((int) $f['indefinida'] === 1 || ($f['venceEl'] && $f['venceEl'] > $f['ahora']));
        $dias = null;
        if ($vigente && (int) $f['indefinida'] !== 1) {
            $dias = (int) floor((strtotime($f['venceEl']) - strtotime($f['ahora'])) / 86400);
        }
        return array(
            'vigente'      => $vigente,
            'requerida'    => (bool) $this->cfg('requerida'),
            'serie'        => $serie,
            'indefinida'   => (int) $f['indefinida'] === 1,
            'venceEl'      => $f['venceEl'],
            'activadaEl'   => $f['activadaEl'],
            'diasRestantes'=> $dias,
            'serieCambio'  => (int) $f['activa'] === 1 && $f['serieActivada'] !== $serie,
            'solicitudFecha' => $f['solicitudFecha'],
            'solicitudPor' => $f['solicitudPor'],
            'servidor'     => $f['servidor'],
            'base'         => $f['base'],
        );
    }

    public function codigo($serie, $solicitud, $opcion){
        $mac = hash_hmac('sha256', $serie . '|' . $solicitud . '|' . $opcion, (string) $this->cfg('secreto'), true);
        // Base32 sin letras confusas (sin 0/O/1/I)
        $alf = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $bits = ''; foreach (str_split(substr($mac, 0, 7)) as $c) $bits .= str_pad(decbin(ord($c)), 8, '0', STR_PAD_LEFT);
        $s = ''; for ($i = 0; $i < 10; $i++) $s .= $alf[bindec(substr($bits, $i * 5, 5))];
        return substr($s, 0, 5) . '-' . substr($s, 5, 5);
    }

    /** Nueva solicitud: regresa serie, solicitud y códigos por opción ('X' = desactivar). */
    public function nuevaSolicitud($usuario){
        $f = $this->fila();
        $serie = $this->serie($f);
        $sol = strtoupper(bin2hex(random_bytes(6)));
        $this->db->query("UPDATE dbo.Licencia SET solicitudId = ?, solicitudFecha = GETDATE(), solicitudPor = ? WHERE id = 1",
                         array($sol, $usuario));
        $codigos = array();
        foreach ($this->cfg('opciones') as $d) $codigos[(string) (int) $d] = $this->codigo($serie, $sol, (int) $d);
        $codigos['X'] = $this->codigo($serie, $sol, 'X');
        $this->movimiento($usuario, 'SOLICITUD', 'Solicitud ' . $sol . ' serie ' . $serie);
        return array('serie' => $serie, 'solicitud' => $sol, 'codigos' => $codigos, 'fila' => $f);
    }

    /** Aplica un código. Regresa [exito, mensaje]. */
    public function aplicar($codigo, $usuario){
        $f = $this->fila();
        $codigo = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $codigo));
        if (strlen($codigo) !== 10 || empty($f['solicitudId'])) {
            $this->movimiento($usuario, 'CODIGO_INVALIDO', $codigo);
            return array(false, empty($f['solicitudId']) ? 'No hay una solicitud pendiente; primero solicita el código.' : 'Código inválido.');
        }
        $codigo = substr($codigo, 0, 5) . '-' . substr($codigo, 5, 5);
        $serie = $this->serie($f);
        $sol = $f['solicitudId'];

        if (hash_equals($this->codigo($serie, $sol, 'X'), $codigo)) {
            $this->db->query("UPDATE dbo.Licencia SET activa = 0, indefinida = 0, venceEl = NULL, solicitudId = NULL WHERE id = 1");
            $this->movimiento($usuario, 'DESACTIVACION', 'Solicitud ' . $sol);
            return array(true, 'Sistema desactivado.');
        }
        foreach ($this->cfg('opciones') as $d) {
            $d = (int) $d;
            if (!hash_equals($this->codigo($serie, $sol, $d), $codigo)) continue;
            if ($d === 0) {
                $this->db->query("UPDATE dbo.Licencia SET activa = 1, indefinida = 1, venceEl = NULL, activadaEl = GETDATE(),
                                         serieActivada = ?, solicitudId = NULL WHERE id = 1", array($serie));
                $txt = 'indefinida';
            } else {
                // Si sigue vigente, los días se suman a partir del vencimiento actual
                $this->db->query("UPDATE dbo.Licencia SET activa = 1, indefinida = 0,
                                         venceEl = DATEADD(DAY, ?, CASE WHEN activa = 1 AND indefinida = 0 AND serieActivada = ? AND venceEl > GETDATE()
                                                                        THEN venceEl ELSE CONVERT(DATE, GETDATE()) END),
                                         activadaEl = GETDATE(), serieActivada = ?, solicitudId = NULL WHERE id = 1",
                                 array($d, $serie, $serie));
                $txt = $d . ' días';
            }
            $this->movimiento($usuario, 'ACTIVACION', 'Solicitud ' . $sol . ', ' . $txt);
            return array(true, 'Licencia activada (' . $txt . ').');
        }
        $this->movimiento($usuario, 'CODIGO_INVALIDO', $codigo);
        return array(false, 'Código inválido.');
    }

    /** Crea las tablas (si faltan) usando la conexión del sistema. */
    public function instalar(){
        $this->db->query("IF OBJECT_ID('dbo.Licencia') IS NULL
            CREATE TABLE dbo.Licencia (
                id INT NOT NULL CONSTRAINT PK_Licencia PRIMARY KEY CONSTRAINT CK_Licencia_Uno CHECK (id = 1),
                instalacionId UNIQUEIDENTIFIER NOT NULL CONSTRAINT DF_Licencia_Inst DEFAULT NEWID(),
                serieActivada VARCHAR(20) NULL,
                activa BIT NOT NULL CONSTRAINT DF_Licencia_Activa DEFAULT 0,
                indefinida BIT NOT NULL CONSTRAINT DF_Licencia_Indef DEFAULT 0,
                venceEl DATETIME NULL,
                activadaEl DATETIME NULL,
                solicitudId VARCHAR(16) NULL,
                solicitudFecha DATETIME NULL,
                solicitudPor VARCHAR(100) NULL)");
        $this->db->query("IF NOT EXISTS (SELECT 1 FROM dbo.Licencia) INSERT INTO dbo.Licencia (id) VALUES (1)");
        $this->db->query("IF OBJECT_ID('dbo.LicenciaMovimiento') IS NULL
            CREATE TABLE dbo.LicenciaMovimiento (
                id INT IDENTITY(1,1) NOT NULL CONSTRAINT PK_LicenciaMovimiento PRIMARY KEY,
                fecha DATETIME NOT NULL CONSTRAINT DF_LicMov_Fecha DEFAULT GETDATE(),
                usuario VARCHAR(100) NULL,
                accion VARCHAR(30) NOT NULL,
                detalle VARCHAR(400) NULL)");
    }

    /** Deja la licencia activa e indefinida sin pasar por el correo. */
    public function activarIndefinida($usuario){
        $f = $this->fila();
        $serie = $this->serie($f);
        $this->db->query("UPDATE dbo.Licencia SET activa = 1, indefinida = 1, venceEl = NULL, activadaEl = GETDATE(),
                                 serieActivada = ?, solicitudId = NULL WHERE id = 1", array($serie));
        $this->movimiento($usuario, 'ACTIVACION', 'Indefinida directa. Serie ' . $serie);
        return $serie;
    }

    public function movimiento($usuario, $accion, $detalle){
        $this->db->query("INSERT INTO dbo.LicenciaMovimiento (usuario, accion, detalle) VALUES (?, ?, ?)",
                         array($usuario, $accion, substr($detalle, 0, 400)));
    }

    public function movimientos(){
        return $this->db->query("SELECT TOP 30 CONVERT(VARCHAR(19), fecha, 120) AS fecha, usuario, accion, detalle
                                 FROM dbo.LicenciaMovimiento ORDER BY id DESC")->result_array();
    }
}
