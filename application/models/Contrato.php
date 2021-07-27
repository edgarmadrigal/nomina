<?php
class Contrato2 extends CI_Model{
    function ___construct(){ 
        parent::___construct();
    }
    public function consulta($idsalario,$idrepresentante,$idhorario,$idempleado,$idempresa,$iddescanso)
    {        
        ini_set('max_execution_time', 0); //para que no limite a 30 segundos la consulta
        $sp = "ConsultaContrato ?,?,?,?,?,? "; 
        $params = array(
        'idsalario'         => intval($idsalario),
        'idRepresentante'   => intval($idrepresentante),
        'idHorario'         => intval($idhorario),
        'idEmpleado'        => intval($idempleado),
        'idEmpresa'         => intval($idempresa),
        'idDescanso'        => intval($iddescanso));
        
        $result = $this->db->query($sp,$params);
        return  $result->result_array();
    }


    public function consultaRazon()
    {
        $sp = "consultaRazon";        
        $result = $this->db->query($sp);
        return  $result->result_array();
    }

    

}

?>









