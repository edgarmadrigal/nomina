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
    
class pensionController extends CI_Controller {
	function __construct() {
        parent::__construct();
        $this->load->database();
  }

    public function index(){
      header('Access-Control-Allow-Origin: *');
      $data = [];
      $this->load->model('pensionModel');
          if (isset($this->session->userdata['login'])) {
        $data['user'] = $this->session->userdata['login']['user'];
                    
              if ($this->session->userdata['login']['exito']) {
                $this->load->view('header', $data);
                $this->load->view('pensionView', $data);
                $this->load->view('footer', $data);
              } else {
                  $this->load->view('login');
            }
          } else {
              $this->load->view('login');
      }
    }
    public function consulta(){
		header('Access-Control-Allow-Origin: *');
		$fechaInicio = $this->input->post('fechaInicio');
		$fechaFin = $this->input->post('fechaFin');
		$result = array('data' => array());
        $this->load->model('pensionModel');
        
        
        
        $data=$this->pensionModel->consulta($fechaInicio,$fechaFin);

        
        
        //$data=$this->pensionModel->consultapension();

		echo json_encode($data);
    }
    
}
?>
