<?php
//MODELO horarios
class HorarioUsuario extends CI_Model{

    function ___construct(){
        parent::___construct();
		//$this->load->library('form_validation');
    }

    public function consultaPlanta(){
        $usuarios = $this->db->select('id ,nombre')->distinct()
        ->from('empresas')
        ->where('estatus','1')
        ->get()
        ->result();                 
        return $usuarios;
    }
    public function consultaEmpleado($search){

        ini_set('memory_limit','512M'); // This also needs to be increased in some cases. Can be changed to a higher value as per need)
        ini_set('sqlsrv.ClientBufferMaxKBSize','524288'); // Setting to 512M
        ini_set('pdo_sqlsrv.client_buffer_max_kb_size','524288'); // Setting to 512M - for pdo_sqlsrv
        ini_set('max_execution_time', 0); //para que no limite a 30 segundos la consulta
        $usuarios = $this->db->select("empleado_id as id,empleado_id+'-'+CONCAT((NOMBRES),(' '),(APELLIDO_PATERNO),(' '),(APELLIDO_MATERNO)) as text")->distinct()
        ->from('empleados')
        ->where('estatus','A')
        ->like("empleado_id+' '+CONCAT((NOMBRES),(' '),(APELLIDO_PATERNO),(' '),(APELLIDO_MATERNO))",$search)
       // ->order_by("NOMBRE_COMPLETO", "asc")
        ->get()
        ->result_array();
        return $usuarios;
    }    
    public function consultaHorario($empleado_id,$idDia)
    {
        ini_set('max_execution_time', 0); //para que no limite a 30 segundos la consulta
        $sp = "ConsultaHorariosEmpleado ?,? " ; 
        $params = array(
            'empleado_id' => $empleado_id,
            'dia' => $idDia);
        $result = $this->db->query($sp,$params);
        return  $result->result_array();
    }

    function ActualizarHorariosEmpleado($id,$data){

        $this->db->where('id', $id)
              ->update('horarios_empleado', $data);
            return true;              
    }    

    public function AgregarHorariosEmpleado($data){   
        
        $d=true;        
        $this->db->insert('horarios_empleado',$data);
            
        return $d;
    }
    

    
    public function consultaHorarioEditar($id)
    {
        ini_set('max_execution_time', 0); //para que no limite a 30 segundos la consulta
        $sp = "ConsultaHorariosEmpleadoEdit ? " ; 
        $params = array(
            'id' => $id);
        $result = $this->db->query($sp,$params);
        return  $result->result_array();
    }

    
    public function consultaHorarioID($id)
    {
        ini_set('max_execution_time', 0); //para que no limite a 30 segundos la consulta
        $sp = "ConsultaHorariosID ? " ; 
        $params = array(
            'id' => $id);
        $result = $this->db->query($sp,$params);
        return  $result->result_array();
    }
    
    

    
    public function consultaHorarios()    {
        ini_set('max_execution_time', 0); //para que no limite a 30 segundos la consulta        
        $sp = "ConsultaHorarios";        
        $result = $this->db->query($sp);
        return  $result->result_array();
    }    
   

}

?>