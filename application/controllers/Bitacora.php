<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Catálogos > Historial de cambios: panel del administrador con los últimos cambios en
 * tiempo real y el historial de altas, modificaciones y eliminaciones (dbo.Bitacora).
 * Lo ve el administrador o quien tenga la opción en sus permisos.
 */
class Bitacora extends CI_Controller {

    function __construct() {
        parent::__construct();
        $this->load->model('BitacoraModel');
    }

    public function index(){
        $data = array('user' => $this->session->userdata['login']['user'], 'catalogos' => BitacoraModel::$catalogos);
        $this->load->view('header', $data);
        $this->load->view('bitacoraView', $data);
        $this->load->view('footer', $data);
    }

    /** Lo nuevo desde el último id que tiene la pantalla (cada pocos segundos). */
    public function recientes(){
        if (!$this->puedeVer()) return;
        $desde = (int) $this->input->post('desde');
        $solo = $this->input->post('soloUsuarios') === '1' || $this->input->post('soloUsuarios') === 'true';
        $this->json(array('exito' => true, 'movimientos' => $this->BitacoraModel->recientes($desde, $desde > 0 ? 100 : 40, $solo),
                          'hoy' => $this->BitacoraModel->hoy()));
    }

    public function historial(){
        if (!$this->puedeVer()) return;
        $mx = new DateTimeZone('America/Mexico_City');   // php.ini trae Europe/Berlin
        $hoy = (new DateTime('now', $mx))->format('Y-m-d');
        $fecha = function ($v, $def) { return preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $v) ? $v : $def; };
        $tabla = (string) $this->input->post('tabla');
        $accion = (string) $this->input->post('accion');
        $f = array(
            'desde'   => $fecha($this->input->post('desde'), (new DateTime('-7 days', $mx))->format('Y-m-d')),
            'hasta'   => $fecha($this->input->post('hasta'), $hoy),
            'tabla'   => isset(BitacoraModel::$catalogos[$tabla]) ? $tabla : '',
            'accion'  => in_array($accion, array('ALTA', 'MODIFICACION', 'ELIMINACION'), true) ? $accion : '',
            'usuario' => substr(trim((string) $this->input->post('usuario')), 0, 100),
            'texto'   => substr(trim((string) $this->input->post('texto')), 0, 100),
            'soloUsuarios' => $this->input->post('soloUsuarios') === '1' || $this->input->post('soloUsuarios') === 'true',
        );
        $this->json(array('exito' => true, 'movimientos' => $this->BitacoraModel->historial($f), 'limite' => 500));
    }

    public function registro(){
        if (!$this->puedeVer()) return;
        $tabla = (string) $this->input->post('tabla');
        if (!isset(BitacoraModel::$catalogos[$tabla])) { $this->json(array('exito' => false, 'mensaje' => 'Catálogo no válido.')); return; }
        $this->json(array('exito' => true, 'movimientos' => $this->BitacoraModel->delRegistro($tabla, (string) $this->input->post('llave'))));
    }

    public function detalle(){
        if (!$this->puedeVer()) return;
        $d = $this->BitacoraModel->detalle((int) $this->input->post('id'));
        $this->json($d ? array('exito' => true, 'movimiento' => $d) : array('exito' => false, 'mensaje' => 'No se encontró el movimiento.'));
    }

    /** Todas las checadas de un evento (un renglón agrupado de "Checadas (cambios a mano)"). */
    public function evento(){
        if (!$this->puedeVer()) return;
        $accion = (string) $this->input->post('accion');
        if (!in_array($accion, array('ALTA', 'MODIFICACION', 'ELIMINACION'), true)) { $this->json(array('exito' => false, 'mensaje' => 'Acción no válida.')); return; }
        $filas = $this->BitacoraModel->evento((int) $this->input->post('desde'), (int) $this->input->post('hasta'),
                                               (string) $this->input->post('usuario'), (string) $this->input->post('pantalla'), $accion);
        $this->json(array('exito' => true, 'checadas' => $filas));
    }

    /**
     * Revierte un cambio (deja el registro como estaba) o lo vuelve a aplicar.
     * Solo el administrador: regresa datos reales de los catálogos.
     * modo = 'revertir' (dejar como 'antes') | 'reaplicar' (dejar como 'despues').
     */
    public function revertir(){
        if (!$this->esAdmin()) return;
        $id = (int) $this->input->post('id');
        $modo = (string) $this->input->post('modo');
        if ($id <= 0) { $this->json(array('exito' => false, 'mensaje' => 'Falta el movimiento.')); return; }
        $this->json($this->BitacoraModel->revertir($id, $modo));
    }

    // ------------------------------------------------------------------

    /** Solo el administrador (idPerfil = 1) revierte cambios. */
    private function esAdmin(){
        $u = isset($this->session->userdata['login']['user']) ? $this->session->userdata['login']['user'] : null;
        if ($u && isset($u->idPerfil) && $u->idPerfil == 1) return true;
        $this->json(array('exito' => false, 'mensaje' => 'Solo el administrador puede revertir o reaplicar cambios.'));
        return false;
    }

    /** El administrador, o quien tenga la opción "Historial de cambios" en sus permisos. */
    private function puedeVer(){
        $u = $this->session->userdata['login']['user'];
        if (isset($u->idPerfil) && $u->idPerfil == 1) return true;
        $r = $this->db->query("SELECT COUNT(*) AS n FROM dbo.UsuarioPermiso p JOIN dbo.MenuOpcion o ON o.id = p.idOpcion
                               WHERE p.idUsuario = ? AND o.url = 'bitacora' AND o.activo = 1", array((int) $u->id))->row_array();
        if ((int) $r['n'] > 0) return true;
        $this->json(array('exito' => false, 'mensaje' => 'No tienes permiso para ver el historial de cambios.'));
        return false;
    }

    private function json($datos){
        $this->output->set_content_type('application/json')->set_output(json_encode($datos, JSON_UNESCAPED_UNICODE));
    }
}
