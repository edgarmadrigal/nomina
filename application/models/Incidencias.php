<?php
//MODELO horarios
class Incidencias extends CI_Model{

    function ___construct(){
        parent::___construct();
		//$this->load->library('form_validation');
    }

    
    /**
     * $empresa limita las incidencias a esa empresa (los números de empleado se repiten entre
     * empresas; sin este filtro, las vacaciones de Microsip de un homónimo en otra empresa
     * aparecían en la persona equivocada). Se incluyen las de empresa NULL (capturas antiguas).
     */
    public function consultaIncidencias($id, $empresa = null){
        $this->db->select('i.id
        ,i.empleado_id
        ,i.concepto_id
        ,i.fechaInicio
        ,i.fechaFin
        ,i.estatus
        ,i.usuarioBaja_id
        ,i.fechaBaja,c.descripcion,c.descripcionCorta,i.origenClave
        ,i.cubreEntrada,i.cubreEntradaComedor,i.cubreSalidaComedor,i.cubreSalida');
        $this->db->from('incidencias i');
        $this->db->join('conceptos c', 'i.concepto_id = c.id')
        ->where('i.empleado_id',$id);
        $this->db->where('i.estatus','1');
        if ($empresa !== null && $empresa !== '' && (int)$empresa > 0) {
            $this->db->where('(i.empresa_id = ' . (int)$empresa . ' OR i.empresa_id IS NULL)');
        }
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

    public function consultaEmpleado($id, $empresa = null){
        $this->db->select('REG_IMSS, FECHA_INGRESO, empresa_id, NOMBRE_COMPLETO')
        ->from('empleados')
        ->where('empleado_id',$id);
        if ($empresa !== null && $empresa !== '' && (int)$empresa > 0) {
            $this->db->where('empresa_id', (int)$empresa);
        }
        return $this->db->get()->result();
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