<?php
class ContratoRH extends CI_Model{
    function ___construct(){ 
        parent::___construct();
    }
    public function consulta($idsalario,$idrepresentante,$idhorario,$idempleado,$idempresa,$iddescanso,$idComida,$idrazon)
    {        

        ini_set('memory_limit','512M'); // This also needs to be increased in some cases. Can be changed to a higher value as per need)
        ini_set('sqlsrv.ClientBufferMaxKBSize','524288'); // Setting to 512M
        ini_set('pdo_sqlsrv.client_buffer_max_kb_size','524288'); // Setting to 512M - for pdo_sqlsrv
        ini_set('max_execution_time', 0); //para que no limite a 30 segundos la consulta
        ini_set('max_execution_time', 0); //para que no limite a 30 segundos la consulta
        $sp = "ConsultaContrato ?,?,?,?,?,?,?,? "; ///595359567
        ///
        $params = array(
        'idSalario'         => intval($idsalario),
        'idRepresentante'   => intval($idrepresentante),
        'idHorario'         => intval($idhorario),
        'idEmpleado'        => intval($idempleado),
        'idEmpresa'         => intval($idempresa),
        'idDescanso'        => intval($iddescanso),
        'idComida'        => intval($idComida),
        'idRazon'        => intval($idrazon)
        
        );
        
        $result = $this->db->query($sp,$params);

        //print_r($result->result_array());
        //die();
        return  $result->result_array();
    }
    
    public function consultaSalario(){
        ini_set('max_execution_time', 0); //para que no limite a 30 segundos la consulta        
        $sp = "ConsultaSalario";        
        $result = $this->db->query($sp);
        return  $result->result_array();
    }   
    
    public function ConsultaEmpleadoIDCount($idempleado){
        ini_set('max_execution_time', 0); //para que no limite a 30 segundos la consulta        
        $sp = "ConsultaEmpleadoIDCount ?";      
        $params = array(
            'empleado_id'         => intval($idempleado),
            );  
        $result = $this->db->query($sp,$params);
        return  $result->result_array();
    }    

    public function ConsultaComida(){
        ini_set('max_execution_time', 0); //para que no limite a 30 segundos la consulta        
        $sp = "ConsultaComida";        
        $result = $this->db->query($sp);
        return  $result->result_array();
    }  
    

    public function consultaRepresentante(){
        ini_set('max_execution_time', 0); //para que no limite a 30 segundos la consulta        
        $sp = "ConsultaRepresentante";        
        $result = $this->db->query($sp);
        return  $result->result_array();
    }  
    
    public function ConsultaHorarioContrato(){
        ini_set('max_execution_time', 0); //para que no limite a 30 segundos la consulta        
        $sp = "ConsultaHorarioContrato";        
        $result = $this->db->query($sp);
        return  $result->result_array();
    }  

    
    public function ConsultaDescanso(){
        ini_set('max_execution_time', 0); //para que no limite a 30 segundos la consulta        
        $sp = "ConsultaDescanso";        
        $result = $this->db->query($sp);
        return  $result->result_array();
    }  

    public function consultaRazon()
    {
        ini_set('max_execution_time', 0); //para que no limite a 30 segundos la consulta        
        $sp = "consultaRazon";        
        $result = $this->db->query($sp);
        return  $result->result_array();
    }

}

?>









