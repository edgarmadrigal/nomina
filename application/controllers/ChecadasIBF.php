<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Reportes > Checadas IBF: el mismo reporte que "Checadas Directo de Checadores y Biostar"
 * (vista ChecadasBiostar + checadaBiostar.js + endpoints de checadasBiostar), fijo a la empresa IBF.
 * IBF es una empresa restringida: solo entra quien tiene el permiso de IBF (el hook EmpresaRestringida
 * además rechaza cualquier consulta de IBF sin ese permiso).
 */
class ChecadasIBF extends CI_Controller {

    public function index(){
        $u = $this->session->userdata['login']['user'];
        $emp = $this->db->query("SELECT id, nombre FROM dbo.empresas WHERE nombre = 'IBF'")->row_array();
        $this->load->model('PermisoModel');
        if (!$emp || !$this->PermisoModel->puedeVerEmpresa($emp['id'])) { show_error('No tienes permiso para ver IBF.', 403); return; }
        $data = array('user' => $u, 'esTorreon' => false, 'soloEmpresa' => array('id' => (int) $emp['id'], 'nombre' => $emp['nombre']));
        $this->load->view('header', $data);
        $this->load->view('ChecadasBiostar', $data);
        $this->load->view('footer', $data);
    }
}
