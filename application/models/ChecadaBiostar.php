<?php
class ChecadaBiostar extends CI_Model{
    private $mysql; 
    function ___construct(){
        parent::___construct();
        $this->load->library('form_validation');
        //$this->load->library('pedeo', [$this->mysql]);
    }
    public function consulta($NoSemana,$anio,$planta,$departamento,$puesto,$noempleado)
    {        
        ini_set('max_execution_time', 0); //para que no limite a 30 segundos la consulta
        $sp = "ConsultaChecadasEmpleadosTorreonBiostar ?,?,?,?,?,? "; 
        $params = array(
        'NoSemana' => $NoSemana,
        'anio' => $anio,
        'planta' => $planta,
        'Code' => $departamento,
        'puesto' => $puesto,
        'NoEmpleado' => $noempleado);
        
        $result = $this->db->query($sp,$params);
        return  $result->result_array();
    }
    public function ActualizarChecadasNomiplus(){        
        $sp = "ActualizaChecadasNomiplus";        
        $result = $this->db->query($sp);
        return  $result->result_array();
    }

    public function consultaPlanta()
    {
        $sp = "ConsultaPlantaMicrosip_PEDRI";        
        $result = $this->db->query($sp);
        return  $result->result_array();
    }
    public function consultaDepartamento($planta)
    {        
        ini_set('max_execution_time', 0); 

        sqlsrv_configure('WarningsReturnAsErrors', 0);
        if($planta==''){
            $result = 'x';
        }else{            
            $result = $this->db->query("ConsultaDepartamentoMicrosip {$planta}")->result_array();
        }
        return  $result;
    }
    public function consultaPuesto ($planta)
    {
        ini_set('max_execution_time', 0); 

        sqlsrv_configure('WarningsReturnAsErrors', 0);
        if($planta==''){
            $result = 'x';
        }else{            
            $result = $this->db->query("ConsultaPuestoMicrosip {$planta}")->result_array();
        }

        return  $result;
    }

    
   

}

?>