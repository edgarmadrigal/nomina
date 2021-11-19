<?php
//MODELO usuario
class retencionesModel extends CI_Model{

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

        $sp = "ConsultaReporteRetenciones ?,? ";
        $params = array(
        'FechaInicio' => $fechaInicio,
        'FechaFin' => $fechaFin);
        
        $result = $this->db->query($sp,$params);
        
        return  $result->result_array();        
    }

/*
    public function consultaretenciones(){

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
        $this->db->from('retencionesMicro');
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