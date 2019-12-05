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
    
class contrato extends CI_Controller {  
	function __construct() {
        parent::__construct();
        $this->load->database();
        $this->load->model('import_model', 'import');
    }

    public function index(){

       $this->load->model('Contrato');
      header('Access-Control-Allow-Origin: *');
      $dompdf = new Dompdf(array('isPhpEnabled' => true));
      
      $idsalario=$this->input->get('idsalario');
      $idrepresentante=$this->input->get('idrepresentante');
      $idhorario=$this->input->get('idhorario');
      $idempleado=$this->input->get('idempleado');
      $idempresa=$this->input->get('idempresa');
      $iddescanso=$this->input->get('iddescanso');      
      
      // echo $NoSemana.$anio.$departamento.$noempleado;
      if (empty($idsalario)){
        $idsalario=null;
       }if (empty($idrepresentante)){
        $idrepresentante=null;
       }if (empty($idhorario)){
        $idhorario=null;
       }if (empty($idempleado)){
        $idempleado=null;
       }if (empty($idempresa)){
        $idempresa=null;
       }if (empty($iddescanso)){
        $iddescanso=null;
       }

       $data =$this->Contrato->consulta($idsalario,$idrepresentante,$idhorario,$idempleado,$idempresa,$iddescanso);

       print_r(json_encode($data));
       die();
        /*  
      $REPRESENTANTE='Lydia Teresa Anaya Venegas';
      $EDOCIVIL='SOLTERO';
      $NOMBRE='OSCAR GONZALEZ CASTAÑEDA';
      $DIRECCION='CIRCUITO EUCALIPTO #65A COL VILLAS DEL BOSQUE TORREON COAH'; 
      $IMSS='08149240684'; 
      $RFC='MARE910615180'; 
      $CURP='MARE910615HCLYD06';    
      $PUESTO='DESARROLLADOR DE SOFTWARE';  
      $FECHAANTIGUEDAD='16/10/2017';
      */
      
      $fechaactual = getdate();
      $FECHAHOY=" $fechaactual[mday] / $fechaactual[mon] / $fechaactual[year]";
      
      ob_start();      
      require (dirname(__DIR__, 1)."/views/contrato.php");
      $html = ob_get_contents();//$this->output->get_output();
      ob_get_clean();      
      $dompdf->loadHtml($html);
      // (Optional) Setup the paper size and orientation
      $dompdf->setPaper('Letter', 'portrait');
      // Render the HTML as PDF
      $dompdf->render();      
      // Parameters
      $x          = 505; 
      $y          = 790;
      $text       = "Página {PAGE_NUM} de {PAGE_COUNT}";     
      $font       = $dompdf->getFontMetrics()->get_font('Helvetica', 'normal');   
      $size       = 10;    
      $color      = array(0,0,0);
      $word_space = 0.0;
      $char_space = 0.0;
      $angle      = 0.0;

      $dompdf->getCanvas()->page_text(
        $x, $y, $text, $font, $size, $color, $word_space, $char_space, $angle
      );      
      // Output the generated PDF to Browser
      $dompdf->stream("contrato.pdf", array("Attachment"=>false));
       
  }
}
?>
