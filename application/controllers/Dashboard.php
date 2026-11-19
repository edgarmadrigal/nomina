<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Datos de las gráficas de Inicio (views/system.php, assets/js/dashboard.js).
 * Solo muestra las plantas que el usuario puede consultar (Usuarios > Permisos).
 */
class Dashboard extends CI_Controller {

    function __construct() {
        parent::__construct();
        $this->load->model('DashboardModel');
        $this->load->model('PermisoModel');
    }

    public function semanas(){
        header('Access-Control-Allow-Origin: *');
        $empresas = $this->empresasUsuario();
        if ($empresas === false) return;
        echo json_encode($this->DashboardModel->semanas($empresas));
    }

    public function resumen(){
        header('Access-Control-Allow-Origin: *');
        $empresas = $this->empresasUsuario();
        if ($empresas === false) return;
        list($anio, $semana, $planta) = $this->filtros();
        if (!$this->plantaPermitida($planta, $empresas)) { echo json_encode(['porDia' => [], 'porPlanta' => []]); return; }
        echo json_encode($this->DashboardModel->resumen($anio, $semana, $planta, $empresas));
    }

    public function detalle(){
        header('Access-Control-Allow-Origin: *');
        $empresas = $this->empresasUsuario();
        if ($empresas === false) return;
        list($anio, $semana, $planta) = $this->filtros();
        if (!$this->plantaPermitida($planta, $empresas)) { echo json_encode([]); return; }
        echo json_encode($this->DashboardModel->detalle(
            $anio, $semana, $planta, (int) $this->input->post('dia'), (string) $this->input->post('tipo'), $empresas));
    }

    // ------------------------------------------------------------------

    private function filtros(){
        $planta = $this->input->post('planta');
        return array((int) $this->input->post('anio'), (int) $this->input->post('semana'),
                     ($planta === null || $planta === '') ? '' : (int) $planta);
    }

    /** Empresas permitidas del usuario (arreglo vacío = todas); false si no hay sesión. */
    private function empresasUsuario(){
        $u = isset($this->session->userdata['login']['user']) ? $this->session->userdata['login']['user'] : null;
        if (!$u || !isset($u->id)) {
            echo json_encode(['exito' => false, 'mensaje' => 'Sesión inválida, vuelva a iniciar sesión.']);
            return false;
        }
        $p = $this->PermisoModel->permisosUsuario($u->id);
        return $p['empresas'];
    }

    private function plantaPermitida($planta, array $empresas){
        return $planta === '' || empty($empresas) || in_array((int) $planta, $empresas, true);
    }
}
