<?php 
defined('BASEPATH') OR exit('No direct script access allowed');  
/*require_once 'dompdf/lib/html5lib/Parser.php';
require_once 'dompdf/lib/php-font-lib/src/FontLib/Autoloader.php';
require_once 'dompdf/lib/php-svg-lib/src/autoload.php';
require_once 'dompdf/src/Autoloader.php';
Dompdf\Autoloader::register();*/
  
use Dompdf\Dompdf;
// reference the Dompdf namespace    
    
class contrato extends CI_Controller {  
	function __construct() {
        parent::__construct();
        $this->load->database();
        $this->load->model('import_model', 'import');
    }

    public function index(){
    header('Access-Control-Allow-Origin: *');
     $this->load->view('ReporteAsistencia');
      
      // Get output html
      $html = $this->output->get_output();
      
      // Load pdf library
      $this->load->library('pdf');
      
      // Load HTML content
      $this->dompdf->loadHtml($html);
      
      // (Optional) Setup the paper size and orientation
      $this->dompdf->setPaper('Letter', 'landscape');
      
      // Render the HTML as PDF
      $this->dompdf->render();
      
      // Output the generated PDF (1 = download and 0 = preview)
      $this->dompdf->stream("welcome.pdf", array("Attachment"=>0));
  }
}
?>
