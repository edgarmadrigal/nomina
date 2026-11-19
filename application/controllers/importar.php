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
 

  public function subirPlanta() {
            header('Access-Control-Allow-Origin: *');
            $planta   =   $this->input->post('planta');
            $this->session->set_userdata('planta', $planta);
            echo json_encode($planta);
  }



/*****************
 * 
 * 
 * 
 * 
  public function uploadData() {
    header('Access-Control-Allow-Origin: *');
    $idPlanta=$this->session->userdata('planta');
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
                $GRUPO   = [1,2,3,4,5,6,7,8,9,10,18,19,40,41];
                $result = false;
                $nuevas = [];
                $repetidas = 0;
                $esGrupo = in_array((int)$idPlanta, $GRUPO, true);
                $mapa = []; $dups = []; $noAsignados = [];
                if ($esGrupo) {
                    $info = $this->import->mapaEmpleadosGrupo($GRUPO);
                    $mapa = $info['mapa'];
                    $dups = $info['duplicados'];
                }
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
                    || $value['G']=='1:N Autenticación exitosa (Rostro)'
										|| $value['G']=='1:1 authentication succeeded (Card)'     ){
                      
                     
                      
                          $x= trim($value['A']);
                          $x = date_create_from_format('d/m/Y H:i',$x );
                          $date =  $x->getTimestamp();                       
                          $fechahora= date("Y-m-d H:i",$date );       

                          $fecha_actual = strtotime("2021-01-01 00:00");
                          $fecha_entrada =  $fechahora;


                          $x = date_create_from_format('d/m/Y H:i', trim($value['A']));


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
                      || $value['F'] =='1:N authentication succeeded (Face)' 
                      || $value['F']  =='1:N authentication succeeded (Face)' 
                      || $value['F'] =='1:N Autenticaci??n exitosa (Rostro)' 
                      || $value['F'] =='1:N Autenticación exitosa (Rostro)' 
											|| $value['F'] =='1:1 authentication succeeded (Card)'     ){
                      
                      
                      try{
                        $fecha= date("Y-m-d",strtotime($value['A']));              
                        $fechahora= date("Y-m-d H:i",strtotime($value['A']));                        
                        $hora=  date("H:i",strtotime($value['A']));
                        $fecha_entrada =  $fechahora;
                      }catch (Exception $e) {         
                        $x= trim($value['A']);
                        $x = date_create_from_format('d/m/Y H:i',$x );
                        $date =  $x->getTimestamp();                       
                        $fechahora= date("Y-m-d H:i",$date );  
                        $fecha_entrada =  $fechahora;     
                     }
  
                        $fecha_actual = '2021-01-01 00:00';


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
		                }

                      $completo=explode('(',$value['E']);
                      $usuarioid=$completo[0];
                      $valida=true;   
                      $dia_id=date("w",strtotime($fecha)); 
                      $idPlanta= $this->session->userdata('planta');

                             
                               
                            
                      

                      $datetime1 = date('Y-m-d');
                      $datetime1 =strtotime ( $datetime1 ) ;
                      $datetime1 = date_create(date ( 'Y-m-d' ));
                      $datetime2 = date_create($fechahora); //fecha de db  
                      $interval = date_diff($datetime1, $datetime2,false);  
                      $dias = intval($interval->format('%R%a'));  
                      if ($dias > 0) {
                            continue;  
                        }
                      if($dia_id==0){
                        $dia_id=7;
                      }
                               $plantaFila = $idPlanta;
                                if ($esGrupo) {
                                    $num = (int)trim($usuarioid);
                                    if (!isset($mapa[$num])) {           // no pertenece al grupo
                                        $noAsignados[$num] = 'no existe en el grupo';
                                        continue;
                                    }
                                    if (isset($dups[$num])) {             // repetido dentro del grupo
                                        $noAsignados[$num] = 'repetido en 2 empresas';
                                        continue;
                                    }
                                    $plantaFila = $mapa[$num];
                                }

                                $inserdata[$i]['fecha']     = $fecha;
                                $inserdata[$i]['fechahora'] = $fechahora;
                                $inserdata[$i]['usuarioid'] = $usuarioid;
                                $inserdata[$i]['hora']      = $hora;
                                $inserdata[$i]['dia_id']    = $dia_id;
                                $inserdata[$i]['idPlanta']  = $plantaFila;
                                $i++;
                      }
                    }
                }
                    if ($inserdata) {
                          // quitar duplicados dentro del mismo archivo
                          $sinduplicados = array_map("unserialize", array_unique(array_map("serialize", $inserdata)));

                          // traer lo que ya existe en BD en el rango de fechas del archivo
                          $fechas = array_column($sinduplicados, 'fechahora');
                          $existentes = $this->import->checadasExistentes(min($fechas), max($fechas) . ':59');

                          foreach ($sinduplicados as $fila) {
                              $key = (int)trim($fila['usuarioid']) . '|' . $fila['fechahora'];
                              if (isset($existentes[$key])) {
                                  $repetidas++;
                                  continue;
                              }
                              $existentes[$key] = true;   // evita repetidas dentro del archivo con distinto formato
                              $nuevas[] = $fila;
                          }

                          if ($nuevas) {
                              $result = $this->import->importdata($nuevas);
                          }
                      }

                      if ($result) {
                          echo "importacion Exitosa! " . count($nuevas) . " checadas nuevas.";
                          if ($repetidas) {
                              echo "\n$repetidas checadas ya existian y se omitieron.";
                          }
                      } else if ($inserdata && !$nuevas) {
                          echo "Todas las checadas del archivo ya existian ($repetidas).";
                      } else {
                          echo "El archivo no tiene informacion valida.";
                      }

                      if ($noAsignados) {
                          echo "\nNo se importaron estos empleados:\n";
                          foreach ($noAsignados as $num => $motivo) {
                              echo "$num ($motivo)\n";
                          }
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
*/

  public function uploadData() {
    header('Access-Control-Allow-Origin: *');
    $idPlanta = $this->session->userdata('planta');

    // Sin planta en sesion todo entraria con idPlanta NULL
    if ($idPlanta === null || $idPlanta === '' || (int)$idPlanta === 0) {
        echo "Selecciona la planta antes de subir el archivo.";
        return;
    }

    $path = 'uploads/';
    require_once APPPATH."/third_party/PHPExcel.php";
    $config['upload_path']   = $path;
    $config['allowed_types'] = 'xlsx|xls|csv';
    $config['remove_spaces'] = TRUE;
    $this->load->library('upload', $config);
    $this->upload->initialize($config);

    if (!$this->upload->do_upload('uploadFile')) {
        print_r('Error');
        return;
    }
    $data = array('upload_data' => $this->upload->data());
    $import_xls_file = !empty($data['upload_data']['file_name']) ? $data['upload_data']['file_name'] : 0;
    $inputFileName = $path . $import_xls_file;

    try {
        $inputFileType  = PHPExcel_IOFactory::identify($inputFileName);
        $objReader      = PHPExcel_IOFactory::createReader($inputFileType);
        $objPHPExcel    = $objReader->load($inputFileName);
        $allDataInSheet = $objPHPExcel->getActiveSheet()->toArray(null, true, true, true);

        $eventosValidos = array(
            '1:N authentication succeeded (Face)',
            '1:N Autenticaciￃﾳn exitosa (Rostro)',
            '1:N Autenticaci??n exitosa (Rostro)',
            '1:N Autenticación exitosa (Rostro)',
            '1:1 authentication succeeded (Card)',
        );

             // Grupos de empresas que checan en los mismos equipos.
        // Si la planta elegida pertenece a un grupo, cada checada se asigna
        // a la empresa real del empleado DENTRO de ese grupo.
        $GRUPOS = array(
            array(1,2,3,4,5,6,7,8,9,10,18,19,25,40,41),/// TODO TORREON (25 = EVENTUALES, checan en los mismos relojes)
            array(11,12,13,34),   // NAZARENO1, 2 y 3 / DELTA3 (la 11 y la 13 ya no tienen activos)
            array(14,43),   // CUENCAME / DTL_DURANGO
            array(15,42),   // PILAR / DTL_EL_PILAR
        );
        $GRUPO = array();
        foreach ($GRUPOS as $g) {
            if (in_array((int)$idPlanta, $g, true)) { $GRUPO = $g; break; }
        }
        $esGrupo = !empty($GRUPO);
        $mapa = []; $dups = []; $noAsignados = [];
        if ($esGrupo) {
            $info = $this->import->mapaEmpleadosGrupo($GRUPO);
            $mapa = $info['mapa'];
            $dups = $info['duplicados'];
        }

        $hoy        = new DateTime('today 23:59:59');
        $inserdata  = [];
        $result     = false;
        $nuevas     = [];
        $repetidas  = 0;
        $flag       = true;
        $ordenFecha    = $this->detectarOrdenFecha($allDataInSheet);
        $sinEvento     = 0;
        $fechaInvalida = 0;
        $futuras       = 0;
        $ejemploFecha  = null;

        foreach ($allDataInSheet as $value) {
            if ($flag) {            // encabezado
                $flag = false;
                continue;
            }

            // Detectar formato del archivo:
            //  - con columna extra: usuario en F, evento en G
            //  - normal:            usuario en E, evento en F
                       // Detectar formato del archivo:
            //  - con columna extra: usuario en F, evento en G
            //  - normal:            usuario en E, evento en F
            if (isset($value['G']) && in_array(trim($value['G']), $eventosValidos, true)) {
                $colUsuario = 'F';
            } elseif (isset($value['F']) && in_array(trim($value['F']), $eventosValidos, true)) {
                $colUsuario = 'E';
            } else {
                $sinEvento++;
                continue;           // no es una checada valida
            }

            $x = $this->parsearFecha($value['A'], $ordenFecha);
            if (!$x) {
                $fechaInvalida++;
                if ($ejemploFecha === null) $ejemploFecha = (string)$value['A'];
                continue;           // fecha ilegible: se omite la fila
            }
            if ($x > $hoy) {
                $futuras++;
                continue;           // fecha futura: se omite la fila
            }

            $fecha     = $x->format('Y-m-d');
            $fechahora = $x->format('Y-m-d H:i');
            $hora      = $x->format('H:i');
            $dia_id    = (int)$x->format('N');   // 1=lunes ... 7=domingo

            $completo  = explode('(', $value[$colUsuario]);
            $usuarioid = trim($completo[0]);
            if ($usuarioid === '') {
                continue;
            }

            // Asignar la planta real del empleado cuando es del grupo
            $plantaFila = $idPlanta;
            if ($esGrupo) {
                $num = (int)$usuarioid;
                if (!isset($mapa[$num])) {
                    $noAsignados[$num] = 'no existe en el grupo';
                    continue;
                }
                if (isset($dups[$num])) {
                    $noAsignados[$num] = 'repetido en 2 empresas';
                    continue;
                }
                $plantaFila = $mapa[$num];
            }

            $inserdata[] = array(
                'fecha'     => $fecha,
                'fechahora' => $fechahora,
                'usuarioid' => $usuarioid,
                'hora'      => $hora,
                'dia_id'    => $dia_id,
                'idPlanta'  => $plantaFila,
            );
        }
        $corregidas = 0;

        if ($inserdata) {
            // quitar duplicados dentro del mismo archivo
            $sinduplicados = array_map("unserialize", array_unique(array_map("serialize", $inserdata)));

            // traer lo que ya existe en BD en el rango de fechas del archivo
            $fechas     = array_column($sinduplicados, 'fechahora');
            $existentes = $this->import->checadasExistentes(min($fechas), max($fechas) . ':59');

            $porCorregir = array();   // idPlanta correcta => [ids de checadas]

            foreach ($sinduplicados as $fila) {
                $key = (int)$fila['usuarioid'] . '|' . $fila['fechahora'];

                if (isset($existentes[$key])) {
                    $yaBien = false;
                    $idMal  = null;
                    foreach ($existentes[$key] as $ex) {
                        if ((string)$ex['idPlanta'] === (string)$fila['idPlanta']) {
                            $yaBien = true;
                        } elseif ($idMal === null && (
                                   $ex['idPlanta'] === null
                                || ($esGrupo && in_array((int)$ex['idPlanta'], $GRUPO, true))
                                 )) {
                            // quedo en NULL o en otra empresa del MISMO grupo
                            $idMal = $ex['id'];
                        }
                    }
                    if (!$yaBien && $idMal !== null) {
                        $porCorregir[$fila['idPlanta']][] = $idMal;
                        $corregidas++;
                    } else {
                        $repetidas++;
                    }
                    continue;
                }

                $existentes[$key] = array(array('id' => null, 'idPlanta' => $fila['idPlanta']));
                $nuevas[] = $fila;
            }

            foreach ($porCorregir as $planta => $ids) {
                $this->import->corregirPlanta($ids, $planta);
            }

            if ($nuevas) {
                $result = $this->import->importdata($nuevas);
            }
        }

        if ($result || $corregidas) {
            echo "importacion Exitosa! " . count($nuevas) . " checadas nuevas.";
            if ($corregidas) {
                echo "\n$corregidas checadas ya existian con otra empresa (o sin empresa) y se corrigieron.";
            }
            if ($repetidas) {
                echo "\n$repetidas checadas ya existian y se omitieron.";
            }
        } else if ($inserdata && !$nuevas) {
            echo "Todas las checadas del archivo ya existian ($repetidas).";
        } else {
            echo "El archivo no tiene informacion valida.";
        }

        // Diagnostico: por que se omitieron filas
        if ($sinEvento || $fechaInvalida || $futuras) {
            echo "\n\nFilas omitidas:";
            if ($sinEvento)     echo "\n- $sinEvento sin evento de checada valido";
            if ($fechaInvalida) echo "\n- $fechaInvalida con fecha ilegible (ejemplo: '$ejemploFecha')";
            if ($futuras)       echo "\n- $futuras con fecha futura";
            echo "\nOrden de fecha detectado: $ordenFecha";
        }
        if ($noAsignados) {
            echo "\nNo se importaron estos empleados:\n";
            foreach ($noAsignados as $num => $motivo) {
                echo "$num ($motivo)\n";
            }
        }
    }
    catch (Exception $e) {
        print_r('Error loading file "' . pathinfo($inputFileName, PATHINFO_BASENAME) . '": ' . $e->getMessage());
        die();
    }
  }

    // Decide si el archivo trae dia/mes o mes/dia.
  // Si algun primer numero es >12 es dia/mes; si algun segundo es >12 es mes/dia.
  // Si todo es ambiguo, elige el orden cuya fecha mas reciente no pase de hoy y sea la mas cercana.
  private function detectarOrdenFecha($filas) {
      $hoy = new DateTime('today 23:59:59');
      $maxDM = null; $maxMD = null;
      foreach ($filas as $v) {
          if (!isset($v['A'])) continue;
          if (!preg_match('#^(\d{1,2})/(\d{1,2})/(\d{4})#', trim((string)$v['A']), $m)) continue;
          $a = (int)$m[1]; $b = (int)$m[2]; $y = (int)$m[3];
          if ($a > 12) return 'd/m';
          if ($b > 12) return 'm/d';
          $dm = sprintf('%04d-%02d-%02d', $y, $b, $a);
          $md = sprintf('%04d-%02d-%02d', $y, $a, $b);
          if ($maxDM === null || $dm > $maxDM) $maxDM = $dm;
          if ($maxMD === null || $md > $maxMD) $maxMD = $md;
      }
      if ($maxDM === null) return 'd/m';
      $h = $hoy->format('Y-m-d');
      $dmOk = $maxDM <= $h; $mdOk = $maxMD <= $h;
      if ($dmOk && !$mdOk) return 'd/m';
      if ($mdOk && !$dmOk) return 'm/d';
      return ($maxMD > $maxDM) ? 'm/d' : 'd/m';
  }

  // Convierte el texto de la columna A a DateTime, o false si no se puede.
  private function parsearFecha($texto, $orden) {
      $texto = trim((string)$texto);
      if ($texto === '') return false;

      // Fecha como numero de serie de Excel (ej. 46289.4611)
      if (is_numeric($texto)) {
          return PHPExcel_Shared_Date::ExcelToPHPObject((float)$texto);
      }

      $formatos = array('Y-m-d H:i:s', 'Y-m-d H:i');
      $dm = ($orden === 'm/d') ? 'n/j/Y' : 'j/n/Y';
      foreach (array('G:i:s', 'G:i', 'g:i:s A', 'g:i A') as $t) {
          $formatos[] = $dm . ' ' . $t;
      }

      foreach ($formatos as $f) {
          $x = DateTime::createFromFormat('!' . $f, $texto);
          $err = DateTime::getLastErrors();
          $sinErrores = ($err === false) || ($err['warning_count'] == 0 && $err['error_count'] == 0);
          if ($x && $sinErrores) return $x;   // rechaza fechas invalidas como mes 23
      }
      return false;
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

              $fecha= date("Y-m-d",strtotime($value['A']));
              
              $fechahora= date("Y-m-d H:i",strtotime($value['A']));
              
              $hora= date("H:i",strtotime($value['A']));
              

              $completo=explode('(',$value['E']);
              $usuarioid=$completo[0];
              $valida=true;   
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
                      $inserdata[$i]['idPlanta'] =  $idPlanta;      
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
