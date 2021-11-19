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
                    if($value['G'] =='1:N authentication succeeded (Face)' 
                    || $value['G'] =='1:N Autenticaciￃﾳn exitosa (Rostro)' 
                    || $value['G'] =='1:N Autenticaci??n exitosa (Rostro)' 
                     || $value['G'] =='1:N Autenticación exitosa (Rostro)'   ) {
                      
                     
                      
                      /* NUEVO METODO PARA SACAR LA FECHA */
                          $x= trim($value['A']);
                          $x = date_create_from_format('d/m/Y H:i',$x );
                          $date =  $x->getTimestamp();                       
                          $fechahora= date("Y-m-d H:i",$date );       

                          $fecha_actual = strtotime("2021-01-01 00:00");
                          $fecha_entrada =  $fechahora;

                          if($fecha_actual > $fecha_entrada)
                          {
                        //      echo "La fecha actual es mayor a la comparada.".$fecha_entrada;                                                             
                              $fecha= date("Y-m-d",$date );                
                              $hora= date("H:i",$date );
                          }else
                            {
                              $fecha=  date("Y-m-d H:i",strtotime($value['A']));  
                              $fechahora= date("Y-m-d",strtotime($a));                           
                              $hora= date("H:i",strtotime($a));    
                          // echo "La fecha comparada es igual o menor".$fecha;
                        }

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
                      if($value['F'] =='1:N Autenticaciￃﾳn exitosa (Rostro)' 
                      ||  $value['F'] =='1:N authentication succeeded (Face)' 
                      || $value['F']  =='1:N authentication succeeded (Face)' 
                      || $value['F'] =='1:N Autenticaci??n exitosa (Rostro)' 
                      || $value['F'] =='1:N Autenticación exitosa (Rostro)'      ){


                      
                      /* NUEVO METODO PARA SACAR LA FECHA */
                      
                      try{
                        $fecha= date("Y-m-d",strtotime($value['A']));
              
                        $fechahora= date("Y-m-d H:i",strtotime($value['A']));
                        
                        $hora=  date("H:i",strtotime($value['A']));



                        //echo 'Excepción capturada: ',  $e->getMessage(), "\n";
                       /* $fecha=  date("Y-m-d H:i",strtotime($value['A']));  
                        $fechahora= date("Y-m-d",strtotime($a));                           
                        $hora= date("H:i",strtotime($a));    */

                        $fecha_entrada =  $fechahora;
                      }catch (Exception $e) {
                        
                        
                        $x= trim($value['A']);
                        $x = date_create_from_format('d/m/Y H:i',$x );
                        $date =  $x->getTimestamp();                       
                        $fechahora= date("Y-m-d H:i",$date );  
                        $fecha_entrada =  $fechahora;     
                     }


                      /*echo $fechahora;
                      die();
                      */
  
                        $fecha_actual = '2021-01-01 00:00';

                        //echo 'fecha_actual:'.$fecha_actual.' fecha_entrada:'.$fecha_entrada;
                      //die();

                      if($fecha_actual > $fecha_entrada)
                      {
                    //      echo "La fecha actual es mayor a la comparada.".$fecha_entrada;   
                    
                          $x= trim($value['A']);
                          $x = date_create_from_format('d/m/Y H:i',$x );
                          $date =  $x->getTimestamp();                       
                          $fechahora= date("Y-m-d H:i",$date );  
                          $fecha_entrada =  $fechahora;   
                          $fecha= date("Y-m-d",$date );                  
                          $hora= date("H:i",$date );
                      }else
                        {
                         /*$fecha= date("Y-m-d",$date );                
                          $hora= date("H:i",$date );   */                      
                          
                          //$fecha= date("Y-m-d",strtotime($value['A']));
              
                          //$fechahora= date("Y-m-d H:i",strtotime($value['A']));
                          
                          //$hora= date("H:i",strtotime($value['A']));
                       // echo "La fecha comparada es igual o menor".$fecha;
		                }

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
                      $datetime1 = date('Y-m-d');
                      $datetime1 =strtotime ( $datetime1 ) ;
                      $datetime1 = date_create(date ( 'Y-m-d' ));
                      $datetime2 = date_create($fechahora); //fecha de db  
                      $interval = date_diff($datetime1, $datetime2,false);  
                      $dias = intval($interval->format('%R%a'));  
///*/validar que sea antes de hoy////*/*/*/*/*/*/********************************************************************************************************************** */
/************************************************************************************************************************************************************************** */
                      if ($dias <= 0) {
                        $result=true;
                      }else if ($dias > 0) {
                        $result=false;
                      }
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
                if  ($inserdata && $result){
                    $sinduplicados = array_map("unserialize", array_unique(array_map("serialize",$inserdata)));
                    $result = $this->import->importdata($sinduplicados);

                   //$result = $this->import->importdata($inserdata);
                   
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