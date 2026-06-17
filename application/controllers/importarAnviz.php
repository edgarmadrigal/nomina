<?php 

defined('BASEPATH') OR exit('No direct script access allowed');  
  
class importarAnviz extends CI_Controller {  

  function __construct() {
      parent::__construct();
      $this->load->database();
      $this->load->model('import_model', 'import');
  }
      

  public function index(){
    header('Access-Control-Allow-Origin: *');
		$data = [];
        if (isset($this->session->userdata['login'])) {
			$data['user'] = $this->session->userdata['login']['user'];            			
            if ($this->session->userdata['login']['exito']) {
              $this->load->view('header', $data);
              $this->load->view('uploadANVIZ');
              $this->load->view('footer', $data);
            } else {
                $this->load->view('login');
            }
        } else {
            $this->load->view('login');
		}		
  }
 

  public function uploadData() {
    header('Access-Control-Allow-Origin: *');
    $result =[];
    $result =$this->session->userdata['login']['user'];
    $idPlanta=$result->idPlanta;       

            $path = 'uploads/';
            require_once APPPATH."/third_party/PHPExcel.php";
            $config['upload_path'] = $path;
            $config['allowed_types'] = 'xlsx|xls|csv';
            $config['remove_spaces'] = TRUE;
            $this->load->library('upload', $config);
            $this->upload->initialize($config);            
            if (!$this->upload->do_upload('uploadFile')) {
                $error = array('error' => $this->upload->display_errors());
            } else {
                $data = array('upload_data' => $this->upload->data());
            }
            if(empty($error)){
              if (!empty($data['upload_data']['file_name'])) {
                $import_xls_file = $data['upload_data']['file_name'];
            } else {
                $import_xls_file = 0;
            }
            $inputFileName = $path . $import_xls_file;            
            try {
                $inputFileType = PHPExcel_IOFactory::identify($inputFileName);
                $objReader = PHPExcel_IOFactory::createReader($inputFileType);
                $objPHPExcel = $objReader->load($inputFileName);
                $allDataInSheet = $objPHPExcel->getActiveSheet()->toArray(null, true, true, true);
                $i=0;
                $bandera=true;
                $inserdata=[];
                $result=[];
                foreach ($allDataInSheet as $value) {
                  if($bandera){
                    $bandera=false;
                  }
                  else{
                          /* ANVIZ */
                              $usuarioid= $value['A'];
                              $fecha= trim($value['C']);  
                              $hora= trim($value['D']);
                              $hora2= trim($value['E']);
                              $fechahora=$fecha.' '.$hora;                              
                              $dia_id=date("w",strtotime($fecha)); 
                                                            
                              $inserdata[$i]['fecha'] =$fecha;
                              $inserdata[$i]['fechahora'] =$fechahora;
                              $inserdata[$i]['usuarioid'] = $usuarioid;
                              $inserdata[$i]['hora'] = $hora;
                              $inserdata[$i]['dia_id'] = $dia_id;
                              $inserdata[$i]['idPlanta'] =  $idPlanta;                    
                              $i++;
                              if($hora2==''){

                              }else{
                                  $fechahora=$fecha.' '.$hora2;     
                                  $inserdata[$i]['fecha'] =$fecha;
                                  $inserdata[$i]['fechahora'] =$fechahora;
                                  $inserdata[$i]['usuarioid'] = $usuarioid;
                                  $inserdata[$i]['hora'] = $hora2;
                                  $inserdata[$i]['dia_id'] = $dia_id;
                                  $inserdata[$i]['idPlanta'] =  $idPlanta;                    
                                  $i++;
                              }
                  }
                }
                //print_r($inserdata );
                //die();
                if  ($inserdata){
                    $sinduplicados = array_map("unserialize", array_unique(array_map("serialize",$inserdata)));
                    $result = $this->import->importdata($sinduplicados);
                }
                if($result){
                  echo "importacion Exitosa!";
                }else{
                  //print_r($result);
                  echo "Esas checadas ya existen o el archivo no tiene informacion!";
                }
          } 
          catch (Exception $e) {
               print_r('Error loading file "' . pathinfo($inputFileName, PATHINFO_BASENAME). '": ' .$e->getMessage());
               die();
          }
          }else{
            print_r('Error');
              //echo $error['error'];
          }
  }

}
?>