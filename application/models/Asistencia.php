<?php
class Asistencia extends CI_Model{
    private $mysql; 
    function ___construct(){ 
        parent::___construct();
    }
    public function consulta($NoSemana,$anio,$planta,$departamento,$puesto,$noempleado)
    {        
        ini_set('max_execution_time', 0); //para que no limite a 30 segundos la consulta
        $sp = "ConsultaAsistencia ?,?,?,?,?,? "; 
        $params = array(
        'NoSemana' => intval($NoSemana),
        'anio' => $anio,
        'planta' => intval($planta),
        'Code' => $departamento,
        'puesto' => $puesto,
        'NoEmpleado' => $noempleado);
        
        $result = $this->db->query($sp,$params);
        return  $result->result_array();
    }
    
    public function borrar($NoSemana,$anio,$planta)
    {        
        ini_set('max_execution_time', 0); //para que no limite a 30 segundos la consulta
        $sp = "BorraAsistencia ?,?,? "; 
        $params = array(
        'NoSemana' => intval($NoSemana),
        'anio' => $anio,
        'planta' => intval($planta));
        
        $result = $this->db->query($sp,$params);
        return  $result->result_array();
    }


    public function actualizaTabla($NoSemana){
        
        ini_set('max_execution_time', 0); //para que no limite a 30 segundos la consulta
        $sp = "ActualizaChecadasBiostar ?,?,?,?,?,? "; 
        $params = array(
        'NoSemana' => $NoSemana,
        'anio' => null,
        'planta' => '1',
        'Code' => null,
        'puesto' => null,
        'NoEmpleado' => null);
        
        $result = $this->db->query($sp,$params);

        ini_set('max_execution_time', 0); //para que no limite a 30 segundos la consulta
        $sp = "ActualizaChecadasBiostar ?,?,?,?,?,? "; 
        $params = array(
        'NoSemana' => $NoSemana,
        'anio' => null,
        'planta' => '2',
        'Code' => null,
        'puesto' => null,
        'NoEmpleado' => null);
        
        $result = $this->db->query($sp,$params);
        ini_set('max_execution_time', 0); //para que no limite a 30 segundos la consulta
        $sp = "ActualizaChecadasBiostar ?,?,?,?,?,? "; 
        $params = array(
        'NoSemana' => $NoSemana,
        'anio' => null,
        'planta' => '3',
        'Code' => null,
        'puesto' => null,
        'NoEmpleado' => null);
        
        $result = $this->db->query($sp,$params);
        ini_set('max_execution_time', 0); //para que no limite a 30 segundos la consulta
        $sp = "ActualizaChecadasBiostar ?,?,?,?,?,? "; 
        $params = array(
        'NoSemana' => $NoSemana,
        'anio' => null,
        'planta' => '4',
        'Code' => null,
        'puesto' => null,
        'NoEmpleado' => null);
        
        $result = $this->db->query($sp,$params);
        ini_set('max_execution_time', 0); //para que no limite a 30 segundos la consulta
        $sp = "ActualizaChecadasBiostar ?,?,?,?,?,? "; 
        $params = array(
        'NoSemana' => $NoSemana,
        'anio' => null,
        'planta' => '5',
        'Code' => null,
        'puesto' => null,
        'NoEmpleado' => null);
        
        $result = $this->db->query($sp,$params);
        ini_set('max_execution_time', 0); //para que no limite a 30 segundos la consulta
        $sp = "ActualizaChecadasBiostar ?,?,?,?,?,? "; 
        $params = array(
        'NoSemana' => $NoSemana,
        'anio' => null,
        'planta' => '6',
        'Code' => null,
        'puesto' => null,
        'NoEmpleado' => null);
        
        $result = $this->db->query($sp,$params);
        ini_set('max_execution_time', 0); //para que no limite a 30 segundos la consulta
        $sp = "ActualizaChecadasBiostar ?,?,?,?,?,? "; 
        $params = array(
        'NoSemana' => $NoSemana,
        'anio' => null,
        'planta' => '7',
        'Code' => null,
        'puesto' => null,
        'NoEmpleado' => null);
        
        $result = $this->db->query($sp,$params);
        ini_set('max_execution_time', 0); //para que no limite a 30 segundos la consulta
        $sp = "ActualizaChecadasBiostar ?,?,?,?,?,? "; 
        $params = array(
        'NoSemana' => $NoSemana,
        'anio' => null,
        'planta' => '8',
        'Code' => null,
        'puesto' => null,
        'NoEmpleado' => null);
        
        $result = $this->db->query($sp,$params);
        
        ini_set('max_execution_time', 0); //para que no limite a 30 segundos la consulta
        $sp = "ActualizaChecadasBiostar ?,?,?,?,?,? "; 
        $params = array(
        'NoSemana' => $NoSemana,
        'anio' => null,
        'planta' => '9',
        'Code' => null,
        'puesto' => null,
        'NoEmpleado' => null);
        
        $result = $this->db->query($sp,$params);
        
        ini_set('max_execution_time', 0); //para que no limite a 30 segundos la consulta
        $sp = "ActualizaChecadasBiostar ?,?,?,?,?,? "; 
        $params = array(
        'NoSemana' => $NoSemana,
        'anio' => null,
        'planta' => '10',
        'Code' => null,
        'puesto' => null,
        'NoEmpleado' => null);
        
        $result = $this->db->query($sp,$params);
        
        ini_set('max_execution_time', 0); //para que no limite a 30 segundos la consulta
        $sp = "ActualizaChecadasBiostar ?,?,?,?,?,? "; 
        $params = array(
        'NoSemana' => $NoSemana,
        'anio' => null,
        'planta' => '11',
        'Code' => null,
        'puesto' => null,
        'NoEmpleado' => null);
        
        $result = $this->db->query($sp,$params);
        
        ini_set('max_execution_time', 0); //para que no limite a 30 segundos la consulta
        $sp = "ActualizaChecadasBiostar ?,?,?,?,?,? "; 
        $params = array(
        'NoSemana' => $NoSemana,
        'anio' => null,
        'planta' => '12',
        'Code' => null,
        'puesto' => null,
        'NoEmpleado' => null);
        
        $result = $this->db->query($sp,$params);
        
        ini_set('max_execution_time', 0); //para que no limite a 30 segundos la consulta
        $sp = "ActualizaChecadasBiostar ?,?,?,?,?,? "; 
        $params = array(
        'NoSemana' => $NoSemana,
        'anio' => null,
        'planta' => '13',
        'Code' => null,
        'puesto' => null,
        'NoEmpleado' => null);
        
        $result = $this->db->query($sp,$params);
        
        ini_set('max_execution_time', 0); //para que no limite a 30 segundos la consulta
        $sp = "ActualizaChecadasBiostar ?,?,?,?,?,? "; 
        $params = array(
        'NoSemana' => $NoSemana,
        'anio' => null,
        'planta' => '14',
        'Code' => null,
        'puesto' => null,
        'NoEmpleado' => null);
        
        $result = $this->db->query($sp,$params);
        
        ini_set('max_execution_time', 0); //para que no limite a 30 segundos la consulta
        $sp = "ActualizaChecadasBiostar ?,?,?,?,?,? "; 
        $params = array(
        'NoSemana' => $NoSemana,
        'anio' => null,
        'planta' => '15',
        'Code' => null,
        'puesto' => null,
        'NoEmpleado' => null);
        
        $result = $this->db->query($sp,$params);
        
        ini_set('max_execution_time', 0); //para que no limite a 30 segundos la consulta
        $sp = "ActualizaChecadasBiostar ?,?,?,?,?,? "; 
        $params = array(
        'NoSemana' => $NoSemana,
        'anio' => null,
        'planta' => '16',
        'Code' => null,
        'puesto' => null,
        'NoEmpleado' => null);
        
        $result = $this->db->query($sp,$params);

        return  $result->result_array();
    }
    public function actualizaPlanta($NoSemana,$anio,$planta){
        ini_set('max_execution_time', 0); //para que no limite a 30 segundos la consulta
        $sp = "ActualizaChecadasBiostar ?,?,?,?,?,? "; 
        $params = array(
        'NoSemana' => intval($NoSemana),
        'anio' => $anio,
        'planta' => intval($planta),
        'Code' => null,
        'puesto' => null,
        'NoEmpleado' => null);
        
        $result = $this->db->query($sp,$params);
    }

}

?>









