<?php 

defined('BASEPATH') OR exit('No direct script access allowed');  
  
class Importar extends CI_Controller {  

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
              $this->load->view('upload');
              $this->load->view('footer', $data);
            } else {
                $this->load->view('login');
            }
        } else {
            $this->load->view('login');
		}		
  }
 

  public function uploadData() {
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
                $flag = true;
                $i=0;
                $inserdata=[];
                $result=[];
                $a=null;
                $a1=null;
                $a2=null;
                $a3=null;
                $a4=null;
                $a5=null;
                foreach ($allDataInSheet as $value) {
                  if($flag){
                    $flag =false;
                    continue;
                  }               
                   $continua = isset($value['G']);
                   $continua2 = isset($value['F']);
                    if($continua){
                    if($value['G'] =='1:N authentication succeeded (Face)' || $value['G'] =='1:N Autenticaciￃﾳn exitosa (Rostro)' ) {

                      
                      $a= date("Y-m-d H:i",strtotime($value['A']));
                      
                      $fecha2= date("Y-m-d",strtotime($a));
                      
                      $hora2= date("H:i",strtotime($a));

                       $valida=true;
                      

                      $completo=explode('(',$value['F']);
                      $usuarioid=$completo[0];

                          if($valida==true){ //no existe en la base de datos
                              $inserdata[$i]['fechahora'] =$a;
                              $inserdata[$i]['usuarioid'] = $value['F'];
                              $i++;
                          }
                      }
                    }
                    else if($continua2){
                    if($value['F'] =='1:N Autenticaciￃﾳn exitosa (Rostro)' ||  $value['F'] =='1:N authentication succeeded (Face)')   {



                      /* NUEVO METODO PARA SACAR LA FECHA */

                      $fecha= date("Y-m-d",strtotime($value['A']));
              
                      $fechahora= date("Y-m-d H:i",strtotime($value['A']));
                      
                      $hora= date("H:i",strtotime($value['A']));
                      

                      $completo=explode('(',$value['E']);
                      $usuarioid=$completo[0];
                      $valida=true;   
                      $dia_id=date("w",strtotime($fecha)); 
/*
                      $x= trim($value['A']);
                      $x = date_create_from_format('d/m/Y H:i',$x );
                      $date =  $x->getTimestamp();
                         
                      $fecha= date("Y-m-d",$date );
                      
                      $fechahora= date("Y-m-d H:i",$date );
                      
                      $hora= date("H:i",$date );


                      $completo=explode('(',$value['E']);
                      $usuarioid=$completo[0];
                      $valida=true;

                      $dia_id=date("w",strtotime($fecha)); 
                      

*/
                      
                      if($dia_id==0){
                        $dia_id=7;
                      }

                         // if($valida==true){ //no existe en la base de datos
                              $inserdata[$i]['fecha'] =$fecha;
                              $inserdata[$i]['fechahora'] =$fechahora;
                              $inserdata[$i]['usuarioid'] = $usuarioid;
                              $inserdata[$i]['hora'] = $hora;
                              $inserdata[$i]['dia_id'] = $dia_id;
                              $i++;
                          //}
                      }
                    }
                }
                if  ($inserdata){
                    $sinduplicados = array_map("unserialize", array_unique(array_map("serialize",$inserdata)));
                    $result = $this->import->importdata($sinduplicados);
                   $result = $this->import->importdata($inserdata);
                   
                }
                if($result){
                  echo "importacion Exitosa!";
                }else{
                  echo "Esas checadas ya existen o el archivo no tiene informacion!";
                } 
          } 
          catch (Exception $e) {
               print_r('Error loading file "' . pathinfo($inputFileName, PATHINFO_BASENAME). '": ' .$e->getMessage());
               die();
          }
          }else{
              echo $error['error'];
          }
  }

  public function subirChecadasTemperatura(){
           
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
          $flag = true;
          $i=0;
          $inserdata=[];
          $result=[];
          $a=null;
          $a1=null;
          $a2=null;
          $a3=null;
          $a4=null;
          $a5=null;
          foreach ($allDataInSheet as $value) {
          if($flag){
            $flag =false;
            continue;
          } 
          if($continua2){
            if($value['F'] =='1:N Autenticaciￃﾳn exitosa (Rostro)'){





              
                      /* NUEVO METODO PARA SACAR LA FECHA */
                      $x= trim($value['A']);
                      $x = date_create_from_format('d/m/Y H:i',$x );
                      $date =  $x->getTimestamp();
                         
                      $fecha= date("Y-m-d",$date );
                      
                      $fechahora= date("Y-m-d H:i",$date );
                      
                      $hora= date("H:i",$date );


                      $completo=explode('(',$value['E']);
                      $usuarioid=$completo[0];
                      $valida=true;

                      $dia_id=date("w",strtotime($fecha)); 






/*
metodo anterior
              $fecha= date("Y-m-d",strtotime($value['A']));
              
              $fechahora= date("Y-m-d H:i",strtotime($value['A']));
              
              $hora= date("H:i",strtotime($value['A']));
              

              $completo=explode('(',$value['E']);
              $usuarioid=$completo[0];
              $valida=true;   
              $dia_id=date("w",strtotime($fecha)); 

*/
       if($dia_id==0){
                    $dia_id=7;
                  }
                  if($valida==true){ //no existe en la base de datos
                      $inserdata[$i]['fecha'] =$fecha;
                      $inserdata[$i]['fechahora'] =$fechahora;
                      $inserdata[$i]['usuarioid'] = $usuarioid;
                      $inserdata[$i]['hora'] = $hora;
                      $inserdata[$i]['dia_id'] = $dia_id;
                      $i++;
                  }
              }
            }
        }
        if  ($inserdata){
            $sinduplicados = array_map("unserialize", array_unique(array_map("serialize",$inserdata)));
            $result = $this->import->importdata($sinduplicados);
           $result = $this->import->importdata($inserdata);
           
        }
        if($result){
          echo "importacion Exitosa!";
        }else{
          echo "Esas checadas ya existen o el archivo no tiene informacion!";
        } 
    }
    catch (Exception $e) {
       print_r('Error loading file "' . pathinfo($inputFileName, PATHINFO_BASENAME). '": ' .$e->getMessage());
       die();
    }
    }
    else{
      echo $error['error'];
    }
  }
}
?>