<?php
//MODELO usuario
class Empleado extends CI_Model{

    function ___construct(){
        parent::___construct();
		$this->load->library('form_validation');
    }
/**----------------------------------------------------------------------------  */
    public function consulta($empresa_id)
    {   
        ini_set('memory_limit','512M'); // This also needs to be increased in some cases. Can be changed to a higher value as per need)
        ini_set('sqlsrv.ClientBufferMaxKBSize','524288'); // Setting to 512M
        ini_set('pdo_sqlsrv.client_buffer_max_kb_size','524288'); // Setting to 512M - for pdo_sqlsrv
        ini_set('max_execution_time', 0); //para que no limite a 30 segundos la consulta

        $this->db->select('e.id
        ,empleado_id
        ,NOMBRE_COMPLETO
        ,NOMBRE_PUESTO
        ,NOMBRE_DEPARTAMENTO
        ,e.ESTATUS');       
        $this->db->from('empleados e')->where('empresa_id',$empresa_id);
        $aResult = $this->db->get();
        if(!$aResult->num_rows() == 1)
        {
            return false;
        }
        return $aResult->result_array();
        
    }

   /** ------------------------------------------------------------------------ */     
  
   public function actualizar()
   {
    ini_set('memory_limit','512M'); // This also needs to be increased in some cases. Can be changed to a higher value as per need)
    ini_set('sqlsrv.ClientBufferMaxKBSize','524288'); // Setting to 512M
    ini_set('pdo_sqlsrv.client_buffer_max_kb_size','524288'); // Setting to 512M - for pdo_sqlsrv
       ini_set('max_execution_time', 0); //para que no limite a 30 segundos la consulta
       $sp = "ActualizaEmpleadosMicrosip ";       
       
       $result = $this->db->query($sp);
       return  $result->result_array();
   }

}

?>