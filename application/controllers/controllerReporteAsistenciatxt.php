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
    
    public function actualizar(){
      header('Access-Control-Allow-Origin: *');
      $NoSemana    = $this->input->post('NoSemana');
      $anio        = $this->input->post('anio');
      $planta      = $this->input->post('planta');
      $tiempoExtra = $this->input->post('tiempoExtra');

      // Cadena de 7 posiciones L..D; si viene mal, se asume todos los dias
      $diasTiempoExtra = $this->input->post('diasTiempoExtra');
      if (!preg_match('/^[01]{7}$/', (string)$diasTiempoExtra)) {
          $diasTiempoExtra = $tiempoExtra ? '1111111' : '0000000';
      }

      // AJUSTE 2026-11-19: el TXT lleva DIAS_VACACIONES y reemplaza la excepción en Microsip, así que
      // antes de armarlo se traen de Microsip las vacaciones/incapacidades de esa planta (la sincronía
      // automática es cada hora y una vacación recién capturada se perdería). Si Microsip no responde
      // se procesa con lo que ya hay y se avisa.
      ini_set('max_execution_time', 0);
      $sincronizo = true;
      $debug = $this->db->db_debug;
      $this->db->db_debug = false;
      try {
          $u = isset($this->session->userdata['login']['user']) ? $this->session->userdata['login']['user'] : null;
          $origen = substr('TXT asistencia (' . ($u && isset($u->usuario) ? $u->usuario : '') . ')', 0, 60);
          $r = $this->db->query("EXEC dbo.SincronizaIncidenciasMicrosip @empresa_id = ?, @origen = ?", array((int) $planta, $origen));
          if ($r === false) $sincronizo = false;
      } catch (Exception $e) {
          $sincronizo = false;
      }
      $this->db->db_debug = $debug;

      $this->load->model('ReporteAsistenciatxtModel');
      $this->ReporteAsistenciatxtModel->actualizaPlanta($NoSemana, $anio, $planta, $tiempoExtra, $diasTiempoExtra);
      echo json_encode(array('ok' => true, 'sincronizoMicrosip' => $sincronizo));
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
