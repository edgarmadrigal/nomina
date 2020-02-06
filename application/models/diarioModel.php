<?php
//MODELO usuario
class diarioModel extends CI_Model{

    function ___construct(){
        parent::___construct();
		$this->load->library('form_validation');
    }
/**-------------------------------ConsultaReporteDiario---------------------------------------------  */


    public function consulta($empresa_id,$fecha)
    {        
        ini_set('memory_limit','512M'); // This also needs to be increased in some cases. Can be changed to a higher value as per need)
        ini_set('sqlsrv.ClientBufferMaxKBSize','524288'); // Setting to 512M
        ini_set('pdo_sqlsrv.client_buffer_max_kb_size','524288'); // Setting to 512M - for pdo_sqlsrv
        ini_set('max_execution_time', 0); //para que no limite a 30 segundos la consulta

        $sp = "ConsultaReporteDiario ?,? "; 
        $params = array(
        'FECHA' => $fecha,
        'empresa_id' => intval($empresa_id));
        
        $result = $this->db->query($sp,$params);
        return  $result->result_array();
    }


}

?>