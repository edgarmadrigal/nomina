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

class asistencias extends CI_Controller {
  	function __construct() {
        parent::__construct();
        $this->load->database();
        $this->load->model('import_model', 'import');
        $this->load->model('Asistencia');
    }

    public function index(){          
      header('Access-Control-Allow-Origin: *');
      $data = [];
      //$this->load->model('Asistencia');
          if (isset($this->session->userdata['login'])) {
        $data['user'] = $this->session->userdata['login']['user'];
              //$data['checadas'] =json_encode( $this->Checada->consulta(NULL,NULL,NULL,NULL));
                    
              if ($this->session->userdata['login']['exito']) {

                $this->load->view('header', $data);
                $this->load->view('asistencia', $data);
                $this->load->view('footer', $data);
              } else {
                  $this->load->view('login');
            }
          } else {
              $this->load->view('login');
      }
    }
    
    public function actualizarTodo(){        
      
        header('Access-Control-Allow-Origin: *');
        $NoSemana=$this->input->post('NoSemana');
        if (empty($NoSemana)){
          $NoSemana=intval(date("W")); 
        }
        $this->load->model('Asistencia');
        $data = $this->Asistencia->actualizaTabla($NoSemana);
        echo json_encode($data);
    }
    
    public function actualizar(){      
      header('Access-Control-Allow-Origin: *');
      $NoSemana=$this->input->post('NoSemana');
      $anio=$this->input->post('anio');
      $planta=$this->input->post('planta');
      $this->load->model('Asistencia');
      $data = $this->Asistencia->actualizaPlanta($NoSemana,$anio,$planta);
      echo json_encode($data);    
    }
    
    public function borrar(){      
      header('Access-Control-Allow-Origin: *');
      $NoSemana=$this->input->post('NoSemana');
      $anio=$this->input->post('anio');
      $planta=$this->input->post('planta');
      $this->load->model('Asistencia');
      $data = $this->Asistencia->borrar($NoSemana,$anio,$planta);
      echo json_encode($data);    
      /*
      * Cuando cargamos una librería
      * es similar a hacer en PHP puro esto:
      * require_once("libreria.php");
      * $lib=new Libreria();
      */
    }

    public function reporteAsistencia(){
      
      header('Access-Control-Allow-Origin: *'); 
       
      $NoSemana=$this->input->get('NoSemana');
      $anio=$this->input->get('anio');
      $planta=$this->input->get('planta');
      $departamento=$this->input->get('departamento');
      $puesto=$this->input->get('puesto');
      $noempleado=$this->input->get('noempleado');
      // echo $NoSemana.$anio.$departamento.$noempleado;
      if (empty($NoSemana)){
        $NoSemana=null;
       }if (empty($anio)){
        $anio=null;
       }if (empty($noempleado)){
        $noempleado=null;
       }if (empty($planta)){
        $planta=null;
       }
       if (empty($departamento)){
        $departamento=NULL;
       }
       if (empty($planta)){
        $planta=NULL;
       }
       if (empty($puesto)){
        $puesto=NULL;
       }
      $this->load->model('Asistencia');


      //$data = array('data' => array()); 

      $data =$this->Asistencia->consulta($NoSemana,$anio,$planta,$departamento,$puesto,$noempleado);
 
      //$data=print_r(json_encode($data));
      /*;
      die();*/
      $dompdf = new Dompdf(array('isPhpEnabled' => true));      
      $titulo='Reporte de Asistencia';
      $fechaactual = getdate();
      $fechaactual="Fecha de Impresion: $fechaactual[mday] / $fechaactual[mon] / $fechaactual[year]";
      $NoSemana=$data[0]['NoSemana'];
      
      $Numerodepartamento='50';
      $Nombredepartamento='ADMINISTRACIóN';
      
      $NumeroEmpleado='96358';
      $NombreEmpleado='EDGAR';
      $sumaRetardos=0;
      $sumaFaltas=0;
      $sumaTiempoExtra= new DateTime('00:00:00');
      $total=new DateTime('00:00:00');
      
      $dt = new DateTime('00:00:00');
      $horas='0';
      $segundos='0';
      $minutos='0';

      $TotalFaltas=0;
      $TotalRetardos=0;
      $TotalTiempoExtra= new DateTime('00:00:00');
      $TotalEmpleados=0;

      ob_start();      
      require (dirname(__DIR__, 1)."/views/ReporteAsistencia.php");
      $html = ob_get_contents();//$this->output->get_output();
      ob_get_clean();
      
      $dompdf->loadHtml(utf8_decode($html));

      // (Optional) Setup the paper size and orientation
      $dompdf->setPaper('A4', 'portrait');

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
      $dompdf->stream("ReporteAsistencia.pdf", array("Attachment"=>false));
       

    }
  }
?>
