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
  }

    public function index(){
      header('Access-Control-Allow-Origin: *');
      $data = [];
      $this->load->model('ContratoRH');
          if (isset($this->session->userdata['login'])) {
        $data['user'] = $this->session->userdata['login']['user'];
                    
              if ($this->session->userdata['login']['exito']) {

                $this->load->view('header', $data);
                $this->load->view('contrato', $data);
                $this->load->view('footer', $data);
              } else {
                  $this->load->view('login');
            }
          } else {
              $this->load->view('login');
      }
    }
    
    
    public function ReporteContrato(){
      header('Access-Control-Allow-Origin: *');      
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
       $this->load->model('ContratoRH');

       ///////***COMPROBAR SI EL; EMPLEADO EXISTE */
       $existe=$this->ContratoRH->ConsultaEmpleadoID($idempleado);

       if($existe==0){
         echo 'El empleado no existe o es de otra planta, favor de revisar correctamente la informacion';
       }else
       {        
        try {
            $data =$this->ContratoRH->consulta($idsalario,$idrepresentante,$idhorario,$idempleado,$idempresa,$iddescanso);       
            $dompdf = new Dompdf(array('isPhpEnabled' => true));          
            $REPRESENTANTE=$data[0]['REPRESENTANTE'];
            $EDOCIVIL=$data[0]['EDOCIVIL'];
            $NOMBRE=$data[0]['NOMBRE'];
            $DIRECCION=$data[0]['DIRECCION'];
            $IMSS=$data[0]['IMSS'];
            $RFC=$data[0]['RFC'];
            $CURP=$data[0]['CURP'];
            $PUESTO=$data[0]['PUESTO'];
            $DIRECCIONEMPRESA=$data[0]['DIRECCIONEMPRESA'];
            $HORARIOCONTRATO=$data[0]['HORARIOCONTRATO'];
            $SALARIO=$data[0]['SALARIO'];
            $DESCRIPCIONSALARIO=$data[0]['DESCRIPCIONSALARIO'];
            $DESCANSO=$data[0]['DESCANSO'];
            $FECHAANTIGUEDAD=$data[0]['FECHAANTIGUEDAD'];
            $fechaactual = getdate();
            $FECHAHOY=" $fechaactual[year]-$fechaactual[mon]-$fechaactual[mday] ";
            
            ob_start();      
            require (dirname(__DIR__, 1)."/views/ReporteContrato.php");
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
            $dompdf->stream("ReporteContrato.pdf", array("Attachment"=>false));   
        } catch (Exception $e) {
          echo 'Excepción capturada: ',  $e->getMessage(), "\n";
        }

       }    
    }

    public function consultaSalario(){
          header('Access-Control-Allow-Origin: *');
          $this->load->model('ContratoRH');
          $data = $this->ContratoRH->consultaSalario();
          echo json_encode($data);
    }
    public function consultaRepresentante(){
      header('Access-Control-Allow-Origin: *');
      $this->load->model('ContratoRH');
      $data = $this->ContratoRH->consultaRepresentante();
      echo json_encode($data);
    }

    public function ConsultaHorarioContrato(){
      header('Access-Control-Allow-Origin: *');
      $this->load->model('ContratoRH');
      $data = $this->ContratoRH->ConsultaHorarioContrato();
      echo json_encode($data);
    }

    public function ConsultaDescanso(){
      header('Access-Control-Allow-Origin: *');
      $this->load->model('ContratoRH');
      $data = $this->ContratoRH->ConsultaDescanso();
      echo json_encode($data);
    }
}
?>
