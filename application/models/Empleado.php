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
       $filas = $result->result_array();
       // para la leyenda "última actualización" de la pantalla (las del job salen del historial del Agente)
       $u = $this->session->userdata('login');
       $this->db->query("INSERT INTO dbo.EmpleadosMicrosipSinc (origen, usuario, total) VALUES ('boton', ?, ?)",
                        array(isset($u['user']->usuario) ? $u['user']->usuario : null, isset($filas[0]['TOTAL']) ? (int) $filas[0]['TOTAL'] : null));
       return $filas;
   }

   /** Última actualización del catálogo desde Microsip: la más reciente entre el job de cada hora y el botón. */
   public function ultimaSinc(){
       return $this->db->query(
           "SELECT TOP 1 CONVERT(VARCHAR(19), fecha, 120) AS fecha, origen, DATEDIFF(MINUTE, fecha, GETDATE()) AS minutos
            FROM (
                SELECT msdb.dbo.agent_datetime(h.run_date, h.run_time) AS fecha, 'job' AS origen
                FROM msdb.dbo.sysjobhistory h JOIN msdb.dbo.sysjobs j ON j.job_id = h.job_id
                WHERE j.name = N'Nominas - Empleados Microsip' AND h.step_id = 0 AND h.run_status = 1
                UNION ALL
                SELECT fecha, origen FROM dbo.EmpleadosMicrosipSinc
            ) x ORDER BY fecha DESC")->row_array();
   }

}

?>