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
    
    /**
     * Todo el que está en el catálogo es eventual. Las checadas toman la planta al bajarse del reloj:
     * las que hizo antes de darse de alta quedaron en la planta del reloj. Se pasan a eventuales las de
     * los últimos 60 días, salvo las de una planta donde ese número es empleado activo (otra persona).
     */
    public function traerChecadas($numero){
        $donde = "FROM dbo.checadas ch
              WHERE ch.usuarioid_norm = ? AND ch.fecha >= ? AND ch.idPlanta <> 25
                AND NOT EXISTS (SELECT 1 FROM dbo.empleados e
                                 WHERE e.empleado_id = ? AND e.empresa_id = ch.idPlanta AND e.ESTATUS = 'A')";
        $p = array($numero, date('Y-m-d', strtotime('-60 days')), $numero);
        $r = $this->db->query("SELECT COUNT(*) AS n " . $donde, $p)->row_array();
        $n = $r ? (int) $r['n'] : 0;
        if ($n) $this->db->query("UPDATE ch SET idPlanta = 25 " . $donde, $p);
        return $n;
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