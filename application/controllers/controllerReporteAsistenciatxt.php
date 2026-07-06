<?php 

defined('BASEPATH') OR exit('No direct script access allowed'); 
  
class controllerReporteAsistenciatxt extends CI_Controller {
	function __construct() {
        parent::__construct();
        $this->load->database();
    }

    public function index(){
      header('Access-Control-Allow-Origin: *');
      $data = [];
      $this->load->model('ReporteAsistenciatxtModel');
      if (isset($this->session->userdata['login'])) {
        $data['user'] = $this->session->userdata['login']['user'];
                    
        if ($this->session->userdata['login']['exito']) {
          $this->load->view('header', $data);
          $this->load->view('reporteAsistenciatxtView', $data);
          $this->load->view('footer', $data);
        } else {
          $this->load->view('login');
        }
      } else {
        $this->load->view('login');
      }
    }

    public function exportar(){
      header('Access-Control-Allow-Origin: *');
      $NoSemana = $this->input->post('NoSemana');
      $anio = $this->input->post('anio');
      $planta = $this->input->post('planta');
      $Code = $this->input->post('Code');
      $puesto = $this->input->post('puesto');
      $NoEmpleado = $this->input->post('NoEmpleado');
      $this->load->model('ReporteAsistenciatxtModel');
      $data = $this->ReporteAsistenciatxtModel->ExportarArchivo($NoSemana, $anio, $planta, $Code, $puesto, $NoEmpleado);      
    }

    public function consulta(){
      header('Access-Control-Allow-Origin: *');
      $NoSemana = $this->input->post('NoSemana');
      $anio = $this->input->post('anio');
      $planta = $this->input->post('planta');
      $Code = $this->input->post('Code');
      $puesto = $this->input->post('puesto');
      $NoEmpleado = $this->input->post('NoEmpleado');
      $this->load->model('ReporteAsistenciatxtModel');
      $data = $this->ReporteAsistenciatxtModel->consulta($NoSemana, $anio, $planta, $Code, $puesto, $NoEmpleado);
      echo json_encode($data);
    }
}
?>
