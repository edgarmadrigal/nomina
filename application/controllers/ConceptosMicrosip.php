<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Catálogos > Conceptos Microsip (ver application/sql/microsip_conceptos.sql).
 * Conceptos de nómina de cada empresa de Microsip, copiados cada hora por el job
 * "Nominas - Conceptos Microsip" o con "Actualizar ahora", y la clave que lleva cada
 * renglón |1.1| del TXT de asistencia (dbo.TxtConceptoClave) para que Microsip lo tome tal cual.
 */
class ConceptosMicrosip extends CI_Controller {

    /** Renglones del TXT que llevan clave de concepto (en el orden en que salen). */
    private $conceptosTxt = array(
        'ASISTENCIA'         => 'Premio por asistencia',
        'PUNTUALIDAD'        => 'Premio por puntualidad',
        'DESCANSO_TRABAJADO' => 'Descanso trabajado (horas)',
        'PRIMA_DOMINICAL'    => 'Prima dominical (importe)',
        'DIA_FESTIVO'        => 'Día festivo (importe)',
    );

    public function index(){
        $data = array('user' => $this->session->userdata['login']['user']);
        $this->load->view('header', $data);
        $this->load->view('conceptosMicrosipView', $data);
        $this->load->view('footer', $data);
    }

    /** Empresas con Microsip que el usuario puede consultar. */
    public function empresas(){
        $filas = $this->db->query(
            "SELECT e.id, e.nombre, me.servidor FROM dbo.MicrosipEmpresa me JOIN dbo.empresas e ON e.id = me.empresa_id
             WHERE me.activo = 1 ORDER BY e.nombre")->result_array();
        $this->load->model('PermisoModel');
        $filas = $this->PermisoModel->filtrarPlantas((int) $this->session->userdata['login']['user']->id, $filas);
        $this->json(array('exito' => true, 'empresas' => $filas, 'puedeEditar' => $this->puedeEditar(false),
                          'ultima' => $this->ultimaSinc(null)));
    }

    /** Conceptos de la empresa y las claves del TXT. */
    public function lista(){
        $empresa = (int) $this->input->post('empresa_id');
        if (!$this->puedePlanta($empresa)) { $this->json(array('exito' => false, 'mensaje' => 'No tienes acceso a esa empresa.')); return; }
        $conceptos = $this->db->query(
            "SELECT conceptoNoId, clave, nombre, nombreAbrev, naturaleza, tipo, oculto, activo,
                    CONVERT(VARCHAR(16), fechaAlta, 120) AS fechaAlta, CONVERT(VARCHAR(16), fechaActualiza, 120) AS fechaActualiza
             FROM dbo.MicrosipConcepto WHERE empresa_id = ?
             ORDER BY naturaleza, clave", array($empresa))->result_array();
        $txt = array();
        foreach ($this->conceptosTxt as $c => $nombre) {
            $r = $this->db->query(
                "SELECT TOP 1 LTRIM(RTRIM(clave)) AS clave, CASE WHEN empresa_id IS NULL THEN 'general' ELSE 'empresa' END AS origen
                 FROM dbo.TxtConceptoClave WHERE concepto = ? AND (empresa_id = ? OR empresa_id IS NULL)
                 ORDER BY CASE WHEN empresa_id IS NULL THEN 1 ELSE 0 END", array($c, $empresa))->row_array();
            $txt[] = array('concepto' => $c, 'nombre' => $nombre, 'clave' => $r ? $r['clave'] : null, 'origen' => $r ? $r['origen'] : null);
        }
        $this->json(array('exito' => true, 'conceptos' => $conceptos, 'txt' => $txt, 'ultima' => $this->ultimaSinc($empresa)));
    }

    /** "Actualizar ahora": trae los conceptos de Microsip (una empresa o todas). */
    public function actualizar(){
        if (!$this->puedeEditar()) return;
        ini_set('max_execution_time', 0);
        $empresa = (int) $this->input->post('empresa_id');
        if ($empresa && !$this->puedePlanta($empresa)) { $this->json(array('exito' => false, 'mensaje' => 'No tienes acceso a esa empresa.')); return; }
        $r = $this->db->query("EXEC dbo.SincronizaConceptosMicrosip @empresa_id = ?, @origen = 'boton'", array($empresa ?: null))->result_array();
        $errores = array_values(array_filter($r, function ($x) { return !empty($x['error']); }));
        $nuevos = array_sum(array_column($r, 'nuevos')); $cambiados = array_sum(array_column($r, 'cambiados')); $bajas = array_sum(array_column($r, 'bajas'));
        $msg = count($r) . ' empresa(s): ' . $nuevos . ' nuevo(s), ' . $cambiados . ' cambiado(s), ' . $bajas . ' ya no existen.';
        if ($errores) $msg .= ' No se pudo leer: ' . implode(', ', array_column($errores, 'servidor')) . '.';
        $this->json(array('exito' => !$errores || count($errores) < count($r), 'mensaje' => $msg));
    }

    /** Clave de Microsip para un renglón del TXT en esa empresa ('' = volver a la general). */
    public function guardarClave(){
        if (!$this->puedeEditar()) return;
        $empresa = (int) $this->input->post('empresa_id');
        $concepto = (string) $this->input->post('concepto');
        $clave = trim((string) $this->input->post('clave'));
        if (!isset($this->conceptosTxt[$concepto]) || !$this->puedePlanta($empresa)) { $this->json(array('exito' => false, 'mensaje' => 'Datos inválidos.')); return; }
        if ($clave === '') {
            $this->db->query("DELETE FROM dbo.TxtConceptoClave WHERE concepto = ? AND empresa_id = ?", array($concepto, $empresa));
            $this->json(array('exito' => true, 'mensaje' => 'Se usará la clave general.'));
            return;
        }
        // el formato de Microsip (Excepciones de la nómina) acepta CLAVE_CONCEPTO de 5 caracteres
        if (strlen($clave) > 5) { $this->json(array('exito' => false, 'mensaje' => 'La clave no puede tener más de 5 caracteres (formato de Microsip).')); return; }
        $existe = $this->db->query("SELECT COUNT(*) AS n FROM dbo.MicrosipConcepto WHERE empresa_id = ? AND clave = ? AND activo = 1",
                                   array($empresa, $clave))->row()->n;
        if (!$existe) { $this->json(array('exito' => false, 'mensaje' => 'La clave ' . $clave . ' no existe en Microsip para esta empresa.')); return; }
        $this->db->query(
            "IF EXISTS (SELECT 1 FROM dbo.TxtConceptoClave WHERE concepto = ? AND empresa_id = ?)
                 UPDATE dbo.TxtConceptoClave SET clave = ? WHERE concepto = ? AND empresa_id = ?
             ELSE
                 INSERT INTO dbo.TxtConceptoClave (concepto, empresa_id, clave) VALUES (?, ?, ?)",
            array($concepto, $empresa, $clave, $concepto, $empresa, $concepto, $empresa, $clave));
        $this->json(array('exito' => true, 'mensaje' => $this->conceptosTxt[$concepto] . ' saldrá con la clave ' . $clave . ' en el TXT.'));
    }

    // ------------------------------------------------------------------

    /** Última sincronización (de la empresa, o de cualquiera) y si tuvo error. */
    private function ultimaSinc($empresa){
        $r = $this->db->query(
            "SELECT TOP 1 CONVERT(VARCHAR(19), fecha, 120) AS fecha, origen, error,
                    DATEDIFF(MINUTE, fecha, GETDATE()) AS minutos
             FROM dbo.MicrosipConceptoSinc WHERE (? IS NULL OR empresa_id = ?) ORDER BY id DESC",
            array($empresa ?: null, $empresa ?: null))->row_array();
        return $r ?: null;
    }

    private function puedePlanta($empresa){
        if (!$empresa) return false;
        $this->load->model('PermisoModel');
        $p = $this->PermisoModel->permisosUsuario((int) $this->session->userdata['login']['user']->id);
        return empty($p['empresas']) || in_array((int) $empresa, $p['empresas'], true);
    }

    /** Cambia claves / actualiza: el administrador o quien tenga la opción en sus permisos. */
    private function puedeEditar($responder = true){
        $u = $this->session->userdata['login']['user'];
        $ok = isset($u->idPerfil) && $u->idPerfil == 1;
        if (!$ok) {
            $r = $this->db->query("SELECT COUNT(*) AS n FROM dbo.UsuarioPermiso p JOIN dbo.MenuOpcion o ON o.id = p.idOpcion
                                   WHERE p.idUsuario = ? AND o.url = 'ConceptosMicrosip' AND o.activo = 1", array((int) $u->id))->row_array();
            $ok = (int) $r['n'] > 0;
        }
        if (!$ok && $responder) $this->json(array('exito' => false, 'mensaje' => 'No tienes permiso para cambiar conceptos de Microsip.'));
        return $ok;
    }

    private function json($datos){
        $this->output->set_content_type('application/json')->set_output(json_encode($datos, JSON_UNESCAPED_UNICODE));
    }
}
