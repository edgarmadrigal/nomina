<?php 

defined('BASEPATH') OR exit('No direct script access allowed'); 
# Cargamos la librería dompdf.
require_once 'dompdfOtro/lib/html5lib/Parser.php';
require_once 'dompdfOtro/lib/php-font-lib/src/FontLib/Autoloader.php';
require_once 'dompdfOtro/lib/php-svg-lib/src/autoload.php';
require_once 'dompdfOtro/src/Autoloader.php';
require_once 'dompdfOtro/src/FontMetrics.php';
Dompdf\Autoloader::register();
use Dompdf\Dompdf;
  
// reference the Dompdf namespace    
    
class controller137 extends CI_Controller {
	function __construct() {
        parent::__construct();
        $this->load->database();
  }

    public function index(){
      header('Access-Control-Allow-Origin: *');
      $data = [];
      $this->load->model('costo137Model');
          if (isset($this->session->userdata['login'])) {
        $data['user'] = $this->session->userdata['login']['user'];
                    
              if ($this->session->userdata['login']['exito']) {
                $this->load->view('header', $data);
                $this->load->view('costo137View', $data);
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
      $fechaInicio = $this->input->post('fechaInicio');
      $fechaFin = $this->input->post('fechaFin');
      $empresa_id = $this->input->post('empresa_id');
      $result = array('data' => array());
      $this->load->model('costo137Model');
      $data=$this->costo137Model->ExportarArchivo($fechaInicio,$fechaFin,$empresa_id);      
       //echo json_encode($data);

      // scandir($data);
     // echo json_encode($data);
    }

      public function consulta(){
        header('Access-Control-Allow-Origin: *');
        $fechaInicio = $this->input->post('fechaInicio');
        $fechaFin = $this->input->post('fechaFin');
        $result = array('data' => array());
        $this->load->model('costo137Model');
        
        
        
          $data=$this->costo137Model->consulta($fechaInicio,$fechaFin,$empresa_id);

		     echo json_encode($data);
    }
    
}
?>
