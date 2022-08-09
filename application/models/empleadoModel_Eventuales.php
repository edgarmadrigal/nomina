<?php
//MODELO empleado
class empleadoModel_Eventuales extends CI_Model{

    function ___construct(){
        parent::___construct();
		$this->load->library('form_validation');
    }
/**----------------------------------------------------------------------------  */

   /** ------------------------------------------------------------------------ */     
    public function consulta_empleados(){
        
        $empleado = $this->db->select('id,NUMERO,NOMBRE_COMPLETO,NOMBRE_PUESTO,NOMBRE_DEPARTAMENTO,ESTATUS')
                                ->from('empleados')
                                ->where('empresa_id',25)
                                ->get()
                                ->row();
        return $empleado;
    }

    public function consulta()
    {
        $this->db->select('id,NUMERO,NOMBRE_COMPLETO,NOMBRE_PUESTO,NOMBRE_DEPARTAMENTO,ESTATUS');
        $this->db->from('empleados');
        $this->db->where('empresa_id',25);
        $aResult = $this->db->get();
        if(!$aResult->num_rows() == 1)
        {
            return false;
        }
        return $aResult->result_array();
    }
    function get_empleado($id){
        $empleado = $this->db->select('id,NUMERO,NOMBRES,APELLIDO_PATERNO,APELLIDO_MATERNO,NOMBRE_PUESTO,NOMBRE_DEPARTAMENTO,ESTATUS')
                                ->from('empleados')
                                ->where('id',$id)
                                ->get()
                                ->row();
        return $empleado;
    }
    
    function delete_empleado($id){
        $this->db->where('id', $id)
        ->update('empleados', $data);
       // $this->db->delete('empleados',array('id' => $id));        
        return true;
    }
    
    function update_empleado($id,$data){

        $this->db->where('id', $id)
              ->update('empleados', $data);
            return true;              
    }    
    public function insert_empleado($data){   
        
       $empleado =   [
        'empresa_id'                   =>   25,
        'empleado_id'                  =>   $data['NUMERO'],   
        'NUMERO'                       =>   $data['NUMERO'],       
        'NO_BYTE'                      =>   0,
        'NOMBRE_COMPLETO'              =>   $data['NOMBRE_COMPLETO'],       
        'APELLIDO_PATERNO'             =>   $data['APELLIDO_PATERNO'],
        'APELLIDO_MATERNO'             =>   $data['APELLIDO_MATERNO'],
        'NOMBRES'                      =>   $data['NOMBRES'],
        'PUESTO_NO_ID'                 =>   '',
        'NOMBRE_PUESTO'                =>   $data['NOMBRE_PUESTO'],
        'DEPTO_NO_ID'                  =>   '',
        'NOMBRE_DEPARTAMENTO'          =>   $data['NOMBRE_DEPARTAMENTO'],
        'RFC'                          =>   '',   
        'REG_IMSS'                     =>   '',
        'FECHA_INGRESO'                =>   '',           
        'ESTATUS'                      =>   'A',
       ];        
       $x=$this->db->insert('empleados',$empleado);
       /*print_r($x);
           die();*/
       $d=true;   
       return $d;
   }

}

?>