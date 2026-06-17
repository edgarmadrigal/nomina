<?php
//MODELO usuario
class Planta extends CI_Model{

    function ___construct(){
        parent::___construct();
    }

    public function consulta_Planta(){
        $empresas = $this->db->select('id,nombre')
        ->from('empresas')
        ->get()
        ->result();                 
        return $empresas;
    }   

    function get_planta($id){
        $planta = $this->db->select('*')
                                ->from('empresas')
                                ->where('id',$id)
                                ->get()
                                ->row();

        return $planta;
    }
    
    function delete_planta($id){
        $this->db->delete('empresas',array('id' => $id));
        
        return true;
    }
    
    function update_planta($id,$data){

        $this->db->where('id', $id)
              ->update('empresas', $data);
            return true;              
    }

    
    public function insert_planta($data){   
        
       $planta =   [
       'nombre'     =>  $data['nombre'],
       'telefono'   =>  $data['telefono'],
       'id_unidad'     =>  $data['id_unidad']
           ];   
       $d=true;
        
       $this->db->insert('empresas',$planta);
           
       return $d;
   }


}

?>