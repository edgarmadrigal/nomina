<?php
class ContratoRH extends CI_Model{
    function ___construct(){ 
        parent::___construct();
    }
    public function consulta($idsalario,$idrepresentante,$idhorario,$idempleado,$idempresa,$iddescanso)
    {        

        ini_set('memory_limit','512M'); // This also needs to be increased in some cases. Can be changed to a higher value as per need)
        ini_set('sqlsrv.ClientBufferMaxKBSize','524288'); // Setting to 512M
        ini_set('pdo_sqlsrv.client_buffer_max_kb_size','524288'); // Setting to 512M - for pdo_sqlsrv
        ini_set('max_execution_time', 0); //para que no limite a 30 segundos la consulta
        ini_set('max_execution_time', 0); //para que no limite a 30 segundos la consulta
        $sp = "ConsultaContrato ?,?,?,?,?,? "; 
        $params = array(
        'idSalario'         => intval($idsalario),
        'idRepresentante'   => intval($idrepresentante),
        'idHorario'         => intval($idhorario),
        'idEmpleado'        => intval($idempleado),
        'idEmpresa'         => intval($idempresa),
        'idDescanso'        => intval($iddescanso),
        );
        
        $result = $this->db->query($sp,$params);

        //print_r($result->result_array());
        //die();
        return  $result->result_array();
    }

}

?>









