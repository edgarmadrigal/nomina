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
 
  public function uploadData(){
           
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
                    if($value['G'] =='1:N authentication succeeded (Face)'){  
                     
                      $a= date("Y-m-d H:i",strtotime($value['A']));
                      $a1= date("Y-m-d H:i",strtotime ( '-1 minute' ,strtotime($value['A'])));
                      $a2= date("Y-m-d H:i",strtotime ( '-2 minute' ,strtotime($value['A'])));
                      $a3= date("Y-m-d H:i",strtotime ( '-3 minute' ,strtotime($value['A'])));
                      $a4= date("Y-m-d H:i",strtotime ( '-4 minute' ,strtotime($value['A'])));
                      $a5= date("Y-m-d H:i",strtotime ( '-5 minute' ,strtotime($value['A'])));

                      $valida=$this->import->buscarChecadas($a, $value['F']);    

                      $completo=explode('(',$value['F']);
                      $usuarioid=$completo[0];

                      if(count($inserdata)>0){
                        foreach ($inserdata as $item) {
                            if($item['fechahora']==$a1 && $item['usuarioid']==$usuarioid){
                              $valida=false;  
                            }
                            if($item['fechahora']==$a2 && $item['usuarioid']==$usuarioid){
                              $valida=false;  
                            }if($item['fechahora']==$a3 && $item['usuarioid']==$usuarioid){
                              $valida=false;  
                            }if($item['fechahora']==$a4 && $item['usuarioid']==$usuarioid){
                              $valida=false;  
                            }if($item['fechahora']==$a5 && $item['usuarioid']==$usuarioid){
                              $valida=false;  
                            }
                          }
                        }                      
                          if($valida==true){ //no existe en la base de datos
                              $inserdata[$i]['fechahora'] =$a;
                              $inserdata[$i]['usuarioid'] = $value['F'];
                              $i++;
                          }
                      }
                    }
                    else if($continua2){
                    if($value['F'] =='1:N Autenticaciￃﾳn exitosa (Rostro)'){

                      $fecha= date("Y-m-d",strtotime($value['A']));
                      
                      $fechahora= date("Y-m-d H:i",strtotime($value['A']));
                      
                      $hora= date("H:i",strtotime($value['A']));
                      
                      $hora1= date("H:i",strtotime ( '-1 minute' ,strtotime($value['A'])));
                      $hora2= date("H:i",strtotime ( '-2 minute' ,strtotime($value['A'])));
                      $hora3= date("H:i",strtotime ( '-3 minute' ,strtotime($value['A'])));
                      $hora4= date("H:i",strtotime ( '-4 minute' ,strtotime($value['A'])));
                      $hora5= date("H:i",strtotime ( '-5 minute' ,strtotime($value['A'])));
                      

                      $completo=explode('(',$value['E']);
                      $usuarioid=$completo[0];

                      $valida=$this->import->buscarChecadas($fecha,$hora,$usuarioid);

                      if(count($inserdata)>0){
                        foreach ($inserdata as $item) {
                          if($item['fecha']==$fecha   && $item['hora']==$hora1 && $item['usuarioid']==$usuarioid){
                            $valida=false;  
                          }if($item['fecha']==$fecha  && $item['hora']==$hora2 && $item['usuarioid']==$usuarioid){
                            $valida=false;  
                          }if($item['fecha']==$fecha  && $item['hora']==$hora3 && $item['usuarioid']==$usuarioid){
                            $valida=false;  
                          }if($item['fecha']==$fecha  && $item['hora']==$hora4 && $item['usuarioid']==$usuarioid){
                            $valida=false;  
                          }if($item['fecha']==$fecha  && $item['hora']==$hora5 && $item['usuarioid']==$usuarioid){
                            $valida=false;  
                          }
                        }
                      }     
                        $dia_id=date("w",strtotime($fecha)); 
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
                }
                if($result){
                  echo "importacion Exitosa!";
                }else{
                  echo "Esas checadas ya existen o el archivo no tiene informacion!";
                } 
          } 
          catch (Exception $e) {
               die('Error loading file "' . pathinfo($inputFileName, PATHINFO_BASENAME). '": ' .$e->getMessage());
          }
          }else{
              echo $error['error'];
          }
  }



}
?>