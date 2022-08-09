<?php
//MODELO usuario
class Model137 extends CI_Model{

    function ___construct(){
        parent::___construct();
		$this->load->library('form_validation');
    }
/**-------------------------------ConsultaReporteDiario---------------------------------------------  */
    public function consulta($fechaInicio,$fechaFin)
    {        
        ini_set('memory_limit','512M'); // This also needs to be increased in some cases. Can be changed to a higher value as per need)
        ini_set('sqlsrv.ClientBufferMaxKBSize','524288'); // Setting to 512M
        ini_set('pdo_sqlsrv.client_buffer_max_kb_size','524288'); // Setting to 512M - for pdo_sqlsrv
        ini_set('max_execution_time', 0); //para que no limite a 30 segundos la consulta

        $sp = "ConsultaReporteCosto ?,? "; 
        $params = array(
        'FechaInicio' => $fechaInicio,
        'FechaFin' => $fechaFin);
        
        $result = $this->db->query($sp,$params);
        
        return  $result->result_array();        
    }



    
   
    public function ExportarArchivo($fechaInicio,$fechaFin){
        ini_set('memory_limit','512M'); // This also needs to be increased in some cases. Can be changed to a higher value as per need)
        ini_set('sqlsrv.ClientBufferMaxKBSize','524288'); // Setting to 512M
        ini_set('pdo_sqlsrv.client_buffer_max_kb_size','524288'); // Setting to 512M - for pdo_sqlsrv
        ini_set('max_execution_time', 0); //para que no limite a 30 segundos la consulta
        
        if(unlink('costo.txt')){
            ///se borro
        }
        else{
            ///no se borro el archivo
        }
        
        $sp = "ConsultaReporteCosto  ?,? ";
        $params = array(
            'FechaInicio' => $fechaInicio,
            'FechaFin' => $fechaFin);        
        
        $result = $this->db->query($sp,$params);
        $result = $result->result_array();   
      
        $file = "costo.txt"; //le doy un nombre al archivo
        
        file_put_contents($file,"CENTRO COSTO;DEPARTAMENTO;PLANILLA;PERCEP;HOREXT;GRATIF;INCENT;PPYADE;CROM;TOTPER;SUBEMP;ISPT;IMSS;CAJAAHO;INFONAVIT;PREAHO;PREEMP;INTERGAS;OTRASRET;TOTAL" . PHP_EOL); //creamos el archivo
          
        for($i = 0; $i < count($result); $i++)
            {
                
                for($j = 0; $j < 20; $j++)
                {
                    switch ($j) {
                    case 0:
                        file_put_contents($file, "".$result[$i]['CENTRO_COSTO'], FILE_APPEND); //escribo en el archivo separando el arreglo con comas
                    break;
                    case 1:
                        file_put_contents($file, ";".$result[$i]['DEPARTAMENTO'], FILE_APPEND); //escribo en el archivo separando el arreglo con comas
                        break;
                    case 2:
                        file_put_contents($file, ";".$result[$i]['PLANILLA'], FILE_APPEND); //escribo en el archivo separando el arreglo con comas                        
                        break;
                    case 3:
                        file_put_contents($file, ";".$result[$i]['PERCEP'], FILE_APPEND); //escribo en el archivo separando el arreglo con comas
                        break;
                    case 4:
                        file_put_contents($file, ";".$result[$i]['HOREXT'], FILE_APPEND); //escribo en el archivo separando el arreglo con comas
                        break;
                    case 5:
                        file_put_contents($file, ";".$result[$i]['GRATIF'], FILE_APPEND); //escribo en el archivo separando el arreglo con comas
                        break;
                    case 6:
                        file_put_contents($file, ";".$result[$i]['INCENT'], FILE_APPEND); //escribo en el archivo separando el arreglo con comas
                        break;
                    case 7:
                        file_put_contents($file, ";".$result[$i]['PPYADE'], FILE_APPEND); //escribo en el archivo separando el arreglo con comas
                        break;
                    case 8:
                        file_put_contents($file, ";".$result[$i]['CROM'], FILE_APPEND); //escribo en el archivo separando el arreglo con comas
                        break;
                    case 9:
                        file_put_contents($file, ";".$result[$i]['TOTPER'], FILE_APPEND); //escribo en el archivo separando el arreglo con comas
                        break;
                    case 10:
                        file_put_contents($file, ";".$result[$i]['SUBEMP'], FILE_APPEND); //escribo en el archivo separando el arreglo con comas
                        break;
                    case 11:
                        file_put_contents($file, ";".$result[$i]['ISPT'], FILE_APPEND); //escribo en el archivo separando el arreglo con comas
                        break;
                    case 12:  
                        file_put_contents($file, ";".$result[$i]['IMSS'], FILE_APPEND); //escribo en el archivo separando el arreglo con comas   
                        break;
                    case 13:
                        file_put_contents($file, ";".$result[$i]['CAJAAHO'], FILE_APPEND); //escribo en el archivo separando el arreglo con comas
                        break;
                    case 14:
                        file_put_contents($file, ";".$result[$i]['INFONAVIT'], FILE_APPEND); //escribo en el archivo separando el arreglo con comas
                        break;
                    case 15:
                        file_put_contents($file, ";".$result[$i]['PREAHO'], FILE_APPEND); //escribo en el archivo separando el arreglo con comas
                        break;
                    case 16:
                        file_put_contents($file, ";".$result[$i]['PREEMP'], FILE_APPEND); //escribo en el archivo separando el arreglo con comas
                        break;
                    case 17:
                        file_put_contents($file, ";".$result[$i]['INTERGAS'], FILE_APPEND); //escribo en el archivo separando el arreglo con comas
                        break;
                    case 18:
                        file_put_contents($file, ";".$result[$i]['OTRASRET'], FILE_APPEND); //escribo en el archivo separando el arreglo con comas
                        break;
                    case 19:
                        file_put_contents($file, ";".$result[$i]['TOTAL'], FILE_APPEND); //escribo en el archivo separando el arreglo con comas
                        break;
                   
                    }
                }
                file_put_contents($file, PHP_EOL, FILE_APPEND); //agrego un salto de linea
            }
        if (file_exists($file)) { //verifico que el archivo haya sido creado
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename='.basename($file));
            header('Content-Transfer-Encoding: binary');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($file));
            ob_clean();
            flush();
            readfile($file);
            /*
            file_get_contents($file);	
            	*/	
        } else
        {
          //en caso no se haya creado el archivo, muestro un mensaje
          echo "Hubo un error al momento de crear el archivo, verifique los permisos de las carpetas del servidor.";
        }
        
        
       
       
        return  $result;    
      }

      
        
/*
    public function consultacosto(){

        ini_set('memory_limit','512M'); // This also needs to be increased in some cases. Can be changed to a higher value as per need)
        ini_set('sqlsrv.ClientBufferMaxKBSize','524288'); // Setting to 512M
        ini_set('pdo_sqlsrv.client_buffer_max_kb_size','524288'); // Setting to 512M - for pdo_sqlsrv
        ini_set('max_execution_time', 0); //para que no limite a 30 segundos la consulta

        $this->db->select('TIPO
        ,CLAVE
        ,GPO
        ,EMPRESA
        ,NOMBRECONCEPTO
        ,IMPORTE_GRAVABLE
        ,IMPORTE_EXENTO
        ,TOTAL');       
        $this->db->from('costoMicro');
        $aResult = $this->db->get();
        if(!$aResult->num_rows() == 1)
        {
            return false;
        }
        return $aResult->result_array();
    }
*/

}

?>