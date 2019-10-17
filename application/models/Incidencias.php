<?php
//MODELO horarios
class Incidencias extends CI_Model{

    function ___construct(){
        parent::___construct();
		//$this->load->library('form_validation');
    }

    
    public function consultaIncidencias($id){
        $this->db->select('i.id
        ,i.empleado_id
        ,i.concepto_id
        ,i.fechaInicio
        ,i.fechaFin
        ,i.estatus
        ,i.usuarioBaja_id
        ,i.fechaBaja,c.descripcion,c.descripcionCorta');
        $this->db->from('incidencias i');
        $this->db->join('conceptos c', 'i.concepto_id = c.id')
        ->where('i.empleado_id',$id);
        $this->db->where('i.estatus','1');
        $aResult = $this->db->get();

        if(!$aResult->num_rows() == 1)
        {
            return false;
        }

        return $aResult->result();
    }


    public function consultaConceptos(){        
        $conceptos = $this->db->select('id
        ,descripcion
        ,descripcionCorta
        ,idClasificacion
        ,goceSueldo
        ,comentarios
        ,status
        ,fechaBaja
        ,idUsuarioBaja')->distinct()
        ->from('conceptos')
        ->where('status','1')
        ->get()
        ->result();                 
        return $conceptos;
    }
    
    public function consultaDescripcionCorta($id){
        $conceptos = $this->db->select('
        descripcionCorta')->distinct()
        ->from('conceptos')
        ->where('id',$id)
        ->get()
        ->result();                 
        return $conceptos;

    }

    public function consultaEmpleado($id){
        $empleado = $this->db->select('REG_IMSS')
        ->from('empleados')
        ->where('empleado_id',$id)
        ->get()
        ->result();                 
        return $empleado;
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
    

    
    public function ConsultaIncidenciaEditar($id)
    {
        ini_set('max_execution_time', 0); //para que no limite a 30 segundos la consulta
        $sp = "ConsultaIncidenciaEditar ? " ; 
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
    
    public function ActualizarIncidencia($id,$data){
        $this->db->where('id', $id)
              ->update('incidencias', $data);
            return true;   
    }

    public function GuardarIncidencia($data){
        $d=true;        
        $this->db->insert('incidencias',$data);
            
        return $d;
    }
    
    public function consultaHorarios()    {
        ini_set('max_execution_time', 0); //para que no limite a 30 segundos la consulta        
        $sp = "ConsultaHorarios";        
        $result = $this->db->query($sp);
        return  $result->result_array();
    }    
   
}

?>