<?php
//MODELO horarios
class Concepto extends CI_Model{

    function ___construct(){
        parent::___construct();
		//$this->load->library('form_validation');
    }

/**----------------------------------------------------------------------------  */

    public function consulta($id)
    {
        ini_set('max_execution_time', 0); //para que no limite a 30 segundos la consulta
        $sp = "ConsultaConceptos ?"; 
        $params = array(
        'id' => $id);
        
        $result = $this->db->query($sp,$params);
        return  $result->result_array();
    }
    /**----------------------------------------------------------------------------  */

    public function consultaClasificacion($id)
    {
        ini_set('max_execution_time', 0); //para que no limite a 30 segundos la consulta
        $sp = "ConsultaClasificacion ?"; 
        $params = array(
        'id' => $id);
        
        $result = $this->db->query($sp,$params);
        return  $result->result_array();
    }
/**----------------------------------------------------------------------------  */

    public function actualizaConceptos($id, $concepto , $descripcionCorta , $idClasificacion , $goceSueldo , $comentarios){
        ini_set('max_execution_time', 0); //para que no limite a 30 segundos la consulta
        $sp = "ActualizaConceptos ?,?,?,?,?,?"; 
        $params = array(
            'id' => $id,
            'concepto' => $concepto,
            'descripcionCorta' => $descripcionCorta,
            'idClasificacion' => $idClasificacion,
            'goceSueldo' => $goceSueldo,
            'comentarios' => $comentarios,        
        );
        
        $result = $this->db->query($sp,$params);
        return  $result->result_array();
    }

/**----------------------------------------------------------------------------  */
    function delete_concepto($id){
        $this->db->delete('conceptos',array('id' => $id));        
        return true;
    }
    
/**----------------------------------------------------------------------------  */
    function update_concepto($id,$data){

        $this->db->where('id', $id)
              ->update('conceptos', $data);
            return true;              
    }    
    /**----------------------------------------------------------------------------  */
    public function insert_concepto( $concepto,  $descripcionCorta,  $idClasificacion,  $goceSueldo,  $comentarios){   
        
       $horario =   [
        'descripcion' 	=> $concepto ,	
        'descripcionCorta' 		=> $descripcionCorta,	
        'idClasificacion' 		=> $idClasificacion,	
        'goceSueldo' 	=> $goceSueldo ,	
        'comentarios' 	=> $comentarios,	
        'status' 	=> 1,	
       ];   
       $d=true;        
       $this->db->insert('conceptos',$horario);
           
       return $d;
   }

}

?>